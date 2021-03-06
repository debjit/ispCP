<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2011 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2011 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$cfg = ispCP_Registry::get('Config');

$tpl = ispCP_TemplateEngine::getInstance();
$template = 'user_add2.tpl';

// check if we have only hosting plans for admins - reseller should not edit them
if (isset($cfg->HOSTING_PLANS_LEVEL)
	&& $cfg->HOSTING_PLANS_LEVEL === 'admin') {
	user_goto('users.php?psi=last');
}

// static page messages.
gen_logged_from($tpl);

$tpl->assign(
		array(
			'TR_PAGE_TITLE'					=> tr('ispCP - User/Add user(step2)'),
			'TR_ADD_USER'					=> tr('Add user'),
			'TR_HOSTING_PLAN_PROPERTIES'	=> tr('Hosting plan properties'),
			'TR_TEMPLATE_NAME'				=> tr('Template name'),
			'TR_MAX_DOMAIN'					=> tr('Max domains<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_SUBDOMAIN'				=> tr('Max subdomains<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_DOMAIN_ALIAS'			=> tr('Max aliases<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_MAIL_COUNT'				=> tr('Mail accounts limit<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_FTP'					=> tr('FTP accounts limit<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_SQL_DB'					=> tr('SQL databases limit<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_SQL_USERS'				=> tr('SQL users limit<br /><em>(-1 disabled, 0 unlimited)</em>'),
			'TR_MAX_TRAFFIC'				=> tr('Traffic limit [MB]<br /><em>(0 unlimited)</em>'),
			'TR_MAX_DISK_USAGE'				=> tr('Disk limit [MB]<br /><em>(0 unlimited)</em>'),
			'TR_PHP'						=> tr('PHP'),
			'TR_CGI'						=> tr('CGI / Perl'),
			'TR_BACKUP'						=> tr('Backup'),
			'TR_BACKUP_DOMAIN'				=> tr('Domain'),
			'TR_BACKUP_SQL'					=> tr('SQL'),
			'TR_BACKUP_FULL'				=> tr('Full'),
			'TR_BACKUP_NO'					=> tr('No'),
			'TR_DNS'						=> tr('Manual DNS support'),
			'TR_YES'						=> tr('Yes'),
			'TR_NO'							=> tr('No'),
			'TR_NEXT_STEP'					=> tr('Next step'),
			'TR_APACHE_LOGS'				=> tr('Apache logs'),
			'TR_AWSTATS'					=> tr('Awstats')
		)
);

gen_reseller_mainmenu($tpl, 'main_menu_users_manage.tpl');
gen_reseller_menu($tpl, 'menu_users_manage.tpl');

if (!get_pageone_param()) {
	set_page_message(
		tr("Domain data has been altered. Please enter again."),
		'warning'
	);
	unset_messages();
	user_goto('user_add1.php');
}

if (isset($_POST['uaction'])
	&& ("user_add2_nxt" === $_POST['uaction'])
	&& (!isset($_SESSION['step_one']))) {
	if (check_user_data($tpl)) {
		$_SESSION["step_two_data"] = "$dmn_name;0;";
		$_SESSION["ch_hpprops"] = "$hp_php;$hp_cgi;$hp_sub;$hp_als;$hp_mail;$hp_ftp;$hp_sql_db;$hp_sql_user;$hp_traff;$hp_disk;$hp_backup;$hp_dns";

		if (reseller_limits_check($sql, $ehp_error, $_SESSION['user_id'], 0, $_SESSION["ch_hpprops"])) {
			user_goto('user_add3.php');
		}
	}
} else {
	unset($_SESSION['step_one']);
	global $dmn_chp;
	get_hp_data($dmn_chp, $_SESSION['user_id']);
	$tpl->assign('MESSAGE', '');
}

get_init_au2_page($tpl);
gen_page_message($tpl);

list(
	$rsub_max,
	$rals_max,
	$rmail_max,
	$rftp_max,
	$rsql_db_max,
	$rsql_user_max
	) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');

if ($rsub_max == "-1") $tpl->assign('ALIAS_ADD', '');
if ($rals_max == "-1") $tpl->assign('SUBDOMAIN_ADD', '');
if ($rmail_max == "-1") $tpl->assign('MAIL_ADD', '');
if ($rftp_max == "-1") $tpl->assign('FTP_ADD', '');
if ($rsql_db_max == "-1") $tpl->assign('SQL_DB_ADD', '');
if ($rsql_user_max == "-1") $tpl->assign('SQL_USER_ADD', '');

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

//unset_messages();

// Function declaration

/**
 * get param of previous page
 */
function get_pageone_param() {
	// @todo check if really needed
	global $dmn_name, $dmn_expire, $dmn_chp;

	if (isset($_SESSION['dmn_name'])) {
		$dmn_name 			= $_SESSION['dmn_name'];
		$dmn_expire 		= $_SESSION['dmn_expire_date'];
		// TODO: validate global var
		//$dmn_expire_never = $_SESSION['dmn_expire_never'];
		$dmn_chp 			= $_SESSION['dmn_tpl'];
	} else {
		return false;
	}

	return true;
} // End of get_pageone_param()

/**
 * Show page with initial data fields
 * @param ispCP_TemplateEngine $tpl
 */
function get_init_au2_page($tpl) {
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk, $hp_backup, $hp_dns;

	$cfg = ispCP_Registry::get('Config');

	$tpl->assign(
			array(
				'VL_TEMPLATE_NAME'	=> tohtml($hp_name),
				'MAX_DMN_CNT'		=> '',
				'MAX_SUBDMN_CNT'	=> $hp_sub,
				'MAX_DMN_ALIAS_CNT'	=> $hp_als,
				'MAX_MAIL_CNT'		=> $hp_mail,
				'MAX_FTP_CNT'		=> $hp_ftp,
				'MAX_SQL_CNT'		=> $hp_sql_db,
				'VL_MAX_SQL_USERS'	=> $hp_sql_user,
				'VL_MAX_TRAFFIC'	=> $hp_traff,
				'VL_MAX_DISK_USAGE'	=> $hp_disk,
				'VL_PHPY'			=> ($hp_php === '_yes_') ? $cfg->HTML_CHECKED : '',
				'VL_PHPN'			=> ($hp_php === '_no_') ? $cfg->HTML_CHECKED : '',
				'VL_CGIY'			=> ($hp_cgi === '_yes_') ? $cfg->HTML_CHECKED : '',
				'VL_CGIN'			=> ($hp_cgi === '_no_') ? $cfg->HTML_CHECKED : '',
				'VL_BACKUPD'		=> ($hp_backup === '_dmn_') ? $cfg->HTML_CHECKED : '',
				'VL_BACKUPS'		=> ($hp_backup === '_sql_') ? $cfg->HTML_CHECKED : '',
				'VL_BACKUPF'		=> ($hp_backup === '_full_') ? $cfg->HTML_CHECKED : '',
				'VL_BACKUPN'		=> ($hp_backup === '_no_') ? $cfg->HTML_CHECKED : '',
				'VL_DNSY'			=> ($hp_dns === '_yes_') ? $cfg->HTML_CHECKED : '',
				'VL_DNSN'			=> ($hp_dns === '_no_') ? $cfg->HTML_CHECKED : ''
			)
	);

} // End of get_init_au2_page()

/**
 * Get data for hosting plan
 */
function get_hp_data($hpid, $admin_id) {
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk, $hp_backup, $hp_dns;

	$sql = ispCP_Registry::get('Db');

	$query = "SELECT `name`, `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";

	$res = exec_query($sql, $query, array($admin_id, $hpid));

	if (0 !== $res->rowCount()) {
		$data = $res->fetchRow();

		$props = $data['props'];

		list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
			$hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns) = explode(";", $props);

		$hp_name = $data['name'];
	} else {
			$hp_name = 'Custom';
			$hp_php = '_no_';
			$hp_cgi = '_no_';
			$hp_sub = '';
			$hp_als = '';
			$hp_mail = '';
			$hp_ftp = '';
			$hp_sql_db = '';
			$hp_sql_user = '';
			$hp_traff = '';
			$hp_disk = '';
			$hp_backup = '_no_';
			$hp_dns = '_no_';
	}
} // End of get_hp_data()

/**
 * Check validity of input data
 * @param ispCP_TemplateEngine $tpl
 */
function check_user_data($tpl) {
	global $hp_name, $hp_php, $hp_cgi;
	global $hp_sub, $hp_als, $hp_mail;
	global $hp_ftp, $hp_sql_db, $hp_sql_user;
	global $hp_traff, $hp_disk, $hp_dmn, $hp_backup, $hp_dns;
	global $dmn_chp;

	//$sql = ispCP_Registry::get('Db');

	$ehp_error = array();

	// Get data for fields from previous page
	if (isset($_POST['template'])) {
		$hp_name = $_POST['template'];
	}

	if (isset($_POST['nreseller_max_domain_cnt'])) {
		$hp_dmn = clean_input($_POST['nreseller_max_domain_cnt']);
	}

	if (isset($_POST['nreseller_max_subdomain_cnt'])) {
		$hp_sub = clean_input($_POST['nreseller_max_subdomain_cnt']);
	}

	if (isset($_POST['nreseller_max_alias_cnt'])) {
		$hp_als = clean_input($_POST['nreseller_max_alias_cnt']);
	}

	if (isset($_POST['nreseller_max_mail_cnt'])) {
		$hp_mail = clean_input($_POST['nreseller_max_mail_cnt']);
	}

	if (isset($_POST['nreseller_max_ftp_cnt']) || $hp_ftp == -1) {
		$hp_ftp = clean_input($_POST['nreseller_max_ftp_cnt']);
	}

	if (isset($_POST['nreseller_max_sql_db_cnt'])) {
		$hp_sql_db = clean_input($_POST['nreseller_max_sql_db_cnt']);
	}

	if (isset($_POST['nreseller_max_sql_user_cnt'])) {
		$hp_sql_user = clean_input($_POST['nreseller_max_sql_user_cnt']);
	}

	if (isset($_POST['nreseller_max_traffic'])) {
		$hp_traff = clean_input($_POST['nreseller_max_traffic']);
	}

	if (isset($_POST['nreseller_max_disk'])) {
		$hp_disk = clean_input($_POST['nreseller_max_disk']);
	}

	if (isset($_POST['php'])) {
		$hp_php = $_POST['php'];
	}

	if (isset($_POST['cgi'])) {
		$hp_cgi = $_POST['cgi'];
	}

	if (isset($_POST['backup'])) {
		$hp_backup = $_POST['backup'];
	}

	if (isset($_POST['dns'])) {
		$hp_dns = $_POST['dns'];
	}

	// Begin checking...
	list(
		$rsub_max,
		$rals_max,
		$rmail_max,
		$rftp_max,
		$rsql_db_max,
		$rsql_user_max
		) = check_reseller_permissions($_SESSION['user_id'], 'all_permissions');
	if ($rsub_max == "-1") {
		$hp_sub = "-1";
	} elseif (!ispcp_limit_check($hp_sub, -1)) {
		$ehp_error[] = tr('Incorrect subdomains limit!');
	}
	if ($rals_max == "-1") {
		$hp_als = "-1";
	} elseif (!ispcp_limit_check($hp_als, -1)) {
		$ehp_error[] = tr('Incorrect aliases limit!');
	}

	if ($rmail_max == "-1") {
		$hp_mail = "-1";
	} elseif (!ispcp_limit_check($hp_mail, -1)) {
		$ehp_error[] = tr('Incorrect mail accounts limit!');
	}

	if ($rftp_max == "-1") {
		$hp_ftp = "-1";
	} elseif (!ispcp_limit_check($hp_ftp, -1)) {
		$ehp_error[] = tr('Incorrect FTP accounts limit!');
	}

	if ($rsql_db_max == "-1") {
		$hp_sql_db = "-1";
	} elseif (!ispcp_limit_check($hp_sql_db, -1)) {
		$ehp_error[] = tr('Incorrect SQL databases limit!');
	} else if ($hp_sql_user != -1 && $hp_sql_db == -1) {
		$ehp_error[] = tr('SQL users limit is <em>disabled</em>!');
	}

	if ($rsql_user_max == "-1") {
		$hp_sql_user = "-1";
	} elseif (!ispcp_limit_check($hp_sql_user, -1)) {
		$ehp_error[] = tr('Incorrect SQL users limit!');
	} else if ($hp_sql_user == -1 && $hp_sql_db != -1) {
		$ehp_error[] = tr('SQL databases limit is not <em>disabled</em>!');
	}

	if (!ispcp_limit_check($hp_traff, null)) {
		$ehp_error[] = tr('Incorrect traffic limit!');
	}

	if (!ispcp_limit_check($hp_disk, null)) {
		$ehp_error[] = tr('Incorrect disk quota limit!');
	}

	if (empty($ehp_error) && empty($_SESSION['user_page_message'])) {
		$tpl->assign('MESSAGE', '');
		// send data through session
		return true;
	} else {
		set_page_message(format_message($ehp_error), 'error');
		return false;
	}
} // End of check_user_data()

/**
 * Check if hosting plan with this name already exists!
 */
function check_hosting_plan_name($admin_id) {
	global $hp_name;
	$sql = ispCP_Registry::get('Db');

	$query = "SELECT `id` FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?";
	$res = exec_query($sql, $query, array($hp_name, $admin_id));

	if ($res->rowCount() !== 0) {
		return false;
	}

	return true;
} // End of check_hosting_plan_name()
?>
