# IliAuto

Site public construit avec React et Vite. L'API et l'espace d'administration PHP
restent à la racine du projet.

## Développement

```sh
npm install
npm run dev -- --host 0.0.0.0
```

Pour utiliser l'API PHP pendant le développement, lancer aussi le serveur PHP
dans un second terminal, depuis ce dossier :

```sh
php -S 127.0.0.1:8000 -t .
```

Si le port 8000 est déjà utilisé, définir `PHP_BACKEND_URL` avant de lancer Vite
pour indiquer l'adresse choisie pour le serveur PHP.

Sans serveur PHP, le site affiche les véhicules de démonstration et signale que
l'API est indisponible (mode démo uniquement, voir ci-dessous).

## Mode démo

- `npm run dev` : véhicules fictifs uniquement si l'API est absente. Cette
  solution de repli est désactivable avec `VITE_DEMO_MODE=false`.
- `npm run build` : mode démo **inactif** par défaut. Aucun véhicule fictif n'est
  jamais montré aux visiteurs : si l'API est indisponible, un message propre avec
  un lien WhatsApp s'affiche. Pour une version de démonstration publique :
  `VITE_DEMO_MODE=true npm run build`.

## Adresses des pages

Le site utilise des adresses en `#` (aucune configuration serveur nécessaire) :
`#/` accueil, `#/vehicules`, `#/vehicules/ID` (fiche partageable), `#/services`,
`#/a-propos`, `#/contact`. Le bouton Retour du navigateur fonctionne.

## Accès administrateur

- En production, ouvrir `https://votre-domaine.com/admin/` ou cliquer sur
  « Espace Admin » en bas du site.
- En développement local, démarrer le serveur PHP comme indiqué plus haut puis
  ouvrir `http://127.0.0.1:8000/admin/`.
- Il n'existe pas d'identifiant ni de mot de passe par défaut. Lors de la
  première visite, si aucun compte n'a encore été créé, la page d'installation
  permet de créer le compte administrateur avec un identifiant et un mot de
  passe uniques.
- Avant la mise en ligne, définir dans `admin/config.php` un code aléatoire et
  privé d'au moins 32 caractères. Ce code sert aussi à réinitialiser le mot de
  passe depuis « Mot de passe oublié ? » sur la page de connexion. Ne pas mettre
  ce code ni le mot de passe dans ce README, le dépôt Git ou un espace public.
- Après la création du compte, vérifier la connexion puis supprimer `admin/setup.php`
  du serveur. Transmettre les identifiants au client par un canal privé et lui
  demander de choisir un mot de passe qui lui est propre.
- En cas d'oubli du mot de passe, ouvrir `admin/forgot.php` et saisir le code
  secret de `admin/config.php`, puis choisir un nouveau mot de passe. Si ce code
  est perdu, le remplacer par un nouveau code aléatoire d'au moins 32 caractères
  dans ce fichier via l'hébergement, puis utiliser la page de récupération.

## Production

```sh
npm run build
```

Le site compilé est généré dans `dist/`. Sur Apache, envoyer sur le serveur
uniquement : `dist/`, `api/`, `admin/`, `data/`, `uploads/` et `.htaccess`
(jamais `node_modules/`, `src/` ni `package*.json`). Le `.htaccess` racine sert
l'application compilée tout en laissant les routes PHP et leurs données
protégées. Il nécessite le module Apache `mod_rewrite`.

Les chemins des fichiers compilés sont relatifs : le site fonctionne à la racine
d'un domaine comme dans un sous-dossier.

## Formulaire de contact

Il n'y a pas de serveur de messagerie : le formulaire ouvre WhatsApp avec le
message (nom, téléphone, texte) prêt à être envoyé au numéro configuré dans
l'admin (Paramètres).
