import { onMounted, ref } from 'vue';

export interface AccessibilityPreferences {
    highContrast: boolean;
    reduceMotion: boolean;
    fontSize: 'sm' | 'md' | 'lg' | 'xl';
    underlineLinks: boolean;
    keyboardShortcuts: boolean;
}

const defaultPreferences: AccessibilityPreferences = {
    highContrast: false,
    reduceMotion: false,
    fontSize: 'md',
    underlineLinks: false,
    keyboardShortcuts: true,
};

// Global state for accessibility preferences
const preferences = ref<AccessibilityPreferences>({ ...defaultPreferences });
const isInitialized = ref(false);

export function useAccessibility() {
    const loadPreferences = () => {
        if (isInitialized.value) return;

        const stored = localStorage.getItem('accessibility-preferences');
        if (stored) {
            try {
                const parsed = JSON.parse(stored);
                preferences.value = { ...defaultPreferences, ...parsed };
            } catch (e) {
                console.error('Failed to parse accessibility preferences', e);
            }
        }

        // Also check for system preferences
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            preferences.value.reduceMotion = true;
        }

        if (window.matchMedia('(prefers-contrast: high)').matches) {
            preferences.value.highContrast = true;
        }

        isInitialized.value = true;
    };

    const savePreferences = () => {
        localStorage.setItem('accessibility-preferences', JSON.stringify(preferences.value));
        applyPreferences();
    };

    const applyPreferences = () => {
        const root = document.documentElement;

        // High contrast mode
        root.classList.toggle('high-contrast', preferences.value.highContrast);

        // Reduce motion
        root.classList.toggle('reduce-motion', preferences.value.reduceMotion);

        // Font size
        root.classList.remove('font-sm', 'font-md', 'font-lg', 'font-xl');
        root.classList.add(`font-${preferences.value.fontSize}`);

        // Underline links
        root.classList.toggle('underline-links', preferences.value.underlineLinks);

        // Update CSS custom properties for font scaling
        const fontScaleMap = {
            sm: '0.9',
            md: '1',
            lg: '1.125',
            xl: '1.25',
        };
        root.style.setProperty('--font-scale', fontScaleMap[preferences.value.fontSize]);
    };

    const setHighContrast = (value: boolean) => {
        preferences.value.highContrast = value;
        savePreferences();
    };

    const setReduceMotion = (value: boolean) => {
        preferences.value.reduceMotion = value;
        savePreferences();
    };

    const setFontSize = (size: 'sm' | 'md' | 'lg' | 'xl') => {
        preferences.value.fontSize = size;
        savePreferences();
    };

    const setUnderlineLinks = (value: boolean) => {
        preferences.value.underlineLinks = value;
        savePreferences();
    };

    const setKeyboardShortcuts = (value: boolean) => {
        preferences.value.keyboardShortcuts = value;
        savePreferences();
    };

    const resetToDefaults = () => {
        preferences.value = { ...defaultPreferences };
        savePreferences();
    };

    // Screen reader announcement helper
    const announce = (message: string, priority: 'polite' | 'assertive' = 'polite') => {
        const announcer = document.createElement('div');
        announcer.setAttribute('role', 'status');
        announcer.setAttribute('aria-live', priority);
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        announcer.textContent = message;

        document.body.appendChild(announcer);

        setTimeout(() => {
            document.body.removeChild(announcer);
        }, 1000);
    };

    // Keyboard navigation helpers
    const trapFocus = (element: HTMLElement) => {
        const focusableElements = element.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements[0] as HTMLElement;
        const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

        const handleTabKey = (e: KeyboardEvent) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        };

        element.addEventListener('keydown', handleTabKey);

        return () => {
            element.removeEventListener('keydown', handleTabKey);
        };
    };

    // Initialize on mount
    onMounted(() => {
        loadPreferences();
        applyPreferences();
    });

    return {
        preferences,
        setHighContrast,
        setReduceMotion,
        setFontSize,
        setUnderlineLinks,
        setKeyboardShortcuts,
        resetToDefaults,
        announce,
        trapFocus,
        loadPreferences,
        applyPreferences,
    };
}
