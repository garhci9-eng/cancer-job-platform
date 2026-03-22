<?php
// database/migrations/2025_01_01_000001_create_platform_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── users (Laravel default + extras) ─────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email'); // user | mentor | admin
        });

        // ── user_profiles ─────────────────────────────────────────────────
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cancer_type')->nullable();
            $table->string('treatment_stage')->nullable(); // treatment_active | post_treatment | survivor
            $table->jsonb('treatment_details')->nullable();
            $table->string('work_capacity')->nullable();   // full_time | part_time | remote_only | flexible
            $table->jsonb('desired_job_types')->nullable();
            $table->string('previous_occupation')->nullable();
            $table->jsonb('skills')->nullable();
            $table->boolean('disability_registered')->default(false);
            $table->jsonb('preferred_regions')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_mentor')->default(false);
            $table->string('mentor_cancer_type')->nullable();
            $table->timestamps();
        });

        // ── job_listings ──────────────────────────────────────────────────
        Schema::create('job_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('company');
            $table->string('location');
            $table->string('job_type');          // remote | hybrid | onsite
            $table->string('employment_type');   // full_time | part_time | contract | freelance
            $table->text('description');
            $table->jsonb('required_skills')->nullable();
            $table->boolean('cancer_friendly')->default(true);
            $table->boolean('disability_preferred')->default(false);
            $table->boolean('flexible_hours')->default(false);
            $table->string('source_url')->nullable();
            $table->string('source')->nullable(); // saramin | jobkorea | internal
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // ── saved_jobs ────────────────────────────────────────────────────
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('job_listing_id');
            $table->foreign('job_listing_id')->references('id')->on('job_listings')->cascadeOnDelete();
            $table->unique(['user_id', 'job_listing_id']);
            $table->timestamps();
        });

        // ── chat_messages ─────────────────────────────────────────────────
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');    // user | assistant
            $table->text('content');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        // ── community_posts ───────────────────────────────────────────────
        Schema::create('community_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // story | question | mentor_request | job_tip
            $table->string('title');
            $table->text('content');
            $table->string('cancer_type')->nullable();
            $table->integer('likes')->default(0);
            $table->timestamps();
        });

        // ── community_replies ─────────────────────────────────────────────
        Schema::create('community_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->foreign('post_id')->references('id')->on('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_mentor_reply')->default(false);
            $table->timestamps();
        });

        // ── legal_guides ──────────────────────────────────────────────────
        Schema::create('legal_guides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category'); // employment_law | subsidy | disability | insurance
            $table->string('title');
            $table->text('summary');
            $table->text('content');    // full markdown
            $table->jsonb('tags')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });

        // ── audit_logs ────────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->string('ip')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('legal_guides');
        Schema::dropIfExists('community_replies');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('user_profiles');
    }
};
