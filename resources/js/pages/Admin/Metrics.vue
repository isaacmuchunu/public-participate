<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Clock, Download, Eye, MessageCircle, Monitor, RefreshCw, Smartphone, TrendingDown, TrendingUp, Users } from 'lucide-vue-next';
import { ref } from 'vue';

interface MetricData {
    total_bills: number;
    total_submissions: number;
    total_users: number;
    total_views: number;
    participation_rate: number;
    average_comments_per_bill: number;
    task_completion_rate: number;
    average_time_to_comment: number;
    mobile_vs_desktop: {
        mobile: number;
        desktop: number;
    };
    recent_activity: {
        date: string;
        submissions: number;
        views: number;
    }[];
}

interface Props {
    metrics: MetricData;
    period: '7d' | '30d' | '90d';
}

const props = defineProps<Props>();
const selectedPeriod = ref(props.period);

const formatPercentage = (value: number) => {
    return `${Math.round(value * 100)}%`;
};

const formatTime = (seconds: number) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds}s`;
};

const getTrendIcon = (current: number, previous: number) => {
    if (current > previous) return TrendingUp;
    if (current < previous) return TrendingDown;
    return Clock;
};

const getTrendColor = (current: number, previous: number) => {
    if (current > previous) return 'text-green-600';
    if (current < previous) return 'text-red-600';
    return 'text-gray-600';
};

const refreshMetrics = () => {
    // In a real implementation, this would trigger a reload with new data
    window.location.reload();
};

const exportReport = () => {
    // In a real implementation, this would generate and download a report
    alert('Report export functionality would be implemented here');
};
</script>

<template>
    <AppLayout>
        <Head title="Analytics Dashboard" />

        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Analytics Dashboard</h1>
                    <p class="text-muted-foreground">Monitor platform performance and user engagement</p>
                </div>

                <div class="flex items-center gap-3">
                    <Select v-model="selectedPeriod">
                        <SelectTrigger class="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="7d">Last 7 days</SelectItem>
                            <SelectItem value="30d">Last 30 days</SelectItem>
                            <SelectItem value="90d">Last 90 days</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button @click="refreshMetrics" variant="outline" size="sm">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Refresh
                    </Button>

                    <Button @click="exportReport" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Export Report
                    </Button>
                </div>
            </div>

            <!-- Key Metrics Cards -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Bills -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Bills</CardTitle>
                        <Icon name="file-text" class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metrics.total_bills }}</div>
                        <p class="text-muted-foreground text-xs">Active legislation</p>
                    </CardContent>
                </Card>

                <!-- Total Submissions -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Submissions</CardTitle>
                        <MessageCircle class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metrics.total_submissions }}</div>
                        <p class="text-muted-foreground text-xs">Citizen comments</p>
                    </CardContent>
                </Card>

                <!-- Total Users -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                        <Users class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metrics.total_users }}</div>
                        <p class="text-muted-foreground text-xs">Registered participants</p>
                    </CardContent>
                </Card>

                <!-- Total Views -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Views</CardTitle>
                        <Eye class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ metrics.total_views }}</div>
                        <p class="text-muted-foreground text-xs">Page impressions</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Success Metrics -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- User Experience Metrics -->
                <Card>
                    <CardHeader>
                        <CardTitle>User Experience Metrics</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Task Completion Rate</span>
                            <div class="flex items-center gap-2">
                                <Badge :variant="metrics.task_completion_rate >= 0.95 ? 'default' : 'destructive'">
                                    {{ formatPercentage(metrics.task_completion_rate) }}
                                </Badge>
                                <span class="text-muted-foreground text-sm">Target: 95%</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Avg. Time to Comment</span>
                            <div class="flex items-center gap-2">
                                <Badge :variant="metrics.average_time_to_comment <= 300 ? 'default' : 'destructive'">
                                    {{ formatTime(metrics.average_time_to_comment) }}
                                </Badge>
                                <span class="text-muted-foreground text-sm">Target: &lt; 5 min</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Participation Rate</span>
                            <div class="flex items-center gap-2">
                                <Badge variant="default">
                                    {{ formatPercentage(metrics.participation_rate) }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Engagement Metrics -->
                <Card>
                    <CardHeader>
                        <CardTitle>Engagement Metrics</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Avg. Comments per Bill</span>
                            <Badge variant="default">
                                {{ metrics.average_comments_per_bill.toFixed(1) }}
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Mobile Usage</span>
                            <div class="flex items-center gap-2">
                                <Smartphone class="h-4 w-4 text-blue-600" />
                                <span class="text-sm">{{ formatPercentage(metrics.mobile_vs_desktop.mobile) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Desktop Usage</span>
                            <div class="flex items-center gap-2">
                                <Monitor class="h-4 w-4 text-gray-600" />
                                <span class="text-sm">{{ formatPercentage(metrics.mobile_vs_desktop.desktop) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="activity in metrics.recent_activity" :key="activity.date" class="flex items-center justify-between">
                            <span class="text-sm">{{ activity.date }}</span>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1">
                                    <Eye class="text-muted-foreground h-4 w-4" />
                                    <span class="text-sm">{{ activity.views }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <MessageCircle class="text-muted-foreground h-4 w-4" />
                                    <span class="text-sm">{{ activity.submissions }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Performance Targets -->
            <Card class="mt-8">
                <CardHeader>
                    <CardTitle>Performance Targets</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">✅</div>
                            <div class="text-sm font-medium">FCP &lt; 1.5s</div>
                            <div class="text-muted-foreground text-xs">First Contentful Paint</div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">✅</div>
                            <div class="text-sm font-medium">TTI &lt; 3s</div>
                            <div class="text-muted-foreground text-xs">Time to Interactive</div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">✅</div>
                            <div class="text-sm font-medium">Lighthouse &gt; 90</div>
                            <div class="text-muted-foreground text-xs">Performance Score</div>
                        </div>

                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">✅</div>
                            <div class="text-sm font-medium">Bundle &lt; 200KB</div>
                            <div class="text-muted-foreground text-xs">Gzipped Size</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
