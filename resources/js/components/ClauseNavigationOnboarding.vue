<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronRight, X } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface OnboardingStep {
    id: string;
    title: string;
    content: string;
    target: string;
    position: 'top' | 'bottom' | 'left' | 'right';
}

interface Props {
    currentStep?: number;
    onComplete?: () => void;
    onSkip?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
    currentStep: 0,
});

const emit = defineEmits<{
    complete: [];
    skip: [];
}>();

const steps: OnboardingStep[] = [
    {
        id: 'welcome',
        title: 'Welcome to Clause-by-Clause Reading',
        content: 'This tool allows you to read bills section by section and provide targeted feedback on specific clauses.',
        target: '.clause-reader-welcome',
        position: 'bottom',
    },
    {
        id: 'navigation',
        title: 'Navigate Between Clauses',
        content: 'Use the sidebar to jump between different clauses. Click on any clause to scroll directly to it.',
        target: '.clause-navigation',
        position: 'right',
    },
    {
        id: 'commenting',
        title: 'Comment on Specific Clauses',
        content: 'Click the "Comment on this clause" button to share your thoughts on individual sections.',
        target: '.clause-comment-button',
        position: 'top',
    },
    {
        id: 'highlighting',
        title: 'Highlight Important Text',
        content: 'Select text within a clause to highlight it for reference. This helps you track key points.',
        target: '.clause-content',
        position: 'left',
    },
    {
        id: 'bookmarking',
        title: 'Bookmark Clauses',
        content: 'Use the bookmark button to save clauses for later review or reference.',
        target: '.clause-bookmark',
        position: 'top',
    },
];

const currentStepIndex = ref(props.currentStep);
const showTooltip = ref(false);
const tooltipPosition = ref({ top: 0, left: 0 });

const nextStep = () => {
    if (currentStepIndex.value < steps.length - 1) {
        currentStepIndex.value++;
        showTooltipForCurrentStep();
    } else {
        completeOnboarding();
    }
};

const prevStep = () => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--;
        showTooltipForCurrentStep();
    }
};

const showTooltipForCurrentStep = () => {
    const step = steps[currentStepIndex.value];
    const targetElement = document.querySelector(step.target);

    if (targetElement) {
        const rect = targetElement.getBoundingClientRect();
        const tooltipWidth = 300;
        const tooltipHeight = 150;

        let top = 0;
        let left = 0;

        switch (step.position) {
            case 'top':
                top = rect.top - tooltipHeight - 10;
                left = rect.left + rect.width / 2 - tooltipWidth / 2;
                break;
            case 'bottom':
                top = rect.bottom + 10;
                left = rect.left + rect.width / 2 - tooltipWidth / 2;
                break;
            case 'left':
                top = rect.top + rect.height / 2 - tooltipHeight / 2;
                left = rect.left - tooltipWidth - 10;
                break;
            case 'right':
                top = rect.top + rect.height / 2 - tooltipHeight / 2;
                left = rect.right + 10;
                break;
        }

        // Ensure tooltip stays within viewport
        top = Math.max(10, Math.min(top, window.innerHeight - tooltipHeight - 10));
        left = Math.max(10, Math.min(left, window.innerWidth - tooltipWidth - 10));

        tooltipPosition.value = { top, left };
        showTooltip.value = true;
    }
};

const completeOnboarding = () => {
    showTooltip.value = false;
    emit('complete');
};

const skipOnboarding = () => {
    showTooltip.value = false;
    emit('skip');
};

onMounted(() => {
    // Wait a bit for DOM to be ready
    setTimeout(() => {
        showTooltipForCurrentStep();
    }, 500);
});

onUnmounted(() => {
    showTooltip.value = false;
});
</script>

<template>
    <!-- Tooltip Overlay -->
    <div v-if="showTooltip" class="pointer-events-none fixed inset-0 z-50 bg-black/20" @click="skipOnboarding">
        <div
            class="pointer-events-auto absolute max-w-sm rounded-lg border bg-white p-4 shadow-lg dark:bg-gray-800"
            :style="{ top: tooltipPosition.top + 'px', left: tooltipPosition.left + 'px' }"
        >
            <div class="mb-2 flex items-start justify-between">
                <h3 class="text-sm font-semibold">{{ steps[currentStepIndex]?.title }}</h3>
                <Button @click="skipOnboarding" variant="ghost" size="sm" class="h-6 w-6 p-0">
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <p class="text-muted-foreground mb-4 text-sm">
                {{ steps[currentStepIndex]?.content }}
            </p>

            <div class="flex items-center justify-between">
                <span class="text-muted-foreground text-xs"> {{ currentStepIndex + 1 }} of {{ steps.length }} </span>

                <div class="flex gap-2">
                    <Button v-if="currentStepIndex > 0" @click="prevStep" variant="outline" size="sm"> Previous </Button>
                    <Button @click="nextStep" size="sm">
                        {{ currentStepIndex === steps.length - 1 ? 'Finish' : 'Next' }}
                        <ChevronRight v-if="currentStepIndex < steps.length - 1" class="ml-1 h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
