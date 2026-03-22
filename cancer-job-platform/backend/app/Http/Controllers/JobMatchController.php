<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Models\SavedJob;
use App\Models\UserProfile;
use App\Services\ClaudeService;
use Illuminate\Http\Request;

class JobMatchController extends Controller
{
    public function __construct(private ClaudeService $claude) {}

    /**
     * GET /api/jobs
     * Returns cancer-friendly jobs filtered + AI-scored for the user.
     */
    public function index(Request $request)
    {
        $user    = $request->user();
        $profile = UserProfile::where('user_id', $user->id)->first();

        $query = JobListing::where('cancer_friendly', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        // Filter by work capacity
        if ($profile?->work_capacity === 'remote_only') {
            $query->where('job_type', 'remote');
        } elseif ($profile?->work_capacity === 'part_time') {
            $query->where('employment_type', 'part_time');
        }

        // Filter by disability preference
        if ($profile?->disability_registered) {
            $query->orderByDesc('disability_preferred');
        }

        // Filter by region
        if ($profile?->preferred_regions) {
            $regions = $profile->preferred_regions;
            $query->where(function ($q) use ($regions) {
                foreach ($regions as $region) {
                    $q->orWhere('location', 'like', "%{$region}%");
                }
                $q->orWhere('job_type', 'remote');
            });
        }

        $jobs = $query->latest()->paginate(20);

        // AI match score (lightweight, cached per job per user)
        $scoredJobs = $jobs->getCollection()->map(function ($job) use ($profile) {
            $job->match_score = $this->computeMatchScore($job, $profile);
            return $job;
        })->sortByDesc('match_score')->values();

        $jobs->setCollection($scoredJobs);

        return response()->json($jobs);
    }

    public function show(Request $request, string $id)
    {
        $job     = JobListing::findOrFail($id);
        $profile = UserProfile::where('user_id', $request->user()->id)->first();

        // AI explanation of why this job fits
        $explanation = '';
        if ($profile) {
            $explanation = $this->claude->complete(
                system: "암환자 취업 상담 AI. 아래 일자리가 해당 사용자에게 적합한 이유를 2-3문장으로 간결하게 설명해 주세요. 한국어.",
                messages: [[
                    'role'    => 'user',
                    'content' => "사용자 프로필: " . json_encode($profile->toPublicArray(), JSON_UNESCAPED_UNICODE) .
                                 "\n일자리: " . json_encode([
                                     'title'           => $job->title,
                                     'company'         => $job->company,
                                     'job_type'        => $job->job_type,
                                     'employment_type' => $job->employment_type,
                                     'description'     => substr($job->description, 0, 500),
                                 ], JSON_UNESCAPED_UNICODE),
                ]],
                maxTokens: 256,
            );
        }

        return response()->json([
            'job'         => $job,
            'explanation' => $explanation,
        ]);
    }

    public function save(Request $request, string $id)
    {
        JobListing::findOrFail($id);
        SavedJob::firstOrCreate([
            'user_id'         => $request->user()->id,
            'job_listing_id'  => $id,
        ]);
        return response()->json(['saved' => true]);
    }

    public function saved(Request $request)
    {
        $jobs = JobListing::whereIn(
            'id',
            SavedJob::where('user_id', $request->user()->id)->pluck('job_listing_id')
        )->get();

        return response()->json(['jobs' => $jobs]);
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function computeMatchScore(JobListing $job, ?UserProfile $profile): int
    {
        if (!$profile) return 50;

        $score = 50;

        // Work capacity match
        $capacityMap = [
            'remote_only' => 'remote',
            'part_time'   => 'part_time',
        ];
        if (isset($capacityMap[$profile->work_capacity]) &&
            in_array($capacityMap[$profile->work_capacity], [$job->job_type, $job->employment_type])) {
            $score += 20;
        }

        // Disability preference bonus
        if ($profile->disability_registered && $job->disability_preferred) {
            $score += 15;
        }

        // Flexible hours bonus
        if ($job->flexible_hours) {
            $score += 10;
        }

        // Skills overlap
        $jobSkills     = $job->required_skills ?? [];
        $profileSkills = $profile->skills ?? [];
        $overlap       = count(array_intersect(
            array_map('strtolower', $jobSkills),
            array_map('strtolower', $profileSkills)
        ));
        $score += min($overlap * 5, 15);

        return min($score, 100);
    }
}
