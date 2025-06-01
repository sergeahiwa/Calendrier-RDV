<?php

namespace CalendrierRdv\Tests\Functional;

use CalendrierRdv\Tests\Functional\TestCase;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class AdminDashboardTest extends TestCase
{
    private $adminUsername = 'admin';
    private $adminPassword = 'password';
    
    public function testAdminLogin()
    {
        $this->loginAsAdmin();
        $this->assertStringContainsString('Tableau de bord', $this->webDriver->getTitle());
    }
    
    public function testCalendarView()
    {
        $this->loginAsAdmin();
        
        // Naviguer vers le tableau de bord des rendez-vous
        $this->webDriver->get($this->baseUrl . '/wp-admin/admin.php?page=calendrier-rdv');
        
        // Attendre que le calendrier soit chargé
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('fc-view-container')
            )
        );
        
        // Vérifier que le calendrier est affiché
        $calendar = $this->webDriver->findElement(WebDriverBy::className('fc-view-container'));
        $this->assertTrue($calendar->isDisplayed());
    }
    
    public function testAppointmentModal()
    {
        $this->loginAsAdmin();
        $this->webDriver->get($this->baseUrl . '/wp-admin/admin.php?page=calendrier-rdv');
        
        // Attendre que le calendrier soit chargé
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('fc-event')
            )
        );
        
        // Cliquer sur un événement
        $event = $this->webDriver->findElement(WebDriverBy::className('fc-event'));
        $event->click();
        
        // Attendre que la modale s'affiche
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::id('appointment-details-modal')
            )
        );
        
        // Vérifier que la modale contient les informations du rendez-vous
        $modal = $this->webDriver->findElement(WebDriverBy::id('appointment-details-modal'));
        $this->assertTrue($modal->isDisplayed());
        
        $clientName = $this->webDriver->findElement(WebDriverBy::id('appointment-client-name'));
        $this->assertNotEmpty($clientName->getText());
        
        $serviceName = $this->webDriver->findElement(WebDriverBy::id('appointment-service'));
        $this->assertNotEmpty($serviceName->getText());
    }
    
    public function testAppointmentStatusChange()
    {
        $this->loginAsAdmin();
        $this->webDriver->get($this->baseUrl . '/wp-admin/admin.php?page=calendrier-rdv');
        
        // Ouvrir la modale de détails du rendez-vous
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::className('fc-event')
            )
        )->click();
        
        // Attendre que la modale s'affiche
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::id('appointment-status')
            )
        );
        
        // Changer le statut
        $statusSelect = $this->webDriver->findElement(WebDriverBy::id('appointment-status'));
        $statusSelect->click();
        
        // Sélectionner un nouveau statut
        $newStatus = 'confirmed';
        $option = $this->webDriver->findElement(
            WebDriverBy::xpath("//select[@id='appointment-status']/option[contains(text(), 'Confirmé')]")
        );
        $option->click();
        
        // Cliquer sur le bouton de sauvegarde
        $saveButton = $this->webDriver->findElement(
            WebDriverBy::xpath("//button[contains(@class, 'save-appointment-btn')]")
        );
        $saveButton->click();
        
        // Vérifier le message de succès
        $this->webDriver->wait()->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(
                WebDriverBy::className('notice-success')
            )
        );
        
        $successMessage = $this->webDriver->findElement(WebDriverBy::className('notice-success'));
        $this->assertStringContainsString('Statut mis à jour avec succès', $successMessage->getText());
    }
    
    protected function loginAsAdmin()
    {
        $this->webDriver->get($this->baseUrl . '/wp-login.php');
        $this->webDriver->findElement(WebDriverBy::id('user_login'))->sendKeys($this->adminUsername);
        $this->webDriver->findElement(WebDriverBy::id('user_pass'))->sendKeys($this->adminPassword);
        $this->webDriver->findElement(WebDriverBy::id('wp-submit'))->click();
    }
}
