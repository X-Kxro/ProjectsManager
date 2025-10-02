# Gestionnaire de Projets

Application web professionnelle pour la gestion et l'organisation de projets de développement.

## Fonctionnalités

- **Gestion de projets** : Création, suppression, renommage de projets
- **Support multi-langages** : PHP, HTML, React, Vue.js, Node.js
- **Système de versioning** : Sauvegarde et restauration automatique
- **Clonage Git** : Importation directe depuis GitHub
- **Interface moderne** : Design responsive avec thème sombre/clair
- **Recherche et filtrage** : Tri par nom, date, taille, type

## Structure du projet

```
ProjectsManager/
├── index.php           # Page principale et dashboard
├── actions.php         # Gestionnaire d'actions (CRUD)
├── versions.php        # Historique des versions
├── backup_auto.php     # Script de sauvegarde automatique
├── assets/
│   ├── index.js        # JavaScript principal (classe ProjectManager)
│   └── style.css       # Styles optimisés
├── projects/           # Dossier des projets utilisateur
├── backups/           # Archives des versions
└── logs/              # Fichiers de logs
```

## Installation

1. Placez le dossier dans votre serveur web (Apache/Nginx)
2. Assurez-vous que PHP 7.4+ est installé avec les extensions :
   - `ZipArchive` (pour les sauvegardes)
   - `exec` (pour le clonage Git)
3. Configurez les permissions d'écriture sur les dossiers :
   - `projects/`
   - `backups/`
   - `logs/`

## Configuration

### Variables principales (actions.php)
- `$root` : Chemin vers le dossier des projets
- `$exclude` : Dossiers à exclure
- Politique de rétention : 7 backups maximum par projet

### Sauvegarde automatique
Configurez une tâche cron pour exécuter `backup_auto.php` :
```bash
0 2 * * * php /path/to/backup_auto.php
```

## API Actions

### Création de projet
```php
POST actions.php
action=create&project=nom-projet&template=php|html|react|vue
```

### Suppression
```php
POST actions.php
action=delete&project=nom-projet&confirm=nom-projet
```

### Clonage Git
```php
POST actions.php
action=git_clone&repo=url-github&target=nom-dossier
```

### Versioning
```php
POST actions.php
action=versioning&project=nom-projet
```

## Sécurité

- Validation stricte des noms de fichiers
- Protection contre l'injection de commandes
- Vérification d'intégrité SHA256 des archives
- Liste d'exclusion pour les dossiers système

## Optimisations effectuées

✅ **Code nettoyé** : Suppression de tous les commentaires inutiles
✅ **Performance** : CSS et JavaScript optimisés
✅ **Traductions** : Interface entièrement en français
✅ **Structure** : Organisation cohérente du code
✅ **Sécurité** : Validation et échappement des données

## Technologies

- **Backend** : PHP 7.4+
- **Frontend** : JavaScript ES6, CSS Grid/Flexbox
- **Base de données** : Système de fichiers (JSON pour métadonnées)
- **Archivage** : ZipArchive
- **Versioning** : Git (optionnel)

## Licence

Projet open source - Licence MIT