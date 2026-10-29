# Arena Gym

SaaS de gestion de salles de sport. L’application comprend un espace public, un espace de gestion par salle et un espace Super Admin.

## Stack

- PHP 8.3+, Laravel 13, Filament 5
- React, Vite et Tailwind CSS
- MySQL ou MariaDB

## Déploiement Ubuntu

Le document root Nginx doit pointer vers le dossier `public/` de Laravel. Le site doit être servi en HTTPS. PHP-FPM doit pouvoir écrire dans `storage/` et `bootstrap/cache/`.

1. Créer le fichier `.env` sur le serveur et configurer `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votre-domaine` et l’accès MySQL. Générer `APP_KEY` une seule fois, puis conserver la même clé lors des mises à jour.
2. Renseigner `ARENA_SUPER_ADMIN_EMAIL`, les identifiants Resend avec une adresse expéditrice vérifiée, et les clés Turnstile si la protection est activée.
3. Installer et compiler :

   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   npm ci
   npm run build
   ```

4. Sauvegarder la base, puis appliquer les migrations et optimiser Laravel :

   ```bash
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan optimize
   ```

5. Configurer le cron Laravel chaque minute :

   ```cron
   * * * * * cd /var/www/arena-gym/current && php artisan schedule:run >> /dev/null 2>&1
   ```

6. Créer le Super Admin depuis le serveur. La commande demande le mot de passe de façon interactive :

   ```bash
   php artisan make:filament-user --role=super_admin --name="Super Admin" --email="adresse-configuree-dans-env"
   ```

Ne lance pas `migrate:fresh` ni les seeders de démonstration en production. Les seeders de démo sont réservés au local et aux tests. Le fichier `.env` et les bases locales sont ignorés par Git.

## Développement local

```bash
composer install
npm ci
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
composer run dev
```

Pour créer des données de démonstration, utiliser `php artisan db:seed` uniquement dans un environnement `local` ou `testing`.