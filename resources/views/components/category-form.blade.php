<div class="card border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <label class="form-label fw-semibold mb-2">
            أدخل التصنيفات
        </label>
        <x-category-input />
        <small class="form-text text-muted mt-2 d-block">
            اكتب تصنيفاً ثم اضغط Enter أو فاصلة لإضافته
        </small>

        <div class="d-flex gap-2 mt-4">
            <button type="button" class="btn btn-primary btn-lg"
                @click="submit()"
                :disabled="loading || formTags.length === 0">
                <i class="bi bi-play-fill ms-1"></i>
                <span x-text="loading ? 'جارٍ الجمع...' : 'ابدأ الجمع'"></span>
            </button>
        </div>

        <template x-if="error">
            <div class="alert alert-danger mt-3 mb-0" x-text="error"></div>
        </template>
    </div>
</div>
