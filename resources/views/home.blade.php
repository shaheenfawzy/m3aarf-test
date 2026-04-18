@extends('layouts.app')

@section('content')
    <div x-data="coursesPage()" x-cloak>
        <x-hero />

        <main class="container py-4 py-md-5">
            <x-category-form />

            <template x-if="success">
                <div class="alert alert-success mt-4">
                    <i class="bi bi-check-circle-fill ms-1"></i>
                    تم جمع الدورات بنجاح
                </div>
            </template>
        </main>
    </div>
@endsection
