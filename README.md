# Projet de jeu de plateau web inspiré de Mario Kart

## 1) Mini document de règles (1–2 pages)

### Nombre de joueurs
- **Nombre de joueurs infini** (ou non limité par design).
- Les joueurs peuvent **rejoindre la partie en cours de route**.
- **Pas d’IA prévue**.

### Déroulement tour par tour
- La partie **n’est pas en temps réel** : chaque lancer de dé fait avancer un joueur spécifique et **fait progresser la partie**, sans notion de simultanéité.
- Il n’y a **pas d’ordre fixe**. Les joueurs gagnent des **lancers de dés** de différentes manières (ex : récompenses hebdomadaires) et peuvent les **stocker** jusqu’à leur connexion.
- Un joueur peut **relancer ses dés** une fois qu’au moins **30%** de la totalité des joueurs inscrits ont joué depuis son dernier lancer.
- Des **limites de stock** de lancers (et d’autres restrictions) sont **gérées côté admin**.
- **Structure d’un tour** :
  1. **Début de tour** : affichage des options (lancer, utiliser un objet si disponible).
  2. **Jet de dés** (ou équivalent) : le joueur obtient un nombre de cases à parcourir.
  3. **Déplacement** : le pion avance du nombre obtenu, selon le chemin principal du plateau.
  4. **Résolution de case** : activation des effets (objets, bonus/malus, case spéciale).
  5. **Fin de tour** : passage au joueur suivant.

### Jet de dés
- Jet standard **1–6** (dés classique) pour le MVP.
- Les objets peuvent modifier le résultat (ex : +2, relance) dans une version ultérieure.

### Déplacement
- **Déplacement linéaire** sur un chemin principal (pas de choix de route pour le MVP).
- Si un joueur **dépasse la dernière case**, il **boucle** et continue sur le plateau (pas de case finale décisive).

### Conditions de victoire
- La partie se joue en **X tours de plateau** (nombre choisi au lancement).
- **Gagne** le premier joueur qui **dépasse la case finale pour la Xᵉ fois**.

---

## 2) Fonctionnalités “MVP” vs “Plus tard”

### MVP (prioritaire)
- Plateau **linéaire** avec cases classiques et quelques cases “bonus/malus”.
- **Jet de dés** simple (1–6).
- **Tours non ordonnés** basés sur des lancers gagnés et stockés.
- **Objets simples** : 1–2 objets max (ex : boost +2, ralentissement -2).
- **Victoire** après **X boucles** du plateau.
- **Interface admin basique** pour lancer une partie, gérer les limites de lancers, et les joueurs.

### Plus tard
- **Objets avancés** : combos, objets rares, effets en chaîne.
- **Chemins alternatifs complexes** avec choix et bifurcations.
- **Interface admin complète** : création de plateaux, édition d’objets, gestion de règles.
- **Système de ligue / saisons** et statistiques avancées.

---

## 3) Écrans minimums

1. **Accueil / Inscription**
   - Création de compte, connexion, accès rapide au jeu.

2. **Plateau de jeu**
   - Vue du plateau, position des joueurs, bouton de lancer le dé.
   - Affichage des effets et objets obtenus.

3. **Classement**
   - Résultats de fin de partie.
   - Historique rapide (ex : nb de tours, dépassements).

4. **Interface admin basique**
   - Gestion des parties (créer, lancer, terminer).
   - Ajout/suppression de joueurs.
   - Paramétrage des limites de lancers.

---

## 4) Métriques de base (proposition à valider)

- **Taille du plateau** : 30 à 40 cases.
- **Fréquence d’objets** : **10% maximum** des cases.
- **Bonus/malus** : 10–15% des cases (ex : +2 / -2).
- **Nombre de tours** : choisi au début de la partie (à ajuster en phase de test).
- **Lancers hebdomadaires** : attribution d’un ou plusieurs lancers, stockables avec limites.

Ces métriques servent de base et pourront être ajustées après des tests utilisateurs.
