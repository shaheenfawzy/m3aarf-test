<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

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
 * @property-read string $duration_formatted
 * @property-read string $views_formatted
 * @property-read CourseCategory|null $category
 */
class Course extends Model
{
    /**
     * @var list<string>
     */
    protected $appends = ['duration_formatted', 'views_formatted'];

    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $interval = CarbonInterval::seconds((int) $this->total_duration_seconds)->cascade();
                $interval->locale('ar');

                return $interval->forHumans(['parts' => 2, 'short' => false]);
            },
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function viewsFormatted(): Attribute
    {
        return Attribute::make(get: fn (): string =>
            (string) Number::abbreviate((int) $this->total_views, maxPrecision: 1),
        );
    }
}
