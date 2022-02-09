![Pandora FMS](https://pandorafms.com/wp-content/uploads/2021/11/Pandora-FMS.png)

Pandora FMS WP is a **monitoring plugin for Wordpress**. 100% free and OpenSource. It collect basic information from your Wordpress and allow Pandora FMS to retrieve it remotely using a REST API. Some examples are new posts, comments or user logins in last hour. It also monitor if new plugins or themes has been isntalled, if a new user has been created of if a bruteforce login attempt has been made recently. You can expand easily by defining custom SQL queries to monitor other plugins or create your own SQL to collect information and sent it to Pandora FMS. 

This plugin has been developed by [Pandora FMS team](https://pandorafms.com "Pandora FMS team"). Sourcecode is available at [https://github.com/articaST/pandorafms-wp/](https://github.com/articaST/pandorafms-wp/ "https://github.com/articaST/pandorafms-wp/")
		
## Prerequisites ##

* PandoraFMS-WP requires (optionally) a plugin for REST API, called "JSON REST API". Only needed if you want to integrate the monitoring/status information of the WP site into a central management console with Pandora FMS. This is an optional feature, you can manage all information from Wordpress itself.

* If your Wordpress version is lower than 4.7, you must have the [WP REST API (v2)](https://es.wordpress.org/plugins/rest-api/ "WP REST API (v2)") plugin installed to use the API. (This plugin requires version 4.6 or higher).	

## Installation ## 

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. In the menu, below Settings, you will see 'PandoraFMS WP'. Use it to configure the plugin.
4. If you want a more secure API access to the Pandora FMS WP REST API, set the allowed IPs. Any IP is allowed by default to access Pandora FMS WP Rest API.
5. In order to get information remotely from your Pandora FMS server, you need to have running the REST API in your wordpress setup, and for that, you need also the permalinks to be running. To check if your API is running, check the API manually, for example: http://mywordpress.com/wp-json/pandorafms_wp/online
This API request should report 1 if works as intented.
6. Install the .PSPZ2 package in your Pandora FMS console to load library checks that use this plugin by using the API REST over HTTP(s). You can also create the modules manually, its just a regular HTTP request on a REST API, but it's easier if you load the PSPZ2 with predefined modules.
7. Create a new Plugin Server module in your Pandora FMS WP module in Pandora FMS, define the URL to access the api rest of this plugin, like http://mywordpress.com/ and choose the predefined module from library: online, new_account, plugin check, etc).
8. API Calls available under /wp-json/pandorafms_wp/xxxx :
	/online  			- Check if Wordpress is responding using Pandora FMS WP REST API
	/site_name 			- Check Wordpress sitename
	/version			- Return plugin version
	/wp_version 		- Return Wordpress core version
	/admin          	- Return FALSE if 'admin' account exists (a very bad practice)
	/new_account		- Return FALSE if new user accounts has been created in last hour
	/theme_registered	- Return FALSE if new themes has been installed in last hour
	/plugin_registered	- Return FALSE if new plugins has been installed in last hour
	/new_posts			- New posts in last hour
	/new_comments		- New comments in last hour
	/plugin_update		- Return FALSE if a plugin needs update
	/core_update		- Return FALSE if wordpress core needs update
	/user_login			- Return FALSE if a successful login has been detected in last hour
	/failed_login		- Return FALSE if a unsuccessful login has been detected in last hour
	/bruteforce			- Return FALSE if a bruteforce attack has been detected in last hour
	/custom_sql_1		- Return result of a custom SQL query.
	/custom_sql_2		- Return result of a custom SQL query.
9. In the /wp-content/plugins/pandorafms-wp/pspz directory you have the .pspz2 file ready to be uploaded to your Pandora FMS console to use this plugin as remote plugin modules. See more information about the process in the module library at https://pandorafms.com/library/wordpress-monitoring-plugin/

## Screenshots ##

1. This is the Dashboard, here you can view a summary.
2. Audit records: Here you can see a full log of all user interactions with your site and new themes and plugins installed recently.
3. General Setup Menu: API settings and set the time to delete the logs.
4. Plugins which needs and update (clicking in dashboard / plugin need update dialog button)
5. Example of dashboard reporting bruteforce attacks and other issues
6. Pandora FMS setup of a remote module using Wordpress plugin which connects with this WP plugin.
7. Pandora FMS overview of several wordpress monitors.

## Limitations ##

* WP Multisite not supported on this version. 
* To use the Wordpress API REST, you need version 4.6 or higher.

## Changelog ##

* 2022-02-06 New version, 2.0 removes some filesystem hardening features. Plugin is now much more fast and lighter. API Rest adds new bruteforce detection and custom SQL queries.
* 2017-05-23 First stable version.
