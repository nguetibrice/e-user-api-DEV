<?php

namespace App\Providers;

use App\Models\Subscription;
use App\Services\Contracts\ICurrencyService;
use App\Services\LanguageService;
use App\Services\Contracts\IPriceService;
use App\Services\Contracts\ILanguageService;
use App\Services\Contracts\IOrangeMoneyService;
use App\Services\Contracts\IPaymentMethodService;
use App\Services\Contracts\IPaymentSessionService;
use App\Services\Contracts\IRechargeOrderService;
use App\Services\Contracts\ISubscriptionOrderService;
use Laravel\Cashier\Cashier;
use App\Services\Contracts\ISubscriptionService;
use App\Services\Contracts\ITransactionHistoryService;
use App\Services\Contracts\IUserLevelsService;
use App\Services\Contracts\IUserService;
use App\Services\CurrencyService;
use App\Services\OrangeMoneyService;
use App\Services\PaymentMethodService;
use App\Services\PaymentSessionService;
use App\Services\PriceService;
use App\Services\RechargeOrderService;
use App\Services\SubscriptionOrderService;
use App\Services\SubscriptionService;
use App\Services\TransactionHistoryService;
use App\Services\UserLevelsService;
use App\Services\UserService;
use App\Wallet\Contracts\WalletContract;
use App\Wallet\OrangeMoney\Wallet;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->app->isProduction()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        $this->app->singleton(IUserService::class, UserService::class);
        $this->app->singleton(ISubscriptionService::class, SubscriptionService::class);
        $this->app->singleton(ILanguageService::class, LanguageService::class);
        $this->app->singleton(ICurrencyService::class, CurrencyService::class);
        $this->app->singleton(IPriceService::class, PriceService::class);
        $this->app->singleton(IOrangeMoneyService::class, OrangeMoneyService::class);
        $this->app->singleton(IPaymentMethodService::class, PaymentMethodService::class);
        $this->app->singleton(ISubscriptionOrderService::class, SubscriptionOrderService::class);
        $this->app->singleton(IPaymentSessionService::class, PaymentSessionService::class);
        $this->app->singleton(ITransactionHistoryService::class, TransactionHistoryService::class);
        $this->app->singleton(IRechargeOrderService::class, RechargeOrderService::class);
        $this->app->singleton(IUserLevelsService::class, UserLevelsService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Cashier::useSubscriptionModel(Subscription::class);
    }
}
