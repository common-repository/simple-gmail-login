<?php
/*
Plugin Name: Simple Gmail Login
Plugin URI: http://victorjonsson.se/
Description: With this plugin you can login to wp-admin using your GMail credentials
Donate link: http://victorjonsson.se/donations/
Version: 1.2.8
Author: Victor Jonsson
Author URI: http://victorjonsson.se/
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


/**
 * Helper class for this plugin
 */
class SimpleGmail_Plugin
{

    /**
     * @var string
     */
    private $footer_html_file;

    /**
     * Path to log file
     * @var string
     */
    private $log_file;

    /**
     * Whether or not log can be written plugin directory
     * @var bool
     */
    private $plugin_is_writeable = false;

    /**
     * @var string
     */
    private $plugin_handle;

    /**
     * Initiation of the plugin. Sets up all the different filters
     * and actions used by the plugin
     */
    function init()
    {
        $plugin_dir = dirname(__FILE__);
        $this->plugin_handle = plugin_basename(__FILE__);
        $this->plugin_is_writeable = is_writable( $plugin_dir );
        $this->log_file = $plugin_dir.'/gmail-auth.log';
        $this->footer_html_file = $plugin_dir.'/login-footer.html';

        if( basename($_SERVER['SCRIPT_FILENAME']) == 'wp-login.php' ) {

            // Login action
            add_action('login_init', array($this, 'loginAction'));

            // Footer html
            add_action('login_footer', array($this, 'loginFooterAction'));
        }
        elseif( is_admin() ) {

            // Activate/deactivate
            register_activation_hook(__FILE__, array($this, 'activate'));

            // Settings page
            add_action('admin_menu', array($this, 'adminMenu'));

            // Dashboard widget
            if( $this->plugin_is_writeable ) {
                add_action('wp_dashboard_setup', array($this, 'dashboardWidgetSetup'));
            }

            // Add settings link on plugin page
            add_filter('plugin_action_links_'.$this->plugin_handle, array($this, 'settingsLinkOnPluginPage'));
        }
    }

    function settingsLinkOnPluginPage($links)
    {
        $settings_link = '<a href="options-general.php?page='.$this->plugin_handle.'">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     */
    function loginFooterAction()
    {
        $content = $this->getFooterHTML();
        if( $content ) {
            ?>
        <div style="padding-top:35px; text-align: center ">
            <?php echo $content ?>
        </div>
        <?php
        }
    }

    /**
     */
    function dashboardWidgetSetup()
    {
        if( current_user_can('activate_plugins') ) {
            wp_add_dashboard_widget(
                'simple-gmail-login',
                'Simple Gmail Login',
                array($this, 'authLogToHTML')
            );
        }
    }

    /**
     * Adds an option page to wp-admin where gmail log can be seen
     */
    function adminMenu()
    {
        // Add css
        wp_enqueue_style($this->plugin_handle, plugin_dir_url(__FILE__).'/admin.css?upd=2');

        add_options_page(
            'Simple Gmail Login',
            'Simple Gmail Login',
            'manage_options',
            $this->plugin_handle,
            array($this, 'adminOptionsPage')
        );
    }

    /**
     * Options page in wp-admin where gamil log can be seen
     */
    function adminOptionsPage()
    {
        // Prepare variables for the view
        $form_message = false;
        $plugin_writeable = $this->plugin_is_writeable;
        $log_html = $this->authLogToHTML(400, true);
        $footer_html = $this->getFooterHTML();
        $form_url = '?page='.$this->plugin_handle;

        // Form posted
        if( isset($_POST['footer_html']) ) {
            $this->setFooterHTML($_POST['footer_html']);
            $form_message = 'New footer HTML is saved';
            $footer_html = trim(stripslashes($_POST['footer_html']));
        }

        // Clear log
        if( isset($_GET['clear-log']) ) {
            if( $this->plugin_is_writeable && file_exists($this->log_file) ) {
                file_put_contents($this->log_file, '');
                $log_html = '';
            }
        }

        // Load view
        require 'settings-page.php';
    }

    /**
     * @param string $html
     */
    function setFooterHTML($html)
    {
        file_put_contents($this->footer_html_file, trim($html));
    }

    /**
     * @return string
     */
    function getFooterHTML()
    {
        if( file_exists($this->footer_html_file) ) {
            return stripslashes( file_get_contents($this->footer_html_file) );
        }
        return '';
    }

    /**
     * @param int $height
     * @param bool $return
     * @return void|string
     */
    function authLogToHTML($height = 190, $return = false)
    {
        if( !file_exists($this->log_file) ) {
            return '';
        }

        if( !is_bool($return) ) {
            $return = false;
        }

        $lines = explode(PHP_EOL, file_get_contents($this->log_file));
        $lines = implode(PHP_EOL, array_reverse($lines));

        if( !$height )
            $height = 190;

        $html = '<p>
                    <strong>Authentication log:</strong>
                </p>
                <div id="gmail-auth-log" style="height: '.$height.'px;">
                    '.nl2br($lines).'
                </div>
            ';

        if( $return ) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     * @param $email
     * @param $password
     * @throws SimpleGmail_AuthException
     */
    private function gmailAuthenticate($email, $password)
    {
        // No empty args
        if(empty($email) || empty($password))
            throw new SimpleGmail_AuthException('Neither e-mail nor password can be empty');

        // check if we have curl or not
        if( !function_exists('curl_init') )
            throw new SimpleGmail_AuthException('curl needs to be installed in order to use this function');

        $curl = curl_init('https://mail.google.com/mail/feed/atom');

        if( $this->isFollowLocationSupported() ) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $email.':'.$password);
        curl_exec($curl);

        $error = curl_error($curl);
        if( $error !== '' ) {
            $err_num = curl_errno($curl);
            curl_close($curl);
            throw new SimpleGmail_AuthException($error, $err_num);
        }
        else {
            // failed authentication
            $status_code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if($status_code !== 200) {
                throw new SimpleGmail_AuthException('Google responded with status '.$status_code);
            }
        }
    }

    /**
     * @return bool
     */
    private function isFollowLocationSupported() {
        $safe_mode = ini_get('safe_mode');
        $open_basedir = ini_get('open_basedir');
        return (!$safe_mode || strtolower($safe_mode) == 'off') &&
            (!$open_basedir || strtolower($open_basedir) == 'off');

    }

    /**
     * Action invoked when user is about to login
     */
    function loginAction()
    {
        if( !empty($_POST['log']) && isset($_POST['pwd']) ) {

            $remember = !empty($_POST['rememberme']);
            $redirect = !empty($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : admin_url();
            $login_by = filter_var($_POST['log'], FILTER_VALIDATE_EMAIL) !== false ? 'email':'login';

            if ( $user = get_user_by($login_by, sanitize_user($_POST['log'])) ) {

                // WP login
                if( user_pass_ok($user->user_login, $_POST['pwd']) ) {
                    $this->log('User #'.$user->ID.' ('.$user->user_email.') authenticated using <strong>wordpress</strong> credentials', false);
                    $this->afterSuccessfulAuth($user, $remember, $redirect);
                    die;
                }

                // Gmail login
                try {
                    // Authenticate against gmail
                    $this->gmailAuthenticate($user->user_email, $_POST['pwd']);
                    $this->log('User #'.$user->ID.' ('.$user->user_email.') authenticated via <strong>Gmail</strong>', false);
                    $this->afterSuccessfulAuth($user, $remember, $redirect);
                    die;
                } catch(SimpleGmail_AuthException $e) {
                    // Failed both validating against wp and gmail
                    $this->log('Failed login for user #'.$user->ID.' ('.$user->user_email.') Message: '.$e->getMessage());
                    $this->overrideDefaultWPAuth();
                }
            }

            // No user found
            else {
                $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : ( isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']:'no remote address');
                $this->log('Failed login, no user could be found with given username/e-mail "'.$_POST['log'].'" (ip: '.$ip.')');
                $this->overrideDefaultWPAuth();
            }
        }
    }

    /**
     * Replace ordinary wp authentication with an auth function that always fails
     * This hook is only applied when we already know that user credentials is incorrect
     */
    private function overrideDefaultWPAuth()
    {
        // Override ordinary wp_authentication, really removing all auth hooks
        remove_all_filters('authenticate');
        add_filter('authenticate', array($this, 'failingAuthHook'));
    }

    /**
     * @return null
     */
    public function failingAuthHook()
    {
        return null;
    }

    /**
     * Add authentication cookie and redirect user
     * @param stdClass|object $user
     * @param string $remember
     * @param string $redirect_to
     */
    private function afterSuccessfulAuth($user, $remember, $redirect_to)
    {
        // Set client as authenticated
        wp_set_auth_cookie($user->ID, $remember);
        do_action('wp_login', $user->user_login, $user);

        // Redirect
        wp_safe_redirect($redirect_to);
        die;
    }

    /**
     * Activation
     */
    function activate()
    {
        $this->log('Plugin activated', false);
    }

    /**
     * Write message to plugin log file or error log
     * @param $mess
     * @param bool $is_error
     * @return string
     */
    public function log($mess, $is_error=true) {
        if( $this->plugin_is_writeable ) {

            $mess = sprintf(
                '<span style="color: %s">[%s] %s</span>',
                $is_error ? 'red':'green',
                $this->getCurrentTime(),
                strip_tags($mess, '<strong>')
            );

            file_put_contents($this->log_file, $mess.PHP_EOL, FILE_APPEND);

            // Down size log file
            if( filesize($this->log_file) > (1024*1024) ) {
                file_put_contents($this->log_file, file_get_contents($this->log_file, null, null, 102400));
            }

            return $mess;
        }
        else {
            error_log('[Simple-gmail-login] - '.$mess);
        }

        return $mess;
    }

    /**
     * @return string
     */
    private function getCurrentTime()
    {
        try {
            $date = new DateTime('now', new DateTimeZone(get_option('timezone_string')));
            return $date->format('Y-m-d H:i:s');
        } catch(Exception $e) {
            return date('Y-m-d H:i:s', time() + ((int)get_option('gmt_offset') * 3600) );
        }
    }
}

/**
 * Exception thrown when authentication against GMail fails
 */
class SimpleGmail_AuthException extends Exception {}

// Run plugin
$simple_gmail_plugin = new SimpleGmail_Plugin();
$simple_gmail_plugin->init();