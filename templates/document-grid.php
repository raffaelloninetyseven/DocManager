<?php
/**
 * Template Document Card AR Private Files
 * 
 * @package ARPrivateFiles
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$document_id = get_the_ID();
$user_id = get_current_user_id();
$file_name = get_post_meta($document_id, '_ar_file_name', true);
$file_size = get_post_meta($document_id, '_ar_file_size', true);
$download_count = get_post_meta($document_id, '_ar_download_count', true) ?: 0;
$expires_date = get_post_meta($document_id, '_ar_expires_date', true);
$download_limit = get_post_meta($document_id, '_ar_download_limit', true);

// Verifica stato documento
$is_expired = $expires_date && strtotime($expires_date) < time();
$limit_reached = $download_limit && $download_count >= $download_limit;
$can_download = !$is_expired && !$limit_reached && $file_name;
?>

<div class="ar-document-card <?php echo $is_expired ? 'ar-expired' : ''; ?> <?php echo $limit_reached ? 'ar-limit-reached' : ''; ?>">
    <div class="ar-card-header">
        <h3 class="ar-card-title"><?php the_title(); ?></h3>
        <div class="ar-card-meta">
            <span class="ar-card-date">
                📅 <?php echo get_the_date('d/m/Y'); ?>
            </span>
            <?php if ($download_count > 0): ?>
                <span class="ar-download-count">
                    📥 <?php echo number_format_i18n($download_count); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (get_the_content()): ?>
        <div class="ar-card-content">
            <p><?php echo wp_trim_words(get_the_content(), 25); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="ar-card-footer">
        <?php if ($file_name): ?>
            <div class="ar-file-info">
                <span class="ar-file-icon"><?php echo ar_get_file_icon($file_name); ?></span>
                <div class="ar-file-details">
                    <span class="ar-file-name">
                        <?php echo esc_html(pathinfo($file_name, PATHINFO_FILENAME)); ?>
                    </span>
                    <?php if ($file_size): ?>
                        <span class="ar-file-size">
                            <?php echo ar_format_file_size($file_size); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="ar-card-actions">
                <?php if ($is_expired): ?>
                    <span class="ar-status-notice ar-expired-notice">
                        ⚠️ <?php _e('Scaduto', 'ar-private-files'); ?>
                    </span>
                <?php elseif ($limit_reached): ?>
                    <span class="ar-status-notice ar-limit-notice">
                        🚫 <?php _e('Limite raggiunto', 'ar-private-files'); ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo ar_get_download_url($document_id, $user_id); ?>" 
                       class="ar-download-btn" target="_blank">
                        📥 <?php _e('Visualizza', 'ar-private-files'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($expires_date && !$is_expired): ?>
                <div class="ar-expires-info">
                    <small>
                        ⏰ <?php printf(__('Scade il %s', 'ar-private-files'), 
                                       date_i18n(get_option('date_format'), strtotime($expires_date))); ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <?php if ($download_limit && !$limit_reached): ?>
                <div class="ar-limit-info">
                    <small>
                        🔢 <?php printf(__('Download: %d/%d', 'ar-private-files'), $download_count, $download_limit); ?>
                    </small>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="ar-no-file">
                <span class="ar-no-file-notice">
                    ⚠️ <?php _e('File non disponibile', 'ar-private-files'); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>