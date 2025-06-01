<?php
/**
 * Service pour l'intégration Apple Pay et Google Pay (MVP : structure, hooks)
 */
namespace CalendrierRdv\Infrastructure\Payment;

class AppleGooglePayService
{
    /**
     * Simule un paiement Apple Pay / Google Pay (MVP)
     * @param array $paymentData
     * @return bool
     */
    public function processPayment(array $paymentData): bool
    {
        // Ici, intégrer l'API réelle selon l'environnement de prod
        // Pour MVP : toujours succès
        return true;
    }
}
