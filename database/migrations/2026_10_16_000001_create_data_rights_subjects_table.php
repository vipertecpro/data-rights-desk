<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_subjects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->morphs('subject');
            $table->string('age_status', 16)->nullable();
            $table->timestamp('age_declared_at')->nullable();
            // When staff changed the person's age answer, who did, and why (encrypted).
            $table->timestamp('age_changed_at')->nullable();
            $table->nullableMorphs('age_changed_by');
            $table->text('age_note')->nullable();
            $table->boolean('has_lawful_guardian')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('erasure_warned_at')->nullable();
            $table->timestamp('erasure_due_at')->nullable();
            $table->timestamp('erased_at')->nullable();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id'], 'drd_subjects_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_subjects');
    }
};
