<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref } from 'vue';
import type { WonBid } from '@/pages/HistoryBids.vue';
import type { LengthAwarePaginator } from '@/types';
import ReviewModal from './ReviewModal.vue';

const params = new URLSearchParams(window.location.search);

const props = defineProps<{
    bids: LengthAwarePaginator<WonBid>;
    userPoints: number | null;
}>();

const search = ref(params.get('search') ?? '');

function visit(extra: Record<string, string | number> = {}) {
    router.get(
        '/history',
        {
            ...(search.value ? { search: search.value } : {}),
            ...extra,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const onSearch = useDebounceFn(() => visit({ page: 1 }), 400);

function goToPage(page: number | null) {
    if (page === null) {
        return;
    }

    visit({ page });
}

const visiblePages = computed(() => {
    const { current_page: current, last_page: last } = props.bids;
    const pages: (number | '...')[] = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }

        return pages;
    }

    pages.push(1);

    if (current > 3) {
        pages.push('...');
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    if (current < last - 2) {
        pages.push('...');
    }

    pages.push(last);

    return pages;
});

const selectedBid = ref<WonBid | null>(null);
const showModal = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const reviewMode = ref<'view' | 'write'>('view');

function openReviewModal(bid: WonBid, mode: 'view' | 'write') {
    selectedBid.value = bid;
    reviewMode.value = mode;
    showModal.value = true;
}

function onReviewSuccess() {
    showModal.value = false;
    toastMessage.value = 'Review submitted successfully!';
    showToast.value = true;

    setTimeout(() => {
        showToast.value = false;
        router.reload();
    }, 5000);
}

function formattedPrice(price: string): string {
    const num = parseFloat(price);

    return isNaN(num) ? price : `₦ ${num.toLocaleString()}`;
}
</script>

<template>
    <section class="mx-auto max-w-screen-2xl px-6 pt-12 pb-24">
        <!-- Page header -->
        <header class="mb-12">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <div class="space-y-2">
                    <span class="font-label text-xs font-bold tracking-widest text-primary uppercase">
                        Winning Moments
                    </span>
                    <h1 class="font-headline text-4xl font-extrabold tracking-tight text-on-surface md:text-5xl">
                        Won Bids History
                    </h1>
                </div>

                <div class="flex w-full flex-col gap-4 sm:flex-row md:w-auto">
                    <!-- Search -->
                    <div class="group relative">
                        <span class="material-symbols-outlined absolute top-1/2 left-4 -translate-y-1/2 text-outline">
                            search
                        </span>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search history..."
                            class="w-full rounded-full border-none bg-surface-container-low py-3.5 pr-6 pl-12 text-sm font-medium focus:ring-2 focus:ring-primary-container sm:w-[300px]"
                            @input="onSearch"
                        />
                    </div>
                </div>
            </div>

            <div v-if="bids.total > 0" class="mt-6 flex flex-wrap items-center gap-3">
                <span class="ml-auto text-xs font-bold text-outline">
                    {{ bids.total }} result{{ bids.total !== 1 ? 's' : '' }}
                </span>
            </div>
        </header>

        <!-- Empty state -->
        <div v-if="bids.data.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
            <span class="material-symbols-outlined mb-4 text-5xl text-outline">history_toggle_off</span>
            <p class="font-headline text-xl font-bold text-on-surface-variant">
                No past bids found
            </p>
            <p class="mt-2 text-sm text-outline">
                You haven't won any bids yet, or none matched your search.
            </p>
        </div>

        <!-- Bid cards grid -->
        <div v-else class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="bid in bids.data"
                :key="bid.id"
                class="group flex h-full flex-col overflow-hidden rounded-3xl border border-surface-container bg-surface-container-lowest transition-all duration-500 hover:shadow-2xl hover:shadow-primary/5"
            >
                <!-- Image -->
                <div class="relative aspect-4/3 overflow-hidden">
                    <img
                        :src="bid.image"
                        :alt="bid.name"
                        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                    />
                    <a
                        :href="bid.url"
                        target="_blank"
                        rel="noopener"
                        class="absolute top-4 right-4 cursor-pointer rounded-full bg-white/90 p-2 shadow-lg backdrop-blur transition-all hover:bg-primary hover:text-white"
                    >
                        <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                    </a>
                    <div class="absolute bottom-4 left-4 flex gap-2">
                        <span class="rounded-lg bg-secondary px-3 py-1 text-[10px] font-black tracking-widest text-white uppercase shadow-lg">
                            Won Bid
                        </span>
                    </div>
                </div>

                <!-- Card body -->
                <div class="flex grow flex-col p-6">
                    <div class="mb-4 flex items-start justify-between">
                        <h3 class="font-headline text-xl leading-tight font-extrabold line-clamp-2">
                            {{ bid.name }}
                        </h3>
                        <div class="text-right">
                            <p class="mb-1 text-[10px] font-bold tracking-wider text-secondary uppercase">
                                Retail
                            </p>
                            <p class="text-xl font-black text-primary">
                                {{ formattedPrice(bid.price) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <div class="mb-4 rounded-xl border border-secondary/20 bg-secondary-container/10 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-outline">Winning User:</span>
                                <span class="text-sm font-black text-on-surface">
                                    {{ bid.bid_winner ? (bid.bid_winner.msisdn.slice(0, -4) + '****') : 'N/A' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-outline">Winning Points:</span>
                                <span class="text-sm font-black text-primary">
                                    {{ bid.bid_winner ? bid.bid_winner.total_points : 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl py-3 text-sm font-bold transition-all bg-surface-container-high text-on-surface hover:bg-surface-container-highest active:scale-95 group-hover:scale-[1.02]"
                                @click="openReviewModal(bid, 'view')"
                            >
                                <span class="material-symbols-outlined align-middle mr-1 text-sm">visibility</span>
                                View Reviews
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-xl py-3 text-sm font-bold transition-all bg-primary-container text-on-primary-container hover:bg-primary hover:text-white active:scale-95 group-hover:scale-[1.02]"
                                @click="openReviewModal(bid, 'write')"
                            >
                                <span class="material-symbols-outlined align-middle mr-1 text-sm">rate_review</span>
                                Post Review
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="bids.last_page > 1" class="mt-20 flex items-center justify-center gap-4">
            <button
                type="button"
                :disabled="bids.current_page === 1"
                class="flex items-center justify-center rounded-full bg-surface-container-low p-2 text-outline transition-colors hover:bg-surface-container active:scale-90 disabled:opacity-40"
                @click="goToPage(bids.current_page - 1)"
            >
                <span class="material-symbols-outlined">chevron_left</span>
            </button>

            <div class="flex gap-2">
                <template v-for="page in visiblePages" :key="page">
                    <span v-if="page === '...'" class="flex w-10 items-center justify-center text-outline">...</span>
                    <button
                        v-else
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-sm font-bold transition-colors"
                        :class="page === bids.current_page ? 'bg-primary text-white' : 'bg-surface-container-low text-on-surface hover:bg-surface-container'"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </button>
                </template>
            </div>

            <button
                type="button"
                :disabled="bids.current_page === bids.last_page"
                class="flex items-center justify-center rounded-full bg-surface-container-low p-2 text-outline transition-colors hover:bg-surface-container active:scale-90 disabled:opacity-40"
                @click="goToPage(bids.current_page + 1)"
            >
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>

        <!-- Review Modal -->
        <ReviewModal
            :show="showModal"
            :bid="selectedBid"
            :mode="reviewMode"
            @close="showModal = false"
            @success="onReviewSuccess"
        />

        <!-- Toast Notification -->
        <Teleport to="body">
            <div
                v-if="showToast"
                class="fixed right-6 bottom-6 z-50 flex items-center gap-3 rounded-2xl border border-surface-container bg-surface-container-highest px-6 py-4 shadow-xl"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                    <span class="material-symbols-outlined text-sm">check</span>
                </div>
                <span class="text-sm font-bold text-on-surface">{{ toastMessage }}</span>
            </div>
        </Teleport>
    </section>
</template>
