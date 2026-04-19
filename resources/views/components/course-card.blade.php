<article {{ $attributes->merge(['class' => 'course-card card h-100 border-0 position-relative']) }}>
    <a class="stretched-link"
        aria-label="فتح قائمة التشغيل على يوتيوب"
        :href="`https://www.youtube.com/playlist?list=${course.playlist_id}`"
        target="_blank"
        rel="noopener noreferrer"></a>

    <div class="thumb position-relative rounded-top overflow-hidden">
        <template x-if="course.thumbnail">
            <img :src="course.thumbnail" :alt="course.title" class="w-100 h-100 object-fit-cover">
        </template>

        <span class="badge bg-primary position-absolute top-0 start-0 m-2">
            <span x-text="course.video_count"></span> درس
        </span>
        <span class="badge bg-dark bg-opacity-75 position-absolute top-0 end-0 m-2">
            <i class="bi bi-collection-play-fill"></i>
        </span>
        <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 end-0 m-2"
              x-text="course.duration_formatted"></span>
    </div>

    <div class="card-body d-flex flex-column">
        <h3 class="h6 fw-bold mb-3 lh-base" x-text="course.title"></h3>

        <div class="mt-auto">
            <div class="d-flex align-items-center text-muted small text-truncate">
                <i class="bi bi-person me-1 flex-shrink-0"></i>
                <span class="text-truncate" x-text="course.channel"></span>
            </div>

            <hr class="my-2">

            <div class="d-flex justify-content-between align-items-center">
                <span class="badge rounded-pill bg-primary-subtle text-primary" x-text="course.category?.name"></span>
                <span class="text-muted small text-nowrap">
                    <i class="bi bi-eye me-1"></i><span x-text="course.views_formatted"></span> مشاهدة
                </span>
            </div>
        </div>
    </div>
</article>
