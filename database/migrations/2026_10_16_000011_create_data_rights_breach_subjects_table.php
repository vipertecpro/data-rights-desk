<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\Support\Morphs;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_breach_subjects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('breach_id')->constrained('data_rights_breaches')->cascadeOnDelete();
            Morphs::add($table, 'subject');
            $table->timestamp('notified_at')->nullable();
            $table->string('channel', 32)->nullable();

            $table->unique(['breach_id', 'subject_type', 'subject_id'], 'drd_breach_subjects_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_breach_subjects');
    }
};
