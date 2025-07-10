# ScriptSphere E-commerce Website

Symfony 7 / PHP 8.2 e-commerce marketplace for digital products, templates and web assets.

## Main improvements added

### Frontend
- Modern responsive navbar and product grid.
- Cleaner product cards with category badge, rating state, stock state and stronger call-to-action buttons.
- Global flash-message system with dismissible Bootstrap alerts.
- Safer JavaScript: no page-breaking errors when GSAP is not loaded.
- Favorite button AJAX behavior moved to a reusable `public/js/favorites.js` file.
- Safer direct-payment page that does not collect fake card data.

### Backend
- Added reusable services:
  - `ProductDisplayService` for search, sorting, top-rated products and rating display data.
  - `OrderService` for cart validation, preview orders and order creation.
- Added CSRF protection for cart actions, favorite actions, direct checkout, product deletion and order cancellation.
- Hardened Stripe success flow by retrieving the Stripe session and checking `payment_status === paid` and the matching user metadata before creating an order.
- Fixed missing templates:
  - `templates/payment/success.html.twig`
  - `templates/comment/_single_comment.html.twig`
- Fixed duplicate route in `MainController`.
- Fixed order price typing and ensured Stripe orders store the final price.
- Improved route access rules in `config/packages/security.yaml`.
- Admin users now always inherit `ROLE_USER` through `User::getRoles()`, which is the Symfony-standard behavior.

## Run locally

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

Or with PHP built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

## Environment variables

The project uses SQLite by default. For Stripe checkout, set:

```env
STRIPE_SECRET_KEY=sk_test_your_key_here
APP_BASE_URL=http://127.0.0.1:8000
```

## Notes

The current cart model stores each product only once because it uses a direct `ManyToMany` relation between `Panier` and `Product`. To support multiple quantities per cart line, the next backend improvement should be a `CartItem` entity with `quantity`, `unitPrice`, `product`, and `panier` fields.
