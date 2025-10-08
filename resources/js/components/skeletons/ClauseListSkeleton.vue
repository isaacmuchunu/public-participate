<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="space-y-6">
        <div v-for="i in 3" :key="i" class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
            <!-- Clause header skeleton -->
            <div class="mb-4 flex items-start justify-between">
                <div class="flex-1 space-y-2">
                    <Skeleton class="h-6 w-32" />
                    <Skeleton class="h-4 w-48" />
                </div>
                <Skeleton class="h-6 w-24 rounded-full" />
            </div>

            <!-- Clause content skeleton -->
            <div class="space-y-3">
                <Skeleton class="h-4 w-full" />
                <Skeleton class="h-4 w-11/12" />
                <Skeleton class="h-4 w-10/12" />
                <Skeleton class="h-4 w-9/12" />
            </div>

            <!-- Clause footer skeleton -->
            <div class="mt-6 flex items-center gap-3">
                <Skeleton class="h-10 w-48" />
                <Skeleton class="h-10 w-28" />
                <div class="ml-auto">
                    <Skeleton class="h-4 w-24" />
                </div>
            </div>
        </div>
    </div>
</template>
