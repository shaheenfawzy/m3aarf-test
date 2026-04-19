import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('coursesPage', () => ({
    formTags: [],
    formDraft: '',
    formSelectAll: false,
    loading: false,
    error: null,
    success: false,

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

    pasteTags(event) {
        const text = event.clipboardData?.getData('text') ?? '';

        if (! /[,\n\r]/.test(text)) return;

        event.preventDefault();

        text.split(/[,\n\r]+/).forEach((part) => {
            const value = part.trim();

            if (value && ! this.formTags.includes(value)) this.formTags.push(value);
        });
    },

    async submit() {
        this.commitDraft();

        if (! this.formTags.length) return;

        this.loading = true;
        this.error = null;
        this.success = false;

        try {
            const response = await fetch('/courses/discover', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ categories: [...this.formTags] }),
            });

            if (! response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message ?? `فشلت العملية (${response.status})`);
            }

            this.success = true;
        } catch (err) {
            this.error = err.message;
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();
