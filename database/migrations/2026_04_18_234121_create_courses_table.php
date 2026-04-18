<?php

use App\Models\CourseCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('playlist_id')->unique();
            $table->string('title', 1000);
            $table->text('description');
            $table->string('thumbnail');
            $table->string('channel');
            $table->unsignedInteger('video_count')->nullable();
            $table->unsignedInteger('total_duration_seconds')->nullable();
            $table->unsignedBigInteger('total_views')->nullable();
            $table->foreignIdFor(CourseCategory::class, 'category_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
