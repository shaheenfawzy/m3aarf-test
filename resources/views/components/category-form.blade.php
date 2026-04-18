<div class="card border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <label class="form-label fw-semibold mb-2">
            أدخل التصنيفات (مفصولة بفواصل)
        </label>
        <textarea class="form-control mb-3" rows="3"
            placeholder="مثال: لارافيل, فيو, تايلويند"
            x-model="rawCategories"></textarea>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-lg"
                @click="submit()"
                :disabled="loading || rawCategories.trim().length === 0">
                <i class="bi bi-play-fill ms-1"></i>
                <span x-text="loading ? 'جارٍ الجمع...' : 'ابدأ الجمع'"></span>
            </button>
        </div>

        <template x-if="error">
            <div class="alert alert-danger mt-3 mb-0" x-text="error"></div>
        </template>
    </div>
</div>
