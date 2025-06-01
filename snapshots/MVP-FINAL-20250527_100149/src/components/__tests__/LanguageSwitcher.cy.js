import React from 'react';
import { I18nextProvider } from 'react-i18next';
import { mount } from '@cypress/react';
import i18n from '../../../cypress/fixtures/i18n';
import LanguageSwitcher from '../LanguageSwitcher';

// Configuration des tests
describe('LanguageSwitcher Component', () => {
  beforeEach(() => {
    // Configuration de la langue par défaut avant chaque test
    i18n.changeLanguage('fr');
    
    // Monter le composant avec le fournisseur i18n
    mount(
      <I18nextProvider i18n={i18n}>
        <LanguageSwitcher />
      </I18nextProvider>
    );
  });

  it('should render with French as default language', () => {
    cy.get('[data-testid="language-switcher"]')
      .should('be.visible')
      .and('contain', 'Français');
  });

  it('should switch to English when selected', () => {
    // Vérifier que le sélecteur de langue est présent
    cy.get('[data-testid="language-select"]')
      .should('exist')
      .select('en');
    
    // Vérifier que la langue a été changée
    cy.window().its('i18n.language').should('eq', 'en');
    
    // Vérifier que le texte a été traduit
    cy.get('[data-testid="language-switcher"]')
      .should('contain', 'English');
  });

  it('should update UI when language changes', () => {
    // Changer la langue en anglais
    cy.get('[data-testid="language-select"]').select('en');
    
    // Vérifier que les éléments d'interface sont mis à jour
    cy.get('button[type="submit"]')
      .should('contain', 'Save')
      .and('be.visible');
      
    // Changer la langue en français
    cy.get('[data-testid="language-select"]').select('fr');
    
    // Vérifier que les éléments d'interface sont mis à jour
    cy.get('button[type="submit"]')
      .should('contain', 'Enregistrer')
      .and('be.visible');
  });

  it('should persist language preference', () => {
    // Changer la langue en anglais
    cy.get('[data-testid="language-select"]').select('en');
    
    // Vérifier que la préférence est enregistrée
    cy.window().then((win) => {
      expect(win.localStorage.getItem('i18nextLng')).to.equal('en');
    });
    
    // Recharger la page
    cy.reload();
    
    // Vérifier que la langue est toujours en anglais
    cy.window().its('i18n.language').should('eq', 'en');
  });
});
