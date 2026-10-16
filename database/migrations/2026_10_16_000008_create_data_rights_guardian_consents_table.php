<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_guardian_consents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->morphs('subject');
            $table->string('kind', 32);
            $table->string('method', 32);
            $table->nullableMorphs('guardian');
            $table->text('guardian_name')->nullable();
            $table->text('verification_reference')->nullable();
            $table->timestamp('verified_at');
            $table->string('verified_by');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_guardian_consents');
    }
};
