<?php

declare(strict_types=1);

use App\Http\Controllers\DiscoverCoursesController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::post('/courses/discover', DiscoverCoursesController::class)
    ->middleware('throttle:5,1')
    ->name('courses.discover');
