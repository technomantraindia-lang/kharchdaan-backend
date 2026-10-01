<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inquiries')) {
            Schema::create('inquiries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone');
                $table->string('email');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->longText('msg');
                $table->enum('status', ['pending', 'contacted', 'closed'])->default('pending');
                $table->longText('note')->nullable();
                $table->timestamps();
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            });
        }

        if (! Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('image');
                $table->string('link')->nullable();
                $table->integer('sort_order')->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('img')->nullable();
                $table->integer('rating')->default(5);
                $table->longText('msg');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->longText('answer');
                $table->integer('sort_order')->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content');
                $table->string('seo_title')->nullable();
                $table->text('seo_desc')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('model')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->longText('chg')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('inquiries');
    }
};
