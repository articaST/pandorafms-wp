# pandorafms-wp
## v0.1

Hardening, monitoring and security plugin for Wordpress. 100% free and openSource. No tricks, no freemium of "enterprise" features. 

## Features implemented in this version:

* Rename of login page.
* Recapcha on login.
* Protection against login brute force.
* Protection against login: IP Ban.
* Access accounting log.
* Secure Robots.txt setup.
* Secure setup against malicious PHP code upload.
* Scan for malicious code injection.
* New plugin install monitoring.
* New theme install monitoring.
* File content comparishon against official WP core files.
* File change detection on custom contents & plugins.
* New file detector.
* WP generator disabler.
* Check of WP Core version update.
* Check of Plugins version update (with exception list).
* Monitor new posts in 24hrs.
* Check for default "admin" user account.
* Monitor new comments in 24hrs.
* User password audit for weak passwords.
* Check for weak file/directory permissions.
* Send email on different events: new user, new user login, user change email, new plugin added, new theme added.
* Remote integration with [Pandora FMS](http://pandorafms.com) for gather information in a central monitoring solution. Pandora FMS is also an OpenSource [server monitoring](http://pandorafms.com/monitoring-solutions/server-monitoring/) solution.

## Requirements and setup

PandoraFMS-WP requires (optionally) a plugin for REST API, called "JSON REST API". You only need if you want to integrate the monitoring/status information of the WP site in a central management console with Pandor FMS. This is an optional feature, you can manage all information from the Wordpress itself.

## Limitations 

This not support WP Multisite. 
Tested only with 4.5.2 version.

## More information

At this time this is our first readme file :-)



## comentario