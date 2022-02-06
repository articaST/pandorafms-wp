	
/*	
	var authwindow;

	function pfms_popupwindow(url, w, h) {
		'use strict';
		var left = (screen.width/2)-(w/2);
		var top = (screen.height/8);
		authwindow = window.open(url, '', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
	}

	function pfms_closepopupwindow() {
		authwindow.close();
	}
*/

	//this function is called by the button 'Send test email' and calls the function send_email_files_changed
	function send_test_email() {

		jQuery(document).ready(function($) {
			var data = {
				'action': 'send_test_email'				
			}

			console.log(data);

			jQuery.post(ajaxurl, data, function(response) {
				console.log('llamada ajax');
					console.log(response); 
				
			})

		})

	} 

			
	function check_admin_user_enabled() {

		jQuery(document).ready(function($) {
			var data = {
				'action': 'check_admin_user_enabled'
			};

			jQuery("#admin_user_enabled").empty();
			jQuery("#admin_user_enabled").append(
				jQuery("#ajax_loading").clone());
			
			jQuery.post(ajaxurl, data, function(response) {
				jQuery("#admin_user_enabled").empty();
				
				if (response.result) {
					jQuery("#admin_user_enabled").append(
						jQuery("#ajax_result_ok").clone());
				}
				else {
					jQuery("#admin_user_enabled").append(
						jQuery("#ajax_result_fail").clone());
				}
			},
			"json");

		})

	}

	
	function check_plugins_pending_update() {

		jQuery(document).ready(function($) {
			var data = {
				'action': 'check_plugins_pending_update'
			};
			
			jQuery("#ajax_result_fail_plugins_are_updated")
				.hide();
			jQuery("#ajax_result_ok_plugins_are_updated")
				.hide();
			jQuery("#ajax_result_loading_plugins_are_updated")
				.show();
			
			jQuery.post(ajaxurl, data, function(response) {
				jQuery("#ajax_result_loading_plugins_are_updated")
					.hide();
				
				if (response.result) {
					jQuery("#ajax_result_fail_plugins_are_updated")
						.hide();
					jQuery("#ajax_result_ok_plugins_are_updated")
						.show();
				}
				else {
					jQuery("#ajax_result_fail_plugins_are_updated")
						.show();
					jQuery("#ajax_result_ok_plugins_are_updated")
						.hide();
				}
				
				var dialog_plugins_pending_update =
					jQuery("<div id='dialog_plugins_pending_update' title='List plugins pending update' />")
						.html(response.plugins.join('<br />'))
						.appendTo("body");
				
				dialog_plugins_pending_update.dialog({
					'dialogClass' : 'wp-dialog',
					'height': 200,
					'modal' : true,
					'autoOpen' : false,
					'closeOnEscape' : true})
					.dialog('open');
			},
			"json");
		})
	}


