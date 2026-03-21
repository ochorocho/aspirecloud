<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, watch } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    packages: Array,
    filters: Object,
    pagination: Object,
    types: Array,
});

const search = ref(props.filters.q || '');
const selectedType = ref(props.filters.type);
let debounceTimer = null;

function performSearch() {
    router.get(route('extensions.index'), {
        q: search.value || undefined,
        type: selectedType.value,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: false,
    });
}

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(performSearch, 350);
});

watch(selectedType, () => {
    performSearch();
});

function goToPage(page) {
    router.get(route('extensions.index'), {
        q: search.value || undefined,
        type: selectedType.value,
        page,
    }, {
        preserveState: true,
        preserveScroll: false,
    });
}

function typeLabel(type) {
    const found = props.types.find(t => t.value === type);
    return found ? found.label : type;
}

function typeBadgeClass(type) {
    if (type.startsWith('typo3')) return 'bg-typo3-orange text-white';
    return 'bg-blue-500 text-white';
}
</script>

<template>
    <Head title="Extensions" />

    <!-- Hero Search -->
    <section class="bg-gradient-to-br from-typo3-dark to-gray-800 py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">
                Discover Extensions
            </h1>
            <p class="text-gray-300 mb-8 text-lg">
                Browse and search {{ pagination.total.toLocaleString() }} packages
            </p>
            <div class="relative max-w-2xl mx-auto">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search extensions by name, keyword, or description..."
                    class="w-full pl-12 pr-4 py-4 rounded-xl border-0 text-lg shadow-lg focus:ring-2 focus:ring-typo3-orange focus:outline-none"
                />
            </div>
        </div>
    </section>

    <!-- Filters & Results -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-8">
                    <h3 class="text-sm font-semibold text-typo3-dark uppercase tracking-wider mb-4">Package Type</h3>
                    <div class="space-y-2">
                        <label
                            v-for="type in types"
                            :key="type.value"
                            class="flex items-center cursor-pointer group"
                        >
                            <input
                                type="radio"
                                :value="type.value"
                                v-model="selectedType"
                                class="text-typo3-orange focus:ring-typo3-orange border-gray-300"
                            />
                            <span class="ml-2 text-sm text-gray-700 group-hover:text-typo3-dark">
                                {{ type.label }}
                                <span class="text-xs text-gray-400">({{ type.count?.toLocaleString() }})</span>
                            </span>
                        </label>
                    </div>
                </div>
            </aside>

            <!-- Results -->
            <div class="flex-1 min-w-0">
                <!-- Results count & info -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-sm text-gray-500">
                        Showing
                        <span class="font-medium text-typo3-dark">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span>
                        &ndash;
                        <span class="font-medium text-typo3-dark">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
                        of
                        <span class="font-medium text-typo3-dark">{{ pagination.total.toLocaleString() }}</span>
                        results
                    </p>
                </div>

                <!-- Package Cards -->
                <div v-if="packages.length > 0" class="space-y-4">
                    <Link
                        v-for="pkg in packages"
                        :key="pkg.id"
                        :href="route('extensions.show', { type: pkg.type, slug: pkg.slug })"
                        class="block bg-white rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 p-6 group"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-typo3-orange to-orange-400 flex items-center justify-center">
                                <span class="text-white font-bold text-lg">{{ (pkg.name || pkg.slug).charAt(0).toUpperCase() }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <h2 class="text-lg font-semibold text-typo3-dark group-hover:text-typo3-orange transition-colors truncate">
                                        {{ pkg.name || pkg.slug }}
                                    </h2>
                                    <span :class="typeBadgeClass(pkg.type)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium flex-shrink-0">
                                        {{ pkg.type }}
                                    </span>
                                </div>

                                <p v-if="pkg.description" class="text-gray-600 text-sm line-clamp-2 mb-3">
                                    {{ pkg.description }}
                                </p>

                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-400">
                                    <span v-if="pkg.latest_version" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        v{{ pkg.latest_version }}
                                    </span>
                                    <span v-if="pkg.authors && pkg.authors.length" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ pkg.authors[0].name }}
                                    </span>
                                    <span v-if="pkg.license" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        {{ pkg.license }}
                                    </span>
                                    <span v-if="pkg.releases_count" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        {{ pkg.releases_count }} {{ pkg.releases_count === 1 ? 'release' : 'releases' }}
                                    </span>
                                </div>

                                <!-- Tags -->
                                <div v-if="pkg.tags && pkg.tags.length" class="flex flex-wrap gap-1.5 mt-3">
                                    <span
                                        v-for="tag in pkg.tags.slice(0, 5)"
                                        :key="tag"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"
                                    >
                                        {{ tag }}
                                    </span>
                                    <span v-if="pkg.tags.length > 5" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">
                                        +{{ pkg.tags.length - 5 }} more
                                    </span>
                                </div>
                            </div>

                            <!-- Arrow -->
                            <div class="flex-shrink-0 text-gray-300 group-hover:text-typo3-orange transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No packages found</h3>
                    <p class="text-gray-500">Try adjusting your search or filters.</p>
                </div>

                <!-- Pagination -->
                <nav v-if="pagination.last_page > 1" class="flex items-center justify-center gap-1 mt-8">
                    <button
                        @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed text-gray-600 hover:bg-white hover:shadow-sm"
                    >
                        &larr; Previous
                    </button>

                    <template v-for="page in paginationPages" :key="page">
                        <span v-if="page === '...'" class="px-2 py-2 text-gray-400 text-sm">...</span>
                        <button
                            v-else
                            @click="goToPage(page)"
                            :class="[
                                'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                page === pagination.current_page
                                    ? 'bg-typo3-orange text-white shadow-sm'
                                    : 'text-gray-600 hover:bg-white hover:shadow-sm'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </template>

                    <button
                        @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed text-gray-600 hover:bg-white hover:shadow-sm"
                    >
                        Next &rarr;
                    </button>
                </nav>
            </div>
        </div>
    </section>
</template>

<script>
export default {
    computed: {
        paginationPages() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const pages = [];

            if (last <= 7) {
                for (let i = 1; i <= last; i++) pages.push(i);
                return pages;
            }

            pages.push(1);
            if (current > 3) pages.push('...');

            const start = Math.max(2, current - 1);
            const end = Math.min(last - 1, current + 1);
            for (let i = start; i <= end; i++) pages.push(i);

            if (current < last - 2) pages.push('...');
            pages.push(last);

            return pages;
        },
    },
};
</script>
