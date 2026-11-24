<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_inquiries', function (Blueprint $table): void {
            $table->text('social_media_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quote_inquiries', function (Blueprint $table): void {
            $table->dropColumn('social_media_link');
        });
    }
};
