<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'job_id',
        'cover_letter',
        'cv_path',
        'status',
        'reviewed_at',
        'reviewed_by',
        'payment_reference',
        'payment_amount',
        'payment_method',
        'payment_status',
        'payment_verified_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected $appends = ['cv_url'];

    /**
     * Get the user that owns the application
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job that the application is for
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get the payment associated with the application
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the URL to the CV file
     */
    public function getCvUrlAttribute()
    {
        return $this->cv_path ? asset('storage/' . $this->cv_path) : null;
    }

    /**
     * Check if the application has been paid for
     */
    public function isPaid()
    {
        return $this->payment && $this->payment->status === 'completed';
    }

    /**
     * Mark the application as paid
     */
    public function markAsPaid()
    {
        $this->status = 'paid';
        $this->save();
    }

    /**
     * Scope a query to only include pending applications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include accepted applications
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope a query to only include rejected applications
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Get the reviewer who reviewed the application
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if the application has been reviewed
     */
    public function isReviewed()
    {
        return $this->status !== 'pending';
    }
}
