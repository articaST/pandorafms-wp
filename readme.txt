=== Pandora FMS WP ===
Contributors: articast 
Tags: monitoring, security, hardening, audit, secure
Requires at least: 4.7
Tested up to: 4.7.2
Stable tag: 1.0
License: Apache License 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0

== Description ==

Hardening, monitoring and security plugin for Wordpress. 100% free and OpenSource. No tricks, no freemium or "enterprise" features. This plugin is used to secure access to your Wordpress control panel renaming login page and protecting it with recaptcha and to audit accesses and protect it from brute force attacks.

Additional security features include: password audit for all active accounts (via dictionary), control WP Core version and all plugins updates, avoid malicious PHP code upload, disable WP Generator, enhance robots.txt.

This plugin also performs a full-scan of your files to detect new files, changed files, suspicious code in current contents and bad permissions. It also checks "official" WP code with your installation to check if it's the original.

All security checks can be enabled/disabled and warnings sent by email.

Remote integration with [Pandora FMS](http://pandorafms.com) can be set up (optionally) to gather information in a central monitoring solution. Pandora FMS is also an OpenSource [server monitoring](http://pandorafms.com/monitoring-solutions/server-monitoring/) solution and there is NO PREMIUM feature involved. 100% free. No tricks.

This plugin has been developed by [Pandora FMS team](https://pandorafms.com "Pandora FMS team"). Sourcecode is available at [https://github.com/articaST/pandorafms-wp/](https://github.com/articaST/pandorafms-wp/ "https://github.com/articaST/pandorafms-wp/")

Sections: 

*	__Dashboard:__ here, you can view a summary: 
	*	Monitoring: You can view the status for each option.
	*	Access Control: There is a control table, where you can see if there have been correct or incorrect logins, if a user has been locked, if new plugins or themes have been installed, etc. and the date on which these events occurred. And some options for the login page.
	*	System Security: You can view the status for each security option.
*	__Access Control:__ This section manages access to your Wordpress. Here you can define if you want to be warned on some events and you can see a full log of all user interactions with your site. 
	*	You can view a table with user access data: users, IP, if the login has been correct or incorrect and how many times, and the date of the last access.
	*	Send email notification on different events: new user, new user login, user change email, new plugin added, new theme added.
	*	Redirect the login page to another url, this is a basic security measure to ensure your WP is not attacked.
	*	Bruteforce attack protection: limits login attempts. You have 3 configurable options:
		*	Login attempts limit  
		*	Login lockdown time  
		*	How many times/how long should such failed attempts occur in order for the account to freeze.
	*	Blacklist of IPs that cannot access login page. You can optionally redirect them to another url.
	*	Set a login recaptcha.
	*	Disable the XMLRPC of Wordpress.
*	__System Security:__ Options to enforce security on your site.
	*	Bruteforce attack logs: A table with the users that have tried to access by brute force, how many times and the date of the last attempt.
	*	Check if "admin" user exists.
	*	Check if there are core updates available.
	*	Check the plugins updates available. There is a blacklist of plugins that you can indicate that are not checked.
	*	Protect upload of PHP Code, set a .htaccess in upload directory.
	*	Robots.txt enhancement, set a custom Robots.txt.
	*	Disable the WP Generator in wp_head.				
*	__General Setup:__ Set general options:
	*	API Settings
		*	Email for notifications.
		*	List of IPs with access to the API.
		*	Set the time to show new data in the API.
	*	Delete Logs Time
		*	Clean fields of filesystem table with status deleted for data older than X days
		*	Remove the status ¨new¨ on fields of filesystem table for data older than X days
*	__Filesystem Status:__ Check the status of system files:
	*	Check WP integrity, compare files with official WP core files.
	*	Blacklist of files that you do not want to be checked.
	*	Scan for infected files with malicous code.
	*	Send email when files list is modified.

== Prerequisites ==

*	PandoraFMS-WP requires (optionally) a plugin for REST API, called "JSON REST API". Only needed if you want to integrate the monitoring/status information of the WP site into a central management console with Pandora FMS. This is an optional feature, you can manage all information from Wordpress itself.

*	If your Wordpress version is lower than 4.7, you must have the [WP REST API (v2)](https://es.wordpress.org/plugins/rest-api/ "WP REST API (v2)") plugin installed to use the API. (This plugin requires version 4.6 or higher).	

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. In the menu, below Settings, you will see 'PandoraFMS WP'. Use it to configure the plugin.
4. Go to the different submenus to view and configure the options.

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

== Upgrade Notice ==
