<?php
/*
Copyright (c) 2021 Artica PFMS 

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
			<h2><?php esc_html_e("Pandora FMS WP Monitoring dashboard");?></h2>

			<div id="col-container">		
				<?php $options = get_option('pfmswp-options');?>
				
					<div class="col-wrap">
						<div class="card_pfms">
							<h2 class="title"><?php esc_html_e("Monitoring");?></h2>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e("Monitored item");?></th>
										<th><?php esc_html_e("Status");?></th>

									</tr>
								</thead>
								<tbody>
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
	<tr>
		<td><?php esc_html_e('Total users');?></td>
		<td>
			<span>
				<?php echo esc_html($pfms_wp->get_user_count()); ?>
			</span>
		</td>
	</tr>
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
	<td><?php esc_html_e('New themes installed recently');?></td>
	<td>
		<span>

<?php		
	if ($pfms_wp->api_new_themes() == 1) {												
		?>
		<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
		<?php
	}
	else {
		?>
		<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
		 <i>Check audit records</i>
		<?php
	}
?>
		</span>
	</td>
</tr>

<tr>
	<td><?php esc_html_e('New plugins installed recently');?></td>
	<td>
		<span>

<?php		
	if ($pfms_wp->api_new_plugins() == 1) {												
		?>
		<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
		<?php
	}
	else {
		?>
		<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
	    <i>Check audit logs</i>
		<?php

	}
?>
		</span>
	</td>
</tr>

<tr>
	<td><?php esc_html_e('Is "admin" user active in the system?');?></td>
	<td>
		<span>

<?php		
	if ($pfms_wp->check_admin_user_enabled() == 1) {												
		?>
		<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
		<?php
	}
	else {
		?>
		<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
	    <i>You SHOULD rename it for security</i>
		<?php

	}
?>
		</span>
	</td>
</tr>

<tr>
	<td><?php esc_html_e('Recent brute force attempts');?></td>
	<td>
		<span>

<?php		
	if ($pfms_wp->brute_force_attempts(60) == 1) {												
		?>
		<img src="<?php echo esc_url( admin_url( 'images/yes.png' ) ); ?>" alt="yes" />
		<?php
	}
	else {
		?>
		<img src="<?php echo esc_url( admin_url( 'images/no.png' ) ); ?>" alt="no" />
	    <i>Check audit records</i>
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
			</div><!-- /container -->
		</div><!-- /wrap -->
		<?php
	}
	//=== END === DASHBOARD VIEW =======================================


	//=== ACCESS CONTROL VIEW ==========================================
	public static function show_access_control() {
		global $wpdb;
		
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		?>
	
		<h2><?php esc_html_e("System audit");?></h2>
	
		<?php
			$tablename = $wpdb->prefix . $pfms_wp->prefix . "access_control";
			$audit_logs = $wpdb->get_results( "SELECT *	FROM `" . $tablename . "` ORDER BY timestamp DESC" );
		?>
		<table id="list_audit" class="widefat striped" style="width: 95%">
			<thead>
				<tr>
					<th><?php esc_html_e("Type");?></th>
					<th><?php esc_html_e("Data");?></th>
					<th><?php esc_html_e("Timestamp");?></th>
				</tr>
			</thead>
			<tbody>
	<?php
		if (empty($audit_logs)) {
			?>				
			<tr>
				<td colspan="3">
					<p><strong><?php esc_html_e("Empty list");?></strong></p>
				</td>
			</tr>
		<?php
		}
		else {
			foreach ($audit_logs as $audit) {
				?>
				<tr>
					<td><?php echo esc_html($audit->type);?></td>
					<td><?php echo esc_html($audit->data);?></td>
					<td><?php echo esc_html($audit->timestamp);?></td>
				</tr>
				<?php
			}
		}
				?>
			</tbody>
		</table>


		<h2><?php esc_html_e("User audit");?></h2>
		
		<?php
			$tablename = $wpdb->prefix . $pfms_wp->prefix . "user_stats";
			$user_stats = $wpdb->get_results( "SELECT *	FROM `" . $tablename . "` ORDER BY timestamp DESC" );
		?>
			<table id="list_access_control" class="widefat striped" style="width: 95%">
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
					jQuery('#list_access_control').scrollTableBody({'rowsToDisplay': 10});
				});

			</script>

		<?php
	}
	//=== END === ACCESS CONTROL VIEW ==================================


	//=== GENERAL SETUP VIEW ===========================================
	public static function show_general_setup() {
		$pfms_ap = PFMS_AdminPages::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		
		?>
		<div class="wrap">
			<h2><?php esc_html_e("Pandora FMS WP Plugin Setup");?></h2>
			<form method="post" action="options.php">
				<?php settings_fields('pfmswp-settings-group');?>
				<?php $options = get_option('pfmswp-options');?>			
				<table class="form-table">
					<tr>
						<th scope="row">
							<h3><?php esc_html_e("API Settings");?></h3>
						</th>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Exclusion list for plugins to be checked for updates");?>
						</th>
						<td>
							<fieldset>
								<p class="description">
									<?php esc_html_e("Use plugin name, one per line");?>
								</p>
								<p>
									<textarea
										name="pfmswp-options[blacklist_plugins_check_update]"
										class="large-text code"
										rows="3"><?php 
										echo esc_textarea($options['blacklist_plugins_check_update']); ?></textarea>
								</p>
							</fieldset>
						</td>
					</tr>

					<!-- In this version we don't suppor auth -->
					<tr valign="top" style="visibility: collapse;">
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
										type="text"
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
								<legend>
									<span> 
										<?php esc_html_e("Allowed IPs to access API");?> 
										<i>. A '*'' means any IP's allowed (by default)</i>
									</span>
								</legend>
								<p>
									<textarea name="pfmswp-options[api_ip]" class="large-text code" rows="3"><?php 
										echo esc_textarea($options['api_ip']); ?></textarea>
								</p>
							</fieldset>
						</td>
					</tr>

<?php 
$check_url = get_home_url()."/wp-json/pandorafms_wp/online";
$check_url = "<a href='$check_url'>$check_url</a>";
?>
					<tr valign="top">
						<th scope="row">
							<?php esc_html_e("How to use the REST PI");?>
						</th>
						<td>
							Use <b><?php echo $check_url;?> </b> to check for a working API. It should return 1 as 'OK, thats good'. You need <b>permalinks enabled</b> in your Wordpress, if not you will get a 404. <br><br>Please the documentation for more API calls available.
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
			
				<tr valign="top">
					<th scope="row">
						<h3><?php esc_html_e("Custom SQL calls");?></h3>
					</th>
				</tr>

				<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Cuscom SQL Call #1");?> 
						</th>
						<td>
							<fieldset>
								<legend>
									<span> 
										<i>Enter your SQL command to extract info. Should return a single value. <br>Use this to extract info from plugins. API REST is /custom_sql_1</i>
									</span>
								</legend>
								<p>
									<textarea name="pfmswp-options[custom_1]" class="large-text code" rows="2"><?php 
										echo esc_textarea($options['custom_1']); ?></textarea>
								</p>
							</fieldset>
						</td>
				</tr>	
				
				<tr valign="top">
						<th scope="row">
							<?php esc_html_e("Cuscom SQL Call #2");?> 
						</th>
						<td>
							<fieldset>
								<legend>
									<span> 
										<i>Enter your SQL command to extract info. Should return a single value. <br>Use this to extract info from plugins. API REST is /custom_sql_2</i>
									</span>
								</legend>
								<p>
									<textarea name="pfmswp-options[custom_2]" class="large-text code" rows="2"><?php 
										echo esc_textarea($options['custom_2']); ?></textarea>
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