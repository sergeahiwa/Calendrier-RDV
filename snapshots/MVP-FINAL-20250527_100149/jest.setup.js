// Configuration de base pour les tests Jest
import '@testing-library/jest-dom';

// Configuration pour i18next
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

i18n.use(initReactI18next).init({
  lng: 'fr',
  fallbackLng: 'fr',
  debug: false,
  ns: ['common'],
  defaultNS: 'common',
  interpolation: {
    escapeValue: false,
  },
  resources: {
    fr: {
      common: require('./assets/locales/fr/common.json'),
    },
    en: {
      common: require('./assets/locales/en/common.json'),
    },
  },
});

// Mocks globaux
window.matchMedia = window.matchMedia || (() => ({
  matches: false,
  addListener: () => {},
  removeListener: () => {},
}));

// Configuration pour les tests de composants React
const originalError = console.error;
beforeAll(() => {
  // Ignorer les avertissements spécifiques pendant les tests
  console.error = (...args) => {
    if (
      /Warning: React does not recognize the.*prop on a DOM element/.test(args[0]) ||
      /Warning: validateDOMNesting/.test(args[0]) ||
      /Warning: Each child in a list should have a unique "key" prop/.test(args[0]) ||
      /Warning: Failed prop type/.test(args[0]) ||
      /Warning: Can't perform a React state update on an unmounted component/.test(args[0]) ||
      /Warning: An update to/.test(args[0]) && /inside a test was not wrapped in act/.test(args[0])
    ) {
      return;
    }
    originalError.call(console, ...args);
  };
});

afterAll(() => {
  // Restaurer la fonction console.error d'origine
  console.error = originalError;
});
