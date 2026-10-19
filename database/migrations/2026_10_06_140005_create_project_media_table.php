<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->enum('type', ['photo', 'video', 'music']);
            $table->string('slot_key'); // e.g. photo_01..15, video_01..03, music_main
            
            $table->string('file_path');
            $table->string('file_url');
            $table->string('original_name')->nullable();
            $table->string('title')->nullable(); // custom photo/video title
            $table->text('caption')->nullable(); // custom photo caption
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Unique slot key per project
            $table->unique(['project_id', 'slot_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_media');
    }
};
