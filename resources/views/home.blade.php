@extends('layouts.app')

@section('content')
    <div x-data="coursesPage()" x-cloak>
        <x-hero />

        <main class="container py-4 py-md-5">
            <x-category-form />

            <div x-ref="results">
                <template x-if="error">
                    <div class="alert alert-danger mt-4" x-text="error"></div>
                </template>

                <section class="row g-4 mt-5" x-show="loading || discovering"
                    x-transition:leave="transition-fade"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0">
                    <template x-for="i in 8" :key="i">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <x-skeleton-card />
                        </div>
                    </template>
                </section>

                <template x-if="! loading && ! discovering && categories.length === 0">
                    <x-empty-state />
                </template>

                <template x-if="! loading && ! discovering && categories.length > 0 && courses.length === 0">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search display-4 d-block mb-3"></i>
                        <p class="mb-0">لم يتم العثور على دورات</p>
                    </div>
                </template>

                <section class="mt-5" x-show="! loading && ! discovering && courses.length > 0"
                    x-transition:enter="transition-fade"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100">
                    <x-results-header />

                    <x-category-tabs class="mb-4" />

                    <div class="row g-4">
                        <template x-for="course in pagedCourses" :key="course.id">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <x-course-card />
                            </div>
                        </template>
                    </div>

                    <x-pagination class="mt-5" />
                </section>
            </div>
        </main>
    </div>
@endsection
