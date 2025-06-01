<?php
/**
 * Règle de validation : longueur maximale
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : longueur maximale
 */
class MaxLengthRule extends AbstractValidationRule
{
    /**
     * @var int Longueur maximale autorisée
     */
    private int $maxLength;
    
    /**
     * Constructeur
     *
     * @param int $maxLength Longueur maximale autorisée
     * @param string|null $message Message d'erreur personnalisé
     * @param array $params Paramètres pour le message d'erreur
     */
    public function __construct(int $maxLength, ?string $message = null, array $params = [])
    {
        parent::__construct($message, $params);
        $this->maxLength = $maxLength;
        $this->params['max'] = $maxLength;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (empty($value) && $value !== '0') {
            return true; // La validation passe si le champ est vide
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        
        return mb_strlen((string) $value) <= $this->maxLength;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'Le champ {field} ne doit pas dépasser {max} caractères.';
    }
}
