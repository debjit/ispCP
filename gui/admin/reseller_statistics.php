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

$template = 'reseller_statistics.tpl';

$year = 0;
$month = 0;

if (isset($_POST['month']) && isset($_POST['year'])) {
	$year = $_POST['year'];

	$month = $_POST['month'];
} else if (isset($_GET['month']) && isset($_GET['year'])) {
	$month = $_GET['month'];

	$year = $_GET['year'];
}

$crnt_month = '';
$crnt_year = '';

// static page messages
$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('ispCP - Admin/Reseller statistics'),
		'TR_RESELLER_STATISTICS' => tr('Reseller statistics table'),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_RESELLER_NAME' => tr('Reseller name'),
		'TR_TRAFF' => tr('Traffic'),
		'TR_DISK' => tr('Disk'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_SUBDOMAIN' => tr('Subdomain'),
		'TR_ALIAS' => tr('Alias'),
		'TR_MAIL' => tr('Mail'),
		'TR_FTP' => tr('FTP'),
		'TR_SQL_DB' => tr('SQL database'),
		'TR_SQL_USER' => tr('SQL user'),
	)
);

gen_admin_mainmenu($tpl, 'main_menu_statistics.tpl');
gen_admin_menu($tpl, 'menu_statistics.tpl');

gen_page_message($tpl);
generate_page ($tpl);

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

unset_messages();

/**
 * @param ispCP_TemplateEngine $tpl
 */
function generate_page($tpl) {

	global $month, $year;

	$cfg = ispCP_Registry::get('Config');
	$sql = ispCP_Registry::get('Db');

	$start_index = 0;

	$rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_GET['psi'];
	} else if (isset($_POST['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_POST['psi'];
	}

	$tpl->assign(
		array(
			'POST_PREV_PSI' => $start_index
		)
	);

	// count query
	$count_query = "
		SELECT
			COUNT(`admin_id`) AS cnt
		FROM
			`admin`
		WHERE
			`admin_type` = 'reseller'
	";

	$query = <<<SQL_QUERY
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_type` = 'reseller'
		ORDER BY
			`admin_name` DESC
		LIMIT
			$start_index, $rows_per_page
SQL_QUERY;

	$rs = exec_query($sql, $count_query);
	$records_count = $rs->fields['cnt'];

	$rs = exec_query($sql, $query);

	if ($rs->rowCount() == 0) {

		$tpl->assign(
			array(
				'TRAFFIC_TABLE' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => ''
			)
		);

		set_page_message(tr('There are no resellers in your system!'), 'notice');
		return;
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY'	=> '',
					'PREV_PSI'			=> $prev_si
				)
			);
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $next_si
				)
			);
		}

		$tpl->assign(
			array(
				'PAGE_MESSAGE' => ''
			)
		);

		gen_select_lists($tpl, @$month, @$year);

		$row = 1;

		while (!$rs->EOF) {
			generate_reseller_entry($tpl, $rs->fields['admin_id'], $rs->fields['admin_name'], $row++);

			$rs->moveNext();
		}
	}

}

/**
 * @param ispCP_TemplateEngine $tpl
 * @param int $reseller_id
 * @param string $reseller_name
 * @param int $row
 * @return void
 */
function generate_reseller_entry($tpl, $reseller_id, $reseller_name, $row) {
	global $crnt_month, $crnt_year;

	list($rdmn_current, $rdmn_max,
		$rsub_current, $rsub_max,
		$rals_current, $rals_max,
		$rmail_current, $rmail_max,
		$rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max,
		$rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max,
		$rdisk_current, $rdisk_max
	) = generate_reseller_props($reseller_id);

	list($udmn_current, , ,
		$usub_current, , ,
		$uals_current, , ,
		$umail_current, , ,
		$uftp_current, , ,
		$usql_db_current, , ,
		$usql_user_current, , ,
		$utraff_current, , ,
		$udisk_current, ,
	) = generate_reseller_users_props($reseller_id);

	$rtraff_max = $rtraff_max * 1024 * 1024;
	$rtraff_current = $rtraff_current * 1024 * 1024;
	$rdisk_max = $rdisk_max * 1024 * 1024;
	$rdisk_current = $rdisk_current * 1024 * 1024;

	$traff_show_percent = calc_bar_value($utraff_current, $rtraff_max, 400);
	$disk_show_percent  = calc_bar_value($udisk_current, $rdisk_max, 400);

	if ($rtraff_max > 0) {
		$traff_percent = (($utraff_current/$rtraff_max)*100 < 99.7) ? ($utraff_current/$rtraff_max)*100 : 99.7;
	} else {
		$traff_percent = 0;
	}

	if ($rdisk_max > 0) {
		$disk_percent = (($udisk_current/$rdisk_max)*100 < 99.7) ? ($udisk_current/$rdisk_max)*100 : 99.7;
	} else {
		$disk_percent = 0;
	}

	$tpl->assign(
		array('ITEM_CLASS' => ($row % 2 == 0) ? 'content' : 'content2')
	);

	$tpl->assign(
		array(
			'RESELLER_NAME' => tohtml($reseller_name),
			'RESELLER_ID' => $reseller_id,
			'MONTH' => $crnt_month,
			'YEAR' => $crnt_year,

			'TRAFF_SHOW_PERCENT' => $traff_show_percent,
			'TRAFF_PERCENT' => $traff_percent,

			'TRAFF_MSG' => ($rtraff_max)
				? tr('%1$s / %2$s <br/>of<br/> <strong>%3$s</strong>', sizeit($utraff_current), sizeit($rtraff_current), sizeit($rtraff_max))
				: tr('%1$s / %2$s <br/>of<br/> <strong>unlimited</strong>', sizeit($utraff_current), sizeit($rtraff_current)),

			'DISK_SHOW_PERCENT' => $disk_show_percent,
			'DISK_PERCENT' => $disk_percent,

			'DISK_MSG' => ($rdisk_max)
				? tr('%1$s / %2$s <br/>of<br/> <strong>%3$s</strong>', sizeit($udisk_current), sizeit($rdisk_current), sizeit($rdisk_max))
				: tr('%1$s / %2$s <br/>of<br/> <strong>unlimited</strong>', sizeit($udisk_current), sizeit($rdisk_current)),

			'DMN_MSG' => ($rdmn_max)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $udmn_current, $rdmn_current, $rdmn_max)
				: tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $udmn_current, $rdmn_current),

			'SUB_MSG' => ($rsub_max > 0)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $usub_current, $rsub_current, $rsub_max)
				: (($rsub_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $usub_current, $rsub_current)),

			'ALS_MSG' => ($rals_max > 0)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $uals_current, $rals_current, $rals_max)
				: (($rals_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $uals_current, $rals_current)),

			'MAIL_MSG' => ($rmail_max > 0)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $umail_current, $rmail_current, $rmail_max)
				: (($rmail_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $umail_current, $rmail_current)),

			'FTP_MSG' => ($rftp_max > 0)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $uftp_current, $rftp_current, $rftp_max)
				: (($rftp_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $uftp_current, $rftp_current)),

			'SQL_DB_MSG' => ($rsql_db_max > 0)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $usql_db_current, $rsql_db_current, $rsql_db_max)
				: (($rsql_db_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $usql_db_current, $rsql_db_current)),

			'SQL_USER_MSG' => ($rsql_user_max > 0)
				? tr('%1$d / %2$d <br/>of<br/> <strong>%3$d</strong>', $usql_user_current, $rsql_user_current, $rsql_user_max)
				: (($rsql_user_max === "-1") ? tr('<strong>disabled</strong>') : tr('%1$d / %2$d <br/>of<br/> <strong>unlimited</strong>', $usql_user_current, $rsql_user_current))
		)
	);

}
?>