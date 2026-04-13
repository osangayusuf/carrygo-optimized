<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import HomeBidItemsSection from '@/components/home/HomeBidItemsSection.vue';
import HomeHeroSection from '@/components/home/HomeHeroSection.vue';
import HomePromoSection from '@/components/home/HomePromoSection.vue';
import HomeWinnersSection from '@/components/home/HomeWinnersSection.vue';
import HomeTestimonialsSection from '@/components/home/HomeTestimonialsSection.vue';

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
    bids: Bid[];
    winners: Winner[];
    userPoints: number | null;
    reviews: Review[];
}>();
</script>

<template>
    <Head title="Home" />
    <HomeHeroSection :heroBid="props.heroBid" />
    <HomeWinnersSection :winners="props.winners" />
    <HomeTestimonialsSection v-if="props.reviews?.length > 0" :reviews="props.reviews" />
    <HomeBidItemsSection :bids="props.bids" :userPoints="props.userPoints" />
    <HomePromoSection />
</template>
