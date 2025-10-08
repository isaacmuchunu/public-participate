import { useI18n as useVueI18n } from 'vue-i18n';

export function useI18n() {
    const { t, locale, availableLocales } = useVueI18n();

    const setLocale = (newLocale: 'en' | 'sw') => {
        locale.value = newLocale;
        localStorage.setItem('locale', newLocale);
        document.documentElement.lang = newLocale;
        document.documentElement.setAttribute('lang', newLocale);
    };

    return { t, locale, setLocale, availableLocales };
}
