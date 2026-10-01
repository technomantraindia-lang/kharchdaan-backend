<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['slug', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
