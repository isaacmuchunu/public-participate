import { useI18n as useVueI18n } from 'vue-i18n';

export function useI18n() {
    const { t, locale, availableLocales } = useVueI18n();

    const setLocale = (newLocale: 'en' | 'sw') => {
        locale.value = newLocale;
        localStorage.setItem('locale', newLocale);
        document.documentElement.lang = newLocale;
    };

    // Load saved locale on init
    const savedLocale = localStorage.getItem('locale') as 'en' | 'sw' | null;
    if (savedLocale && availableLocales.includes(savedLocale)) {
        locale.value = savedLocale;
        document.documentElement.lang = savedLocale;
    }

    return { t, locale, setLocale, availableLocales };
}
