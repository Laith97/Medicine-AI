<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\Paginator;
use App\Models\StripeInvoice;

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
    }
}
