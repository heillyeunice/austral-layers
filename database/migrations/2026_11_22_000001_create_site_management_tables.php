<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('status')->index();
            $table->json('keywords');
            $table->text('description');
            $table->text('work');
            $table->json('images');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->json('images')->nullable();
            $table->json('order_links')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150);
            $table->string('company', 100)->nullable();
            $table->string('service', 100);
            $table->string('quantity', 100);
            $table->text('message');
            $table->json('inspiration_files')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('quote_inquiries');
        Schema::dropIfExists('products');
        Schema::dropIfExists('portfolio_projects');
    }
};
