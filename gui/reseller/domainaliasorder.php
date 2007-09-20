<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$theme_color = $cfg['USER_INITIAL_THEME'];

if(isset($_GET['action']) && $_GET['action'] === "delete") {

	if(isset($_GET['del_id']) && !empty($_GET['del_id']))
		$del_id = $_GET['del_id'];
	else{
		$_SESSION['orderaldel'] = '_no_';
		header("Location: domain_alias.php");
		die();
	}

	$query = "DELETE FROM domain_aliasses WHERE alias_id='".$del_id."'";
	$rs = exec_query($sql, $query);
	header("Location: domain_alias.php");
	die();

} else if (isset($_GET['action']) && $_GET['action'] === "activate") {

	if(isset($_GET['act_id']) && !empty($_GET['act_id']))
		$act_id = $_GET['act_id'];
	else{
		$_SESSION['orderalact'] = '_no_';
		header("Location: domain_alias.php");
		die();
	}
	$query = "SELECT alias_name FROM domain_aliasses WHERE alias_id='".$act_id."'";
	$rs = exec_query($sql, $query);
		if ($rs -> RecordCount() == 0) {
			header('Location: domain_alias.php');
			die();
		}
	$alias_name = $rs -> fields['alias_name'];

	$query = "UPDATE domain_aliasses SET alias_status='toadd' WHERE alias_id='".$act_id."'";
	$rs = exec_query($sql, $query);

	send_request();

	$admin_login = $_SESSION['user_logged'];

	write_log("$admin_login: domain alias activated: $alias_name.");

	set_page_message(tr('Alias scheduled for activation!'));

	$_SESSION['orderalact'] = '_yes_';
	header("Location: domain_alias.php");
	die();

} else {
	header("Location: domain_alias.php");
	die();
}
?>