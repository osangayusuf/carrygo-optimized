<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useNow } from '@vueuse/core';
import { ref, onMounted } from 'vue';
import type { Bid } from '@/pages/Home.vue';

const props = defineProps<{
    bids: Bid[];
    userPoints: number | null;
}>();

function progressPct(bid: Bid): number {
    if (!bid.open_points) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(((bid.bid_entry_points ?? 0) / bid.open_points) * 100),
    );
}

function buttonLabel(bid: Bid): string {
    const pct = progressPct(bid);

    if (bid.status === 0) {
        return 'Place Bid';
    }

    if (pct >= 100) {
        return 'Last Chance Bid';
    }

    return 'Place Bid';
}

function formattedPrice(price: string): string {
    const num = parseFloat(price);

    return isNaN(num) ? price : `₦ ${num.toLocaleString()}`;
}

const now = useNow();
const page = usePage();
const selectedBid = ref<Bid | null>(null);
const showModal = ref(false);
const showToast = ref(false);
const toastMessage = ref('');

const form = useForm({
    points: 0,
});

onMounted(() => {
    if (page.props.auth?.user) {
        const pendingBidId = sessionStorage.getItem('pendingBidId');

        if (pendingBidId) {
            const bid = props.bids.find(
                (b) => b.id.toString() === pendingBidId,
            );

            if (bid) {
                openBidModal(bid);
                sessionStorage.removeItem('pendingBidId');
            }
        }
    }
});

function openBidModal(bid: Bid) {
    if (!page.props.auth?.user) {
        sessionStorage.setItem('pendingBidId', bid.id.toString());
        router.get('/login');

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

    form.post(`/bids/${selectedBid.value.id}/place`, {
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
    <section class="mx-auto max-w-screen-2xl px-8 py-24">
        <div
            class="mb-16 flex flex-col items-start justify-between gap-6 md:flex-row md:items-center"
        >
            <h2 class="font-headline text-4xl font-extrabold tracking-tighter">
                Live Opportunities
            </h2>
            <div class="flex w-full flex-col gap-4 sm:flex-row md:w-auto">
                <div class="group relative">
                    <span
                        class="material-symbols-outlined absolute top-1/2 left-4 -translate-y-1/2 text-outline"
                        >search</span
                    >
                    <input
                        class="w-full rounded-full border-none bg-surface-container-low py-3.5 pr-6 pl-12 text-sm font-medium focus:ring-2 focus:ring-primary-container sm:w-[300px]"
                        placeholder="Search premium items..."
                        type="search"
                    />
                </div>
                <div
                    class="flex cursor-pointer items-center rounded-full bg-surface-container-low px-6 py-3.5 transition-colors hover:bg-surface-container-high"
                >
                    <span class="mr-4 text-sm font-bold text-on-surface-variant"
                        >Sort By: Category</span
                    >
                    <span class="material-symbols-outlined text-sm"
                        >expand_more</span
                    >
                </div>
            </div>
        </div>

        <div
            class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="bid in props.bids"
                :key="bid.id"
                class="group flex h-full flex-col overflow-hidden rounded-3xl border border-surface-container bg-surface-container-lowest transition-all duration-500 hover:shadow-2xl hover:shadow-primary/5"
            >
                <div class="relative aspect-4/3 overflow-hidden">
                    <img
                        :alt="bid.name"
                        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                        :src="bid.image"
                    />
                    <div
                        class="absolute top-4 right-4 cursor-pointer rounded-full bg-white/90 p-2 shadow-lg backdrop-blur transition-all hover:bg-primary hover:text-white"
                    >
                        <span class="material-symbols-outlined text-[20px]"
                            >open_in_new</span
                        >
                    </div>
                    <div class="absolute bottom-4 left-4">
                        <span
                            v-if="bid.status === 1"
                            class="rounded-lg bg-primary-container px-3 py-1 text-[10px] font-black tracking-widest text-on-primary-container uppercase shadow-lg"
                            >Live</span
                        >
                        <span
                            v-else-if="bid.status === 2"
                            class="rounded-lg bg-error px-3 py-1 text-[10px] font-black tracking-widest text-white uppercase shadow-lg"
                            >Closed</span
                        >
                        <span
                            v-else
                            class="rounded-lg bg-secondary px-3 py-1 text-[10px] font-black tracking-widest text-white uppercase shadow-lg"
                            >Upcoming</span
                        >
                    </div>
                </div>
                <div class="flex grow flex-col p-6">
                    <div class="mb-4 flex items-start justify-between">
                        <h3
                            class="font-headline text-xl leading-tight font-extrabold"
                        >
                            {{ bid.name }}
                        </h3>
                        <div class="text-right">
                            <p
                                class="mb-1 text-[10px] font-bold tracking-wider text-secondary uppercase"
                            >
                                Price
                            </p>
                            <p class="text-xl font-black text-primary">
                                {{ formattedPrice(bid.price) }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-bold text-secondary">
                                Progress: {{ bid.bid_entry_points ?? 0 }}/{{
                                    bid.open_points
                                }}
                                Points
                            </span>
                            <span
                                class="text-xs font-black"
                                :class="
                                    progressPct(bid) >= 100
                                        ? 'text-error'
                                        : 'text-primary'
                                "
                            >
                                {{ progressPct(bid) }}%
                            </span>
                        </div>
                        <div
                            class="mb-2 h-2 w-full overflow-hidden rounded-full bg-surface-container-highest"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{ width: progressPct(bid) + '%' }"
                                :class="
                                    progressPct(bid) >= 100
                                        ? 'bg-error'
                                        : 'bg-linear-to-r from-primary to-tertiary'
                                "
                            />
                        </div>

                        <div
                            v-if="bid.status === 1 && bid.ends_at"
                            class="mb-4 text-center text-xs font-bold text-outline"
                        >
                            <span
                                class="material-symbols-outlined mr-1 align-middle text-[14px]"
                                >schedule</span
                            >
                            <span class="align-middle"
                                >{{ getRemainingTime(bid.ends_at) }} left</span
                            >
                        </div>
                        <div v-else class="mb-4 h-[18px]"></div>

                        <button
                            type="button"
                            :disabled="bid.status === 2"
                            :class="[
                                'w-full rounded-2xl py-4 font-bold transition-all',
                                bid.status === 2
                                    ? 'cursor-not-allowed bg-surface-container-low text-on-surface-variant'
                                    : progressPct(bid) >= 100
                                      ? 'bg-primary font-bold text-on-primary shadow-lg shadow-primary/20 group-hover:scale-[1.02] hover:bg-on-primary-fixed active:scale-95'
                                      : 'bg-primary text-on-primary group-hover:scale-[1.02] hover:bg-on-primary-fixed active:scale-95',
                            ]"
                            @click="openBidModal(bid)"
                        >
                            {{ buttonLabel(bid) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 text-center">
            <Link
                href="/trending"
                class="rounded-full bg-surface-container-low px-12 py-4 font-bold text-on-surface transition-all hover:bg-surface-container-high"
            >
                View All Auction Items
            </Link>
        </div>

        <!-- Teleport Modal to body -->
        <Teleport to="body">
            <div
                v-if="showModal && selectedBid"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
            >
                <!-- Modal Backdrop -->
                <div class="absolute inset-0" @click="showModal = false"></div>

                <!-- Modal Content -->
                <div
                    class="relative w-full max-w-md overflow-hidden rounded-3xl bg-surface-container-lowest p-6 shadow-2xl"
                >
                    <button
                        type="button"
                        class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-on-surface-variant transition-colors hover:bg-surface-container-high"
                        @click="showModal = false"
                    >
                        <span class="material-symbols-outlined text-[20px]"
                            >close</span
                        >
                    </button>

                    <h2
                        class="mb-2 font-headline text-2xl font-extrabold text-on-surface"
                    >
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
                            <label
                                class="mb-2 block text-xs font-bold tracking-widest text-outline uppercase"
                                >Bid Points</label
                            >
                            <input
                                v-model="form.points"
                                type="number"
                                required
                                min="1"
                                class="w-full rounded-2xl border border-surface-container bg-surface-container-low px-4 py-3 text-lg font-bold focus:border-primary focus:ring-2 focus:ring-primary-container disabled:opacity-50"
                                :disabled="form.processing"
                            />
                            <p
                                v-if="form.errors.points"
                                class="mt-2 text-xs font-bold text-error"
                            >
                                {{ form.errors.points }}
                            </p>
                        </div>

                        <div
                            class="mb-6 rounded-xl border border-secondary/20 bg-secondary-container/30 p-4"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <span
                                    class="text-sm font-semibold text-on-surface-variant"
                                    >Your Active Points</span
                                >
                                <span
                                    class="text-sm font-black text-on-surface"
                                    >{{ props.userPoints ?? 0 }}</span
                                >
                            </div>
                            <div
                                v-if="selectedBid.status === 0"
                                class="flex items-center justify-between"
                            >
                                <span
                                    class="text-sm font-semibold text-on-surface-variant"
                                    >Points Needed to Unlock</span
                                >
                                <span
                                    class="text-sm font-black text-on-surface"
                                    >{{ selectedBid.open_points }}</span
                                >
                            </div>
                            <div
                                v-if="selectedBid.status === 1"
                                class="flex items-center justify-between"
                            >
                                <span
                                    class="text-sm font-semibold text-on-surface-variant"
                                    >Total Bidded Points</span
                                >
                                <span
                                    class="text-sm font-black text-on-surface"
                                    >{{ selectedBid.bid_entry_points }}</span
                                >
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center rounded-2xl bg-primary py-4 font-bold text-on-primary transition-all hover:bg-on-primary-fixed active:scale-95 disabled:opacity-50"
                        >
                            <span
                                v-if="form.processing"
                                class="material-symbols-outlined mr-2 animate-spin"
                                >progress_activity</span
                            >
                            Confirm Bid
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notification -->
        <Teleport to="body">
            <div
                v-if="showToast"
                class="fixed right-6 bottom-6 z-50 flex items-center gap-3 rounded-2xl border border-surface-container bg-surface-container-highest px-6 py-4 shadow-xl"
            >
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary"
                >
                    <span class="material-symbols-outlined text-sm">check</span>
                </div>
                <span class="text-sm font-bold text-on-surface">{{
                    toastMessage
                }}</span>
            </div>
        </Teleport>
    </section>
</template>
