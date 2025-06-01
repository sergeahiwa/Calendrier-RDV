// Configuration d'i18next pour le frontend
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import Backend from 'i18next-http-backend';
import LanguageDetector from 'i18next-browser-languagedetector';

// Configuration des espaces de noms pour les traductions
const namespaces = [
  'common',
  'calendar',
  'appointments',
  'validation',
  'notifications'
];

i18n
  // Chargement des traductions via XHR
  .use(Backend)
  // Détection automatique de la langue
  .use(LanguageDetector)
  // Initialisation de react-i18next
  .use(initReactI18next)
  .init({
    // Langue par défaut
    lng: document.documentElement.lang || 'fr',
    fallbackLng: 'fr',
    debug: process.env.NODE_ENV === 'development',
    ns: namespaces,
    defaultNS: 'common',
    
    // Configuration du chargement des traductions
    backend: {
      loadPath: `${window.calendrierRdvVars.pluginUrl}assets/locales/{{lng}}/{{ns}}.json`,
    },
    
    // Options de détection de langue
    detection: {
      order: ['querystring', 'cookie', 'localStorage', 'navigator'],
      caches: ['localStorage', 'cookie'],
      lookupQuerystring: 'lang',
      lookupCookie: 'calendrier_rdv_language',
      lookupLocalStorage: 'calendrier_rdv_language',
    },
    
    // Options d'interpolation
    interpolation: {
      escapeValue: false, // React s'occupe déjà de l'échappement
    },
    
    // Options de réactivité
    react: {
      useSuspense: false,
      bindI18n: 'languageChanged loaded',
      bindStore: 'added removed',
      nsMode: 'default'
    }
  });

export default i18n;
