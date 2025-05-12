<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList, navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { toUrl, urlIsActive } from '@/lib/utils';
import { dashboard, home } from '@/routes';
import * as billRoutes from '@/routes/bills';
import * as notificationRoutes from '@/routes/notifications';
import * as submissionRoutes from '@/routes/submissions';
import type { BreadcrumbItem, NavItem } from '@/types';
import { InertiaLinkProps, Link, router, usePage } from '@inertiajs/vue3';
import { Bell, BookOpen, Folder, LayoutGrid, Menu, Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const isAuthenticated = computed(() => Boolean(auth.value?.user));

const notificationSummary = computed(() => {
    const summary = page.props.notifications as { unread_count?: number; latest?: Array<Record<string, any>> } | undefined;

    return summary ?? { unread_count: 0, latest: [] };
});

const unreadCount = computed(() => notificationSummary.value.unread_count ?? 0);
const hasUnread = computed(() => unreadCount.value > 0);
const notificationPreview = computed(() => notificationSummary.value.latest ?? []);

const quickTitle = (notification: Record<string, any>): string => {
    switch (notification.type) {
        case 'bill_published':
            return `New bill: ${notification.data?.title ?? ''}`.trim();
        case 'participation_opened':
            return `Commentary open: ${notification.data?.title ?? ''}`.trim();
        case 'submission_aggregated':
            return 'Submission aggregated';
        case 'legislator_follow_up':
            return `${notification.data?.sender?.name ?? 'Legislator'} followed up`;
        default:
            return 'Portal update';
    }
};

const quickBody = (notification: Record<string, any>): string => {
    switch (notification.type) {
        case 'bill_published':
            return 'Review the newly published bill overview.';
        case 'participation_opened':
            return 'Share your views before the commentary window closes.';
        case 'submission_aggregated':
            return 'Your submission is now part of the committee pack.';
        case 'legislator_follow_up':
            return notification.data?.subject ?? 'Respond to the legislator’s request for more details.';
        default:
            return 'Stay informed on your participation activity.';
    }
};

const quickLink = (notification: Record<string, any>): string | null => {
    switch (notification.type) {
        case 'bill_published':
        case 'participation_opened':
            return notification.data?.bill_id ? billRoutes.show({ bill: notification.data.bill_id }).url : null;
        case 'submission_aggregated':
        case 'legislator_follow_up':
            return notification.data?.submission_id ? submissionRoutes.show({ submission: notification.data.submission_id }).url : null;
        default:
            return null;
    }
};

const quickTimestamp = (value: string | null | undefined): string => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString();
};

const markAllNotifications = () => {
    if (!isAuthenticated.value) {
        return;
    }

    router.post(notificationRoutes.readAll().url, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const isCurrentRoute = computed(() => (url: NonNullable<InertiaLinkProps['href']>) => urlIsActive(url, page.url));

const activeItemStyles = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        isCurrentRoute.value(toUrl(url)) ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : '',
);

const mainNavItems = computed<NavItem[]>(() =>
    isAuthenticated.value
        ? [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ]
        : []
);

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon class="size-6 fill-current text-black dark:text-white" />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <nav v-if="mainNavItems.length" class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="activeItemStyles(item.href)"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="isAuthenticated ? dashboard() : home()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div v-if="mainNavItems.length" class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-2">
                            <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index" class="relative flex h-full items-center">
                                <Link
                                    :class="[navigationMenuTriggerStyle(), activeItemStyles(item.href), 'h-9 cursor-pointer px-3']"
                                    :href="item.href"
                                >
                                    <component v-if="item.icon" :is="item.icon" class="mr-2 h-4 w-4" />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentRoute(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <Button variant="ghost" size="icon" class="group h-9 w-9 cursor-pointer">
                            <Search class="size-5 opacity-80 group-hover:opacity-100" />
                        </Button>

                        <div class="hidden space-x-1 lg:flex">
                            <template v-for="item in rightNavItems" :key="item.title">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button variant="ghost" size="icon" as-child class="group h-9 w-9 cursor-pointer">
                                                <a :href="toUrl(item.href)" target="_blank" rel="noopener noreferrer">
                                                    <span class="sr-only">{{ item.title }}</span>
                                                    <component :is="item.icon" class="size-5 opacity-80 group-hover:opacity-100" />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
                    </div>

                    <DropdownMenu v-if="isAuthenticated">
                        <DropdownMenuTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="relative h-9 w-9">
                                <Bell class="size-5" />
                                <span
                                    v-if="hasUnread"
                                    class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-emerald-500 px-1 text-[11px] font-semibold text-white shadow-sm"
                                >
                                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-80 p-0">
                            <div class="flex items-center justify-between border-b border-sidebar-border/60 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-foreground">Notifications</p>
                                    <p class="text-xs text-muted-foreground">{{ hasUnread ? `${unreadCount} unread updates` : 'All updates read' }}</p>
                                </div>
                                <Link
                                    :href="notificationRoutes.index().url"
                                    class="text-xs font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    View all
                                </Link>
                            </div>
                            <div v-if="notificationPreview.length" class="max-h-80 overflow-y-auto">
                                <DropdownMenuItem v-for="notification in notificationPreview" :key="notification.id" as-child class="whitespace-normal px-0 py-0">
                                    <Link :href="quickLink(notification) ?? notificationRoutes.index().url" class="flex flex-col gap-1 px-4 py-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-sm font-semibold text-foreground">{{ quickTitle(notification) }}</p>
                                            <span class="text-[11px] text-muted-foreground">{{ quickTimestamp(notification.created_at ?? null) }}</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ quickBody(notification) }}</p>
                                    </Link>
                                </DropdownMenuItem>
                            </div>
                            <div v-else class="px-4 py-6 text-center text-sm text-muted-foreground">
                                You are up to date.
                            </div>
                            <DropdownMenuSeparator />
                            <div class="flex items-center justify-between px-4 py-3">
                                <Link :href="notificationRoutes.index().url" class="text-xs font-medium text-primary underline-offset-4 hover:underline">
                                    Notification centre
                                </Link>
                                <Button variant="ghost" size="sm" class="text-xs" @click.prevent="markAllNotifications">
                                    Mark all read
                                </Button>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <DropdownMenu v-if="isAuthenticated">
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="auth.user.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-sidebar-border/70">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
