<?php
/*
Plugin Name: Choyal Custom Chat Plugin
Description: A simple chat plugin for web admin and customers.
Version: 1.4
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Choyal_Custom_Chat_Plugin {
    private static $instance = null;

    private function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'choyal_create_chat_table'));

        // Add shortcode
        add_shortcode('choyal_chat_box', array($this, 'choyal_chat_box_shortcode'));

        // Handle AJAX requests
        add_action('wp_ajax_choyal_send_chat', array($this, 'choyal_handle_chat_ajax'));
        add_action('wp_ajax_nopriv_choyal_send_chat', array($this, 'choyal_handle_chat_ajax'));
        add_action('wp_ajax_choyal_get_chats', array($this, 'choyal_handle_chat_ajax'));
        add_action('wp_ajax_nopriv_choyal_get_chats', array($this, 'choyal_handle_chat_ajax'));
        add_action('wp_ajax_choyal_get_admin_status', array($this, 'choyal_get_admin_status'));
        add_action('wp_ajax_nopriv_choyal_get_admin_status', array($this, 'choyal_get_admin_status'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'choyal_enqueue_chat_script'));

        // Check admin status
        add_action('init', array($this, 'choyal_update_admin_status'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function choyal_create_chat_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . "choyal_chat_messages";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            admin_id mediumint(9) NOT NULL,
            guest_name varchar(255) DEFAULT '' NOT NULL,
            guest_email varchar(255) DEFAULT '' NOT NULL,
            guest_website varchar(255) DEFAULT '' NOT NULL,
            message text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function choyal_chat_box_shortcode() {
        ob_start();
        ?>
        <div id="choyal-chat-box" class="choyal-chat-box">
            <div id="choyal-chat-header" class="choyal-chat-header">
                <span>Chat with Us</span>
                <button id="choyal-chat-toggle" class="choyal-chat-toggle">-</button>
            </div>
            <div id="choyal-chat-content" class="choyal-chat-content">
                <?php if (is_user_logged_in()): ?>
                    <div id="choyal-chat-messages" class="choyal-chat-messages"></div>
                    <textarea id="choyal-chat-input" class="choyal-chat-input" placeholder="Type your message here..."></textarea>
                    <button id="choyal-chat-send" class="choyal-chat-send">Send</button>
                <?php else: ?>
                    <form id="choyal-guest-form" class="choyal-guest-form">
                        <input type="text" id="choyal-guest-name" class="choyal-guest-input" placeholder="Your Name" required>
                        <input type="email" id="choyal-guest-email" class="choyal-guest-input" placeholder="Your Email" required>
                        <input type="text" id="choyal-guest-website" class="choyal-guest-input" placeholder="Your Business Website" required>
                        <button type="submit" class="choyal-guest-submit">Start Chat</button>
                    </form>
                    <div id="choyal-guest-chat" class="choyal-guest-chat" style="display:none;">
                        <div id="choyal-chat-status" class="choyal-chat-status">
                            <span id="choyal-admin-status"><div class="choyal-status-dot choyal-status-gray"></div> Connecting to agent...</span>
                        </div>
                        <div id="choyal-chat-messages" class="choyal-chat-messages"></div>
                        <textarea id="choyal-chat-input" class="choyal-chat-input" placeholder="Type your message here..."></textarea>
                        <button id="choyal-chat-send" class="choyal-chat-send">Send</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .choyal-chat-box {
                position: fixed;
                bottom: 0;
                right: 20px;
                width: 300px;
                border: 1px solid #ccc;
                background: #fff;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                border-radius: 10px 10px 0 0;
                overflow: hidden;
            }
            .choyal-chat-header {
                background: #0073aa;
                color: #fff;
                padding: 10px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .choyal-chat-toggle {
                background: none;
                border: none;
                color: #fff;
                font-size: 16px;
                cursor: pointer;
            }
            .choyal-chat-content {
                display: none;
                padding: 10px;
            }
            .choyal-chat-messages {
                height: 200px;
                overflow-y: scroll;
                border: 1px solid #ccc;
                padding: 5px;
                margin-bottom: 10px;
            }
            .choyal-chat-input {
                width: 100%;
                padding: 10px;
                margin-bottom: 10px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            .choyal-chat-send {
                width: 100%;
                padding: 10px;
                background: #0073aa;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            .choyal-guest-form {
                display: flex;
                flex-direction: column;
            }
            .choyal-guest-input {
                width: 100%;
                padding: 10px;
                margin-bottom: 10px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            .choyal-guest-submit {
                padding: 10px;
                background: #0073aa;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            .choyal-chat-status {
                margin-bottom: 10px;
                display: flex;
                align-items: center;
            }
            .choyal-status-dot {
                height: 10px;
                width: 10px;
                border-radius: 50%;
                display: inline-block;
                margin-right: 5px;
            }
            .choyal-status-gray {
                background-color: gray;
            }
            .choyal-status-yellow {
                background-color: yellow;
            }
            .choyal-status-green {
                background-color: green;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    public function choyal_handle_chat_ajax() {
        global $wpdb;
        $table_name = $wpdb->prefix . "choyal_chat_messages";

        if (isset($_POST['message'])) {
            $user_id = is_user_logged_in() ? get_current_user_id() : 0;
            $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
            $guest_name = isset($_POST['guest_name']) ? sanitize_text_field($_POST['guest_name']) : '';
            $guest_email = isset($_POST['guest_email']) ? sanitize_email($_POST['guest_email']) : '';
            $guest_website = isset($_POST['guest_website']) ? sanitize_text_field($_POST['guest_website']) : '';
            $message = sanitize_text_field($_POST['message']);

            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'admin_id' => $admin_id,
                'guest_name' => $guest_name,
                'guest_email' => $guest_email,
                'guest_website' => $guest_website,
                'message' => $message
            ]);

            // Send email notification to admin if it's a new chat
            if ($admin_id == 0 && !is_user_logged_in()) {
                $admin_email = 'noreply.developer.web@gmail.com';
                $subject = 'New Chat Started on Your Website';
                $body = "A new user has started a chat:\n\nName: $guest_name\nEmail: $guest_email\nWebsite: $guest_website\n\nPlease login to your website to respond.";
                wp_mail($admin_email, $subject, $body);
            }

            wp_send_json_success();
        } elseif (isset($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d OR admin_id = %d ORDER BY timestamp ASC", $user_id, $user_id));
            wp_send_json_success($messages);
        } elseif (isset($_POST['guest_email'])) {
            $guest_email = sanitize_email($_POST['guest_email']);
            $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE guest_email = %s ORDER BY timestamp ASC", $guest_email));
            wp_send_json_success($messages);
        } else {
            wp_send_json_error('User ID or Guest Email not provided');
        }
    }

    public function choyal_get_admin_status() {
        $online_status = get_option('choyal_admin_online_status', 'offline');
        wp_send_json_success(['status' => $online_status]);
    }

    public function choyal_update_admin_status() {
        if (is_user_logged_in() && current_user_can('administrator')) {
            update_option('choyal_admin_online_status', 'online');
        } else {
            update_option('choyal_admin_online_status', 'offline');
        }
    }

    public function choyal_enqueue_chat_script() {
        wp_enqueue_script('choyal-chat-script', plugins_url('/choyal-chat-script.js', __FILE__), ['jquery'], null, true);
        wp_localize_script('choyal-chat-script', 'choyalChatAjax', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
}

function choyal_custom_chat_plugin() {
    return Choyal_Custom_Chat_Plugin::get_instance();
}

// Initialize the plugin
choyal_custom_chat_plugin();