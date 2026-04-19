import Alpine from 'alpinejs';

window.Alpine = Alpine;

const PER_PAGE = 8;

Alpine.data('coursesPage', () => ({
    categories: [],
    categoryId: null,
    page: 1,
    courses: [],
    loading: false,
    discovering: false,
    error: null,

    formTags: [],
    formDraft: '',
    formSelectAll: false,

    init() {
        const { categories, categoryId, page } = this.readUrl();

        this.categories = categories;
        this.formTags = [...categories];
        this.categoryId = categoryId;
        this.page = page;

        if (categories.length) {
            this.fetchCourses();
        }
    },

    readUrl() {
        const params = new URLSearchParams(window.location.search);
        const categories = (params.get('categories') ?? '')
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean);
        const categoryId = params.get('category_id');
        const page = Number.parseInt(params.get('page') ?? '1', 10);

        return {
            categories,
            categoryId: categoryId ? Number.parseInt(categoryId, 10) : null,
            page: Number.isFinite(page) && page > 0 ? page : 1,
        };
    },

    syncUrl() {
        const params = new URLSearchParams();

        if (this.categories.length) params.set('categories', this.categories.join(','));
        if (this.categoryId) params.set('category_id', String(this.categoryId));
        if (this.page > 1) params.set('page', String(this.page));

        const search = params.toString();
        const url = `${window.location.pathname}${search ? `?${search}` : ''}`;

        window.history.replaceState({}, '', url);
    },

    get filteredCourses() {
        if (! this.categoryId) return this.courses;

        return this.courses.filter((course) => course.category?.id === this.categoryId);
    },

    get totalPages() {
        return Math.max(1, Math.ceil(this.filteredCourses.length / PER_PAGE));
    },

    get pagedCourses() {
        const start = (this.page - 1) * PER_PAGE;

        return this.filteredCourses.slice(start, start + PER_PAGE);
    },

    get categoryCounts() {
        const counts = new Map();

        this.courses.forEach((course) => {
            const cat = course.category;

            if (! cat) return;

            const current = counts.get(cat.id) ?? { id: cat.id, name: cat.name, count: 0 };

            current.count += 1;
            counts.set(cat.id, current);
        });

        return [...counts.values()];
    },

    scrollToResults() {
        this.$nextTick(() => {
            this.$refs.results?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    },

    async fetchCourses() {
        if (! this.categories.length) {
            this.courses = [];
            return;
        }

        this.loading = true;
        this.error = null;

        try {
            const params = new URLSearchParams();

            this.categories.forEach((value) => params.append('categories[]', value));

            const response = await fetch(`/courses?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (! response.ok) throw new Error(`فشل تحميل الدورات (${response.status})`);

            const payload = await response.json();

            this.courses = payload.data ?? [];
        } catch (err) {
            this.error = err.message;
            this.courses = [];
        } finally {
            this.loading = false;
        }
    },

    async submit() {
        this.commitDraft();

        const tags = [...this.formTags];

        if (! tags.length) return;

        this.categories = tags;
        this.categoryId = null;
        this.page = 1;
        this.discovering = true;
        this.error = null;
        this.scrollToResults();

        try {
            const response = await fetch('/courses/discover', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ categories: tags }),
            });

            if (! response.ok) {
                const payload = await response.json().catch(() => ({}));

                throw new Error(payload.message ?? `فشلت العملية (${response.status})`);
            }

            this.syncUrl();
            await this.fetchCourses();
        } catch (err) {
            this.error = err.message;
        } finally {
            this.discovering = false;
        }
    },

    selectCategory(categoryId) {
        this.categoryId = categoryId;
        this.page = 1;
        this.syncUrl();
    },

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.page = page;
        this.syncUrl();
    },

    handleTagKey(event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'a' && this.formDraft === '' && this.formTags.length) {
            event.preventDefault();
            this.formSelectAll = true;
            return;
        }

        if (event.key === 'Backspace' && this.formSelectAll) {
            event.preventDefault();
            this.clearTags();
            return;
        }

        if (this.formSelectAll && event.key !== 'Meta' && event.key !== 'Control') {
            this.formSelectAll = false;
        }

        if (['Enter', ','].includes(event.key)) {
            event.preventDefault();
            this.commitDraft();
            return;
        }

        if (event.key === 'Backspace' && this.formDraft === '') {
            this.formTags.pop();
        }
    },

    commitDraft() {
        const value = this.formDraft.trim();

        this.formDraft = '';

        if (! value) return;
        if (this.formTags.includes(value)) return;

        this.formTags.push(value);
    },

    removeTag(index) {
        this.formTags.splice(index, 1);
    },

    clearTags() {
        this.formTags = [];
        this.formDraft = '';
        this.formSelectAll = false;
    },

    reset() {
        this.clearTags();
        this.categories = [];
        this.categoryId = null;
        this.page = 1;
        this.courses = [];
        this.error = null;
        this.syncUrl();
    },

    pasteTags(event) {
        const text = event.clipboardData?.getData('text') ?? '';

        if (! /[,\n\r]/.test(text)) return;

        event.preventDefault();

        text.split(/[,\n\r]+/).forEach((part) => {
            const value = part.trim();

            if (value && ! this.formTags.includes(value)) this.formTags.push(value);
        });
    },
}));

Alpine.start();
