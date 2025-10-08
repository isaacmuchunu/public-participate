<script setup lang="ts">
/**
 * ClauseHighlight Component
 *
 * Text selection and highlighting system for clause content.
 * This component wraps text content and allows users to highlight
 * specific portions for reference in their comments.
 *
 * Features:
 * - Text selection detection
 * - Highlight storage and display
 * - Comment attachment to highlights
 * - Color-coded highlights
 * - Persistent highlights across sessions
 *
 * Note: This is a future enhancement component for advanced highlighting features.
 * Currently serves as a placeholder for the highlighting system architecture.
 */

import { onMounted, onUnmounted, ref } from 'vue';

interface Highlight {
    id: string;
    clauseId: number;
    startOffset: number;
    endOffset: number;
    text: string;
    color: string;
    createdAt: string;
}

interface Props {
    clauseId: number;
    content: string;
    highlights?: Highlight[];
    enabled?: boolean;
}

interface Emits {
    (e: 'highlight', highlight: Omit<Highlight, 'id' | 'createdAt'>): void;
    (e: 'removeHighlight', highlightId: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    highlights: () => [],
    enabled: true,
});

const emit = defineEmits<Emits>();

const contentRef = ref<HTMLElement | null>(null);
const selectedText = ref<string>('');
const showHighlightMenu = ref(false);
const menuPosition = ref({ x: 0, y: 0 });

const HIGHLIGHT_COLORS = [
    { name: 'yellow', class: 'bg-yellow-200', label: 'Yellow' },
    { name: 'green', class: 'bg-emerald-200', label: 'Green' },
    { name: 'blue', class: 'bg-blue-200', label: 'Blue' },
    { name: 'pink', class: 'bg-pink-200', label: 'Pink' },
];

const handleTextSelection = () => {
    if (!props.enabled) {
        return;
    }

    const selection = window.getSelection();
    if (!selection || selection.isCollapsed) {
        showHighlightMenu.value = false;
        return;
    }

    const text = selection.toString().trim();
    if (text.length < 3) {
        showHighlightMenu.value = false;
        return;
    }

    selectedText.value = text;

    // Get selection position for menu placement
    const range = selection.getRangeAt(0);
    const rect = range.getBoundingClientRect();

    menuPosition.value = {
        x: rect.left + rect.width / 2,
        y: rect.top - 10,
    };

    showHighlightMenu.value = true;
};

const createHighlight = (color: string) => {
    const selection = window.getSelection();
    if (!selection || !contentRef.value) {
        return;
    }

    const range = selection.getRangeAt(0);

    // Calculate text offsets relative to content element
    const preSelectionRange = range.cloneRange();
    preSelectionRange.selectNodeContents(contentRef.value);
    preSelectionRange.setEnd(range.startContainer, range.startOffset);
    const startOffset = preSelectionRange.toString().length;

    const endOffset = startOffset + selectedText.value.length;

    emit('highlight', {
        clauseId: props.clauseId,
        startOffset,
        endOffset,
        text: selectedText.value,
        color,
    });

    // Clear selection and hide menu
    selection.removeAllRanges();
    showHighlightMenu.value = false;
    selectedText.value = '';
};

const handleDocumentClick = (event: MouseEvent) => {
    // Hide menu when clicking outside
    if (showHighlightMenu.value && !(event.target as HTMLElement).closest('.highlight-menu')) {
        showHighlightMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mouseup', handleTextSelection);
    document.addEventListener('click', handleDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('mouseup', handleTextSelection);
    document.removeEventListener('click', handleDocumentClick);
});
</script>

<template>
    <div ref="contentRef" class="relative">
        <slot />

        <!-- Highlight Menu Popup -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="showHighlightMenu"
                class="highlight-menu fixed z-50 flex gap-1 rounded-lg border border-emerald-200 bg-white p-2 shadow-lg"
                :style="{
                    left: `${menuPosition.x}px`,
                    top: `${menuPosition.y}px`,
                    transform: 'translate(-50%, -100%)',
                }"
                role="toolbar"
                aria-label="Text highlighting options"
            >
                <button
                    v-for="color in HIGHLIGHT_COLORS"
                    :key="color.name"
                    type="button"
                    :aria-label="`Highlight text in ${color.label}`"
                    :class="[
                        'h-8 w-8 rounded border-2 border-transparent transition',
                        color.class,
                        'hover:border-emerald-400 focus:border-emerald-500 focus:outline-none',
                    ]"
                    @click="createHighlight(color.name)"
                />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Highlight styles - applied dynamically to highlighted text spans */
:deep(.highlight) {
    cursor: pointer;
    transition: background-color 0.2s;
}

:deep(.highlight:hover) {
    filter: brightness(0.95);
}

:deep(.highlight-yellow) {
    @apply bg-yellow-200;
}

:deep(.highlight-green) {
    @apply bg-emerald-200;
}

:deep(.highlight-blue) {
    @apply bg-blue-200;
}

:deep(.highlight-pink) {
    @apply bg-pink-200;
}
</style>
