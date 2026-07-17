<?php
// Payment provider config - set real keys in production.
// For Stripe Checkout, set STRIPE_SECRET and STRIPE_PUBLISHABLE.

if (!defined('STRIPE_SECRET')) {
    // replace with your Stripe secret key or set via environment
    define('STRIPE_SECRET', getenv('STRIPE_SECRET') ?: 'sk_test_REPLACE_ME');
}
if (!defined('STRIPE_PUBLISHABLE')) {
    define('STRIPE_PUBLISHABLE', getenv('STRIPE_PUBLISHABLE') ?: 'pk_test_REPLACE_ME');
}
// Webhook signing secret (optional)
if (!defined('STRIPE_WEBHOOK_SECRET')) {
    define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_REPLACE_ME');
}
?>