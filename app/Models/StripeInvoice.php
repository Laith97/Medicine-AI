<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StripeInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_invoice_id',
        'stripe_session_id',
        'invoice_type',
        'invoice_month',
        'invoice_year',
        'amount_due',
        'amount_paid',
        'status',
        'due_date',
        'grace_period_ends_at',
        'reminder_count',
        'last_reminder_sent_at',
        'auto_generated',
        'paid_at',
        'invoice_url',
        'invoice_pdf',
        'currency',
        'description',
        'line_items',
        'metadata',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'invoice_month' => 'integer',
        'invoice_year' => 'integer',
        'reminder_count' => 'integer',
        'auto_generated' => 'boolean',
        'due_date' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'line_items' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the invoice
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get invoice URL attribute (handle array case)
     */
    public function getInvoiceUrlAttribute($value)
    {
        try {
            // Handle null or empty values
            if (empty($value)) {
                return null;
            }
            
            // Handle array case
            if (is_array($value)) {
                return isset($value[0]) && is_string($value[0]) ? $value[0] : null;
            }
            
            // Handle serialized array case
            if (is_string($value) && (strpos($value, 'a:') === 0 || strpos($value, '[') === 0)) {
                try {
                    $unserialized = unserialize($value);
                    if (is_array($unserialized)) {
                        return isset($unserialized[0]) && is_string($unserialized[0]) ? $unserialized[0] : null;
                    }
                } catch (Exception $e) {
                    // If unserialize fails, try json_decode
                    try {
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            return isset($decoded[0]) && is_string($decoded[0]) ? $decoded[0] : null;
                        }
                    } catch (Exception $e) {
                        // If both fail, return the original value if it's a string
                        return is_string($value) ? $value : null;
                    }
                }
            }
            
            // Return as string if it's already a string
            return is_string($value) ? $value : null;
        } catch (Exception $e) {
            \Log::error('Error in getInvoiceUrlAttribute', [
                'invoice_id' => $this->id,
                'value_type' => gettype($value),
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get invoice PDF attribute (handle array case)
     */
    public function getInvoicePdfAttribute($value)
    {
        try {
            // Handle null or empty values
            if (empty($value)) {
                return null;
            }
            
            // Handle array case
            if (is_array($value)) {
                return isset($value[0]) && is_string($value[0]) ? $value[0] : null;
            }
            
            // Handle serialized array case
            if (is_string($value) && (strpos($value, 'a:') === 0 || strpos($value, '[') === 0)) {
                try {
                    $unserialized = unserialize($value);
                    if (is_array($unserialized)) {
                        return isset($unserialized[0]) && is_string($unserialized[0]) ? $unserialized[0] : null;
                    }
                } catch (Exception $e) {
                    // If unserialize fails, try json_decode
                    try {
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            return isset($decoded[0]) && is_string($decoded[0]) ? $decoded[0] : null;
                        }
                    } catch (Exception $e) {
                        // If both fail, return the original value if it's a string
                        return is_string($value) ? $value : null;
                    }
                }
            }
            
            // Return as string if it's already a string
            return is_string($value) ? $value : null;
        } catch (Exception $e) {
            \Log::error('Error in getInvoicePdfAttribute', [
                'invoice_id' => $this->id,
                'value_type' => gettype($value),
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convert the model instance to an array (ensure URLs are strings)
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Ensure URLs are always strings in array representation
        if (isset($array['invoice_url'])) {
            $array['invoice_url'] = $this->invoice_url; // This will use our accessor
        }
        if (isset($array['invoice_pdf'])) {
            $array['invoice_pdf'] = $this->invoice_pdf; // This will use our accessor
        }
        
        return $array;
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'open' && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Check if invoice is due soon (within 3 days)
     */
    public function isDueSoon(): bool
    {
        return $this->status === 'open' && 
               $this->due_date && 
               $this->due_date->isBetween(now(), now()->addDays(3));
    }

    /**
     * Get the outstanding amount
     */
    public function getOutstandingAmount(): float
    {
        return $this->amount_due - $this->amount_paid;
    }

    /**
     * Get formatted amount due
     */
    public function getFormattedAmountDue(): string
    {
        return '$' . number_format($this->amount_due, 2);
    }

    /**
     * Get formatted amount paid
     */
    public function getFormattedAmountPaid(): string
    {
        return '$' . number_format($this->amount_paid, 2);
    }

    /**
     * Get formatted outstanding amount
     */
    public function getFormattedOutstandingAmount(): string
    {
        return '$' . number_format($this->getOutstandingAmount(), 2);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'paid' => 'badge bg-success',
            'open' => $this->isOverdue() ? 'badge bg-danger' : 'badge bg-warning',
            'draft' => 'badge bg-secondary',
            'void' => 'badge bg-dark',
            'uncollectible' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Get human readable status
     */
    public function getHumanStatus(): string
    {
        return match($this->status) {
            'paid' => 'Paid',
            'open' => $this->isOverdue() ? 'Overdue' : 'Open',
            'draft' => 'Draft',
            'void' => 'Void',
            'uncollectible' => 'Uncollectible',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope for unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['open', 'draft']);
    }

    /**
     * Scope for paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'open')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope for due soon invoices
     */
    public function scopeDueSoon($query)
    {
        return $query->where('status', 'open')
                    ->whereBetween('due_date', [now(), now()->addDays(3)]);
    }

    /**
     * Scope for monthly invoices
     */
    public function scopeMonthly($query)
    {
        return $query->where('invoice_type', 'monthly');
    }

    /**
     * Scope for invoices past grace period
     */
    public function scopePastGracePeriod($query)
    {
        return $query->where('status', 'open')
                    ->whereNotNull('grace_period_ends_at')
                    ->where('grace_period_ends_at', '<', now());
    }

    /**
     * Check if invoice is a monthly invoice
     */
    public function isMonthlyInvoice(): bool
    {
        return $this->invoice_type === 'monthly';
    }

    /**
     * Check if invoice is past grace period
     */
    public function isPastGracePeriod(): bool
    {
        return $this->status === 'open' && 
               $this->grace_period_ends_at && 
               $this->grace_period_ends_at->isPast();
    }

    /**
     * Check if invoice needs a reminder
     */
    public function needsReminder(): bool
    {
        if ($this->status !== 'open' || !$this->isMonthlyInvoice()) {
            return false;
        }

        // If past grace period, check reminder frequency
        if ($this->isPastGracePeriod()) {
            $user = $this->user;
            $setting = $user->monthlyInvoiceSetting;
            
            if (!$setting) {
                return false;
            }

            // If no reminder sent yet, send one
            if (!$this->last_reminder_sent_at) {
                return true;
            }

            // Check if enough time has passed since last reminder
            return $this->last_reminder_sent_at
                ->addDays($setting->reminder_frequency_days)
                ->isPast();
        }

        return false;
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent(): void
    {
        $this->update([
            'reminder_count' => $this->reminder_count + 1,
            'last_reminder_sent_at' => now(),
        ]);
    }

    /**
     * Get formatted month/year
     */
    public function getFormattedPeriod(): string
    {
        if (!$this->invoice_month || !$this->invoice_year) {
            return '';
        }

        return Carbon::createFromDate($this->invoice_year, $this->invoice_month, 1)
            ->format('F Y');
    }

    /**
     * Get invoice type badge class
     */
    public function getTypeBadgeClass(): string
    {
        return match($this->invoice_type) {
            'monthly' => 'badge bg-primary',
            'subscription' => 'badge bg-info',
            'manual' => 'badge bg-secondary',
            default => 'badge bg-light',
        };
    }

    /**
     * Get human readable invoice type
     */
    public function getHumanType(): string
    {
        return match($this->invoice_type) {
            'monthly' => 'Monthly Invoice',
            'subscription' => 'Subscription',
            'manual' => 'Manual Invoice',
            default => ucfirst($this->invoice_type),
        };
    }
}
