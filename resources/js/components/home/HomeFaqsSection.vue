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
    <div class="max-w-[1300px] mx-auto mb-5 px-4" v-if="props.faqs.length > 0">
        <div class="flex items-center justify-between mb-3.5">
            <div class="font-condensed text-2xl font-extrabold text-ink flex items-center">
                <span class="inline-block w-1 h-[22px] bg-forest rounded-sm mr-2 align-middle"></span>
                <span class="material-symbols-outlined text-[22px] text-forest mr-1.5 leading-none">help</span>
                Frequently Asked Questions
            </div>
        </div>
        <p class="text-center text-[13px] text-muted-green mb-4.5">
            Everything you need to know about playing CarryGo
        </p>
        <div class="space-y-2.5">
            <div
                v-for="(faq, index) in props.faqs"
                :key="index"
                class="bg-white rounded-lg border border-sage-border-dark shadow-sm overflow-hidden"
            >
                <button
                    type="button"
                    class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-sage-bg/50 transition-colors"
                    :aria-expanded="openIndex === index"
                    @click="toggle(index)"
                >
                    <span class="text-sm font-extrabold text-ink">{{ faq.question }}</span>
                    <span
                        class="material-symbols-outlined text-forest shrink-0 transition-transform duration-200"
                        :class="{ 'rotate-180': openIndex === index }"
                    >
                        expand_more
                    </span>
                </button>
                <div
                    v-show="openIndex === index"
                    class="px-4 pb-4 text-sm text-gray-700 leading-relaxed border-t border-sage-border-dark/60"
                >
                    {{ faq.answer }}
                </div>
            </div>
        </div>
    </div>
</template>
