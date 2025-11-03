<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserProfile
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $bio
 * @property array|null $skills
 * @property string|null $experience_level
 * @property string|null $portfolio_url
 * @property string|null $resume_url
 * @property array|null $resumes
 * @property array|null $ai_analysis
 * @property int $last_ai_analysis
 * @property \App\Models\User $user
 */
class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'skills',
        'experience_level',
        'portfolio_url',
        'resume_url',
        'resumes',
        'ai_analysis',
        'extracted_experience',
        'extracted_education',
        'extracted_certifications',
        'extracted_languages',
        'resume_summary',
        'last_ai_analysis',
        'education_attainment',
    ];

    protected $casts = [
        'skills' => 'array',
        'resumes' => 'array',
        'ai_analysis' => 'array',
        'last_ai_analysis' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
