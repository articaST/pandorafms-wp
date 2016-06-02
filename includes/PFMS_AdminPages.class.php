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

class PFMS_AdminPages {
	
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
		
		<table id="list_access_control" class="widefat striped">
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
				jQuery('#list_access_control').scrollTableBody({'rowsToDisplay': 5});
			});
		</script>
		<?php
	}
	
	public static function show_dashboard() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_ap = PFMS_AdminPages::getInstance();
		
		$data = $pfms_wp->get_dashboard_data();
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Dashboard");?></h2>
		</div>
		<div id="col-container">
		
		<div id="col-right">
			<div class="col-wrap">
				
				<div class="card">
					<h2 class="title"><?php esc_html_e("Access control");?></h2>
					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
					<?php
					$pfms_ap->print_access_control_list_dashboard();
					?>
				</div>
				
				<div class="card">
					<h2 class="title"><?php esc_html_e("System security");?></h2>
					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e("Item");?></th>
								<th><?php esc_html_e("Status");?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php esc_html_e("Protect upload of PHP Code");?></td>
								<td>
									<?php
									if ($data['system_security']['protect_upload_php_code']) {
										?>
										<img
											src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
										<?php
									}
									else {
										?>
										<img
											src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
										<?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e("Robots.txt enhancement");?></td>
								<td>
									<?php
									if ($data['system_security']['installed_robot_txt']) {
										?>
										<img
											src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
										<?php
									}
									else {
										?>
										<img
											src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
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
										<img
											src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
										<?php
									}
									else {
										?>
										<img
											src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
										<?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e("Activated rename login");?></td>
								<td>
									<?php
									if ($data['system_security']['activated_rename_login']) {
										?>
										<img
											src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
										<?php
									}
									else {
										?>
										<a href="#" onclick="show_activated_rename_login();">
											<img
												src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
										</a>
										<?php
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				
			</div>
		</div><!-- /col-right -->
		
		<div id="col-left">
			<div class="col-wrap">
				<div class="card">
					<h2 class="title"><?php esc_html_e("Monitoring");?></h2>
					<p>
						<?php
						esc_html_e("Lorem ipsum dolor sit amet, consectetur adipiscing elit.");
						?>
					</p>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e("Item");?></th>
								<th><?php esc_html_e("Status");?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php esc_html_e('Check of "admin" user enabled');?></td>
								<td>
									<a href="javascript: check_admin_user_enabled();">
										<div id="admin_user_enabled">
											<?php
											if ($data['monitoring']['check_admin']) {
												?>
												<img id ="ajax_result_ok"
													src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
												<?php
											}
											else {
												?>
												<img id ="ajax_result_fail"
													src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
												<?php
											}
											?>
										</div>
									</a>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('Audit of password strength');?></td>
								<td>
									<a href="#" onclick="show_weak_user_dialog();">
									<span id="audit_password_status">
										<?php
										if ($data['monitoring']['audit_password']['status']) {
											?>
											<img id ="ajax_result_ok"
												src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
											<?php
										}
										else {
											?>
											<img id ="ajax_result_fail"
												src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
											<?php
										}
										?>
									</span>
									</a>
									<a href="javascript: force_cron_audit_password();">
										<span id="audit_password_last_execute">
											<?php
											if (empty($data['monitoring']['audit_password']['last_execution'])) {
												esc_html_e('Never execute');
											}
											else {
												echo esc_html(
													date_i18n(
														get_option('date_format'),
														$data['monitoring']['audit_password']['last_execution']));
											}
											?>
										</span>
									</a>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('Audit of files');?></td>
								<td>
									<a href="#" onclick="show_files_dialog();">
									<span id="audit_files_status">
										<?php
										if ($data['monitoring']['audit_files']['status']) {
											?>
											<img id ="ajax_result_ok"
												src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
											<?php
										}
										else {
											?>
											<img id ="ajax_result_fail"
												src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
											<?php
										}
										?>
									</span>
									</a>
									<a href="javascript: force_cron_audit_files();">
										<span id="audit_files_last_execute">
											<?php
											if (empty($data['monitoring']['audit_files']['last_execution'])) {
												esc_html_e('Never execute');
											}
											else {
												echo esc_html(
													date_i18n(
														get_option('date_format'),
														$data['monitoring']['audit_files']['last_execution']));
											}
											?>
										</span>
									</a>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('New Coments in last 24h');?></td>
								<td>
									<span class="title-count">
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
									<span class="title-count">
										<?php
										echo esc_html(
											$pfms_wp->get_count_posts_last_day());
										?>
									</span>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('Wordpress is updated');?></td>
								<td>
									<span id="wordpress_is_updated">
										<?php
										if ($data['monitoring']['wordpress_updated']) {
											?>
											<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
											<?php
										}
										else {
											?>
											<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
											<?php
										}
										?>
									</span>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('Plugins are updated');?></td>
								<td>
									<a href="#" onclick="check_plugins_pending_update();">
										<span id="plugins_are_updated">
											<?php
											if ($data['monitoring']['plugins_updated']) {
												?>
												<img id ="ajax_result_ok"
													src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
												<?php
											}
											else {
												?>
												<img id ="ajax_result_fail"
													src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
												<?php
											}
											?>
										</span>
									</a>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('API Rest enabled');?></td>
								<td>
									<a href="#" onclick="show_api_rest_plugin();">
										<span id="api_rest_plugin">
											<?php
											if ($data['monitoring']['api_rest_plugin']) {
												?>
												<img id ="ajax_result_ok"
													src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
												<?php
											}
											else {
												?>
												<img id ="ajax_result_fail"
													src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
												<?php
											}
											?>
										</span>
									</a>
								</td>
							</tr>
						</tbody>
					</table>
					<div style="display: none;">
						<img id="ajax_loading" src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="" />
						<img id="ajax_result_ok" src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="" />
						<img id="ajax_result_fail" src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="" />
					</div>
				</div>
			</div>
		</div><!-- /col-left -->
		
		</div>
		<?php
	}
	
	public static function show_general_setup() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e("General Setup");?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group');?>
				<?php $options = get_option('pfmswp-options');?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Footer");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Footer");?>
									</span>
								</legend>
								<label for="pfmswp-options[show_footer]">
									<input
										type="checkbox"
										name="pfmswp-options[show_footer]"
										value="1"
										<?php
										checked($options['show_footer'], 1, true);
										?>
										/>
									<?php esc_html_e("Show");?>
								</label>
							</fieldset>
						</td>
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
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("API Password");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("API password");?>
									</span>
								</legend>
								<label for="pfmswp-options[api_password]">
									<input
										class="regular-text"
										type="password"
										name="pfmswp-options[api_password]"
										value="<?php echo esc_attr($options['api_password']);?>"
										/>
								</label>
							</fieldset>
						</td>
					</tr>
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
									<textarea
										name="pfmswp-options[api_ip]"
										class="large-text code"
										rows="3"><?php
										echo esc_textarea($options['api_ip']);
										?></textarea>
								</p>
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
	
	public static function show_monitoring() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Monitoring");?></h2>
		</div>
		
		<div class="wrap">
			<h2><?php esc_html_e("Comments in last 24h");?></h2>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
			<?php
			
			$tcomments = $wpdb->prefix . "comments";
			$tposts = $wpdb->prefix . "posts";
			$sql = "
				SELECT COUNT(comments.comment_ID) AS count, posts.post_title AS post
				FROM `" . $tcomments . "` AS comments
				INNER JOIN `" . $tposts . "` AS posts
					ON comments.comment_post_ID = posts.ID
				WHERE TIMESTAMPDIFF(HOUR, comments.comment_date, now()) < 25
				ORDER BY post ASC";
			$comments = $wpdb->get_results($sql);
			
			if (empty($comments)) {
				?>
				<p><strong><?php esc_html_e("Empty list");?></strong></p>
				<?php
			}
			else {
				?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e("Post");?></th>
							<th><?php esc_html_e("Count");?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($comments as $comment) {
							?>
							<tr>
								<td><?php echo esc_html($comment->post);?></td>
								<td><?php echo esc_html($comment->count);?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php
			}
			?>
			
			
			<h2><?php esc_html_e("Posts in last 24h");?></h2>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
			<?php
			
			$tposts = $wpdb->prefix . "posts";
			$sql = "
				SELECT posts.post_title AS post
				FROM `" . $tposts . "` AS posts
				WHERE TIMESTAMPDIFF(HOUR, posts.post_date, now()) < 25
				ORDER BY post ASC";
			$posts = $wpdb->get_results($sql);
			
			if (empty($posts)) {
				?>
				<p><strong><?php esc_html_e("Empty list");?></strong></p>
				<?php
			}
			else {
				?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e("Post");?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($posts as $post) {
							?>
							<tr>
								<td><?php echo esc_html($post->post);?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php
			}
			?>
			
			
			<h2><?php esc_html_e("Setup");?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group-options-monitoring');?>
				<?php $options = get_option('pfmswp-options-monitoring');?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Black list plugins to check updates.");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Black list plugins to check updates.");?>
									</span>
								</legend>
								<p>
									<textarea
										name="pfmswp-options-monitoring[blacklist_plugins_check_update]"
										class="large-text code"
										rows="10"><?php
										echo esc_textarea($options['blacklist_plugins_check_update']);
										?></textarea>
								</p>
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
	
	public static function show_access_control() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Access Control");?></h2>
		</div>
		
		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
		
		<?php
		$user_stats = $wpdb->get_results(
			"SELECT *
			FROM `" . $tablename . "`
			ORDER BY timestamp DESC");
		
		if (empty($user_stats)) {
			?>
			<p><strong><?php esc_html_e("Empty list");?></strong></p>
			<?php
		}
		else {
			?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e("User");?></th>
						<th><?php esc_html_e("Action");?></th>
						<th><?php esc_html_e("Count");?></th>
						<th><?php esc_html_e("Last time");?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($user_stats as $user) {
						?>
						<tr>
							<td><?php echo esc_html($user->user);?></td>
							<td><?php echo esc_html($user->action);?></td>
							<td><?php echo esc_html($user->count);?></td>
							<td><?php echo esc_html($user->timestamp);?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<?php
		}
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Setup");?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group-access_control');?>
				<?php $options = get_option('pfmswp-options-access_control');?>
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
										<?php
										checked($options['email_new_account'], 1, true);
										?>
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
										<?php
										checked($options['email_user_login'], 1, true);
										?>
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
										<?php
										checked($options['email_change_email'], 1, true);
										?>
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
										<?php
										checked($options['email_plugin_new'], 1, true);
										?>
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
	
	public static function show_system_security() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_ap = PFMS_AdminPages::getInstance();
		
		?>
		<div class="wrap">
			<h2><?php esc_html_e("System security");?></h2>
		</div>
		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
		
		<div class="wrap">
			<h2><?php esc_html_e("Filesystem status");?></h2>
			<?php
			$tablename = $wpdb->prefix . $pfms_wp->prefix . "filesystem";
			
			$list = $wpdb->get_results("
				SELECT path, status, writable_others, original, infected
				FROM `" . $tablename . "`
				WHERE status != '' or writable_others = 1
				ORDER BY status DESC");
			if (empty($list))
				$list = array();
			
			if (empty($list)) {
				?>
				<p><?php esc_html_e("Empty data");?></p>
				<?php
			}
			else {
				?>
				<table id="list_filesystem" class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e("Path");?></th>
							<th><?php esc_html_e("Status");?></th>
							<th><?php esc_html_e("No Writable others");?></th>
							<th><?php esc_html_e("Original");?></th>
							<th><?php esc_html_e("Infected");?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($list as $entry) {
							if ($entry->writable_others) {
								$icon = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
							}
							else {
								$icon = "<img  src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
							}
							
							$icon_original = "";
							if ($entry->original == "no") {
								$icon_original = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
							}
							else {
								$icon_original = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
							}
							
							$icon_infected = "";
							if ($entry->infected == "yes") {
								$icon_infected = "<img src='" . esc_url(admin_url( 'images/no.png')) . "' alt='' />";
							}
							else {
								$icon_infected = "<img src='" . esc_url(admin_url( 'images/yes.png')) . "' alt='' />";
							}
							?>
							<tr>
								<td><?php esc_html_e($entry->path);?></td>
								<td><?php esc_html_e($entry->status);?></td>
								<td><?php echo $icon;?></td>
								<td><?php echo $icon_original;?></td>
								<td><?php echo $icon_infected;?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#list_filesystem').scrollTableBody({'rowsToDisplay': 5});
					});
				</script>
				<?php
			}
			?>
			
			
			<h2><?php esc_html_e("Setup");?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group-system_security');?>
				<?php $options = get_option('pfmswp-options-system_security');?>
				<table class="form-table">
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
										<?php
										checked($options['upload_htaccess'], 1, true);
										?>
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
										<?php
										checked($options['upload_robots_txt'], 1, true);
										?>
										/>
									<?php esc_html_e("Active and set a Robots.txt from data plugin directory.");?>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Directory to save the Robots.txt");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[directory_robot_txt]">
									<input
										class="regular-text"
										type="text"
										name="pfmswp-options-system_security[directory_robot_txt]"
										value="<?php echo esc_attr($options['directory_robot_txt']);?>"
										/>
									<p class="description">
										<?php
										esc_html_e("The directory to save the Robot.txt.");
										?>
									</p>
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
										<?php
										checked($options['wp_generator_disable'], 1, true);
										?>
										/>
									<?php esc_html_e("Disable the WP Generator in wp_head.");?>
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
								<label for="pfmswp-options-system_security[activate_login_rename]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[activate_login_rename]"
										value="1"
										<?php
										checked($options['activate_login_rename'], 1, true);
										?>
										/>
									<?php esc_html_e("Activate the plugin 'Rename wp-login.php' and install.");?>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Directory to save the Robots.txt");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[directory_robot_txt]">
									<?php
									if (get_option('permalink_structure')) {
										echo '<code>' .
											trailingslashit(home_url()) .
											'</code> ' .
											'<input
												type="text" name="pfmswp-options-system_security[login_rename_page]"
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
												type="text" name="pfmswp-options-system_security[login_rename_page]"
												value="' . esc_attr($options['login_rename_page'])  . '">';
									}
									?>
									<p class="description">
										<?php
										esc_html_e("The rename login page.");
										?>
									</p>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Check file hash to the svn files.");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Check file hash to the svn files.");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[check_filehash_svn]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[check_filehash_svn]"
										value="1"
										<?php
										checked($options['check_filehash_svn'], 1, true);
										?>
										/>
									<?php esc_html_e("Each 24h (cron) check the file hash of svn files.");?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Limit login attempts (bruteforce attack protection).");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Limit login attempts (bruteforce attack protection).");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[bruteforce_attack_protection]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[bruteforce_attack_protection]"
										value="1"
										<?php
										checked($options['bruteforce_attack_protection'], 1, true);
										?>
										/>
									<?php esc_html_e("Active to attack protection.");?>
								</label>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Directory to save the Robots.txt");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[bruteforce_attack_attempts]">
									<input
										class="small-text"
										type="text"
										name="pfmswp-options-system_security[bruteforce_attack_attempts]"
										value="<?php echo esc_attr($options['bruteforce_attack_attempts']);?>"
										/>
									<span class="description">
										<?php
										esc_html_e("Number of attempts to enable.");
										?>
									</span>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Black list IPs.");?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Black list IPs.");?>
									</span>
								</legend>
								<p>
									<textarea
										name="pfmswp-options-system_security[blacklist_ips]"
										class="large-text code"
										rows="10"><?php
										echo esc_textarea($options['blacklist_ips']);
										?></textarea>
								</p>
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										<?php esc_html_e("Redirect URL if the ip is banned.");?>
									</span>
								</legend>
								<label for="pfmswp-options-system_security[url_redirect_ip_banned]">
									<input
										class="regular-text"
										type="text"
										name="pfmswp-options-system_security[url_redirect_ip_banned]"
										value="<?php echo esc_attr($options['url_redirect_ip_banned']);?>"
										/>
									<p class="description">
										<?php
										esc_html_e("Full URL starting with the 'http://'");
										?>
									</p>
								</label>
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
								<label for="pfmswp-options-system_security[scan_infected_files]">
									<input
										type="checkbox"
										name="pfmswp-options-system_security[scan_infected_files]"
										value="1"
										<?php
										checked($options['scan_infected_files'], 1, true);
										?>
										/>
									<?php esc_html_e("Active to scan each 24h in the cron.");?>
								</label>
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
	
	private function use_trailing_slashes() {
		return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
	}
}
?>