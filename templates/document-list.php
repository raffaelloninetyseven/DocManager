<?php
/**
 * Template Dashboard AR Private Files
 * 
 * @package ARPrivateFiles
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$columns = $atts['columns'] ?? 2;
?>

<div class="ar-dashboard-wrapper">
    <div class="ar-dashboard-header">
        <div class="ar-header-content">
            <div class="ar-header-left">
                <h2><?php echo esc_html($atts['title'] ?? __('Area Riservata', 'ar-private-files')); ?></h2>
                <p class="ar-welcome-message">
                    <?php echo esc_html($atts['welcome_message'] ?? sprintf(__('Benvenuto %s', 'ar-private-files'), $current_user->display_name)); ?>
                </p>
            </div>
            <div class="ar-header-right">
                <div class="ar-user-info">
                    <span class="ar-user-name">👤 <?php echo esc_html($current_user->display_name); ?></span>
                    <a href="<?php echo wp_logout_url(); ?>" class="ar-logout-btn">
                        <?php _e('Logout', 'ar-private-files'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Query documenti utente
    $documents = new WP_Query(array(
        'post_type' => 'ar_document',
        'posts_per_page' => intval($atts['per_page'] ?? 12),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_ar_assigned_user',
                'value' => $user_id,
                'compare' => '='
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    ?>
    
    <?php if ($documents->have_posts()): ?>
        <div class="ar-documents-grid ar-columns-<?php echo esc_attr($columns); ?>">
            <?php while ($documents->have_posts()): $documents->the_post(); ?>
                <?php include AR_PLUGIN_PATH . 'templates/document-card.php'; ?>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="ar-empty-state">
            <div class="ar-empty-icon">📂</div>
            <h3><?php _e('Nessun documento disponibile', 'ar-private-files'); ?></h3>
            <p><?php _e('I tuoi documenti appariranno qui non appena saranno caricati.', 'ar-private-files'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div>