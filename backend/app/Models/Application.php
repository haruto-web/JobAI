<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Application
 *
 * @property int $id
 * @property int $user_id
 * @property int $job_id
 * @property string|null $status
 * @property \App\Models\User $user
 * @property \App\Models\Job $job
 */
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'status',
        'cover_letter',
        'resume_path',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * Scope a query to only include applications for a given employer's jobs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $employerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEmployerJobs($query, $employerId)
    {
        return $query->whereHas('job', function ($q) use ($employerId) {
            $q->where('user_id', $employerId);
        });
    }
}
