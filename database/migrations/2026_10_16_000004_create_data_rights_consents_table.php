<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only consent ledger (s.6(10)).
        Schema::create('data_rights_consents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->morphs('subject');
            $table->string('purpose', 64);
            $table->string('action', 16);
            $table->foreignUlid('notice_id')->nullable()->constrained('data_rights_notices')->nullOnDelete();
            $table->char('notice_hash', 64)->nullable();
            $table->string('locale', 12)->nullable();
            $table->string('channel', 32);
            $table->string('given_by', 32)->default('self');
            $table->ulid('guardian_consent_id')->nullable();
            $table->string('consent_manager')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('occurred_at', 6);
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id', 'purpose', 'occurred_at'], 'drd_consents_subject_purpose_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_consents');
    }
};
