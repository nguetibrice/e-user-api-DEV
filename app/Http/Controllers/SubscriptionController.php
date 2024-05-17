<?php

namespace App\Http\Controllers;

use App\Mail\CheckoutLinkGenerated;
use App\Models\User;
use App\Exceptions\ModelNotFoundException;
use App\Models\PaymentSession;
use App\Models\SubscriptionOrder;
use App\Services\Contracts\IOrangeMoneyService;
use App\Services\Contracts\IPaymentSessionService;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\ISubscriptionOrderService;
use App\Services\Contracts\ISubscriptionService;
use App\Services\Contracts\IUserService;
use App\Wallet\Stripe\Requests\TransactionRequest;
use App\Wallet\Stripe\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Status;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Stripe\Exception\ApiErrorException;
use App\Dtos\SubscriptionOrder as DtoSubscriptionOrder;
use App\Dtos\PaymentSession as DtoPaymentSession;
use App\Services\Contracts\ITransactionHistoryService;

class SubscriptionController extends Controller
{
    protected ISubscriptionService $subscriptionService;
    protected IUserService $userService;
    protected IOrangeMoneyService $omService;
    protected IPriceService $priceService;
    protected ISubscriptionOrderService $subscriptionOrderService;
    protected IPaymentSessionService $paymentSessionService;
    protected ITransactionHistoryService $transactionHistoryService;

    public function __construct(
        ISubscriptionService $subscriptionService,
        IUserService $userService,
        IOrangeMoneyService $omService,
        IPriceService $priceService,
        ISubscriptionOrderService $subscriptionOrderService,
        ITransactionHistoryService $transactionHistoryService,
        IPaymentSessionService $paymentSessionService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->userService = $userService;
        $this->omService = $omService;
        $this->priceService = $priceService;
        $this->subscriptionOrderService = $subscriptionOrderService;
        $this->paymentSessionService = $paymentSessionService;
        $this->transactionHistoryService = $transactionHistoryService;
    }

    /**
     * @param  Request $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws IncompletePayment
     * @throws ApiErrorException
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_name' => 'required|string',
                'price_id' => 'required|string',
                'quantity' => 'required|numeric|min:1',
                'currency' => 'required_if:payment_method.type,OM|string',
                'payment_method' => 'required|array',
                'payment_method.type' => 'required|string|in:card,OM',
                'payment_method.card' => 'required_if:payment_method.type,card|array',
                'payment_method.card.number' => 'required_if:payment_method.type,card|string|min:16|max:16',
                'payment_method.card.exp_month' => 'required_if:payment_method.type,card|numeric|min:1|max:12',
                'payment_method.card.exp_year' => 'required_if:payment_method.type,card|numeric|min:1',
                'payment_method.card.cvc' => 'required_if:payment_method.type,card|numeric|min:1',
                'address' => 'required|array',
                'address.country' => 'required|string',
                'address.line1' => 'required|string',
                'address.line2' => 'required|string',
                'address.postal_code' => 'required|string',
                'address.state' => 'required|string',
                'redirect_url' => 'required_if:payment_method.type,OM',

            ]);

            $start_date = new \DateTime("now");
            $user = $this->userService->getUserFromGuard();

            // a user with no email or no phone number should not be able to make a payment
            if (!$user->getContactDetail()) {
                return Response::error(Lang::get('Invalid contact details'), Status::HTTP_FORBIDDEN);
            }

            $type = $request->input('payment_method')['type'];
            $priceId = $request->input('price_id');
            $name = $request->input('product_name');
            $quantity = $request->input('quantity');
            $price = $this->priceService->getPrice($priceId);
            switch ($type) {
                case 'card':
                    $card = $request->input('payment_method')['card'];
                    $address = $request->input('address');
                    $user->createOrGetStripeCustomer(); //in case user is not yet stripe customer
                    $paymentMethod =
                    $this->subscriptionService->createStripePaymentMethod($user, $type, $card, $address);
                    if (!$user->hasDefaultPaymentMethod()) {
                        $user->updateDefaultPaymentMethod($paymentMethod);
                    }
                    $result =
                    $this->subscriptionService->makePayment($user, $name, $priceId, $quantity, $paymentMethod);
                    // if ($user->referrer != null) {
                    //     $referrer = $this->userService->findOneById($user->referrer);
                    //     if ($referrer != null) {
                    //         $referrer_customer = verifyCustomer($referrer);
                    //         if (isset($price["currency_options"])
                    //             && isset($price["currency_options"][$referrer_customer["currency"]])
                    //         ) {
                    //             $commission = 0.1
                    //             * $price["currency_options"][$referrer_customer["currency"]]["unit_amount"];
                    //             processRecharge(
                    //                 $referrer,
                    //                 $commission,
                    //                 $referrer_customer["currency"],
                    //                 $type = "CREDIT",
                    //                 $this->transactionHistoryService,
                    //                 $this->userService,
                    //                 $paymentMethod
                    //             );
                    //             $trx = new TransactionRequest(
                    //                 "credit",
                    //                 $referrer_customer["id"],
                    //                 $commission,
                    //                 $referrer_customer["currency"]
                    //             );
                    //             $response = (new Wallet(
                    //                 $this->transactionHistoryService,
                    //                 $this->userService,
                    //             ))->credit($trx);
                    //             Log::info(
                    //                 "REFERRER_ACCOUNT_CREDITED",
                    //                 [
                    //                     "transaction" => $trx,
                    //                     "commission" => $commission,
                    //                     "referrer_customer" => $referrer_customer,
                    //                     "referrer" => $referrer,
                    //                     "price" => $price,
                    //                     "response" => $response,
                    //                 ]
                    //             );
                    //         } else {
                    //             Log::error(
                    //                 "PRICE DOES NOT HAVE MATCHING CURRENCY WITH REFERRER",
                    //                 [
                    //                     "referrer" => $referrer,
                    //                     "user" => $user,
                    //                     "price" => $price,
                    //                 ]
                    //             );
                    //         }
                    //     }
                    // }
                    $end_date = new \DateTime("now");
                    Log::info(
                        "PAYMENT_CARD_SUCCESSFUL:",
                        [
                            "start_date" => $start_date,
                            "end_date" => $end_date,
                            "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                            "request" => $request->input(),
                            "paymentMethod" => $paymentMethod,
                            "result" => $result
                        ]
                    );
                    return Response::success(['subscription' => $result]);
                    break;
                case 'OM':
                    if (!isset($price["currency_options"][strtolower($request->currency)])) {
                        return Response::error("Amount in desired currency not found", 400);
                    }
                    if (!isset($price["currency_options"][strtolower($request->currency)])) {
                        return Response::error("Currency not tierred", 400);
                    }

                    $amount = 0;
                    if ($quantity >= 10) {
                        foreach ($price["currency_options"][strtolower($request->currency)]["tiers"] as $tier) {
                            if ($tier["up_to"] == null) {
                                $amount = $tier["unit_amount"];
                            }
                        }
                    } else {
                        $nearest = null;
                        foreach ($price["currency_options"][strtolower($request->currency)]["tiers"] as $tier) {
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
                    $order_dto = new DtoSubscriptionOrder(
                        $user->id,
                        $name,$quantity,
                        $request->price_id,
                        strtoupper($request->currency),
                        "SUBSCRIPTION PAYMENT"
                    );
                    $order = $this->subscriptionOrderService->addSubscriptionOrder($order_dto);
                    $ref = UuidV4::uuid4()->toString();

                    $data = [
                        "currency" => strtoupper($request->currency),
                        "order_id" => $ref,
                        "amount" => $amount,
                        // "return_url" => "https://e-user-dev.languelite.com/dashboard?payment_status=1",
                        // "cancel_url" => "https://e-user-dev.languelite.com/dashboard?payment_status=-1",
                        // "notif_url" => "https://e-user-dev.languelite.com/api/v1/payment/callback?uuid=". $ref,
                        "return_url" => $request->redirect_url."/dashboard?payment_status=1",
                        "cancel_url" => $request->redirect_url."/dashboard?payment_status=-1",
                        "notif_url" => env('APP_URL')."/api/v1/payment/callback?uuid=". $ref,
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
                        $priceId,
                        "SUBSCRIPTION",
                        "ORANGE_MONEY",
                        $amount,
                        "XAF",
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
                            "start_date" => $start_date,
                            "end_date" => $end_date,
                            "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                            "request" => $request->input(),
                            "session" => $session
                        ]),
                        [
                            "results" => $results,
                            "data" => $data,
                            "order" => $order,
                            "start_date" => $start_date,
                            "end_date" => $end_date,
                            "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                            "request" => $request->input(),
                            "session" => $session
                        ]
                    );
                    return Response::success($results["data"], 201);
                    break;
                default:
                    $end_date = new \DateTime("now");
                    Log::error(
                        "Unknown Payment Method:",
                        [
                            "start_date" => $start_date,
                            "end_date" => $end_date,
                            "overall_processed_time" => date_diff($start_date, $end_date)->format('%s')." seconds",
                            "request" => $request->input(),
                        ]
                    );
                    return Response::error("Unknown Payment Method", 400, ["request" => $request->input()]);
                    break;
            }
        }catch(\Stripe\Exception\CardException $e) {
            Log::error(
                "INVALID CARD INPUT:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return Response::error("INVALID CARD INPUT", 400, ["trace" => $e->getTrace()]);
        } catch (\Stripe\Exception\RateLimitException $e) {
            Log::error(
                "RATE LIMIT EXCEPTION:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return Response::error("RATE LIMIT EXCEPTION", 400, ["trace" => $e->getTrace()]);
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
            return Response::error("INVALID REQUEST EXCEPTION", 400, ["trace" => $e->getTrace()]);
            // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Exception\AuthenticationException $e) {
            Log::error(
                "SOMETHING WENT WRONG:".
                json_encode([
                    "error" => $e->getError()->toArray(),
                    "status" => $e->getHttpStatus(),
                    "body" => $e->getHttpBody(),
                ])
            );
            return Response::error("INVALID REQUEST EXCEPTION", 400, ["trace" => $e->getTrace()]);
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
            return Response::error("API CONNECTION EXCEPTION", 400, ["trace" => $e->getTrace()]);
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
            return Response::error("API ERROR EXCEPTION", 400, ["trace" => $e->getTrace()]);
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
            return Response::error("SOMETHING WENT WRONG", 400, ["trace" => $th->getTrace()]);
        }
    }

    /**
     * @param  Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function getPackages(Request $request): JsonResponse
    {
        $this->validate(
            $request,
            ['currency' => 'sometimes|string|min:3|max:3']
        );

        $currency = $request->input('currency') ?? env('CASHIER_CURRENCY');

        // make sure the request always has a currency value
        $request->request->set('currency', $currency);

        // get all packages from stripe
        $packages = $this->subscriptionService->findPackagesByCurrency($currency);

        return Response::success(['packages' => $packages]);
    }

    /**
     * Gets the subscriptions of a user.
     *
     * @param  Request  $request
     * @param  int  $id The user ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscriptionsOfUser(Request $request, $user_id): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $this->userService->findOneById((int) $user_id);

        if ($request->has('onlySponsored')) {
            $subscriptions = $this->subscriptionService->getSponsoredSubscriptions($user);
        } else {
            $subscriptions = $this->subscriptionService->getSubscriptionsOfUser($user);
        }

        return Response::success([
            'subscriptions' => array_map(function ($subscription) {
                $stripeSubscription = $subscription->asStripeSubscription();
                $subscription->ends_at = date('Y-m-d H:i:s', $stripeSubscription->current_period_end);
                return $subscription;
            }, $subscriptions)
        ]);
    }

    public function checkout(Request $request) : JsonResponse
    {
        $request->validate([
            'product_name' => 'required|string',
            'price_id' => 'required|string',
            'quantity' => 'required|numeric|min:1',
            'redirect_url' => 'required|string',
        ]);

        // $user = User::find(3);
        $user = $this->userService->getUserFromGuard();
        $link = $this->subscriptionService->createCheckout(
            $user,
            $request->product_name,
            $request->price_id,
            $request->quantity,
            $request->redirect_url
        );

        Mail::to($user)->send(new CheckoutLinkGenerated($user->first_name, $link->url));
        return Response::success($link, 201);
    }
}
