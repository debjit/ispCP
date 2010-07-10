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
 * @package		ispCP_Exception
 * @subpackage	Writer
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * @see ispCP_Exception_Writer
 */
require_once  INCLUDEPATH . '/ispCP/Exception/Writer.php';

/**
 * Exception Mail writer
 *
 * This writer writes a mail that contain the exception messages and some debug
 * backtrace information.
 *
 * <b>Note:</b> Will be improved later.
 *
 * @category	ispCP
 * @package		ispCP_Exception
 * @subpackage	Writer
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.3
 * @todo		Clean expired mails body footprint
 */
class ispCP_Exception_Writer_Mail extends ispCP_Exception_Writer {

	/**
	 * Exception Writer name
	 *
	 * @var string
	 */
	const NAME = 'ispCP Exception Mail Writer';

	/**
	 * Mail recipient
	 *
	 * @var string
	 */
	protected $_to = '';

	/**
	 * Mail Header
	 *
	 * @var string
	 */
	protected $_header = '';

	/**
	 * Mail subject
	 *
	 * @var string
	 */
	protected $_subject = '';

	/**
	 * Mail body
	 *
	 * @var string
	 */
	protected $_body = '';

	/**
	 * Mail body footprint
	 * @var md5
	 */
	protected $_footPrint = '';

	/**
	 * Constructor - Create a new mail writer object
	 *
	 * @throws ispCP_Exception
	 * @param string $to An valid adresse email
	 * @return void
	 */
	public function __construct($to) {

		// filter_var() is only available in PHP >= 5.2
		if(function_exists('filter_var')) {
			$ret = filter_var($to, FILTER_VALIDATE_EMAIL);
		} else {
			$ret = (bool) preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $to);
		}

		if($ret === false) {
			throw new ispCP_Exception(
				'ispCP_Exception_Writer_Mail error: Invalid email address!'
			);
		} else {
			$this->_to = $to;
		}
	}

	/**
	 * This methods is called from the subject (i.e. when an event occur)
	 *
	 * @param ispCP_Exception_Handler $exceptionHandler An ispCP_Exception_Handler
	 * object
	 * @return void
	 */
	public function update(SplSubject $exceptionHandler) {

		$exception = $exceptionHandler->getException();
		$this->_message = $exception->getMessage() . "\n";
		$this->prepareMail($exception);

		if(!$this->isAlreadySent()) {
			$this->_write();
			$this->_cacheMessage();
		}
	}

	/**
	 * Checks if the mail was already sent
	 *
	 * @return boolean TRUE if the message was already sent, FALSE otherwise
	 * @todo Flat file for alternative storage
	 */
	protected function isAlreadySent() {

		if(ispCP_Registry::isRegistered('Db_Config')) {
			$config = ispCP_Registry::get('Db_Config');

			if(isset($config->EXCEPTION_MESSAGES) &&
				is_serialized($config->EXCEPTION_MESSAGES)) {

				$cachedMsg = unserialize($config->EXCEPTION_MESSAGES);

				if(array_key_exists($this->_footPrint, $cachedMsg) &&
					$cachedMsg[$this->_footPrint] > strtotime('now')) {

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Creates the mail body footprint and expiration time
	 *
	 * Both footprint and expiration time are used to avoid multiple sending of
	 * mail for the same exception raised in interval of 12 hours.
	 *
	 * @todo Flat file
	 * @return void
	 */
	protected function _cacheMessage() {

		if(ispCP_Registry::isRegistered('Db_Config')) {
			$dbConfig = ispCP_Registry::get('Db_Config');

			if(isset($dbConfig->EXCEPTION_MESSAGES) &&
				is_serialized($dbConfig->EXCEPTION_MESSAGES)) {

				$cachedMsg = unserialize($dbConfig->EXCEPTION_MESSAGES);
			}

			$cachedMsg[$this->_footPrint] = strtotime('+12 hour');
			$dbConfig->EXCEPTION_MESSAGES = serialize($cachedMsg);
		}
	}

	/**
	 * Writes the mail
	 *
	 * @return boolean TRUE on sucess, FALSE otherwise
	 */
	protected function _write() {

		if(mail($this->_to, $this->_subject, $this->_body, $this->_header)) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare the mail to be send
	 *
	 * @param Exception $exception An exception
	 * @return void
	 */
	protected function prepareMail($exception) {

		// Header
		$this->_header = 'From: "' . self::NAME . "\" <{$this->_to}>\n";
		$this->_header .= "MIME-Version: 1.0\n";
		$this->_header .= "Content-Type: text/plain; charset=utf-8\n";
		$this->_header .= "Content-Transfer-Encoding: 8bit\n";
		$this->_header .= 'X-Mailer: ' . self::NAME . "\n";

		// Subject
		$this->_subject = self::NAME . ' - Exception raised!';

		// Body
		$this->_body ="Dear admin,\n\n";
		$this->_body .=
			'An exception with the following message was raised in file ' .
			$exception->getFile() . ' (Line: ' . $exception->getLine() . "):\n\n";

		$this->_body .="==================================================\n\n";
		$this->_body .= " {$this->_message}\n";
		$this->_body .="==================================================\n\n";

		// Debug Backtrace
		$this->_body .= "Debug backtrace:\n";
		$this->_body .= "---------------\n\n";

		if(count($exception->getTrace()) != 0) {
			foreach ($exception->getTrace() as $trace) {
					if(isset($trace['file'])) {
						$this->_body .=
							"File: {$trace['file']} (Line: {$trace['line']})\n";
					}

					if(isset($trace['class'])) {
						$this->_body .=
							"Method: {$trace['class']}::{$trace['function']}()\n";
					} elseif(isset($trace['function'])) {
						$this->_body .= "Function: {$trace['function']}()\n";
					}
			}
		} else {
			$this->_body .= 'File: ' . $exception->getFile() . ' (Line: ' .
				$exception->getLine() . ")\n";
			$this->_body .= "Function: main()\n";
		}

		// Additional information
		$this->_body .= "\nAdditional information:\n";
		$this->_body .= "----------------------\n\n";

		foreach(array('HTTP_USER_AGENT', 'REQUEST_URI', 'HTTP_REFERER',
			'REMOTE_ADDR', 'SERVER_ADDR') as $key) {

			if(isset($_SERVER[$key]) && $_SERVER[$key] != '' ) {
				$this->_body .=
					ucwords(strtolower(str_replace('_', ' ', $key))) .
						": {$_SERVER["$key"]}\n";
			}
		}

		$this->_body .= "\n______________________________________________\n";
		$this->_body .= self::NAME . "\n";
		$this->_body = wordwrap($this->_body, 70, "\n");

		// Get the mail body footprint
		$this->_footPrint = md5($this->_body);
	}
}
