<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('team_id')->index();
            $table->uuid('user_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('source_lang', 20);
            $table->string('target_lang', 20);
            $table->string('status', 20)->default('active');
            $table->jsonb('qa_config')->default('{}');
            $table->boolean('use_global_tm')->default(true);
            $table->string('mt_provider', 20)->nullable();
            $table->boolean('mt_prefill')->default(false);
            $table->integer('char_limit_per_segment')->nullable();
            $table->integer('char_limit_warning_pct')->default(90);
            $table->integer('tm_min_match_pct')->default(75);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
