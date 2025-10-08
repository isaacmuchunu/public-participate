<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    message: string;
    priority?: 'polite' | 'assertive';
    clearDelay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    priority: 'polite',
    clearDelay: 5000,
});

const displayMessage = ref(props.message);

// Clear message after delay to prevent announcement spam
watch(
    () => props.message,
    (newMessage) => {
        displayMessage.value = newMessage;

        if (props.clearDelay > 0) {
            setTimeout(() => {
                displayMessage.value = '';
            }, props.clearDelay);
        }
    },
);
</script>

<template>
    <div class="sr-only" role="status" :aria-live="priority" aria-atomic="true">
        {{ displayMessage }}
    </div>
</template>
