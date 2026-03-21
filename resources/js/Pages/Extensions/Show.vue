<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    package: Object,
});

const activeTab = ref('overview');
const copied = ref(false);

const latestRelease = computed(() => {
    if (!props.package.releases || !props.package.releases.length) return null;
    return props.package.releases[0];
});

const composerName = computed(() => {
    const slug = props.package.slug;
    if (props.package.type === 'typo3-extension') {
        return slug.replace(/_/g, '-');
    }
    return slug;
});

const requiresEntries = computed(() => {
    if (!latestRelease.value?.requires) return [];
    return Object.entries(latestRelease.value.requires);
});

function copyComposerCommand() {
    const cmd = `composer require ${composerName.value}`;
    navigator.clipboard.writeText(cmd);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

function typeBadgeClass(type) {
    if (type.startsWith('typo3')) return 'bg-typo3-orange text-white';
    return 'bg-blue-500 text-white';
}

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'releases', label: 'Releases' },
];
</script>

<template>
    <Head :title="package.name || package.slug" />

    <!-- Package Header -->
    <section class="bg-gradient-to-br from-typo3-dark to-gray-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center mb-4">
                <Link :href="route('extensions.index', { type: package.type })" class="text-gray-400 hover:text-typo3-orange transition-colors text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to list
                </Link>
            </div>
            <div class="flex items-start gap-5">
                <div class="flex-shrink-0 w-16 h-16 rounded-xl bg-gradient-to-br from-typo3-orange to-orange-400 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-2xl">{{ (package.name || package.slug).charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
                            {{ package.name || package.slug }}
                        </h1>
                        <span :class="typeBadgeClass(package.type)" class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium">
                            {{ package.type }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-300">
                        <span v-if="latestRelease" class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            v{{ latestRelease.version }}
                        </span>
                        <span v-if="package.authors && package.authors.length" class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <template v-for="(author, i) in package.authors" :key="i">
                                <a v-if="author.url" :href="author.url" target="_blank" class="hover:text-typo3-orange transition-colors">{{ author.name }}</a>
                                <span v-else>{{ author.name }}</span>
                                <span v-if="i < package.authors.length - 1">, </span>
                            </template>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            {{ package.license }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ package.releases.length }} {{ package.releases.length === 1 ? 'release' : 'releases' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Content -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="flex-1 min-w-0">
                <!-- Tabs -->
                <div class="border-b border-typo3-border mb-6">
                    <nav class="flex gap-6">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'pb-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === tab.id
                                    ? 'border-typo3-orange text-typo3-orange'
                                    : 'border-transparent text-gray-500 hover:text-typo3-dark hover:border-gray-300'
                            ]"
                        >
                            {{ tab.label }}
                            <span v-if="tab.id === 'releases'" class="ml-1.5 text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full">
                                {{ package.releases.length }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'">
                    <div v-if="package.description" class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-semibold text-typo3-dark mb-3">Description</h2>
                        <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ package.description }}</p>
                    </div>

                    <!-- Sections (changelog etc.) -->
                    <div v-if="package.sections && Object.keys(package.sections).length" class="space-y-6">
                        <div v-for="(content, title) in package.sections" :key="title" class="bg-white rounded-xl shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-typo3-dark mb-3 capitalize">{{ title }}</h2>
                            <div class="prose prose-sm max-w-none text-gray-600" v-html="content"></div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="package.tags && package.tags.length" class="bg-white rounded-xl shadow-sm p-6 mt-6">
                        <h2 class="text-lg font-semibold text-typo3-dark mb-3">Tags</h2>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in package.tags"
                                :key="tag"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-typo3-orange hover:text-white transition-colors cursor-default"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Releases Tab -->
                <div v-if="activeTab === 'releases'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div v-if="package.releases.length === 0" class="p-6 text-center text-gray-500">
                            No releases available.
                        </div>
                        <div v-else class="divide-y divide-gray-100">
                            <div
                                v-for="release in package.releases"
                                :key="release.version"
                                class="p-5 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-mono font-semibold text-typo3-dark bg-gray-100 px-2.5 py-1 rounded">
                                            v{{ release.version }}
                                        </span>
                                        <div v-if="release.requires" class="flex flex-wrap gap-2">
                                            <span
                                                v-for="(ver, key) in release.requires"
                                                :key="key"
                                                class="text-xs text-gray-500 bg-gray-50 px-2 py-0.5 rounded"
                                            >
                                                {{ key }}: {{ ver }}
                                            </span>
                                        </div>
                                    </div>
                                    <a
                                        v-if="release.download_url"
                                        :href="release.download_url"
                                        target="_blank"
                                        class="text-sm text-typo3-orange hover:text-typo3-orange-hover font-medium flex items-center gap-1"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="lg:w-80 flex-shrink-0 space-y-6">
                <!-- Install Card -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-typo3-dark uppercase tracking-wider mb-3">Install</h3>
                    <div class="bg-gray-900 rounded-lg p-4 flex items-center justify-between gap-2">
                        <code class="text-sm text-green-400 truncate">composer require {{ composerName }}</code>
                        <button
                            @click="copyComposerCommand"
                            class="flex-shrink-0 text-gray-400 hover:text-white transition-colors"
                            :title="copied ? 'Copied!' : 'Copy to clipboard'"
                        >
                            <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <svg v-else class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                    <a
                        v-if="latestRelease && latestRelease.download_url"
                        :href="latestRelease.download_url"
                        target="_blank"
                        class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-typo3-orange hover:bg-typo3-orange-hover text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download v{{ latestRelease.version }}
                    </a>
                </div>

                <!-- Metadata Card -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-typo3-dark uppercase tracking-wider mb-4">Details</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Slug</dt>
                            <dd class="text-sm font-mono text-typo3-dark">{{ package.slug }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Type</dt>
                            <dd class="text-sm text-typo3-dark">{{ package.type }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Origin</dt>
                            <dd class="text-sm text-typo3-dark capitalize">{{ package.origin }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">License</dt>
                            <dd class="text-sm text-typo3-dark">{{ package.license }}</dd>
                        </div>
                        <div v-if="package.releases.length" class="flex justify-between">
                            <dt class="text-sm text-gray-500">Releases</dt>
                            <dd class="text-sm text-typo3-dark">{{ package.releases.length }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Compatibility Card -->
                <div v-if="requiresEntries.length" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-typo3-dark uppercase tracking-wider mb-4">Compatibility</h3>
                    <dl class="space-y-3">
                        <div v-for="[key, value] in requiresEntries" :key="key" class="flex justify-between">
                            <dt class="text-sm text-gray-500 capitalize">{{ key }}</dt>
                            <dd class="text-sm font-mono text-typo3-dark">{{ value }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Authors Card -->
                <div v-if="package.authors && package.authors.length" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-typo3-dark uppercase tracking-wider mb-4">Authors</h3>
                    <ul class="space-y-2">
                        <li v-for="(author, i) in package.authors" :key="i" class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                                {{ author.name.charAt(0).toUpperCase() }}
                            </div>
                            <a
                                v-if="author.url"
                                :href="author.url"
                                target="_blank"
                                class="text-sm text-typo3-dark hover:text-typo3-orange transition-colors"
                            >
                                {{ author.name }}
                            </a>
                            <span v-else class="text-sm text-typo3-dark">{{ author.name }}</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>
</template>
