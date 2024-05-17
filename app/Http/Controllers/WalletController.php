<?php

namespace App\Http\Controllers;

use App\Dtos\TransactionHistory;
use App\Services\Contracts\ISubscriptionService;
use App\Services\Contracts\ITransactionHistoryService;
use App\Services\Contracts\IUserService;
use App\Wallet\Stripe\Requests\TransactionRequest;
use App\Wallet\Stripe\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as Status;
use App\Dtos\SubscriptionOrder as DtoSubscriptionOrder;
use App\Dtos\PaymentSession as DtoPaymentSession;
use App\Dtos\RechargeOrder;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Contracts\IOrangeMoneyService;
use App\Services\Contracts\IPaymentSessionService;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\IRechargeOrderService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Stripe\Customer;
use Stripe\PaymentMethod as StripePaymentMethod;

class WalletController extends Controller
{

    protected ISubscriptionService $subscriptionService;
    protected IUserService $userService;
    protected ITransactionHistoryService $transactionHistoryService;
    protected IOrangeMoneyService $omService;
    protected IPaymentSessionService $paymentSessionService;
    protected IRechargeOrderService $rechargeOrderService;
    protected IPriceService $priceService;
    public function __construct(
        ISubscriptionService $subscriptionService,
        IUserService $userService,
        IOrangeMoneyService $omService,
        ITransactionHistoryService $transactionHistoryService,
        IRechargeOrderService $rechargeOrderService,
        IPriceService $priceService,
        IPaymentSessionService $paymentSessionService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->userService = $userService;
        $this->transactionHistoryService = $transactionHistoryService;
        $this->omService = $omService;
        $this->priceService = $priceService;
        $this->paymentSessionService = $paymentSessionService;
        $this->rechargeOrderService = $rechargeOrderService;
    }
    public function recharge(Request $request)
    {
        try {
            $request->validate([
                'currency' => 'required',
                'amount' => 'required',
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

            $user = $this->userService->getUserFromGuard();
            //
            $payment_method = $request->payment_method;
            $currency = env("CASHIER_CURRENCY");
            if (strtoupper($currency) == strtoupper($request->currency)) {
                $amount = (int) $request->amount;
            } else {
                $amount = round(convertCurrency($request->currency, (int) $request->amount));
            }
            switch ($payment_method["type"]) {
                case 'card':
                    $card = $request->payment_method["card"];
                    $card["exp_month"] = (int) $card["exp_month"];
                    $card["exp_year"] = (int) $card["exp_year"];
                    $paymentMethod = $this->subscriptionService->createStripePaymentMethod($user, "card", $card);
                    $trx = processRecharge(
                        $user,
                        $amount,
                        $currency,
                        "CREDIT",
                        $this->transactionHistoryService,
                        $this->userService,
                        $paymentMethod
                    );
                    if ($trx->getError() != null) {
                        return Response::error("Erreur Lors de la recharge",400,['error' => $trx->getError()]);
                    }
                    $customer = verifyCustomer($user);
                    return Response::success(['balance' => strtoupper($customer["default_currency"])." " . (-1 * ( (int) $trx->getBalance())) ]);
                    break;
                case 'OM':
                    $order_dto = new RechargeOrder(
                        $user->id,
                        "RECHARGE",
                        $request->amount,
                        $request->currency,
                        "RECHARGE PAYMENT"
                    );
                    $order = $this->rechargeOrderService->addRechargeOrder($order_dto);

                    $ref = UuidV4::uuid4()->toString();

                    if (strtolower($request->currency) == "xaf") {
                        $amount = (int) $request->amount;
                    } else {
                        // todo: convert amount to xaf
                        $amount = round(convertCurrency("xaf", $request->amount));
                        // $amount = $request->amount;
                    }

                    $data = [
                        "currency" => "XAF",
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
                            "PAYMENT_ORANGE_MONEY: Unable to create payment:". json_encode(["results" => $results, "data" => $data]),
                            ["results" => $results, "data" => $data]
                        );
                        return Response::error(json_encode($results['error']), 400);
                    }
                    $session_dto = new DtoPaymentSession(
                        $ref,
                        $order->id,
                        null,
                        "RECHARGE",
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
                            "end_date" => $end_date,
                            "request" => $request->input(),
                            "session" => $session
                        ])
                    );
                    return Response::success($results["data"], 201);
                    break;

                default:
                    Log::error( "UNKNOWN PAYMENT METHOD:". $payment_method["type"] );
                    return Response::error("Unknown Payment Method");
                    break;
            }
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

    public function transfer(Request $request)
    {
        try {
            $request->validate([
                'target' => 'required',
                'amount' => 'required'
            ]);

            if ($request->target != null) {
                if ($this->userService->getUserByAlias($request->target) != null) {
                    $target = $this->userService->getUserByAlias($request->target);
                } elseif ($this->userService->getUserByEmail($request->target) != null) {
                    $target = $this->userService->getUserByEmail($request->target);
                } elseif ($this->userService->getUserByCip($request->target) != null) {
                    $target = $this->userService->getUserByCip($request->target);
                } elseif ($this->userService->getUserByPhone($request->target) != null) {
                    $target = $this->userService->getUserByPhone($request->target);
                } else {
                    return Response::error("Destinataire Inconnu, bien vouloir verifier que le compte du tuteur a bien ete cree", Status::HTTP_BAD_REQUEST);
                }
            }


            $user = $this->userService->getUserFromGuard();
            $customer = verifyCustomer($user);
            if ($customer["balance"] >= 0) {
                return Response::error("Solde Insuffisant", Status::HTTP_BAD_REQUEST);
            }

            $target_customer = verifyCustomer($target);
            // we assume that all transfer transactions will be done in the default system settings
            // if (strtoupper($target_customer["default_currency"]) == $customer["default_currency"]) {
            //     $amount = $request->amount;
            // } else {
            //     // convert amount here to user default currency
            //     $amount = $request->amount; //review
            // }
            $amount = (int) $request->amount;
            if (($customer["balance"] * -1) < $amount) {
                return Response::error("Solde Insuffisant", Status::HTTP_BAD_REQUEST);
            }

            $debit = processRecharge(
                $user,
                $amount,
                env("CASHIER_CURRENCY"),
                "DEBIT",
                $this->transactionHistoryService,
                $this->userService,
            );
            if ($debit->getError() != null) {
                return Response::error("Erreur Lors de la recharge",400,['error_debit' => $debit->getError()]);
            }
            // Log response

            // if ($target_customer["default_currency"] != null && strtoupper($target_customer["default_currency"]) != $customer["default_currency"]) {
            //     // convert amount here to target default currency
            //     $amount = $request->amount; //review
            // }
            $credit = processRecharge(
                $user,
                $amount,
                env("CASHIER_CURRENCY"),
                "TRANSFER",
                $this->transactionHistoryService,
                $this->userService,
                null,
                $target
            );

            if ($credit->getError() != null) {
                return Response::error("Erreur Lors de la recharge",400,['error_credit' => $credit->getError()]);
            }
            // Log response
            return Response::success([
                "balance" => strtoupper($customer["default_currency"])." " . (-1 * ( (int) $credit->getBalance())) ,
                "message" => "Transfer Successful"
            ]);


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

    public function paySubscription(Request $request)
    {
        try {
            $request->validate([
                'product_name' => 'required|string',
                'price_id' => 'required|string',
                'quantity' => 'required|numeric|min:1',

            ]);
            $user = $this->userService->getUserFromGuard();
            $customer = verifyCustomer($user);
            $quantity = $request->quantity;
            $price = $this->priceService->getPrice($request->price_id);
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
            if ($customer["balance"] >= 0 ) {
                // negative wallet balance
                return Response::error("Solde Insuffisant", Status::HTTP_BAD_REQUEST);
            }
            if (($customer["balance"] * -1) < ($amount * $quantity)) {
                return Response::error("Solde Insuffisant", Status::HTTP_BAD_REQUEST);
            }

            $subscription = $this->subscriptionService->makePayment(
                $user,
                $request->product_name,
                $request->price_id,
                $request->quantity
            );
            $customer = verifyCustomer($user);
            return Response::success([
                'subscription' => $subscription,
                'balance' => strtoupper($customer["default_currency"])." " . (-1 * ( (int) $customer["balance"])),
            ]);
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
}
