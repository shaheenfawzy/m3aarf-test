<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $playlist_id
 * @property-read string $title
 * @property-read string $description
 * @property-read string $thumbnail
 * @property-read string $channel
 * @property-read int|null $video_count
 * @property-read int|null $total_duration_seconds
 * @property-read int|null $total_views
 * @property-read int $category_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read CourseCategory|null $category
 */
class Course extends Model
{
    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }
}
