<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { HeroBid } from '@/pages/Home.vue';
import { howToPlay, trending } from '@/routes';

const FALLBACK_IMAGE =
    'https://lh3.googleusercontent.com/aida-public/AB6AXuBHjOXTZ8fUFaJuDZIWWWDx6eEMzHkxy6MXp0cUyhnl0shzmwRHLXNU257TOk4kpsZiWukqBCu831ok55wdJu6guTlZ-D3EPbrqZ8_nBrIGEVIeX6ZJFbRGoDCwx1szVZdNj5_HU60NW_HWfO7VP_8kKOO1nt40zZYk0gTM2SuHwAI_MAGJIYsbuu3iWHYAkqSs4EjnEKqgW0W9GxsAzRaPDMj8rbiKTHzcQhH7nztmeXYMgGteKnWO-UPUEcTtGrsD0t9yRJR0';

const props = defineProps<{
    heroBid: HeroBid | null;
}>();

const heroName = computed(() => props.heroBid?.name ?? 'Rolex Submariner');
const heroImage = computed(() => props.heroBid?.image ?? FALLBACK_IMAGE);
const heroAlt = computed(() => props.heroBid?.name ?? 'Luxury Watch');

const heroPrice = computed(() => {
    if (!props.heroBid) {
        return '₦ 1,250,000';
    }

    const num = parseFloat(props.heroBid.price);

    return Number.isNaN(num)
        ? props.heroBid.price
        : `₦ ${num.toLocaleString()}`;
});
</script>

<template>
    <section
        class="hero-pattern relative flex min-h-[819px] items-center overflow-hidden px-8"
    >
        <div
            class="mx-auto grid w-full max-w-screen-2xl grid-cols-1 items-center gap-12 lg:grid-cols-2"
        >
            <div class="z-10">
                <span
                    class="mb-6 inline-block rounded-full bg-secondary-container px-4 py-1.5 text-xs font-bold tracking-widest text-on-secondary-container uppercase"
                >
                    Premium Auction Concierge
                </span>
                <h1
                    class="mb-8 font-headline text-6xl leading-[0.9] font-extrabold tracking-tighter text-on-surface md:text-8xl"
                >
                    Bid, Win,
                    <br />
                    <span
                        class="bg-linear-to-r from-primary to-primary-container bg-clip-text text-transparent"
                        >&amp; Save.</span
                    >
                </h1>
                <p
                    class="mb-10 max-w-lg text-lg leading-relaxed text-secondary"
                >
                    Experience the most transparent executive bidding platform.
                    Curated luxury items, verified winners, and a seamless
                    digital concierge service.
                </p>
                <div class="flex flex-wrap gap-4">
                    <Link
                        :href="trending.url()"
                        class="flex items-center gap-3 rounded-xl bg-primary px-10 py-5 text-lg font-bold text-on-primary transition-all hover:shadow-2xl hover:shadow-primary/30 active:scale-[0.98]"
                    >
                        Start Bidding
                        <span class="material-symbols-outlined"
                            >trending_up</span
                        >
                    </Link>
                    <Link
                        :href="howToPlay.url()"
                        class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest px-10 py-5 text-lg font-bold text-on-surface transition-all hover:bg-surface-container-low"
                    >
                        How it Works
                    </Link>
                </div>
            </div>
            <div class="relative hidden lg:block">
                <div
                    class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-primary-container/20 blur-[120px]"
                />
                <div
                    class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-tertiary-container/20 blur-[100px]"
                />
                <div
                    class="relative z-10 rotate-3 rounded-3xl bg-surface-container-lowest p-6 shadow-2xl transition-transform duration-700 hover:rotate-0"
                >
                    <img
                        :alt="heroAlt"
                        class="h-[500px] w-full rounded-2xl object-cover"
                        :src="heroImage"
                    />
                    <div
                        class="absolute -bottom-10 -left-10 max-w-[200px] animate-bounce-slow rounded-2xl bg-white p-6 shadow-xl"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-error" />
                            <span
                                class="text-xs font-bold tracking-wide text-secondary uppercase"
                                >Ending Soon</span
                            >
                        </div>
                        <p class="text-sm font-bold">{{ heroName }}</p>
                        <p class="text-lg font-black text-primary">
                            {{ heroPrice }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
