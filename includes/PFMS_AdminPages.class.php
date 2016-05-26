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
								<label for="pfmswp-options[api_ip]">
									<textarea
										name="pfmswp-options[api_ip]"
										class="large-text code"
										rows="3"><?php
										echo esc_textarea($options['api_ip']);
										?></textarea>
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
	
	public static function show_monitoring() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Monitoring");?></h2>
		</div>
		
		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
		<?php
		
		$tcomments = $wpdb->prefix . "comments";
		$tposts = $wpdb->prefix . "posts";
		$sql = "
			SELECT COUNT(comments.comment_ID) AS count, posts.post_title AS post
			FROM " . $tcomments . " AS comments
			INNER JOIN wp_posts AS posts
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
								<label for="pfmswp-options[email_new_account]">
									<input
										type="checkbox"
										name="pfmswp-options[email_new_account]"
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
								<label for="pfmswp-options[email_user_login]">
									<input
										type="checkbox"
										name="pfmswp-options[email_user_login]"
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
								<label for="pfmswp-options[email_change_email]">
									<input
										type="checkbox"
										name="pfmswp-options[email_change_email]"
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
								<label for="pfmswp-options[email_plugin_new]">
									<input
										type="checkbox"
										name="pfmswp-options[email_plugin_new]"
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
								<label for="pfmswp-options[email_theme_new]">
									<input
										type="checkbox"
										name="pfmswp-options[email_theme_new]"
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
		?>
		<div class="wrap">
			<h2><?php esc_html_e("System security");?></h2>
		</div>
		<?php
	}
}
?>