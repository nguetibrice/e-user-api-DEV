<?php

namespace App\Http\Controllers;

use App\Models\PaymentSession;
use App\Dtos\PaymentSession as DtoPaymentSession;
use App\Dtos\SubscriptionOrder as DtosSubscriptionOrder;
use App\Mail\CompleteProfile;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Rules\CurrencyCode;
use App\Services\Contracts\IOrangeMoneyService;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\IUserService;
use App\Wallet\Stripe\Requests\TransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use App\Services\Contracts\ICurrencyService;
use App\Services\Contracts\IPaymentSessionService;
use App\Services\Contracts\IRechargeOrderService;
use App\Services\Contracts\ISubscriptionOrderService;
use App\Services\Contracts\ISubscriptionService;
use App\Services\Contracts\ITransactionHistoryService;
use App\Wallet\Stripe\Wallet;
use Faker\Core\Uuid;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Lang;
use Laravel\Cashier\Cashier;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Stripe\Customer;
use Stripe\PaymentMethod;

use function Deployer\timestamp;

class PaymentController extends Controller
{
    protected IOrangeMoneyService $omService;
    protected ISubscriptionService $subscriptionService;
    protected IUserService $userService;
    protected IPriceService $priceService;
    protected ISubscriptionOrderService $subscriptionOrderService;
    protected IPaymentSessionService $paymentSessionService;
    protected IRechargeOrderService $rechargeOrderService;
    protected ITransactionHistoryService $transactionHistoryService;

    public function __construct(
        IOrangeMoneyService $omService,
        ISubscriptionService $subscriptionService,
        IPriceService $priceService,
        IUserService $userService,
        ISubscriptionOrderService $subscriptionOrderService,
        IRechargeOrderService $rechargeOrderService,
        ITransactionHistoryService $transactionHistoryService,
        IPaymentSessionService $paymentSessionService
    ) {
        $this->omService = $omService;
        $this->subscriptionService = $subscriptionService;
        $this->userService = $userService;
        $this->priceService = $priceService;
        $this->subscriptionOrderService = $subscriptionOrderService;
        $this->paymentSessionService = $paymentSessionService;
        $this->rechargeOrderService = $rechargeOrderService;
        $this->transactionHistoryService = $transactionHistoryService;
    }

    /**
     * Verify payment
     */
    private function checkStatus($uuid) : array
    {
        try {
            $start_date = new \DateTime("now");
            $session = $this->paymentSessionService->getPaymentSessionByReference($uuid);
            if ($session == null) {
                Log::error("CHECK_STATUS: Unknown Payment Session");
                return ["error" => "Unknown Payment Session"];
            }
            if ($session->status != 0) {
                Log::error("CHECK_STATUS: Payment has already been treated");
                return ["error" => "Payment has already been treated"];//return different type of response
            }

            switch ($session->order_type) {
                case 'SUBSCRIPTION':
                    $order = $this->subscriptionOrderService->getSubscriptionOrder($session->order_id);
                    break;
                case 'RECHARGE':
                    $order = $this->rechargeOrderService->getRechargeOrder($session->order_id);
                    break;

                default:
                    Log::error("CHECK_STATUS: Unknown Order", ["session" => $session, "uuid" => $uuid]);
                    return ["error" => "Unknown Order"];
                    break;
            }

            switch ($session->payment_method) {
                case 'ORANGE_MONEY':
                    $results = $this->omService->checkStatus($session, $order);
                    if (isset($results["error"])) {
                        // log everything
                        Log::error(
                            "CHECK_STATUS: Unable to check status".
                            json_encode(["results" => $results, "session" => $session]),
                            [
                                "results" => $results,
                                "session" => $session,
                            ]
                        );
                        return ["error" => json_encode($results['error'])];
                    }
                    Log::info(
                        "CHECK_STATUS_SUCCESSFUL:". json_encode(["results" => $results, "session" => $session]),
                        ["results" => $results, "session" => $session]
                    );
                    switch ($results["data"]["status"]) {
                        case 'SUCCESS':
                            // credit stripe account
                            $user = $this->userService->getUserByAlias($order->user->alias);
                            $customer = verifyCustomer($user);
                            $amount =  $session->amount;
                            // $equiv = convertCurrency("xaf", 1);
                            // $amount = $equiv * $amount;
                            // $paymentMethod = $this->subscriptionService->createStripePaymentMethod($user, "om");
                            $transaction = processRecharge(
                                $user,
                                $amount,
                                env("CASHIER_CURRENCY"),
                                "CREDIT",
                                $this->transactionHistoryService,
                                $this->userService,
                                // $paymentMethod
                            );

                            if ($session->order_type == "SUBSCRIPTION") {
                                $subscription = $this->subscriptionService->makePayment(
                                    $user,
                                    $order->product_name,
                                    $session->price_id,
                                    $order->quantity,
                                    // $paymentMethod
                                );
                            }

                            $session->status = 1;
                            $session->transaction_id = $results["data"]["txnid"];
                            $session->save();
                            $order->status = 1;
                            $order->save();


                            // credit parrain
                            if ($user->referrer != null) {
                                $referrer = User::where("id", $user->referrer)->first();
                                if ($referrer != null) {
                                    $transaction = processRecharge(
                                        $referrer,
                                        $amount * (env("REFERRAL_PERCENTAGE") / 100),
                                        env("CASHIER_CURRENCY"),
                                        "CREDIT",
                                        $this->transactionHistoryService,
                                        $this->userService,
                                        // $paymentMethod
                                    );
                                }
                            }

                            $results = ["results" => $subscription];
                            $end_date = new \DateTime("now");
                            Log::info(
                                "STRIPE_SUBSCRIPTION_CREATED:". json_encode([
                                    "results" => $results,
                                    "customer" => $customer,
                                    "user" => $user,
                                    "product" => $order->product_name,
                                    "qty" => $order->quantity,
                                    "transaction" => $transaction,
                                    "amount" => $amount,
                                    "price_id" => $order->price_id,
                                    // "price" => $price,
                                    "start_date" => $start_date,
                                    "end_date" => $end_date,
                                    "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                                ])
                            );
                            break;
                        case 'FAILED':
                        case 'EXPIRED':
                            $session->status = -1;
                            $session->save();
                            $order->status = -1;
                            $order->save();
                            $end_date = new \DateTime("now");
                            Log::info(
                                "ORANGE_MONEY_PAYMENT_FAILED: OPERATION EXPIRED:". json_encode([
                                    "results" => $results,
                                    "session" => $session,
                                    "order" => $order,
                                    "start_date" => $start_date,
                                    "end_date" => $end_date,
                                    "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                                ])
                            );
                            $results = ["error" => "Payment Failed"];
                            break;
                        case 'INITIATED':
                        case 'PENDING':
                            //DO NOTHING
                            break;
                        default:
                            $end_date = new \DateTime("now");
                            Log::error(
                                "CHECK_STATUS: Unknown Payment Status",
                                [
                                    "results" => $results,
                                    "start_date" => $start_date,
                                    "end_date" => $end_date,
                                    "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                                ]
                            );
                            $results = ["error" => "Unknown Payment Status"];
                            break;
                    }
                    break;
                default:
                    $end_date = new \DateTime("now");
                    Log::error(
                        "CHECK_STATUS: Unknown Payment Method",
                        [
                            "session" => $session,
                            "start_date" => $start_date,
                            "end_date" => $end_date,
                            "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                        ]
                    );
                    $results = ["error" => "Unknown Payment Method"];
                    break;
            }

            return $results;
        } catch(\Stripe\Exception\CardException $e) {
            Log::error(
                "INVALID CARD INPUT:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return ["error" => "INVALID CARD INPUT"];
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error(
                "RATE LIMIT EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return ["error" => "RATE LIMIT EXCEPTION"];
            // Too many requests made to the API too quickly
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error(
                "INVALID REQUEST EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return ["error" => "INVALID REQUEST EXCEPTION"];
            // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error(
                "STRIPE AUTHENTICATION FAILED:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return ["error" => "STRIPE AUTHENTICATION FAILED"];
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error(
                "API CONNECTION EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return ["error" => "API CONNECTION EXCEPTION"];
            // Network communication with Stripe failed
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error(
                "API ERROR EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return ["error" => "API ERROR EXCEPTION"];
            // Display a very generic error to the user, and maybe send
            // yourself an email
        } catch (\Throwable $th) {
            Log::error(
                "SOMETHING WENT WRONG:".
                json_encode([
                    "exception" => $th->getMessage(),
                    "code" => $th->getCode(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                    "trace" => $th->getTrace(),
                ])
            );
            return ["error" => "SOMETHING WENT WRONG"];
        }

    }

    public function callBack(Request $request)
    {
        $request->validate([
            "uuid" => 'required'
        ]);
        return $this->checkStatus($request->uuid);
    }

    public function test()
    {
        try {
            // $start_date = new \DateTime("now");
            $uuid = "2aedf96b-e40a-47db-9a8d-384f34e7e0a0";
            $session = $this->paymentSessionService->getPaymentSessionByReference($uuid);
            $order = $this->subscriptionOrderService->getSubscriptionOrder($session->order_id);
            $session->status = 1;
            $session->transaction_id = UuidV4::uuid4()->toString();
            $session->save();
            $order->status = 1;
            $order->save();

            // credit stripe account
            $user = $this->userService->getUserByAlias($order->user->alias);
            $customer = verifyCustomer($user);
            $amount =  $session->amount;

            $paymentMethod = $this->subscriptionService->createStripePaymentMethod($user, "om");
            $transaction = processRecharge(
                $user,
                $amount,
                $customer["default_currency"],
                "CREDIT",
                $this->transactionHistoryService,
                $this->userService,
                $paymentMethod
            );

            Log::info(
                "STRIPE_CUSTOMER_ACCOUNT_CREDITED:". json_encode([
                    "transaction" => $transaction,
                    "customer" => $customer,
                    "amount" => $amount,
                    "price_id" => $order->price_id,
                    "user" => $user,
                ])
            );

            $customer = $user->asStripeCustomer();
            Log::info("CUSTOMER:". json_encode($customer));
            $amount =  $session->amount;
            // dd($user->paymentMethods());
            $paymentMethod = $this->subscriptionService->createStripePaymentMethod($user, "om");
            Log::info(
                "STRIPE_PAYMENT_METHOD_CREATED: ". json_encode([
                    "paymentMethod" => $paymentMethod,
                    "customer" => $customer,
                    "user" => $user,
                ])
            );
            $subscription = $this->subscriptionService->makePayment(
                $user,
                $order->product_name,
                $session->price_id,
                $order->quantity,
                $paymentMethod
            );

            return response(["status" => "OK", "subscription" => $subscription]);
        } catch(\Stripe\Exception\CardException $e) {
            Log::error(
                "INVALID CARD INPUT:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return response(["error" => "INVALID CARD INPUT"]);
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error(
                "RATE LIMIT EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return response(["error" => "RATE LIMIT EXCEPTION"]);
            // Too many requests made to the API too quickly
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error(
                "INVALID REQUEST EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return response(["error" => "INVALID REQUEST EXCEPTION"]);
            // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error(
                "STRIPE AUTHENTICATION FAILED:".
                json_encode([
                    "error" => $e->getError(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return response(["error" => "STRIPE AUTHENTICATION FAILED"]);
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            Log::error(
                "API CONNECTION EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return response(["error" => "API CONNECTION EXCEPTION"]);
            // Network communication with Stripe failed
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error(
                "API ERROR EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return response(["error" => "API ERROR EXCEPTION"]);
            // Display a very generic error to the user, and maybe send
            // yourself an email
        } catch (\Throwable $th) {
            Log::error(
                "SOMETHING WENT WRONG:".
                json_encode([
                    "exception" => $th->getMessage(),
                    "code" => $th->getCode(),
                    "file" => $th->getFile(),
                    "line" => $th->getLine(),
                    "trace" => $th->getTrace(),
                ])
            );
            return response(["error" => "SOMETHING WENT WRONG"]);
        }
    }

    public function quickPay(Request $request)
    {
        try {
            //code...
            $request->validate([
                "firstname" => 'required',
                "lastname" => 'sometimes',
                "email" => 'required',
                "phone" => 'sometimes',
                'product_name' => 'required|string',
                'price_id' => 'required|string',
                'quantity' => 'required|numeric|min:1',
                'payment_method' => 'required',
                'birthday' => 'required|date',
            ]);

            // if ($this->userService->getUserByEmail($request->input("email")) != null) {
            //     return Response::error("Un compte avec cet email existe deja", 400);
            // }
            $user = new User([
                'first_name' => $request->input('firstname'),
                'last_name' => $request->input('lastname'),
                'alias' => explode("@",$request->input("email"))[0],
                'phone' => str_replace(" ", "", $request->input('phone')),
                'email' => $request->input('email'),
                'password' => Hash::make("123456789"),
                'cip' => bin2hex(random_bytes(3)),
                'birthday' => $request->input('birthday'),
                'status' => 0,
            ]);
            $user = $this->userService->createAccount($user, false);

            switch ($request->input("payment_method")) {
                case 'card':
                    $res = $this->subscriptionService->createCheckout(
                        $user,
                        $request->product_name,
                        $request->price_id,
                        $request->quantity,
                        env("EUSER_APP_URL")."/complete-registration?cip=".$user->cip
                    );


                    break;
                case 'OM':
                    $price = $this->priceService->getPrice($request->price_id);
                    $quantity = $request->quantity;
                    $amount = 0;
                    if ($quantity >= 10) {
                        foreach ($price["currency_options"][strtolower(env("CASHIER_CURRENCY"))]["tiers"] as $tier) {
                            if ($tier["up_to"] == null) {
                                $amount = $tier["unit_amount"];
                            }
                        }
                    } else {
                        $nearest = null;
                        foreach ($price["currency_options"][strtolower(env("CASHIER_CURRENCY"))]["tiers"] as $tier) {
                            if ($tier["up_to"] != null) {
                                if ($tier["up_to"] >= $quantity) {
                                    // $amount = $tier["flat_amount"];
                                    if ($tier["up_to"] - $quantity < $nearest - $quantity || $nearest === null) {
                                        $amount = $tier["unit_amount"];
                                        $nearest = $tier["up_to"];
                                    }
                                }
                            }
                        }
                    }
                    $amount *=  $quantity;
                    $amount_xaf = round((int) convertCurrency("xaf", $amount));
                    $order_dto = new DtosSubscriptionOrder(
                        $user->id,
                        $request->product_name,
                        $quantity,
                        $request->price_id,
                        strtoupper(env("CASHIER_CURRENCY")),
                        "SUBSCRIPTION PAYMENT"
                    );
                    $order = $this->subscriptionOrderService->addSubscriptionOrder($order_dto);
                    $ref = UuidV4::uuid4()->toString();

                    $data = [
                        "currency" => "XAF",
                        "order_id" => $ref,
                        "amount" => $amount_xaf,
                        "return_url" => env("EUSER_APP_URL")."/complete-registration?cip=".$user->cip,
                        "cancel_url" => env("EUSER_APP_URL")."/complete-registration?cip=".$user->cip,
                        "notif_url" => "https://e-user-dev.languelite.com/api/v1/payment/callback?uuid=". $ref,
                        // "return_url" => $request->redirect_url."/dashboard?payment_status=1",
                        // "cancel_url" => $request->redirect_url."/dashboard?payment_status=-1",
                        // "notif_url" => env('APP_URL')."/api/v1/payment/callback?uuid=". $ref,
                        "lang" => "fr",
                        "reference" =>"DJEDSO".time(),
                    ];
                    $results = $this->omService->pay($data);
                    Log::info("PAYMENT_ORANGE_MONEY:", ["results" => $results]);
                    if (isset($results['error'])) {
                        // requests from e-user, data sent to orange, expected response from orange
                        Log::error(
                            "PAYMENT_ORANGE_MONEY: Unable to create payment:". json_encode(["results" => $results, "data" => $data, "order" => $order]),
                            ["results" => $results, "data" => $data, "order" => $order]
                        );
                        return Response::error(json_encode($results['error']), 400);
                    }
                    $session_dto = new DtoPaymentSession(
                        $ref,
                        $order->id,
                        $request->price_id,
                        "SUBSCRIPTION",
                        "ORANGE_MONEY",
                        $amount,
                        env("CASHIER_CURRENCY"),
                        $results["data"]["payment_url"],
                        $results["data"]["pay_token"],
                        $results["data"]["notif_token"]
                    );
                    $session = $this->paymentSessionService->addPaymentSession($session_dto);
                    $end_date = new \DateTime("now");
                    Log::info(
                        "PAYMENT_ORANGE_MONEY_SUCCESSFUL:"
                        .json_encode([
                            "results" => $results,
                            "data" => $data,
                            "order" => $order,
                            "end_date" => $end_date,
                            "request" => $request->input(),
                            "session" => $session
                        ])
                    );
                    $res = $results["data"];
                    break;
                default:
                    return Response::error("Mode de paiement inconnu", 400);
                    break;
            }
            Mail::to($user)->send(
                new CompleteProfile($user->first_name.' '.$user->last_name, env("EUSER_APP_URL")."/complete-registration?cip=".$user->cip)
            );
            return Response::success($res, 201);
        } catch (\Swift_TransportException $e) {
            // Handle issues related to mail transport (e.g., connection problems)
            Log::error('Email transport error on quickpay: ' . $e->getMessage());
            return Response::success([
                "response" => $res,
                "user_link" => env("EUSER_APP_URL")."/complete-registration?cip=".$user->cip
            ], 201);
            // ... (optional: notify admin, retry, etc.)
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Technical error on quickpay: ' . json_encode([
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]));

            return Response::error("Erreur Technique, veillez reessayer plus tard", 400);
        }

    }
}
