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

/**
 * Handles interactions with Google Analytics' Stat API
 *
 **/
class PFMS_GoogleAnalytics {
	private static $instance = null;
	
	private $client = null;
	private $accountId;
	private $baseFeed = 'https://www.googleapis.com/analytics/v3';
	private $token = false;
	public $GOOGLE_ANALYTICATOR_SCOPE = 'https://www.googleapis.com/auth/analytics';
	public $GOOGLE_ANALYTICATOR_REDIRECT = 'urn:ietf:wg:oauth:2.0:oob';
	public $GOOGLE_ANALYTICATOR_CLIENTID = '306233129774-fecd8o976qcvibndd2htkelbo967vd2h.apps.googleusercontent.com';
	public $GOOGLE_ANALYTICATOR_CLIENTSECRET = 'eVx0Uqn__0kptR1vWxWrP7qW';
	
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function show_google_analytics () {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_ga = PFMS_GoogleAnalytics::getInstance();
		
		if (isset($_POST['update_values_token'])) {
			update_option($pfms_wp->prefix . 'ga_google_init', $_POST['ga_google_init']);
			update_option($pfms_wp->prefix . 'ga_google_uid_token', $_POST['ga_google_uid_token']);
			update_option($pfms_wp->prefix . 'ga_google_token', $_POST['ga_google_token']);
		}
		if (isset($_POST['update_values_uid'])) {
			update_option($pfms_wp->prefix . 'ga_google_init', $_POST['ga_google_init']);
			update_option($pfms_wp->prefix . 'ga_google_uid_token', $_POST['ga_google_uid_token']);
			update_option($pfms_wp->prefix . 'ga_google_token', $_POST['ga_google_token']);
		}
		
		$pfms_wp->debug("HOLAAAAA");
		
		$varialbe = get_option($pfms_wp->prefix . 'ga_google_init');
		if ( $varialbe == true ) {
			$pfms_ga->ga_activate ();
		}
		else {
			
			if ($google_options['ga_google_token'] != '') {
				echo "Hola estoy funcionando";
				
			}
			else {
				echo "Hola no estoy funcionando";
			}	
		}
	}
	
	public function ga_activate () {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_ga = PFMS_GoogleAnalytics::getInstance();
		
		$url = http_build_query( array(
				'scope' => $pfms_ga->GOOGLE_ANALYTICATOR_SCOPE,
				'response_type' => 'code',
				'redirect_uri' => $pfms_ga->GOOGLE_ANALYTICATOR_REDIRECT,
				'client_id' => $pfms_ga->GOOGLE_ANALYTICATOR_CLIENTID
				)
		);

		?>
		<div class="wrap">
		  <p><strong>Google Authentication Code </strong> </p>
		  <p>You need to sign in to Google and grant this plugin access to your Google Analytics account</p>
		  <p> <a
						onclick="window.open('https://accounts.google.com/o/oauth2/auth?<?php echo $url ?>', 'activate','width=700, height=600, menubar=0, status=0, location=0, toolbar=0')"
						href="javascript:void(0);"> Click Here </a> - <small> Or <a target="_blank" href="https://accounts.google.com/o/oauth2/auth?<?php echo $url ?>">here</a> if you have popups blocked</small> </p>
		  <div  id="key">
			<p>Enter your Google Authentication Code in this box. This code will be used to get an Authentication Token so you can access your website stats.</p>
			<form method="post" action="admin.php?page=pfms_wp_admin_menu_google_analytics">
			  <?php //wp_nonce_field('google-analyticator-update_settings'); ?>
			  <input type="text" name="ga_google_token" value="" style="width:450px;"/>
			  <input type="hidden" name="ga_google_uid_token" value="" style="width:450px;"/>
			  <input type="hidden" name="ga_google_init" value="false" />
			  <input type="hidden" name="update_values_token" value="true" />
			  <input type="submit"  value="Save &amp; Continue" />
			</form>
		  </div>
		  <br />
		  <br />
		  <br />
		  <hr />
		  <br />
		  <p><strong>I Don't Want To Authenticate Through Google </strong> </p>
		  <p>If you don't want to authenticate through Google and only use the tracking capability of the plugin (<strong><u>not the dashboard functionality</u></strong>), you can do this by clicking the button below. </p>
		  <p>You will be asked on the next page to manually enter your Google Analytics UID.</p>
		  <form method="post" action="">
			<input type="text" name="ga_google_uid_token_uid" value="" style="width:450px;"/>
			<input type="hidden" name="ga_google_token_uid" value="" style="width:450px;"/>
			<input type="hidden" name="ga_google_init_uid" value="false" />
			<input type="hidden" name="update_values_uid" value="true" />
			<input type="submit"  value="Save &amp; Continue" />
		  </form>
		</div>
		<?php
	}
}	
?>