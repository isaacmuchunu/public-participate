<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';

const { locale, setLocale, t } = useI18n();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="focus-visible-ring">
                <Icon name="languages" class="h-4 w-4" />
                <span class="ml-2 hidden sm:inline">
                    {{ locale === 'en' ? 'English' : 'Kiswahili' }}
                </span>
                <span class="sr-only">{{ t('accessibility.languageToggle') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem @click="setLocale('en')" :class="{ 'bg-accent': locale === 'en' }">
                <Icon name="check" class="mr-2 h-4 w-4" :class="{ 'opacity-0': locale !== 'en' }" />
                English
            </DropdownMenuItem>
            <DropdownMenuItem @click="setLocale('sw')" :class="{ 'bg-accent': locale === 'sw' }">
                <Icon name="check" class="mr-2 h-4 w-4" :class="{ 'opacity-0': locale !== 'sw' }" />
                Kiswahili
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
