<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_breaches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('nature');
            $table->text('extent')->nullable();
            $table->text('location')->nullable();
            $table->text('likely_impact')->nullable();
            $table->text('facts')->nullable();
            $table->text('mitigation')->nullable();
            $table->text('findings')->nullable();
            $table->text('remedial')->nullable();
            $table->text('consequences_for_people')->nullable();
            $table->text('safety_steps')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('became_aware_at');
            $table->timestamp('board_alerted_at')->nullable();
            $table->timestamp('board_report_due_at');
            $table->timestamp('board_extension_until')->nullable();
            $table->timestamp('board_reported_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_breaches');
    }
};
