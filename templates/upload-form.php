<?php
/**
 * Template Login Form AR Private Files
 * 
 * @package ARPrivateFiles
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $atts['title'] ?? __('Area Riservata', 'ar-private-files');
$redirect = $atts['redirect'] ?? get_permalink();
?>

<div class="ar-login-container">
    <div class="ar-login-box">
        <h3 class="ar-login-title">🔐 <?php echo esc_html($title); ?></h3>
        <p class="ar-login-description">
            <?php _e('Inserisci le tue credenziali per accedere ai documenti privati.', 'ar-private-files'); ?>
        </p>
        
        <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?>
            <div class="ar-login-error">
                <?php _e('Credenziali non valide. Riprova.', 'ar-private-files'); ?>
            </div>
        <?php endif; ?>
        
        <?php
        wp_login_form(array(
            'echo' => true,
            'redirect' => $redirect,
            'form_id' => 'ar-loginform',
            'label_username' => __('Nome utente o Email', 'ar-private-files'),
            'label_password' => __('Password', 'ar-private-files'),
            'label_remember' => __('Ricordami', 'ar-private-files'),
            'label_log_in' => __('Accedi', 'ar-private-files'),
            'value_remember' => true
        ));
        ?>
        
        <p class="ar-login-help">
            <small><?php _e('Non hai le credenziali di accesso? Contatta l\'amministratore.', 'ar-private-files'); ?></small>
        </p>
    </div>
</div>