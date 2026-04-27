<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mt_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('provider', 20);
            $table->text('api_key_enc');
            $table->boolean('is_active')->default(true);
            $table->integer('usage_monthly_chars')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mt_configs');
    }
};
