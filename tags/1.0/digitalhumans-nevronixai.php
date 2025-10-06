<?php
/** 
 * Plugin Name:       Digital Humans - NevronixAI
 * Plugin URI:        https://nevronix.ai/
 * Description:       Leading Digital Human Platform that Puts a Face to AI. Powered by the NevronixAI team.
 * Version:           1.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// NevronixAI plugin version
define( 'NEVRONIXAI_PLUGIN_VERSION', '1.0' );

// Register activation hook to initialize settings.
register_activation_hook(__FILE__, 'nevronixai_plugin_activation_flow');
function nevronixai_plugin_activation_flow() {
    // Clear any existing welcome flag
    delete_option('nevronixai_welcome_shown');
    
    // Set transient to show welcome screen (5 minute window)
    set_transient('nevronixai_show_welcome', true, 300);
    
    // Your existing settings initialization
    add_option('nevronixai_plugin_settings', array(
        'api_url' => '',
        'iframe_width' => '340',
        'iframe_height' => '340',
        'show_after_seconds' => '1',
        'selected_items' => array(),
        'button_bg_color' => '#ffffff',
        'button_text' => 'Hi! Click here to start a conversation',
        'button_text_color' => '#000000',
        'position' => 'bottom-right'
    ));
}

// Register deactivation hook to clean up settings.
register_deactivation_hook(__FILE__, 'nevronixai_plugin_deactivate');
function nevronixai_plugin_deactivate() {

    delete_option('nevronixai_welcome_shown');
    delete_option('nevronixai_plugin_settings');
}

// 2. Add this new function to handle the redirect:
add_action('admin_menu', 'nevronixai_check_welcome_redirect', 5);
function nevronixai_check_welcome_redirect() {
    // Only proceed if our transient exists and we haven't shown welcome yet
    if (!get_transient('nevronixai_show_welcome') || get_option('nevronixai_welcome_shown')) {
        return;
    }
    
    // Delete transient and set flag
    delete_transient('nevronixai_show_welcome');
    update_option('nevronixai_welcome_shown', true);
    
    // Only for admins and not already on our page
    if (!current_user_can('manage_options') || (isset($_GET['page']) && $_GET['page'] === 'nevronixai-plugin')) {
        return;
    }
    
    // Redirect to welcome screen
    wp_safe_redirect(admin_url('admin.php?page=nevronixai-plugin&welcome=1'));
    exit;
}




// Add a menu item in the admin dashboard.
add_action('admin_menu', 'nevronixai_plugin_menu');
function nevronixai_plugin_menu() {
    add_menu_page(
        'Digital Humans - NevronixAI Settings', // Page title (shown on top of the admin page)
        'Digital Humans - NevronixAI',                       // Menu name (shown in sidebar)
        'manage_options',                       // Capability
        'nevronixai-plugin',                    // Slug
        'nevronixai_plugin_settings_page',      // Callback to render content
        plugin_dir_url(__FILE__) . 'img/favicon.png', // 👈 custom image icon (PNG/SVG)
        56                                      // Position in sidebar (lower is higher up)
    );
    add_options_page('Digital Humans - NevronixAI Settings', 'Digital Humans - NevronixAI', 'manage_options', 'nevronixai-plugin', 'nevronixai_plugin_settings_page');
}



// Enqueue admin styles for the settings page
add_action('admin_enqueue_scripts', 'nevronixai_plugin_admin_enqueue_styles');
function nevronixai_plugin_admin_enqueue_styles($hook) {
    // Check if we're on the plugin's settings page
    if ($hook != 'settings_page_nevronixai-plugin' &&         $hook !== 'toplevel_page_nevronixai-plugin'
) {
        return;
    }

    // Enqueue the style
    wp_register_style('nevronixai-plugin-admin-style', false, array(), NEVRONIXAI_PLUGIN_VERSION);
    wp_enqueue_style('nevronixai-plugin-admin-style');

    $custom_css = '
        .nevronix-logo-container {
            position: absolute;
        }
        .nevronix-logo {
            width: 200px;
        }
        /* Added CSS for jQuery UI Tabs */
        #nevronixai-tabs { margin-top: 20px; }
        #nevronixai-tabs ul { list-style: none; margin: 0; padding: 0; }
        #nevronixai-tabs ul li { display: inline-block; margin-right: 5px; }
        #nevronixai-tabs ul li a { padding: 10px 15px; background: #eee; border: 1px solid #ccc; border-bottom: none; text-decoration: none; }
        #nevronixai-tabs .ui-tabs-active a { background: #fff; border-bottom: 1px solid #fff; }
        .tab-content { padding: 20px; border: 1px solid #ccc; background: #fff; }
    ';
    wp_add_inline_style('nevronixai-plugin-admin-style', $custom_css);

    // Enqueue jQuery UI Tabs and initialize the tabs
    wp_enqueue_script('jquery-ui-tabs');
      $custom_js = '
            jQuery(document).ready(function($) {
                var activeTab = localStorage.getItem("nevronixaiActiveTab");
                if (activeTab) {
                    $("#nevronixai-tabs").tabs({ active: parseInt(activeTab) });
                } else {
                    $("#nevronixai-tabs").tabs();
                }

                $("#nevronixai-tabs").on("tabsactivate", function(event, ui) {
                    localStorage.setItem("nevronixaiActiveTab", ui.newTab.index());
                });

                // Ensure smooth scroll remains disabled after reload
                $("form").submit(function() {
                    localStorage.setItem("nevronixaiActiveTab", $("#nevronixai-tabs").tabs("option", "active"));
                });
            });
        ';
        wp_add_inline_script("jquery-ui-tabs", $custom_js);


        // Enqueue the AJAX script
    wp_enqueue_script('nevronixai-ajax-handler', plugin_dir_url(__FILE__) . 'js/nevronixai-ajax.js', array('jquery'), NEVRONIXAI_PLUGIN_VERSION, true);
    
   
}

// Display the settings page.
function nevronixai_plugin_settings_page() {

    // Check if this is the welcome screen
    if (isset($_GET['welcome']) && $_GET['welcome'] == '1') {
        ?>
        <div class="wrap" style="max-width: 800px; margin: 0 auto;">
            <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'img/logo.png'); ?>" alt="NevronixAI" style="max-height: 80px;">
                    <h1>Thank you for installing our plugin. We have a special gift for you.</h1>
                    
                </div>
                
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem;">
                    <h2 style="margin-top: 0;">Activate your plugin TODAY and get 1500 FREE minutes </h2>
                    
               <form id="nevronixai-permissions-form" method="post" action="https://nevronix.ai/activate-credit.php" >
                    <!-- Remove the hidden action and nonce fields since we're submitting externally -->
                    <div style="margin: 1rem 0;">
                        <label style="display: flex; gap: 10px; margin-bottom: 1rem;">
                            <input type="checkbox" name="share_website_check" value="1" checked onclick="return false;">
                            <input type="hidden" name="share_website" value="<?php echo esc_url(home_url()); ?>">
                            <div>
                                <strong>Website address</strong><br>
                                <small style="color: #666;"><?php echo esc_url(home_url()); ?></small>
                            </div>
                        </label>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                        <button type="submit" name="permission_action" value="accept" class="button button-primary" style="background-color: #4CAF50;">
                            Accept & Get Credits
                        </button>
                       
                    </div>
                </form>

                    <div id="nevronixai-form-message" style="margin-top: 1rem; font-weight: bold;"></div>

                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <h3>Next Steps</h3>
                    <ol style="text-align: left; max-width: 500px; margin: 1rem auto;">
                        <li>Create your free account</li>
                        <li>Build your digital human</li>
                        <li>Paste your API URL in settings</li>
                    </ol>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=nevronixai-plugin')); ?>" 
                       class="button button-primary">
                        Continue to Settings
                    </a>
                </div>
            </div>
        </div>
        <?php
        return; // This stops the rest of the function from executing
    }


    if (isset($_POST['nevronixai_plugin_settings']) && check_admin_referer('nevronixai_plugin_update_options')) {
        $settings = array(
            'api_url' => isset($_POST['nevronixai_plugin_settings']['api_url']) 
                ? sanitize_text_field(wp_unslash($_POST['nevronixai_plugin_settings']['api_url'])) 
                : '',
            'iframe_width' => isset($_POST['nevronixai_plugin_settings']['iframe_width']) 
                ? absint(wp_unslash($_POST['nevronixai_plugin_settings']['iframe_width'])) 
                : 0,
            'iframe_height' => isset($_POST['nevronixai_plugin_settings']['iframe_height']) 
                ? absint(wp_unslash($_POST['nevronixai_plugin_settings']['iframe_height'])) 
                : 0,
            'iframe_mobile_width' => absint($_POST['nevronixai_plugin_settings']['iframe_mobile_width'] ?? 300),
            'iframe_mobile_height' => absint($_POST['nevronixai_plugin_settings']['iframe_mobile_height'] ?? 300),
            

            'show_after_seconds' => isset($_POST['nevronixai_plugin_settings']['show_after_seconds']) 
                ? absint(wp_unslash($_POST['nevronixai_plugin_settings']['show_after_seconds'])) 
                : 0,
            'selected_items' => isset($_POST['nevronixai_plugin_settings']['selected_items']) && is_array($_POST['nevronixai_plugin_settings']['selected_items']) 
                ? array_map('sanitize_text_field', wp_unslash($_POST['nevronixai_plugin_settings']['selected_items'])) 
                : array(),
             'button_bg_color' => !empty(trim($_POST['nevronixai_plugin_settings']['button_bg_color'] ?? '')) 
                ? sanitize_hex_color(wp_unslash($_POST['nevronixai_plugin_settings']['button_bg_color'])) 
                : '#ffffff',

            'button_text' => !empty(trim($_POST['nevronixai_plugin_settings']['button_text'] ?? '')) 
                ? sanitize_text_field(wp_unslash($_POST['nevronixai_plugin_settings']['button_text'])) 
                : 'Hi! Click here to start a conversation',

            'button_text_color' => !empty(trim($_POST['nevronixai_plugin_settings']['button_text_color'] ?? '')) 
                ? sanitize_hex_color(wp_unslash($_POST['nevronixai_plugin_settings']['button_text_color'])) 
                : '#000000',
            'position' => isset($_POST['nevronixai_plugin_settings']['position']) && in_array($_POST['nevronixai_plugin_settings']['position'], ['bottom-right', 'center', 'custom'])
                ? sanitize_text_field($_POST['nevronixai_plugin_settings']['position'])
                : 'bottom-right',            

        );

        update_option('nevronixai_plugin_settings', $settings);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $settings = get_option('nevronixai_plugin_settings');
    ?>
    <div class="wrap">
        
        <div class="nevronix-logo-container">
            <img src="<?php echo esc_url( plugin_dir_url(__FILE__) . 'img/logo.png' ); ?>" alt="Plugin Logo" class="nevronix-logo">
        </div>
        <br><br><br>
           <div style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif;">
        <h2>Welcome to NevronixAI!</h2>
        <p>
            We’re excited to have you on board!   </p>

        <h3>Getting started is simple:</h3>
        <ol>

            <li>Create your real-time Digital Assistant (Avatar). It takes less than 5 minutes!<p><a href="https://platform.nevronix.ai:3000/register" target="_blank"
           style="
             display: inline-flex;
             align-items: center;
             gap: 8px;
             background-color: #007bff;
             color: white;
             font-weight: 400;
             font-size: 0.8rem;
             padding: 14px 28px;
             border-radius: 8px;
             text-decoration: none;
             box-shadow: 0 6px 12px rgba(90,103,216,0.35);
             transition: background-color 0.3s ease, box-shadow 0.3s ease;
           "
           onmouseover="this.style.backgroundColor='#007bff'; this.style.boxShadow='0 8px 16px rgba(76,84,192,0.45)';"
           onmouseout="this.style.backgroundColor='#007bff'; this.style.boxShadow='0 6px 12px rgba(90,103,216,0.35)';"
        >Create Digital Human</a></p></li>
<li>Copy your unique API URL and paste it into the plugin’s <strong>API URL</strong> field. </li>
<li>Then, choose where you’d like your Digital Human to appear — and click <strong>Save Changes</strong>. That’s it!</li>

        </ol>

        <p>
            Need help? Our team is here for you — FREE of charge!
            <br />
            👉 <a href="https://calendly.com/chris-nevronix/30min" target="_blank" style="color: #5a67d8;">Book a meeting with us</a><br />
            📧 Or email us at <a href="mailto:hi@nevronix.ai" style="color: #5a67d8;">hi@nevronix.ai</a>

        </p><p>You can visit our website here: 
            <a href="https://nevronix.ai" target="_blank" style="color: #5a67d8; text-decoration: none;">nevronix.ai</a>
      </p>
    </div>
        <!-- Start of Tabbed Interface -->
        <div id="nevronixai-tabs">
            <ul>
                  
              
                <li><a href="#tab-integration">Settings</a></li>
            </ul>
            
    
            <!-- Tab Content for Integration (Original Settings Form) -->
            <div id="tab-integration" class="tab-content">
                <form method="post" action="">
                    <?php wp_nonce_field('nevronixai_plugin_update_options'); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">API URL</th>
                            <td>
                                <input type="text" name="nevronixai_plugin_settings[api_url]" value="<?php echo esc_attr($settings['api_url']); ?>" size="80" style="vertical-align: middle;">
                                <button type="button" 
                                        onclick="window.open('https://platform.nevronix.ai:3000/', '_blank')"
                                        style="
                                            background-color: #007bff; 
                                            border: none; 
                                            color: white; 
                                            padding: 7px 15px; 
                                            font-size: 14px; 
                                            cursor: pointer; 
                                            border-radius: 4px;
                                            margin-left: 10px;
                                            vertical-align: middle;
                                        ">
                                    GET FREE API URL
                                </button>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Width on Desktop (px)</th>
                            <td><input type="text" name="nevronixai_plugin_settings[iframe_width]" value="<?php echo esc_attr($settings['iframe_width']); ?>" size="5"></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Height on Desktop (px)</th>
                            <td><input type="text" name="nevronixai_plugin_settings[iframe_height]" value="<?php echo esc_attr($settings['iframe_height']); ?>" size="5"></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"> Width on Mobile (px)</th>
                            <td><input type="text" name="nevronixai_plugin_settings[iframe_mobile_width]" value="<?php echo esc_attr($settings['iframe_mobile_width'] ?? '300'); ?>" size="5"></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"> Height on Mobile (px)</th>
                            <td><input type="text" name="nevronixai_plugin_settings[iframe_mobile_height]" value="<?php echo esc_attr($settings['iframe_mobile_height'] ?? '300'); ?>" size="5"></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Show After (seconds)</th>
                            <td><input type="text" name="nevronixai_plugin_settings[show_after_seconds]" value="<?php echo esc_attr($settings['show_after_seconds']); ?>" size="5"></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Button Background Color</th>
                            <td>
                                <input type="color" name="nevronixai_plugin_settings[button_bg_color]" value="<?php echo esc_attr($settings['button_bg_color'] ?? '#ffffff'); ?>" />
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Button Label Text</th>
                            <td>
                                <input type="text" name="nevronixai_plugin_settings[button_text]"
                                       value="<?php echo esc_attr(!empty($settings['button_text']) ? $settings['button_text'] : 'Hi! Click here to start a conversation'); ?>"
                                       size="50" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Button Text Color</th>
                            <td>
                                <input type="color" name="nevronixai_plugin_settings[button_text_color]"
                                       value="<?php echo esc_attr(!empty($settings['button_text_color']) ? $settings['button_text_color'] : '#000000'); ?>" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Where do you want your Digital Human to appear</th>
                            <td>
                                <select name="nevronixai_plugin_settings[position]">
                                    <option value="bottom-right" <?php selected($settings['position'] ?? '', 'bottom-right'); ?>>Bottom Right (default)</option>
                                    <option value="center" <?php selected($settings['position'] ?? '', 'center'); ?>>Center of the screen</option>
                                   
                                </select>
                                <p class="description">Select where the iframe should appear on screen.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Select the pages where the plugin should be displayed:</th>
                            <td>
                                <?php
                                // Add option for the homepage
                                $selected_home = in_array('home', $settings['selected_items']) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="nevronixai_plugin_settings[selected_items][]" value="home" ' . esc_attr($selected_home) . '> Home Page</label><br>';

                                // Fetch all pages
                                $pages = get_pages();
                                foreach ($pages as $page) {
                                    $selected = in_array($page->ID, $settings['selected_items']) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="nevronixai_plugin_settings[selected_items][]" value="' . esc_attr($page->ID) . '" ' . esc_attr($selected) . '> ' . esc_html($page->post_title) . ' (Page)</label><br>';
                                }

                                // Fetch all posts
                                $posts = get_posts(array('numberposts' => -1)); // No limit
                                foreach ($posts as $post) {
                                    $selected = in_array($post->ID, $settings['selected_items']) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="nevronixai_plugin_settings[selected_items][]" value="' . esc_attr($post->ID) . '" ' . esc_attr($selected) . '> ' . esc_html($post->post_title) . ' (Post)</label><br>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Save Changes">
                    </p>
                </form>
            </div>
        </div>
        <!-- End of Tabbed Interface -->
    </div>
   
    <?php
}


// Enqueue JavaScript and CSS on the front end
add_action('wp_enqueue_scripts', 'nevronixai_plugin_enqueue_scripts');
function nevronixai_plugin_enqueue_scripts() {
    $settings = get_option('nevronixai_plugin_settings');
    $mobile_width = esc_attr($settings['iframe_mobile_width'] ?? '300');
    $mobile_height = esc_attr($settings['iframe_mobile_height'] ?? '300');
    $button_bg_color = esc_attr($settings['button_bg_color'] ?? '#ffffff');
    $button_text = esc_attr($settings['button_text'] ?? 'Hi! Click here to start a conversation');
    $button_text_color = esc_attr($settings['button_text_color'] ?? '#000000');

    // Check if the current page/post/homepage is selected for the iframe
    $display_iframe = false;
    if (is_front_page() && in_array('home', $settings['selected_items']) ||
        (is_singular() && in_array(get_the_ID(), $settings['selected_items']))) {
        $display_iframe = true;
    }

    if ($display_iframe) {
        // Enqueue CSS
        wp_register_style('nevronixai-plugin-style', false, array(), NEVRONIXAI_PLUGIN_VERSION);
        wp_enqueue_style('nevronixai-plugin-style');

        $custom_css = '

        #openIframeButton {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99998;
            background: ' . esc_attr($button_bg_color) . ';
            padding: 10px 15px;
            border-radius: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            /* Add these mobile-specific styles */
            max-width: 90%;
            box-sizing: border-box;
        }

        #openIframeButton #openPrompt span {
            color: ' . esc_attr($button_text_color) . ';
        }

        /* Mobile-specific styles */
        @media (max-width: 768px) {
            #openIframeButton {
                bottom: 10px;
                right: 10px;
                padding: 8px 12px;
            }
            
            #openIframeButton span {
                font-size: 12px;
            }
        }
           #iframeContainer {
                position: fixed;
                bottom: 0;
                right: 0;
                z-index: 99999;
                display: none;
                width: auto;
                height: auto;
            }

            #closeButton {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                cursor: pointer;
                z-index: 100000;
            }
            #closeButton svg {
                fill: #ffffff;
            }
            #nevronixFrame {
                position: absolute;
                bottom: 0;
                right: 0;
                width: ' . esc_attr($settings['iframe_width']) . 'px;
                height: ' . esc_attr($settings['iframe_height']) . 'px;
                border: none;
                border-radius: 25px;
                transition: transform 300ms ease-out, opacity 300ms ease-out;
                opacity: 0;
                transform: scale(0.8);
                transform-origin: bottom right;
            }
        
        #iframeContainer.iframe-position-bottom-right {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99999;
        }

        #iframeContainer.iframe-position-center {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 99999;
        }


        ';


        wp_add_inline_style('nevronixai-plugin-style', $custom_css);

        // Enqueue JavaScript
        wp_register_script('nevronixai-plugin-script', '', array(), NEVRONIXAI_PLUGIN_VERSION, true);
        wp_enqueue_script('nevronixai-plugin-script');
        $custom_js = '
        function showIframe() {
            var iframeContainer = document.getElementById("iframeContainer");
            var iframe = document.getElementById("nevronixFrame");
            var closeButton = document.getElementById("closeButton");
            var openPrompt = document.getElementById("openIframeButton");

            if (window.innerWidth <= 768) {
                iframeContainer.style.width = "' . $mobile_width . 'px";
                iframeContainer.style.height = "' . $mobile_height . 'px";
                iframe.style.width = "' . $mobile_width . 'px";
                iframe.style.height = "' . $mobile_height . 'px";
            } else {
                iframeContainer.style.width = "' . esc_attr($settings['iframe_width']) . 'px";
                iframeContainer.style.height = "' . esc_attr($settings['iframe_height']) . 'px";
                iframe.style.width = "' . esc_attr($settings['iframe_width']) . 'px";
                iframe.style.height = "' . esc_attr($settings['iframe_height']) . 'px";
            }

            iframe.style.opacity = "1";
            iframe.style.transform = "scale(1)";
            closeButton.style.display = "block";
            iframeContainer.style.display = "block";
            openPrompt.style.display = "none";
        }

        //close and re-initiate the iframe element
        function closeIframe() {
      
            const iframe = document.getElementById("nevronixFrame");
            const parent = iframe.parentNode;

            // Clone the iframe without its children (iframe has no children anyway)
            const newIframe = iframe.cloneNode(false); // shallow clone, copies attributes

            // Force reload by setting src again (optional but ensures reload)
            newIframe.src = iframe.src;

            // Remove the old iframe
            parent.removeChild(iframe);

            // Append the new one (with same attributes & styles)
            parent.appendChild(newIframe);


            var iframeContainer = document.getElementById("iframeContainer");
            var openPrompt = document.getElementById("openIframeButton");

            iframeContainer.style.display = "none";
            openPrompt.style.display = "flex";
        }


        document.addEventListener("DOMContentLoaded", function() {

             var btnSpan = document.querySelector("#openIframeButton #openPrompt span");
            if(btnSpan) {
                btnSpan.textContent = '. json_encode($button_text) .';
            }

            setTimeout(showIframe, ' . esc_js($settings['show_after_seconds'] * 1000) . ');

            document.getElementById("closeButton").addEventListener("click", closeIframe);

            document.getElementById("openIframeButton").addEventListener("click", function() {
                showIframe();
                document.getElementById("iframeContainer")
            });
        });
    ';
        wp_add_inline_script('nevronixai-plugin-script', $custom_js);
    }
}



// Insert the iframe container into the footer
add_action('wp_footer', 'nevronixai_plugin_insert_iframe');
function nevronixai_plugin_insert_iframe() {
    $settings = get_option('nevronixai_plugin_settings');

    // Check if the current page/post/homepage is selected for the iframe
    if (is_front_page() && in_array('home', $settings['selected_items']) ||
        (is_singular() && in_array(get_the_ID(), $settings['selected_items']))) {


        $position = $settings['position'] ?? 'bottom-right';

        ?>
        <div id="iframeContainer" class="iframe-position-<?php echo esc_attr($position); ?>">
            <button id="closeButton">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path d="M18.36 6.64a1.5 1.5 0 0 0-2.12 0L12 9.88 7.76 5.64a1.5 1.5 0 0 0-2.12 2.12L9.88 12 5.64 16.24a1.5 1.5 0 0 0 2.12 2.12L12 14.12l4.24 4.24a1.5 1.5 0 0 0 2.12-2.12L14.12 12l4.24-4.24a1.5 1.5 0 0 0 0-2.12z"/>
                </svg>
            </button>
          


                        <iframe id="nevronixFrame" src="<?php echo esc_url($settings['api_url']); ?>" frameborder="0" allow="microphone; fullscreen; accelerometer; autoplay; encrypted-media; gyroscope;" allowfullscreen=""></iframe>
        </div>


        <div id="openIframeButton" style="display: none;">
            <div id="openPrompt">
                <span>Hi! Click here to start a conversation</span>
            </div>
        </div>

        <?php
    }
}
