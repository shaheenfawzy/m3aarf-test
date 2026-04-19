<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexCoursesRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CoursesController extends Controller
{
    public function index(IndexCoursesRequest $request): AnonymousResourceCollection
    {
        $categoryIds = CourseCategory::query()
            ->whereIn('name', $request->categories())
            ->pluck('id');

        $courses = Course::query()
            ->with('category')
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('total_views')
            ->get();

        return CourseResource::collection($courses);
    }
}
