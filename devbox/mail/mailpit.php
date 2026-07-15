<?php
/**
 * Plugin Name: Devbox Mailpit mailer
 * Description: Routes all WordPress/WooCommerce outgoing mail to the devbox
 *              Mailpit catcher (SMTP mailpit:1025 on the shared docker network),
 *              so nothing leaves the box. DEV ONLY — dropped in as a must-use
 *              plugin via a compose bind mount; never ship to production.
 *
 * Mailpit listens unauthenticated on :1025 with no TLS, so we force plain SMTP
 * and disable PHPMailer's opportunistic STARTTLS.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('phpmailer_init', static function ($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host        = 'mailpit';
    $phpmailer->Port        = 1025;
    $phpmailer->SMTPAuth    = false;
    $phpmailer->SMTPAutoTLS = false;
    $phpmailer->SMTPSecure  = '';
});
