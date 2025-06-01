import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import Backend from 'i18next-http-backend';
import LanguageDetector from 'i18next-browser-languagedetector';

// Configuration de test pour i18next
const initI18n = () => {
  return i18n
    .use(Backend)
    .use(LanguageDetector)
    .use(initReactI18next)
    .init({
      lng: 'fr',
      fallbackLng: 'fr',
      debug: false,
      ns: ['common'],
      defaultNS: 'common',
      interpolation: {
        escapeValue: false,
      },
      backend: {
        loadPath: '/locales/{{lng}}/{{ns}}.json',
      },
    });
};

describe('i18n Configuration', () => {
  beforeAll(async () => {
    await initI18n();
  });

  it('should initialize i18n', () => {
    expect(i18n.isInitialized).toBeTruthy();
  });

  it('should have french translations', () => {
    expect(i18n.t('app.name')).toBe('Calendrier RDV');
    expect(i18n.t('app.loading')).toBe('Chargement...');
  });

  it('should switch to english', async () => {
    await i18n.changeLanguage('en');
    expect(i18n.language).toBe('en');
    expect(i18n.t('app.name')).toBe('Appointment Scheduler');
  });
});
