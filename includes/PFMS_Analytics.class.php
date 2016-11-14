<?php


/**
 * Handles interactions with Google Analytics' Stat API
 *
 **/
class PFMS_GoogleAnalyticsStats {
	private static $instance = null;
	
	private $client = null;
	private $accountId;
	private $baseFeed = 'https://www.googleapis.com/analytics/v3';
	private $token = false;
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @param token - a one-time use token to be exchanged for a real token
	 **/
	public function PFMS_AnalyticsStats() {
		$pfms_ap = PFMS_AdminPages::getInstance();
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_gas = PFMS_GoogleAnalyticsStats::getInstance();
		# Include SimplePie if it doesn't exist
		if ( !class_exists('SimplePie') ) {
			require_once (ABSPATH . WPINC . '/class-feed.php');
		}
		
		//~ if ( !class_exists('Google_Client') ) {
			require_once 'google-api-php-client/src/Google_Client.php';
		//~ }
		//~ if ( !class_exists('Google_AnalyticsService') ) {
			require_once 'google-api-php-client/src/contrib/Google_AnalyticsService.php';
		//~ }

		$pfms_gas->client = new Google_Client();
		$pfms_gas->client->setApprovalPrompt("force");
		$pfms_gas->client->setAccessType('offline');
		$pfms_gas->client->setClientId($pfms_ap->GOOGLE_ANALYTICATOR_CLIENTID);
		$pfms_gas->client->setClientSecret($pfms_ap->GOOGLE_ANALYTICATOR_CLIENTSECRET);
		$pfms_gas->client->setRedirectUri($pfms_ap->GOOGLE_ANALYTICATOR_REDIRECT);
		
		$pfms_gas->client->setScopes(array("https://www.googleapis.com/auth/analytics.readonly"));

		// Magic. Returns objects from the Analytics Service instead of associative arrays.
		$pfms_gas->client->setUseObjects(true);
		$test = $pfms_gas->client->getAccessToken();
		$pfms_wp->debug("HOLAAA");
		$pfms_wp->debug($test);
		try {
				$pfms_gas->analytics = new Google_AnalyticsService($pfms_gas->client);
			}
		catch (Google_ServiceException $e)
			{
				print '(cas:48) There was an Analytics API service error ' . $e->getCode() . ':' . $e->getMessage();
				return false;
			}
	}

	function checkLogin() {
		$pfms_wp = PandoraFMS_WP::getInstance();
		$pfms_gas = PFMS_GoogleAnalyticsStats::getInstance();
		$options = get_option('pfmswp-options');
		$ga_google_authtoken  = (isset($options['ga_google_authtoken']) ? $options['ga_google_authtoken'] : '');
		
		if (!empty($ga_google_authtoken)) {
			try
			{
				$pfms_gas->client->setAccessToken($ga_google_authtoken);
			}
			catch( Google_AuthException $e )
			{
				print '(cas:72) Google Analyticator was unable to authenticate you with
						Google using the Auth Token you pasted into the input box on the previous step. <br><br>
						This could mean either you pasted the token wrong, or the time/date on your server is wrong,
						or an SSL issue preventing Google from Authenticating. <br><br>
						<a href="' . admin_url('/options-general.php?page=ga_reset').'"> Try Deauthorizing &amp; Resetting Google Analyticator.</a>
						<br><br><strong>Tech Info111 </strong> ' . $e->getCode() . ':' . $e->getMessage();

				return false;
			}
		}
		else {
			$authCode = $options['key_ga_google_token'];
			$pfms_wp->debug("Primerp....");
			$pfms_wp->debug($authCode);
			if (empty($authCode)) return false;

			try {
				$accessToken = $pfms_gas->client->authenticate($authCode);
			}
			catch( Exception $e ) {
				print '(cas:72) Google Analyticator was unable to authenticate you with
						Google using the Auth Token you pasted into the input box on the previous step. <br><br>
						This could mean either you pasted the token wrong, or the time/date on your server is wrong,
						or an SSL issue preventing Google from Authenticating. <br><br>
						<a href="' . admin_url('/options-general.php?page=ga_reset').'"> Try Deauthorizing &amp; Resetting Google Analyticator.</a>
						<br><br><strong>Tech Info3 </strong> ' . $e->getCode() . ':' . $e->getMessage();

				return false;
			}
			
			$pfms_wp->debug("Segundo....");
			$pfms_wp->debug($accessToken);
			if($accessToken) {
				
				$pfms_gas->client->setAccessToken($accessToken);
				$options['ga_google_authtoken'] = $accessToken;
				//update_option('pfmswp-options', $options);
			}
			else {
				return false;
			}
		}

		$this->token =  $this->client->getAccessToken();
		return true;
	}

	function deauthorize() {
		update_option('ga_google_token', '');
		update_option('ga_google_authtoken', '');
	}

	function getSingleProfile() {
		$webproperty_id = get_option('ga_uid');
		list($pre, $account_id, $post) = explode('-',$webproperty_id);

		if (empty($webproperty_id)) return false;

		try {
			$profiles = $this->analytics->management_profiles->listManagementProfiles($account_id, $webproperty_id);
		}
		catch (Google_ServiceException $e)
		{
			print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
			return false;
		}

		$profile_id = $profiles->items[0]->id;
		if (empty($profile_id)) return false;

		$account_array = array();
		array_push($account_array, array('id'=>$profile_id, 'ga:webPropertyId'=>$webproperty_id));
		return $account_array;
	}

	function getAllProfiles() {
		$profile_array = array();
		
		try {
				$profiles = $this->analytics->management_webproperties->listManagementWebproperties('~all');
			}
			catch (Google_ServiceException $e)
			{
				print 'There was an Analytics API service error ' . $e->getCode() . ': ' . $e->getMessage();
			}


		if( !empty( $profiles->items ) )
		{
			foreach( $profiles->items as $profile )
			{
				$profile_array[ $profile->id ] = str_replace('http://','',$profile->name );
			}
		}

		return $profile_array;
	}

	function getAnalyticsAccounts() {
		
		$analytics = new Google_AnalyticsService($this->client);
		$accounts = $analytics->management_accounts->listManagementAccounts();
		$account_array = array();

		$items = $accounts->getItems();

		if (count($items) > 0) {
			foreach ($items as $key => $item)
			{
				$account_id = $item->getId();

				$webproperties = $analytics->management_webproperties->listManagementWebproperties($account_id);

				if (!empty($webproperties))
				{
					foreach ($webproperties->getItems() as $webp_key => $webp_item) {
						$profiles = $analytics->management_profiles->listManagementProfiles($account_id, $webp_item->id);

						$profile_id = $profiles->items[0]->id;
						array_push($account_array, array('id'=>$profile_id, 'ga:webPropertyId'=>$webp_item->id));
					}
				}
			}

			return $account_array;
		}
		return false;

	}

	/**
	 * Sets the account id to use for queries
	 *
	 * @param id - the account id
	 **/
	function setAccount($id)
	{
		$this->accountId = $id;
	}

	/**
	 * Get a specific data metrics
	 *
	 * @param metrics - the metrics to get
	 * @param startDate - the start date to get
	 * @param endDate - the end date to get
	 * @param dimensions - the dimensions to grab
	 * @param sort - the properties to sort on
	 * @param filter - the property to filter on
	 * @param limit - the number of items to get
	 * @return the specific metrics in array form
	 **/
	function getMetrics($metric, $startDate, $endDate, $dimensions = false, $sort = false, $filter = false, $limit = false)
	{
		$analytics = new Google_AnalyticsService($this->client);

		$params = array();

		if ($dimensions)
		{
			$params['dimensions'] = $dimensions;
		}
		if ($sort)
		{
			$params['sort'] = $sort;
		}
		if ($filter)
		{
			$params['filters'] = $filter;
		}
		if ($limit)
		{
			$params['max-results'] = $limit;
		}
           
           // Just incase, the ga: is still used in the account id, strip it out to prevent it breaking
           $filtered_id = str_replace( 'ga:', '', $this->accountId );
           
           if(!$filtered_id){
                echo 'Error - Account ID is blank';
                return false;
           }
                
	   return $analytics->data_ga->get(
	       'ga:'.$filtered_id,
	       $startDate,
	       $endDate,
	       $metric,
	       $params
	       );
	}

	/**
	 * Checks the date against Jan. 1 2005 because GA API only works until that date
	 *
	 * @param date - the date to compare
	 * @return the correct date
	 **/
	function verifyStartDate($date)
	{
		if ( strtotime($date) > strtotime('2005-01-01') )
			return $date;
		else
			return '2005-01-01';
	}

} // END class	