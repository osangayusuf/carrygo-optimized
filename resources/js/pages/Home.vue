<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import BidCard from '@/components/cards/BidCard.vue';
import HomeBidItemsSection from '@/components/home/HomeBidItemsSection.vue';
import HomeEventPopup from '@/components/home/HomeEventPopup.vue';
import HomeFaqsSection from '@/components/home/HomeFaqsSection.vue';
import HomeHeroSection from '@/components/home/HomeHeroSection.vue';
import HomeTestimonialsSection from '@/components/home/HomeTestimonialsSection.vue';
import HomeWinnerPopup from '@/components/home/HomeWinnerPopup.vue';
import { formatPrice, formatMsisdn, getDaysAgo } from '@/lib/utils';
import {
    howToPlay,
    openBids as openBidsRoute,
    tasks,
    trending,
} from '@/routes';

export type Bidder = {
    msisdn: string;
    total_points: string;
};

export type EventPopupItem = {
    title: string;
    bid: Bid;
};

export type Bid = {
    id: number;
    name: string;
    image: string;
    url: string;
    price: string;
    open_points: number;
    rating: string;
    open_date: number;
    status: 0 | 1 | 2;
    created_at: string;
    bid_entry_points: number | null;
    bid_active_points: number | null;
    ends_at: string | null;
    top_bidders?: Bidder[];
};

export type HeroBid = Pick<
    Bid,
    | 'id'
    | 'name'
    | 'image'
    | 'url'
    | 'price'
    | 'open_points'
    | 'rating'
    | 'open_date'
    | 'status'
    | 'created_at'
>;

export type Winner = {
    id: number;
    msisdn: string;
    total_points: number;
    bidid: number;
    created_at: string;
    bid: {
        id: number;
        name: string;
        image: string;
        url: string;
        price: string;
    } | null;
};

export type Review = {
    id: number;
    user_id: string;
    rating: number;
    comment: string;
    social_platform?: string | null;
    social_handle?: string | null;
    bidid: number;
    created_at: string;
    bid: { id: number; name: string; image: string; url: string } | null;
};

const props = defineProps<{
    heroBid: Bid | null;
    recentlyAddedBids: Bid[];
    trendingBids: Bid[];
    openBids: Bid[];
    luxuryBids: Bid[];
    categoryBids: Record<string, Bid[]>;
    bids: Bid[];
    categories: string[];
    winners: Winner[];
    userPoints: number | null;
    reviews: Review[];
    winnerPopup: Winner | null;
    eventPopupBids: EventPopupItem[];
    faqs: { question: string; answer: string }[];
}>();

const bidItemsSectionRef = ref<InstanceType<typeof HomeBidItemsSection> | null>(
    null,
);

function openBidModal(bid: Bid) {
    if (bidItemsSectionRef.value) {
        bidItemsSectionRef.value.openBidModal(bid);
    }
}

const showEventPopup = ref(props.eventPopupBids?.length > 0);

function handlePopupClose() {
    showEventPopup.value = false;
}
const getCategoryIcon = (category: string): string => {
    const map: Record<string, string> = {
        Appliances: 'kitchen',
        Computing: 'computer',
        Electronics: 'devices',
        Fashion: 'checkroom',
        'Gadgets & Accessories': 'headphones',
        Gaming: 'sports_esports',
        'Health & Beauty': 'spa',
        'Home & Office': 'home_work',
        'Musical Instrument': 'piano',
        Supermarket: 'local_grocery_store',
    };

    return map[category] || 'category';
};
</script>

<template>
    <Head title="Home" />
    <HomeWinnerPopup v-if="props.winnerPopup" :winner="props.winnerPopup" />
    <HomeEventPopup
        v-if="showEventPopup"
        :items="props.eventPopupBids"
        @open-bid-modal="openBidModal"
        @close="handlePopupClose"
    />

    <!-- Hidden section to retain the bid modal logic -->
    <HomeBidItemsSection
        ref="bidItemsSectionRef"
        :bids="[]"
        :categories="[]"
        :userPoints="props.userPoints"
        class="hidden"
    />

    <div class="overflow-x-hidden bg-sage-bg text-left font-sans text-ink">
        <HomeHeroSection :getCategoryIcon="getCategoryIcon" />

        <!-- PRODUCT STRIP (8 items - 2 rows of 4) -->
        <div
            class="mx-auto mt-2.5 grid max-w-[1300px] grid-cols-2 gap-2.5 px-4 md:grid-cols-5"
        >
            <BidCard
                v-for="bid in props.bids"
                :key="bid.id"
                :bid="bid"
                @open-bid-modal="openBidModal"
            />
        </div>

        <!-- LIVE TICKER -->
        <div class="my-3 overflow-hidden border-y-2 border-lemon bg-navy py-2">
            <div class="flex animate-marquee items-center whitespace-nowrap">
                <span
                    class="inline-flex items-center gap-2 px-7 text-sm font-bold text-lemon"
                    v-for="bid in props.bids"
                    :key="bid.id"
                >
                    <span
                        class="inline-block h-2 w-2 rounded-full bg-lemon"
                    ></span>
                    {{ bid.name }}
                    <span class="font-extrabold text-white">{{
                        formatPrice(bid.price)
                    }}</span>
                    <span
                        class="ml-1 rounded-sm bg-forest px-2 py-0.5 text-xs font-extrabold text-lemon"
                        v-if="bid.status === 1"
                        >LIVE</span
                    >
                </span>
            </div>
        </div>

        <!-- CATEGORIES -->
        <div class="mx-auto mb-5 max-w-[1300px] px-4">
            <div class="mb-3.5 flex items-center justify-between">
                <div
                    class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                >
                    <span
                        class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                    ></span>
                    Browse Categories
                </div>
                <Link
                    :href="trending.url()"
                    class="text-sm font-bold text-forest hover:underline"
                    >View All →</Link
                >
            </div>
            <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4 lg:grid-cols-8">
                <Link
                    :href="
                        trending.url() + '?category=' + encodeURIComponent(cat)
                    "
                    class="group cursor-pointer rounded-lg border-2 border-transparent bg-white p-3.5 px-2 text-center shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-lemon hover:bg-navy"
                    v-for="cat in props.categories"
                    :key="cat"
                    style="text-decoration: none"
                >
                    <div class="mb-1.5 text-3xl">
                        <span class="material-symbols-outlined">{{
                            getCategoryIcon(cat)
                        }}</span>
                    </div>
                    <div
                        class="text-xs font-extrabold text-ink group-hover:text-lemon"
                    >
                        {{ cat }}
                    </div>
                </Link>
            </div>
        </div>

        <!-- TRENDING BIDS -->
        <div
            class="mx-auto mb-5 max-w-[1300px] px-4"
            v-if="props.trendingBids?.length > 0"
        >
            <div class="mb-3.5 flex items-center justify-between">
                <div
                    class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                >
                    <span
                        class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                    ></span>
                    <span class="pi pi-fire mr-1 text-lg text-amber"></span>
                    Trending Bids
                </div>
                <Link
                    :href="trending.url()"
                    class="text-sm font-bold text-forest hover:underline"
                    >View More →</Link
                >
            </div>
            <div
                class="mx-auto grid max-w-[1300px] grid-cols-2 gap-2.5 md:grid-cols-5"
            >
                <BidCard
                    v-for="bid in props.trendingBids.slice(0, 10)"
                    :key="bid.id"
                    :bid="bid"
                    @open-bid-modal="openBidModal"
                />
            </div>
        </div>

        <!-- RECENTLY ADDED -->
        <div
            class="mx-auto mb-5 max-w-[1300px] px-4"
            v-if="props.recentlyAddedBids?.length > 0"
        >
            <div class="mb-3.5 flex items-center justify-between">
                <div
                    class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                >
                    <span
                        class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                    ></span>
                    <span class="pi pi-sparkles mr-1 text-lg text-amber"></span>
                    Recently Added
                </div>
                <Link
                    :href="trending.url() + '?sort=recent'"
                    class="text-sm font-bold text-forest hover:underline"
                    >View More →</Link
                >
            </div>
            <div
                class="mx-auto grid max-w-[1300px] grid-cols-2 gap-2.5 md:grid-cols-5"
            >
                <BidCard
                    v-for="bid in props.recentlyAddedBids.slice(0, 10)"
                    :key="bid.id"
                    :bid="bid"
                    @open-bid-modal="openBidModal"
                />
            </div>
        </div>

        <!-- FEATURE BANNERS -->
        <div class="mx-auto mb-5 max-w-[1300px] px-4">
            <div class="grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                <div
                    class="relative flex items-center justify-between overflow-hidden rounded-xl bg-linear-to-br from-navy to-forest px-7 py-6 text-white"
                >
                    <div>
                        <div
                            class="mb-2 inline-block rounded-xl bg-lemon/25 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-lemon uppercase"
                        >
                            <span class="pi pi-bullseye mr-1"></span> Task
                            Center
                        </div>
                        <div
                            class="mb-1.5 font-condensed text-3xl leading-tight font-black text-white"
                        >
                            Win More<br />Points Free!
                        </div>
                        <div
                            class="mb-3.5 text-xs leading-snug text-[#cde] opacity-85"
                        >
                            Complete simple tasks to earn bidding points and
                            increase your chances of winning luxury items.
                        </div>
                        <Link
                            :href="tasks.url()"
                            class="inline-block rounded-md bg-lemon px-4 py-2 text-sm font-extrabold text-navy transition-colors hover:bg-amber"
                        >
                            Visit Task Center →
                        </Link>
                    </div>
                    <div
                        class="text-[90px] leading-none font-black opacity-[0.18]"
                    >
                        <span class="pi pi-trophy"></span>
                    </div>
                </div>
                <div
                    class="relative flex items-center justify-between overflow-hidden rounded-xl bg-linear-to-br from-ink to-forest px-7 py-6 text-white"
                >
                    <div>
                        <div
                            class="mb-2 inline-block rounded-xl bg-lemon/25 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-lemon uppercase"
                        >
                            <span class="pi pi-lock mr-1"></span> Verified
                            Integrity
                        </div>
                        <div
                            class="mb-1.5 font-condensed text-3xl leading-tight font-black text-white"
                        >
                            100% Fair<br />&amp; Secure!
                        </div>
                        <div
                            class="mb-3.5 text-xs leading-snug text-[#cde] opacity-85"
                        >
                            Every bid is recorded on our secure database. Fully
                            transparent, fully trustworthy auction process.
                        </div>
                        <Link
                            :href="howToPlay.url()"
                            class="inline-block rounded-md bg-lemon px-4 py-2 text-sm font-extrabold text-navy transition-colors hover:bg-amber"
                        >
                            Learn More →
                        </Link>
                    </div>
                    <div
                        class="text-[90px] leading-none font-black opacity-[0.18]"
                    >
                        <span class="pi pi-shield"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LIVE OPPORTUNITIES -->
        <div
            class="mx-auto mb-5 max-w-[1300px] px-4"
            v-if="props.openBids?.length > 0"
        >
            <div class="mb-3.5 flex items-center justify-between">
                <div
                    class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                >
                    <span
                        class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                    ></span>
                    <span class="pi pi-bolt mr-1 text-lg text-lemon"></span>
                    Live Opportunities
                </div>
                <Link
                    :href="openBidsRoute.url()"
                    class="text-sm font-bold text-forest hover:underline"
                    >View All →
                </Link>
            </div>
            <div
                class="mx-auto grid max-w-[1300px] grid-cols-2 gap-2.5 md:grid-cols-5"
            >
                <BidCard
                    v-for="bid in props.openBids.slice(0, 4)"
                    :key="bid.id"
                    :bid="bid"
                    @open-bid-modal="openBidModal"
                />
            </div>
        </div>

        <!-- LUXURY BIDS -->
        <div
            class="mx-auto mb-5 max-w-[1300px] px-4"
            v-if="props.luxuryBids?.length > 0"
        >
            <div class="mb-3.5 flex items-center justify-between">
                <div
                    class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                >
                    <span
                        class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                    ></span>
                    <span class="pi pi-star mr-1 text-lg text-amber"></span>
                    Premium & Luxury
                </div>
                <Link
                    :href="trending.url() + '?sort=price'"
                    class="text-sm font-bold text-forest hover:underline"
                    >View All →
                </Link>
            </div>
            <div
                class="mx-auto grid max-w-[1300px] grid-cols-2 gap-2.5 md:grid-cols-5"
            >
                <BidCard
                    v-for="bid in props.luxuryBids.slice(0, 10)"
                    :key="bid.id"
                    :bid="bid"
                    @open-bid-modal="openBidModal"
                />
            </div>
        </div>

        <!-- CATEGORY BIDS -->
        <div
            v-for="(bids, categoryName) in props.categoryBids"
            :key="categoryName"
            class="mx-auto mb-5 max-w-[1300px] px-4"
        >
            <template v-if="bids?.length > 0">
                <div class="mb-3.5 flex items-center justify-between">
                    <div
                        class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                    >
                        <span
                            class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                        ></span>
                        <span
                            class="material-symbols-outlined mr-1.5 text-[22px] leading-none text-forest"
                            >{{ getCategoryIcon(String(categoryName)) }}</span
                        >
                        {{ categoryName }}
                    </div>
                    <Link
                        :href="
                            trending.url() +
                            '?category=' +
                            encodeURIComponent(String(categoryName))
                        "
                        class="text-sm font-bold text-forest hover:underline"
                        >View All →
                    </Link>
                </div>
                <div
                    class="mx-auto grid max-w-[1300px] grid-cols-2 gap-2.5 md:grid-cols-5"
                >
                    <BidCard
                        v-for="bid in bids"
                        :key="bid.id"
                        :bid="bid"
                        @open-bid-modal="openBidModal"
                    />
                </div>
            </template>
        </div>

        <!-- RECENT WINNERS -->
        <div
            class="mx-auto mb-5 max-w-[1300px] px-4"
            v-if="props.winners?.length > 0"
        >
            <div class="mb-3.5 flex items-center justify-between">
                <div
                    class="flex items-center font-condensed text-2xl font-extrabold text-ink"
                >
                    <span
                        class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                    ></span>
                    <span class="pi pi-trophy mr-1 text-lg"></span> Recent
                    Winners
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3.5 md:grid-cols-5">
                <div
                    v-for="winner in props.winners"
                    :key="winner.id"
                    class="group relative flex flex-col overflow-hidden rounded-lg border border-sage-border-dark bg-white p-3 shadow-sm transition-colors hover:border-lemon"
                >
                    <div
                        class="mb-3.5 flex h-[120px] items-center justify-center rounded-md bg-sage-bg p-2"
                    >
                        <img
                            v-if="winner.bid?.image"
                            :src="winner.bid.image"
                            :alt="winner.bid.name"
                            class="max-h-full max-w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <span
                            v-else
                            class="pi pi-image text-4xl text-gray-300"
                        ></span>
                    </div>
                    <div
                        class="mb-1 line-clamp-1 text-sm font-bold text-ink"
                        :title="winner.bid?.name || 'Luxury Item'"
                    >
                        {{ winner.bid?.name || 'Luxury Item' }}
                    </div>
                    <div
                        class="mb-3 flex items-center justify-between text-xs text-muted-green"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="pi pi-user text-[10px]"></span>
                            {{ formatMsisdn(winner.msisdn) }}
                        </div>
                        <div class="text-[10px]">
                            {{ getDaysAgo(winner.created_at) }}
                        </div>
                    </div>
                    <div
                        class="mt-auto rounded border border-sage-border bg-sage-bg px-2 py-1.5 text-xs font-extrabold text-forest"
                    >
                        Won with {{ winner.total_points }} pts
                    </div>
                </div>
            </div>
        </div>

        <!-- TESTIMONIALS -->
        <HomeTestimonialsSection
            v-if="props.reviews?.length > 0"
            :reviews="props.reviews"
        />

        <!-- FAQS -->
        <HomeFaqsSection :faqs="props.faqs" />

        <!-- TRUST BAR -->
        <div class="mb-5 border-y-2 border-lemon bg-navy px-4 py-4.5">
            <div
                class="mx-auto grid max-w-[1300px] grid-cols-2 gap-5 text-center lg:grid-cols-4"
            >
                <div>
                    <div class="mb-1.5 text-[26px]">
                        <span class="pi pi-bolt text-white"></span>
                    </div>
                    <div class="text-sm font-extrabold text-lemon">
                        Fast Delivery
                    </div>
                    <div class="text-xs text-[#8aaa80]">
                        Winners receive items within 3–5 working days nationwide
                    </div>
                </div>
                <div>
                    <div class="mb-1.5 text-[26px]">
                        <span class="pi pi-lock text-white"></span>
                    </div>
                    <div class="text-sm font-extrabold text-lemon">
                        Secure Payments
                    </div>
                    <div class="text-xs text-[#8aaa80]">
                        All transactions protected with bank-grade encryption
                    </div>
                </div>
                <div>
                    <div class="mb-1.5 text-[26px]">
                        <span class="pi pi-verified text-white"></span>
                    </div>
                    <div class="text-sm font-extrabold text-lemon">
                        Verified Winners
                    </div>
                    <div class="text-xs text-[#8aaa80]">
                        Every winner is verified before item dispatch
                    </div>
                </div>
                <div>
                    <div class="mb-1.5 text-[26px]">
                        <span class="pi pi-headphones text-white"></span>
                    </div>
                    <div class="text-sm font-extrabold text-lemon">
                        24/7 Support
                    </div>
                    <div class="text-xs text-[#8aaa80]">
                        Our team is available round-the-clock for assistance
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
