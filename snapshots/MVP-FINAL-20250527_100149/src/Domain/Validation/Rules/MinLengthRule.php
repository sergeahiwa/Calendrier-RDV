<?php
/**
 * Règle de validation : longueur minimale
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : longueur minimale
 */
class MinLengthRule extends AbstractValidationRule
{
    /**
     * @var int Longueur minimale requise
     */
    private int $minLength;
    
    /**
     * Constructeur
     *
     * @param int $minLength Longueur minimale requise
     * @param string|null $message Message d'erreur personnalisé
     * @param array $params Paramètres pour le message d'erreur
     */
    public function __construct(int $minLength, ?string $message = null, array $params = [])
    {
        parent::__construct($message, $params);
        $this->minLength = $minLength;
        $this->params['min'] = $minLength;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (empty($value) && $value !== '0') {
            return true; // La validation passe si le champ est vide (utiliser RequiredRule pour rendre obligatoire)
        }
        
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        
        return mb_strlen((string) $value) >= $this->minLength;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'Le champ {field} doit contenir au moins {min} caractères.';
    }
}
