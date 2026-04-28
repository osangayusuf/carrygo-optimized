<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useIntervalFn } from '@vueuse/core';
import { ref } from 'vue';
import CompactBidCard from '@/components/home/CompactBidCard.vue';
import CompactWinnerCard from '@/components/home/CompactWinnerCard.vue';
import HomeBidItemsSection from '@/components/home/HomeBidItemsSection.vue';
import HomePromoSection from '@/components/home/HomePromoSection.vue';
import HomeSectionRow from '@/components/home/HomeSectionRow.vue';
import HomeTestimonialsSection from '@/components/home/HomeTestimonialsSection.vue';
import HomeWinnerPopup from '@/components/home/HomeWinnerPopup.vue';
import { trending, openBids as openBidsRoute, history, home } from '@/routes';

export type Bidder = {
    msisdn: string;
    total_points: string;
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
    user_id: number;
    rating: number;
    comment: string;
    social_platform?: string | null;
    social_handle?: string | null;
    bidid: number;
    created_at: string;
    bid: {
        id: number;
        name: string;
        image: string;
        url: string;
    } | null;
    user: {
        id: number;
        msisdn: string;
    } | null;
};

const props = defineProps<{
    heroBid: Bid | null;
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
}>();

const bidItemsSectionRef = ref<InstanceType<typeof HomeBidItemsSection> | null>(null);
const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();

const search = ref(params.get('search') ?? '');


function openBidModal(bid: Bid) {
    if (bidItemsSectionRef.value) {
        bidItemsSectionRef.value.openBidModal(bid);
    }
}


function visit(extra: Record<string, string | number> = {}) {
    router.get(
        home.url(),
        {
            ...(search.value ? { search: search.value } : {}),
            ...extra,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

const onSearch = useDebounceFn(() => visit(), 400);

const bannerImages = [
    'images/banner1.jpeg',
    'images/banner2.png',
    'images/banner3.png',
];
const currentBannerIndex = ref(0);

useIntervalFn(() => {
    currentBannerIndex.value = (currentBannerIndex.value + 1) % bannerImages.length;
}, 3000);

</script>

<template>

    <Head title="Home" />
    <HomeWinnerPopup v-if="props.winnerPopup" :winner="props.winnerPopup" />

    <div class="text-center py-4">
        <h1 class="font-headline text-2xl md:text-4xl lg:text-5xl font-extrabold py-5">Nigeria's First Digital E-Bidding
            Platform</h1>
        <div class="flex w-full flex-col gap-4 ">
            <div class="group relative w-4/5 mx-auto">
                <input v-model="search"
                    class="block w-full rounded-full border-secondary border bg-surface-container-low px-6 py-3.5 text-sm font-medium focus:ring-2 focus:ring-primary-container"
                    placeholder="Search premium items..." type="search" @input="onSearch" />
                <span
                    class="material-symbols-outlined absolute top-1/2 right-4 -translate-y-1/2 text-outline">search</span>
            </div>
        </div>
        <div class="w-10/12 md:w-4/5 mx-auto my-5 lg:my-10 overflow-hidden">
            <div class="flex transition-transform duration-500 ease-in-out" :style="{ transform: `translateX(-${currentBannerIndex * 100}%)` }">
                <img v-for="(img, index) in bannerImages" :key="index" :src="img" alt="Banner" class="w-full shrink-0 object-cover">
            </div>
        </div>
    </div>

    <div class="bg-surface-container-low md:py-5">
        <HomeSectionRow v-if="props.trendingBids?.length > 0" title="Trending Bids" :view-more-route="trending.url()">
            <CompactBidCard v-for="bid in props.trendingBids" :key="bid.id" :bid="bid" @click="openBidModal(bid)" />
        </HomeSectionRow>
    </div>

    <HomeBidItemsSection ref="bidItemsSectionRef" :bids="props.bids" :categories="props.categories"
        :userPoints="props.userPoints" />

    <div class="bg-surface-container-low md:py-5">
        <HomeSectionRow v-if="props.luxuryBids?.length > 0" title="Luxury Picks"
            :view-more-route="trending.url({ sort: 'value_desc' })" view-more-text="Explore Premium">
            <CompactBidCard v-for="bid in props.luxuryBids" :key="bid.id" :bid="bid" @click="openBidModal(bid)" />
        </HomeSectionRow>

        <template v-for="(bids, cat) in props.categoryBids" :key="cat">
            <HomeSectionRow v-if="bids?.length > 0" :title="cat" :view-more-route="trending.url({ category: cat })"
                view-more-text="Explore Category">
                <CompactBidCard v-for="bid in bids" :key="bid.id" :bid="bid" @click="openBidModal(bid)" />
            </HomeSectionRow>
        </template>
    </div>

    <div class="bg-surface-container-low md:py-5">
        <HomeSectionRow v-if="props.winners?.length > 0" title="Latest Winners" :view-more-route="history.url()">
            <CompactWinnerCard v-for="winner in props.winners" :key="winner.id" :winner="winner" />
        </HomeSectionRow>

        <HomeSectionRow v-if="props.openBids?.length > 0" title="Open Bids" :view-more-route="openBidsRoute.url()">
            <CompactBidCard v-for="bid in props.openBids" :key="bid.id" :bid="bid" @click="openBidModal(bid)" />
        </HomeSectionRow>
    </div>

    <HomeTestimonialsSection v-if="props.reviews?.length > 0" :reviews="props.reviews" />
    <HomePromoSection />
</template>
