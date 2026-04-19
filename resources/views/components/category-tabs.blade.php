<div {{ $attributes->merge(['class' => 'd-flex flex-wrap gap-2 justify-content-end']) }}>
    <button type="button"
        class="btn btn-sm rounded-pill"
        :class="categoryId === null ? 'btn-primary' : 'btn-outline-secondary'"
        @click="selectCategory(null)">
        الكل (<span x-text="courses.length"></span>)
    </button>

    <template x-for="cat in categoryCounts" :key="cat.id">
        <button type="button"
            class="btn btn-sm rounded-pill"
            :class="categoryId === cat.id ? 'btn-primary' : 'btn-outline-secondary'"
            @click="selectCategory(cat.id)">
            <span x-text="cat.name"></span> (<span x-text="cat.count"></span>)
        </button>
    </template>
</div>
