<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'cancer_type',          // 암 종류 (예: 유방암, 갑상선암)
        'treatment_stage',      // treatment_active | post_treatment | survivor
        'treatment_details',    // jsonb: { surgery, chemo, radiation, immunotherapy }
        'work_capacity',        // full_time | part_time | remote_only | flexible
        'desired_job_types',    // jsonb array
        'previous_occupation',
        'skills',               // jsonb array
        'disability_registered', // boolean
        'preferred_regions',    // jsonb array
        'bio',
        'is_mentor',            // 멘토 활동 여부
        'mentor_cancer_type',   // 멘토링 가능한 암 종류
    ];

    protected $casts = [
        'treatment_details'   => 'array',
        'desired_job_types'   => 'array',
        'skills'              => 'array',
        'preferred_regions'   => 'array',
        'disability_registered' => 'boolean',
        'is_mentor'           => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns only non-sensitive fields safe for AI context window.
     */
    public function toPublicArray(): array
    {
        return [
            'cancer_type'          => $this->cancer_type,
            'treatment_stage'      => $this->treatment_stage,
            'work_capacity'        => $this->work_capacity,
            'desired_job_types'    => $this->desired_job_types,
            'previous_occupation'  => $this->previous_occupation,
            'skills'               => $this->skills,
            'disability_registered' => $this->disability_registered,
            'preferred_regions'    => $this->preferred_regions,
        ];
    }
}
