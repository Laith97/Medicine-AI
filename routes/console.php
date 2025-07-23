<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\CreateMonthlyInvoices;
use App\Jobs\SendInvoiceNotifications;
use App\Jobs\SyncStripeInvoices;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Invoice management commands
Artisan::command('invoices:generate-monthly', function () {
    $this->info('Generating monthly invoices...');
    CreateMonthlyInvoices::dispatch();
    $this->info('Monthly invoice generation job dispatched.');
})->purpose('Generate monthly invoices for all users');

Artisan::command('invoices:send-notifications', function () {
    $this->info('Sending invoice notifications...');
    SendInvoiceNotifications::dispatch();
    $this->info('Invoice notification job dispatched.');
})->purpose('Send due soon and overdue invoice notifications');

Artisan::command('invoices:sync', function () {
    $this->info('Syncing invoice statuses with Stripe...');
    SyncStripeInvoices::dispatch();
    $this->info('Invoice sync job dispatched.');
})->purpose('Sync invoice statuses with Stripe');
