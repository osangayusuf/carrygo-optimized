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
    if (!selectedReview.value) return '';
    return `${window.location.origin}/reviews/${selectedReview.value.id}`;
});

const shareText = computed(() => {
    if (!selectedReview.value) return '';
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
    <div class="max-w-[1300px] mx-auto mb-5 px-4">
        <!-- Section Header -->
        <div class="flex items-center justify-between mb-3.5">
            <div class="font-condensed text-2xl font-extrabold text-ink flex items-center">
                <span class="inline-block w-1 h-[22px] bg-forest rounded-sm mr-2 align-middle"></span>
                <span class="pi pi-comments text-lg text-forest mr-1"></span> What Our Community Says
            </div>
        </div>
        <div class="text-center text-[13px] text-muted-green mb-4.5">
            Hear from winners who have scored amazing bids on CarryGo
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <div v-for="review in props.reviews" :key="review.id"
                class="bg-white rounded-lg p-4.5 shadow-sm border border-sage-border-dark flex flex-col h-full relative group">
                
                <!-- Stars & Share Row -->
                <div class="flex justify-between items-center mb-2.5">
                    <div class="text-amber text-xs flex gap-0.5">
                        <span v-for="i in review.rating" :key="i" class="pi pi-star-fill"></span>
                        <span v-for="i in 5 - review.rating" :key="i" class="pi pi-star opacity-25"></span>
                    </div>
                    <button @click="openShareModal(review)" class="text-secondary/50 hover:text-primary transition-colors flex items-center justify-center p-1 rounded-full hover:bg-sage-bg cursor-pointer" title="Share Review">
                        <span class="material-symbols-outlined text-[16px]">share</span>
                    </button>
                </div>

                <!-- Comment -->
                <div class="text-sm text-gray-800 leading-relaxed mb-3.5 italic grow">
                    "{{ review.comment }}"
                </div>

                <!-- User and Bid Info -->
                <div class="mt-auto">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-lemon flex items-center justify-center text-base">
                            <span class="pi pi-user"></span>
                        </div>
                        <div>
                            <div class="text-sm font-extrabold text-ink">
                                {{ formatMsisdn(review.user_id) || 'Anonymous' }}
                            </div>
                            <div class="text-xs text-muted-green">Verified User</div>
                        </div>
                    </div>
                    <span v-if="review.bid"
                        class="mt-2.5 bg-[#e8f5e0] text-forest text-xs font-bold px-2.5 py-1 rounded-md inline-block">
                        Item: {{ review.bid.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Share Micro-Modal -->
        <Teleport to="body">
            <div v-if="showShareModal && selectedReview" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
                <div class="absolute inset-0" @click="closeShareModal"></div>

                <div class="relative flex w-full max-w-sm flex-col overflow-hidden rounded-3xl bg-surface-container-lowest shadow-2xl p-6 border border-surface-container font-sans text-left">
                    <button type="button" class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-surface-container text-on-surface-variant transition-colors hover:bg-surface-container-high" @click="closeShareModal">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>

                    <h3 class="font-headline text-xl font-extrabold text-on-surface mb-2">Share Review</h3>
                    <p class="text-xs text-outline mb-4 italic truncate">"{{ selectedReview.comment }}"</p>

                    <!-- Sharing Actions -->
                    <div class="space-y-3">
                        <a :href="whatsappUrl" target="_blank" class="flex items-center gap-3 w-full rounded-2xl bg-[#25D366] hover:bg-[#20ba5a] px-4 py-3.5 text-sm font-bold text-white transition-all shadow-xs justify-center" style="text-decoration:none;">
                            <span class="pi pi-whatsapp text-lg"></span> Share to WhatsApp
                        </a>

                        <a :href="facebookUrl" target="_blank" class="flex items-center gap-3 w-full rounded-2xl bg-[#1877F2] hover:bg-[#166fe5] px-4 py-3.5 text-sm font-bold text-white transition-all shadow-xs justify-center" style="text-decoration:none;">
                            <span class="pi pi-facebook text-lg"></span> Share to Facebook
                        </a>

                        <a :href="xUrl" target="_blank" class="flex items-center gap-3 w-full rounded-2xl bg-black hover:bg-gray-900 px-4 py-3.5 text-sm font-bold text-white transition-all shadow-xs justify-center" style="text-decoration:none;">
                            <span class="text-lg font-black leading-none">𝕏</span> Share to X
                        </a>

                        <!-- Direct View Link -->
                        <a :href="`/reviews/${selectedReview.id}`" class="flex items-center gap-3 w-full rounded-2xl bg-navy hover:bg-forest px-4 py-3.5 text-sm font-bold text-lemon transition-all shadow-xs justify-center" style="text-decoration:none;">
                            <span class="pi pi-external-link text-lg"></span> View Dedicated Page
                        </a>

                        <!-- Copy Link Button -->
                        <button @click="copyLink" class="flex items-center gap-3 w-full rounded-2xl border px-4 py-3.5 text-sm font-bold transition-all shadow-xs justify-center cursor-pointer" :class="copied ? 'bg-forest text-lemon border-forest' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface border-surface-container'">
                            <span class="pi text-lg" :class="copied ? 'pi-check' : 'pi-link'"></span>
                            {{ copied ? 'Copied Review Link!' : 'Copy Review Link' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
