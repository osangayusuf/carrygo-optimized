<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import OpenBidsSection from '@/components/open-bids/OpenBidsSection.vue';
import type { Bid } from '@/pages/Home.vue';
import type { LengthAwarePaginator } from '@/types';

defineProps<{
    bids: LengthAwarePaginator<Bid>;
    userPoints: number | null;
}>();
</script>

<template>
    <Head title="Open Bids" />
    <OpenBidsSection :bids="bids" :userPoints="userPoints" />
</template>
