<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Models\StripeInvoice;
use App\Models\DoctorBlogPost;
use App\Policies\BlogPostPolicy;

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
    }
}
