<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';
import { dashboard, appearance } from '@/routes';
import * as bills from '@/routes/bills';
import * as submissions from '@/routes/submissions';
import * as submissionsTrack from '@/routes/submissions/track';
import * as sessions from '@/routes/sessions';
import * as profile from '@/routes/profile';
import * as notifications from '@/routes/notifications';
import clerk from '@/routes/clerk';
import { type NavItem, type User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BellRing,
    CheckCheck,
    ClipboardPlus,
    Compass,
    LayoutGrid,
    LifeBuoy,
    MessageSquare,
    NotebookPen,
    Settings,
    ShieldCheck,
    UserCog,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<{ auth: { user: User | null } }>();
const currentUser = computed(() => page.props.auth.user);

const primaryNavItems = computed<NavItem[]>(() => {
    if (! currentUser.value) {
        return [];
    }

    const role = currentUser.value.role ?? 'citizen';
    const items: NavItem[] = [
        {
            title: 'Overview',
            href: dashboard().url,
            icon: LayoutGrid,
        },
    ];

    if (role === 'citizen') {
        items.push(
            {
                title: 'Participation hub',
                href: bills.participate().url,
                icon: ClipboardPlus,
            },
            {
                title: 'Submission history',
                href: submissions.index().url,
                icon: BarChart3,
            }
        );
    } else if (role === 'mp' || role === 'senator') {
        items.push(
            {
                title: 'Bill register',
                href: bills.index().url,
                icon: ClipboardPlus,
            },
            {
                title: 'Citizen feedback',
                href: submissions.index().url,
                icon: BarChart3,
            }
        );
    } else if (role === 'admin') {
        items.push(
            {
                title: 'System intelligence',
                href: submissions.index().url,
                icon: LayoutGrid,
            },
            {
                title: 'Account governance',
                href: clerk.citizens.index().url,
                icon: UserCog,
            },
            {
                title: 'Legislator onboarding',
                href: clerk.legislators.index().url,
                icon: ShieldCheck,
            }
        );
    } else {
        items.push(
            {
                title: 'Bill workspace',
                href: bills.index().url,
                icon: ClipboardPlus,
            },
            {
                title: 'Review queue',
                href: submissions.index().url,
                icon: BarChart3,
            },
            {
                title: 'System controls',
                href: appearance().url,
                icon: Settings,
            }
        );
    }

    return items;
});

const quickActions = computed<NavItem[]>(() => {
    if (! currentUser.value) {
        return [];
    }

    const role = currentUser.value.role ?? 'citizen';

    if (role === 'citizen') {
        return [
            {
                title: 'Start a submission',
                href: submissions.create().url,
                icon: MessageSquare,
            },
            {
                title: 'Saved drafts',
                href: `${submissions.create().url}#drafts-panel`,
                icon: NotebookPen,
            },
            {
                title: 'Track submission',
                href: submissionsTrack.form().url,
                icon: Compass,
            },
            {
                title: 'Active sessions',
                href: sessions.index().url,
                icon: ShieldCheck,
            },
            {
                title: 'Profile & contacts',
                href: profile.edit().url,
                icon: UserCog,
            },
        ];
    }

    if (role === 'mp' || role === 'senator') {
        return [
            {
                title: 'Submission analytics',
                href: submissions.index().url,
                icon: BarChart3,
            },
            {
                title: 'Sessions & devices',
                href: sessions.index().url,
                icon: ShieldCheck,
            },
            {
                title: 'Update profile',
                href: profile.edit().url,
                icon: UserCog,
            },
            {
                title: 'Public participation view',
                href: bills.participate().url,
                icon: Compass,
            },
        ];
    }

    if (role === 'admin') {
        return [
            {
                title: 'Audit sessions',
                href: sessions.index().url,
                icon: ShieldCheck,
            },
            {
                title: 'Review invitations',
                href: clerk.legislators.index().url,
                icon: CheckCheck,
            },
            {
                title: 'Citizen registry',
                href: clerk.citizens.index().url,
                icon: Users,
            },
            {
                title: 'Profile & security',
                href: profile.edit().url,
                icon: UserCog,
            },
        ];
    }

    return [
        {
            title: 'Create bill',
            href: bills.create().url,
            icon: ClipboardPlus,
        },
        {
            title: 'Manage sessions',
            href: sessions.index().url,
            icon: ShieldCheck,
        },
        {
            title: 'Account settings',
            href: profile.edit().url,
            icon: UserCog,
        },
        {
            title: 'Branding & theme',
            href: appearance().url,
            icon: LifeBuoy,
        },
    ];
});

const insightLink = computed<NavItem>(() => {
    if (! currentUser.value) {
        return {
            title: 'Participation insights',
            href: submissions.index().url,
            icon: CheckCheck,
        };
    }

    const role = currentUser.value.role ?? 'citizen';

    if (role === 'admin') {
        return {
            title: 'System audit log',
            href: sessions.index().url,
            icon: ShieldCheck,
        };
    }

    if (role === 'mp' || role === 'senator') {
        return {
            title: 'Participation insights',
            href: submissions.index().url,
            icon: CheckCheck,
        };
    }

    if (role === 'clerk') {
        return {
            title: 'Workflow reminders',
            href: bills.index().url,
            icon: ClipboardPlus,
        };
    }

    return {
        title: 'Participation insights',
        href: submissions.index().url,
        icon: CheckCheck,
    };
});
</script>

<template>
    <Sidebar v-if="currentUser" collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="primaryNavItems" />

            <SidebarGroup v-if="quickActions.length" class="px-2 py-0">
                <SidebarGroupLabel>Quick actions</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in quickActions" :key="item.title">
                            <SidebarMenuButton as-child :tooltip="item.title">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Insights</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child :tooltip="insightLink.title">
                                <Link :href="insightLink.href">
                                    <component :is="insightLink.icon" />
                                    <span>{{ insightLink.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="mt-auto px-2 pb-2">
            <SidebarGroup class="w-full">
                <SidebarGroupLabel>Account</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child :tooltip="'Profile & security'">
                                <Link :href="profile.edit().url">
                                    <UserCog />
                                    <span>Profile & security</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child :tooltip="'Device sessions'">
                                <Link :href="sessions.index().url">
                                    <ShieldCheck />
                                    <span>Device sessions</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child :tooltip="'Notification centre'">
                                <Link :href="notifications.index().url">
                                    <BellRing />
                                    <span>Notification centre</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
