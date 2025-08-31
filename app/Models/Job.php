<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'salary',
        'location',
        'type',
        'category',
        'status',
        'closing_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'salary' => 'decimal:2',
        'closing_date' => 'date',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'closing_date',
    ];

    /**
     * Get the company that owns the job.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Get the applications for the job.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Scope a query to only include active jobs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('closing_date')
                          ->orWhere('closing_date', '>=', now());
                    });
    }

    /**
     * Scope a query to only include featured jobs.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Check if the job is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->closing_date === null || $this->closing_date->isFuture());
    }

    /**
     * Close the job.
     */
    public function close(): void
    {
        $this->status = 'closed';
        $this->save();
    }

    /**
     * Get the count of applications for this job.
     */
    public function applicationCount(): int
    {
        return $this->applications()->count();
    }
}
