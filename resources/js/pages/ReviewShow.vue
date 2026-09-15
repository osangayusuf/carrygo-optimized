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
    bid?: {
        id: number;
        name: string;
        image: string;
        url: string;
        price: string;
    } | null;
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

    if (digits.length < 7) {
        return msisdn;
    }

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
        <title>
            Review of {{ props.review.bid?.name || 'CarryGo' }} - CarryGo
        </title>
        <meta name="description" :content="props.review.comment" />

        <!-- Open Graph Meta Tags -->
        <meta
            property="og:title"
            :content="
                props.isWinner
                    ? `Winner Review: ${props.review.bid?.name}`
                    : 'CarryGo Verified User Review'
            "
        />
        <meta property="og:description" :content="props.review.comment" />
        <meta property="og:image" :content="props.cardImageUrl" />
        <meta property="og:url" :content="props.shareUrl" />
        <meta property="og:type" content="website" />

        <!-- Twitter Card Tags -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            :content="
                props.isWinner
                    ? `Winner Review: ${props.review.bid?.name}`
                    : 'CarryGo Verified User Review'
            "
        />
        <meta name="twitter:description" :content="props.review.comment" />
        <meta name="twitter:image" :content="props.cardImageUrl" />
    </Head>

    <div
        class="flex min-h-[80vh] flex-col justify-between bg-sage-bg px-4 py-12 font-sans sm:px-6 lg:px-8"
    >
        <!-- Top Back Nav -->
        <div class="mx-auto mb-6 w-full max-w-4xl">
            <Link
                :href="home.url()"
                class="inline-flex items-center gap-1 text-sm font-bold text-forest transition-colors hover:underline"
            >
                <span class="material-symbols-outlined text-sm"
                    >arrow_back</span
                >
                Back to CarryGo
            </Link>
        </div>

        <!-- Main Review Showcase Card -->
        <div
            class="group relative mx-auto flex min-h-[450px] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border-4 border-lemon bg-navy shadow-2xl md:flex-row"
        >
            <!-- Absolute Top Left Corner Brackets (Stylized Graphic) -->
            <div
                class="absolute top-4 left-4 h-6 w-6 rounded-tl-md border-t-2 border-l-2 border-lemon/20"
            ></div>
            <div
                class="absolute right-4 bottom-4 h-6 w-6 rounded-br-md border-r-2 border-b-2 border-lemon/20"
            ></div>

            <!-- Left / Centered Pane: Review Content -->
            <div
                class="flex flex-1 flex-col justify-between p-8 sm:p-12"
                :class="
                    props.review.bid
                        ? 'md:max-w-[60%]'
                        : 'items-center text-center md:max-w-full'
                "
            >
                <div>
                    <!-- Branding Header and Badge -->
                    <div
                        class="mb-8 flex flex-wrap items-center gap-4"
                        :class="!props.review.bid && 'justify-center'"
                    >
                        <span
                            class="font-condensed text-3xl font-black tracking-wider text-white"
                            >CarryGo</span
                        >
                        <div
                            class="rounded-full px-3 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                            :class="
                                props.isWinner
                                    ? 'bg-lemon text-navy'
                                    : 'bg-sage-light text-forest'
                            "
                        >
                            {{
                                props.isWinner
                                    ? 'Verified Winner'
                                    : 'Verified User'
                            }}
                        </div>
                    </div>

                    <!-- Masked Winner Phone -->
                    <div class="mb-4">
                        <p
                            class="text-xl font-black tracking-wide text-white sm:text-2xl"
                        >
                            {{ maskedUserPhone }}
                        </p>
                    </div>

                    <!-- Star Ratings -->
                    <div
                        class="mb-6 flex gap-1.5 text-amber"
                        :class="!props.review.bid && 'justify-center'"
                    >
                        <span
                            v-for="i in props.review.rating"
                            :key="'filled-' + i"
                            class="pi pi-star-fill text-lg sm:text-xl"
                        ></span>
                        <span
                            v-for="i in 5 - props.review.rating"
                            :key="'empty-' + i"
                            class="pi pi-star text-lg opacity-20 sm:text-xl"
                        ></span>
                    </div>

                    <!-- Giant Quote Backdrop for General Reviews -->
                    <div
                        v-if="!props.review.bid"
                        class="h-4 animate-bounce-slow font-serif text-6xl leading-none text-lemon/10 select-none"
                    >
                        “
                    </div>

                    <!-- Comment text wrapped -->
                    <p
                        class="grow text-base leading-relaxed font-medium text-white italic sm:text-lg md:text-xl"
                        :class="
                            props.review.bid
                                ? 'text-left'
                                : 'max-w-2xl px-4 text-center'
                        "
                    >
                        "{{ props.review.comment }}"
                    </p>

                    <div
                        v-if="!props.review.bid"
                        class="mt-4 h-4 font-serif text-6xl leading-none text-lemon/10 select-none"
                    >
                        ”
                    </div>
                </div>

                <!-- Footer URL Link -->
                <div
                    class="mt-8 w-full border-t border-white/10 pt-4"
                    :class="props.review.bid ? 'text-left' : 'text-center'"
                >
                    <span class="text-xs font-semibold text-[#8aaa80]"
                        >www.carrygo.test</span
                    >
                </div>
            </div>

            <!-- Right Pane: Item Frame (Shown for all reviews with an associated bid) -->
            <div
                v-if="props.review.bid"
                class="flex flex-col items-center justify-between border-t border-sage-border-dark bg-white p-8 text-center md:w-[40%] md:border-t-0 md:border-l"
            >
                <!-- Product Image Area -->
                <div
                    class="flex min-h-[220px] w-full flex-1 items-center justify-center p-4"
                >
                    <img
                        v-if="props.review.bid.image"
                        :src="props.review.bid.image"
                        :alt="props.review.bid.name"
                        class="max-h-[200px] max-w-full object-contain drop-shadow-md transition-transform duration-300 hover:scale-105"
                    />
                    <span
                        v-else
                        class="pi pi-image text-5xl text-gray-300"
                    ></span>
                </div>

                <!-- Product Details -->
                <div class="mt-4 w-full">
                    <h3
                        class="mb-3 line-clamp-2 font-headline text-sm font-bold text-ink sm:text-base"
                    >
                        {{ props.review.bid.name }}
                    </h3>

                    <!-- Value pill -->
                    <div
                        class="inline-block w-full rounded-xl border border-sage-border bg-sage-light px-4 py-2.5"
                    >
                        <p class="text-xs font-semibold text-muted-green">
                            {{
                                props.isWinner ? 'Won Item Value' : 'Item Value'
                            }}
                        </p>
                        <p class="text-base font-black text-forest">
                            ₦{{ props.review.bid.price }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sharing Panel / Action Grid -->
        <div
            class="mx-auto mt-8 w-full max-w-4xl rounded-2xl border border-sage-border bg-white p-6 shadow-sm sm:p-8"
        >
            <h3
                class="mb-4 text-center font-headline text-lg font-extrabold text-ink"
            >
                Share this Review with the World!
            </h3>

            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
                <!-- WhatsApp Share Button -->
                <a
                    :href="whatsappUrl"
                    target="_blank"
                    class="group flex flex-col items-center justify-center rounded-xl bg-[#25D366] p-4 text-center font-bold text-white shadow-xs transition-all hover:-translate-y-0.5 hover:bg-[#20ba5a]"
                    style="text-decoration: none"
                >
                    <span
                        class="pi pi-whatsapp mb-2 text-2xl transition-transform group-hover:scale-110"
                    ></span>
                    <span class="text-xs sm:text-sm">WhatsApp</span>
                </a>

                <!-- Facebook Share Button -->
                <a
                    :href="facebookUrl"
                    target="_blank"
                    class="group flex flex-col items-center justify-center rounded-xl bg-[#1877F2] p-4 text-center font-bold text-white shadow-xs transition-all hover:-translate-y-0.5 hover:bg-[#166fe5]"
                    style="text-decoration: none"
                >
                    <span
                        class="pi pi-facebook mb-2 text-2xl transition-transform group-hover:scale-110"
                    ></span>
                    <span class="text-xs sm:text-sm">Facebook</span>
                </a>

                <!-- X Share Button -->
                <a
                    :href="xUrl"
                    target="_blank"
                    class="group flex flex-col items-center justify-center rounded-xl bg-black p-4 text-center font-bold text-white shadow-xs transition-all hover:-translate-y-0.5 hover:bg-gray-900"
                    style="text-decoration: none"
                >
                    <span
                        class="mb-2 text-2xl leading-none font-black transition-transform group-hover:scale-110"
                        >𝕏</span
                    >
                    <span class="text-xs sm:text-sm">X</span>
                </a>

                <!-- Download Image Card Button (Points to dynamic PNG generator) -->
                <a
                    :href="props.cardImageUrl"
                    download="carrygo-review-card.png"
                    class="group flex flex-col items-center justify-center rounded-xl bg-navy p-4 text-center font-bold text-lemon shadow-xs transition-all hover:-translate-y-0.5 hover:bg-forest"
                    style="text-decoration: none"
                >
                    <span
                        class="pi pi-download mb-2 text-2xl transition-transform group-hover:scale-110"
                    ></span>
                    <span class="text-xs sm:text-sm">Download Card</span>
                </a>

                <!-- Copy Link Button -->
                <button
                    @click="copyLink"
                    class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border border-sage-border p-4 text-center font-bold shadow-xs transition-all hover:-translate-y-0.5"
                    :class="
                        copied
                            ? 'border-forest bg-forest text-lemon'
                            : 'bg-white text-ink hover:bg-sage-bg'
                    "
                >
                    <span
                        class="pi mb-2 text-2xl transition-transform group-hover:scale-110"
                        :class="copied ? 'pi-check' : 'pi-link'"
                    ></span>
                    <span class="text-xs sm:text-sm">{{
                        copied ? 'Copied Link!' : 'Copy Link'
                    }}</span>
                </button>
            </div>

            <!-- Platforms disclaimer / Instagram TikTok helper -->
            <div class="mt-5 border-t border-sage-border pt-4 text-center">
                <p
                    class="mx-auto max-w-lg text-xs leading-relaxed text-muted-green"
                >
                    💡 **Instagram & TikTok Sharing**: Download the image card
                    above, copy the link, and post them directly to your stories
                    or feed!
                </p>
            </div>
        </div>

        <!-- Conversion Driver Section (Call to Action) -->
        <div
            class="relative mx-auto mt-6 w-full max-w-4xl overflow-hidden rounded-2xl bg-linear-to-r from-forest to-navy p-6 text-center text-white shadow-sm sm:p-8"
        >
            <div
                class="absolute -right-4 -bottom-4 text-9xl font-black opacity-[0.07] select-none"
            >
                <span class="pi pi-bolt"></span>
            </div>

            <h3
                class="mb-2 font-headline text-xl font-extrabold text-white sm:text-2xl"
            >
                Want to Win Luxury Items Like This?
            </h3>
            <p
                class="mx-auto mb-5 max-w-xl text-xs leading-relaxed text-sage-light/80 sm:text-sm"
            >
                Join our fair, secure community of bidders. Complete tasks to
                earn free points and place bids on high-value products starting
                today!
            </p>

            <div
                class="flex flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link
                    v-if="props.review.bid"
                    :href="trending.url()"
                    class="w-full rounded-xl bg-lemon px-6 py-3 text-sm font-bold text-navy transition-colors hover:bg-amber sm:w-auto"
                    style="text-decoration: none"
                >
                    Bid on Similar Items →
                </Link>
                <Link
                    v-else
                    :href="home.url()"
                    class="w-full rounded-xl bg-lemon px-6 py-3 text-sm font-bold text-navy transition-colors hover:bg-amber sm:w-auto"
                    style="text-decoration: none"
                >
                    Explore Live Bids Now →
                </Link>
                <Link
                    :href="home.url()"
                    class="w-full rounded-xl border border-white/20 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-white/10 sm:w-auto"
                    style="text-decoration: none"
                >
                    Learn How to Play
                </Link>
            </div>
        </div>
    </div>
</template>
