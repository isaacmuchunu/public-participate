<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <Skeleton class="h-8 w-48" />
            <Skeleton class="h-10 w-32 rounded-full" />
        </div>

        <!-- Stats cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div v-for="i in 4" :key="i" class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
                <Skeleton class="mb-2 h-4 w-24" />
                <Skeleton class="h-8 w-16" />
                <Skeleton class="mt-2 h-3 w-32" />
            </div>
        </div>

        <!-- Charts -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Chart 1 -->
            <div class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
                <Skeleton class="mb-4 h-6 w-40" />
                <div class="space-y-3">
                    <div class="flex items-end gap-2">
                        <Skeleton class="h-32 w-full" />
                        <Skeleton class="h-24 w-full" />
                        <Skeleton class="h-40 w-full" />
                        <Skeleton class="h-28 w-full" />
                        <Skeleton class="h-36 w-full" />
                    </div>
                    <div class="flex justify-around">
                        <Skeleton class="h-3 w-12" />
                        <Skeleton class="h-3 w-12" />
                        <Skeleton class="h-3 w-12" />
                        <Skeleton class="h-3 w-12" />
                        <Skeleton class="h-3 w-12" />
                    </div>
                </div>
            </div>

            <!-- Chart 2 -->
            <div class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
                <Skeleton class="mb-4 h-6 w-40" />
                <div class="flex items-center justify-center">
                    <Skeleton class="h-48 w-48 rounded-full" />
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-4 w-24" />
                        <Skeleton class="h-4 w-12" />
                    </div>
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-4 w-28" />
                        <Skeleton class="h-4 w-12" />
                    </div>
                    <div class="flex items-center justify-between">
                        <Skeleton class="h-4 w-20" />
                        <Skeleton class="h-4 w-12" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Data table -->
        <div class="rounded-xl border border-emerald-100 bg-white p-6 shadow-sm">
            <Skeleton class="mb-4 h-6 w-56" />
            <div class="space-y-3">
                <div class="flex gap-4">
                    <Skeleton class="h-10 w-full" />
                    <Skeleton class="h-10 w-32" />
                    <Skeleton class="h-10 w-24" />
                    <Skeleton class="h-10 w-28" />
                </div>
                <div v-for="i in 5" :key="i" class="flex gap-4">
                    <Skeleton class="h-8 w-full" />
                    <Skeleton class="h-8 w-32" />
                    <Skeleton class="h-8 w-24" />
                    <Skeleton class="h-8 w-28" />
                </div>
            </div>
        </div>
    </div>
</template>
