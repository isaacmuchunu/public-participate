<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { BookOpen, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface GlossaryTerm {
    term: string;
    translation: string;
    definition?: string;
    category: 'legal' | 'technical' | 'general';
}

interface Props {
    terms?: GlossaryTerm[];
    searchable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    searchable: true,
    terms: () => [
        {
            term: 'Bill',
            translation: 'Mswada',
            definition: 'A proposed law presented to parliament for consideration',
            category: 'legal',
        },
        {
            term: 'Clause',
            translation: 'Kifungu',
            definition: 'A specific section or provision within a bill',
            category: 'legal',
        },
        {
            term: 'Gazette',
            translation: 'Gazeti',
            definition: 'Official government publication for legal notices',
            category: 'legal',
        },
        {
            term: 'Participation',
            translation: 'Ushiriki',
            definition: 'The process of citizens engaging with legislative processes',
            category: 'general',
        },
        {
            term: 'Submission',
            translation: 'Maoni',
            definition: 'Comments or feedback provided by citizens on bills',
            category: 'technical',
        },
        {
            term: 'Committee',
            translation: 'Kamati',
            definition: 'A parliamentary group that reviews and amends legislation',
            category: 'legal',
        },
        {
            term: 'Sponsor',
            translation: 'Mfadhili',
            definition: 'The legislator or entity that introduces a bill',
            category: 'legal',
        },
        {
            term: 'House',
            translation: 'Nyumba',
            definition: 'Either the National Assembly or Senate',
            category: 'legal',
        },
    ],
});

const searchQuery = ref('');
const selectedCategory = ref<string>('all');

const filteredTerms = computed(() => {
    let filtered = props.terms;

    if (searchQuery.value) {
        filtered = filtered.filter(
            (term) =>
                term.term.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                term.translation.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                (term.definition && term.definition.toLowerCase().includes(searchQuery.value.toLowerCase())),
        );
    }

    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter((term) => term.category === selectedCategory.value);
    }

    return filtered;
});

const categories = [
    { value: 'all', label: 'All Terms' },
    { value: 'legal', label: 'Legal Terms' },
    { value: 'technical', label: 'Technical Terms' },
    { value: 'general', label: 'General Terms' },
];

const getCategoryColor = (category: string) => {
    switch (category) {
        case 'legal':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100';
        case 'technical':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100';
        case 'general':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100';
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <BookOpen class="h-5 w-5" />
                Translation Glossary
            </CardTitle>
            <p class="text-muted-foreground text-sm">Common legal and technical terms with their Swahili translations</p>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Search and Filter -->
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <Search class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                    <Input v-model="searchQuery" placeholder="Search terms..." class="pl-10" />
                </div>

                <div class="flex gap-2">
                    <Button
                        v-for="category in categories"
                        :key="category.value"
                        @click="selectedCategory = category.value"
                        :variant="selectedCategory === category.value ? 'default' : 'outline'"
                        size="sm"
                    >
                        {{ category.label }}
                    </Button>
                </div>
            </div>

            <!-- Terms List -->
            <div class="max-h-96 space-y-3 overflow-y-auto">
                <div v-for="term in filteredTerms" :key="term.term" class="bg-muted/30 rounded-lg border p-4">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="text-lg font-semibold">{{ term.term }}</span>
                                <Badge :class="getCategoryColor(term.category)">
                                    {{ term.category }}
                                </Badge>
                            </div>
                            <div class="text-primary text-lg font-medium">{{ term.translation }}</div>
                        </div>
                    </div>

                    <p v-if="term.definition" class="text-muted-foreground mb-2 text-sm">
                        {{ term.definition }}
                    </p>

                    <div class="text-muted-foreground flex items-center gap-2 text-xs">
                        <span>Category: {{ term.category }}</span>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-if="filteredTerms.length === 0" class="p-8 text-center">
                <Icon name="search" class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                <p class="text-muted-foreground">No terms found matching your search</p>
                <p class="text-muted-foreground mt-1 text-sm">Try adjusting your search terms or category filter</p>
            </div>

            <!-- Footer Note -->
            <div class="text-muted-foreground bg-muted/30 mt-4 rounded p-3 text-xs">
                <p>
                    <strong>Note:</strong> This glossary provides translations for common terms used in the platform. For official legal translations,
                    please consult authorized government sources.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
