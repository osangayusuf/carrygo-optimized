<script setup lang="ts">
import type { Review } from '@/pages/Home.vue';

const props = defineProps<{
    reviews: Review[];
}>();

function maskedPhone(msisdn: string | undefined): { prefix: string; suffix: string } {
    if (!msisdn) return { prefix: '+000', suffix: 'xxxx' };

    const digits = msisdn.replace(/\D/g, '');

    return {
        prefix: `+${digits.slice(0, 3)} ${digits.slice(3, 6)}`,
        suffix: digits.slice(-4),
    };
}
</script>

<template>
    <section class="bg-surface-container py-20 pb-24">
        <div class="mx-auto max-w-screen-2xl px-8">
            <div class="mb-12 text-center">
                <h2 class="mb-2 font-headline text-3xl font-extrabold tracking-tight">
                    What Our Community Says
                </h2>
                <p class="font-medium text-secondary">
                    Hear from winners who have scored amazing bids on CarryGo
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="review in props.reviews"
                    :key="review.id"
                    class="flex h-full flex-col rounded-2xl bg-surface-container-lowest p-6 shadow-sm transition-shadow hover:shadow-md"
                >
                    <!-- Stars -->
                    <div class="mb-4 flex items-center gap-1 text-[#f59e0b]">
                        <span
                            v-for="i in 5"
                            :key="i"
                            class="material-symbols-outlined text-xl"
                            :style="{ fontVariationSettings: i <= Number(review.rating) ? '\'FILL\' 1, \'wght\' 300, \'GRAD\' 0, \'opsz\' 24' : '\'FILL\' 0, \'wght\' 300, \'GRAD\' 0, \'opsz\' 24' }"
                        >
                            star
                        </span>
                    </div>

                    <!-- Comment -->
                    <p class="mb-6 flex-grow text-sm leading-relaxed text-on-surface">
                        "{{ review.comment }}"
                    </p>

                    <!-- User and Bid Info -->
                    <div class="mt-auto border-t border-surface-container pt-4">
                        <div class="mb-3 flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container text-primary">
                                <span class="material-symbols-outlined text-sm" data-weight="fill">
                                    person
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-bold leading-none">
                                    {{ maskedPhone(review.user?.msisdn).prefix }}
                                    <span class="text-secondary opacity-50">***</span>
                                    {{ maskedPhone(review.user?.msisdn).suffix }}
                                </p>
                                <p class="mt-1 text-xs text-secondary">Verified Winner</p>
                            </div>
                        </div>
                        <div v-if="review.bid" class="rounded-lg bg-surface-container p-2">
                            <p class="text-xs font-semibold text-secondary truncate">
                                Won: {{ review.bid.name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
