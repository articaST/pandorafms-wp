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

class PFMS_Widget_Dashboard {
	
	//=== INIT SINGLETON CODE ==========================================
	private static $instance = null;
	
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	//=== END SINGLETON CODE ===========================================
	
	
	private function __construct() {
	}
	
	
	//=== INIT HOOKS CODE ==============================================
	public static function show_dashboard() {
		// Check if installed
			error_log( "show_dashboard" );
		// Only active the plugin again
	}
	//=== END HOOKS CODE ===============================================
}
?>