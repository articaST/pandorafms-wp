	
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
	

	function force_cron_audit_password() {

		jQuery(document).ready(function($) {
			var data = {
				'action': 'force_cron_audit_password'
			};
			
			jQuery("#audit_password_status").empty();
			jQuery("#audit_password_status").append(
				jQuery("#ajax_loading").clone());
			
			jQuery("#audit_password_last_execute").empty();
			
			jQuery.post(ajaxurl, data, function(response) {
				jQuery("#audit_password_status").empty();
				
				if (response.status) {
					jQuery("#audit_password_status").append(
						jQuery("#ajax_result_ok").clone());
				}
				else {
					jQuery("#audit_password_status").append(
						jQuery("#ajax_result_fail").clone());
				}
				
				jQuery("#audit_password_last_execute").append(
					response.last_execution);
			},
			"json");
		})
	}
	

	function force_cron_audit_files() {

		jQuery(document).ready(function($) {
			var data = {
				'action': 'force_cron_audit_files'
			};
			
			jQuery("#audit_files_status").empty();
			jQuery("#audit_files_status").append(
				jQuery("#ajax_loading").clone());
			
			jQuery("#audit_files_last_execute").empty();
			
			//console.log('Aqui debe dar el status 0 o 1');

			jQuery.get(ajaxurl, data, function(response) {
				jQuery("#audit_files_status").empty();
				
				console.log(response); 

				if (response.status) {
					jQuery("#audit_files_status").append(
						jQuery("#ajax_result_ok").clone());
				}
				else {
					jQuery("#audit_files_status").append(
						jQuery("#ajax_result_fail").clone());
				}
				
				jQuery("#audit_files_last_execute").append(
					response.last_execution);
			},
			"json");

		})

	}
	

	function show_weak_user_dialog() {

		jQuery(document).ready(function($) {		
			var status = jQuery("#audit_password_status img").attr("id");
			
			if (status !== "ajax_result_fail") {
				return;
			}
			
			var data = {
				'action': 'get_list_users_with_weak_password'
			};
			
			jQuery("#audit_password_status").empty();
			jQuery("#audit_password_status").append(
				jQuery("#ajax_loading").clone());
			
			jQuery.post(ajaxurl, data, function(response) {
				var list_users = jQuery.makeArray(response.list_users);
				
				jQuery("#audit_password_status").empty();
				jQuery("#audit_password_status").append(
						jQuery("#ajax_result_fail").clone());
				
				var dialog_weak_user =
					jQuery("<div id='dialog_weak_user' title='List weak users' ")
						.html(list_users.join('<br />'))
						.appendTo("body");
				
				dialog_weak_user.dialog({
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
	
	///This table is shown in the dashboard, Monitoring-> Filesystem audit
	function show_files_dialog() {

		jQuery(document).ready(function($) {		
			var status = jQuery("#audit_files_status img").attr("id");
			
			if (status !== "ajax_result_fail") {
				return;
			}
			
			var data = {
				'action': 'get_list_audit_files'
			};
			
			jQuery("#audit_files_status").empty();
			jQuery("#audit_files_status").append(
				jQuery("#ajax_loading").clone());
			
			jQuery.post(ajaxurl, data, function(response) {
				var list_files = jQuery.makeArray(response.list_files);

				jQuery("#audit_files_status").empty();
				jQuery("#audit_files_status").append(
						jQuery("#ajax_result_fail").clone());
				
				var $table = jQuery("<table width='100%'>")
					.append("<thead>" +
						"<tr>" +
							"<th align='left'>Path</th>" +
							"<th>Date</th>" +
							"<th>Status</th>" +
							"<th>No writable others</th>" +
							"<th>Original</th>" +
							"<th>No Infected</th>" +
						"</tr>" +
						"</thead>");
				jQuery.each(list_files, function(i, file) {
					var tr = "<tr>";
					
					jQuery.each(file, function(i, item) {
						if (i == "path") {
							
							tr = tr + "<td align='left'>";
						}
						else {
							tr = tr + "<td align='center'>";
						}
						tr = tr + item + "</td>";
					});
					tr = tr + "</tr>";
					
					$table.append(tr);
				});
				
				var dialog_weak_user =
					jQuery("<div id='dialog_list_files' title='List change or new files' />")
						.append($table)
						.appendTo("body");
				
				dialog_weak_user.dialog({
					'dialogClass' : 'wp-dialog',
					'height': 300,
					'width' : '80%',
					'modal' : true,
					'autoOpen' : false,
					'closeOnEscape' : true})
					.dialog('open');
				
			},
			"json");
		})
	}


	function empty_rename_login_page_or_repatcha(){

		jQuery(document).ready(function($) {
			var data = {
				
			}	

			if(jQuery("[name = 'pfmswp-options-access_control[activate_login_rename]' ]").is(':checked') == true 
				&& jQuery("[name = 'pfmswp-options-access_control[login_rename_page]' ]").val() == '') {

				jQuery("input[name='submit-access_control']").prop("type", "button");
				jQuery("[name = 'pfmswp-options-access_control[login_rename_page]' ]").css('border-color','red');
				alert("No puedes dejar el cuadro de la url vacío.");
			}
			else if(jQuery("[name = 'pfmswp-options-access_control[activate_login_recaptcha]' ]").is(':checked') == true 
				&& jQuery("[name = 'pfmswp-options-access_control[site_key]' ]").val() == '') {

				jQuery("input[name='submit-access_control']").prop("type", "button");
				jQuery("[name = 'pfmswp-options-access_control[site_key]' ]").css('border-color','red');	
				alert("No puedes dejar el site key vacío.");
			}
			else if(jQuery("[name = 'pfmswp-options-access_control[activate_login_recaptcha]' ]").is(':checked') == true 
				&&  jQuery("[name = 'pfmswp-options-access_control[secret]' ]").val() == '') {

				jQuery("input[name='submit-access_control']").prop("type", "button");			
				jQuery("[name = 'pfmswp-options-access_control[secret]' ]").css('border-color','red');
				alert("No puedes dejar el secret key vacío.");

			}
			else if(jQuery("[name = 'pfmswp-options-access_control[activate_login_recaptcha]' ]").is(':checked') == true 
				&&  (jQuery("[name = 'pfmswp-options-access_control[secret]' ]").val() == '' &&  jQuery("[name = 'pfmswp-options-access_control[site_key]' ]").val() == '') ) {

				jQuery("input[name='submit-access_control']").prop("type", "button");			
				jQuery("[name = 'pfmswp-options-access_control[secret]' ]").css('border-color','red');				
				jQuery("[name = 'pfmswp-options-access_control[site_key]' ]").css('border-color','red');
				alert("No puedes dejar el secret key ni el secret key vacíos.");

			}
			else{
				jQuery("input[name='submit-access_control']").prop("type", "submit");						
			}
		

			jQuery.post(ajaxurl, data, function(response) {
					//console.log('llamada ajax');									
			})

		})

	}


