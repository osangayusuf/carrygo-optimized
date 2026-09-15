<script setup lang="ts">
import { ref } from 'vue';
import { formatMsisdn, formatPrice, getDaysAgo } from '@/lib/utils';
import type { WonBid } from '@/pages/HistoryBids.vue';

defineProps<{ bid: WonBid }>();

const emit = defineEmits<{
    'open-review-modal': [{ bid: WonBid; mode: 'view' | 'write' }];
}>();

const expandedImage = ref<string | null>(null);
</script>

<template>
    <div
        class="flex flex-col overflow-hidden rounded-lg border-2 border-sage-border bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-lemon"
    >
        <!-- Image area -->
        <div
            class="relative h-36 cursor-pointer overflow-hidden bg-sage-light"
            @click="expandedImage = bid.image"
        >
            <img
                :src="bid.image"
                :alt="bid.name"
                class="block h-full w-full object-cover transition-transform duration-700 hover:scale-110"
            />
            <div class="absolute bottom-2 left-2 flex gap-2">
                <span
                    class="rounded-lg bg-navy px-2 py-1 text-[9px] font-black tracking-widest text-lemon uppercase shadow-lg sm:px-3 sm:text-[10px]"
                >
                    Won Bid
                </span>
            </div>
        </div>

        <!-- Body -->
        <div class="flex flex-1 flex-col p-3">
            <div class="mb-2 flex items-start justify-between">
                <div class="line-clamp-2 pr-2 text-xs font-extrabold text-ink">
                    {{ bid.name }}
                </div>
                <div class="shrink-0 text-right">
                    <p
                        class="mb-0.5 text-[8px] font-bold tracking-wider text-sage-dark uppercase"
                    >
                        Retail
                    </p>
                    <p class="text-sm font-black whitespace-nowrap text-forest">
                        {{ formatPrice(bid.price) }}
                    </p>
                </div>
            </div>

            <div class="mt-auto">
                <div
                    class="mb-3 rounded-xl border border-sage-border bg-sage-light/30 p-2 sm:p-2.5"
                >
                    <div class="mb-1.5 flex items-center justify-between">
                        <span
                            class="text-[9px] font-bold text-sage-dark sm:text-[10px]"
                            >Winning User:</span
                        >
                        <span
                            class="text-[10px] font-black text-ink sm:text-xs"
                        >
                            {{
                                formatMsisdn(
                                    bid.bid_winner ? bid.bid_winner.msisdn : '',
                                )
                            }}
                        </span>
                    </div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <span
                            class="text-[9px] font-bold text-sage-dark sm:text-[10px]"
                            >Winning Points:</span
                        >
                        <span
                            class="text-[10px] font-black text-forest sm:text-xs"
                        >
                            {{
                                bid.bid_winner
                                    ? bid.bid_winner.total_points
                                    : 'N/A'
                            }}
                        </span>
                    </div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <span
                            class="text-[9px] font-bold text-sage-dark sm:text-[10px]"
                            >Total Bidded Points:</span
                        >
                        <span
                            class="text-[10px] font-black text-ink sm:text-xs"
                        >
                            {{ bid.bid_entry_points ?? 0 }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-[9px] font-bold text-sage-dark sm:text-[10px]"
                            >Date Won:</span
                        >
                        <span
                            class="text-[10px] font-black text-ink sm:text-xs"
                        >
                            {{
                                bid.bid_winner?.created_at
                                    ? getDaysAgo(bid.bid_winner.created_at)
                                    : 'N/A'
                            }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5 sm:flex-row sm:gap-2">
                    <button
                        type="button"
                        class="w-full flex-1 rounded-lg bg-surface-container-high py-1.5 text-[9px] font-bold text-on-surface transition-colors hover:bg-surface-container-highest active:scale-95 sm:py-2.5 sm:text-[10px]"
                        @click="
                            emit('open-review-modal', { bid, mode: 'view' })
                        "
                    >
                        <i class="pi pi-eye max-sm:mr-1"></i>
                        View Reviews
                    </button>
                    <button
                        type="button"
                        class="w-full flex-1 rounded-lg bg-navy py-1.5 text-[9px] font-bold text-lemon transition-colors hover:bg-forest active:scale-95 sm:py-2.5 sm:text-[10px]"
                        @click="
                            emit('open-review-modal', { bid, mode: 'write' })
                        "
                    >
                        <i class="pi pi-pencil max-sm:mr-1"></i>
                        Post Review
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Overlay -->
    <Teleport to="body">
        <div
            v-if="expandedImage"
            class="fixed inset-0 z-[100] flex cursor-pointer items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
            @click="expandedImage = null"
        >
            <img
                :src="expandedImage"
                class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl"
                alt="Expanded image"
            />
        </div>
    </Teleport>
</template>
