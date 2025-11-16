<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KioskPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'kiosk_session_id',
        'stripe_payment_intent',
        'amount',
        'currency',
        'status',
        'payment_metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payment_metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->currency)) {
                $payment->currency = 'USD';
            }
        });

        static::updating(function ($payment) {
            if ($payment->isDirty('status') && in_array($payment->status, ['succeeded', 'failed', 'cancelled', 'refunded'])) {
                if (empty($payment->processed_at)) {
                    $payment->processed_at = now();
                }
            }
        });
    }

    /**
     * Get the appointment for this payment
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the kiosk session for this payment
     */
    public function kioskSession()
    {
        return $this->belongsTo(KioskSession::class, 'kiosk_session_id', 'session_id');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing payments
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for succeeded payments
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for cancelled payments
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for refunded payments
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is succeeded
     */
    public function isSucceeded()
    {
        return $this->status === 'succeeded';
    }

    /**
     * Check if payment is failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Get amount in dollars
     */
    public function getAmountDollarsAttribute()
    {
        return $this->amount / 100;
    }

    /**
     * Get payment metadata value
     */
    public function getPaymentMetadata($key, $default = null)
    {
        return data_get($this->payment_metadata, $key, $default);
    }

    /**
     * Set payment metadata value
     */
    public function setPaymentMetadata($key, $value)
    {
        $data = $this->payment_metadata ?? [];
        data_set($data, $key, $value);
        $this->update(['payment_metadata' => $data]);
    }

    /**
     * Mark payment as succeeded
     */
    public function markAsSucceeded()
    {
        $this->update(['status' => 'succeeded']);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Mark payment as cancelled
     */
    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
    }
}
