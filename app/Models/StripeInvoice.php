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
        'amount_due',
        'amount_paid',
        'status',
        'due_date',
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
        'due_date' => 'datetime',
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
}
