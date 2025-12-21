<?php
/**
 * Admin Interface - VENDOR
 */

if (!defined('ABSPATH')) exit;

class IPV_Vendor_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_ipv_create_license', [$this, 'handle_create_license']);
        add_action('admin_post_ipv_create_golden_prompt_license', [$this, 'handle_create_golden_prompt_license']);
        add_action('admin_post_ipv_delete_golden_prompt_license', [$this, 'handle_delete_golden_prompt_license']);
        add_action('admin_post_ipv_update_golden_prompt_content', [$this, 'handle_update_golden_prompt_content']);
        add_action('admin_post_ipv_save_master_template', [$this, 'handle_save_master_template']);
        add_action('admin_post_ipv_push_master_to_all', [$this, 'handle_push_master_to_all']);
        add_action('admin_post_ipv_restore_master_version', [$this, 'handle_restore_master_version']);
        add_action('admin_post_ipv_download_master_template', [$this, 'handle_download_master_template']);
        // ✅ NEW: AJAX toggle Golden Prompt per singola licenza
        add_action('wp_ajax_ipv_toggle_golden_prompt', [$this, 'ajax_toggle_golden_prompt']);
    }
    
    public function add_menu() {
        add_menu_page(
            'IPV Vendor',
            'IPV Vendor',
            'manage_options',
            'ipv-vendor',
            [$this, 'dashboard_page'],
            'dashicons-admin-network',
            30
        );
        
        add_submenu_page(
            'ipv-vendor',
            'Licenses',
            'Licenses',
            'manage_options',
            'ipv-vendor-licenses',
            [$this, 'licenses_page']
        );
        
        // ✅ NEW: Golden Prompt submenu
        add_submenu_page(
            'ipv-vendor',
            'Golden Prompt',
            '🌟 Golden Prompt',
            'manage_options',
            'ipv-vendor-golden-prompt',
            [$this, 'golden_prompt_page']
        );
        
        // ✅ NEW: Golden Prompt Master Template
        add_submenu_page(
            'ipv-vendor',
            'Golden Prompt Master',
            '📋 Master Template',
            'manage_options',
            'ipv-vendor-golden-prompt-master',
            [$this, 'golden_prompt_master_page']
        );
        
        add_submenu_page(
            'ipv-vendor',
            'Settings',
            'Settings',
            'manage_options',
            'ipv-vendor-settings',
            [$this, 'settings_page']
        );
    }
    
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>🏢 IPV Vendor Dashboard</h1>
            <p>Sistema per gestione licenze e API</p>
            
            <?php
            global $wpdb;
            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses");
            $active = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ipv_licenses WHERE status='active'");
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0;">Total Licenses</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo $total; ?></div>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0;">Active</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #46b450;"><?php echo $active; ?></div>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0;">API Status</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #46b450;">✓ Online</div>
                </div>
            </div>
            
            <h2>Quick Actions</h2>
            <p>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-licenses'); ?>" class="button button-primary">Manage Licenses</a>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-settings'); ?>" class="button">Settings</a>
            </p>
        </div>
        <?php
    }
    
    public function licenses_page() {
        $manager = IPV_Vendor_License_Manager::get_instance();
        $licenses = $manager->get_all();
        
        ?>
        <div class="wrap">
            <h1>Licenses</h1>
            
            <h2>Create New License</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="background: white; padding: 20px; max-width: 600px; border-radius: 8px;">
                <input type="hidden" name="action" value="ipv_create_license">
                <?php wp_nonce_field('ipv_create_license'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Plan</th>
                        <td>
                            <select name="plan" required>
                                <option value="trial">Trial (10 credits)</option>
                                <option value="starter">Starter (50 credits/month)</option>
                                <option value="professional">Professional (100 credits/month)</option>
                                <option value="business">Business (500 credits/month)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Domain</th>
                        <td><input type="text" name="domain" class="regular-text" placeholder="example.com" required></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><input type="email" name="email" class="regular-text"></td>
                    </tr>
                </table>
                
                <p><input type="submit" class="button button-primary" value="Create License"></p>
            </form>
            
            <h2>All Licenses</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Domain</th>
                        <th>Plan</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licenses as $lic): ?>
                    <tr>
                        <td><code><?php echo esc_html($lic->license_key); ?></code></td>
                        <td><?php echo esc_html($lic->domain); ?></td>
                        <td><?php echo esc_html($lic->plan); ?></td>
                        <td><?php echo $lic->credits_used . ' / ' . $lic->credits_total; ?></td>
                        <td><?php echo esc_html($lic->status); ?></td>
                        <td><?php echo esc_html($lic->created_at); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            update_option('ipv_vendor_youtube_api_key', sanitize_text_field($_POST['youtube_api_key']));
            update_option('ipv_vendor_supadata_key', sanitize_text_field($_POST['supadata_api_key']));
            update_option('ipv_vendor_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $youtube = get_option('ipv_vendor_youtube_api_key', '');
        $supadata = get_option('ipv_vendor_supadata_key', '');
        $openai = get_option('ipv_vendor_openai_api_key', '');
        
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            
            <form method="post" style="background: white; padding: 20px; max-width: 800px;">
                <h2>API Keys</h2>
                
                <table class="form-table">
                    <tr>
                        <th>YouTube Data API Key</th>
                        <td><input type="text" name="youtube_api_key" value="<?php echo esc_attr($youtube); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>SupaData API Key</th>
                        <td><input type="text" name="supadata_api_key" value="<?php echo esc_attr($supadata); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="openai_api_key" value="<?php echo esc_attr($openai); ?>" class="regular-text"></td>
                    </tr>
                </table>
                
                <p><input type="submit" name="save_settings" class="button button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }
    
    public function handle_create_license() {
        check_admin_referer('ipv_create_license');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $credits_map = [
            'trial' => 10,
            'starter' => 50,
            'professional' => 100,
            'business' => 500
        ];
        
        $plan = sanitize_text_field($_POST['plan']);
        
        $manager = IPV_Vendor_License_Manager::get_instance();
        $license = $manager->create([
            'plan' => $plan,
            'domain' => sanitize_text_field($_POST['domain']),
            'credits_total' => $credits_map[$plan] ?? 10,
            'customer_email' => sanitize_email($_POST['email'])
        ]);
        
        if ($license) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-licenses&created=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-licenses&error=1'));
        }
        exit;
    }
    
    // ═══════════════════════════════════════════════════════════════════
    // GOLDEN PROMPT MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════
    
    /**
     * Golden Prompt Page
     */
    public function golden_prompt_page() {
        // Handle form submissions
        $message = '';
        $message_type = '';
        
        if (isset($_GET['created'])) {
            $message = '✅ Licenza Golden Prompt creata con successo!';
            $message_type = 'success';
        } elseif (isset($_GET['deleted'])) {
            $message = '✅ Licenza eliminata con successo!';
            $message_type = 'success';
        } elseif (isset($_GET['updated'])) {
            $message = '✅ Golden Prompt aggiornato con successo!';
            $message_type = 'success';
        } elseif (isset($_GET['error'])) {
            $message = '❌ Errore durante l\'operazione!';
            $message_type = 'error';
        }
        
        // Get stats
        $stats = IPV_Vendor_Golden_Prompt_Manager::get_stats();
        
        // Get all licenses
        $licenses = IPV_Vendor_Golden_Prompt_Manager::get_all_licenses();
        
        ?>
        <div class="wrap">
            <h1>🌟 Golden Prompt Management</h1>
            <p>Gestisci licenze e contenuto Golden Prompt</p>
            
            <?php if ($message) : ?>
                <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
                <div style="background: #fff; padding: 20px; border-left: 4px solid #10b981;">
                    <h3 style="margin: 0; color: #10b981;">📊 Totale Licenze</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo $stats['total']; ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #3b82f6;">
                    <h3 style="margin: 0; color: #3b82f6;">✅ Attive</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo $stats['active']; ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #fbbf24;">
                    <h3 style="margin: 0; color: #fbbf24;">🌟 GP Enabled</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo $stats['enabled']; ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border-left: 4px solid #ef4444;">
                    <h3 style="margin: 0; color: #ef4444;">❌ Scadute</h3>
                    <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo $stats['expired']; ?></p>
                </div>
            </div>
            
            <!-- Create New License -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2>➕ Crea Nuova Licenza Golden Prompt</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="ipv_create_golden_prompt_license">
                    <?php wp_nonce_field('ipv_create_golden_prompt_license'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="domain">Dominio Cliente *</label></th>
                            <td><input type="text" name="domain" id="domain" class="regular-text" placeholder="cliente.com" required></td>
                        </tr>
                        <tr>
                            <th><label for="email">Email Cliente</label></th>
                            <td><input type="email" name="email" id="email" class="regular-text" placeholder="cliente@email.com"></td>
                        </tr>
                        <tr>
                            <th><label for="golden_prompt">Golden Prompt Content *</label></th>
                            <td>
                                <textarea name="golden_prompt" id="golden_prompt" rows="10" class="large-text" placeholder="Incolla qui il contenuto del Golden Prompt..." required></textarea>
                                <p class="description">Questo contenuto verrà criptato e inviato al client quando richiesto.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="expires_at">Scadenza (Opzionale)</label></th>
                            <td>
                                <input type="date" name="expires_at" id="expires_at" class="regular-text">
                                <p class="description">Lascia vuoto per licenza senza scadenza.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="has_golden_prompt">🌟 Golden Prompt Attivo</label></th>
                            <td>
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="has_golden_prompt" id="has_golden_prompt" value="1" checked>
                                    <span>Abilita Golden Prompt per questa licenza</span>
                                </label>
                                <p class="description">
                                    Se disabilitato, il cliente NON vedrà i campi Golden Prompt anche se ha la licenza valida.
                                    Puoi attivarlo/disattivarlo in qualsiasi momento con lo switch nella tabella.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            🌟 Genera Licenza Golden Prompt
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Licenses List -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2>📋 Licenze Golden Prompt</h2>
                
                <?php if (empty($licenses)) : ?>
                    <p>Nessuna licenza Golden Prompt trovata.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Dominio</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>🌟 Golden Prompt</th>
                                <th>Scadenza</th>
                                <th>Creata</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($licenses as $license) : ?>
                                <tr>
                                    <td><code><?php echo esc_html($license->license_key); ?></code></td>
                                    <td><?php echo esc_html($license->domain); ?></td>
                                    <td><?php echo esc_html($license->customer_email); ?></td>
                                    <td>
                                        <?php
                                        $status_colors = ['active' => 'green', 'expired' => 'red', 'suspended' => 'orange'];
                                        $color = $status_colors[$license->status] ?? 'gray';
                                        ?>
                                        <span style="background: <?php echo $color; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo strtoupper($license->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- ✅ TOGGLE SWITCH -->
                                        <label class="ipv-toggle-switch" style="position: relative; display: inline-block; width: 50px; height: 24px;">
                                            <input type="checkbox" 
                                                   class="ipv-golden-toggle"
                                                   data-license="<?php echo esc_attr($license->license_key); ?>"
                                                   <?php checked($license->has_golden_prompt, 1); ?>
                                                   style="opacity: 0; width: 0; height: 0;">
                                            <span class="ipv-toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $license->has_golden_prompt ? '#4caf50' : '#ccc'; ?>; transition: 0.3s; border-radius: 24px;">
                                                <span style="position: absolute; content: ''; height: 18px; width: 18px; left: <?php echo $license->has_golden_prompt ? '28px' : '3px'; ?>; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%;"></span>
                                            </span>
                                        </label>
                                        <span style="margin-left: 8px; font-size: 11px; font-weight: bold; color: <?php echo $license->has_golden_prompt ? '#4caf50' : '#999'; ?>;">
                                            <?php echo $license->has_golden_prompt ? 'ON' : 'OFF'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $license->expires_at ? date('Y-m-d', strtotime($license->expires_at)) : 'Mai'; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($license->created_at)); ?></td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="toggleGoldenPrompt('<?php echo $license->license_key; ?>')">
                                            👁️ View Content
                                        </button>
                                        
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                            <input type="hidden" name="action" value="ipv_delete_golden_prompt_license">
                                            <input type="hidden" name="license_key" value="<?php echo esc_attr($license->license_key); ?>">
                                            <?php wp_nonce_field('ipv_delete_golden_prompt_license'); ?>
                                            <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Eliminare questa licenza?')">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                        
                                        <div id="prompt-<?php echo $license->license_key; ?>" style="display: none; margin-top: 10px; background: #f0f0f0; padding: 10px; border-radius: 4px;">
                                            <pre style="white-space: pre-wrap; font-size: 11px;"><?php 
                                                $content = IPV_Vendor_Golden_Prompt_Manager::fetch_golden_prompt($license->license_key);
                                                echo esc_html(substr($content, 0, 500) . (strlen($content) > 500 ? '...' : ''));
                                            ?></pre>
                                            
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 10px;">
                                                <input type="hidden" name="action" value="ipv_update_golden_prompt_content">
                                                <input type="hidden" name="license_key" value="<?php echo esc_attr($license->license_key); ?>">
                                                <?php wp_nonce_field('ipv_update_golden_prompt_content'); ?>
                                                <textarea name="new_content" rows="8" class="large-text"><?php echo esc_textarea($content); ?></textarea>
                                                <br>
                                                <button type="submit" class="button button-primary button-small" style="margin-top: 5px;">
                                                    💾 Update Content
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        function toggleGoldenPrompt(licenseKey) {
            var el = document.getElementById('prompt-' + licenseKey);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
        
        // ✅ TOGGLE SWITCH AJAX
        jQuery(document).ready(function($) {
            $('.ipv-golden-toggle').on('change', function() {
                var checkbox = $(this);
                var licenseKey = checkbox.data('license');
                var enabled = checkbox.is(':checked') ? 1 : 0;
                var slider = checkbox.next('.ipv-toggle-slider');
                var label = checkbox.closest('td').find('span:last');
                
                // Disable durante call
                checkbox.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_toggle_golden_prompt',
                        nonce: '<?php echo wp_create_nonce('ipv_toggle_golden_prompt'); ?>',
                        license_key: licenseKey,
                        enabled: enabled
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update UI
                            var color = enabled ? '#4caf50' : '#ccc';
                            var left = enabled ? '28px' : '3px';
                            var textColor = enabled ? '#4caf50' : '#999';
                            var text = enabled ? 'ON' : 'OFF';
                            
                            slider.css('background-color', color);
                            slider.find('span').css('left', left);
                            label.text(text).css('color', textColor);
                            
                            // Toast notification
                            var message = enabled ? '✅ Golden Prompt ENABLED' : '⚪ Golden Prompt DISABLED';
                            showToast(message);
                        } else {
                            // Revert checkbox
                            checkbox.prop('checked', !enabled);
                            alert('Errore: ' + (response.data?.message || 'Operazione fallita'));
                        }
                        checkbox.prop('disabled', false);
                    },
                    error: function() {
                        checkbox.prop('checked', !enabled);
                        checkbox.prop('disabled', false);
                        alert('Errore di connessione');
                    }
                });
            });
            
            function showToast(message) {
                var toast = $('<div>')
                    .text(message)
                    .css({
                        position: 'fixed',
                        bottom: '20px',
                        right: '20px',
                        background: '#323232',
                        color: 'white',
                        padding: '12px 24px',
                        borderRadius: '4px',
                        zIndex: 9999,
                        fontSize: '14px',
                        boxShadow: '0 4px 6px rgba(0,0,0,0.3)'
                    });
                    
                $('body').append(toast);
                
                setTimeout(function() {
                    toast.fadeOut(function() {
                        toast.remove();
                    });
                }, 3000);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Handle Create Golden Prompt License
     */
    public function handle_create_golden_prompt_license() {
        check_admin_referer('ipv_create_golden_prompt_license');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $domain = sanitize_text_field($_POST['domain']);
        $email = sanitize_email($_POST['email']);
        $golden_prompt = wp_unslash($_POST['golden_prompt']); // Preserve formatting
        $expires_at = !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null;
        $has_golden_prompt = isset($_POST['has_golden_prompt']) ? 1 : 0;
        
        $license_key = IPV_Vendor_Golden_Prompt_Manager::generate_license(
            $domain,
            $email,
            $golden_prompt,
            $expires_at,
            $has_golden_prompt
        );
        
        if ($license_key) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt&created=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt&error=1'));
        }
        exit;
    }
    
    /**
     * Handle Delete Golden Prompt License
     */
    public function handle_delete_golden_prompt_license() {
        check_admin_referer('ipv_delete_golden_prompt_license');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        $result = IPV_Vendor_Golden_Prompt_Manager::delete_license($license_key);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt&deleted=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt&error=1'));
        }
        exit;
    }
    
    /**
     * Handle Update Golden Prompt Content
     */
    public function handle_update_golden_prompt_content() {
        check_admin_referer('ipv_update_golden_prompt_content');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        $new_content = wp_unslash($_POST['new_content']); // Preserve formatting
        
        $result = IPV_Vendor_Golden_Prompt_Manager::update_golden_prompt_content($license_key, $new_content);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt&updated=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt&error=1'));
        }
        exit;
    }
    
    // ═══════════════════════════════════════════════════════════════════
    // GOLDEN PROMPT MASTER TEMPLATE
    // ═══════════════════════════════════════════════════════════════════
    
    /**
     * Golden Prompt Master Template Page
     */
    public function golden_prompt_master_page() {
        // Handle messages
        $message = '';
        $message_type = '';
        
        if (isset($_GET['saved'])) {
            $message = '✅ Master Template salvato con successo! (Version: ' . sanitize_text_field($_GET['version']) . ')';
            $message_type = 'success';
        } elseif (isset($_GET['pushed'])) {
            $count = intval($_GET['count']);
            $message = "✅ Master Template pushato a {$count} licenze attive!";
            $message_type = 'success';
        } elseif (isset($_GET['restored'])) {
            $message = '✅ Versione ripristinata con successo!';
            $message_type = 'success';
        } elseif (isset($_GET['error'])) {
            $message = '❌ Errore durante l\'operazione!';
            $message_type = 'error';
        }
        
        // Get stats
        $master_stats = IPV_Vendor_Golden_Prompt_Manager::get_master_stats();
        $license_stats = IPV_Vendor_Golden_Prompt_Manager::get_stats();
        $master = IPV_Vendor_Golden_Prompt_Manager::get_master_template();
        $history = IPV_Vendor_Golden_Prompt_Manager::get_master_history(10);
        
        ?>
        <div class="wrap">
            <h1>📋 Golden Prompt Master Template</h1>
            <p>Gestisci il template centrale e pushalo a tutte le licenze attive con un click!</p>
            
            <?php if ($message) : ?>
                <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Stats Dashboard -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
                    <h3 style="margin: 0; opacity: 0.9; font-size: 14px;">📋 Versione Corrente</h3>
                    <p style="font-size: 28px; font-weight: bold; margin: 10px 0 0 0;">
                        <?php echo esc_html($master_stats['current_version']); ?>
                    </p>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; color: white;">
                    <h3 style="margin: 0; opacity: 0.9; font-size: 14px;">📚 Versioni Totali</h3>
                    <p style="font-size: 28px; font-weight: bold; margin: 10px 0 0 0;">
                        <?php echo $master_stats['total_versions']; ?>
                    </p>
                </div>
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 8px; color: white;">
                    <h3 style="margin: 0; opacity: 0.9; font-size: 14px;">✅ Licenze Attive</h3>
                    <p style="font-size: 28px; font-weight: bold; margin: 10px 0 0 0;">
                        <?php echo $license_stats['active']; ?>
                    </p>
                </div>
                <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 20px; border-radius: 8px; color: white;">
                    <h3 style="margin: 0; opacity: 0.9; font-size: 14px;">⏱️ Ultimo Aggiornamento</h3>
                    <p style="font-size: 14px; font-weight: bold; margin: 10px 0 0 0;">
                        <?php echo $master_stats['last_updated'] ? date('Y-m-d H:i', strtotime($master_stats['last_updated'])) : 'N/A'; ?>
                    </p>
                </div>
            </div>
            
            <!-- Current Master Template -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">📄 Master Template Corrente</h2>
                    <div>
                        <?php if ($master) : ?>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <input type="hidden" name="action" value="ipv_download_master_template">
                                <?php wp_nonce_field('ipv_download_master_template'); ?>
                                <button type="submit" class="button button-secondary">
                                    📥 Download Master (.txt)
                                </button>
                            </form>
                            
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline; margin-left: 10px;">
                                <input type="hidden" name="action" value="ipv_push_master_to_all">
                                <?php wp_nonce_field('ipv_push_master_to_all'); ?>
                                <button type="submit" class="button button-primary" 
                                        onclick="return confirm('Pushare Master Template alle <?php echo $license_stats['enabled']; ?> licenze con Golden Prompt ENABLED?')">
                                    🚀 Push to All GP Enabled (<?php echo $license_stats['enabled']; ?>)
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($master) : ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <div style="display: flex; gap: 20px; font-size: 13px;">
                            <div><strong>Version:</strong> <?php echo esc_html($master->version); ?></div>
                            <div><strong>Created:</strong> <?php echo date('Y-m-d H:i', strtotime($master->created_at)); ?></div>
                            <div><strong>By:</strong> <?php echo esc_html($master->created_by ?: 'Admin'); ?></div>
                            <div><strong>Size:</strong> <?php echo number_format(strlen($master->content)); ?> chars</div>
                        </div>
                    </div>
                    
                    <pre style="background: #2d2d2d; color: #f8f8f2; padding: 20px; border-radius: 4px; overflow: auto; max-height: 400px; font-size: 12px; line-height: 1.6;"><?php echo esc_html($master->content); ?></pre>
                <?php else : ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 4px; text-align: center;">
                        <p style="margin: 0; color: #856404;">
                            ⚠️ Nessun Master Template trovato! Carica il primo template qui sotto.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Upload New Master Template -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2>📤 Upload Nuovo Master Template</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="ipv_save_master_template">
                    <?php wp_nonce_field('ipv_save_master_template'); ?>
                    
                    <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin-bottom: 20px;">
                        <p style="margin: 0; color: #1565c0;">
                            <strong>💡 Info:</strong> Questo diventerà il template centrale. 
                            Puoi poi pusharlo a tutte le licenze attive con 1 click!
                            Il vecchio template verrà salvato nella history.
                        </p>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="master_content">Golden Prompt Content *</label></th>
                            <td>
                                <textarea name="master_content" id="master_content" rows="15" class="large-text code" 
                                          placeholder="Incolla qui il contenuto del Golden Prompt Master Template...
                                          
Esempio con placeholder:
{NOME_CANALE}
{HANDLE}
{LINK_YOUTUBE}
..." required><?php echo $master ? esc_textarea($master->content) : ''; ?></textarea>
                                <p class="description">
                                    Questo template supporta tutti i placeholder: {NOME_CANALE}, {HANDLE}, {NICCHIA}, 
                                    {LINK_YOUTUBE}, {LINK_TELEGRAM}, etc.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            💾 Salva Come Nuova Versione Master
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Version History -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2>📚 Version History</h2>
                
                <?php if (empty($history)) : ?>
                    <p>Nessuna versione trovata.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Size</th>
                                <th>Preview</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $version) : ?>
                                <tr style="<?php echo $version->is_active ? 'background: #e8f5e9;' : ''; ?>">
                                    <td>
                                        <strong><?php echo esc_html($version->version); ?></strong>
                                        <?php if ($version->is_active) : ?>
                                            <span style="background: #4caf50; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">ACTIVE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $version->is_active ? '✅ Active' : '⚪ Archived'; ?></td>
                                    <td><?php echo esc_html($version->created_by ?: 'Admin'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($version->created_at)); ?></td>
                                    <td><?php echo number_format(strlen($version->content)); ?> chars</td>
                                    <td>
                                        <button type="button" class="button button-small" 
                                                onclick="toggleVersion('<?php echo $version->id; ?>')">
                                            👁️ View
                                        </button>
                                    </td>
                                    <td>
                                        <?php if (!$version->is_active) : ?>
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                                <input type="hidden" name="action" value="ipv_restore_master_version">
                                                <input type="hidden" name="version" value="<?php echo esc_attr($version->version); ?>">
                                                <?php wp_nonce_field('ipv_restore_master_version'); ?>
                                                <button type="submit" class="button button-small" 
                                                        onclick="return confirm('Ripristinare questa versione come active?')">
                                                    🔄 Restore
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr id="version-<?php echo $version->id; ?>" style="display: none;">
                                    <td colspan="7">
                                        <pre style="background: #f5f5f5; padding: 15px; overflow: auto; max-height: 300px; font-size: 11px;"><?php echo esc_html($version->content); ?></pre>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        function toggleVersion(id) {
            var el = document.getElementById('version-' + id);
            el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
        }
        </script>
        <?php
    }
    
    /**
     * Handle Save Master Template
     */
    public function handle_save_master_template() {
        check_admin_referer('ipv_save_master_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $content = wp_unslash($_POST['master_content']); // Preserve formatting
        $current_user = wp_get_current_user();
        $created_by = $current_user->display_name;
        
        $version = IPV_Vendor_Golden_Prompt_Manager::save_master_template($content, $created_by);
        
        if ($version) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt-master&saved=1&version=' . urlencode($version)));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt-master&error=1'));
        }
        exit;
    }
    
    /**
     * Handle Push Master to All
     */
    public function handle_push_master_to_all() {
        check_admin_referer('ipv_push_master_to_all');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $count = IPV_Vendor_Golden_Prompt_Manager::push_master_to_all_licenses();
        
        if ($count !== false) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt-master&pushed=1&count=' . $count));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt-master&error=1'));
        }
        exit;
    }
    
    /**
     * Handle Restore Master Version
     */
    public function handle_restore_master_version() {
        check_admin_referer('ipv_restore_master_version');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $version = sanitize_text_field($_POST['version']);
        
        $result = IPV_Vendor_Golden_Prompt_Manager::restore_master_version($version);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt-master&restored=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=ipv-vendor-golden-prompt-master&error=1'));
        }
        exit;
    }
    
    /**
     * Handle Download Master Template
     */
    public function handle_download_master_template() {
        check_admin_referer('ipv_download_master_template');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $master = IPV_Vendor_Golden_Prompt_Manager::get_master_template();
        
        if (!$master) {
            wp_die('No master template found');
        }
        
        $filename = 'golden-prompt-master-' . $master->version . '.txt';
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($master->content));
        
        echo $master->content;
        exit;
    }
    
    /**
     * ✅ AJAX Handler: Toggle Golden Prompt per singola licenza
     */
    public function ajax_toggle_golden_prompt() {
        check_ajax_referer('ipv_toggle_golden_prompt', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        $enabled = intval($_POST['enabled'] ?? 0);
        
        if (empty($license_key)) {
            wp_send_json_error(['message' => 'License key required']);
        }
        
        $result = IPV_Vendor_Golden_Prompt_Manager::toggle_golden_prompt($license_key, $enabled);
        
        if ($result) {
            $status = $enabled ? 'ENABLED' : 'DISABLED';
            wp_send_json_success([
                'message' => "Golden Prompt {$status}",
                'enabled' => $enabled,
                'license_key' => $license_key
            ]);
        } else {
            wp_send_json_error(['message' => 'Toggle failed']);
        }
    }
}
