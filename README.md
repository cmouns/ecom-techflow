# TechFlow

Boutique en ligne développée avec Symfony dans le cadre d'un projet professionnel. Elle couvre les fonctionnalités classiques d'un e-commerce : catalogue produits, panier, espace client et paiement sécurisé via Stripe.

---

## Installation

**1. Cloner le dépôt**

```bash
git clone https://github.com/cmouns/ecom_techflow.git
cd ecom_techflow
```

**2. Installer les dépendances**

```bash
composer install
npm install
```

**3. Créer le fichier `.env.local`** à la racine du projet avec les variables suivantes :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/ecom_techflow"
STRIPE_SECRET_KEY="sk_test_..."
STRIPE_WEBHOOK_SECRET="whsec_..."

ADMIN_EMAIL="admin@techflow.local"
ADMIN_PASSWORD="VotreMotDePasse"
```

**4. Initialiser la base de données**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load -n
```

---

## Lancer le projet en local

```bash
# Démarrer le serveur
symfony serve

# Compiler Tailwind et surveiller les modifications
php bin/console tailwind:build --watch
```

---

## Qualité du code

```bash
# Tests unitaires et fonctionnels
vendor/bin/phpunit

# Analyse statique
vendor/bin/phpstan analyse src

# Formatage automatique
vendor/bin/php-cs-fixer fix src
```

---

## Paiement Stripe

Pour tester les webhooks en local, il faut faire tourner le listener Stripe en parallèle du serveur :

```bash
stripe listen --forward-to 127.0.0.1:8000/stripe/webhook
```

Pour les paiements de test, utiliser la carte `4242 4242 4242 4242` avec n'importe quelle date future et le CVC `123`.

---

## Accès admin

Se connecter sur `/login` avec les identifiants définis dans `.env.local`. Le compte admin est généré automatiquement lors du chargement des fixtures.

---

Développé par **Mounir SEBTI**.
