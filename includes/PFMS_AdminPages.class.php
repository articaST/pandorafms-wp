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
	
	
	public static function show_dashboard() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Dashboard");?></h2>
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
				<?php
				$pfms_wp = PandoraFMS_WP::getInstance();
				$pfms_wp->debug($options);
				?>
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
										echo esc_textarea($options['api_ip']);?></textarea>
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
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Monitoring");?></h2>
		</div>
		<?php
	}
	
	public static function show_acccess_control() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Access Control");?></h2>
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