import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import sw from '../locales/sw.json';

// Get saved locale from localStorage or default to 'en'
const savedLocale = localStorage.getItem('locale') as 'en' | 'sw' | null;
const initialLocale = savedLocale && ['en', 'sw'].includes(savedLocale) ? savedLocale : 'en';

// Set document language on initial load
document.documentElement.lang = initialLocale;
document.documentElement.setAttribute('lang', initialLocale);

const i18n = createI18n({
    legacy: false, // Use Composition API
    locale: initialLocale,
    fallbackLocale: 'en',
    messages: {
        en,
        sw,
    },
    globalInjection: true,
});

export default i18n;
