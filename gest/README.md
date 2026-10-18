# Gym SaaS (`gest`) - Journal Projet & Documentation Continue

Ce document est la source de vérité du projet.
Objectif: ne jamais perdre le contexte, savoir où on s'est arrêté, et reprendre rapidement.

## 1) Vision du projet
Plateforme SaaS de gestion de salles de gym avec:
- un espace **Super Admin** (pilotage global SaaS)
- un espace **Admin Salle / Tenant** (gestion quotidienne de chaque salle)
- une partie publique (landing + inscription d'une salle)

## 2) Stack technique
- Backend: Laravel `13.x`
- PHP: `^8.3`
- Admin panel: Filament `^5`
- Front build: Vite `^8` + Tailwind CSS `^4`
- DB: MySQL (en local actuellement)

## 3) Routes et accès importants
- Landing publique: `/`
- Inscription salle et création automatique du compte Admin: `/inscription-salle`
- Inscription membre: `/inscription`
- Login admin salle: `/admin/login`
- Login super admin: `/super-admin/login`

Lorsqu’un propriétaire inscrit sa salle, son compte Admin et sa salle sont
créés ensemble. Il est connecté automatiquement et arrive sur le tableau de
bord de son espace, sans étape de connexion séparée.

## 4) Création des comptes Filament
Pour créer un utilisateur, lancer:
```bash
php artisan make:filament-user
```

La commande demande le rôle **Admin salle** ou **Super Admin**. Pour un admin,
il faut sélectionner la salle à laquelle il sera rattaché. Pour un Super Admin,
configurer d’abord `ARENA_SUPER_ADMIN_EMAIL` dans `.env` avec l’adresse qui sera
utilisée pour ce compte. Le mot de passe est demandé de façon interactive et
stocké sous forme hachée.

Le compte de démonstration éventuellement créé par le seeder utilise un mot de
passe faible : ne pas réutiliser ces identifiants en production.

## 5) Historique du projet (chronologie)
### 2026-04-17 - Commit `5670e5e` - "projet salle de gym"
- Initialisation du SaaS gym (multi-tenant + modules métier).
- Base fonctionnelle Laravel + Filament + modèles gym/membres/abonnements/paiements.

### 2026-04-25 - Commit `0e5490f` - "amelioration"
- Ajout de design/UX (landing, registration, thème Filament), SaaS billing, etc.
- Ajout de fichiers Docker (`Dockerfile`, `docker-compose.yml`, `docker/apache/000-default.conf`).
- Effets collatéraux observés ensuite:
  - dépendances backend/front manquantes localement
  - lock Composer incompatible PHP local
  - fichier CSS `resources/css/filament/shared.css` tronqué
  - incohérences sur certaines migrations

### 2026-04-27 - Session de restauration (travail en cours)
Actions effectuées:
- Réinstallation des dépendances npm/composer.
- Mise à jour lockfile pour compatibilité PHP `8.3`.
- Réparation de `resources/css/filament/shared.css` (build front OK).
- Suppression de l'approche Docker (retour souhaité "sans Docker").
- Correction des migrations pour exécution fiable:
  - `database/migrations/2026_04_16_000001_create_gyms_table.php`
  - `database/migrations/2026_04_16_110945_create_gyms_table.php`
  - `database/migrations/2026_04_16_120627_add_gym_id_and_super_admin_columns.php`
- Vérification réussie de:
  - `php artisan migrate:fresh --seed`
  - présence des comptes d'accès en base

### 2026-04-27 - Session design admin (inspirée FlexAdmin)
Actions effectuées:
- Validation de la direction visuelle à partir de la référence FlexAdmin (BootstrapMade).
- Refonte de la landing publique (`welcome.blade.php`) dans un style plus dynamique + animations.
- Renforcement du dashboard super-admin avec widgets orientés pilotage:
  - KPIs globaux (`SaasOverview`)
  - courbe revenus 6 mois (`SaasRevenueTrendChart`)
  - répartition des offres (`GymsByPlanChart`)
  - tableau des salles à relancer (`ExpiringGymsTable`)
- Renforcement du dashboard admin salle:
  - ajout des alertes abonnements (`SubscriptionAlerts`)
  - ajout des transactions récentes (`RecentTransactions`)
- Harmonisation du thème Filament vers un style plus \"dashboard SaaS\" (palette indigo, cartes et sidebar modernisées).

Commandes de validation:
- `php -l` sur les fichiers PHP modifiés
- `npm run build`
- `php artisan route:list` (routes admin/super-admin OK)

Point de reprise:
- Ajustement fin UI/UX des pages internes admin selon les modules prioritaires (tables, filtres, actions rapides).

## 6) Décisions prises
- **Docker annulé** pour l'instant (travail local standard).
- Les prochaines améliorations doivent se faire prioritairement sur la **vue** (UX/UI responsive), sans casser la logique métier.
- Filament reste la base pour l'admin (pas de refonte back-office complète).

## 7) État actuel (point de reprise)
Le projet est relançable en local (MySQL), avec seed et accès SaaS opérationnels.

Travail demandé suivant (en attente):
- choisir une direction template/UI pour:
  - `resources/views/welcome.blade.php`
  - `resources/views/gym/register.blade.php`
- objectif: vue plus pro, responsive, UX claire.

## 8) Fichiers actuellement modifiés localement (non commit)
État `git status --short` au moment de cette documentation:
- `D Dockerfile`
- `D docker-compose.yml`
- `D docker/apache/000-default.conf`
- `M composer.lock`
- `M package-lock.json`
- `M resources/css/filament/shared.css`
- `M database/migrations/2026_04_16_000001_create_gyms_table.php`
- `M database/migrations/2026_04_16_110945_create_gyms_table.php`
- `M database/migrations/2026_04_16_120627_add_gym_id_and_super_admin_columns.php`

## 9) Procédure de démarrage local (sans Docker)
1. Installer dépendances:
```bash
composer install
npm install
```
2. Vérifier `.env` (DB MySQL locale).
3. Générer clé si nécessaire:
```bash
php artisan key:generate
```
4. Recréer base + données:
```bash
php artisan migrate:fresh --seed
```
5. Lancer app:
```bash
composer run dev
```

## 10) Journal de travail - format à utiliser dorénavant
À chaque session, ajouter une entrée ici:

```md
### YYYY-MM-DD - Session
- Contexte de départ:
- Actions réalisées:
- Fichiers modifiés:
- Commandes de validation exécutées:
- Résultat:
- Prochain point de reprise:
```

## 11) Backlog prioritaire
- [ ] Choisir template / direction visuelle finale (Bootstrap orienté SaaS gym)
- [ ] Refaire landing publique (`welcome.blade.php`)
- [ ] Refaire page inscription salle (`gym/register.blade.php`)
- [ ] Harmoniser couleurs/typo/composants avec identité unique
- [ ] Vérifier responsive mobile/tablette/desktop
- [ ] Nettoyer les fichiers/locks avant commit final

---
Dernière mise à jour: **2026-04-27**
