<?php
/**
 * @package PandoraFMS WP
 * @version 0.0
 */
/*
Plugin Name: PandoraFMS WP
Plugin URI: https://github.com/articaST/pandorafms-wp
Description: Hardening, monitoring and security plugin for Wordpress.
Author: Artica Soluciones Tecnologicas
Version: 0.0
Author URI: http://artica.es/
Text Domain: pandorafms-wp
License: AGPLv3
Copyright: (c) 2016-2016 Artica Soluciones Tecnologicas
*/

if ( ! defined( 'ABSPATH' ) ) die();

require_once(plugin_dir_path(__FILE__) . "/includes/PandoraFMS_WP.class.php");


register_activation_hook(__FILE__, array('PandoraFMS_WP', 'activation'));
register_deactivation_hook(__FILE__, array('PandoraFMS_WP', 'deactivation'));
?>