<script setup lang="ts">
interface Props {
    message?: string;
    id?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div v-show="message" :id="id" role="alert" aria-live="assertive" aria-atomic="true">
        <p class="text-sm text-red-600 dark:text-red-500">
            {{ message }}
        </p>
    </div>
</template>
