<script setup lang="ts">
import { ref } from 'vue';

export type Faq = {
    question: string;
    answer: string;
};

const props = defineProps<{
    faqs: Faq[];
}>();

const openIndex = ref<number | null>(null);

function toggle(index: number): void {
    openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
    <div class="mx-auto mb-5 max-w-[1300px] px-4" v-if="props.faqs.length > 0">
        <div class="mb-3.5 flex items-center justify-between">
            <div
                class="flex items-center font-condensed text-2xl font-extrabold text-ink"
            >
                <span
                    class="mr-2 inline-block h-[22px] w-1 rounded-sm bg-forest align-middle"
                ></span>
                <span
                    class="material-symbols-outlined mr-1.5 text-[22px] leading-none text-forest"
                    >help</span
                >
                Frequently Asked Questions
            </div>
        </div>
        <p class="mb-4.5 text-center text-[13px] text-muted-green">
            Everything you need to know about playing CarryGo
        </p>
        <div class="space-y-2.5">
            <div
                v-for="(faq, index) in props.faqs"
                :key="index"
                class="overflow-hidden rounded-lg border border-sage-border-dark bg-white shadow-sm"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left transition-colors hover:bg-sage-bg/50"
                    :aria-expanded="openIndex === index"
                    @click="toggle(index)"
                >
                    <span class="text-sm font-extrabold text-ink">{{
                        faq.question
                    }}</span>
                    <span
                        class="material-symbols-outlined shrink-0 text-forest transition-transform duration-200"
                        :class="{ 'rotate-180': openIndex === index }"
                    >
                        expand_more
                    </span>
                </button>
                <div
                    v-show="openIndex === index"
                    class="border-t border-sage-border-dark/60 px-4 pb-4 text-sm leading-relaxed text-gray-700"
                >
                    {{ faq.answer }}
                </div>
            </div>
        </div>
    </div>
</template>
