<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { home, trending } from '@/routes';

export type Review = {
    id: number;
    user_id: string;
    rating: number;
    comment: string;
    social_platform?: string | null;
    social_handle?: string | null;
    bidid?: number | null;
    created_at: string;
    bid?: { id: number; name: string; image: string; url: string; price: string; } | null;
    user?: { msisdn: string; name?: string | null } | null;
};

const props = defineProps<{
    review: Review;
    isWinner: boolean;
    cardImageUrl: string;
    shareUrl: string;
}>();

const copied = ref(false);

function maskPhone(msisdn: string): string {
    const digits = msisdn.replace(/\D/g, '');

    if (digits.length < 7) return msisdn;

    return `+${digits.slice(0, 3)} ${digits.slice(3, 6)} *** ${digits.slice(-4)}`;
}

const maskedUserPhone = computed(() => {
    return maskPhone(props.review.user_id);
});

// Dynamic sharing text
const shareText = computed(() => {
    if (props.isWinner && props.review.bid) {
        return `I just won ${props.review.bid.name} on CarryGo! Check out my review! 🏆🎉`;
    }

    const stars = '⭐'.repeat(props.review.rating);

    return `CarryGo is amazing! Check out my review! ${stars}`;
});

const whatsappUrl = computed(() => {
    return `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value + ' ' + props.shareUrl)}`;
});

const facebookUrl = computed(() => {
    return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.shareUrl)}`;
});

const xUrl = computed(() => {
    const params = new URLSearchParams({
        text: shareText.value,
        url: props.shareUrl,
    });

    return `https://x.com/intent/tweet?${params.toString()}`;
});

function copyLink() {
    navigator.clipboard.writeText(props.shareUrl).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
}
</script>

<template>

    <Head>
        <title>Review of {{ props.review.bid?.name || 'CarryGo' }} - CarryGo</title>
        <meta name="description" :content="props.review.comment" />

        <!-- Open Graph Meta Tags -->
        <meta property="og:title"
            :content="props.isWinner ? `Winner Review: ${props.review.bid?.name}` : 'CarryGo Verified User Review'" />
        <meta property="og:description" :content="props.review.comment" />
        <meta property="og:image" :content="props.cardImageUrl" />
        <meta property="og:url" :content="props.shareUrl" />
        <meta property="og:type" content="website" />

        <!-- Twitter Card Tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title"
            :content="props.isWinner ? `Winner Review: ${props.review.bid?.name}` : 'CarryGo Verified User Review'" />
        <meta name="twitter:description" :content="props.review.comment" />
        <meta name="twitter:image" :content="props.cardImageUrl" />
    </Head>

    <div class="min-h-[80vh] bg-sage-bg py-12 px-4 sm:px-6 lg:px-8 flex flex-col justify-between font-sans">

        <!-- Top Back Nav -->
        <div class="max-w-4xl mx-auto w-full mb-6">
            <Link :href="home.url()"
                class="inline-flex items-center text-forest text-sm font-bold hover:underline gap-1 transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to CarryGo
            </Link>
        </div>

        <!-- Main Review Showcase Card -->
        <div
            class="max-w-4xl mx-auto w-full bg-navy rounded-3xl overflow-hidden shadow-2xl border-4 border-lemon relative group flex flex-col md:flex-row min-h-[450px]">

            <!-- Absolute Top Left Corner Brackets (Stylized Graphic) -->
            <div class="absolute top-4 left-4 w-6 h-6 border-t-2 border-l-2 border-lemon/20 rounded-tl-md"></div>
            <div class="absolute bottom-4 right-4 w-6 h-6 border-b-2 border-r-2 border-lemon/20 rounded-br-md"></div>

            <!-- Left / Centered Pane: Review Content -->
            <div class="flex-1 p-8 sm:p-12 flex flex-col justify-between"
                :class="props.review.bid ? 'md:max-w-[60%]' : 'text-center md:max-w-full items-center'">

                <div>
                    <!-- Branding Header and Badge -->
                    <div class="flex items-center gap-4 flex-wrap mb-8" :class="!props.review.bid && 'justify-center'">
                        <span class="font-condensed text-3xl font-black text-white tracking-wider">CarryGo</span>
                        <div class="px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider uppercase"
                            :class="props.isWinner ? 'bg-lemon text-navy' : 'bg-sage-light text-forest'">
                            {{ props.isWinner ? 'Verified Winner' : 'Verified User' }}
                        </div>
                    </div>

                    <!-- Masked Winner Phone -->
                    <div class="mb-4">
                        <p class="text-xl sm:text-2xl font-black text-white tracking-wide">
                            {{ maskedUserPhone }}
                        </p>
                    </div>

                    <!-- Star Ratings -->
                    <div class="flex text-amber gap-1.5 mb-6" :class="!props.review.bid && 'justify-center'">
                        <span v-for="i in props.review.rating" :key="'filled-' + i"
                            class="pi pi-star-fill text-lg sm:text-xl"></span>
                        <span v-for="i in 5 - props.review.rating" :key="'empty-' + i"
                            class="pi pi-star text-lg sm:text-xl opacity-20"></span>
                    </div>

                    <!-- Giant Quote Backdrop for General Reviews -->
                    <div v-if="!props.review.bid"
                        class="text-6xl text-lemon/10 font-serif leading-none h-4 select-none animate-bounce-slow">“
                    </div>

                    <!-- Comment text wrapped -->
                    <p class="text-white text-base sm:text-lg md:text-xl leading-relaxed italic grow font-medium"
                        :class="props.review.bid ? 'text-left' : 'max-w-2xl text-center px-4'">
                        "{{ props.review.comment }}"
                    </p>

                    <div v-if="!props.review.bid"
                        class="text-6xl text-lemon/10 font-serif leading-none h-4 mt-4 select-none">”</div>
                </div>

                <!-- Footer URL Link -->
                <div class="mt-8 border-t border-white/10 pt-4 w-full"
                    :class="props.review.bid ? 'text-left' : 'text-center'">
                    <span class="text-xs text-[#8aaa80] font-semibold">www.carrygo.test</span>
                </div>
            </div>

            <!-- Right Pane: Item Frame (Shown for all reviews with an associated bid) -->
            <div v-if="props.review.bid"
                class="md:w-[40%] bg-white p-8 border-t md:border-t-0 md:border-l border-sage-border-dark flex flex-col justify-between items-center text-center">

                <!-- Product Image Area -->
                <div class="w-full flex-1 flex items-center justify-center p-4 min-h-[220px]">
                    <img v-if="props.review.bid.image" :src="props.review.bid.image" :alt="props.review.bid.name"
                        class="max-h-[200px] max-w-full object-contain drop-shadow-md hover:scale-105 transition-transform duration-300">
                    <span v-else class="pi pi-image text-5xl text-gray-300"></span>
                </div>

                <!-- Product Details -->
                <div class="w-full mt-4">
                    <h3 class="font-headline font-bold text-ink text-sm sm:text-base line-clamp-2 mb-3">
                        {{ props.review.bid.name }}
                    </h3>

                    <!-- Value pill -->
                    <div class="bg-sage-light border border-sage-border rounded-xl py-2.5 px-4 inline-block w-full">
                        <p class="text-xs font-semibold text-muted-green">
                            {{ props.isWinner ? 'Won Item Value' : 'Item Value' }}
                        </p>
                        <p class="text-base font-black text-forest">₦{{ props.review.bid.price }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sharing Panel / Action Grid -->
        <div class="max-w-4xl mx-auto w-full mt-8 bg-white rounded-2xl border border-sage-border p-6 sm:p-8 shadow-sm">
            <h3 class="font-headline font-extrabold text-ink text-lg mb-4 text-center">
                Share this Review with the World!
            </h3>

            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">

                <!-- WhatsApp Share Button -->
                <a :href="whatsappUrl" target="_blank"
                    class="flex flex-col items-center justify-center p-4 bg-[#25D366] hover:bg-[#20ba5a] text-white rounded-xl font-bold transition-all text-center group shadow-xs hover:-translate-y-0.5"
                    style="text-decoration: none;">
                    <span class="pi pi-whatsapp text-2xl mb-2 group-hover:scale-110 transition-transform"></span>
                    <span class="text-xs sm:text-sm">WhatsApp</span>
                </a>

                <!-- Facebook Share Button -->
                <a :href="facebookUrl" target="_blank"
                    class="flex flex-col items-center justify-center p-4 bg-[#1877F2] hover:bg-[#166fe5] text-white rounded-xl font-bold transition-all text-center group shadow-xs hover:-translate-y-0.5"
                    style="text-decoration: none;">
                    <span class="pi pi-facebook text-2xl mb-2 group-hover:scale-110 transition-transform"></span>
                    <span class="text-xs sm:text-sm">Facebook</span>
                </a>

                <!-- X Share Button -->
                <a :href="xUrl" target="_blank"
                    class="flex flex-col items-center justify-center p-4 bg-black hover:bg-gray-900 text-white rounded-xl font-bold transition-all text-center group shadow-xs hover:-translate-y-0.5"
                    style="text-decoration: none;">
                    <span
                        class="text-2xl mb-2 font-black leading-none group-hover:scale-110 transition-transform">𝕏</span>
                    <span class="text-xs sm:text-sm">X</span>
                </a>

                <!-- Download Image Card Button (Points to dynamic PNG generator) -->
                <a :href="props.cardImageUrl" download="carrygo-review-card.png"
                    class="flex flex-col items-center justify-center p-4 bg-navy hover:bg-forest text-lemon rounded-xl font-bold transition-all text-center group shadow-xs hover:-translate-y-0.5"
                    style="text-decoration: none;">
                    <span class="pi pi-download text-2xl mb-2 group-hover:scale-110 transition-transform"></span>
                    <span class="text-xs sm:text-sm">Download Card</span>
                </a>

                <!-- Copy Link Button -->
                <button @click="copyLink"
                    class="flex flex-col items-center justify-center p-4 rounded-xl font-bold transition-all text-center group border border-sage-border shadow-xs hover:-translate-y-0.5 cursor-pointer"
                    :class="copied ? 'bg-forest text-lemon border-forest' : 'bg-white hover:bg-sage-bg text-ink'">
                    <span class="pi text-2xl mb-2 group-hover:scale-110 transition-transform"
                        :class="copied ? 'pi-check' : 'pi-link'"></span>
                    <span class="text-xs sm:text-sm">{{ copied ? 'Copied Link!' : 'Copy Link' }}</span>
                </button>
            </div>

            <!-- Platforms disclaimer / Instagram TikTok helper -->
            <div class="mt-5 border-t border-sage-border pt-4 text-center">
                <p class="text-xs text-muted-green leading-relaxed max-w-lg mx-auto">
                    💡 **Instagram & TikTok Sharing**: Download the image card above, copy the link, and post them
                    directly to your stories or feed!
                </p>
            </div>
        </div>

        <!-- Conversion Driver Section (Call to Action) -->
        <div
            class="max-w-4xl mx-auto w-full mt-6 text-center bg-linear-to-r from-forest to-navy rounded-2xl p-6 sm:p-8 text-white relative overflow-hidden shadow-sm">
            <div class="absolute -right-4 -bottom-4 text-9xl font-black opacity-[0.07] select-none"><span
                    class="pi pi-bolt"></span></div>

            <h3 class="font-headline font-extrabold text-xl sm:text-2xl mb-2 text-white">
                Want to Win Luxury Items Like This?
            </h3>
            <p class="text-xs sm:text-sm text-sage-light/80 mb-5 max-w-xl mx-auto leading-relaxed">
                Join our fair, secure community of bidders. Complete tasks to earn free points and place bids on
                high-value products starting today!
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link v-if="props.review.bid" :href="trending.url()"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl bg-lemon text-navy font-bold hover:bg-amber transition-colors text-sm"
                    style="text-decoration:none;">
                    Bid on Similar Items →
                </Link>
                <Link v-else :href="home.url()"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl bg-lemon text-navy font-bold hover:bg-amber transition-colors text-sm"
                    style="text-decoration:none;">
                    Explore Live Bids Now →
                </Link>
                <Link :href="home.url()"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl border border-white/20 text-white font-bold hover:bg-white/10 transition-colors text-sm"
                    style="text-decoration:none;">
                    Learn How to Play
                </Link>
            </div>
        </div>

    </div>
</template>
