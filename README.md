# Gestion des Notes & Notifications SMS — V1

Application multi-établissement de gestion des évaluations, des notes et des notifications SMS aux parents, construite avec **Laravel 12 + Inertia.js + React + Tailwind CSS**.

Workflow principal : **Établissement → Année scolaire → Classe → Matière → Évaluation → Saisie des notes → Validation → Notification SMS → Historique**.

## Installation

Prérequis : PHP ≥ 8.2, Composer, Node.js ≥ 18, npm.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # ou configurez MySQL/PostgreSQL dans .env
php artisan migrate
php artisan db:seed              # données de démonstration (voir ci-dessous)
npm install
npm run build                    # ou `npm run dev` pendant le développement
php artisan serve
```

Pour que les SMS soient réellement envoyés en tâche de fond plutôt qu'en synchrone, lancez aussi :

```bash
php artisan queue:work
```

## Comptes de démonstration

Le seeder crée deux établissements avec des identités visuelles différentes (pour vérifier
l'isolation multi-établissement et le thème dynamique), 100 élèves, des enseignants affectés,
et une évaluation déjà validée (SMS déjà "envoyés" via le fournisseur de journalisation par défaut).

Mot de passe pour tous les comptes : `password`

| Établissement | Rôle | Email |
|---|---|---|
| Lycée d'Excellence de Cotonou | Administrateur | admin@lycee-excellence.test |
| Lycée d'Excellence de Cotonou | Enseignant | prof1@lycee-excellence.test |
| Collège Les Palmiers | Administrateur | admin@palmiers.test |
| Collège Les Palmiers | Enseignant | prof@palmiers.test |

## Notifications SMS

Le fournisseur SMS est abstrait derrière `App\Services\Sms\SmsProviderInterface`. Par défaut
(`SMS_PROVIDER=log` dans `.env`), les SMS sont simplement journalisés (`storage/logs/laravel.log`)
au lieu d'être réellement envoyés — pratique pour développer et démontrer tout le flux sans
compte fournisseur. Pour brancher un vrai fournisseur (Twilio, Orange, MTN…), implémentez
l'interface et ajustez `App\Providers\SmsServiceProvider`.

## Tests

```bash
php artisan test
```

La suite couvre : l'authentification, l'isolation stricte des données entre établissements,
les paramètres d'établissement (upload de logo, couleurs, permissions), le workflow complet
évaluation → saisie → validation → notification, et le renvoi des SMS en échec.

## Structure

- `app/Models/Concerns/BelongsToSchool.php` — trait central de l'isolation multi-établissement
  (global scope + `school_id` toujours renseigné côté serveur).
- `app/Support/SchoolBranding.php` — résout l'identité visuelle (couleurs, logo) de l'école
  connectée, partagée à React via Inertia et injectée en variables CSS (`--school-primary`, etc.).
- `app/Actions/Evaluations/` — création, saisie des notes, validation.
- `app/Services/Sms/` et `app/Services/Notifications/` — abstraction SMS et service de notification.
- `resources/js/Components/UI/` — kit de composants réutilisables (Button, Table responsive,
  Modal, ColorPicker, FileUpload, etc.), tous thémés via les variables CSS de l'établissement.
- `resources/js/Pages/` — pages Inertia, organisées par rôle (`Admin/`, `Teacher/`) et par
  domaine.

## Hors périmètre V1

Volontairement non développés à ce stade (voir le cahier des charges) : bulletins, calcul de
moyennes, classement, présence, paiements, espace parent/élève, WhatsApp, application mobile.
L'architecture (isolation par établissement, abstraction SMS, design system) est conçue pour
permettre leur ajout ultérieur sans réécriture majeure.


## Évolution SaaS multi-établissements

La plateforme est désormais structurée pour plusieurs établissements dans une seule application Laravel : `school_id` isole les données, `school_domains` prépare les domaines/sous-domaines et `SchoolSettings` porte le branding par école.

### Workflow des notes

`Saisie → Soumission → Validation → Notifications`

Les enseignants et secrétaires saisissent/soumettent. Les administrateurs et censeurs valident. La provenance `entered_by`, `submitted_by` et `validated_by` est conservée.

### Google Authentication

Configurer `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` et `GOOGLE_REDIRECT_URI`. Pour imposer Google comme seul mode d'authentification en production, utiliser `AUTH_GOOGLE_ONLY=true`. Les actions sensibles utilisent une réauthentification Google récente (`GOOGLE_REAUTH_SECONDS`).

### SMS

Le fournisseur peut rester sur `SMS_PROVIDER=log` en développement. Pour eSMS Africa : `SMS_PROVIDER=esms_africa`, `SMS_API_URL=https://sms.esmsafrica.io`, puis renseigner `SMS_API_KEY` et `SMS_SENDER_ID`. L'intégration utilise l'API HTTP officielle de l'envoi de SMS.

### Modèle économique

Les paramètres par établissement sont stockés dans `school_billing_settings` : installation, contribution annuelle par élève, partage Coriyase/établissement et quota SMS. Les contributions sont suivies dans `student_contributions`.


## Phase 2 — Espace parent, suivi scolaire et authentification forte

- TOTP Authenticator obligatoire pour tous les comptes, y compris les parents.
- Configuration initiale par QR code, clé manuelle et codes de récupération.
- Vérification TOTP à la connexion et revalidation pour les actions sensibles.
- Comptes parents liés à un ou plusieurs élèves.
- Espace parent : enfants, résultats validés, absences et retards.
- Années/périodes scolaires, présences et justifications.
- Modèle de bulletin versionné et token de vérification publique.

### Installation frontend
Après extraction, installer les dépendances avec `npm install`, puis lancer `npm run build` ou `npm run dev`.
