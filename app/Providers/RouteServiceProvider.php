<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::group(['prefix' => 'api', 'namespace' => $this->namespace], function() {

                // Auth
                Route::prefix('auth')
                    ->group(base_path('routes/modules/auth.php'));

                Route::group(['middleware' => 'auth:api'], function() {
                    // Activity Logs
                    Route::prefix('activity_logs')
                        ->group(base_path('routes/modules/activity_log.php'));

                    // Clients
                    Route::prefix('clients')
                        ->group(base_path('routes/modules/client.php'));

                    // Documents
                    Route::prefix('documents')
                        ->group(base_path('routes/modules/document.php'));

                    // Groups
                    Route::prefix('groups')
                        ->group(base_path('routes/modules/group.php'));

                    // Nationalities
                    Route::prefix('nationalities')
                        ->group(base_path('routes/modules/nationality.php'));

                    // Payments
                    Route::prefix('payments')
                        ->group(base_path('routes/modules/payment.php'));

                    // Rates
                    Route::prefix('rates')
                        ->group(base_path('routes/modules/rate.php'));

                    // Services
                    Route::prefix('services')
                        ->group(base_path('routes/modules/service.php'));

                    // Service Transactions
                    Route::prefix('service_transactions')
                        ->group(base_path('routes/modules/service_transaction.php'));

                    // Transactions
                    Route::prefix('transactions')
                        ->group(base_path('routes/modules/transaction.php'));

                    // Users
                    Route::prefix('users')
                        ->group(base_path('routes/modules/user.php'));
                            
                });
    
            });

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
