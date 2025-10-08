<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';
import { useAccessibility } from '@/composables/useAccessibility';

const {
    preferences,
    setHighContrast,
    setReduceMotion,
    setFontSize,
    setUnderlineLinks,
    setKeyboardShortcuts,
    resetToDefaults,
} = useAccessibility();

const highContrast = computed({
    get: () => preferences.value.highContrast,
    set: (value: boolean) => setHighContrast(value),
});

const reduceMotion = computed({
    get: () => preferences.value.reduceMotion,
    set: (value: boolean) => setReduceMotion(value),
});

const fontSize = computed({
    get: () => preferences.value.fontSize,
    set: (value: 'sm' | 'md' | 'lg' | 'xl') => setFontSize(value),
});

const underlineLinks = computed({
    get: () => preferences.value.underlineLinks,
    set: (value: boolean) => setUnderlineLinks(value),
});

const keyboardShortcuts = computed({
    get: () => preferences.value.keyboardShortcuts,
    set: (value: boolean) => setKeyboardShortcuts(value),
});

const fontSizeOptions = [
    { value: 'sm', label: 'Small', percent: '90%' },
    { value: 'md', label: 'Medium (Default)', percent: '100%' },
    { value: 'lg', label: 'Large', percent: '125%' },
    { value: 'xl', label: 'Extra Large', percent: '150%' },
];

const handleReset = () => {
    resetToDefaults();
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Accessibility Preferences</CardTitle>
            <CardDescription> Customize your reading and navigation experience to meet your needs </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">
            <!-- Visual Adjustments Section -->
            <section class="space-y-4">
                <h3 class="text-foreground text-sm font-semibold">Visual Adjustments</h3>

                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <Label for="high-contrast" class="cursor-pointer text-sm font-medium"> High Contrast Mode </Label>
                        <p class="text-muted-foreground mt-1 text-xs">Increases contrast between text and background for better readability</p>
                    </div>
                    <Checkbox id="high-contrast" v-model:checked="highContrast" aria-describedby="high-contrast-description" />
                </div>
                <span id="high-contrast-description" class="sr-only"> Toggle high contrast mode for improved visibility </span>

                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <Label for="underline-links" class="cursor-pointer text-sm font-medium"> Underline Links </Label>
                        <p class="text-muted-foreground mt-1 text-xs">Underlines all links to make them easier to identify</p>
                    </div>
                    <Checkbox id="underline-links" v-model:checked="underlineLinks" aria-describedby="underline-links-description" />
                </div>
                <span id="underline-links-description" class="sr-only"> Toggle link underlining for improved visibility </span>

                <div class="space-y-2">
                    <Label for="font-size" class="text-sm font-medium"> Font Size </Label>
                    <p class="text-muted-foreground text-xs">Adjust text size for comfortable reading</p>
                    <div role="radiogroup" aria-labelledby="font-size" class="mt-2 grid grid-cols-2 gap-2">
                        <button
                            v-for="option in fontSizeOptions"
                            :key="option.value"
                            type="button"
                            role="radio"
                            :aria-checked="fontSize === option.value"
                            :class="[
                                'rounded-lg border-2 px-4 py-3 text-left text-sm transition',
                                fontSize === option.value ? 'border-primary bg-primary/5 font-semibold' : 'border-border hover:border-primary/50',
                            ]"
                            @click="fontSize = option.value"
                        >
                            <div>{{ option.label }}</div>
                            <div class="text-muted-foreground text-xs">{{ option.percent }}</div>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Motion & Animation Section -->
            <section class="space-y-4">
                <h3 class="text-foreground text-sm font-semibold">Motion & Animation</h3>

                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <Label for="reduce-motion" class="cursor-pointer text-sm font-medium"> Reduce Motion </Label>
                        <p class="text-muted-foreground mt-1 text-xs">Minimizes animations and transitions that may cause discomfort</p>
                    </div>
                    <Checkbox id="reduce-motion" v-model:checked="reduceMotion" aria-describedby="reduce-motion-description" />
                </div>
                <span id="reduce-motion-description" class="sr-only"> Toggle reduced motion for improved comfort </span>
            </section>

            <!-- Keyboard & Navigation Section -->
            <section class="space-y-4">
                <h3 class="text-foreground text-sm font-semibold">Keyboard & Navigation</h3>

                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <Label for="keyboard-shortcuts" class="cursor-pointer text-sm font-medium"> Enable Keyboard Shortcuts </Label>
                        <p class="text-muted-foreground mt-1 text-xs">Use keyboard shortcuts to navigate faster (press ? to see all shortcuts)</p>
                    </div>
                    <Checkbox id="keyboard-shortcuts" v-model:checked="keyboardShortcuts" aria-describedby="keyboard-shortcuts-description" />
                </div>
                <span id="keyboard-shortcuts-description" class="sr-only"> Toggle keyboard shortcuts for improved navigation </span>
            </section>

            <!-- Actions -->
            <div class="flex justify-between border-t pt-4">
                <Button variant="outline" @click="handleReset"> Reset to Defaults </Button>

                <p class="text-muted-foreground self-center text-xs">Preferences are saved automatically</p>
            </div>
        </CardContent>
    </Card>
</template>
