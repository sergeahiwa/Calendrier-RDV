<?php
/**
 * Classe de base pour les règles de validation
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Classe de base pour les règles de validation
 */
abstract class AbstractValidationRule implements ValidationRuleInterface
{
    /**
     * @var string Message d'erreur par défaut
     */
    protected string $message;
    
    /**
     * @var array Paramètres pour le message d'erreur
     */
    protected array $params = [];
    
    /**
     * Constructeur
     *
     * @param string $message Message d'erreur personnalisé (optionnel)
     * @param array $params Paramètres pour le message d'erreur
     */
    public function __construct(string $message = null, array $params = [])
    {
        if ($message !== null) {
            $this->message = $message;
        }
        
        $this->params = $params;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getErrorMessage(): string
    {
        if (empty($this->message)) {
            return $this->getDefaultMessage();
        }
        
        return $this->replacePlaceholders($this->message, $this->params);
    }
    
    /**
     * Retourne le message d'erreur par défaut
     *
     * @return string
     */
    abstract protected function getDefaultMessage(): string;
    
    /**
     * Remplace les placeholders dans un message
     *
     * @param string $message
     * @param array $params
     * @return string
     */
    protected function replacePlaceholders(string $message, array $params = []): string
    {
        if (empty($params)) {
            return $message;
        }
        
        $replace = [];
        foreach ($params as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }
        
        return strtr($message, $replace);
    }
}
