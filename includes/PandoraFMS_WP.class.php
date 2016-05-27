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

class PandoraFMS_WP {
	//=== INIT === ATRIBUTES ===========================================
	public $prefix = 'pfms-wp::';
	private $acl_user_menu_entry = "manage_options"; // acl settings
	private $position_menu_entry = 75; //Under tools
	private $items_per_page = 25;
	/*
	 * DEBUG == 0
	 *  - The force the cron task execute now.
	 * DEBUG == 1
	 *  - The force the cron task for to execute the next time
	 */
	public $debug = 1;
	//=== END ==== ATRIBUTES ===========================================
	
	
	//=== INIT === SINGLETON CODE ======================================
	private static $instance = null;
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	//=== END ==== SINGLETON CODE ======================================
	
	
	private function __construct() {
	}
	
	private function install() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$installed = get_option("installed", false);
		
		if (!$installed) {
			add_option($pfms_wp->prefix . "installed", true);
			
			$audit_password = array(
				'last_execution' => null,
				'status' => null);
			add_option($pfms_wp->prefix . "audit_passwords", $audit_password);
		}
		
		// The wordpress has the function dbDelta that create (or update
		// if it created previously).
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		
		// Table "audit_users_weak_password"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`user` varchar(60) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "access_control"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`type` varchar(60) NOT NULL DEFAULT '',
			`data` varchar(255) NOT NULL DEFAULT '',
			`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "user_stats"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`user` varchar(60) NOT NULL DEFAULT '',
			`action` varchar(60) NOT NULL DEFAULT '',
			`count` INT NOT NULL DEFAULT 0,
			`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
		
		
		// Table "list_files"
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		$sql = "CREATE TABLE `$tablename` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`path` longtext NOT NULL,
			`writable_others` INT NOT NULL DEFAULT 0,
			`type` varchar(60) NOT NULL DEFAULT '',
			`status` varchar(60) NOT NULL DEFAULT '',
			`sha1` varchar(60) NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
			);";
		dbDelta($sql);
	}
	
	public function get_last_access_control() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$rows = $wpdb->get_results(
			"SELECT *
			FROM `" . $tablename . "`
			ORDER BY `timestamp` DESC
			LIMIT " . $pfms_wp->items_per_page);
		if (empty($rows))
			$rows = array();
		
		return $rows;
	}
	
	public function store_user_login($user_login, $login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
		
		
		if ($login) {
			$action = 'login_ok';
		}
		else {
			$action = 'login_fail';
		}
		
		$rows = $wpdb->get_results(
			"SELECT *, UNIX_TIMESTAMP(timestamp) AS unix_timestamp
			FROM `" . $tablename . "`
			WHERE user = '" . esc_sql($user_login) ."'
			ORDER BY timestamp DESC");
		
		$now = date("Ymd");
		$yesterday = date("Ymd", (time() - (24 * 60 * 60)));
		
		// --- Delete old stats ----------------------------------------
		foreach ($rows as $i => $row) {
			$row = (array)$row;
			
			$date = date("Ymd", $row['unix_timestamp']);
			
			if (($now == $date) || ($now == $yesterday)) {
				continue;
			}
			
			// Delete the row in the array and in the db
			$wpdb->delete($tablename,
				array('id' => $row['id']),
				"%d");
			
			unset($rows[$i]);
		}
		// -------------------------------------------------------------
		
		
		$actual_stats = null;
		foreach ($rows as $row) {
			$row = (array)$row;
			
			$date = date("Ymd", $row['unix_timestamp']);
			
			if (($now == $date) && ($row['action'] == $action)) {
				$actual_stats = $row;
			}
		}
		
		if (empty($actual_stats)) {
			$actual_stats = array();
			$actual_stats['user'] = $user_login;
			$actual_stats['action'] = $action;
			$actual_stats['count'] = 1;
			$actual_stats['timestamp'] = date('Y-m-d H:i:s');
			
			$id = $wpdb->insert(
				$tablename,
				$actual_stats,
				array('%s', '%s', '%d', '%s'));
			$wpdb->flush();
		}
		else {
			$id = $actual_stats['id'];
			unset($actual_stats['id']);
			unset($actual_stats['unix_timestamp']);
			
			// Refresh the data
			$actual_stats['count'] = $actual_stats['count'] + 1;
			$actual_stats['timestamp'] = date('Y-m-d H:i:s');
			
			$wpdb->update(
				$tablename,
				$actual_stats,
				array('id' => $id),
				array('%s', '%s', '%d', '%s'),
				array('%d'));
		}
	}
	
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
	
	public static function init() {
		error_log( "Init" );
		
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
		//~ add_action("activated_plugin", array('PandoraFMS_WP', 'activate_plugin'));
		add_action("wp_login_failed", array('PandoraFMS_WP', 'user_login_failed'));
		//=== END ==== EVENT HOOKS =====================================
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_wp->check_new_themes();
		$pfms_wp->check_new_plugins();
		
		$options_system_security = get_option('pfmswp-options-system_security');
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
			"pfmswp-settings-group-access_control",
			"pfmswp-options-access_control",
			array("PandoraFMS_WP", "sanitize_options_access_control"));
		register_setting(
			"pfmswp-settings-group-system_security",
			"pfmswp-options-system_security",
			array("PandoraFMS_WP", "sanitize_options_system_security"));
		
		
		// Added script
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style("wp-jquery-ui-dialog");
		
		wp_enqueue_script(
			'my_custom_script',
			plugin_dir_url( __FILE__ ) . '../js/jquery.scrollTableBody-1.0.0.js');
		
		error_log( "Admin Init" );
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
				'type' => 'user_login',
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
		
		$pfms_wp->store_user_login($user_login, false);
		
		error_log("user_login_failed");
	}
	
	public static function user_login($user_login) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->store_user_login($user_login, true);
		
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
		
		$pfms_wp->debug($options_access_control);
		
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
	
	public static function activate_plugin($plugin) {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$plugins = get_plugins();
		if (empty($plugins))
			$plugins = array();
		
		$plugin_name = "";
		foreach ($plugins as $file => $p) {
			if ($file === $plugin) {
				$plugin_name = $p['Name'];
			}
		}
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
		$return = $wpdb->insert(
			$tablename,
			array(
				'type' => 'activate_plugin',
				'data' =>
					sprintf(
						esc_sql(__("Activate plugin [%s].")),
						$plugin_name),
				'timestamp' => date('Y-m-d H:i:s')),
			array('%s', '%s', '%s'));
		
		if (!$options_access_control['email_activate_plugin'])
			return;
		
		$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		
		if (empty($options['email_notifications']))
			$email_to = get_option('admin_email');
		else
			$email_to = $options['email_notifications'];
		
		
		$message  = sprintf(__('Activate plugin in %s:'), $blog) . "\r\n\r\n";
		$message .= sprintf(__('Plugin: %s'), $plugin_name) . "\r\n\r\n";
		
		$result = wp_mail($email_to,
			sprintf(__('[%s] activate plugin'), $blog),
			$message);
	}
	//=== END ==== HOOKS CODE ==========================================
	
	private function install_htaccess() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		$destination_dir = $options_system_security['directory_htaccess'];
		
		$htacess_file = plugin_dir_path(__FILE__) .
			"../data/htaccess_file";
		
		$installed = false;
		
		// The file is from data directory of plugin
		if (!empty($destination_dir)) {
			if (!is_dir($destination_dir)) {
				$destination_dir = realpath(
					ABSPATH . $destination_dir);
			}
			
			if (is_dir($destination_dir)) {
				$installed_htaccess_file =
					$destination_dir . "/.htaccess";
				$installed = copy($htacess_file, $installed_htaccess_file);
			}
		}
		
		if ($installed) {
			update_option($pfms_wp->prefix . "installed_htaccess", (int)$installed);
			update_option($pfms_wp->prefix . "installed_htaccess_file",
				$installed_htaccess_file);
		}
	}
	
	private function uninstall_htaccess() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$installed_file = get_option($pfms_wp->prefix . "installed_htaccess_file",
			null);
		
		$install = 0;
		if (!empty($installed_file)) {
			$install = !unlink($installed_file);
		}
		
		if (!$install) {
			update_option($pfms_wp->prefix . "installed_htaccess_file", "");
		}
		update_option($pfms_wp->prefix . "installed_htaccess", (int)$install);
	}
	
	public function install_robots_txt() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options_system_security = get_option('pfmswp-options-system_security');
		$destination_dir = $options_system_security['directory_robot_txt'];
		
		$robots_txt_file = plugin_dir_path(__FILE__) .
			"../data/robots_txt_file";
		
		$installed = false;
		
		// The file is from data directory of plugin
		if (!empty($destination_dir)) {
			if (!is_dir($destination_dir)) {
				$destination_dir = realpath(
					ABSPATH . $destination_dir);
			}
			
			if (is_dir($destination_dir)) {
				$installed_htaccess_file =
					$destination_dir . "/robots.txt";
				$installed = copy($robots_txt_file, $installed_htaccess_file);
			}
		}
		
		if ($installed) {
			update_option($pfms_wp->prefix . "installed_robot_txt", (int)$installed);
			update_option($pfms_wp->prefix . "installed_robots_txt_file",
				$installed_htaccess_file);
		}
	}
	
	private function uninstall_robots_txt() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$installed_file = get_option($pfms_wp->prefix . "installed_robot_txt",
			null);
		
		$install = 0;
		if (!empty($installed_file)) {
			$install = !unlink($installed_file);
		}
		
		if (!$install) {
			update_option($pfms_wp->prefix . "installed_robots_txt_file", "");
		}
		update_option($pfms_wp->prefix . "installed_robot_txt", (int)$install);
	}
	
	public function check_new_plugins() {
		require_once(ABSPATH . "/wp-admin/includes/plugin.php");
		
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$last_installed_plugins = get_option($pfms_wp->prefix . "installed_plugins", false);
		
		$installed_plugins = get_plugins();
		$temp = array();
		foreach ($installed_plugins as $plugin) {
			$temp[] = $plugin['Name'];
		}
		$installed_plugins = $temp;
		
		if (empty($last_installed_plugins)) {
			add_option($pfms_wp->prefix . "installed_plugins", $installed_plugins);
		}
		else {
			$new_plugins = array();
			foreach ($installed_plugins as $plugin) {
				if (array_search($plugin, $last_installed_plugins) === false)
					$new_plugins[] = $plugin;
			}
			
			update_option($pfms_wp->prefix . "installed_plugins", $installed_plugins);
		}
		
		if (!empty($new_plugins)) {
			foreach ($new_plugins as $new_plugin) {
				$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
				$return = $wpdb->insert(
					$tablename,
					array(
						'type' => 'new_plugin',
						'data' =>
							sprintf(
								esc_sql(__("New plugin [%s].")),
								$new_plugin),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
				
				if (!$options_access_control['email_plugin_new'])
					continue;
				
				$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
				
				if (empty($options['email_notifications']))
					$email_to = get_option('admin_email');
				else
					$email_to = $options['email_notifications'];
				
				
				$message  = sprintf(__('New plugin in %s:'), $blog) . "\r\n\r\n";
				$message .= sprintf(__('Plugin: %s'), $new_plugin) . "\r\n\r\n";
				
				$result = wp_mail($email_to,
					sprintf(__('[%s] New plugin'), $blog),
					$message);
			}
		}
	}
	
	public function check_new_themes() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$options = get_option('pfmswp-options');
		$options = $pfms_wp->sanitize_options($options);
		
		$options_access_control = get_option('pfmswp-options-access_control');
		$options_access_control = $pfms_wp->sanitize_options_access_control($options_access_control);
		
		$last_installed_themes = get_option($pfms_wp->prefix . "installed_themes", false);
		
		$installed_themes = wp_get_themes();
		$temp = array();
		foreach ($installed_themes as $theme) {
			$temp[] = $theme->get('Name');
		}
		$installed_themes = $temp;
		
		if (empty($last_installed_themes)) {
			add_option($pfms_wp->prefix . "installed_themes", $installed_themes);
		}
		else {
			$new_themes = array();
			foreach ($installed_themes as $theme) {
				if (array_search($theme, $last_installed_themes) === false)
					$new_themes[] = $theme;
			}
			
			update_option($pfms_wp->prefix . "installed_themes", $installed_themes);
		}
		
		if (!empty($new_themes)) {
			foreach ($new_themes as $new_theme) {
				$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
				$return = $wpdb->insert(
					$tablename,
					array(
						'type' => 'new_theme',
						'data' =>
							sprintf(
								esc_sql(__("New theme [%s].")),
								$new_theme),
						'timestamp' => date('Y-m-d H:i:s')),
					array('%s', '%s', '%s'));
				
				if (!$options_access_control['email_theme_new'])
					continue;
				
				$blog = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
				
				if (empty($options['email_notifications']))
					$email_to = get_option('admin_email');
				else
					$email_to = $options['email_notifications'];
				
				
				$message  = sprintf(__('New theme in %s:'), $blog) . "\r\n\r\n";
				$message .= sprintf(__('Theme: %s'), $new_theme) . "\r\n\r\n";
				
				$result = wp_mail($email_to,
					sprintf(__('[%s] New theme'), $blog),
					$message);
			}
		}
	}
	
	
	private function set_default_options() {
		$default_options = array();
		
		$default_options['show_footer'] = 0;
		$default_options['email_notifications'] = "";
		$default_options['api_password'] = "";
		$default_options['api_ip'] = "";
		$default_options['email_new_account'] = 1;
		$default_options['email_user_login'] = 1;
		$default_options['email_change_email'] = 1;
		$default_options['email_plugin_new'] = 1;
		$default_options['email_theme_new'] = 1;
		$default_options['upload_htaccess'] = 0;
		$default_options['directory_htaccess'] = "";
		$default_options['upload_robots_txt'] = 0;
		$default_options['directory_robot_txt'] = "";
		$default_options['wp_generator_disable'] = 0;
		
		return $default_options;
	}
	
	public static function sanitize_options($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		$options['email_notifications'] =
			sanitize_email($options['email_notifications']);
		
		if (!isset($options['show_footer']))
			$options['show_footer'] = 0;
		
		return $options;
	}
	
	public static function sanitize_options_access_control($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		if (!isset($options['email_new_account']))
			$options['email_new_account'] = 0;
		if (!isset($options['email_user_login']))
			$options['email_user_login'] = 0;
		if (!isset($options['email_change_email']))
			$options['email_change_email'] = 0;
		if (!isset($options['email_plugin_new']))
			$options['email_plugin_new'] = 0;
		if (!isset($options['email_theme_new']))
			$options['email_theme_new'] = 0;
		
		return $options;
	}
	
	public static function sanitize_options_system_security($options) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!is_array($options) || empty($options) || (false === $options))
			return $pfms_wp->set_default_options();
		
		if (!isset($options['upload_htaccess']))
			$options['upload_htaccess'] = 0;
		
		if (!isset($options['upload_robots_txt']))
			$options['upload_robots_txt'] = 0;
			
		if (!isset($options['wp_generator_disable']))
			$options['wp_generator_disable'] = 0;
		
		return $options;
	}
	
	public function debug($var) {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (!$pfms_wp->debug)
			return;
		
		$more_info = '';
		if (is_string($var)) {
			$more_info = 'size: ' . strlen($var);
		}
		elseif (is_bool($var)) {
			$more_info = 'val: ' .
				($var ? 'true' : 'false');
		}
		elseif (is_null($var)) {
			$more_info = 'is null';
		}
		elseif (is_array($var)) {
			$more_info = count($var);
		}
		
		ob_start();
		echo "(" . gettype($var) . ") " . $more_info . "\n";
		print_r($var);
		echo "\n\n";
		$output = ob_get_clean();
		
		error_log($output);
	}
	
	public static function add_admin_menu_entries() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		add_menu_page(
			_("PandoraFMS WP : Dashboard"),
			_("PandoraFMS WP"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu",
			array("PFMS_AdminPages", "show_dashboard"),
			"dashicons-admin-tools",
			$pfms_wp->position_menu_entry);
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : Dashboard"),
			_("Dashboard"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu",
			array("PFMS_AdminPages", "show_dashboard"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : Monitoring"),
			_("Monitoring"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_monitoring",
			array("PFMS_AdminPages", "show_monitoring"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : Access control"),
			_("Access control"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_access_control",
			array("PFMS_AdminPages", "show_access_control"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : System Security"),
			_("System Security"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_system_security",
			array("PFMS_AdminPages", "show_system_security"));
		
		add_submenu_page(
			"pfms_wp_admin_menu",
			_("PandoraFMS WP : General Setup"),
			_("General Setup"),
			$pfms_wp->acl_user_menu_entry,
			"pfms_wp_admin_menu_general_setup",
			array("PFMS_AdminPages", "show_general_setup"));
	}
	
	public function get_dashboard_data() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$return = array();
		
		$return['monitoring'] = array();
		$return['monitoring']['check_admin'] = $this->check_admin_user_enabled();
		
		// audit_passwords_strength
		$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
			array(
				'last_execution' => null,
				'status' => null));
		$return['monitoring']['audit_password'] = $audit_password;
		
		// audit_files
		$audit_password = get_option($pfms_wp->prefix . "audit_files",
			array(
				'last_execution' => null,
				'status' => null));
		$return['monitoring']['audit_files'] = $audit_password;
		
		
		$options_system_security = get_option('pfmswp-options-system_security');
		$return['system_security'] = array();
		$return['system_security']['protect_upload_php_code'] =
			(int)get_option($pfms_wp->prefix . "installed_htaccess", 0);
		$return['system_security']['installed_robot_txt'] =
			(int)get_option($pfms_wp->prefix . "installed_robot_txt", 0);
		$return['system_security']['wp_generator_disable'] =
			$options_system_security['wp_generator_disable'];
		
		return $return;
	}
	
	private function get_filesystem_status($directory = null) {
		$filesystem = array();
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if (empty($directory))
			$directory = ABSPATH;
		
		$dir = dir($directory);
		
		while (false !== ($entry = $dir->read())) {
			if (($entry === '..'))
				continue;
			
			$path = realpath($directory . '/' . $entry);
			$perms = fileperms($path);
			
			$entry_filesystem = array();
			
			$entry_filesystem['path'] = $path;
			$entry_filesystem['writable_others'] = ($perms & 0x0002)? 1 : 0;
			
			if ($entry === '.') {
				$entry_filesystem['type'] = 'dir';
				$entry_filesystem['sha1'] = '';
				
				$filesystem[] = $entry_filesystem;
			}
			elseif (is_dir($path)) {
				$filesystem_subdir = $pfms_wp->get_filesystem_status($path);
				$filesystem = array_merge($filesystem, $filesystem_subdir);
			}
			else {
				$entry_filesystem['type'] = 'file';
				$entry_filesystem['sha1'] = sha1_file($path);
				
				$filesystem[] = $entry_filesystem;
			}
		}
		
		$dir->close();
		
		return $filesystem;
	}
	
	//=== INIT === CHECKS ==============================================
	public function get_count_posts_last_day() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$sql = "
			SELECT COUNT(*) AS count
			FROM `" . $wpdb->prefix . "posts" . "`
			WHERE TIMESTAMPDIFF(HOUR, post_date, now()) < 25";
		
		$count = $wpdb->get_results($sql);
		
		return $count[0]->count;
	}
	public function get_count_comments_last_day() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$sql = "
			SELECT COUNT(*) AS count
			FROM `" . $wpdb->prefix . "comments" . "`
			WHERE TIMESTAMPDIFF(HOUR, comment_date, now()) < 25";
		
		$count = $wpdb->get_results($sql);
		
		return $count[0]->count;
	}
	
	private function audit_files() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		
		$audit_files = get_option($pfms_wp->prefix . "audit_files",
			array(
				'last_execution' => null,
				'status' => null));
		
		$filesystem = $pfms_wp->get_filesystem_status();
		
		$not_changes_filesystem = true;
		
		if (is_null($audit_files['last_execution'])) {
			// Save the files only
			foreach ($filesystem as $entry) {
				$value = array(
					'path' => $entry['path'],
					'writable_others' => $entry['writable_others'],
					'type' => $entry['type'],
					'status' => 'new',
					'sha1' => $entry['sha1']);
				
				$wpdb->insert(
					$tablename,
					$value);
			}
		}
		else {
			// Clean the audit_files table from the last execution
			$store_filesystem = $wpdb->get_results("
				SELECT * FROM `" . $tablename . "`");
			
			foreach ($store_filesystem as $i => $store_entry) {
				$store_entry = (array)$store_entry;
				
				switch ($store_entry['status']) {
					case 'deleted':
						$wpdb->delete(
							$tablename,
							array('id' => $store_entry['id']));
						unset($store_filesystem[$i]);
						break;
					case 'changed':
					case 'new':
						$wpdb->update(
							$tablename,
							array('status' => ""),
							array('id' => $store_entry['id']),
							array('%s'),
							array('%d'));
						$store_filesystem[$i]->status = "";
						break;
				}
			}
			
			foreach ($filesystem as $entry) {
				$found = false;
				foreach ($store_filesystem as $i => $store_entry) {
					$store_entry = (array)$store_entry;
					
					if ($entry['path'] === $store_entry['path']) {
						$found = true;
						
						if ($store_entry['sha1'] !== $entry['sha1']) {
							// Changed
							
							$wpdb->update(
								$tablename,
								array('status' => "changed"),
								array('id' => $store_entry['id']),
								array('%s'),
								array('%d'));
							
							$not_changes_filesystem = false;
						}
						
						unset($store_filesystem[$i]);
						
						break;
					}
				}
				
				if (!$found) {
					// New
					$value = array(
						'path' => $entry['path'],
						'status' => 'new',
						'sha1' => $entry['sha1']);
					
					$wpdb->insert(
						$tablename,
						$value);
					
					$not_changes_filesystem = false;
				}
			}
			
			
			
			// Check the files unpaired because they are deleted files
			foreach ($store_filesystem as $store_entry) {
				// Deleted
				
				$wpdb->update(
					$tablename,
					array('status' => "deleted"),
					array('id' => $store_entry->id),
					array('%s'),
					array('%d'));
				
				$not_changes_filesystem = false;
			}
		}
		
		$audit_files['status'] = (int)$not_changes_filesystem;
		$audit_files['last_execution'] = time();
		
		update_option($pfms_wp->prefix . "audit_files", $audit_files);
	}
	
	private function audit_passwords_strength() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$table_user_weak_password =
			$wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		
		$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
			array(
				'last_execution' => null,
				'status' => null));
		
		//For first versions it is store in data plugin directory.
		$weak_passwords_list = file(
			plugin_dir_path(__FILE__) . "../data/password_dictionary.default.txt");
		
		//Get all users (included the disabled users because they can return to enabled)
		$users = get_users();
		
		
		$not_exists_weak_users = true;
		foreach ($users as $user) {
			foreach ($weak_passwords_list as $weak_password) {
				$weak = wp_check_password(
					trim($weak_password), $user->data->user_pass, $user->ID);
				
				$user_login = $user->data->user_login;
				
				if ($weak) {
					$not_exists_weak_users = false;
					
					// Store the user with weak password.
					$wpdb->delete(
						$table_user_weak_password,
						array('user' => $user_login));
					$wpdb->insert(
						$table_user_weak_password,
						array('user' => $user_login));
					
					break;
				}
				else {
					// Delete user with previous weak password.
					$wpdb->delete(
						$table_user_weak_password,
						array('user' => $user_login));
				}
			}
		}
		
		$audit_password['status'] = (int)$not_exists_weak_users;
		$last_execution = time();
		$audit_password['last_execution'] = $last_execution;
		
		update_option($pfms_wp->prefix . "audit_passwords", $audit_password);
	}
	
	private function check_admin_user_enabled() {
		//Check all users (included the disabled users because they can return to enabled)
		$user = get_user_by('login', 'admin');
		
		return empty($user);
	}
	//=== END ==== CHECKS ==============================================
	
	
	//=== INIT === CRON HOOKS CODE =====================================
	public static function cron_audit_passwords_strength() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_passwords_strength();
	}
	
	public static function cron_audit_files() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_files();
	}
	//=== END ==== CRON HOOKS CODE =====================================
	
	
	//=== INIT === AJAX HOOKS CODE =====================================
	public static function ajax() {
		error_log("ajax");
		?>
		<script type="text/javascript" >
			jQuery(document).ready(function($) {
				
			});
			
			function check_admin_user_enabled() {
				var data = {
					'action': 'check_admin_user_enabled'
				};
				
				jQuery("#admin_user_enabled").empty();
				jQuery("#admin_user_enabled").append(
					jQuery("#ajax_loading").clone());
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#admin_user_enabled").empty();
					
					if (response.result) {
						jQuery("#admin_user_enabled").append(
							jQuery("#ajax_result_ok").clone());
					}
					else {
						jQuery("#admin_user_enabled").append(
							jQuery("#ajax_result_fail").clone());
					}
				},
				"json");
			}
			
			function force_cron_audit_password() {
				var data = {
					'action': 'force_cron_audit_password'
				};
				
				jQuery("#audit_password_status").empty();
				jQuery("#audit_password_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery("#audit_password_last_execute").empty();
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#audit_password_status").empty();
					
					if (response.status) {
						jQuery("#audit_password_status").append(
							jQuery("#ajax_result_ok").clone());
					}
					else {
						jQuery("#audit_password_status").append(
							jQuery("#ajax_result_fail").clone());
					}
					
					jQuery("#audit_password_last_execute").append(
						response.last_execution);
				},
				"json");
			}
			
			function force_cron_audit_files() {
				var data = {
					'action': 'force_cron_audit_files'
				};
				
				jQuery("#audit_files_status").empty();
				jQuery("#audit_files_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery("#audit_files_last_execute").empty();
				
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#audit_files_status").empty();
					
					if (response.status) {
						jQuery("#audit_files_status").append(
							jQuery("#ajax_result_ok").clone());
					}
					else {
						jQuery("#audit_files_status").append(
							jQuery("#ajax_result_fail").clone());
					}
					
					jQuery("#audit_files_last_execute").append(
						response.last_execution);
				},
				"json");
			}
			
			function show_weak_user_dialog() {
				var status = jQuery("#audit_password_status img").attr("id");
				
				if (status !== "ajax_result_fail") {
					return;
				}
				
				var data = {
					'action': 'get_list_users_with_weak_password'
				};
				
				jQuery("#audit_password_status").empty();
				jQuery("#audit_password_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery.post(ajaxurl, data, function(response) {
					var list_users = jQuery.makeArray(response.list_users);
					
					jQuery("#audit_password_status").empty();
					jQuery("#audit_password_status").append(
							jQuery("#ajax_result_fail").clone());
					
					var dialog_weak_user =
					jQuery("<div id='dialog_weak_user' title='<?php esc_attr_e("List weak users");?>' />")
						.html(list_users.join('<br />'))
						.appendTo("body");
					
					dialog_weak_user.dialog({
						'dialogClass' : 'wp-dialog',
						'height': 200,
						'modal' : true,
						'autoOpen' : false,
						'closeOnEscape' : true})
						.dialog('open');
					
				},
				"json");
			}
			
			function show_files_dialog() {
				var status = jQuery("#audit_files_status img").attr("id");
				
				if (status !== "ajax_result_fail") {
					return;
				}
				
				var data = {
					'action': 'get_list_audit_files'
				};
				
				jQuery("#audit_files_status").empty();
				jQuery("#audit_files_status").append(
					jQuery("#ajax_loading").clone());
				
				jQuery.post(ajaxurl, data, function(response) {
					var list_files = jQuery.makeArray(response.list_files);
					
					jQuery("#audit_files_status").empty();
					jQuery("#audit_files_status").append(
							jQuery("#ajax_result_fail").clone());
					
					var $table = jQuery("<table width='100%'>")
						.append("<thead><tr><th><?php esc_html_e("Path");?></th><th><?php esc_html_e("Status");?></th><th><?php esc_html_e("Writable others");?></th></tr></thead>");
					jQuery.each(list_files, function(i, file) {
						var tr = "<tr>";
						
						jQuery.each(file, function(i, item) {
							if (i == "writable_others") {
								tr = tr + "<td align='center'>";
							}
							else {
								tr = tr + "<td>";
							}
							tr = tr + item + "</td>";
						});
						tr = tr + "</tr>";
						
						$table.append(tr);
					});
					
					var dialog_weak_user =
					jQuery("<div id='dialog_list_files' title='<?php esc_attr_e("List change or new files");?>' />")
						.append($table)
						.appendTo("body");
					
					dialog_weak_user.dialog({
						'dialogClass' : 'wp-dialog',
						'height': 200,
						'minWidth': 900,
						'modal' : true,
						'autoOpen' : false,
						'closeOnEscape' : true})
						.dialog('open');
					
				},
				"json");
			}
		</script>
		<?php
	}
	
	public static function ajax_force_cron_audit_files() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->debug) {
			$pfms_wp->ajax_check_audit_files();
		}
		else {
			wp_reschedule_event(time(), 'daily', 'cron_audit_files');
			
			$audit_files = get_option($pfms_wp->prefix . "audit_files",
				array(
					'last_execution' => null,
					'status' => null));
			$audit_files['last_execution'] = esc_html(_("Scheduled"));
			echo json_encode($audit_files);
			
			wp_die();
		}
	}
	
	public static function ajax_check_audit_files() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_files();
		
		$audit_files = get_option($pfms_wp->prefix . "audit_files",
			array(
				'last_execution' => null,
				'status' => null));
		
		if (empty($audit_files['last_execution'])) {
			$audit_files['last_execution'] = esc_html(_("Never execute"));
		}
		else {
			$audit_files['last_execution'] = esc_html(
				date_i18n(get_option('date_format'),
					$audit_files['last_execution']));
		}
		
		echo json_encode($audit_files);
		
		wp_die();
	}
	
	public static function ajax_force_cron_audit_password() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->debug) {
			$pfms_wp->ajax_check_audit_password();
		}
		else {
			wp_reschedule_event(time(), 'daily', 'cron_audit_passwords_strength');
			
			$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
				array(
					'last_execution' => null,
					'status' => null));
			$audit_password['last_execution'] = esc_html(_("Scheduled"));
			echo json_encode($audit_password);
			
			wp_die();
		}
	}
	
	public static function ajax_check_audit_password() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$pfms_wp->audit_passwords_strength();
		
		$audit_password = get_option($pfms_wp->prefix . "audit_passwords",
			array(
				'last_execution' => null,
				'status' => null));
		
		if (empty($audit_password['last_execution'])) {
			$audit_password['last_execution'] = esc_html(_("Never execute"));
		}
		else {
			$audit_password['last_execution'] = esc_html(
				date_i18n(get_option('date_format'),
					$audit_password['last_execution']));
		}
		
		echo json_encode($audit_password);
		
		wp_die();
	}
	
	public static function ajax_check_admin_user_enabled() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		if ($pfms_wp->check_admin_user_enabled()) {
			echo json_encode(array('result' => 1));
		}
		else {
			echo json_encode(array('result' => 0));
		}
		
		wp_die();
	}
	
	public static function ajax_get_list_users_with_weak_password() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "audit_users_weak_password";
		$users = $wpdb->get_results("SELECT user FROM `" . $tablename . "`");
		if (empty($users))
			$users = array();
		
		$return = array();
		foreach ($users as $user) {
			$return[] = $user->user;
		}
		
		echo json_encode(array('list_users' => $return));
		
		wp_die();
	}
	
	public static function ajax_get_list_audit_files() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
		$filesystem = $wpdb->get_results("
			SELECT path, status, writable_others
			FROM `" . $tablename . "`
			WHERE status != '' or writable_others = 1
			ORDER BY status DESC");
		if (empty($filesystem))
			$filesystem = array();
		
		$return = array();
		foreach ($filesystem as $entry) {
			$icon = "";
			if ($entry->writable_others) {
				$icon = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
			}
			else {
				$icon = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
			}
			
			
			$return[] = array(
				'path' => $entry->path,
				'status' => $entry->status,
				'writable_others' => $icon);
		}
		
		echo json_encode(array('list_files' => $return));
		
		wp_die();
	}
	//=== END ==== AJAX HOOKS CODE =====================================
}
?>