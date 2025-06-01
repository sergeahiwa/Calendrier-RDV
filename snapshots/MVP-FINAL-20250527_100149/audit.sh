#!/bin/bash

echo "📦 Audit Git du projet Calendrier RDV"
echo "====================================="
echo ""

# 1. Afficher l'état Git
echo "🔍 État du dépôt Git :"
git status
echo ""

# 2. Afficher les 10 derniers fichiers modifiés
echo "🕒 Derniers fichiers modifiés (dans Git) :"
git log --name-only --pretty=format: --since="7 days ago" | sort | uniq | tail -n 10
echo ""

# 3. Fichiers modifiés non commités
echo "📝 Fichiers modifiés non commités :"
git diff --name-only
echo ""

# 4. Vérification des fichiers IA critiques
echo "🤖 Fichiers IA modifiés récemment :"
git diff --name-only | grep -E 'ia|intelligence|ml|ai' || echo "Aucun fichier IA modifié."
echo ""

# 5. Dernier commit
echo "📌 Dernier commit :"
git log -1 --oneline
echo ""

echo "✅ Audit terminé."
