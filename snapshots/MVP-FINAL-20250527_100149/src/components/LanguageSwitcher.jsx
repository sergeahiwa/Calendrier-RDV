import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import PropTypes from 'prop-types';

/**
 * Composant pour changer la langue de l'application
 * @param {Object} props - Les propriétés du composant
 * @param {Array} props.languages - Liste des langues disponibles
 * @param {string} props.className - Classes CSS supplémentaires
 * @returns {JSX.Element} Le composant de sélection de langue
 */
const LanguageSwitcher = ({ 
  languages = [
    { code: 'fr', name: 'Français' },
    { code: 'en', name: 'English' },
  ],
  className = ''
}) => {
  const { i18n } = useTranslation();
  const [currentLanguage, setCurrentLanguage] = useState(i18n.language);

  // Mettre à jour la langue sélectionnée lors du chargement
  useEffect(() => {
    const savedLanguage = localStorage.getItem('i18nextLng');
    if (savedLanguage) {
      setCurrentLanguage(savedLanguage);
    }
  }, []);

  // Gérer le changement de langue
  const handleLanguageChange = (event) => {
    const newLanguage = event.target.value;
    i18n.changeLanguage(newLanguage);
    setCurrentLanguage(newLanguage);
    
    // Sauvegarder la préférence
    localStorage.setItem('i18nextLng', newLanguage);
    
    // Déclencher un événement personnalisé pour les autres composants
    window.dispatchEvent(new Event('languageChanged'));
  };

  return (
    <div 
      className={`language-switcher ${className}`}
      data-testid="language-switcher"
    >
      <select
        value={currentLanguage}
        onChange={handleLanguageChange}
        className="language-select"
        data-testid="language-select"
        aria-label="Sélectionner la langue"
      >
        {languages.map((lang) => (
          <option 
            key={lang.code} 
            value={lang.code}
            data-testid={`option-${lang.code}`}
          >
            {lang.name}
          </option>
        ))}
      </select>
    </div>
  );
};

LanguageSwitcher.propTypes = {
  languages: PropTypes.arrayOf(
    PropTypes.shape({
      code: PropTypes.string.isRequired,
      name: PropTypes.string.isRequired,
    })
  ),
  className: PropTypes.string,
};

export default LanguageSwitcher;
