<?php
/**
 * Gestion des paiements pour le module Calendrier RDV
 */
class Calendrier_RDV_Payment_Handler {
    private $stripe_public_key;
    private $stripe_secret_key;
    private $currency;
    private $test_mode;

    public function __construct() {
        $options = get_option('calendrier_rdv_payment_settings', []);
        
        $this->test_mode = isset($options['test_mode']) ? (bool) $options['test_mode'] : true;
        
        if ($this->test_mode) {
            $this->stripe_public_key = isset($options['test_public_key']) ? $options['test_public_key'] : '';
            $this->stripe_secret_key = isset($options['test_secret_key']) ? $options['test_secret_key'] : '';
        } else {
            $this->stripe_public_key = isset($options['live_public_key']) ? $options['live_public_key'] : '';
            $this->stripe_secret_key = isset($options['live_secret_key']) ? $options['live_secret_key'] : '';
        }
        
        $this->currency = isset($options['currency']) ? $options['currency'] : 'eur';
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_calendrier_rdv_create_payment_intent', [$this, 'create_payment_intent']);
        add_action('wp_ajax_nopriv_calendrier_rdv_create_payment_intent', [$this, 'create_payment_intent']);
        
        add_action('wp_ajax_calendrier_rdv_confirm_payment', [$this, 'confirm_payment']);
        add_action('wp_ajax_nopriv_calendrier_rdv_confirm_payment', [$this, 'confirm_payment']);
    }
    
    /**
     * Crée une intention de paiement Stripe
     */
    public function create_payment_intent() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');
        
        try {
            // Récupérer les données de la requête
            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) * 100 : 0; // Convertir en centimes
            
            if (!$booking_id || $amount <= 0) {
                throw new Exception(__('Données de paiement invalides', 'calendrier-rdv'));
            }
            
            // Initialiser l'API Stripe
            \Stripe\Stripe::setApiKey($this->stripe_secret_key);
            
            // Créer une intention de paiement
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => strtolower($this->currency),
                'metadata' => [
                    'booking_id' => $booking_id,
                    'source' => 'calendrier-rdv',
                ],
                'payment_method_types' => ['card'],
            ]);
            
            wp_send_json_success([
                'client_secret' => $intent->client_secret,
                'publishable_key' => $this->stripe_public_key,
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Confirme un paiement réussi
     */
    public function confirm_payment() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');
        
        try {
            $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
            $payment_intent_id = isset($_POST['payment_intent_id']) ? sanitize_text_field($_POST['payment_intent_id']) : '';
            
            if (!$booking_id || !$payment_intent_id) {
                throw new Exception(__('Données de confirmation de paiement invalides', 'calendrier-rdv'));
            }
            
            // Vérifier l'intention de paiement
            \Stripe\Stripe::setApiKey($this->stripe_secret_key);
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            if ($intent->status !== 'succeeded') {
                throw new Exception(__('Le paiement n\'a pas abouti', 'calendrier-rdv'));
            }
            
            // Mettre à jour la réservation
            $this->update_booking_payment($booking_id, $intent->id, $intent->amount_received);
            
            // Envoyer une confirmation de paiement
            $this->send_payment_confirmation($booking_id);
            
            wp_send_json_success([
                'message' => __('Paiement confirmé avec succès', 'calendrier-rdv'),
                'receipt_url' => $intent->charges->data[0]->receipt_url ?? '',
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Met à jour le statut de paiement d'une réservation
     */
    private function update_booking_payment($booking_id, $transaction_id, $amount) {
        // Mettre à jour la réservation dans la base de données
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'rdv_booking',
            [
                'payment_status' => 'paid',
                'transaction_id' => $transaction_id,
                'amount_paid' => $amount / 100, // Convertir en unité monétaire standard
                'payment_date' => current_time('mysql'),
            ],
            ['id' => $booking_id],
            ['%s', '%s', '%f', '%s'],
            ['%d']
        );
        
        // Déclencher une action pour les extensions
        do_action('calendrier_rdv_booking_paid', $booking_id, $transaction_id, $amount);
    }
    
    /**
     * Envoie une confirmation de paiement par email
     */
    private function send_payment_confirmation($booking_id) {
        // Récupérer les détails de la réservation
        $booking = $this->get_booking_details($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $to = $booking->client_email;
        $subject = sprintf(__('Confirmation de paiement pour votre rendez-vous du %s', 'calendrier-rdv'), 
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->date_rdv . ' ' . $booking->heure_debut))
        );
        
        $message = $this->get_email_template('payment_confirmation', [
            'booking' => $booking,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Récupère les détails d'une réservation
     */
    private function get_booking_details($booking_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rdv_booking WHERE id = %d",
            $booking_id
        ));
    }
    
    /**
     * Charge un modèle d'email
     */
    private function get_email_template($template_name, $args = []) {
        $template_path = CALENDRIER_RDV_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return '';
        }
        
        ob_start();
        extract($args);
        include $template_path;
        return ob_get_clean();
    }
}

// Initialiser le gestionnaire de paiement
function calendrier_rdv_init_payment_handler() {
    if (class_exists('Stripe\\Stripe')) {
        new Calendrier_RDV_Payment_Handler();
    }
}
add_action('plugins_loaded', 'calendrier_rdv_init_payment_handler');
