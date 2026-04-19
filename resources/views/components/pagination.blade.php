<nav {{ $attributes->merge(['class' => 'd-flex justify-content-center']) }} aria-label="Pagination" x-show="totalPages > 1">
    <ul class="pagination pagination-pill gap-2 mb-0">
        <li class="page-item" :class="page === 1 ? 'disabled' : ''">
            <a class="page-link" href="#" @click.prevent="goToPage(page - 1)"><i class="bi bi-arrow-right"></i></a>
        </li>

        <template x-for="p in Math.min(4, totalPages)" :key="p">
            <li class="page-item" :class="p === page ? 'active' : ''">
                <a class="page-link" href="#" @click.prevent="goToPage(p)" x-text="p"></a>
            </li>
        </template>

        <template x-if="totalPages > 5">
            <li class="page-item disabled"><span class="page-link">…</span></li>
        </template>

        <template x-if="totalPages > 4">
            <li class="page-item" :class="totalPages === page ? 'active' : ''">
                <a class="page-link" href="#" @click.prevent="goToPage(totalPages)" x-text="totalPages"></a>
            </li>
        </template>

        <li class="page-item" :class="page === totalPages ? 'disabled' : ''">
            <a class="page-link" href="#" @click.prevent="goToPage(page + 1)"><i class="bi bi-arrow-left"></i></a>
        </li>
    </ul>
</nav>
