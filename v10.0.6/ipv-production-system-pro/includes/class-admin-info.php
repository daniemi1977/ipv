<?php
if (!defined('ABSPATH')) exit;

class IPV_Admin_Info {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
    }

    public static function menu() {
        add_submenu_page(
            'ipv-production-system',
            'IPV Info',
            'What\'s New 4.5',
            'manage_options',
            'ipv-info',
            [__CLASS__, 'render']
        );
    }

    public static function render() {
        echo '<div class="wrap"><h1>IPV Production System Pro – Version 4.5</h1>';
        echo '<p>This update introduces major improvements including:</p>';
        echo '<ul>
                <li><strong>Prompt Gold AI Integration</strong></li>
                <li><strong>Markdown + Notion Renderer</strong></li>
                <li><strong>Automatic filtering of videos shorter than 5 minutes</strong></li>
                <li><strong>New CPT Template</strong></li>
                <li><strong>Stability and performance improvements</strong></li>
              </ul>';
        echo '<p>See <strong>CHANGELOG.md</strong> for full details.</p>';
        echo '</div>';
    }
}
IPV_Admin_Info::init();
