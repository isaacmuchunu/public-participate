<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-emerald-400 text-white shadow-sm"
        >
            <AppLogoIcon class="h-5 w-5" />
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-semibold text-emerald-900">Huduma Ya Raia</span>
            <span class="text-xs text-emerald-700/80">Public Participation Portal</span>
        </div>
    </div>
</template>
