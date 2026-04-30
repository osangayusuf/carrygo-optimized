<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import { ref, computed } from 'vue';
import { howToPlay, trending } from '@/routes';

const page = usePage();
const props = defineProps<{ getCategoryIcon: (category: string) => string }>();
const categories = computed(() => (page.props.categories as string[]) ?? []);

const bannerImages = [
    '/images/banner1.jpeg',
    '/images/banner2.png',
    '/images/banner3.png',
];
const currentBannerIndex = ref(0);

useIntervalFn(() => {
    currentBannerIndex.value = (currentBannerIndex.value + 1) % bannerImages.length;
}, 3000);
</script>

<template>
    <!-- HERO -->
    <section class="max-w-7xl mx-auto mt-2.5 px-4 md:grid gap-2.5 items-stretch"
        style="grid-template-columns: 1fr 4fr;">
        <!-- LEFT CATEGORIES NAV -->
        <div class="bg-white rounded-xl overflow-hidden border border-outline-variant flex-col hidden md:flex">
            <Link v-for="cat in categories" :key="cat" :href="trending.url({ category: cat })"
                class="flex items-center gap-2.5 px-3.5 text-sm font-bold text-ink no-underline border-b border-sage-tint transition-all duration-150 flex-1 whitespace-nowrap last:border-b-0 hover:bg-sage-tint hover:text-primary hover:pl-4.5">
                <span v-if="props.getCategoryIcon(cat)" class="material-symbols-outlined mr-1 text-sm">
                    {{ getCategoryIcon(cat) }}
                </span>
                {{ cat }} <i class="pi pi-chevron-right ml-auto text-xs"></i>
            </Link>
        </div>

        <!-- MAIN BANNER -->
        <div class="relative rounded-xl overflow-hidden bg-forest-dark flex items-stretch min-h-84">
            <div class="grow py-7.5 px-8 flex flex-col justify-center z-2">
                <div
                    class="bg-lemon text-navy text-xs font-extrabold py-1 px-3 rounded-full inline-flex items-center gap-1 uppercase tracking-wider self-start mb-3.5">
                    🇳🇬 Nigeria's #1 Auction Platform
                </div>
                <h1 class="font-condensed text-5xl font-black text-white leading-none mb-2.5">
                    Win More.<span class="text-lemon block">Carry More.</span>
                </h1>
                <p class="text-sm text-white/65 mb-5.5 leading-relaxed">
                    Bid on premium luxury items. Pay nothing if you lose.
                </p>
                <div class="flex gap-2.5 mb-6">
                    <Link :href="trending.url()"
                        class="bg-lemon text-navy border-none py-2.5 px-6 rounded-lg text-sm font-extrabold cursor-pointer whitespace-nowrap">
                        Start Bidding
                    </Link>
                    <Link :href="howToPlay.url()"
                        class="bg-transparent text-lemon border-2 border-lemon py-2 px-5 rounded-lg text-sm font-bold cursor-pointer whitespace-nowrap">
                        How It Works
                    </Link>
                </div>
                <div class="flex gap-7">
                    <div>
                        <span class="text-2xl font-black text-lemon block leading-none">50K+</span>
                        <span class="text-xs text-white/55 block mt-0.5">Happy Winners</span>
                    </div>
                    <div>
                        <span class="text-2xl font-black text-lemon block leading-none">₦0</span>
                        <span class="text-xs text-white/55 block mt-0.5">Lost Bid Cost</span>
                    </div>
                    <div>
                        <span class="text-2xl font-black text-lemon block leading-none">100%</span>
                        <span class="text-xs text-white/55 block mt-0.5">Transparent</span>
                    </div>
                </div>
            </div>

            <div class="shrink-0 relative overflow-hidden hero-banner-right-bg w-1/2 select-none hidden md:flex">
                <div class="w-full h-full relative overflow-hidden flex items-center justify-center" id="heroCarousel">
                    <div class="flex w-full h-full items-center transition-transform duration-500 ease-in-out"
                        :style="{ transform: `translateX(-${currentBannerIndex * 100}%)` }">
                        <div v-for="(img, index) in bannerImages" :key="index"
                            class="min-w-full flex flex-col items-center justify-center">
                            <img :src="img" alt="Banner"
                                class="w-full h-auto object-contain animate-float-img rounded-md px-7">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
