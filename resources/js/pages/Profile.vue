<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppPaginator from '@/components/layout/AppPaginator.vue';
import { formatPrice } from '@/lib/utils';
import type { Bid } from '@/pages/Home.vue';
import { home, profile } from '@/routes';
import { place } from '@/routes/bids';
import type { LengthAwarePaginator } from '@/types';

type MyBid = Bid & {
    user_total_points: number;
};

const props = defineProps<{
    user: {
        msisdn: string;
        referral_code: string;
        points: number;
    };
    referral_url: string;
    myBids: LengthAwarePaginator<MyBid>;
}>();

const copied = ref(false);
const selectedBid = ref<MyBid | null>(null);
const showModal = ref(false);
const showToast = ref(false);
const toastMessage = ref('');

const form = useForm({
    points: 0,
});

function copyToClipboard() {
    navigator.clipboard.writeText(props.referral_url);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function share() {
    if (navigator.share) {
        navigator
            .share({
                title: 'CarryGo Referral',
                text: `Join me on CarryGo and start bidding! My referral code: ${props.user.referral_code}`,
                url: props.referral_url,
            })
            .catch(() => {
                copyToClipboard();
            });
    } else {
        copyToClipboard();
    }
}

function progressPct(bid: MyBid): number {
    if (!bid.open_points) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(((bid.bid_entry_points ?? 0) / bid.open_points) * 100),
    );
}

function openBidModal(bid: MyBid) {
    selectedBid.value = bid;
    form.points = props.user.points ?? 0;
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

function goToPage(pageNum: number): void {
    router.get(
        profile.url({
            query: {
                page: pageNum,
            },
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="My Profile" />

    <div class="min-h-screen bg-background px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="mx-auto max-w-3xl">
            <!-- Profile Header -->
            <div
                class="relative mb-8 overflow-hidden rounded-3xl border border-outline-variant/30 bg-surface p-8 shadow-sm sm:p-10"
            >
                <!-- Background decoration -->
                <div
                    class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-primary/10 blur-3xl"
                ></div>
                <div
                    class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-secondary/10 blur-3xl"
                ></div>

                <div
                    class="relative flex flex-col items-center gap-8 md:flex-row"
                >
                    <div
                        class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full border-4 border-white bg-primary/10 text-primary shadow-md dark:border-surface"
                    >
                        <span class="material-symbols-outlined text-5xl!"
                            >person</span
                        >
                    </div>

                    <div class="flex-1 text-center md:text-left">
                        <h1
                            class="font-headline text-3xl font-black tracking-tight text-on-surface"
                        >
                            My Profile
                        </h1>
                        <p class="mt-1 font-medium text-primary/70">
                            Welcome back to CarryGo
                        </p>

                        <div
                            class="mt-4 flex flex-wrap justify-center gap-4 md:justify-start"
                        >
                            <div
                                class="flex items-center gap-2 rounded-full border border-outline-variant/20 bg-white px-4 py-1.5 text-sm font-bold shadow-xs dark:bg-surface-container"
                            >
                                <span
                                    class="material-symbols-outlined text-lg! text-primary"
                                    >phone_iphone</span
                                >
                                <span>{{ user.msisdn }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Points Card -->
                <div
                    class="group relative overflow-hidden rounded-3xl bg-primary p-8 text-white shadow-lg shadow-primary/20"
                >
                    <span
                        class="material-symbols-outlined absolute -right-4 -bottom-4 rotate-12 text-8xl! opacity-10 transition-transform group-hover:scale-110"
                        >stars</span
                    >
                    <div class="relative">
                        <p
                            class="font-headline text-sm font-bold tracking-widest uppercase opacity-80"
                        >
                            Available Points
                        </p>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="text-4xl font-black tracking-tighter"
                                >{{ user.points.toLocaleString() }}</span
                            >
                            <span class="text-sm font-bold opacity-70"
                                >PTS</span
                            >
                        </div>
                        <p
                            class="mt-4 inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-medium backdrop-blur-md"
                        >
                            Points updated in real-time
                        </p>
                    </div>
                </div>

                <!-- Referral Code Card -->
                <div
                    class="group relative overflow-hidden rounded-3xl bg-secondary p-8 text-white shadow-lg shadow-secondary/20"
                >
                    <span
                        class="material-symbols-outlined absolute -right-4 -bottom-4 -rotate-12 text-8xl! opacity-10 transition-transform group-hover:scale-110"
                        >qr_code_2</span
                    >
                    <div class="relative">
                        <p
                            class="font-headline text-sm font-bold tracking-widest uppercase opacity-80"
                        >
                            Referral Code
                        </p>
                        <div class="mt-2 text-4xl font-black tracking-tighter">
                            {{ user.referral_code || '---' }}
                        </div>
                        <p
                            class="mt-4 inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-medium backdrop-blur-md"
                        >
                            Use this to invite friends
                        </p>
                    </div>
                </div>
            </div>

            <!-- Referral Link Card -->
            <div
                class="rounded-3xl border border-outline-variant/30 bg-surface p-8 shadow-sm"
            >
                <h2
                    class="mb-4 font-headline text-xl font-bold text-on-surface"
                >
                    Share & Earn
                </h2>
                <p class="mb-6 max-w-lg text-sm text-primary/70">
                    Invite your friends to join CarryGo using your unique link.
                    When they subscribe and bid, you earn more points!
                </p>

                <div class="group relative">
                    <div
                        class="flex items-center gap-3 rounded-2xl border border-outline-variant/50 bg-background p-4 transition-colors group-hover:border-primary/50"
                    >
                        <span
                            class="material-symbols-outlined shrink-0 text-primary/40"
                            >link</span
                        >
                        <span class="flex-1 truncate text-sm font-medium">{{
                            referral_url
                        }}</span>
                        <button
                            @click="copyToClipboard"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary transition hover:bg-primary/20 active:scale-95"
                            :title="copied ? 'Copied!' : 'Copy link'"
                        >
                            <span class="material-symbols-outlined text-xl!">{{
                                copied ? 'check' : 'content_copy'
                            }}</span>
                        </button>
                    </div>

                    <button
                        @click="share"
                        class="mt-6 flex w-full transform items-center justify-center gap-3 rounded-2xl bg-primary px-8 py-4 text-lg font-bold text-white shadow-md shadow-primary/20 transition hover:opacity-95 active:scale-95"
                    >
                        <span class="material-symbols-outlined">share</span>
                        Share Link Now
                    </button>

                    <transition
                        enter-active-class="transform transition duration-300 ease-out"
                        enter-from-class="translate-y-2 opacity-0"
                        enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="copied"
                            class="absolute -top-12 left-1/2 -translate-x-1/2 rounded-full bg-on-surface px-3 py-1 text-xs font-bold text-surface shadow-lg"
                        >
                            Link copied to clipboard!
                        </div>
                    </transition>
                </div>
            </div>

            <!-- My Bids Section -->
            <div
                class="mt-8 rounded-3xl border border-outline-variant/30 bg-surface p-8 shadow-sm"
            >
                <div
                    class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <h2
                            class="font-headline text-xl font-bold text-on-surface"
                        >
                            My Bids
                        </h2>
                        <p class="mt-1 text-xs text-primary/70">
                            Bids you have participated in. Click to view or
                            continue bidding.
                        </p>
                    </div>
                    <span
                        v-if="myBids.total > 0"
                        class="text-xs font-bold text-outline"
                    >
                        {{ myBids.total }} result{{
                            myBids.total !== 1 ? 's' : ''
                        }}
                    </span>
                </div>

                <!-- Empty State -->
                <div
                    v-if="myBids.data.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center"
                >
                    <span
                        class="material-symbols-outlined mb-3 text-4xl text-outline"
                        >bid_landscape</span
                    >
                    <p
                        class="font-headline text-base font-bold text-on-surface-variant"
                    >
                        No engaged bids
                    </p>
                    <p class="mt-1 text-xs text-outline">
                        You haven't placed any bids yet.
                    </p>
                    <Link
                        :href="home.url()"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition hover:opacity-90 active:scale-95"
                    >
                        Explore Bids
                    </Link>
                </div>

                <!-- Bids Grid -->
                <div v-else>
                    <div
                        class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="bid in myBids.data"
                            :key="bid.id"
                            class="flex flex-col overflow-hidden rounded-2xl border border-surface-container bg-surface-container-lowest transition-all duration-300 hover:shadow-lg"
                        >
                            <div class="relative aspect-4/3 overflow-hidden">
                                <img
                                    :src="bid.image"
                                    :alt="bid.name"
                                    class="h-full w-full object-cover"
                                />
                                <div class="absolute bottom-3 left-3">
                                    <span
                                        v-if="bid.status === 0"
                                        class="rounded bg-primary-container px-2 py-0.5 text-[9px] font-black tracking-widest text-on-primary-container uppercase shadow"
                                        >Live</span
                                    >
                                    <span
                                        v-else-if="bid.status === 2"
                                        class="rounded bg-error px-2 py-0.5 text-[9px] font-black tracking-widest text-white uppercase shadow"
                                        >Closed</span
                                    >
                                    <span
                                        v-else
                                        class="rounded bg-secondary px-2 py-0.5 text-[9px] font-black tracking-widest text-white uppercase shadow"
                                        >Upcoming</span
                                    >
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <h3
                                    class="mb-2 line-clamp-2 font-headline text-sm font-extrabold text-on-surface"
                                >
                                    {{ bid.name }}
                                </h3>
                                <div
                                    class="mb-3 flex items-center justify-between text-xs"
                                >
                                    <span class="text-primary/70"
                                        >Retail Price</span
                                    >
                                    <span class="font-black text-primary">{{
                                        formatPrice(bid.price)
                                    }}</span>
                                </div>
                                <div
                                    class="mt-auto border-t border-surface-container/50 pt-3"
                                >
                                    <div
                                        class="mb-2 flex items-center justify-between text-[10px] font-bold text-primary"
                                    >
                                        <span>My Total Bids:</span>
                                        <span
                                            class="font-extrabold text-on-surface"
                                            >{{
                                                bid.user_total_points.toLocaleString()
                                            }}
                                            PTS</span
                                        >
                                    </div>
                                    <div class="mb-3">
                                        <div
                                            class="mb-1 flex items-center justify-between text-[9px] font-medium text-primary"
                                        >
                                            <span
                                                >Progress ({{
                                                    bid.bid_entry_points ?? 0
                                                }}/{{ bid.open_points }})</span
                                            >
                                            <span
                                                :class="
                                                    progressPct(bid) >= 100
                                                        ? 'font-bold text-error'
                                                        : 'font-bold text-primary'
                                                "
                                                >{{ progressPct(bid) }}%</span
                                            >
                                        </div>
                                        <div
                                            class="h-1 w-full overflow-hidden rounded-full bg-surface-container-highest"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :style="{
                                                    width:
                                                        progressPct(bid) + '%',
                                                }"
                                                :class="
                                                    progressPct(bid) >= 100
                                                        ? 'bg-error'
                                                        : 'bg-primary'
                                                "
                                            />
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        :disabled="bid.status === 2"
                                        class="w-full rounded-xl py-2 text-xs font-bold transition-all"
                                        :class="
                                            bid.status === 2
                                                ? 'cursor-not-allowed bg-surface-container-low text-on-surface-variant'
                                                : 'hover:bg-opacity-95 bg-primary text-white shadow active:scale-95'
                                        "
                                        @click="openBidModal(bid)"
                                    >
                                        {{
                                            bid.status === 2
                                                ? 'Closed'
                                                : 'Bid Again'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <AppPaginator
                        :current-page="myBids.current_page"
                        :last-page="myBids.last_page"
                        @page-change="goToPage"
                    />
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-12 text-center">
                <Link
                    :href="home.url()"
                    class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:underline"
                >
                    <span class="material-symbols-outlined text-lg!"
                        >arrow_back</span
                    >
                    Back to Bidding
                </Link>
            </div>
        </div>
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
                            <span class="text-sm font-black text-on-surface">{{
                                props.user.points ?? 0
                            }}</span>
                        </div>
                        <div
                            v-if="selectedBid.status === 0"
                            class="flex items-center justify-between"
                        >
                            <span
                                class="text-sm font-semibold text-on-surface-variant"
                                >Points Needed to Unlock</span
                            >
                            <span class="text-sm font-black text-on-surface">{{
                                selectedBid.open_points
                            }}</span>
                        </div>
                        <div
                            v-if="selectedBid.status === 1"
                            class="flex items-center justify-between"
                        >
                            <span
                                class="text-sm font-semibold text-on-surface-variant"
                                >Total Bidded Points</span
                            >
                            <span class="text-sm font-black text-on-surface">{{
                                selectedBid.bid_entry_points
                            }}</span>
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
</template>
