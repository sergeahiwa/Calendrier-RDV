<?php
/**
 * Règle de validation : date valide
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : date valide
 */
class DateRule extends AbstractValidationRule
{
    /**
     * @var string Format de date attendu
     */
    private string $format;
    
    /**
     * Constructeur
     *
     * @param string $format Format de date attendu (par défaut : Y-m-d H:i:s)
     * @param string|null $message Message d'erreur personnalisé
     * @param array $params Paramètres pour le message d'erreur
     */
    public function __construct(string $format = 'Y-m-d H:i:s', ?string $message = null, array $params = [])
    {
        parent::__construct($message, $params);
        $this->format = $format;
        $this->params['format'] = $format;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (empty($value) && $value !== '0') {
            return true; // La validation passe si le champ est vide (utiliser RequiredRule pour rendre obligatoire)
        }
        
        if (!is_string($value)) {
            return false;
        }
        
        $date = \DateTime::createFromFormat($this->format, $value);
        return $date && $date->format($this->format) === $value;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'Le champ {field} doit être une date valide au format {format}.';
    }
}
