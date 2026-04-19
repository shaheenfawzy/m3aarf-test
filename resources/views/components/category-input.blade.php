<div
    class="category-input d-flex flex-wrap align-items-center gap-2"
    @click="$refs.input.focus()"
>
    <template x-for="(tag, index) in formTags" :key="tag">
        <span class="category-chip d-inline-flex align-items-center gap-2"
            :class="{ 'category-chip-selected': formSelectAll }">
            <span x-text="tag"></span>
            <button
                type="button"
                class="btn-chip-remove"
                @click.stop="removeTag(index)"
                aria-label="حذف"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </span>
    </template>

    <input
        type="text"
        x-ref="input"
        x-model="formDraft"
        @keydown="handleTagKey($event)"
        @paste="pasteTags($event)"
        @blur="commitDraft()"
        class="category-input-field flex-grow-1"
    >

    <button
        type="button"
        class="btn-clear-all"
        x-show="formTags.length"
        @click.stop="clearTags()"
        x-cloak
        aria-label="مسح الكل"
    >
        <i class="bi bi-x-circle-fill"></i>
    </button>
</div>
