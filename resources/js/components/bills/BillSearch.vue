<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Search bills by title, number, or keyword...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    search: [query: string];
}>();

const localValue = ref(props.modelValue);
const isSearching = ref(false);

// Debounced search with 300ms delay
const debouncedSearch = useDebounceFn((value: string) => {
    isSearching.value = false;
    emit('search', value);
}, 300);

watch(localValue, (newValue) => {
    isSearching.value = true;
    emit('update:modelValue', newValue);
    debouncedSearch(newValue);
});

const clearSearch = () => {
    localValue.value = '';
    emit('update:modelValue', '');
    emit('search', '');
};
</script>

<template>
    <div class="relative">
        <div class="relative">
            <Icon name="search" class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" aria-hidden="true" />
            <Input v-model="localValue" type="search" :placeholder="placeholder" class="pl-10 pr-10" aria-label="Search bills" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                <Icon v-if="isSearching" name="loader-2" class="text-muted-foreground h-4 w-4 animate-spin" aria-label="Searching" />
                <Button v-else-if="localValue" variant="ghost" size="sm" class="h-6 w-6 p-0" @click="clearSearch" aria-label="Clear search">
                    <Icon name="x" class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
