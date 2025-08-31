<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'application_id',
        'transaction_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_details' => 'array',
    ];

    protected $appends = ['invoice'];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->transaction_id = $payment->transaction_id ?? uniqid('pay_');
            $payment->currency = $payment->currency ?? 'BDT';
        });
    }

    /**
     * Get the user that owns the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the application that the payment is for
     */
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the invoice data for the payment
     */
    public function getInvoiceAttribute()
    {
        return [
            'id' => $this->transaction_id,
            'user' => $this->user->name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'date' => $this->created_at->format('Y-m-d H:i:s'),
            'receipt_url' => $this->receipt_url,
            'items' => [
                [
                    'description' => 'Job Application Fee',
                    'amount' => $this->amount,
                ]
            ]
        ];
    }

    /**
     * Mark the payment as completed
     */
    public function markAsCompleted($paymentDetails = [])
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->payment_details = $paymentDetails;
        $this->save();

        // Update the associated application
        if ($this->application) {
            $this->application->markAsPaid();
        }

        return $this;
    }

    /**
     * Mark the payment as failed
     */
    public function markAsFailed($error = null)
    {
        $this->status = 'failed';
        $this->payment_details = array_merge(
            (array) $this->payment_details,
            ['error' => $error]
        );
        $this->save();

        return $this;
    }

    /**
     * Check if the payment is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Scope a query to only include completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
