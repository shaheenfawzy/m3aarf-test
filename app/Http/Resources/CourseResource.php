<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'playlist_id' => $this->playlist_id,
            'title' => $this->title,
            'channel' => $this->channel,
            'thumbnail' => $this->thumbnail,
            'video_count' => $this->video_count,
            'total_duration_seconds' => $this->total_duration_seconds,
            'total_views' => $this->total_views,
            'duration_formatted' => $this->duration_formatted,
            'views_formatted' => $this->views_formatted,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ]),
        ];
    }
}
