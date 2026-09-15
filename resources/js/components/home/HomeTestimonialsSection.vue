<script setup lang="ts">
import { ref, computed } from 'vue';
import { formatMsisdn } from '@/lib/utils';
import type { Review } from '@/pages/Home.vue';

const props = defineProps<{
    reviews: Review[];
}>();

const selectedReview = ref<Review | null>(null);
const showShareModal = ref(false);
const copied = ref(false);

function openShareModal(review: Review) {
    selectedReview.value = review;
    showShareModal.value = true;
    copied.value = false;
}

function closeShareModal() {
    showShareModal.value = false;
    selectedReview.value = null;
}

const currentShareUrl = computed(() => {
    if (!selectedReview.value) {
        return '';
    }

    return `${window.location.origin}/reviews/${selectedReview.value.id}`;
});

const shareText = computed(() => {
    if (!selectedReview.value) {
        return '';
    }

    if (selectedReview.value.bid) {
        return `Check out this review of ${selectedReview.value.bid.name} won on CarryGo! 🏆🎉`;
    }

    return `Check out this 5-star review of CarryGo! ⭐⭐⭐⭐⭐`;
});

const whatsappUrl = computed(() => {
    return `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value + ' ' + currentShareUrl.value)}`;
});

const facebookUrl = computed(() => {
    return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentShareUrl.value)}`;
});

const xUrl = computed(() => {
    const params = new URLSearchParams({
        text: shareText.value,
        url: currentShareUrl.value,
    });

    return `https://x.com/intent/tweet?${params.toString()}`;
});

function copyLink() {
    navigator.clipboard.writeText(currentShareUrl.value).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
}
</script>

<template>
    <div class="mx-auto mb-5 max-w-[1300px] px-4">
        <!-- Section Header -->
        <div class="mb-3.5 flex items-center justify-between">
            <div
                class="flex items-center font-condensed text-2xl font-extrabold text-ink"
            >
                <span
                    class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                ></span>
                <span class="pi pi-comments mr-1 text-lg text-forest"></span>
                What Our Community Says
            </div>
        </div>
        <div class="mb-4.5 text-center text-[13px] text-muted-green">
            Hear from winners who have scored amazing bids on CarryGo
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="review in props.reviews"
                :key="review.id"
                class="group relative flex h-full flex-col rounded-lg border border-sage-border-dark bg-white p-4.5 shadow-sm"
            >
                <!-- Stars & Share Row -->
                <div class="mb-2.5 flex items-center justify-between">
                    <div class="flex gap-0.5 text-xs text-amber">
                        <span
                            v-for="i in review.rating"
                            :key="i"
                            class="pi pi-star-fill"
                        ></span>
                        <span
                            v-for="i in 5 - review.rating"
                            :key="i"
                            class="pi pi-star opacity-25"
                        ></span>
                    </div>
                    <button
                        @click="openShareModal(review)"
                        class="flex cursor-pointer items-center justify-center rounded-full p-1 text-secondary/50 transition-colors hover:bg-sage-bg hover:text-primary"
                        title="Share Review"
                    >
                        <span class="material-symbols-outlined text-[16px]"
                            >share</span
                        >
                    </button>
                </div>

                <!-- Comment -->
                <div
                    class="mb-3.5 grow text-sm leading-relaxed text-gray-800 italic"
                >
                    "{{ review.comment }}"
                </div>

                <!-- User and Bid Info -->
                <div class="mt-auto">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-lemon text-base"
                        >
                            <span class="pi pi-user"></span>
                        </div>
                        <div>
                            <div class="text-sm font-extrabold text-ink">
                                {{
                                    formatMsisdn(review.user_id) || 'Anonymous'
                                }}
                            </div>
                            <div class="text-xs text-muted-green">
                                Verified User
                            </div>
                        </div>
                    </div>
                    <span
                        v-if="review.bid"
                        class="mt-2.5 inline-block rounded-md bg-[#e8f5e0] px-2.5 py-1 text-xs font-bold text-forest"
                    >
                        Item: {{ review.bid.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Share Micro-Modal -->
        <Teleport to="body">
            <div
                v-if="showShareModal && selectedReview"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs"
            >
                <div class="absolute inset-0" @click="closeShareModal"></div>

                <div
                    class="relative flex w-full max-w-sm flex-col overflow-hidden rounded-3xl border border-surface-container bg-surface-container-lowest p-6 text-left font-sans shadow-2xl"
                >
                    <button
                        type="button"
                        class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-on-surface-variant transition-colors hover:bg-surface-container-high"
                        @click="closeShareModal"
                    >
                        <span class="material-symbols-outlined text-[18px]"
                            >close</span
                        >
                    </button>

                    <h3
                        class="mb-2 font-headline text-xl font-extrabold text-on-surface"
                    >
                        Share Review
                    </h3>
                    <p class="mb-4 truncate text-xs text-outline italic">
                        "{{ selectedReview.comment }}"
                    </p>

                    <!-- Sharing Actions -->
                    <div class="space-y-3">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            class="flex w-full items-center justify-center gap-3 rounded-2xl bg-[#25D366] px-4 py-3.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-[#20ba5a]"
                            style="text-decoration: none"
                        >
                            <span class="pi pi-whatsapp text-lg"></span> Share
                            to WhatsApp
                        </a>

                        <a
                            :href="facebookUrl"
                            target="_blank"
                            class="flex w-full items-center justify-center gap-3 rounded-2xl bg-[#1877F2] px-4 py-3.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-[#166fe5]"
                            style="text-decoration: none"
                        >
                            <span class="pi pi-facebook text-lg"></span> Share
                            to Facebook
                        </a>

                        <a
                            :href="xUrl"
                            target="_blank"
                            class="flex w-full items-center justify-center gap-3 rounded-2xl bg-black px-4 py-3.5 text-sm font-bold text-white shadow-xs transition-all hover:bg-gray-900"
                            style="text-decoration: none"
                        >
                            <span class="text-lg leading-none font-black"
                                >𝕏</span
                            >
                            Share to X
                        </a>

                        <!-- Direct View Link -->
                        <a
                            :href="`/reviews/${selectedReview.id}`"
                            class="flex w-full items-center justify-center gap-3 rounded-2xl bg-navy px-4 py-3.5 text-sm font-bold text-lemon shadow-xs transition-all hover:bg-forest"
                            style="text-decoration: none"
                        >
                            <span class="pi pi-external-link text-lg"></span>
                            View Dedicated Page
                        </a>

                        <!-- Copy Link Button -->
                        <button
                            @click="copyLink"
                            class="flex w-full cursor-pointer items-center justify-center gap-3 rounded-2xl border px-4 py-3.5 text-sm font-bold shadow-xs transition-all"
                            :class="
                                copied
                                    ? 'border-forest bg-forest text-lemon'
                                    : 'border-surface-container bg-surface-container-low text-on-surface hover:bg-surface-container-high'
                            "
                        >
                            <span
                                class="pi text-lg"
                                :class="copied ? 'pi-check' : 'pi-link'"
                            ></span>
                            {{
                                copied
                                    ? 'Copied Review Link!'
                                    : 'Copy Review Link'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
