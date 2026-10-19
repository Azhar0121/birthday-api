<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('templates')->onDelete('restrict');
            $table->foreignId('template_version_id')->constrained('template_versions')->onDelete('restrict');
            
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            
            // Dynamic Greeting Payload
            $table->string('recipient_name');
            $table->integer('age')->nullable();
            $table->date('birthday_date')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('short_message')->nullable();
            $table->text('letter_content')->nullable();
            $table->text('closing_message')->nullable();
            $table->json('custom_settings')->nullable(); // custom colors, font choices, etc.
            
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
