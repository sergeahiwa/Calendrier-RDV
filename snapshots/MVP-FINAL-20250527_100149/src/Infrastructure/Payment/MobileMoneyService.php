<?php
/**
 * Service pour l'intégration Mobile Money Afrique (MVP : structure, hooks)
 */
namespace CalendrierRdv\Infrastructure\Payment;

class MobileMoneyService
{
    /**
     * Simule un paiement Mobile Money (MVP)
     * @param array $paymentData
     * @return bool
     */
    public function processPayment(array $paymentData): bool
    {
        // Ici, intégrer l'API réelle selon l'opérateur (Orange, MTN, etc.)
        // Pour MVP : toujours succès
        return true;
    }
}
