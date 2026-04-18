<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Collection<int, Course> $courses
 */
class CourseCategory extends Model
{
    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
