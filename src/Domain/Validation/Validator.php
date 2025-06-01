<?php
/**
 * Validateur de données
 *
 * @package CalendrierRdv\Domain\Validation
 */

namespace CalendrierRdv\Domain\Validation;

use CalendrierRdv\Domain\Validation\Rules\ValidationRuleInterface;

/**
 * Validateur de données
 */
class Validator implements ValidatorInterface
{
    /**
     * @var array Règles de validation par champ
     */
    private array $rules = [];
    
    /**
     * @var array Données à valider
     */
    private array $data = [];
    
    /**
     * @var ValidationRuleFactory Fabrique de règles
     */
    private ValidationRuleFactory $ruleFactory;
    
    /**
     * Constructeur
     *
     * @param ValidationRuleFactory|null $ruleFactory Fabrique de règles (optionnel)
     */
    public function __construct(?ValidationRuleFactory $ruleFactory = null)
    {
        $this->ruleFactory = $ruleFactory ?? new ValidationRuleFactory();
    }
    
    /**
     * {@inheritdoc}
     */
    public function addRules(string $field, array $rules): self
    {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        foreach ($rules as $rule => $params) {
            if (is_numeric($rule)) {
                $rule = $params;
                $params = [];
            } elseif (!is_array($params)) {
                $params = [$params];
            }
            
            $this->rules[$field][] = $this->ruleFactory->create($rule, ...(array) $params);
        }
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate(array $data): ValidationResult
    {
        $this->data = $data;
        $result = new ValidationResult();
        
        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            
            foreach ($rules as $rule) {
                if (!$rule->validate($value, $data)) {
                    $message = $this->processErrorMessage($rule->getErrorMessage(), $field);
                    $result->addError($field, $message);
                }
            }
        }
        
        if ($result->isValid()) {
            $result->setValidatedData($this->filterValidatedData($data));
        }
        
        return $result;
    }
    
    /**
     * Traite le message d'erreur en remplaçant les placeholders
     *
     * @param string $message Message d'erreur avec placeholders
     * @param string $field Nom du champ
     * @return string Message traité
     */
    private function processErrorMessage(string $message, string $field): string
    {
        $replace = [
            '{field}' => $this->getFieldLabel($field),
            '{value}' => $this->data[$field] ?? '',
        ];
        
        // Ajouter les paramètres de la règle
        foreach ($this->data as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }
        
        return strtr($message, $replace);
    }
    
    /**
     * Filtre les données pour ne garder que les champs validés
     *
     * @param array $data Données à filtrer
     * @return array Données filtrées
     */
    private function filterValidatedData(array $data): array
    {
        $validatedData = [];
        
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $data)) {
                $validatedData[$field] = $data[$field];
            }
        }
        
        return $validatedData;
    }
    
    /**
     * Retourne le libellé d'un champ
     *
     * @param string $field Nom du champ
     * @return string Libellé du champ
     */
    private function getFieldLabel(string $field): string
    {
        // Par défaut, on met la première lettre en majuscule et on remplace les _ par des espaces
        return ucfirst(str_replace('_', ' ', $field));
    }
}
