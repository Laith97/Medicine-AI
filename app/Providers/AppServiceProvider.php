<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Models\StripeInvoice;
use App\Models\DoctorBlogPost;
use App\Policies\BlogPostPolicy;
use App\Channels\SmsChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use custom pagination view for admin pages
        Paginator::defaultView('custom.pagination');
        Paginator::defaultSimpleView('custom.pagination');

        // Custom route model binding for StripeInvoice to ensure URLs are strings
        Route::bind('invoice', function ($value) {
            $invoice = StripeInvoice::findOrFail($value);

            // Force access to URL attributes to trigger accessors
            $invoice->invoice_url;
            $invoice->invoice_pdf;

            return $invoice;
        });

        // Route model binding for DoctorBlogPost
        Route::bind('post', function ($value) {
            return DoctorBlogPost::where('slug', $value)->orWhere('id', $value)->firstOrFail();
        });

        // Register BlogPost policy
        Gate::policy(DoctorBlogPost::class, BlogPostPolicy::class);

        // Register custom SMS notification channel
        Notification::extend('sms', function ($app) {
            return new SmsChannel($app->make(\App\Services\SmsService::class));
        });

        // Rate limiter for ambient chunk uploads per user
        RateLimiter::for('ambient-chunks', function ($request) {
            $key = optional($request->user())->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return [
                Limit::perMinute(60)->by($key), // 60 uploads/minute default
            ];
        });
    }
}
