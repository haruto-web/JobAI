<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Job
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $company
 * @property string|null $location
 * @property string|null $type
 * @property float|null $salary
 * @property array|null $requirements
 * @property int|null $user_id
 * @property \App\Models\User $user
 */
class Job extends Model
{
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'title',
        'description',
        'summary',
        'qualifications',
        'company',
        'location',
        'type',
        'salary',
        'requirements',
        'user_id',
        'urgent',
    ];

    protected $casts = [
        'requirements' => 'array',
        'salary' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'job_id');
    }
}
