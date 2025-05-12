<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import Button from '@/components/ui/button/Button.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as billRoutes from '@/routes/bills';
import clerkRoutes from '@/routes/clerk';
import * as submissionRoutes from '@/routes/submissions';
import type { BreadcrumbItemType, User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<{ auth: { user: User | null } }>();
const currentUser = computed(() => page.props.auth.user);

interface PrimaryAction {
    label: string;
    href: string;
}

const primaryAction = computed<PrimaryAction | null>(() => {
    const user = currentUser.value;

    if (! user) {
        return null;
    }

    switch (user.role) {
        case 'citizen':
            return {
                label: 'Start submission',
                href: submissionRoutes.create().url,
            };
        case 'mp':
        case 'senator':
            return {
                label: 'View bill workspace',
                href: billRoutes.index().url,
            };
        case 'clerk':
            return {
                label: 'Manage queues',
                href: clerkRoutes.legislators.index().url,
            };
        case 'admin':
            return {
                label: 'Admin dashboard',
                href: dashboard().url,
            };
        default:
            return null;
    }
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="props.breadcrumbs && props.breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-3">
            <Button v-if="primaryAction" variant="secondary" size="sm" as-child>
                <Link :href="primaryAction.href" class="flex items-center gap-1.5">
                    <Plus class="h-4 w-4" />
                    <span class="text-sm font-medium">{{ primaryAction.label }}</span>
                </Link>
            </Button>
            <NotificationBell />
        </div>
    </header>
</template>
