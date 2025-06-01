<?php
// ================================
// Fichier : public/formulaire.php
// Rôle   : Formulaire de prise de rendez-vous avec prestataires dynamiques
// Auteur : SAN Digital Solutions
// ================================

// Chargement de la config PDO
require_once __DIR__ . '/../includes/config.php';

// Récupération des prestataires depuis la BDD
$stmt = $pdo->prepare("SELECT id, nom FROM prestataires ORDER BY nom");
$stmt->execute();
$prestataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Prise de rendez-vous</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="rdv-container">
    <h1>Réservez votre rendez‑vous</h1>
    <form id="form-rdv" action="https://sandigitalsolutions.com/calendrier-rdv/rdv-handler/traitement-rdv.php" method="post" novalidate>

      <!-- Nom -->
      <label for="nom">Nom complet :</label>
      <input type="text" id="nom" name="nom" required>

      <!-- Email -->
      <label for="email">Email :</label>
      <input type="email" id="email" name="email" required>

      <!-- Téléphone -->
      <label for="telephone">Téléphone :</label>
      <input type="tel" id="telephone" name="telephone">

      <!-- Prestation -->
      <label for="prestation">Prestation souhaitée :</label>
      <input type="text" id="prestation" name="prestation" required>

      <!-- Prestataire -->
      <label for="prestataire">Prestataire :</label>
      <select id="prestataire" name="prestataire" required>
        <option value="">Sélectionnez votre prestataire</option>
        <?php foreach ($prestataires as $p): ?>
          <option value="<?= htmlspecialchars($p['id']) ?>">
            <?= htmlspecialchars($p['nom']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Date -->
      <label for="date_rdv">Date du RDV :</label>
      <input type="date" id="date_rdv" name="date_rdv" required>

      <!-- Heure -->
      <label for="heure_rdv">Heure du RDV :</label>
      <input type="time" id="heure_rdv" name="heure_rdv" required>

      <!-- Commentaire -->
      <label for="commentaire">Commentaire (optionnel) :</label>
      <textarea id="commentaire" name="commentaire" rows="4"></textarea>

      <!-- Bouton -->
      <button type="submit" class="btn-submit">Envoyer</button>
    </form>
    <div id="form-message" class="message"></div>
  </div>

  <script src="script.js" defer></script>
</body>
</html>
