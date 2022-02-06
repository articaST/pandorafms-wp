=== Pandora FMS WP ===
Contributors: artica
Tags: monitoring, security, audit, secure
Requires at least: 4.7
Tested up to: 5.9
Stable tag: 1.0
License: Apache License 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0

== Description ==

Pandora FMS WP is a monitoring plugin for Wordpress. 100% free and OpenSource, no freemium or enterprise version. Collect basic information from your Wordpress and allow Pandora FMS to retrieve it remotely using a REST API. It also allow to define custom SQL queries to monitor other plugins or do your custom queries and collect from Pandora FMS.

This plugin has been developed by [Pandora FMS team](https://pandorafms.com "Pandora FMS team"). Sourcecode is available at [https://github.com/articaST/pandorafms-wp/](https://github.com/articaST/pandorafms-wp/ "https://github.com/articaST/pandorafms-wp/")

Sections: 

*	__Dashboard:__ here, you can view a summary of the items monitored: plugins updated, version of WP and if they need an update, total users, new posts in last 24hr, new replies in 24hr and other checks.
	
*	__Access Control:__ You can view a table with user access data: users, IP, if the login has been correct or incorrect and how many times, and the date of the last access. Also can see if new plugins or themes have been installed and the date on which these events occurred. 
				
*	__General Setup:__ Set general options:
	*	API Settings
		*	Email for notifications.
		*	List of IPs with access to the API.
		*	Set the time to show new data in the API.
	*	Delete Logs Time
		*	Clean fields of filesystem table with status deleted for data older than X days
		*	Remove the status ¨new¨ on fields of filesystem table for data older than X days

== Prerequisites ==

*	PandoraFMS-WP requires (optionally) a plugin for REST API, called "JSON REST API". Only needed if you want to integrate the monitoring/status information of the WP site into a central management console with Pandora FMS. This is an optional feature, you can manage all information from Wordpress itself.

* You need to have running the REST API, and for that, you need the permalinks to be running. To check if your API is running, check the API manually, for example: http://mywordpress.com/wp-json/pandorafms_wp/online
This API request should report 1.

*	If your Wordpress version is lower than 4.7, you must have the [WP REST API (v2)](https://es.wordpress.org/plugins/rest-api/ "WP REST API (v2)") plugin installed to use the API. (This plugin requires version 4.6 or higher).	

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. In the menu, below Settings, you will see 'PandoraFMS WP'. Use it to configure the plugin.
4. If you want a more secure API access to the Pandora FMS WP REST API, set the allowed IPs. Any IP is allowed by default
5. Install the .PSPZ package in your Pandora FMS console to load library checks that use this plugin by using the API REST over HTTP(s).
6. In your Pandora FMS WP module in Pandora FMS, define the URL to access the api rest of this plugin, like http://mywordpress.com/ and choose the module (online, plugin check, etc).
7. API Calls available (under /wp-json/pandorafms_wp/xxxx):

	/online
	/site_name
	/version
	/wp_version
	/admin          
	/new_account
	/theme_registered
	/plugin_registered
	/new_posts
	/new_comments
	/plugin_update
	/core_update
	/user_login
	/failed_login

== Screenshots ==

1. This is the Dashboard, here you can view a summary.
2. Access Control Menu: This section manages access to your Wordpress. Here you can see a full log of all user interactions with your site. 
3. System Security Menu: Here you can configure options to enforce security on your site.
4. General Setup Menu: API settings and set the time to delete the logs.
5. Filesystem Status Menu: Check the status of system files: check WP integrity, scan for infected files, and send email when files list is modified.

== Limitations ==

*	WP Multisite not supported on this version. 
*	To use the Wordpress API REST, you need version 4.6 or higher.

== Changelog ==

* 2022-02-06 New version, 2.0 removes some filesystem hardening tools. Plugin is now much more fast and lighter. 

== Upgrade Notice ==
