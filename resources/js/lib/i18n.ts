import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import sw from '../locales/sw.json';

const i18n = createI18n({
    legacy: false, // Use Composition API
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en,
        sw,
    },
    globalInjection: true,
});

export default i18n;
