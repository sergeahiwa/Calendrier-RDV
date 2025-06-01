<?php
/**
 * Règle de validation : date postérieure à une autre
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : date postérieure à une autre
 */
class AfterDateRule extends AbstractValidationRule
{
    /**
     * @var string Champ de comparaison
     */
    private string $field;
    
    /**
     * @var string Format de date
     */
    private string $format;
    
    /**
     * Constructeur
     *
     * @param string $field Champ à comparer
     * @param string $format Format de date (par défaut : Y-m-d H:i:s)
     * @param string|null $message Message d'erreur personnalisé
     * @param array $params Paramètres pour le message d'erreur
     */
    public function __construct(string $field, string $format = 'Y-m-d H:i:s', ?string $message = null, array $params = [])
    {
        parent::__construct($message, $params);
        $this->field = $field;
        $this->format = $format;
        $this->params['field'] = $field;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (empty($value) || empty($data[$this->field])) {
            return true; // La validation passe si un des champs est vide
        }
        
        $date1 = \DateTime::createFromFormat($this->format, $data[$this->field]);
        $date2 = \DateTime::createFromFormat($this->format, $value);
        
        if (!$date1 || !$date2) {
            return false;
        }
        
        return $date2 > $date1;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'La date du champ {field} doit être postérieure à celle du champ {compare_field}.';
    }
}
