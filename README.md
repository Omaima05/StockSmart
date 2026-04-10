# 🚀 StockSmart Pro — Guide d'installation

## Structure du projet
```
STOCKSMART/
├── index.php          ← Landing page publique (NOUVEAU)
├── dashboard.php      ← Tableau de bord (ancien index.php)
├── config.php         ← Configuration BDD + helpers
├── _nav.php           ← Navigation partagée (NOUVEAU)
├── stocksmart_v2.sql  ← Base de données complète
├── assets/
│   └── img/
│       ├── hero.jpg              ← Photo hero section
│       ├── card-stock.jpg        ← Photo carte inventaire
│       ├── card-tracabilite.jpg  ← Photo carte traçabilité
│       ├── card-multienseignes.jpg ← Photo carte multi-enseignes
│       ├── split-inventaire.jpg  ← Photo section inventaire
│       ├── split-tracabilite.jpg ← Photo section traçabilité
│       └── produits/             ← Photos produits uploadées
├── css/
│   └── style.css
├── js/
│   └── script.js
└── pages/
    ├── login.php
    ├── register.php
    ├── logout.php
    ├── produits.php
    ├── categories.php
    ├── mouvements.php
    └── admin.php
```

## Installation

### 1. Base de données
1. Ouvre http://localhost/phpmyadmin
2. Clique sur **SQL** (ou Importer)
3. Colle le contenu de `stocksmart_v2.sql`
4. Clique **Exécuter**

### 2. Fichiers
Copie tout le dossier dans `C:/xampp/htdocs/stocksmart/`

### 3. Images
Renomme tes photos uploadées et place-les dans `assets/img/` :
- `hero.jpg`              → la photo de l'employée au rayon (grocery)
- `card-stock.jpg`        → bulk-operations.webp
- `card-tracabilite.jpg`  → know-zom.webp
- `card-multienseignes.jpg` → predefined-parameters.webp
- `split-inventaire.jpg`  → products_one_source.webp
- `split-tracabilite.jpg` → retail-photography...jpg

### 4. config.php
Vérifie les paramètres de connexion :
```php
$host     = '127.0.0.1';
$port     = '3306';
$dbname   = 'stocksmart_v2';
$username = 'root';
$password = '';  // vide sur XAMPP par défaut
```

### 5. Accès
- **Landing page** : http://localhost/stocksmart/
- **Connexion** : http://localhost/stocksmart/pages/login.php

## Compte démo
| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@stocksmart.pro | password | Admin |
| thomas@carrefour.fr | password | Employé |
| marc@leclerc.fr | password | Gérant |

> ⚠️ Le mot de passe "password" est haché avec PHP `password_hash()`.
> Pour le recréer : `<?php echo password_hash('password', PASSWORD_DEFAULT); ?>`
> Puis mets le hash dans la colonne `mot_de_passe` des utilisateurs.

## Flux de navigation
```
Visiteur → index.php (landing)
              ↓
         login.php ← register.php
              ↓
         dashboard.php
         ├── produits.php
         ├── categories.php
         ├── mouvements.php
         └── admin.php (admin seulement)
```

## Points clés techniques
- **Session** : gérée dans `config.php` (une seule fois)
- **Sécurité** : `requireLogin()` sur toutes les pages internes
- **Multi-enseignes** : `enseigne_id` en session après login
- **Photos** : API Open Food Facts chargée en JS côté client
- **Trigger SQL** : mise à jour automatique du stock après chaque mouvement
