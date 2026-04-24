<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref } from 'vue';
import { formatPrice } from '@/lib/utils';
import type { LeaderboardBid } from '@/pages/LeaderboardBids.vue';
import { leaderboard } from '@/routes';
import type { LengthAwarePaginator } from '@/types';

const params = new URLSearchParams(window.location.search);

const props = defineProps<{
    bids: LengthAwarePaginator<LeaderboardBid>;
}>();

const search = ref(params.get('search') ?? '');

function visit(extra: Record<string, string | number> = {}) {
    router.get(
        leaderboard.url({
            query: {
                ...(search.value ? { search: search.value } : {}),
                ...extra,
            },
        }),
        {},
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

function formatMsisdn(msisdn: string): string {
    if (!msisdn || msisdn.length < 5) return 'Unknown';

    return msisdn.slice(0, -4) + '****';
}


const expandedImage = ref<string | null>(null);
</script>

<template>
    <section class="mx-auto max-w-screen-2xl px-6 pt-12 pb-24">
        <!-- Page header -->
        <header class="mb-12">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <div class="space-y-2">
                    <span class="font-label text-xs font-bold tracking-widest text-primary uppercase">
                        Active Bidding Wars
                    </span>
                    <h1 class="font-headline text-4xl font-extrabold tracking-tight text-on-surface md:text-5xl">
                        Leaderboard
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
                            placeholder="Find items..."
                            class="w-full rounded-full border-none bg-surface-container-low py-3.5 pr-6 pl-12 text-sm font-medium focus:ring-2 focus:ring-primary-container sm:w-[300px]"
                            @input="onSearch"
                        />
                    </div>
                </div>
            </div>

            <div v-if="bids.total > 0" class="mt-6 flex flex-wrap items-center gap-3">
                <span class="ml-auto text-xs font-bold text-outline">
                    {{ bids.total }} active item{{ bids.total !== 1 ? 's' : '' }}
                </span>
            </div>
        </header>

        <!-- Empty state -->
        <div v-if="bids.data.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
            <span class="material-symbols-outlined mb-4 text-5xl text-outline">equalizer</span>
            <p class="font-headline text-xl font-bold text-on-surface-variant">
                No active bids found
            </p>
            <p class="mt-2 text-sm text-outline">
                Check back soon when more upcoming and live items hit the shelves.
            </p>
        </div>

        <!-- Leaderboard grid -->
        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-6 lg:grid-cols-4 xl:grid-cols-5">
            <div
                v-for="bid in bids.data"
                :key="bid.id"
                class="group flex h-full flex-col overflow-hidden rounded-2xl border border-surface-container bg-surface-container-lowest transition-all duration-500 hover:shadow-2xl hover:shadow-primary/5 sm:rounded-3xl"
            >
                <!-- Image -->
                <div class="relative aspect-4/3 overflow-hidden border-b border-surface-container">
                    <img
                        :src="bid.image"
                        :alt="bid.name"
                        class="h-full w-full cursor-pointer object-cover transition-transform duration-700 group-hover:scale-110"
                        @click="expandedImage = bid.image"
                    />
                    <a
                        :href="bid.url"
                        target="_blank"
                        rel="noopener"
                        class="absolute top-4 right-4 cursor-pointer rounded-full bg-white/90 p-2 shadow-lg backdrop-blur transition-all hover:bg-primary hover:text-white"
                    >
                        <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                    </a>
                    <div class="absolute bottom-4 left-4">
                        <span
                            v-if="bid.status === 1"
                            class="rounded-lg bg-primary-container px-2 py-1 text-[9px] font-black tracking-widest text-on-primary-container uppercase shadow-lg sm:px-3 sm:text-[10px]"
                        >
                            Live
                        </span>
                        <span
                            v-else
                            class="rounded-lg bg-secondary px-2 py-1 text-[9px] font-black tracking-widest text-white uppercase shadow-lg sm:px-3 sm:text-[10px]"
                        >
                            Upcoming
                        </span>
                    </div>
                </div>

                <!-- Card body -->
                <div class="flex grow flex-col p-3 sm:p-4">
                    <div class="mb-3 flex items-start justify-between">
                        <h3 class="font-headline text-sm leading-snug font-extrabold line-clamp-2 pr-2 sm:text-base">
                            {{ bid.name }}
                        </h3>
                        <div class="text-right shrink-0">
                            <p class="mb-0.5 text-[8px] font-bold tracking-wider text-secondary uppercase sm:text-[9px]">
                                Value
                            </p>
                            <p class="text-base font-black text-primary whitespace-nowrap sm:text-lg">
                                {{ formatPrice(bid.price) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <h4 class="text-[9px] font-black tracking-widest text-outline uppercase mb-2 sm:text-[10px]">
                            Top Bidders
                        </h4>

                        <div v-if="bid.top_bidders && bid.top_bidders.length > 0" class="flex flex-col gap-1.5">
                            <div
                                v-for="(bidder, index) in bid.top_bidders"
                                :key="index"
                                class="flex items-center justify-between rounded-xl px-2.5 py-1.5 sm:px-3 sm:py-2"
                                :class="index === 0 ? 'bg-primary-container/40 border border-primary/20' : 'bg-surface-container-low'"
                            >
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <span
                                        class="flex h-4 w-4 items-center justify-center rounded-full text-[8px] font-black sm:h-5 sm:w-5 sm:text-[9px]"
                                        :class="index === 0 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-surface-container-high text-on-surface-variant'"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <span class="text-[10px] font-bold text-on-surface sm:text-xs">
                                        {{ formatMsisdn(bidder.msisdn) }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-black sm:text-xs" :class="index === 0 ? 'text-primary' : 'text-on-surface-variant'">
                                    {{ bidder.total_points }} pts
                                </span>
                            </div>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-outline-variant p-3 text-center sm:p-4">
                            <p class="text-[10px] font-bold text-outline sm:text-xs">No bids recorded</p>
                            <p class="text-[9px] text-outline/80 mt-0.5 sm:text-[10px]">Check back once the bidding war starts.</p>
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

        <!-- Image Overlay -->
        <Teleport to="body">
            <div v-if="expandedImage"
                class="fixed inset-0 z-100 flex cursor-pointer items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
                @click="expandedImage = null">
                <img :src="expandedImage"
                    class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl"
                    alt="Expanded image" />
            </div>
        </Teleport>
    </section>
</template>
