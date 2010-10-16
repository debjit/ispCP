<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$cfg = ispCP_Registry::get('Config');

$tpl = ispCP_Registry::get('template');
$tpl->assign('PAGE_TITLE', tr('ispCP - Client/Webtools'));
$tpl->assign('PAGE_CONTENT', 'ftp_choose_dir.tpl');


function gen_directories(&$tpl) {

	$sql = ispCP_Registry::get('Db');
	// Initialize variables
	$path = isset($_GET['cur_dir']) ? $_GET['cur_dir'] : '';
	$domain = $_SESSION['user_logged'];
	// Create the virtual file system and open it so it can be used
	$vfs = new ispCP_VirtualFileSystem($domain, $sql);
	// Get the directory listing
	$list = $vfs->ls($path);
	if (!$list) {
		set_page_message(tr('Cannot open directory!<br>Please contact your administrator!'));
		return;
	}
	// Show parent directory link
	$parent = explode('/', $path);
	array_pop($parent);
	$parent = implode('/', $parent);
	$tpl->append('ACTION_LINK', 'no');
	$tpl->append(
		array(
			'ACTION' => '',
			'ICON' => "parent",
			'DIR_NAME' => tr('Parent Directory'),
			'LINK' => 'ftp_choose_dir.php?cur_dir=' . $parent,
		)
	);
	// Show directories only
	foreach ($list as $entry) {
		// Skip non-directory entries
		if ($entry['type'] != ispCP_VirtualFileSystem::VFS_TYPE_DIR) {
			continue;
		}
		// Skip '.' and '..'
		if ($entry['file'] == '.' || $entry['file'] == '..') {
			continue;
		}
		// Check for .htaccess existence to display another icon
		$dr = $path . '/' . $entry['file'];
		$tfile = $dr . '/.htaccess';
		if ($vfs->exists($tfile)) {
			$image = "locked";
		} else {
			$image = "folder";
		}
		// Create the directory link
		$tpl->append(
			array(
				'ACTION' => tr('Protect it'),
				'PROTECT_IT' => "protected_areas_add.php?file=".$dr,
				'ICON' => $image,
				'DIR_NAME' => tohtml($entry['file']),
				'CHOOSE_IT' => $dr,
				'LINK' => "ftp_choose_dir.php?cur_dir=".$dr,
			)
		);
	}
}

// functions end


gen_directories($tpl);

$tpl->assign(
	array(
		'TR_DIRECTORY_TREE' => tr('Directory tree'),
		'TR_DIRS' => tr('Directories'),
		'TR__ACTION' => tr('Action'),
		'CHOOSE' => tr('Choose')
	)
);

gen_page_message($tpl);

$tpl->prnt('ftp_choose_dir.tpl');

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
