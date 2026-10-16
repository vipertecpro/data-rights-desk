<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_rights_notices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->unsignedInteger('version');
            $table->string('locale', 12);
            $table->longText('body');
            $table->char('hash', 64);
            $table->json('purposes');
            $table->timestamp('published_at');

            $table->unique(['version', 'locale'], 'drd_notices_version_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_rights_notices');
    }
};
