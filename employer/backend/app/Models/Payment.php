<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int|null $application_id
 * @property int|null $employer_id
 * @property int|null $jobseeker_id
 * @property string $type
 * @property float $amount
 * @property string|null $status
 * @property \App\Models\Application $application
 * @property \App\Models\User $employer
 * @property \App\Models\User $jobseeker
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'employer_id',
        'jobseeker_id',
        'type',
        'amount',
        'description',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function jobseeker()
    {
        return $this->belongsTo(User::class, 'jobseeker_id');
    }
}
