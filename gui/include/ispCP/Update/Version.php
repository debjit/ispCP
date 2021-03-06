<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2011 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category	ispCP
 * @package		ispCP_Update
 * @copyright 	2006-2011 by ispCP | http://isp-control.net
 * @author 		ispCP Team
 * @version 	SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class ispCP_Update_Version implements the ispCP_Update abstract class for
 * future online version update functions
 *
 * @package		ispCP_Update
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0.1
 * @since		r1355
 */
class ispCP_Update_Version extends ispCP_Update {

	/**
	 * ispCP_Update_Version instance
	 *
	 * @var ispCP_Update_Version
	 */
	protected static $_instance = null;

	/**
	 * Database variable name for the update version
	 *
	 * @var string
	 */
	protected $_databaseVariableName = 'VERSION_UPDATE';

	/**
	 * Error message string
	 *
	 * @var string
	 */
	protected $_errorMessage = 'Version update %s failed';

	/**
	 * Gets a ispCP_Update_Version instance
	 *
	 * @return ispCP_Update_Version
	 */
	public static function getInstance() {

		if (is_null(self::$_instance)) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Return the current ispCP installed version
	 *
	 * @return int Current ispCP installed version
	 */
	protected function _getCurrentVersion() {

		$cfg = ispCP_Registry::get('Config');

		return (int) $cfg->BuildDate;
	}

	/**
	 * Gets the last available ispCP version
	 *
	 * @return bool|int Returns the last ispCP version available or FALSE on
	 * failure
	 * @todo Rename this function name that don't reflects the real purpose
	 */
	protected function _getNextVersion() {

		$last_update = "http://www.isp-control.net/latest.txt";
		ini_set('user_agent', 'Mozilla/5.0');
		$timeout = 2;
		$old_timeout = ini_set('default_socket_timeout', $timeout);
		$dh2 = @fopen($last_update, 'r');
		ini_set('default_socket_timeout', $old_timeout);

		if (!is_resource($dh2)) {
			$this->_addErrorMessage(
				tr("Couldn't check for updates! Website not reachable."),
				'error'
			);

			return false;
		}

		$last_update_result = (int) fread($dh2, 8);
		fclose($dh2);

		return $last_update_result;
	}

	/**
	 * Check for ispCP update
	 *
	 * @return boolean TRUE if a new ispCP version is available FALSE otherwise
	 * @todo Rename this function name that don't reflects the real purpose
	 */
	public function checkUpdateExists() {

		return ($this->_getNextVersion() > $this->_currentVersion)
			? true : false;
	}

	/**
	 * Should be documented
	 *
	 * @param  $version
	 * @return string
	 */
	protected function _returnFunctionName($version) {

		return 'dummyFunctionThatAllwaysExists';
	}

	/**
	 * Should be documented
	 *
	 * @param  $engine_run_request
	 * @return void
	 */
	protected function dummyFunctionThatAllwaysExists(&$engine_run_request) {
		// uncomment when engine part will be ready
		/*
		$dbConfig = ispCP_Registry::get(DbConfig);
		$dbConfig->VERSION_UPDATE = $this->getNextVersion();
		$engine_run_request = true;
		 */
	}
}
