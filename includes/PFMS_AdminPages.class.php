<?php
/*
Copyright (c) 2017-2017 Artica Soluciones Tecnologicas

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

require_once($plugin_dir_path . "pagination.class.php");

class PFMS_AdminPages {
	
	//=== INIT === SINGLETON CODE ======================================
	private static $instance = null;
	
	public $GOOGLE_ANALYTICATOR_CLIENTID = '306233129774-fecd8o976qcvibndd2htkelbo967vd2h.apps.googleusercontent.com';
	public $GOOGLE_ANALYTICATOR_CLIENTSECRET = 'eVx0Uqn__0kptR1vWxWrP7qW'; //don't worry - this don't need to be secret in our case
	public $GOOGLE_ANALYTICATOR_REDIRECT = 'urn:ietf:wg:oauth:2.0:oob';
	public $GOOGLE_ANALYTICATOR_SCOPE = 'https://www.googleapis.com/auth/analytics';//.readonly
	
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	//=== END ==== SINGLETON CODE ======================================
	
	
	private function __construct() {
	}
	

	//=== ACCESS CONTROL Dashboard =====================================
	public static function print_access_control_list_dashboard() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$list = $pfms_wp->get_last_access_control();
		
		if (empty($list)) {
			?>
			<p><?php esc_html_e("Empty data");?></p>
			<?php
			return;
		}
		?>
		
		<table id="list_access_control_dashboard" class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e("Time");?></th>
					<th><?php esc_html_e("Type");?></th>
					<th><?php esc_html_e("Data");?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($list as $item) {
					?>
					<tr>
						<td><?php esc_html_e($item->timestamp);?></td>
						<td><?php esc_html_e($item->type);?></td>
						<td><?php esc_html_e($item->data);?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		
		<script type="text/javascript">
			jQuery(function() {
				jQuery('#list_access_control_dashboard').scrollTableBody({'rowsToDisplay': 5});
			});
		</script>
		<?php
	}
	//=== END === ACCESS CONTROL Dashboard =============================


	//=== DASHBOARD VIEW =============================================== 
	public static function show_dashboard() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_ap = PFMS_AdminPages::getInstance();
		
		$data = $pfms_wp->get_dashboard_data();
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Monitoring dashboard");?></h2>

			<div id="col-container">		
				<div id="col-right">
					<div class="col-wrap">					
						<div class="card">
							<h2 class="title"><?php esc_html_e("Access Control");?></h2>
							<?php
							$pfms_ap->print_access_control_list_dashboard();
							?>
							<br/>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e("Control item");?></th>
										<th><?php esc_html_e("Status");?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?php esc_html_e("Login page protection");?></td>
										<td>
											<?php
											if ($data['access_control']['activate_login_rename']) { 
												?>
												<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
												<?php
											}
											else {
												?>
												<a href="#" onclick="show_activated_rename_login();">
													<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
												</a>
												<?php
											}
											?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e("Login page protected by reCaptcha");?></td>
										<td>
											<?php
											if ( ($data['access_control']['activated_recaptcha'] == '1')
												&& ($data['access_control']['site_key'] != '') && ($data['access_control']['secret'] != '') ) {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
												<?php
											}
											else {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
												<?php
											}
											?>
										</td>
									</tr>

								</tbody>
							</table>
						</div>			
						<div class="card">
							<h2 class="title"><?php esc_html_e("System Security");?></h2>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e("Control item");?></th>
										<th><?php esc_html_e("Status");?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?php esc_html_e("Malicious PHP code upload protection");?></td>
										<td>
											<?php
											if ($data['system_security']['protect_upload_php_code']) {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
												<?php
											}
											else {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
												<?php
											}
											?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e("Robots.txt security enhancement");?></td>
										<td>
											<?php
											if ($data['system_security']['installed_robot_txt']) {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
												<?php
											}
											else {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
												<?php
											}
											?>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e("WP generator disabled");?></td>
										<td>
											<?php
											if ($data['system_security']['wp_generator_disable']) {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
												<?php
											}
											else {
												?>
												<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
												<?php
											}
											?>
										</td>
									</tr>									
								</tbody>
							</table>
						</div>				
					</div><!-- /col-wrap -->
				</div><!-- /col-right -->
				<?php $options_access_control = get_option('pfmswp-options-access_control'); ?>
				<?php $options = get_option('pfmswp-options');?>
				<div id="col-left">
					<div class="col-wrap">
						<div class="card">
							<h2 class="title"><?php esc_html_e("Monitoring");?></h2>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e("Monitored item");?></th>
										<th><?php esc_html_e("Status");?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									if ($data['monitoring']['enabled_check_admin']) {
									?>
										<tr>
											<td><?php esc_html_e('Default admin user check');?></td>
											<td>
												<a href="javascript: check_admin_user_enabled();">
													<div id="admin_user_enabled">
														<?php
														if ($data['monitoring']['check_admin']) {
															?>
															<img id ="ajax_result_ok"
																src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
															<?php
														}
														else {
															?>
															<img id ="ajax_result_fail"
																src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
															<?php
														}
														?>
													</div>
												</a>
											</td>
										</tr>
									<?php
									}
									?>
									<tr>
										<td><?php esc_html_e('Password strength audit');?></td>
										<td>
											<a href="#" onclick="show_weak_user_dialog();">
											<span id="audit_password_status">
												<?php
												if ($data['monitoring']['audit_password']['status']) {
													?>
													<img id ="ajax_result_ok"
														src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
													<?php
												}
												else {
													?>
													<img id ="ajax_result_fail"
														src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
													<?php
												}
												?>
											</span>
											</a>
											<br />
											<a href="javascript: force_cron_audit_password();" style="font-size: 10px;">
												<span id="audit_password_last_execute">
													<?php
													if (empty($data['monitoring']['audit_password']['last_execution'])) {
														esc_html_e('Never execute');
													}
													else {
														echo esc_html(
															date_i18n(
																get_option('date_format'), $data['monitoring']['audit_password']['last_execution']));
													}
													?>
												</span>
											</a>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e('Filesystem audit');?></td>
										<td>
											<a href="#" onclick="show_files_dialog();">
											<span id="audit_files_status">
												<?php
												if ($data['monitoring']['filesystem_audit']) {
													?>
													<img id ="ajax_result_ok" src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
													<?php
												}
												else {
													?>
													<img id ="ajax_result_fail" src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
													<?php
												}
												?>
											</span>
											</a>
											<br />
											<a href="javascript: force_cron_audit_files();" style="font-size: 10px;">
												<span id="audit_files_last_execute">
													<?php
													if (empty($data['monitoring']['audit_files']['last_execution'])) {
														esc_html_e('Never execute');
													}
													else {
														echo esc_html(
															date_i18n(
																get_option('date_format'), $data['monitoring']['audit_files']['last_execution']));
													}
													?>
												</span>
											</a>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e('New Coments in last 24h');?></td>
										<td>
											<span style="color: #00000; font-weight: bolder;">
											<?php
											echo esc_html(
												$pfms_wp->get_count_comments_last_day());
											?>
											</span>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e('New posts in last 24h');?></td>
										<td>
											<span style="color: #00000; font-weight: bolder;">
												<?php echo esc_html( $pfms_wp->get_count_posts_last_day() ); ?>
											</span>
										</td>
									</tr>
									<?php
									if ($data['monitoring']['enabled_wordpress_updated']) {
									?>
										<tr>
											<td><?php esc_html_e('Wordpress code updated');?></td>
											<td>
												<span id="wordpress_is_updated">
													<?php
													if ($data['monitoring']['wordpress_updated']) {
														?>
														<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
														<?php
													}
													else {
														?>
														<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
														<?php
													}
													?>
												</span>
											</td>
										</tr>
									<?php
									}									
									if ($data['monitoring']['enabled_plugins_updated']) {
									?>
										<tr>
											<td><?php esc_html_e('Plugins code updated');?></td>
											<td>
												<span>
													<img id ="ajax_result_loading_plugins_are_updated" style="display: none;"
														src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="" />
													<img id ="ajax_result_ok_plugins_are_updated"
														style="display: none;"
														src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
													<span id ="ajax_result_fail_plugins_are_updated" style="display: none;">
														<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
														<a href="#" onclick="check_plugins_pending_update();" style="font-size: 10px;">
															<?php esc_html_e("Show");?>
														</a>
													</span>
													<?php
													if ($data['monitoring']['plugins_updated']) {
														?>
														<script type="text/javascript">
															jQuery(document).ready(function($) {
																jQuery("#ajax_result_fail_plugins_are_updated")
																	.hide();
																jQuery("#ajax_result_ok_plugins_are_updated")
																	.show();
															});
														</script>
														<?php
													}
													else {
														?>
														<script type="text/javascript">
															jQuery(document).ready(function($) {
																jQuery("#ajax_result_ok_plugins_are_updated")
																	.hide();
																jQuery("#ajax_result_fail_plugins_are_updated")
																	.show();
															});
														</script>
														<?php
													}
													?>
												</span>
											</td>
										</tr>
									<?php
									}
									?>
									<tr>
										<td><?php esc_html_e('API Rest enabled');?></td>
										<td>											
											<span id="api_rest_plugin">
												<?php
												if ($data['monitoring']['api_rest_plugin']) {
													?>
													<img id ="ajax_result_ok"
														src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
													<?php
												}
												else {
													?>														
													<a href="#" onclick="show_api_rest_plugin();">
														<img id ="ajax_result_fail" src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
													</a>
													<?php
												}
												?>
											</span>											
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e('Wordpress version');?></td>
										<td>
											<span>
												<?php echo esc_html($data['monitoring']['wordpress_version']); ?>
											</span>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e('Wordpress sitename');?></td>
										<td>
											<span>
												<?php echo esc_html($data['monitoring']['wordpress_sitename']); ?>
											</span>
										</td>
									</tr>
									<tr>
										<td><?php esc_html_e('PandoraFMS WP version');?></td>
										<td>
											<span>
												<?php echo esc_html($data['monitoring']['pandorafms_wp_version']); ?>
											</span>
										</td>
									</tr>
									<tr>
										<td><?php echo('Recent brute force attempts in '. $options["api_data_newer_minutes"] .' minutes.');?></td>
										<td>
											<span>
												<?php													
													if ($data['monitoring']['brute_force_attempts'] === 1) {												
														?>
														<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
														<?php
													}
													else {
														?>
														<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
														<?php
													}
												?>
											</span>
										</td>
									</tr>
								</tbody>
							</table>
							<div style="display: none;">
								<img id="ajax_loading" src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="" />
								<img id="ajax_result_ok" src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
								<img id="ajax_result_fail" src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
							</div>
						</div>
					</div>
				</div><!-- /col-left -->
			</div><!-- /container -->
		</div><!-- /wrap -->
		<?php
	}
	//=== END === DASHBOARD VIEW =======================================


	//=== ACCESS CONTROL VIEW ==========================================
	public static function show_access_control() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";

		?>

		<div class="wrap">
			<h2><?php esc_html_e("Access Control");?></h2>
		</div>
		
		<p>This section manages access to your Wordpress. Here you can define if you want to be warned on some events and you can see a full log of all user interactions with your site. <br/>This information is automatically purged each 7 days by default. You can change this time in General Setup.</p>
		
		<?php
			$user_stats = $wpdb->get_results( "SELECT *	FROM `" . $tablename . "` ORDER BY timestamp DESC" );
		?>
			<table id="list_access_control" class="widefat striped" >
				<thead>
					<tr>
						<th><?php esc_html_e("User");?></th>
						<th><?php esc_html_e("IP");?></th>
						<th><?php esc_html_e("Action");?></th>
						<th><?php esc_html_e("Count");?></th>
						<th><?php esc_html_e("Last time");?></th>
					</tr>
				</thead>
				<tbody>
		<?php
			if (empty($user_stats)) {
				?>				
				<tr>
					<td colspan="5">
						<p><strong><?php esc_html_e("Empty list");?></strong></p>
					</td>
				</tr>
			<?php
			}
			else {
				foreach ($user_stats as $user) {
					?>
					<tr>
						<td><?php echo esc_html($user->user);?></td>
						<td><?php echo esc_html($user->ip_user);?></td>
						<td><?php echo esc_html($user->action);?></td>
						<td><?php echo esc_html($user->count);?></td>
						<td><?php echo esc_html($user->timestamp);?></td>
					</tr>
					<?php
				}
			}
					?>
				</tbody>
			</table>

				<script type="text/javascript" >

					jQuery(function() {
						jQuery('#list_access_control').scrollTableBody({'rowsToDisplay': 5});
					});

				</script>

		<div class="wrap">		
			<form method="post" action="options.php">
				<?php 
					settings_fields('pfmswp-settings-group-access_control');
					$options = get_option('pfmswp-options-access_control');
					$pfms_ap = PFMS_AdminPages::getInstance();
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email on new account creation");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span> 
										<?php esc_html_e("Email on new account creation");?> 
									</span>
								</legend>
								<label for="pfmswp-options-access_control[email_new_account]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[email_new_account]"
										value="1"
										<?php checked($options['email_new_account'], 1, true); ?>
									/>
									<?php esc_html_e("Send email with each new account.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email on login user");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Email on login user");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[email_user_login]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[email_user_login]"
										value="1"
										<?php checked($options['email_user_login'], 1, true); ?>
									/>
									<?php esc_html_e("Send email with each login.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email on user email change");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Email on user email change");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[email_change_email]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[email_change_email]"
										value="1"
										<?php checked($options['email_change_email'], 1, true); ?>
									/>
									<?php esc_html_e("Send email when any user change the email.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email on new plugin");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Email on new plugin");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[email_plugin_new]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[email_plugin_new]"
										value="1"
										<?php checked($options['email_plugin_new'], 1, true); ?>
									/>
									<?php esc_html_e("Send email when add new plugin.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email on new theme");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Email on new theme");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[email_theme_new]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[email_theme_new]"
										value="1"
										<?php
										checked($options['email_theme_new'], 1, true);
										?>
										/>
									<?php esc_html_e("Send email when add new theme.");?>
								</label>
							</fieldset>
						</td>
					</tr>				
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Activate login rename");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Activate login rename");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[activate_login_rename]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[activate_login_rename]"
										value="1"
										<?php checked($options['activate_login_rename'], 1, true); ?>
									/>
									<?php esc_html_e("Activate the plugin 'Rename wp-login.php' and install.");?>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("The rename login page.");  ?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[login_rename_page]">
									<?php
										if (get_option('permalink_structure')) {
											echo '<code>' .
												trailingslashit(home_url()) .
												'</code> ' .
												'<input
													type="text" name="pfmswp-options-access_control[login_rename_page]"
													value="' . esc_attr($options['login_rename_page']) . '">' .
												($pfms_ap->use_trailing_slashes() ?
													' <code>/</code>' :
													'');
										}
										else {
											echo '<code>' .
												trailingslashit(home_url()) .
												'?</code> ' .
												'<input
													type="text" name="pfmswp-options-access_control[login_rename_page]"
													value="' . esc_attr($options['login_rename_page'])  . '">';
										}
									?>						
									<p class="description">
										<?php esc_html_e("The rename login page."); ?>
									</p>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Limit login attempts");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Limit login attempts");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[bruteforce_attack_protection]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[bruteforce_attack_protection]"
										value="1"
										<?php checked($options['bruteforce_attack_protection'], 1, true); ?>
									/>
									<?php esc_html_e("Active to enable bruteforce attack protection.");?>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Max. Number of invalid login attemps before account locking.");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[bruteforce_attack_attempts]">
									<input
										class="small-text"
										type="text"
										name="pfmswp-options-access_control[bruteforce_attack_attempts]"
										value="<?php echo esc_attr($options['bruteforce_attack_attempts']);?>"
									/>
									<span class="description">
										<?php esc_html_e("Login attempts limit"); ?>
									</span>
								</label>
								<br/>
								<label for="pfmswp-options-access_control[wait_protect_bruteforce_login_seconds]">
									<input
										class="small-text"
										type="text"
										name="pfmswp-options-access_control[wait_protect_bruteforce_login_seconds]"
										value="<?php echo esc_attr($options['wait_protect_bruteforce_login_seconds']);?>"
									/>
									<span class="description">
										<?php esc_html_e("Login lockdown time"); ?>
									</span>
								</label>
								<br/>
								<label for="pfmswp-options-access_control[h_recent_brute_force]">
									<input
									    class="small-text"
										type="text"
										name="pfmswp-options-access_control[h_recent_brute_force]"
										value="<?php echo $options['h_recent_brute_force'];?>" 
									/>
									<span class="description">
										<?php
											esc_html_e("seconds. How long should such failed attempts occur to freeze the account?");
										?>
									</span>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Black list IPs");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Black list IPs");?>
									</span>
								</legend>
								<p>
									<textarea
										name="pfmswp-options-access_control[blacklist_ips]"
										class="large-text code"
										rows="10"><?php 
										echo esc_textarea($options['blacklist_ips']); ?></textarea>
								</p>
							</fieldset>
							<!--<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php //esc_html_e("Redirect URL if the ip is banned.");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[url_redirect_ip_banned]">
									<p class="description">-->
										<?php
											//esc_html_e("Full URL starting with the 'http://' for to send banned ips. (Opcional)");
										?>
									<!--</p>
									<input
										class="regular-text"
										type="text"
										name="pfmswp-options-access_control[url_redirect_ip_banned]"
										value="<?php //echo esc_attr($options['url_redirect_ip_banned']);?>"
									/>
								</label>
							</fieldset>-->
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Login recapcha");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Login repcatcha");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[activate_login_recaptcha]">
									<input
										type="checkbox"
										name="pfmswp-options-access_control[activate_login_recaptcha]"
										value="1"
										<?php checked($options['activate_login_recaptcha'], 1, true); ?>
									/>
									<?php esc_html_e("Activate the reCaptcha in the login page.");?>
								</label>
									<p class="description">
										<?php
											echo("You need to get your free <a href='https://www.google.com/recaptcha/intro/index.html'>ReCaptcha keys</a>.");
										?>
									</p>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Site key");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[site_key]">
									<p class="description">
										<?php esc_html_e("Site key."); ?>
									</p>
									<input
										class="regular-text"
										type="text"
										name="pfmswp-options-access_control[site_key]"
										value="<?php echo esc_attr($options['site_key']);?>"
									/>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Secret key");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[secret]">
									<p class="description">
										<?php esc_html_e("Secret key."); ?>
									</p>
									<input
										class="regular-text"
										type="text"
										name="pfmswp-options-access_control[secret]"
										value="<?php echo esc_attr($options['secret']);?>"
									/>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Disable the XMLRPC of Wordpress");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Disable the XMLRPC of Wordpress");?>
									</span>
								</legend>
								<label for="pfmswp-options-access_control[disable_xmlrpc]">
										<input 
										type="checkbox" 
										name="pfmswp-options-access_control[disable_xmlrpc]"
										value="1" 
										<?php 
											checked($options['disable_xmlrpc'], 1, true);
											$pfms_wp->check_disable_xmlrpc();
										?> 
										/>
									<?php esc_html_e("Active to disable XMLRPC.");?>
								</label>
							</fieldset>
							<br />
						</td>
					</tr>
				</table>
				<p class="submit">
					<input
						type="submit" name="submit-access_control" id="submit-access_control"
						class="button button-primary"
						value="<?php esc_attr_e("Save Changes");?>" 
						onclick="empty_rename_login_page_or_repatcha();"
					/>
				</p>
			</form>
		</div>
		<?php
	}
	//=== END === ACCESS CONTROL VIEW ==================================


	//=== SYSTEM SECURITY VIEW =========================================
	public static function show_system_security() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_ap = PFMS_AdminPages::getInstance();
		
		?>
		<div class="wrap">
			<h2><?php esc_html_e("System Security");?></h2>
		</div>
		<p>Options to enforce security on your site.</p>
		
		<div class="wrap">
			<h3><?php esc_html_e("Bruteforce attack logs");?></h3>
			<?php
			$list = $pfms_wp->get_list_login_lockout();
			if (empty($list))
				$list = array();
			
			if (empty($list)) {
				?>
				<p><?php esc_html_e("Empty data");?></p>
				<?php
			}
			else {
				?>
				<table id="list_bruteforce_attack_logs" class="widefat striped">
					<thead >
						<tr>
							<th><?php esc_html_e("User");?></th>
							<th><?php esc_html_e("Count");?></th>
							<th><?php esc_html_e("Last time");?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($list as $entry) {
							?>
							<tr>
								<td><?php esc_html_e($entry['user']);?></td>
								<td><?php esc_html_e($entry['count']);?></td>
								<td><?php esc_html_e($entry['time']);?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>

				<script type="text/javascript" >

					jQuery(function() {
						jQuery('#list_bruteforce_attack_logs').scrollTableBody({'rowsToDisplay': 5});
					});

				</script>

				<?php
			}
			?>
			
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group-system_security');?>
				<?php $options = get_option('pfmswp-options-system_security');?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Check of \"admin\" user enabled");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Check of \"admin\" user enabled");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[enabled_check_admin]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[enabled_check_admin]"
										value="1"
										<?php checked($options['enabled_check_admin'], 1, true); ?>
									/>
									<?php esc_html_e("Active to check if \"admin\" exists.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Check core updates enabled");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Check core updates enabled");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[enabled_wordpress_updated]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[enabled_wordpress_updated]"
										value="1"
										<?php checked($options['enabled_wordpress_updated'], 1, true); ?>
									/>
									<?php esc_html_e("Active to check the core updates available.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Check plugins updates enabled");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Check plugin updates enabled");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[enabled_plugins_updated]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[enabled_plugins_updated]"
										value="1"
										<?php checked($options['enabled_plugins_updated'], 1, true); ?>
									/>
									<?php esc_html_e("Active to check the plugins updates available.");?>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<p class="description">
									<?php esc_html_e("Black list plugins to check updates.");?>
								</p>
								<p>
									<textarea
										name="pfmswp-options-system_security[blacklist_plugins_check_update]"
										class="large-text code"
										rows="10"><?php 
										echo esc_textarea($options['blacklist_plugins_check_update']); ?></textarea>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Protect upload of PHP Code");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Protect upload of PHP Code");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[upload_htaccess]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[upload_htaccess]"
										value="1"
										<?php checked($options['upload_htaccess'], 1, true); ?>
									/>
									<?php esc_html_e("Active and set a .htaccess in upload directory.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Robots.txt enhancement");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Robots.txt enhancement");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[upload_robots_txt]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[upload_robots_txt]"
										value="1"
										<?php checked($options['upload_robots_txt'], 1, true); ?>
									/>
									<?php esc_html_e("Active and set a custom Robots.txt.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("WP Generator disable");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("WP Generator disable");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[wp_generator_disable]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[wp_generator_disable]"
										value="1"
										<?php checked($options['wp_generator_disable'], 1, true); ?>
									/>
									<?php esc_html_e("Disable the WP Generator in wp_head.");?>
								</label>
							</fieldset>
						</td>
					</tr>					
				</table>
				<p class="submit">
					<input
						type="submit" name="submit" id="submit"
						class="button button-primary"
						value="<?php esc_attr_e("Save Changes");?>" 
					/>
				</p>
			</form>
		</div>
		<?php
	}
	//=== END === SYSTEM SECURITY VIEW =================================


	//=== GENERAL SETUP VIEW ===========================================
	public static function show_general_setup() {
		$pfms_ap = PFMS_AdminPages::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		?>
		<div class="wrap">
			<h2><?php esc_html_e("General Setup");?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group');?>
				<?php $options = get_option('pfmswp-options');?>			
				<table class="form-table">
					<!--<tr valign="top">
						<th scope="row">
							<?php //esc_html_e("Footer");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php //esc_html_e("Footer");?>
									</span>
								</legend>
								<label for="pfmswp-options[show_footer]">
									<input
										type="checkbox"
										name="pfmswp-options[show_footer]"
										value="1"
										<?php
										//checked($options['show_footer'], 1, true);
										// />
										?>
									<?php //esc_html_e("Show");?>
								<!--</label>
							</fieldset>
						</td>
					</tr>-->
					<tr>
						<th scope="row">
							<h3><?php esc_html_e("API Settings");?></h3>
						</th>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email for notifications");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span> 
										<?php esc_html_e("Email for notifications");?> 
									</span>
								</legend>
								<label for="pfmswp-options[email_notifications]">
									<input
										class="regular-text"
										type="text"
										name="pfmswp-options[email_notifications]"
										value="<?php echo esc_attr($options['email_notifications']);?>"
									/>
									<p class="description">
										<?php
										esc_html_e("If this address is not set, the notifications uses the default admin email.");
										?>
									</p>
								</label>
							</fieldset>
						</td>
					</tr>
					<!--<tr valign="top">
						<th scope="row">
							<?php //esc_html_e("API Password");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php //esc_html_e("API password");?>
									</span>
								</legend>
								<label for="pfmswp-options[api_password]">
									<input
										class="regular-text"
										type="password"
										name="pfmswp-options[api_password]"
										value="<?php //echo esc_attr($options['api_password']);?>"
										/>
								</label>
							</fieldset>
						</td>
					</tr>-->
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("API IPs");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span> 
										<?php esc_html_e("API Source allowed IPs");?> 
									</span>
								</legend>
								<p>
									<textarea name="pfmswp-options[api_ip]" class="large-text code" rows="3"><?php 
										echo esc_textarea($options['api_ip']); ?></textarea>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("API show alert on data newer than X minutes");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Show alert on data newer than X minutes");?>
									</span>
								</legend>
								<label for="pfmswp-options[api_data_newer_minutes]">
									<input
										class="small-text"
										type="text"
										name="pfmswp-options[api_data_newer_minutes]"
										value="<?php echo esc_attr($options['api_data_newer_minutes']);?>"
									/>
									<span class="description"> <?php esc_html_e("Minutes"); ?> </span>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<h3><?php esc_html_e("Delete Logs Time");?></h3>
						</th>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Clean status deleted ");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span> 
										<?php esc_html_e("Clean status deleted ");?> 
									</span>
								</legend>
								<p>
									<input
										class="small-text"
										type="text"
										name="pfmswp-options[deleted_time]"
										value="<?php echo esc_attr($options['deleted_time']); ?>"
									/>
									<span class="description"> 
										<?php esc_html_e("Clean fields of filesystem table with status deleted for data older than X days (7 days default)"); ?> 
									</span>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Clean status new");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Clean status new");?>
									</span>
								</legend>
								<p>
									<input
										class="small-text"
										type="text"
										name="pfmswp-options[new_time]"
										value="<?php echo esc_attr($options['new_time']); ?>"
									/>
									<span class="description">
										<?php esc_html_e("Remove the status new on fields of filesystem table for data older than X days (7 days default)"); ?>
									</span>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input
						type="submit" name="submit" id="submit"
						class="button button-primary"
						value="<?php esc_attr_e("Save Changes");?>" 
					/>
				</p>
			</form>
		</div>
		<?php
	}
	//=== END === GENERAL SETUP VIEW ===================================
	

	//=== FILESYSTEM STATUS VIEW =======================================
	public static function show_filesystem_status() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();	
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";

		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$limit = 10; // number of rows in page
		$offset = ( $pagenum - 1 ) * $limit; //10,20,30,30.....

		$list = $wpdb->get_results("
			SELECT id, path, status, writable_others, original, infected 
			FROM `$tablename`
			WHERE  status NOT IN ('skyped','') OR ( status IN ('') AND ( writable_others = 1 OR infected = 'yes' OR original = 'no' ) )
			ORDER BY status DESC  LIMIT $offset, $limit"); 

		?>

		<div class="wrap">
			<h2><?php esc_html_e("Filesystem Status");?></h2>
			<table id="list_filesystem_pagination" class="widefat striped"> 
			<thead>
			    <tr>
					<th style="text-align: left; font-weight: bold;"><?php esc_html_e("Path");?></th>
					<th style="text-align: center; font-weight: bold;"><?php esc_html_e("Date");?></th>
					<th style="text-align: center; font-weight: bold;"><?php esc_html_e("Status");?></th>
					<th style="text-align: center; font-weight: bold;"><?php esc_html_e("No Writable others");?></th>
					<th style="text-align: center; font-weight: bold;"><?php esc_html_e("Original");?></th>
					<th style="text-align: center; font-weight: bold;"><?php esc_html_e("No Infected");?></th>
			</tr>
			</thead>
			<tbody>
		<?php

		if (empty($list))
			$list = array();
		
		if (empty($list)) {
			?>
			<tr>
				<td colspan="6"><?php esc_html_e("No data available");?></td>
			</tr>
			<?php
		}
		else {
			$total = $wpdb->get_var( "
				SELECT COUNT(`id`)
				id, path, status, writable_others, original, infected 
				FROM `$tablename`
				WHERE  status NOT IN ('skyped','') OR (status IN ('') AND (writable_others = 1 OR infected = 'yes' OR original = 'no' ) )
				ORDER BY status DESC "); 

			$num_of_pages = ceil( $total / $limit );

			$page_links = paginate_links( array(
			    'base' => add_query_arg( 'pagenum', '%#%' ),
			    'format' => '',
			    'prev_text' => __( '&laquo;', 'text-domain' ),
			    'next_text' => __( '&raquo;', 'text-domain' ),
			    'total' => $num_of_pages,
			    'current' => $pagenum
			) );

			if($total == 0) {

				?>
				 <tr>
			        <td colspan="6" ><?php echo 'No data available.'; ?></td>
			     </tr>

				<?php

			} else {

			    $p = new pagination;

				if (count($list) > 0 ) {

					foreach ($list as $i => $value) {

						$array_list = json_decode(json_encode($value), True); 
				           
			            $path = $array_list['path']; 
			            $status = $array_list['status'];
			            $writable_others = $array_list['writable_others'];
			            $original = $array_list['original'];
			            $infected = $array_list['infected'];

						if ($writable_others) {
							$icon = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='writable others yes' />";
						}
						else {
							$icon = "<img  src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='writable others no' />";
						}
										
						$icon_original = "";
						if ($original == "no") {
							$icon_original = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='no original' />";
						}
						else {
							$icon_original = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='yes original' />";
						}
						
						$icon_infected = "";
						if ($infected == "yes") {
							$icon_infected = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='no infected' />";
						}
						else {
							$icon_infected = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='yes infected' />";
						}

						?>
				        <tr>				           
				            <td>
				            	<?php echo $path; ?>				            	
				            </td>
							<td style="text-align: center;"> 
								<?php
								if (file_exists($path))
									echo date_i18n(get_option('date_format'), filemtime($path));
								else
									echo "[Missing file]";
								?>
							</td>
				            <td style="text-align: center;"><?php echo $status; ?></td>    
				            <td style="text-align: center;"><?php echo $icon; ?></td>
				            <td style="text-align: center;"><?php echo $icon_original; ?></td>  
				            <td style="text-align: center;"><?php echo $icon_infected; ?></td>
				        </tr>
						<?php 
					}//foreach 
				} 
				else { 
					?>
						<tr>
							<td colspan="6"><?php esc_html_e("No data found");?></td>
						</tr>
					<?php 
				} 
				?>
			</tbody>
		</table>
				<?php

				if ( $page_links ) {
				    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>'; 
				}

			}//else prints the table

		}//else table is not empty
		?>

			<form method="post" action="options.php">	
				<?php settings_fields('pfmswp-settings-group-filesystem');?>
				<?php 
					$options_filesystem = get_option('pfmswp-options-filesystem');
					$options_filesystem = $pfms_wp->sanitize_options_filesystem($options_filesystem);
				?>

				<table class="form-table">								
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Check WP integrity.");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Compares your files with original Wordpress source filess.");?>
									</span>
								</legend>
								<label for="pfmswp-options-filesystem[check_filehash_svn]">
									<input
										type="checkbox"
										name="pfmswp-options-filesystem[check_filehash_svn]"
										value="1"
										<?php checked($options_filesystem['check_filehash_svn'], 1, true); ?>
									/>
									<?php esc_html_e("Each 24h (cron) check the file hash of svn files.");?>
								</label>
							</fieldset>
						</td>
					</tr>	
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Black list files");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Black List of files for not to be checked");?>
									</span>
								</legend>
								<p>
									<textarea
										id="id_textarea"
										name="pfmswp-options-filesystem[blacklist_files]"
										class="large-text code"
										rows="10"><?php  
										echo esc_textarea($options_filesystem['blacklist_files']); ?></textarea>
								</p>
							</fieldset>	
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Scan for infected files.");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Scan for infected files.");?>
									</span>
								</legend>
								<label for="pfmswp-options-filesystem[scan_infected_files]">
									<input
										type="checkbox"
										name="pfmswp-options-filesystem[scan_infected_files]"
										value="1"
										<?php checked($options_filesystem['scan_infected_files'], 1, true); ?>
										/>
									<?php esc_html_e("Active to search for malicious code. This is a daily check.");?>
								</label>
							</fieldset>
						</td>
					</tr>					
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Email on files list modified");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Email on files list modified");?>
									</span>
								</legend>
								<label for="pfmswp-options-filesystem[send_email_files_modified]">
									<input
										type="checkbox"
										name="pfmswp-options-filesystem[send_email_files_modified]"
										value="1"
										<?php checked($options_filesystem['send_email_files_modified'], 1, true); ?>
										/>
									<?php esc_html_e("Send email when files list is modified.");?>
								</label>
								<input
									type="submit" name="submit_test_email" id="submit_test_email"
									class="button button-primary"
									value="<?php esc_attr_e("Send Test Email");?>" 
									onclick="send_test_email()" 
								/>	
							</fieldset>
						</td>
					</tr>	
				</table>
				<p class="submit">
					<input
						type="submit" name="submit" id="submit"
						class="button button-primary"
						value="<?php esc_attr_e("Save Changes");?>" />
				</p>
			</form>				
		</div>
		<?php
	}
	//=== END === FILESYSTEM STATUS VIEW ===============================


	private function use_trailing_slashes() {
		return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
	}



} // === END === CLASS PFMS_AdminPages

?>