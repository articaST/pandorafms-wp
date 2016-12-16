<?php
/*
Copyright (c) 2016-2016 Artica Soluciones Tecnologicas

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


//$plugin_dir_path = plugin_dir_path(__FILE__);

//=== INIT === INCLUDES ================================================
//require_once($plugin_dir_path . "/includes/PandoraFMS_WP.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_AdminPages.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Widget_Dashboard.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Footer.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_GoogleAnalytics.class.php");
//require_once(plugin_dir_path(__FILE__) . "/includes/PFMS_Hooks.class.php");

//require_once(ABSPATH . "wp-admin/includes/class-wp-upgrader.php");
//require_once(ABSPATH . "wp-admin/includes/file.php");
//require_once(ABSPATH . "wp-admin/includes/template.php");
//=== END ==== INCLUDES ================================================







	
	//=== INIT === HOOKS CODE ==========================================
	public static function activation() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// Check if installed
		$pfms_wp->install();
		
		// Only active the plugin again
	}
	
	public static function deactivation() {
		error_log( "Deactivation" );
	}
	
	public static function uninstall() {
		PandoraFMS_WP::deactivation();
		error_log( "Uninstall" );
	}
	
	public static function rest_api_init() {
		error_log("rest_api_init");
		
		/*
		 * EXAMPLE A PHP CALL OF API
		 * 
		
		$process = curl_init("https://192.168.70.155/wordpress/wp-json/pandorafms_wp/online");
		$headers = array(
			'Content-Type:application/json',
			'Authorization: Basic '. base64_encode("admin:password") // <---
		);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$return = curl_exec($process);
		var_dump(curl_getinfo($process));
		curl_close($process);
		
		var_dump($return);
		
		*/
		
		
		// https://<URL_WORDPRESS>/wp-json/pandorafms_wp/online
		// HTTP header
		// Authentication: Basic ' . base64_encode( 'admin' . ':' . '<PANDORA FMS API PASSWORD>' ),
		register_rest_route('pandorafms_wp', '/online',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_online')
			)
		);
		
		register_rest_route('pandorafms_wp', '/site_name',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_site_name')
			)
		);
		
		register_rest_route('pandorafms_wp', '/version',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_version')
			)
		);
		
		register_rest_route('pandorafms_wp', '/wp_version',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_wp_version')
			)
		);
		
		register_rest_route('pandorafms_wp', '/admin',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_admin_user')
			)
		);
		
		register_rest_route('pandorafms_wp', '/upload_code_protect',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_upload_code_protect')
			)
		);
		
		register_rest_route('pandorafms_wp', '/robots_protect',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_robots_protect')
			)
		);
		
		register_rest_route('pandorafms_wp', '/wp_generator_protect',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_wp_generator_protect')
			)
		);
		
		register_rest_route('pandorafms_wp', '/failed_login_lockout',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_failed_login_lockout')
			)
		);
		
		register_rest_route('pandorafms_wp', '/password_audit',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_password_audit')
			)
		);
		
		register_rest_route('pandorafms_wp', '/new_account',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_new_account')
			)
		);
		
		register_rest_route('pandorafms_wp', '/user_login',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_user_login')
			)
		);
		
		register_rest_route('pandorafms_wp', '/failed_login',
			array(
				'methods' => 'GET',
				'callback' => array('PandoraFMS_WP', 'apirest_failed_login')
			)
		);
	}







	
	public static function init() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_wp->check_new_themes();
		$pfms_wp->check_new_plugins();

		//$pfms_wp->blacklist_files();//para llamarla al inicio
		
		$options_system_security = get_option('pfmswp-options-system_security');
		
		// === INIT === Ban the IPs blacklist_ips ======================
		$ip = $_SERVER['REMOTE_ADDR'];
		$blacklist_ips = $options_system_security['blacklist_ips'];
		$blacklist_ips = str_replace("\r", "\n", $blacklist_ips);
		$blacklist_ips = explode("\n", $blacklist_ips);
		if (empty($blacklist_ips))
			$blacklist_ips = array();
		$blacklist_ips = array_filter($blacklist_ips);
		if (array_search($ip, $blacklist_ips) !== false) {
			if (empty($options_system_security['url_redirect_ip_banned']))
				die("Banned IP : " . $ip);
			else
				wp_redirect($options_system_security['url_redirect_ip_banned']);
		}
		// === END ==== Ban the IPs blacklist_ips ======================
		
		//Code copied from footer-putter plugin
		switch (basename( TEMPLATEPATH ) ) {  
			case 'twentyten':
				add_action('twentyten_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentyeleven':
				add_action('twentyeleven_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentytwelve':
				add_action('twentytwelve_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentythirteen':
				add_action('twentythirteen_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'twentyfourteen':
				add_action('twentyfourteen_credits', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'delicate':
				add_action('get_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'genesis':
				add_action('genesis_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'graphene':
				add_action('graphene_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
			case 'pagelines':
				add_action('pagelines_leaf', array('PandoraFMS_WP', 'show_footer'));
				break;
			default:
				add_action('wp_footer', array('PandoraFMS_WP', 'show_footer'));
				break;
		}
		
		// Added action for footer
		add_action('twentyfourteen_credits', array('PandoraFMS_WP', 'show_footer'));
		
		
		//=== INIT === EVENT HOOKS =====================================
		add_action("user_register", array('PandoraFMS_WP', 'user_register'));
		add_action("wp_login", array('PandoraFMS_WP', 'user_login'));
		add_action("profile_update", array('PandoraFMS_WP', 'user_change_email'), 10, 2);
		add_action("wp_login_failed", array('PandoraFMS_WP', 'user_login_failed'));
		add_action('login_init', array('PandoraFMS_WP', 'login_init'));
		add_action('login_enqueue_scripts', array('PandoraFMS_WP', 'login_js'));
		add_action('login_form', array('PandoraFMS_WP', 'login_form'));
		add_action('wp_authenticate', array('PandoraFMS_WP', 'login_authenticate'), 1, 2);
		//add_action('wp_files', array('PandoraFMS_WP', 'files_modified'), 1, 2);
		//=== END ==== EVENT HOOKS =====================================
		
		if ($options_system_security['upload_htaccess']) {
			$pfms_wp->install_htaccess();
		}
		else {
			$installed_htaccess = get_option($pfms_wp->prefix . "installed_htaccess", 0);
			
			if ($installed_htaccess) {
				$pfms_wp->uninstall_htaccess();
			}
			else {
				// None
			}
		}
		
		if ($options_system_security['upload_robots_txt']) {
			$pfms_wp->install_robots_txt();
		}
		else {
			$installed_robot_txt = get_option($pfms_wp->prefix . "installed_robot_txt", 0);
			
			if ($installed_robot_txt) {
				$pfms_wp->uninstall_robots_txt();
			}
			else {
				// None
			}
		}
		
		if ($options_system_security['wp_generator_disable']) {
			for ($i = 0; $i < 11; $i++) {
				remove_action('wp_head', 'wp_generator', $i);
			}
		}
		
		if ($options_system_security['activate_login_rename']) {
			$pfms_wp->activate_login_rename($options_system_security['login_rename_page']);
		}
		else {
			$pfms_wp->deactivate_login_rename();
		}

		
	}
	
	public static function admin_init() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		// Create the widget
		add_action('wp_dashboard_setup',
			array("PFMS_Widget_Dashboard", "show_dashboard"));
		
		//Added settings
		register_setting(
			"pfmswp-settings-group",
			"pfmswp-options",
			array("PandoraFMS_WP", "sanitize_options"));
		register_setting(
			"pfmswp-settings-google-analytics",
			"pfmswp-options-ga",
			array("PandoraFMS_WP", "sanitize_options_google_analytics"));
		register_setting(
			"pfmswp-settings-group-options-monitoring",
			"pfmswp-options-monitoring",
			array("PandoraFMS_WP", "sanitize_options_monitoring"));
		register_setting(
			"pfmswp-settings-group-access_control",
			"pfmswp-options-access_control",
			array("PandoraFMS_WP", "sanitize_options_access_control"));
		register_setting(
			"pfmswp-settings-group-system_security",
			"pfmswp-options-system_security",
			array("PandoraFMS_WP", "sanitize_options_system_security"));
		register_setting(
			"pfmswp-settings-group-filesystem",
			"pfmswp-options-filesystem",
			array("PandoraFMS_WP", "sanitize_options_filesystem"));

		
		// Added script
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style("wp-jquery-ui-dialog");
		
		wp_enqueue_script(
			'my_custom_script',
			plugin_dir_url( __FILE__ ) . '../js/jquery.scrollTableBody-1.0.0.js');
	}
	
	public static function show_footer() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		if ($options['show_footer']) {
			$pfms_footer = PFMS_Footer::getInstance();
			$pfms_footer->show_footer();
		}
	}
	
	public static function user_register($user_id) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$user = get_userdata($user_id);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_register',
				'data' =>
					sprintf(
						esc_sql(__("User [%s] register.")),
						$user->user_login),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_new_account'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('New account in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] New account creation'), $blog),
			$message);
	}
	
	public static function user_login_failed($user_login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		
		$pfms_wp->store_user_login($user_login, false);
		
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'failed_login',
				'data' =>
					sprintf(
						esc_sql(__("User [%s] failed login.")),
						$user_login),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		$options_system_security = get_option('pfmswp-options-system_security');
		if ($options_system_security['bruteforce_attack_protection']) {
			
			$attempts = get_transient("pfms_wp::bruteforce_attempts");
			if ($attempts === false)
				$attempts = 0;
			else
				$attempts = (int)$attempts;
			
			$attempts++;
			
			set_transient("pfms_wp::bruteforce_attempts", $attempts, DAY_IN_SECONDS);
			
			if ($attempts >= $options_system_security['bruteforce_attack_attempts']) {
				$return = $wpdb->insert(
					$tablename,
					array(
						'type' => 'login_lockout',
						'data' =>
							sprintf(
								esc_sql(__("User [%s] login lockout after [%d] attempts.")),
								$user_login, $attempts),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
			}
		}
		
		error_log("user_login_failed");
	}
	
	//Send an email with each login
	public static function user_login($user_login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->store_user_login($user_login, true);
		
		$options_system_security = get_option('pfmswp-options-system_security');
		if ($options_system_security['bruteforce_attack_protection']) {
			set_transient("pfms_wp::bruteforce_attempts", 0, DAY_IN_SECONDS);
		}
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$user = get_user_by('login', $user_login);
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_login',
				'data' =>
					sprintf(
						esc_sql(__("User [%s] login.")),
						$user->user_login),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_user_login'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('Login user in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] Login user %s'), $blog, $user->user_login),
			$message);
	}
	
	//Send an email when any user change the email
	public static function user_change_email($user_id, $old_user_data) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$user = get_userdata($user_id);
		
		$old_email = $old_user_data->data->user_email;
		$new_email = $user->data->user_email;
		
		if ($old_email === $new_email)
			return;
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'user_change_email',
				'data' =>
					sprintf(
						esc_sql(__("User [%s] with old email [%s] and new email [%s].")),
						$user->user_login,
						$old_email,
						$new_email),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_change_email'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('User email change in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
		$message .= sprintf(__('Old email: %s'), $old_email) . "\r\n\r\n";
		$message .= sprintf(__('New email: %s'), $new_email) . "\r\n\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] %s change the email'), $blog, $user->user_login),
			$message);
	}
	
	public static function login_init() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		if ($options_system_security['bruteforce_attack_protection']) {
			
			$attempts = get_transient("pfms_wp::bruteforce_attempts");
			if ($attempts === false)
				$attempts = 0;
			else
				$attempts = (int)$attempts;
			
			if ($attempts >= $options_system_security['bruteforce_attack_attempts']) {
				error_log("protect bruteforce");
				set_transient("pfms_wp::bruteforce_attempts", 0, DAY_IN_SECONDS);
				sleep($pfms_wp->wait_protect_bruteforce_login_seconds);
			}
		}
		
	}
	
	public static function login_js() {
		$options = get_option('pfmswp-options-system_security');
		
		if (!$options['activate_login_recaptcha'])
			return;
		
		error_log("login_js");
		$lang = get_locale();
		?>
		<script type="text/javascript"
			src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang; ?>"></script>
		<?php
	}
	
	public static function login_form() {
		$options = get_option('pfmswp-options-system_security');
		
		if (!$options['activate_login_recaptcha'])
			return;
		
		?>
		<div class="g-recaptcha" data-sitekey="<?php echo $options['site_key']; ?>"></div>
		<?php 
	}
	
	public static function login_authenticate(&$user_login, &$user_pass) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options-system_security');
		
		if (!$options['activate_login_recaptcha'])
			return;
		
		$sitekey = $options['site_key'];
		$secret = $options['secret'];
		
		$parameters = array(
			'secret' => trim($secret),
			'response' => isset($_POST['g-recaptcha-response']) ?
				$_POST['g-recaptcha-response'] : "",
			'remoteip' => $_SERVER['REMOTE_ADDR']
		);
		$url = 'https://www.google.com/recaptcha/api/siteverify?' .
			http_build_query($parameters);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		
		$json_response = json_decode($response, true);
				
		if (isset($json_response['success']) && true !== $json_response['success']) {
			// Delete the user_login and user_password to stop the login process
			$user_login = null;
			$user_pass = null;
			return;
		}
	}
	//=== END ==== HOOKS CODE ==========================================
	








?>