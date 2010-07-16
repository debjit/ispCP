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
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category	ispCP
 * @package		ispCP_NetworkCard
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @author 		ispCP Team
 * @version 	SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class Network Card
 *
 * @category	ispCP
 * @package		ispCP_NetworkCard
 * @author 		ispCP Team
 */
class ispCP_NetworkCard {

	/**
	 * Should be documented
	 *
	 * @var array
	 */
	protected $_interfacesInfo = array();

	/**
	 * Should be documented
	 *
	 * @var array
	 */
	protected $_interfaces = array();

	/**
	 * Should be documented
	 *
	 * array
	 */
	protected $_offlineInterfaces = array();

	/**
	 * Should be documented
	 *
	 * @var array
	 */
	protected $_virtualInterfaces = array();

	/**
	 * Should be documented
	 *
	 * @var array
	 */
	protected $_availableInterfaces = array();

	/**
	 * Should be documented
	 *
	 * @var array
	 */
	protected $_errors = '';

	/**
	 * Should be documented
	 *
	 * @return void
	 */
	public function __construct() {

		$this->_getInterface();
		$this->_populateInterfaces();
	}

	/**
	 * Should be documented
	 *
	 * @param  $filename
	 * @return string
	 */
	public function read($filename) {

		if (($result = @file_get_contents($filename)) === false) {
			$this->_errors .= sprintf(tr('File %s does not exists or cannot be reached!'), $filename);
			return '';
		}

		return $result;

	}

	/**
	 * Should be documented
	 *
	 * @return string
	 */
	public function network() {

		$file = $this->read('/proc/net/dev');
		preg_match_all('/(.+):.+/', $file, $dev_name);

		return $dev_name[1];
	}

	/**
	 * Should be documented
	 *
	 * @return void
	 */
	private function _getInterface() {

		foreach ($this->network() as $key => $value) {
			$this->_interfaces[] = trim($value);
		}
	}

	/**
	 * Should be documented
	 *
	 * @param  string $strProgram
	 * @param  string &$strError
	 * @return bool|string
	 */
	protected function executeExternal($strProgram, &$strError) {

		$strBuffer = '';

		$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);

		$pipes = array();
		$process = proc_open($strProgram, $descriptorspec, $pipes);

		if (is_resource($process)) {
			while (!feof($pipes[1])) {
				$strBuffer .= fgets($pipes[1], 1024);
			}

			fclose($pipes[1]);

			while (!feof($pipes[2])) {
				$strError .= fgets($pipes[2], 1024);
			}

			fclose($pipes[2]);
		}

		$return_value = proc_close($process);
		$strError = trim($strError);
		$strBuffer = trim($strBuffer);

		if (!empty($strError) || $return_value != 0) {
			$strError .= "\nReturn value: " . $return_value;
			return false;
		}

		return $strBuffer;
	}

	/**
	 * Should be documented
	 *
	 * @return bool
	 */
	private function _populateInterfaces() {

		$err = '';
		$message = $this->executeExternal(ispCP_Config::getInstance()->Get('CMD_IFCONFIG'), $err);

		if (!$message) {
			$this->_errors .= tr('Error while trying to obtain list of network cards!') . $err;

			return false;
		}

		preg_match_all("/(?isU)([^ ]{1,}) {1,}.+(?:(?:\n\n)|$)/", $message, $this->_interfacesInfo);

		foreach ($this->_interfacesInfo[0] as $a) {
			if (preg_match("/inet addr\:([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/",$a,$b)) {
				$this->_interfacesInfo[2][] = trim($b[1]);
			} else {
				$this->_interfacesInfo[2][] = '';
			}
		}

		$this->_offlineInterfaces =
			array_diff($this->_interfaces, $this->_interfacesInfo[1]);

		$this->_virtualInterfaces =
			array_diff($this->_interfacesInfo[1], $this->_interfaces);

		$this->_availableInterfaces = array_diff(
			$this->_interfaces,
			$this->_offlineInterfaces,
			$this->_virtualInterfaces,
			array('lo')
		);
	}

	/**
	 * Should be documented
	 *
	 * @return array
	 */
	public function getAvailableInterface() {

		return $this->_availableInterfaces;
	}

	/**
	 * Should be documented
	 *
	 * @return string
	 */
	public function getErrors() {

		return nl2br($this->_errors);
	}

	/**
	 * Should be documented
	 *
	 * @param  string $ip
	 * @return array
	 */
	public function ip2NetworkCard($ip) {

		$key = array_search($ip,$this->_interfacesInfo[2]);

		if ($key === false) {
			$this->_errors .= sprintf(
				tr("This IP (%s) is not assigned to any network card!"), $ip
			);
		} else {
			return $this->_interfacesInfo[1][$key];
		}
	}
}
