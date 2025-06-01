const { defineConfig } = require('cypress')

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8000',
    supportFile: 'cypress/support/e2e.js',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    setupNodeEvents(on, config) {
      // Configuration de la couverture de code
      if (process.env.CYPRESS_COVERAGE === 'true') {
        require('@cypress/code-coverage/task')(on, config)
      }
      
      // Configuration pour le chargement des fichiers de traduction
      on('before:browser:launch', (browser = {}, launchOptions) => {
        if (browser.family === 'chromium' && browser.name !== 'electron') {
          launchOptions.preferences.default.intl = { accept_languages: 'fr,en' }
        }
        return launchOptions
      })
      
      return config
    },
    video: false,
    screenshotOnRunFailure: true,
    viewportWidth: 1280,
    viewportHeight: 720,
    defaultCommandTimeout: 10000,
    env: {
      adminUsername: 'admin',
      adminPassword: 'password',
      apiUrl: 'http://localhost:8080/wp-json/calendrier-rdv/v1',
      defaultLanguage: 'fr',
      supportedLanguages: ['fr', 'en']
    },
    // Configuration pour ignorer les erreurs non attrapées
    chromeWebSecurity: false,
    retries: {
      runMode: 2,
      openMode: 0
    }
  },
  // Configuration pour les tests de composants
  component: {
    devServer: {
      framework: 'react',
      bundler: 'webpack',
      webpackConfig: require('./webpack.config.js')
    },
    supportFile: 'cypress/support/component.js',
    specPattern: 'src/**/*.cy.{js,jsx,ts,tsx}'
  }
})
