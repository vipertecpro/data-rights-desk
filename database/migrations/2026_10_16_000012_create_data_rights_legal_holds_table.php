<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\Support\Morphs;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_legal_holds', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // A string id so a hold can point at app models (integer, UUID or ULID keys)
            // and at package records (ULID keys) alike.
            $table->string('holdable_type');
            $table->string('holdable_id', 64);
            $table->text('reason');
            $table->string('law_reference')->nullable();
            Morphs::add($table, 'placed_by', nullable: true);
            $table->timestamp('placed_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();

            $table->index(['holdable_type', 'holdable_id'], 'drd_legal_holds_holdable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_legal_holds');
    }
};
