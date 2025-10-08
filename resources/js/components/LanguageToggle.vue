<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import { Check, Globe } from 'lucide-vue-next';
import { computed } from 'vue';

const { t, locale, setLocale } = useI18n();

interface Language {
    code: 'en' | 'sw';
    name: string;
    nativeName: string;
}

const languages: Language[] = [
    { code: 'en', name: 'English', nativeName: 'English' },
    { code: 'sw', name: 'Swahili', nativeName: 'Kiswahili' },
];

const currentLanguage = computed(() => {
    return languages.find((lang) => lang.code === locale.value) || languages[0];
});

const handleLanguageChange = (languageCode: 'en' | 'sw') => {
    setLocale(languageCode);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" :aria-label="t('accessibility.languageSelector')" :title="t('language.change_language')">
                <Globe class="h-5 w-5" />
                <span class="sr-only">
                    {{ t('accessibility.currentLanguage', { language: currentLanguage.nativeName }) }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-48">
            <DropdownMenuLabel>{{ t('language.select_language') }}</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuItem
                v-for="language in languages"
                :key="language.code"
                @click="handleLanguageChange(language.code)"
                :class="{ 'bg-accent': locale === language.code }"
                class="flex cursor-pointer items-center justify-between"
            >
                <div class="flex flex-col">
                    <span class="font-medium">{{ language.nativeName }}</span>
                    <span class="text-muted-foreground text-xs">{{ language.name }}</span>
                </div>
                <Check v-if="locale === language.code" class="text-primary h-4 w-4" :aria-label="t('common.selected')" />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
