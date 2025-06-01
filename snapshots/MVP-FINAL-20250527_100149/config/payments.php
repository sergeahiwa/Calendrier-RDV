<?php
/**
 * Configuration des paiements pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

return [
    // Paramètres généraux des paiements
    'general' => [
        'currency' => 'EUR',
        'currency_position' => 'right', // left, right, left_space, right_space
        'thousand_separator' => ' ',
        'decimal_separator' => ',',
        'number_of_decimals' => 2,
        'price_display_suffix' => '', // Ex: 'TTC' ou 'HT'
        'enable_taxes' => false,
        'tax_rate' => 20, // Taux de TVA par défaut
        'tax_inclusive' => true, // Les prix sont-ils TTC ?
        'enable_test_mode' => true, // Mode test activé par défaut
        'test_mode_notice' => __('Mode test activé. Les paiements ne seront pas réellement traités.', 'calendrier-rdv'),
    ],
    
    // Paramètres des méthodes de paiement
    'payment_methods' => [
        'enabled' => [
            'stripe',
            'paypal',
            'bank_transfer',
            'check_payments',
            'cash',
        ],
        'default' => 'stripe',
    ],
    
    // Paramètres Stripe
    'stripe' => [
        'enabled' => true,
        'title' => __('Carte de crédit (Stripe)', 'calendrier-rdv'),
        'description' => __('Paiement sécurisé par carte bancaire', 'calendrier-rdv'),
        'publishable_key' => '',
        'secret_key' => '',
        'webhook_secret' => '',
        'statement_descriptor' => 'SERVICES', // Max 22 caractères
        'capture' => true, // Paiement capturé immédiatement (true) ou autorisation seule (false)
        'payment_request' => true, // Activer Apple Pay / Google Pay
        'payment_request_button_theme' => 'dark', // dark, light, light-outline
        'payment_request_button_type' => 'buy', // default, book, buy, donate
        'payment_request_button_height' => '40', // en px
        'saved_cards' => true, // Permettre d'enregistrer les cartes pour les paiements futurs
        'checkout_locale' => 'auto', // auto, fr, en, etc.
        'debug' => false,
    ],
    
    // Paramètres PayPal
    'paypal' => [
        'enabled' => true,
        'title' => __('PayPal', 'calendrier-rdv'),
        'description' => __('Paiement sécurisé via PayPal', 'calendrier-rdv'),
        'email' => '', // Email du compte PayPal professionnel
        'client_id' => '',
        'client_secret' => '',
        'testmode' => true,
        'debug' => false,
        'identity_token' => '', // Pour le paiement IPN
        'invoice_prefix' => 'RDV-', // Préfixe des factures
        'payment_action' => 'sale', // sale, authorize, order
        'page_style' => '', // Nom de la page de paiement personnalisée
        'image_url' => '', // URL de l'image à afficher sur la page de paiement
        'api_details' => [
            'sandbox' => [
                'api_username' => '',
                'api_password' => '',
                'api_signature' => '',
            ],
            'live' => [
                'api_username' => '',
                'api_password' => '',
                'api_signature' => '',
            ],
        ],
    ],
    
    // Paramètres virement bancaire
    'bank_transfer' => [
        'enabled' => true,
        'title' => __('Virement bancaire', 'calendrier-rdv'),
        'description' => __('Effectuez un virement bancaire directement sur notre compte. Les détails du compte vous seront fournis après la commande.', 'calendrier-rdv'),
        'instructions' => __('Veuillez effectuer le virement sur le compte bancaire indiqué ci-dessous. Utilisez le numéro de commande comme référence de paiement. Votre commande sera traitée dès réception du paiement.', 'calendrier-rdv'),
        'account_details' => [
            'account_name' => '',
            'account_number' => '',
            'bank_name' => '',
            'sort_code' => '',
            'iban' => '',
            'bic' => '',
        ],
    ],
    
    // Paramètres chèque
    'check_payments' => [
        'enabled' => true,
        'title' => __('Chèque', 'calendrier-rdv'),
        'description' => __('Envoyez un chèque à l\'adresse suivante :', 'calendrier-rdv'),
        'instructions' => __('Veuillez libeller votre chèque à l\'ordre de [nom du bénéficiaire]. Envoyez votre chèque à l\'adresse ci-dessous. Votre commande sera traitée dès réception du paiement.', 'calendrier-rdv'),
        'payable_to' => '',
        'mailing_address' => [
            'name' => '',
            'company' => '',
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'postcode' => '',
            'country' => 'France',
        ],
    ],
    
    // Paramètres paiement en espèces
    'cash' => [
        'enabled' => true,
        'title' => __('Paiement sur place', 'calendrier-rdv'),
        'description' => __('Paiement en espèces ou carte bancaire directement sur place', 'calendrier-rdv'),
        'instructions' => __('Aucun paiement n\'est requis pour le moment. Vous réglerez directement sur place.', 'calendrier-rdv'),
    ],
    
    // Paramètres des acomptes
    'deposits' => [
        'enabled' => true,
        'type' => 'percentage', // percentage, fixed
        'amount' => 50, // Montant ou pourcentage de l'acompte
        'enable_partial_payments' => true, // Permettre les paiements partiels
        'min_deposit' => 10, // Montant minimum de l'acompte (en pourcentage ou montant fixe)
        'max_deposit' => 100, // Montant maximum de l'acompte (en pourcentage ou montant fixe)
        'deposit_due_after' => 0, // Jours avant que l'acompte ne soit dû (0 = immédiatement)
        'final_payment_due_before' => 7, // Jours avant le rendez-vous pour le paiement final
        'enable_reminders' => true, // Activer les rappels de paiement
        'reminder_days_before' => [3, 1], // Jours avant l'échéance pour envoyer des rappels
    ],
    
    // Paramètres des remboursements
    'refunds' => [
        'enabled' => true,
        'refund_days' => 30, // Nombre de jours pour demander un remboursement
        'refund_fee' => 0, // Frais de remboursement (montant fixe ou pourcentage)
        'refund_fee_type' => 'fixed', // fixed, percentage
        'cancel_refund_days' => 2, // Nombre de jours pour annuler et être remboursé à 100%
        'cancel_refund_percentage' => 100, // Pourcentage remboursé en cas d'annulation avant le délai
        'late_cancel_refund_percentage' => 50, // Pourcentage remboursé en cas d'annulation après le délai
        'no_show_refund_percentage' => 0, // Pourcentage remboursé en cas de non-présentation
        'refund_reasons' => [ // Raisons de remboursement prédéfinies
            'changed_mind' => __('Changement d\'avis', 'calendrier-rdv'),
            'found_cheaper' => __('J\'ai trouvé moins cher ailleurs', 'calendrier-rdv'),
            'not_as_described' => __('Le service ne correspond pas à la description', 'calendrier-rdv'),
            'other' => __('Autre raison', 'calendrier-rdv'),
        ],
    ],
    
    // Paramètres des factures
    'invoicing' => [
        'enabled' => true,
        'company_details' => [
            'name' => get_bloginfo('name'),
            'address' => '',
            'city' => '',
            'postcode' => '',
            'country' => 'France',
            'vat_number' => '',
            'company_number' => '',
            'logo' => '', // URL du logo
        ],
        'invoice_prefix' => 'FAC-',
        'next_invoice_number' => 1000,
        'invoice_due_days' => 30,
        'invoice_footer' => sprintf(
            __('Merci pour votre confiance. Pour tout renseignement, veuillez contacter %s.', 'calendrier-rdv'),
            get_bloginfo('admin_email')
        ),
        'enable_tax' => false,
        'tax_display' => 'itemized', // itemized, single
        'enable_discounts' => true,
        'enable_credit_notes' => true,
    ],
    
    // Paramètres des reçus
    'receipts' => [
        'enabled' => true,
        'send_email' => true,
        'email_subject' => __('Votre reçu pour la réservation #{booking_id}', 'calendrier-rdv'),
        'email_heading' => __('Voici votre reçu', 'calendrier-rdv'),
        'email_type' => 'html', // html, plain
        'show_tax' => false,
        'show_tax_number' => false,
        'show_business_info' => true,
        'show_customer_info' => true,
        'show_booking_details' => true,
        'show_payment_info' => true,
        'show_footer' => true,
        'footer_text' => sprintf(
            __('Merci pour votre confiance. Pour toute question, contactez-nous à %s.', 'calendrier-rdv'),
            get_bloginfo('admin_email')
        ),
    ],
];
