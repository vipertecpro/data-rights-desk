<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->nullableMorphs('subject');
            $table->string('type', 32);
            $table->string('status', 32);
            $table->text('details')->nullable();
            $table->text('response')->nullable();
            $table->json('result')->nullable();
            $table->string('channel', 16);
            $table->string('locale', 12);
            $table->timestamp('received_at');
            $table->timestamp('due_at');
            $table->timestamp('identity_verified_at')->nullable();
            $table->text('identity_method')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_at'], 'drd_requests_status_due_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_requests');
    }
};
