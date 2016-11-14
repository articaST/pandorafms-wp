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