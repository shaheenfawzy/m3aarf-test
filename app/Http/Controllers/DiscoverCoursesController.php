<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DiscoverCoursesRequest;
use App\Services\CourseDiscoveryService;
use Illuminate\Http\JsonResponse;

class DiscoverCoursesController extends Controller
{
    public function __invoke(DiscoverCoursesRequest $request, CourseDiscoveryService $service): JsonResponse
    {
        $categoryIds = $service->discover($request->categories());

        return response()->json(['category_ids' => $categoryIds]);
    }
}
