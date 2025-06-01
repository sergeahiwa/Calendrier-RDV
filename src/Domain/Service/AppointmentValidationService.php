<?php
/**
 * Service de validation des rendez-vous
 *
 * @package CalendrierRdv\Domain\Service
 */

namespace CalendrierRdv\Domain\Service;

use CalendrierRdv\Domain\Model\Appointment;
use CalendrierRdv\Domain\Repository\AppointmentRepositoryInterface;
use CalendrierRdv\Domain\Repository\ProviderRepositoryInterface;
use CalendrierRdv\Domain\Repository\ServiceRepositoryInterface;
use CalendrierRdv\Domain\Validation\ValidationRuleFactory;
use CalendrierRdv\Domain\Validation\Validator;
use CalendrierRdv\Domain\Validation\ValidatorInterface;

/**
 * Service de validation des rendez-vous
 */
class AppointmentValidationService
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;
    
    /**
     * @var ServiceRepositoryInterface
     */
    private ServiceRepositoryInterface $serviceRepository;
    
    /**
     * @var ProviderRepositoryInterface
     */
    private ProviderRepositoryInterface $providerRepository;
    
    /**
     * @var AppointmentRepositoryInterface
     */
    private AppointmentRepositoryInterface $appointmentRepository;
    
    /**
     * Constructeur
     *
     * @param ServiceRepositoryInterface $serviceRepository
     * @param ProviderRepositoryInterface $providerRepository
     * @param AppointmentRepositoryInterface $appointmentRepository
     * @param ValidatorInterface|null $validator
     */
    public function __construct(
        ServiceRepositoryInterface $serviceRepository,
        ProviderRepositoryInterface $providerRepository,
        AppointmentRepositoryInterface $appointmentRepository,
        ?ValidatorInterface $validator = null
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->providerRepository = $providerRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->validator = $validator ?? $this->createDefaultValidator();
    }
    
    /**
     * Valide les données d'un rendez-vous
     *
     * @param array $data Données à valider
     * @return array [bool $isValid, array $errors, array $validatedData]
     */
    public function validateAppointmentData(array $data): array
    {
        $result = $this->validator->validate($data);
        
        // Validation personnalisée supplémentaire
        if ($result->isValid()) {
            $this->validateServiceAndProvider($data, $result);
            $this->validateAppointmentTime($data, $result);
        }
        
        return [
            $result->isValid(),
            $result->getErrors(),
            $result->getValidatedData()
        ];
    }
    
    /**
     * Valide un objet rendez-vous
     *
     * @param Appointment $appointment
     * @return array [bool $isValid, array $errors]
     */
    public function validateAppointment(Appointment $appointment): array
    {
        $data = [
            'service_id' => $appointment->getServiceId(),
            'provider_id' => $appointment->getProviderId(),
            'customer_name' => $appointment->getCustomerName(),
            'customer_email' => $appointment->getCustomerEmail(),
            'customer_phone' => $appointment->getCustomerPhone(),
            'start_date' => $appointment->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $appointment->getEndDate()->format('Y-m-d H:i:s'),
            'notes' => $appointment->getNotes(),
        ];
        
        list($isValid, $errors) = $this->validateAppointmentData($data);
        
        // Vérification des conflits de rendez-vous
        if ($isValid && $this->hasAppointmentConflict($appointment)) {
            $errors['appointment'] = ['Un rendez-vous existe déjà pour ce créneau.'];
            $isValid = false;
        }
        
        return [$isValid, $errors];
    }
    
    /**
     * Vérifie s'il y a un conflit de rendez-vous
     *
     * @param Appointment $appointment
     * @return bool
     */
    private function hasAppointmentConflict(Appointment $appointment): bool
    {
        $conflicts = $this->appointmentRepository->findConflicts(
            $appointment->getProviderId(),
            $appointment->getStartDate(),
            $appointment->getEndDate(),
            $appointment->getId() // Exclure le rendez-vous actuel des conflits
        );
        
        return !empty($conflicts);
    }
    
    /**
     * Valide le service et le prestataire
     *
     * @param array $data
     * @param \CalendrierRdv\Domain\Validation\ValidationResult $result
     * @return void
     */
    private function validateServiceAndProvider(array $data, $result): void
    {
        // Vérifier que le service existe et est actif
        $service = $this->serviceRepository->findById($data['service_id'] ?? 0);
        if (!$service || !$service->isActive()) {
            $result->addError('service_id', 'Le service sélectionné n\'est pas disponible.');
        }
        
        // Vérifier que le prestataire existe et est actif
        $provider = $this->providerRepository->findById($data['provider_id'] ?? 0);
        if (!$provider || !$provider->isActive()) {
            $result->addError('provider_id', 'Le prestataire sélectionné n\'est pas disponible.');
        }
        
        // Vérifier que le prestataire propose bien ce service
        if ($service && $provider && !$this->providerRepository->providesService($provider->getId(), $service->getId())) {
            $result->addError('service_id', 'Le prestataire sélectionné ne propose pas ce service.');
        }
    }
    
    /**
     * Valide les horaires du rendez-vous
     *
     * @param array $data
     * @param \CalendrierRdv\Domain\Validation\ValidationResult $result
     * @return void
     */
    private function validateAppointmentTime(array $data, $result): void
    {
        if (empty($data['start_date']) || empty($data['end_date'])) {
            return;
        }
        
        try {
            $startDate = new \DateTimeImmutable($data['start_date']);
            $endDate = new \DateTimeImmutable($data['end_date']);
            
            // Vérifier que la date de fin est postérieure à la date de début
            if ($endDate <= $startDate) {
                $result->addError('end_date', 'La date de fin doit être postérieure à la date de début.');
            }
            
            // Vérifier que le rendez-vous est pendant les heures d'ouverture
            $startTime = (int) $startDate->format('H');
            $endTime = (int) $endDate->format('H');
            
            if ($startTime < 8 || $endTime > 20) {
                $result->addError('start_date', 'Les rendez-vous sont possibles entre 8h et 20h.');
            }
            
            // Vérifier que le rendez-vous est en semaine
            $dayOfWeek = (int) $startDate->format('N'); // 1 (lundi) à 7 (dimanche)
            if ($dayOfWeek >= 6) { // Samedi ou dimanche
                $result->addError('start_date', 'Les rendez-vous ne sont pas disponibles le week-end.');
            }
            
        } catch (\Exception $e) {
            $result->addError('start_date', 'Format de date invalide.');
        }
    }
    
    /**
     * Crée un validateur avec les règles par défaut
     *
     * @return ValidatorInterface
     */
    private function createDefaultValidator(): ValidatorInterface
    {
        $factory = new ValidationRuleFactory();
        $validator = new Validator($factory);
        
        // Définition des règles de validation
        $validator->addRules('service_id', [
            'required' => [],
            'numeric' => [],
        ]);
        
        $validator->addRules('provider_id', [
            'required' => [],
            'numeric' => [],
        ]);
        
        $validator->addRules('customer_name', [
            'required' => [],
            'min' => [2, 'Le nom doit contenir au moins 2 caractères.'],
            'max' => [100, 'Le nom ne peut pas dépasser 100 caractères.'],
        ]);
        
        $validator->addRules('customer_email', [
            'required' => [],
            'email' => [],
            'max' => [255, 'L\'adresse email ne peut pas dépasser 255 caractères.'],
        ]);
        
        $validator->addRules('customer_phone', [
            'required' => [],
            'min' => [10, 'Le numéro de téléphone doit contenir au moins 10 chiffres.'],
            'max' => [20, 'Le numéro de téléphone ne peut pas dépasser 20 caractères.'],
        ]);
        
        $validator->addRules('start_date', [
            'required' => [],
            'date' => ['Y-m-d H:i:s'],
        ]);
        
        $validator->addRules('end_date', [
            'required' => [],
            'date' => ['Y-m-d H:i:s'],
            'after' => ['start_date', 'Y-m-d H:i:s', 'La date de fin doit être postérieure à la date de début.'],
        ]);
        
        $validator->addRules('notes', [
            'max' => [1000, 'Les notes ne peuvent pas dépasser 1000 caractères.'],
        ]);
        
        return $validator;
    }
}
