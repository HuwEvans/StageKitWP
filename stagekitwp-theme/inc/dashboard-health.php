<?php
/**
 * Theme Details Integration & Compatibility Status Panel
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function stagekitwp_theme_inject_health_status( $prepared_themes ) {
    // Safety check: If for any reason the themes array is empty or malformed, escape early
    if ( ! is_array( $prepared_themes ) ) {
        return $prepared_themes;
    }

    $current_theme_slug = get_stylesheet();

    if ( isset( $prepared_themes[ $current_theme_slug ] ) ) {
        
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        $active_plugins = (array) get_option( 'active_plugins', array() );
        $is_plugin_active = static function( $paths ) use ( $active_plugins ) {
            foreach ( (array) $paths as $path ) {
                if ( in_array( $path, $active_plugins, true ) ) {
                    return true;
                }

                // Studio and ZIP installs may retain a version in the folder name.
                $filename = basename( $path );
                foreach ( $active_plugins as $active_plugin ) {
                    if ( basename( $active_plugin ) === $filename ) {
                        return true;
                    }
                }
            }

            return false;
        };

        $plugins = array(
            'StageKitWP Core' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-core/stagekitwp-core.php' ) ),
                'ready'  => defined( 'STAGEKITWP_CORE_VERSION' ) || class_exists( 'StageKitWP_Core', false ),
            ),
            'StageKitWP Blocks' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-blocks/stagekitwp-blocks.php' ) ),
                'ready'  => function_exists( 'stagekitwp_register_blocks' ),
            ),
            'StageKitWP SEO' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-seo/stagekitwp-seo.php' ) ),
                'ready'  => true,
            ),
            'Import / Export' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-import-export/stagekitwp-import-export.php' ) ),
                'ready'  => defined( 'STAGEKITWP_IMPORT_EXPORT_VERSION' ) || class_exists( 'StageKitWP_Import_Export_Plugin', false ),
            ),
            'Members' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-members/stagekitwp-members.php' ) ),
                'ready'  => defined( 'STAGEKITWPMA_VERSION' ) || class_exists( 'STAGEKITWP_Member_Area', false ),
            ),
            'RC Library' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-rc-library/stagekitwp-rc-library.php' ) ),
                'ready'  => defined( 'STAGEKITWP_RC_LIBRARY_VERSION' ) || class_exists( 'STAGEKITWP_RC_LIBRARY_CPTs', false ),
            ),
            'Sync' => array(
                'active' => $is_plugin_active( array( 'stagekitwp-sync/stagekitwp-sync.php' ) ),
                'ready'  => defined( 'STAGEKITWP_SYNC_VERSION' ) || class_exists( 'STAGEKITWP_Sync', false ),
            ),
        );

        ob_start();
        ?>
        <div class="stagekitwp-theme-health-panel" style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-left: 4px solid #e50914; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #1d2327;">
                🎭 <?php _e( 'StageKitWP System Health', 'stagekitwp-theme' ); ?>
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #dcdcde; text-align: left;">
                        <th style="padding: 6px 0; font-weight: 600;"><?php _e( 'Required Component', 'stagekitwp-theme' ); ?></th>
                        <th style="padding: 6px 0; font-weight: 600; text-align: right;"><?php _e( 'Status', 'stagekitwp-theme' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $plugins as $name => $plugin ) :
                        $is_active = ! empty( $plugin['active'] ) && ! empty( $plugin['ready'] );
                        ?>
                        <tr style="border-bottom: 1px solid #f0f0f1;">
                            <td style="padding: 8px 0; color: #50575e;"><?php echo esc_html( $name ); ?></td>
                            <td style="padding: 8px 0; text-align: right;">
                                <?php if ( $is_active ) : ?>
                                    <span style="color: #00a32a; font-weight: 700;">✅ <?php esc_html_e( 'Active', 'stagekitwp-theme' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #d63638; font-weight: 700;">❌ <?php esc_html_e( 'Missing', 'stagekitwp-theme' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr>
                        <td style="padding: 8px 0; color: #50575e;"><?php esc_html_e( 'Max Allowed Upload Size', 'stagekitwp-theme' ); ?></td>
                        <td style="padding: 8px 0; text-align: right;">
                            <?php 
                            $max_size = wp_max_upload_size();
                            $current_max_mb = $max_size ? round( $max_size / ( 1024 * 1024 ) ) : 0;
                            if ( $current_max_mb >= 15 ) : ?>
                                <span style="color: #00a32a; font-weight: 700;">✅ <?php echo esc_html( (string) $current_max_mb ); ?>MB</span>
                            <?php else : ?>
                                <span style="color: #dba617; font-weight: 700;">⚠️ <?php echo esc_html( (string) $current_max_mb ); ?>MB</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
        $health_html = ob_get_clean();

        // Append cleanly without overriding previous text allocations
        $prepared_themes[ $current_theme_slug ]['description'] .= $health_html;
    }

    return $prepared_themes;
}
add_filter( 'wp_prepare_themes_for_js', 'stagekitwp_theme_inject_health_status' );