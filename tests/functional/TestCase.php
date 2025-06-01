<?php

namespace CalendrierRdv\Tests\Functional;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var RemoteWebDriver
     */
    protected $webDriver;
    
    /**
     * URL de base du site WordPress
     * @var string
     */
    protected $baseUrl = 'http://localhost:8000';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration des options de Chrome
        $options = new ChromeOptions();
        $options->addArguments([
            '--headless',
            '--disable-gpu',
            '--no-sandbox',
            '--window-size=1920,1080',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-software-rasterizer',
            '--disable-infobars',
            '--disable-browser-side-navigation',
            '--disable-features=VizDisplayCompositor',
        ]);
        
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        
        // Désactiver les logs Selenium
        $capabilities->setCapability('loggingPrefs', [
            'browser' => 'SEVERE',
            'performance' => 'ALL'
        ]);
        
        // Démarrer le navigateur
        $this->webDriver = RemoteWebDriver::create(
            'http://selenium-hub:4444/wd/hub',
            $capabilities,
            60000,
            60000
        );
        
        // Définir la taille de la fenêtre
        $this->webDriver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));
        
        // Configurer le temps d'attente implicite
        $this->webDriver->manage()->timeouts()->implicitlyWait(10);
        $this->webDriver->manage()->timeouts()->pageLoadTimeout(30);
        $this->webDriver->manage()->timeouts()->setScriptTimeout(30);
    }
    
    protected function tearDown(): void
    {
        if ($this->webDriver) {
            $this->webDriver->quit();
        }
        parent::tearDown();
    }
    
    /**
     * Attendre qu'un élément soit cliquable
     */
    protected function waitForElementToBeClickable($by, $timeout = 10)
    {
        $this->webDriver->wait($timeout)->until(
            \Facebook\WebDriver\WebDriverExpectedCondition::elementToBeClickable($by)
        );
    }
    
    /**
     * Attendre qu'un élément soit visible
     */
    protected function waitForElementToBeVisible($by, $timeout = 10)
    {
        $this->webDriver->wait($timeout)->until(
            \Facebook\WebDriver\WebDriverExpectedCondition::visibilityOfElementLocated($by)
        );
    }
    
    /**
     * Attendre qu'un élément soit présent dans le DOM
     */
    protected function waitForElementPresence($by, $timeout = 10)
    {
        $this->webDriver->wait($timeout)->until(
            \Facebook\WebDriver\WebDriverExpectedCondition::presenceOfElementLocated($by)
        );
    }
    
    /**
     * Vérifier qu'un élément contient le texte attendu
     */
    protected function assertElementContainsText($by, $expectedText, $message = '')
    {
        $element = $this->webDriver->findElement($by);
        $this->assertStringContainsString($expectedText, $element->getText(), $message);
    }
}
