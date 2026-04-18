import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('coursesPage', () => ({
    rawCategories: '',
    loading: false,
    error: null,
    success: false,

    async submit() {
        const categories = this.rawCategories
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean);

        if (! categories.length) return;

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
                body: JSON.stringify({ categories }),
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
