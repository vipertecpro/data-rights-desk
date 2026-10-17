<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\Support\Morphs;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_notice_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('notice_id')->constrained('data_rights_notices')->cascadeOnDelete();
            Morphs::add($table, 'subject');
            $table->string('channel', 16);
            $table->timestamp('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_notice_deliveries');
    }
};
