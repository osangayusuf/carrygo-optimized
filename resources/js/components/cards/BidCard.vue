<script setup lang="ts">
import { ref } from 'vue';
import { formatPrice, calcProgress, getRemainingTime } from '@/lib/utils';
import type { Bid } from '@/pages/Home.vue';

defineProps<{ bid: Bid }>();

const emit = defineEmits(['open-bid-modal']);

const expandedImage = ref<string | null>(null);

function openBidModal(bid: Bid) {
    emit('open-bid-modal', bid);
}

function buttonLabel(bid: Bid): string {
    if (bid.status === 2) {
        return 'Closed';
    }

    if (calcProgress(bid) >= 100) {
        return 'Final Moment Bid';
    }

    return 'Place Bid';
}
</script>

<template>
    <div
        class="flex flex-col overflow-hidden rounded-lg border-2 border-sage-border bg-white transition-all duration-200 hover:-translate-y-0.5 hover:border-lemon"
    >
        <div
            class="relative h-36 cursor-pointer overflow-hidden bg-sage-light"
            @click="expandedImage = bid.image"
        >
            <img
                :src="bid.image"
                :alt="bid.name"
                class="block h-full w-full object-cover transition-transform duration-700 hover:scale-110"
            />
            <div class="absolute top-2 right-2">
                <span
                    v-if="bid.status === 0"
                    class="rounded-lg bg-navy px-2 py-1 text-[9px] font-black tracking-widest text-lemon uppercase shadow-lg sm:px-3 sm:text-[10px]"
                >
                    Live
                </span>
                <span
                    v-else-if="bid.status === 2"
                    class="rounded-lg bg-error px-2 py-1 text-[9px] font-black tracking-widest text-white uppercase shadow-lg sm:px-3 sm:text-[10px]"
                >
                    Closed
                </span>
            </div>
        </div>
        <div class="flex flex-1 flex-col p-3">
            <div class="mb-1 truncate text-xs font-extrabold text-ink">
                {{ bid.name }}
            </div>
            <div class="mb-1.5 text-lg font-black text-forest">
                {{ formatPrice(bid.price) }}
            </div>
            <div class="mb-1.5 h-1 overflow-hidden rounded-sm bg-sage-mid">
                <div
                    class="h-full rounded-sm bg-linear-to-r from-forest to-lemon"
                    :style="{ width: calcProgress(bid) + '%' }"
                ></div>
            </div>
            <div class="mb-2 flex items-center justify-between">
                <span class="text-[9px] font-bold text-forest sm:text-[10px]">
                    Progress: {{ bid.bid_entry_points ?? 0 }}/{{
                        bid.open_points
                    }}
                    Points
                </span>
                <span
                    class="text-[9px] font-black sm:text-[10px]"
                    :class="
                        calcProgress(bid) >= 100 ? 'text-error' : 'text-primary'
                    "
                >
                    {{ calcProgress(bid) }}%
                </span>
            </div>
            <div
                v-if="
                    (bid.status === 1 || calcProgress(bid) >= 100) &&
                    bid.ends_at
                "
                class="mb-3 text-center text-[9px] font-bold text-outline sm:text-[10px]"
            >
                <span
                    class="material-symbols-outlined mr-1 align-middle text-[12px]"
                    >schedule</span
                >
                <span class="align-middle"
                    >{{ getRemainingTime(bid.ends_at) }} left</span
                >
            </div>
            <button
                type="button"
                :disabled="bid.status === 2"
                class="mt-auto w-full rounded-md bg-navy p-2 text-xs font-extrabold text-lemon transition-colors hover:bg-forest"
                @click="openBidModal(bid)"
            >
                {{ buttonLabel(bid) }}
            </button>
        </div>
    </div>

    <!-- Image Overlay -->
    <Teleport to="body">
        <div
            v-if="expandedImage"
            class="fixed inset-0 z-100 flex cursor-pointer items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
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
