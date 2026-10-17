<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\Support\Morphs;

return new class extends Migration
{
    public function up(): void
    {
        // Rule 8(3): logs of processing. No free text and no personal data here.
        Schema::create('data_rights_processing_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            Morphs::add($table, 'subject');
            $table->string('purpose', 64);
            $table->string('activity', 120);
            $table->timestamp('occurred_at');

            $table->index('occurred_at', 'drd_processing_logs_occurred_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_processing_logs');
    }
};
