<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_request_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('request_id')->constrained('data_rights_requests')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->nullableMorphs('actor');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_request_events');
    }
};
