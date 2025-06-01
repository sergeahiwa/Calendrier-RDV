<?php
/**
 * Résultat d'une validation
 *
 * @package CalendrierRdv\Domain\Validation
 */

namespace CalendrierRdv\Domain\Validation;

/**
 * Résultat d'une validation
 */
class ValidationResult
{
    /**
     * @var array Erreurs de validation par champ
     */
    private array $errors = [];
    
    /**
     * @var array Données validées
     */
    private array $validatedData = [];
    
    /**
     * Ajoute une erreur pour un champ
     *
     * @param string $field Champ concerné
     * @param string $message Message d'erreur
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
        return $this;
    }
    
    /**
     * Ajoute des erreurs pour un champ
     *
     * @param string $field Champ concerné
     * @param array $messages Messages d'erreur
     * @return self
     */
    public function addErrors(string $field, array $messages): self
    {
        foreach ($messages as $message) {
            $this->addError($field, $message);
        }
        
        return $this;
    }
    
    /**
     * Vérifie si la validation a réussi
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Vérifie si un champ a des erreurs
     *
     * @param string $field Champ à vérifier
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return !empty($this->errors[$field]);
    }
    
    /**
     * Récupère toutes les erreurs
     *
     * @return array Tableau d'erreurs par champ
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Récupère les erreurs d'un champ
     *
     * @param string $field Champ concerné
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Récupère le premier message d'erreur d'un champ
     *
     * @param string $field Champ concerné
     * @return string|null
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Définit les données validées
     *
     * @param array $data
     * @return self
     */
    public function setValidatedData(array $data): self
    {
        $this->validatedData = $data;
        return $this;
    }
    
    /**
     * Récupère les données validées
     *
     * @return array
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }
    
    /**
     * Fusionne avec un autre résultat de validation
     *
     * @param ValidationResult $other
     * @return self
     */
    public function merge(ValidationResult $other): self
    {
        $this->errors = array_merge_recursive($this->errors, $other->getErrors());
        $this->validatedData = array_merge($this->validatedData, $other->getValidatedData());
        return $this;
    }
}
