// ================================
// Fichier : public/script.js
// Rôle   : Validation du formulaire et feedback utilisateur
// Auteur : SAN Digital Solutions
// ================================

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-rdv');
    const messageBox = document.getElementById('form-message');
  
    form.addEventListener('submit', function(e) {
      // Reset des erreurs
      const errorMessages = form.querySelectorAll('.error-message');
      errorMessages.forEach(msg => msg.remove());
      const errorFields = form.querySelectorAll('.error');
      errorFields.forEach(field => field.classList.remove('error'));
      messageBox.textContent = '';
  
      let valid = true;
  
      // Liste des champs requis
      const requiredFields = ['nom', 'email', 'prestation', 'prestataire', 'date_rdv', 'heure_rdv'];
  
      requiredFields.forEach(name => {
        const field = form.elements[name];
        if (!field.value.trim()) {
          valid = false;
          field.classList.add('error');
          const err = document.createElement('div');
          err.className = 'error-message';
          err.textContent = 'Ce champ est requis';
          field.parentNode.insertBefore(err, field.nextSibling);
        }
      });
  
      // Vérification basique de l'email
      const emailField = form.elements['email'];
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (emailField.value && !emailPattern.test(emailField.value)) {
        valid = false;
        emailField.classList.add('error');
        const err = document.createElement('div');
        err.className = 'error-message';
        err.textContent = 'Adresse email invalide';
        emailField.parentNode.insertBefore(err, emailField.nextSibling);
      }
  
      if (!valid) {
        e.preventDefault();
        messageBox.textContent = 'Merci de corriger les erreurs indiquées.';
        messageBox.style.color = '#e74c3c';
      } else {
        // Feedback d'envoi
        messageBox.textContent = 'Envoi en cours…';
        messageBox.style.color = '#346fb3';
        // Désactive le bouton pour éviter les doubles clics
        form.querySelector('button[type="submit"]').disabled = true;
      }
    });
  });
  
