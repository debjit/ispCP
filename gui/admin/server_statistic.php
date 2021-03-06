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
$template = 'server_statistic.tpl';

global $month, $year;

if (isset($_GET['month']) && isset($_GET['year'])) {
	$year = intval($_GET['year']);
	$month = intval($_GET['month']);
} else if (isset($_POST['month']) && isset($_POST['year'])) {
	$year = intval($_POST['year']);
	$month = intval($_POST['month']);
} else {
	$month = date('m');
	$year = date('Y');
}

// static page messages
$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('ispCP - Admin/Server statistics'),
		'TR_SERVER_STATISTICS' => tr('Server statistics'),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_DAY' => tr('Day'),
		'TR_WEB_IN' => tr('Web in'),
		'TR_WEB_OUT' => tr('Web out'),
		'TR_SMTP_IN' => tr('SMTP in'),
		'TR_SMTP_OUT' => tr('SMTP out'),
		'TR_POP_IN' => tr('POP3/IMAP in'),
		'TR_POP_OUT' => tr('POP3/IMAP out'),
		'TR_OTHER_IN' => tr('Other in'),
		'TR_OTHER_OUT' => tr('Other out'),
		'TR_ALL_IN' => tr('All in'),
		'TR_ALL_OUT' => tr('All out'),
		'TR_ALL' => tr('All')
	)
);

gen_admin_mainmenu($tpl, 'main_menu_statistics.tpl');
gen_admin_menu($tpl, 'menu_statistics.tpl');

gen_page_message($tpl);
gen_select_lists($tpl, $month, $year);
generate_page($tpl);

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug($tpl);
}

$tpl->display($template);

unset_messages();

function get_server_trafic($from, $to) {
	$sql = ispCP_Registry::get('Db');

	$query = "
		SELECT
			IFNULL(SUM(`bytes_in`), 0) AS sbin,
			IFNULL(SUM(`bytes_out`), 0) AS sbout,
			IFNULL(SUM(`bytes_mail_in`), 0) AS smbin,
			IFNULL(SUM(`bytes_mail_out`), 0) AS smbout,
			IFNULL(SUM(`bytes_pop_in`), 0) AS spbin,
			IFNULL(SUM(`bytes_pop_out`), 0) AS spbout,
			IFNULL(SUM(`bytes_web_in`), 0) AS swbin,
			IFNULL(SUM(`bytes_web_out`), 0) AS swbout
		FROM
			`server_traffic`
		WHERE
			`traff_time` > ? AND `traff_time` < ?
	";

	$rs = exec_query($sql, $query, array($from, $to));

	if ($rs->recordCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	} else {
		return array($rs->fields['swbin'], $rs->fields['swbout'],
			$rs->fields['smbin'], $rs->fields['smbout'],
			$rs->fields['spbin'], $rs->fields['spbout'],
			$rs->fields['sbin'] - ($rs->fields['swbin'] + $rs->fields['smbin'] + $rs->fields['spbin']),
			$rs->fields['sbout'] - ($rs->fields['swbout'] + $rs->fields['smbout'] + $rs->fields['spbout']),
			$rs->fields['sbin'], $rs->fields['sbout']);
	}
}

/**
 * @param ispCP_TemplateEngine $tpl
 */
function generate_page($tpl) {

	global $month, $year;
	$sql = ispCP_Registry::get('Db');


	if ($month == date('m') && $year == date('Y')) {
		$curday = date('j');
	} else {
		$tmp = mktime(1, 0, 0, $month + 1, 0, $year);
		$curday = date('j', $tmp);
	}

	$all[0] = 0;
	$all[1] = 0;
	$all[2] = 0;
	$all[3] = 0;
	$all[4] = 0;
	$all[5] = 0;
	$all[6] = 0;
	$all[7] = 0;

	for ($i = 1; $i <= $curday; $i++) {
		$ftm = mktime(0, 0, 0, $month, $i, $year);
		$ltm = mktime(23, 59, 59, $month, $i, $year);

		$query = "
			SELECT
				COUNT(`bytes_in`) AS cnt
			FROM
				`server_traffic`
			WHERE
				`traff_time` > ? AND `traff_time` < ?
		";

		$rs = exec_query($sql, $query, array($ftm, $ltm));
		$has_data = false;
		// if ($rs->fields['cnt'] > 0) {
		if ($rs->recordCount() > 0) {
			list($web_in,
				$web_out,
				$smtp_in,
				$smtp_out,
				$pop_in,
				$pop_out,
				$other_in,
				$other_out,
				$all_in,
				$all_out) = get_server_trafic($ftm, $ltm);

			$has_data = true;

			$tpl->append('ITEM_CLASS', ($i % 2 == 0) ? 'content' : 'content2');

			$tpl->append(
				array(
					'DAY' => $i,
					'YEAR' => $year,
					'MONTH' => $month,
					'WEB_IN' => sizeit($web_in),
					'WEB_OUT' => sizeit($web_out),
					'SMTP_IN' => sizeit($smtp_in),
					'SMTP_OUT' => sizeit($smtp_out),
					'POP_IN' => sizeit($pop_in),
					'POP_OUT' => sizeit($pop_out),
					'OTHER_IN' => sizeit($other_in),
					'OTHER_OUT' => sizeit($other_out),
					'ALL_IN' => sizeit($all_in),
					'ALL_OUT' => sizeit($all_out),
					'ALL' => sizeit($all_in + $all_out)
				)
			);
			$all[0] = $all[0] + $web_in;
			$all[1] = $all[1] + $web_out;
			$all[2] = $all[2] + $smtp_in;
			$all[3] = $all[3] + $smtp_out;
			$all[4] = $all[4] + $pop_in;
			$all[5] = $all[5] + $pop_out;
			$all[6] = $all[6] + $all_in;
			$all[7] = $all[7] + $all_out;

		} // if count
	} // end for
	if (!$has_data) {
		$tpl->assign('DAY_LIST', '');
	}

	$all_other_in = $all[6] - ($all[0] + $all[2] + $all[4]);
	$all_other_out = $all[7] - ($all[1] + $all[3] + $all[5]);

	$tpl->assign(
		array(
			'WEB_IN_ALL' => sizeit($all[0]),
			'WEB_OUT_ALL' => sizeit($all[1]),
			'SMTP_IN_ALL' => sizeit($all[2]),
			'SMTP_OUT_ALL' => sizeit($all[3]),
			'POP_IN_ALL' => sizeit($all[4]),
			'POP_OUT_ALL' => sizeit($all[5]),
			'OTHER_IN_ALL' => sizeit($all_other_in),
			'OTHER_OUT_ALL' => sizeit($all_other_out),
			'ALL_IN_ALL' => sizeit($all[6]),
			'ALL_OUT_ALL' => sizeit($all[7]),
			'ALL_ALL' => sizeit($all[6] + $all[7])
		)
	);
}
?>