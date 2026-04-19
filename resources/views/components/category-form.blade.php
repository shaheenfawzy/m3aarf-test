<div class="card border-0 form-pull-up">
    <div class="card-body p-4 p-md-5">
        <div class="row g-4 align-items-stretch">
            <div class="col-12 col-lg-8 order-lg-1">
                <label class="form-label fw-semibold mb-2">
                    أدخل التصنيفات
                </label>
                <x-category-input />
                <small class="form-text text-muted mt-2 d-block">
                    اكتب تصنيفاً ثم اضغط Enter أو فاصلة لإضافته
                </small>
            </div>

            <div class="col-12 col-lg-4 order-lg-2 d-flex flex-column justify-content-center gap-3">
                <button type="button" class="btn btn-primary btn-lg w-100"
                    @click="submit()"
                    :disabled="discovering || formTags.length === 0">
                    <i class="bi bi-play-fill ms-1"></i>
                    <span x-text="discovering ? 'جارٍ الجمع...' : 'ابدأ الجمع'"></span>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-lg w-100"
                    @click="reset()"
                    :disabled="discovering || (formTags.length === 0 && categories.length === 0)">
                    <i class="bi bi-arrow-counterclockwise ms-1"></i>
                    إعادة تعيين
                </button>
            </div>
        </div>
    </div>
</div>
