<button type="button"
    class="scroll-top"
    aria-label="العودة إلى الأعلى"
    x-data="{ shown: false }"
    x-init="window.addEventListener('scroll', () => shown = window.scrollY > 400, { passive: true })"
    x-show="shown"
    x-transition:enter="scroll-top-enter"
    x-transition:enter-start="scroll-top-enter-start"
    x-transition:enter-end="scroll-top-enter-end"
    x-transition:leave="scroll-top-leave"
    x-transition:leave-start="scroll-top-leave-start"
    x-transition:leave-end="scroll-top-leave-end"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })">
    <i class="bi bi-chevron-up"></i>
</button>
