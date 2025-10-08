<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { dashboard, home, login, register } from '@/routes';
import * as bills from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const mobileOpen = ref(false);

const navigationLinks = computed(() => [
    {
        label: 'Home',
        href: home(),
    },
    {
        label: 'Open bills',
        href: bills.participate(),
    },
]);

const currentYear = new Date().getFullYear();

function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
}

function closeMobile() {
    mobileOpen.value = false;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gradient-to-b from-emerald-50 via-white to-white">
        <header class="sticky top-0 z-30 border-b border-emerald-100/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 md:px-6">
                <Link :href="home()" class="flex items-center gap-3" @click="closeMobile">
                    <AppLogo />
                </Link>

                <nav class="hidden items-center gap-6 text-sm font-medium text-emerald-900 md:flex">
                    <Link v-for="item in navigationLinks" :key="item.label" :href="item.href" class="transition hover:text-emerald-600">
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <template v-if="user">
                        <Link
                            :href="dashboard()"
                            class="inline-flex items-center rounded-full border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-600 hover:text-white"
                        >
                            My dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex items-center rounded-full border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700 transition hover:border-emerald-400 hover:text-emerald-800"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            Create account
                        </Link>
                    </template>
                </div>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 text-emerald-700 md:hidden"
                    @click="toggleMobile"
                >
                    <Menu class="h-5 w-5" />
                    <span class="sr-only">Toggle navigation</span>
                </button>
            </div>

            <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="mobileOpen" class="border-t border-emerald-100/80 bg-white md:hidden">
                    <div class="space-y-4 px-4 py-4">
                        <nav class="flex flex-col gap-3 text-sm font-medium text-emerald-900">
                            <Link
                                v-for="item in navigationLinks"
                                :key="item.label"
                                :href="item.href"
                                class="rounded-md px-3 py-2 hover:bg-emerald-50"
                                @click="closeMobile"
                            >
                                {{ item.label }}
                            </Link>
                        </nav>
                        <div class="flex flex-col gap-2">
                            <template v-if="user">
                                <Link
                                    :href="dashboard()"
                                    class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                                    @click="closeMobile"
                                >
                                    My dashboard
                                </Link>
                            </template>
                            <template v-else>
                                <Link
                                    :href="login()"
                                    class="inline-flex items-center justify-center rounded-md border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700 hover:border-emerald-400 hover:text-emerald-800"
                                    @click="closeMobile"
                                >
                                    Log in
                                </Link>
                                <Link
                                    :href="register()"
                                    class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                                    @click="closeMobile"
                                >
                                    Create account
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </transition>

            <div v-if="props.breadcrumbs.length" class="border-t border-emerald-100/80 bg-emerald-50/60">
                <div class="mx-auto flex w-full max-w-7xl items-center px-4 py-2 text-sm text-emerald-700 md:px-6">
                    <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-emerald-100 bg-emerald-50/80 py-8">
            <div
                class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 text-sm text-emerald-800 md:flex-row md:items-center md:justify-between md:px-6"
            >
                <div>
                    <p class="font-semibold">Huduma Ya Raia</p>
                    <p class="text-emerald-700/80">Digitising Kenya’s public participation experience.</p>
                </div>
                <div class="flex flex-wrap gap-4 text-emerald-700/80">
                    <span>&copy; {{ currentYear }} Parliament of Kenya</span>
                    <span>Built for transparency &amp; citizen voice</span>
                </div>
            </div>
        </footer>
    </div>
</template>
