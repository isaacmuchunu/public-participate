<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <Skeleton class="h-7 w-40" />
            <Skeleton class="h-9 w-28 rounded-full" />
        </div>

        <!-- Sentiment overview -->
        <div class="grid gap-4 md:grid-cols-3">
            <div v-for="i in 3" :key="i" class="rounded-xl border border-emerald-100 bg-white p-6 text-center shadow-sm">
                <Skeleton class="mx-auto mb-3 h-12 w-12 rounded-full" />
                <Skeleton class="mx-auto mb-2 h-8 w-16" />
                <Skeleton class="mx-auto h-4 w-24" />
            </div>
        </div>

        <!-- Sentiment chart -->
        <div class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
            <Skeleton class="mb-6 h-6 w-48" />
            <div class="space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-4 w-20" />
                        <Skeleton class="h-4 w-12" />
                    </div>
                    <Skeleton class="h-4 w-full rounded-full" />
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-4 w-20" />
                        <Skeleton class="h-4 w-12" />
                    </div>
                    <Skeleton class="h-4 w-3/4 rounded-full" />
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-4 w-20" />
                        <Skeleton class="h-4 w-12" />
                    </div>
                    <Skeleton class="h-4 w-1/2 rounded-full" />
                </div>
            </div>
        </div>

        <!-- Trending keywords -->
        <div class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
            <Skeleton class="mb-4 h-6 w-36" />
            <div class="flex flex-wrap gap-2">
                <Skeleton class="h-8 w-24 rounded-full" />
                <Skeleton class="h-8 w-32 rounded-full" />
                <Skeleton class="h-8 w-28 rounded-full" />
                <Skeleton class="h-8 w-20 rounded-full" />
                <Skeleton class="h-8 w-36 rounded-full" />
                <Skeleton class="h-8 w-24 rounded-full" />
                <Skeleton class="h-8 w-28 rounded-full" />
                <Skeleton class="h-8 w-32 rounded-full" />
            </div>
        </div>
    </div>
</template>
