/// <reference types="cypress" />
// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

describe('Admin Dashboard', () => {
  beforeEach(() => {
    // Log in before each test
    cy.login(Cypress.env('wpUsername'), Cypress.env('wpPassword'))
    // Visit the admin dashboard
    cy.visit('/wp-admin/admin.php?page=calendrier-rdv')
  })

  it('should load the admin dashboard', () => {
    // Check if the main container is visible
    cy.get('.calendrier-rdv-admin').should('be.visible')
    
    // Check if the calendar is loaded
    cy.get('.fc-view-container').should('be.visible')
    
    // Check if the navigation buttons are present
    cy.get('.fc-prev-button').should('be.visible')
    cy.get('.fc-next-button').should('be.visible')
    cy.get('.fc-today-button').should('be.visible')
  })

  it('should display appointments in the calendar', () => {
    // Wait for appointments to load
    cy.get('.fc-event').should('have.length.gt', 0)
    
    // Click on an appointment
    cy.get('.fc-event').first().click()
    
    // Check if the modal is displayed
    cy.get('.calendrier-rdv-modal').should('be.visible')
    
    // Check if the appointment details are displayed
    cy.get('.calendrier-rdv-modal-title').should('be.visible')
    cy.get('.calendrier-rdv-modal-body').should('be.visible')
    
    // Close the modal
    cy.get('.calendrier-rdv-modal-close').click()
    cy.get('.calendrier-rdv-modal').should('not.be.visible')
  })

  it('should navigate between calendar views', () => {
    // Switch to week view
    cy.get('.fc-agendaWeek-button').click()
    cy.url().should('include', 'view=agendaWeek')
    
    // Switch to day view
    cy.get('.fc-agendaDay-button').click()
    cy.url().should('include', 'view=agendaDay')
    
    // Switch back to month view
    cy.get('.fc-month-button').click()
    cy.url().should('include', 'view=month')
  })

  it('should filter appointments by status', () => {
    // Check if the filter dropdown is present
    cy.get('.calendrier-rdv-filter').should('be.visible')
    
    // Filter by confirmed appointments
    cy.get('.calendrier-rdv-filter').select('confirmed')
    cy.get('.fc-event').should('have.attr', 'data-status', 'confirmed')
    
    // Filter by pending appointments
    cy.get('.calendrier-rdv-filter').select('pending')
    cy.get('.fc-event').should('have.attr', 'data-status', 'pending')
    
    // Show all appointments
    cy.get('.calendrier-rdv-filter').select('all')
    cy.get('.fc-event').should('have.length.gt', 0)
  })
})
