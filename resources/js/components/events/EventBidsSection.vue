<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn, useNow } from '@vueuse/core';
import { computed, ref } from 'vue';
import { formatPrice } from '@/lib/utils';
import type { Bid } from '@/pages/Home.vue';
import { events, login } from '@/routes';
import { place } from '@/routes/bids';
import type { LengthAwarePaginator } from '@/types';

const now = useNow();

const params = new URLSearchParams(window.location.search);

const props = defineProps<{
    bids: LengthAwarePaginator<Bid>;
    userPoints: number | null;
}>();

const search = ref(params.get('search') ?? '');
const sort = ref(params.get('sort') ?? 'recent');
const status = ref(params.get('status') ?? '');
const showFilters = ref(false);

const sortOptions: { value: string; label: string }[] = [
    { value: 'recent', label: 'Most Recent Activity' },
    { value: 'value_desc', label: 'Value: High to Low' },
];

const sortLabel = computed(
    () => sortOptions.find((o) => o.value === sort.value)?.label ?? 'Sort By',
);

const activeFilters = computed(() => {
    const chips: { key: string; label: string }[] = [];

    if (status.value === 'live') {
        chips.push({ key: 'status', label: 'Status: Live' });
    }

    if (status.value === 'upcoming') {
        chips.push({ key: 'status', label: 'Status: Upcoming' });
    }

    return chips;
});

function visit(extra: Record<string, string | number> = {}) {
    router.get(
        events.url({
            query: {
                ...(search.value ? { search: search.value } : {}),
                ...(sort.value !== 'recent' ? { sort: sort.value } : {}),
                ...(status.value ? { status: status.value } : {}),
                ...extra,
            }
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const onSearch = useDebounceFn(() => visit({ page: 1 }), 400);

function onSortChange() {
    visit({ page: 1 });
}

function setStatus(value: string) {
    status.value = value;
    visit({ page: 1 });
}

function removeFilter(key: string) {
    if (key === 'status') {
        status.value = '';
        visit({ page: 1 });
    }
}

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

function progressPct(bid: Bid): number {
    if (!bid.open_points) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(((bid.bid_entry_points ?? 0) / bid.open_points) * 100),
    );
}

const page = usePage();
const selectedBid = ref<Bid | null>(null);
const showModal = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const expandedImage = ref<string | null>(null);

const form = useForm({
    points: 0,
});

function openBidModal(bid: Bid) {
    // If auth property is not accessible or user is not logged in
    if (!page.props.auth?.user) {
        router.get(login.url());

        return;
    }

    selectedBid.value = bid;
    form.points = props.userPoints ?? 0;
    showModal.value = true;
}

function submitBid() {
    if (!selectedBid.value) {
        return;
    }

    form.post(place.url({ bid: selectedBid.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            toastMessage.value = 'Bid placed successfully!';
            showToast.value = true;

            setTimeout(() => {
                showToast.value = false;
                router.reload();
            }, 5000);
        },
    });
}

function buttonLabel(bid: Bid): string {
    if (bid.status === 2) {
        return 'Closed';
    }

    if (progressPct(bid) >= 100) {
        return 'Final Moment Bid';
    }

    return 'Place Bid';
}


function getRemainingTime(endsAt: string | null | undefined): string {
    if (!endsAt) {
        return '';
    }

    const diffInMs = new Date(endsAt).getTime() - now.value.getTime();

    if (diffInMs <= 0) {
        return '0 hour(s), 0 minute(s)';
    }

    const totalMinutes = Math.floor(diffInMs / (1000 * 60));
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return `${hours} hour(s), ${minutes} minute(s)`;
}
</script>

<template>
    <section class="mx-auto max-w-screen-2xl px-6 pt-12 pb-24">
        <!-- Page header -->
        <header class="mb-12">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <div class="space-y-2">
                    <span class="font-label text-xs font-bold tracking-widest text-primary uppercase">
                        Curated Selection
                    </span>
                    <h1 class="font-headline text-4xl font-extrabold tracking-tight text-on-surface md:text-5xl">
                        Event Items
                    </h1>
                </div>

                <div class="flex w-full flex-col gap-4 sm:flex-row md:w-auto">
                    <!-- Search — rounded-full, matches home page -->
                    <div class="group relative">
                        <span
                            class="material-symbols-outlined absolute top-1/2 left-4 -translate-y-1/2 text-outline">search</span>
                        <input v-model="search" type="search" placeholder="Search Event Items..."
                            class="w-full rounded-full border-none bg-surface-container-low py-3.5 pr-6 pl-12 text-sm font-medium focus:ring-2 focus:ring-primary-container sm:w-[300px]"
                            @input="onSearch" />
                    </div>

                    <!-- Sort — pill button with invisible select overlay, matches home page -->
                    <div class="relative">
                        <div
                            class="flex cursor-pointer items-center rounded-full bg-surface-container-low px-6 py-3.5 transition-colors hover:bg-surface-container-high">
                            <span class="mr-4 text-sm font-bold text-on-surface-variant">{{ sortLabel }}</span>
                            <span class="material-symbols-outlined text-sm">expand_more</span>
                        </div>
                        <select v-model="sort" class="absolute inset-0 w-full cursor-pointer appearance-none opacity-0"
                            @change="onSortChange">
                            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filters button with dropdown panel -->
                    <div class="relative">
                        <button type="button"
                            class="flex cursor-pointer items-center rounded-full px-6 py-3.5 transition-colors" :class="activeFilters.length > 0
                                ? 'bg-primary text-on-primary'
                                : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high'
                                " @click="showFilters = !showFilters">
                            <span class="material-symbols-outlined mr-2 text-sm">tune</span>
                            <span class="text-sm font-bold">Filters</span>
                            <span v-if="activeFilters.length > 0"
                                class="ml-2 flex h-4 w-4 items-center justify-center rounded-full bg-white/30 text-[10px] font-black">
                                {{ activeFilters.length }}
                            </span>
                        </button>

                        <!-- Filter dropdown panel -->
                        <div v-if="showFilters"
                            class="absolute top-full right-0 z-20 mt-2 w-64 overflow-hidden rounded-2xl border border-surface-container bg-surface-container-lowest shadow-xl shadow-primary/10">
                            <div class="p-3">
                                <p class="mb-2 px-2 text-[10px] font-black tracking-widest text-outline uppercase">
                                    Status
                                </p>
                                <button v-for="opt in [
                                    { value: '', label: 'All' },
                                    { value: 'live', label: 'Live' },
                                    {
                                        value: 'upcoming',
                                        label: 'Upcoming',
                                    },
                                ]" :key="opt.value" type="button"
                                    class="mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors"
                                    :class="status === opt.value
                                        ? 'bg-primary text-on-primary'
                                        : 'text-on-surface-variant hover:bg-surface-container-low'
                                        " @click="setStatus(opt.value)">
                                    <span class="material-symbols-outlined text-base">{{
                                        status === opt.value
                                            ? 'radio_button_checked'
                                            : 'radio_button_unchecked'
                                    }}</span>
                                    {{ opt.label }}
                                </button>


                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active filter chips + result count -->
            <div v-if="activeFilters.length > 0 || bids.total > 0" class="mt-6 flex flex-wrap items-center gap-3">
                <div v-for="filter in activeFilters" :key="filter.key"
                    class="flex items-center gap-2 rounded-full bg-secondary-container/50 px-4 py-2 text-xs font-bold text-primary">
                    <span>{{ filter.label }}</span>
                    <span class="material-symbols-outlined cursor-pointer text-sm"
                        @click="removeFilter(filter.key)">close</span>
                </div>

                <span class="ml-auto text-xs font-bold text-outline">
                    {{ bids.total }} result{{ bids.total !== 1 ? 's' : '' }}
                </span>
            </div>
        </header>

        <!-- Empty state -->
        <div v-if="bids.data.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
            <span class="material-symbols-outlined mb-4 text-5xl text-outline">search_off</span>
            <p class="font-headline text-xl font-bold text-on-surface-variant">
                No Event Items found
            </p>
            <p class="mt-2 text-sm text-outline">
                Try adjusting your search or check back soon.
            </p>
        </div>

        <!-- Bid cards grid -->
        <div v-else class="grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-6 lg:grid-cols-4 xl:grid-cols-5">
            <div v-for="bid in bids.data" :key="bid.id"
                class="group flex h-full flex-col overflow-hidden rounded-2xl border border-surface-container bg-surface-container-lowest transition-all duration-500 hover:shadow-2xl hover:shadow-primary/5 sm:rounded-3xl">
                <!-- Image -->
                <div class="relative aspect-4/3 overflow-hidden">
                    <img :src="bid.image" :alt="bid.name"
                        class="h-full w-full cursor-pointer object-cover transition-transform duration-700 group-hover:scale-110"
                        @click="expandedImage = bid.image" />
                    <a :href="bid.url" target="_blank" rel="noopener"
                        class="absolute top-4 right-4 cursor-pointer rounded-full bg-white/90 p-2 shadow-lg backdrop-blur transition-all hover:bg-primary hover:text-white">
                        <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                    </a>
                    <div class="absolute bottom-4 left-4">
                        <span v-if="bid.status === 1"
                            class="rounded-lg bg-primary-container px-2 py-1 text-[9px] font-black tracking-widest text-on-primary-container uppercase shadow-lg sm:px-3 sm:text-[10px]">
                            Live
                        </span>
                        <span v-else-if="bid.status === 2"
                            class="rounded-lg bg-error px-2 py-1 text-[9px] font-black tracking-widest text-white uppercase shadow-lg sm:px-3 sm:text-[10px]">
                            Closed
                        </span>
                        <span v-else
                            class="rounded-lg bg-secondary px-2 py-1 text-[9px] font-black tracking-widest text-white uppercase shadow-lg sm:px-3 sm:text-[10px]">
                            Upcoming
                        </span>
                    </div>
                </div>

                <div class="flex grow flex-col p-3 sm:p-4">
                    <div class="mb-3 flex items-start justify-between">
                        <h3 class="font-headline text-sm leading-tight font-extrabold sm:text-base">
                            {{ bid.name }}
                        </h3>
                        <div class="text-right">
                            <p class="mb-0.5 text-[8px] font-bold tracking-wider text-secondary uppercase sm:text-[9px]">
                                Price
                            </p>
                            <p class="text-base font-black text-primary whitespace-nowrap sm:text-lg">
                                {{ formatPrice(bid.price) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-[9px] font-bold text-secondary sm:text-[10px]">
                                Progress: {{ bid.bid_entry_points ?? 0 }}/{{
                                    bid.open_points
                                }}
                                Points
                            </span>
                            <span class="text-[9px] font-black sm:text-[10px]" :class="progressPct(bid) >= 100
                                ? 'text-error'
                                : 'text-primary'
                                ">
                                {{ progressPct(bid) }}%
                            </span>
                        </div>

                        <div class="mb-2 h-1.5 w-full overflow-hidden rounded-full bg-surface-container-highest">
                            <div class="h-full rounded-full transition-all duration-500"
                                :style="{ width: `${progressPct(bid)}%` }" :class="progressPct(bid) >= 100
                                    ? 'bg-error'
                                    : 'bg-linear-to-r from-primary to-tertiary'
                                    " />
                        </div>

                        <div v-if="(bid.status === 1 || progressPct(bid) >= 100) && bid.ends_at"
                            class="mb-3 text-center text-[9px] font-bold text-outline sm:text-[10px]">
                            <span class="material-symbols-outlined mr-1 align-middle text-[12px]">schedule</span>
                            <span class="align-middle">{{ getRemainingTime(bid.ends_at) }} left</span>
                        </div>
                        <div v-else class="mb-3 h-[14px]"></div>

                        <button type="button" :disabled="bid.status === 2"
                            class="w-full rounded-xl py-2 text-xs font-bold transition-all sm:py-2.5 sm:text-sm" :class="bid.status === 2
                                ? 'cursor-not-allowed bg-surface-container-low text-on-surface-variant'
                                : progressPct(bid) >= 100
                                    ? 'bg-primary text-on-primary shadow-lg shadow-primary/20 group-hover:scale-[1.02] hover:bg-on-primary-fixed active:scale-95'
                                    : 'bg-primary text-on-primary group-hover:scale-[1.02] hover:bg-on-primary-fixed active:scale-95'
                                " @click="openBidModal(bid)">
                            {{ buttonLabel(bid) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="bids.last_page > 1" class="mt-20 flex items-center justify-center gap-4">
            <button type="button" :disabled="bids.current_page === 1"
                class="flex items-center justify-center rounded-full bg-surface-container-low p-2 text-outline transition-colors hover:bg-surface-container active:scale-90 disabled:opacity-40"
                @click="goToPage(bids.current_page - 1)">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>

            <div class="flex gap-2">
                <template v-for="page in visiblePages" :key="page">
                    <span v-if="page === '...'" class="flex w-10 items-center justify-center text-outline">
                        ...
                    </span>
                    <button v-else type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-sm font-bold transition-colors"
                        :class="page === bids.current_page
                            ? 'bg-primary text-white'
                            : 'bg-surface-container-low text-on-surface hover:bg-surface-container'
                            " @click="goToPage(page)">
                        {{ page }}
                    </button>
                </template>
            </div>

            <button type="button" :disabled="bids.current_page === bids.last_page"
                class="flex items-center justify-center rounded-full bg-surface-container-low p-2 text-outline transition-colors hover:bg-surface-container active:scale-90 disabled:opacity-40"
                @click="goToPage(bids.current_page + 1)">
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>

        <!-- Teleport Modal to body -->
        <Teleport to="body">
            <div v-if="showModal && selectedBid"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
                <!-- Modal Backdrop -->
                <div class="absolute inset-0" @click="showModal = false"></div>

                <!-- Modal Content -->
                <div
                    class="relative w-full max-w-md overflow-hidden rounded-3xl bg-surface-container-lowest p-6 shadow-2xl">
                    <button type="button"
                        class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-on-surface-variant transition-colors hover:bg-surface-container-high"
                        @click="showModal = false">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>

                    <h2 class="mb-2 font-headline text-2xl font-extrabold text-on-surface">
                        Place Your Bid
                    </h2>
                    <p class="mb-6 text-sm text-outline">
                        Bid on
                        <span class="font-bold text-on-surface">{{
                            selectedBid.name
                        }}</span>
                    </p>

                    <form @submit.prevent="submitBid">
                        <div class="mb-4">
                            <label class="mb-2 block text-xs font-bold tracking-widest text-outline uppercase">Bid
                                Points</label>
                            <input v-model="form.points" type="number" required min="1"
                                class="w-full rounded-2xl border border-surface-container bg-surface-container-low px-4 py-3 text-lg font-bold focus:border-primary focus:ring-2 focus:ring-primary-container disabled:opacity-50"
                                :disabled="form.processing" />
                            <p v-if="form.errors.points" class="mt-2 text-xs font-bold text-error">
                                {{ form.errors.points }}
                            </p>
                        </div>

                        <div class="mb-6 rounded-xl border border-secondary/20 bg-secondary-container/30 p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-semibold text-on-surface-variant">Your Active Points</span>
                                <span class="text-sm font-black text-on-surface">{{ props.userPoints ?? 0 }}</span>
                            </div>
                            <div v-if="selectedBid.status === 0" class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-on-surface-variant">Points Needed to
                                    Unlock</span>
                                <span class="text-sm font-black text-on-surface">{{ selectedBid.open_points }}</span>
                            </div>
                            <div v-if="selectedBid.status === 1" class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-on-surface-variant">Total Bidded Points</span>
                                <span class="text-sm font-black text-on-surface">{{ selectedBid.bid_entry_points
                                }}</span>
                            </div>
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="flex w-full items-center justify-center rounded-2xl bg-primary py-4 font-bold text-on-primary transition-all hover:bg-on-primary-fixed active:scale-95 disabled:opacity-50">
                            <span v-if="form.processing"
                                class="material-symbols-outlined mr-2 animate-spin">progress_activity</span>
                            Confirm Bid
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notification -->
        <Teleport to="body">
            <div v-if="showToast"
                class="fixed right-6 bottom-6 z-50 flex items-center gap-3 rounded-2xl border border-surface-container bg-surface-container-highest px-6 py-4 shadow-xl">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                    <span class="material-symbols-outlined text-sm">check</span>
                </div>
                <span class="text-sm font-bold text-on-surface">{{
                    toastMessage
                }}</span>
            </div>
        </Teleport>

        <!-- Image Overlay -->
        <Teleport to="body">
            <div v-if="expandedImage"
                class="fixed inset-0 z-[100] flex cursor-pointer items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
                @click="expandedImage = null">
                <img :src="expandedImage" class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl"
                    alt="Expanded image" />
            </div>
        </Teleport>
    </section>
</template>
