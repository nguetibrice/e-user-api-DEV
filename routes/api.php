<?php

use App\Dtos\Subscription as DtosSubscription;
use App\Events\SubscriptionAssigned;
use App\Events\SubscriptionCreated;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SponsoringController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodsController;
use App\Http\Controllers\WalletController;
use App\Models\Subscription;
use App\Models\SubscriptionAssignment;
use Illuminate\Support\Facades\Redis;
use Laravel\Cashier\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test-redis', [PaymentController::class, "test"]);
Route::get("/app-version",function (Request $request) {
    $version = json_decode(file_get_contents(base_path("composer.json")), true)["version"];
    return response(["version" => $version]);
});
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
Route::get('/test', [SubscriptionController::class, 'test']);
Route::prefix('/languages')->group(function () {
    Route::controller(LanguageController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/', 'store');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'delete');
    });
});

Route::prefix('/currencies')->group(function () {
    Route::controller(CurrencyController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        // Route::middleware(['auth:sanctum', 'verified'])->group(function () {
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        // });
    });
});

Route::prefix('/prices')->group(function () {
    Route::controller(PriceController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        // Route::middleware(['auth:sanctum', 'verified'])->group(function () {
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'delete');
        // });
    });
});

Route::prefix('/payment')->group(function () {
    Route::controller(PaymentController::class)->group(function () {
        Route::get('/callback', 'callBack');
        Route::post('/callback', 'callBack');
        Route::get('/test/{id}', 'test');
    });
});

Route::post('/user/register', [RegisterController::class, 'createAccount']);
Route::post('/user/activate-account', [RegisterController::class, 'activateAccount'])
    ->middleware('auth:sanctum');

Route::post('/user/login', [AuthController::class, 'openSession']);
Route::post('/user/logout', [AuthController::class, 'closeSession'])->middleware('auth:sanctum');

Route::controller(PasswordResetController::class)->group(function () {
    Route::post('/user/forgot-password', 'sendResetCodeEmail')->name('password.request');
    Route::post('/user/reset-password', 'resetPassword')->name('password.reset');
});

Route::post('/resent/user/activation_code', [RegisterController::class, 'resendActivationCode'])
    ->middleware('auth:sanctum');

Route::get('/subscription/packages', [SubscriptionController::class, 'getPackages']);
Route::post('/users/search', [UserController::class, 'find'])->middleware('auth:sanctum');

Route::controller(PaymentMethodsController::class)->prefix("payment-methods")->group(function () {
    Route::get('/', 'index')->name('get.all.pm');
    Route::post('/create', 'create')->name('create.pm');
    Route::get('/show/{id}', 'show')->name('show.pm');
    Route::put('/update/{id}', 'update');
    Route::delete('/delete/{id}', 'delete');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(WalletController::class)->prefix("wallet")->group(function () {
        Route::post('/recharge', 'recharge')->name('wallet.recharge');
        Route::post('/transfer', 'transfer')->name('wallet.transfer');
        Route::post('/pay-subscription', 'paySubscription')->name('wallet.subscription');
    });
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
    Route::get('/user/is/connect', [AuthController::class, 'userIsConnect']);
    Route::post('/subscription/create', [SubscriptionController::class, 'create'])->name('subscription.create');
    Route::get('/user/profile', [AuthController::class, 'showAuthUSer']);
    Route::get('/user/{id}/profile', [AuthController::class, 'getUser']);
    Route::post('/user/update', [AuthController::class, '']);
    Route::get('/users/{id}/subscriptions', [SubscriptionController::class, 'getSubscriptionsOfUser']);
    Route::post(
        '/users/{user_id}/subscriptions/{subscription_id}/assign',
        [SponsoringController::class, 'assignSubscription']
    );

    Route::controller(UserController::class)->group(function () {
        Route::get('/customers/visible/all', 'packageFind')->name('showall.user');
        Route::patch('/user/profile', 'updateProfile')->name('update_user');
        Route::patch('/user/password', 'updatePassword');
        Route::put('/user/guardian', 'updateGuardian');
        Route::delete('/user', 'destroy');
    });

    Route::middleware(['verifySponsor'])->group(function () {
        Route::controller(SponsoringController::class)->group(function () {
            Route::get('/sponsors/{sponsor_id}/godchildren', 'getAllGodchildren');
            Route::get('/sponsors/{sponsor_id}/subscriptions/{subscription_id}/godchildren', 'getGodchildren');
            Route::post('/sponsors/{sponsor_id}/subscriptions/{subscription_id}/deny', 'denySubscription');
        });
    });
});
