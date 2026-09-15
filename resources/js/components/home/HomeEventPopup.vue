<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { getRemainingTime } from '@/lib/utils';
import type { Bid } from '@/pages/Home.vue';

export type EventPopupItem = {
    title: string;
    bid: Bid;
};

const props = defineProps<{
    items: EventPopupItem[];
}>();

const emit = defineEmits<{
    (e: 'open-bid-modal', bid: Bid): void;
    (e: 'close'): void;
}>();

const isVisible = ref(false);

onMounted(() => {
    isVisible.value = true;
});

function dismiss(): void {
    isVisible.value = false;
}

function bidNow(bid: Bid): void {
    emit('open-bid-modal', bid);
    dismiss();
}
</script>

<template>
    <Transition name="popup" @after-leave="emit('close')">
        <div
            v-if="isVisible"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="event-popup-title"
            @click.self="dismiss"
        >
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-black/75 backdrop-blur-sm"
                @click="dismiss"
            />

            <!-- Modal card -->
            <div
                class="relative z-10 flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-surface-container-lowest shadow-2xl"
            >
                <!-- Close icon -->
                <button
                    type="button"
                    class="absolute top-4 right-4 z-20 flex h-8 w-8 items-center justify-center rounded-full bg-black/20 text-white/80 transition-colors hover:bg-black/40 hover:text-white"
                    aria-label="Close"
                    @click="dismiss"
                >
                    <span class="material-symbols-outlined text-xl leading-none"
                        >close</span
                    >
                </button>

                <!-- Top accent bar -->
                <div
                    class="h-1.5 shrink-0 bg-linear-to-r from-forest via-lemon to-forest"
                />

                <div class="flex-1 overflow-y-auto px-6 pt-10 pb-8 md:px-10">
                    <!-- Two items side by side -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div
                            v-for="item in props.items"
                            :key="item.bid.id"
                            class="flex flex-col rounded-2xl border border-surface-container bg-surface-container-low p-5"
                        >
                            <h3
                                class="mb-4 text-center font-headline text-xl font-extrabold tracking-tight text-forest max-md:text-lg"
                            >
                                {{ item.title }}
                            </h3>

                            <div
                                class="mx-auto mb-4 w-auto max-w-[220px] rounded-2xl border-4 border-lemon bg-surface-container-high p-4 shadow-lg shadow-lemon/20"
                            >
                                <img
                                    v-if="item.bid.image"
                                    :src="item.bid.image"
                                    :alt="item.bid.name"
                                    class="h-20 w-auto object-contain md:h-44"
                                />
                                <div
                                    v-else
                                    class="flex h-44 w-full items-center justify-center"
                                >
                                    <span
                                        class="material-symbols-outlined text-6xl text-secondary"
                                    >
                                        stars
                                    </span>
                                </div>
                            </div>

                            <p
                                class="mb-2 text-center text-sm leading-relaxed font-medium text-on-surface/70 max-md:text-xs md:mb-4"
                            >
                                Don't miss out on
                                <span class="font-bold text-on-surface">{{
                                    item.bid.name
                                }}</span>
                            </p>

                            <p
                                class="mb-2 text-center text-base font-bold text-forest max-md:text-sm md:mb-4"
                            >
                                Closes in:
                                {{ getRemainingTime(item.bid.ends_at) }}
                            </p>

                            <button
                                class="mt-auto w-full cursor-pointer rounded-xl bg-lemon py-3 text-center font-extrabold text-navy transition-colors hover:bg-amber max-md:text-sm"
                                @click="bidNow(item.bid)"
                            >
                                Bid Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.popup-enter-active {
    transition: opacity 0.3s ease;
}

.popup-leave-active {
    transition: opacity 0.2s ease;
}

.popup-enter-from,
.popup-leave-to {
    opacity: 0;
}

.popup-enter-active .relative.z-10 {
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.popup-enter-from .relative.z-10 {
    transform: scale(0.9) translateY(12px);
}

.popup-enter-to .relative.z-10 {
    transform: scale(1) translateY(0);
}
</style>
