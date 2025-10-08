<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="space-y-4">
        <div class="mb-6 flex items-center justify-between">
            <Skeleton class="h-7 w-48" />
            <Skeleton class="h-10 w-32" />
        </div>

        <div v-for="i in 5" :key="i" class="rounded-lg border border-emerald-100 bg-white p-4 shadow-sm">
            <!-- Submission header -->
            <div class="mb-3 flex items-start gap-3">
                <Skeleton class="h-10 w-10 rounded-full" />
                <div class="flex-1 space-y-2">
                    <Skeleton class="h-4 w-32" />
                    <Skeleton class="h-3 w-24" />
                </div>
                <Skeleton class="h-6 w-20 rounded-full" />
            </div>

            <!-- Submission content -->
            <div class="ml-13 space-y-2">
                <Skeleton class="h-4 w-full" />
                <Skeleton class="h-4 w-11/12" />
                <Skeleton class="h-4 w-9/12" />
            </div>

            <!-- Submission actions -->
            <div class="ml-13 mt-3 flex items-center gap-4">
                <Skeleton class="h-8 w-16" />
                <Skeleton class="h-8 w-16" />
                <Skeleton class="h-8 w-20" />
            </div>
        </div>
    </div>
</template>
