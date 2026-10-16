<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_retention_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->boolean('pretend')->default(false);
            $table->json('summary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_retention_runs');
    }
};
