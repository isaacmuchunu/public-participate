<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { MessageCircle, Minus, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface SentimentData {
    positive: number;
    negative: number;
    neutral: number;
    total: number;
    average_score: number;
    trends: {
        positive_trend: number;
        negative_trend: number;
        neutral_trend: number;
    };
}

interface Props {
    data?: SentimentData | null;
    loading?: boolean;
    billTitle?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    billTitle: 'Bill',
});

const sentimentScore = computed(() => {
    if (!props.data) return 0;
    return props.data.average_score;
});

const sentimentLabel = computed(() => {
    if (sentimentScore.value > 0.1) return 'Positive';
    if (sentimentScore.value < -0.1) return 'Negative';
    return 'Neutral';
});

const sentimentColor = computed(() => {
    if (sentimentScore.value > 0.1) return 'text-green-600';
    if (sentimentScore.value < -0.1) return 'text-red-600';
    return 'text-gray-600';
});

const sentimentIcon = computed(() => {
    if (sentimentScore.value > 0.1) return TrendingUp;
    if (sentimentScore.value < -0.1) return TrendingDown;
    return Minus;
});

const formatPercentage = (value: number) => {
    return `${Math.round(value * 100)}%`;
};

const getSentimentBadgeVariant = (score: number) => {
    if (score > 0.1) return 'default';
    if (score < -0.1) return 'destructive';
    return 'secondary';
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <MessageCircle class="h-5 w-5" />
                Public Sentiment Analysis
            </CardTitle>
        </CardHeader>

        <CardContent class="space-y-6">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center p-8">
                <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-primary"></div>
                <span class="ml-2 text-sm text-muted-foreground">Analyzing sentiment...</span>
            </div>

            <!-- No Data State -->
            <div v-else-if="!data" class="p-8 text-center">
                <Icon name="bar-chart-3" class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                <p class="text-muted-foreground">No sentiment data available yet</p>
                <p class="mt-1 text-sm text-muted-foreground">Sentiment analysis will appear once there are enough comments to analyze</p>
            </div>

            <!-- Sentiment Data -->
            <div v-else class="space-y-6">
                <!-- Overall Sentiment -->
                <div class="text-center">
                    <div class="mb-2 flex items-center justify-center gap-2">
                        <component :is="sentimentIcon" :class="['h-6 w-6', sentimentColor]" />
                        <span class="text-lg font-semibold">{{ sentimentLabel }}</span>
                    </div>
                    <Badge :variant="getSentimentBadgeVariant(sentimentScore)"> Score: {{ sentimentScore.toFixed(2) }} </Badge>
                    <p class="mt-2 text-sm text-muted-foreground">Based on {{ data.total }} comments</p>
                </div>

                <!-- Sentiment Breakdown -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatPercentage(data.positive) }}
                        </div>
                        <div class="text-sm text-muted-foreground">Positive</div>
                        <div class="mt-1 flex items-center justify-center gap-1">
                            <TrendingUp class="h-3 w-3 text-green-600" />
                            <span class="text-xs text-green-600">
                                {{ data.trends.positive_trend > 0 ? '+' : '' }}{{ formatPercentage(data.trends.positive_trend) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">
                            {{ formatPercentage(data.neutral) }}
                        </div>
                        <div class="text-sm text-muted-foreground">Neutral</div>
                        <div class="mt-1 flex items-center justify-center gap-1">
                            <Minus class="h-3 w-3 text-gray-600" />
                            <span class="text-xs text-gray-600">
                                {{ data.trends.neutral_trend > 0 ? '+' : '' }}{{ formatPercentage(data.trends.neutral_trend) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatPercentage(data.negative) }}
                        </div>
                        <div class="text-sm text-muted-foreground">Negative</div>
                        <div class="mt-1 flex items-center justify-center gap-1">
                            <TrendingDown class="h-3 w-3 text-red-600" />
                            <span class="text-xs text-red-600">
                                {{ data.trends.negative_trend > 0 ? '+' : '' }}{{ formatPercentage(data.trends.negative_trend) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Sentiment Trends -->
                <div class="space-y-2">
                    <h4 class="text-sm font-medium">Recent Trends</h4>
                    <div class="grid grid-cols-1 gap-2">
                        <div class="flex items-center justify-between rounded bg-muted/50 p-2">
                            <span class="text-sm">Positive sentiment</span>
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-16 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-green-500 transition-all"
                                        :style="{ width: `${Math.min(100, data.trends.positive_trend * 100)}%` }"
                                    ></div>
                                </div>
                                <span class="text-xs font-medium text-green-600">
                                    {{ formatPercentage(data.trends.positive_trend) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded bg-muted/50 p-2">
                            <span class="text-sm">Neutral sentiment</span>
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-16 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-gray-500 transition-all"
                                        :style="{ width: `${Math.min(100, data.trends.neutral_trend * 100)}%` }"
                                    ></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600">
                                    {{ formatPercentage(data.trends.neutral_trend) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded bg-muted/50 p-2">
                            <span class="text-sm">Negative sentiment</span>
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-16 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-red-500 transition-all"
                                        :style="{ width: `${Math.min(100, data.trends.negative_trend * 100)}%` }"
                                    ></div>
                                </div>
                                <span class="text-xs font-medium text-red-600">
                                    {{ formatPercentage(data.trends.negative_trend) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="rounded bg-muted/30 p-3 text-xs text-muted-foreground">
                    <p>
                        <strong>Note:</strong> Sentiment analysis is powered by AI and may not always be 100% accurate. This is for informational
                        purposes only and should not be considered official analysis.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
