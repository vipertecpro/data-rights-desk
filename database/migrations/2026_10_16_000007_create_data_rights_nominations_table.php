<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vipertecpro\DataRightsDesk\Support\Morphs;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_nominations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            Morphs::add($table, 'subject');
            $table->text('nominee_name');
            $table->text('nominee_contact');
            $table->text('relationship')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_nominations');
    }
};
