<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaapps
 *
 * @copyright   Copyright (C) 2013 Efthimios Mavrogeorgiadis. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Support for JoomlaApps download after registration functionality
 *
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaapps
 * @since       3.1
 */
class PlgSystemJoomlaapps extends JPlugin
{
	var $_joomlaapps = array();
	var $_sessionvals = array();
	
	private function getJoomlaapps()
	{
		if (!count($this->_joomlaapps))
		{
			$app = JFactory::getApplication();
			$this->_joomlaapps = array(
				'installat'	=>	base64_decode(JRequest::getVar('installat', null, 'POST')),
				'installapp'	=>	JRequest::getInt('installapp', null, 'POST'),
				'timestamp'	=>	time(),
				'product'	=>	addslashes(base64_decode(JRequest::getCmd('product', '', 'POST'))),
				'release'	=>	preg_replace('/[^\d\.]/', '', base64_decode(JRequest::getCmd('release', '', 'POST'))),
				'dev_level'	=>	(int) base64_decode(JRequest::getCmd('dev_level', '', 'POST')),
			);
		}
		//print_r($_POST); die;
		return $this->_joomlaapps;
	}
	
	private function getSessionValues()
	{
		if (!$this->_sessionvals)
		{
			$session = JFactory::getSession();
			$joomlaapps = $this->getJoomlaapps();
			foreach ($joomlaapps as $key => $value)
			{
				$this->_sessionvals[$key] = $session->get('joomlaapps.' . $key, null);
			}
		}
		print_r($this->_sessionvals);
		return $this->_sessionvals;
	}
	
	private function setSessionValues($null = false)
	{
		$session = JFactory::getSession();
		$joomlaapps = $this->getJoomlaapps();
		foreach ($joomlaapps as $key => $value)
		{
			if ($null)
			{
				$value = null;
			}
			$session->set('joomlaapps.' . $key, $value);
		}
	}
	
	private function setSessionValuesNull()
	{
		$this->setSessionValues(true);
	}
	
	private function isDataOK()
	{
		$session = JFactory::getSession();
		$joomlaapps = $this->getJoomlaapps();
		
		return $this->validateURL($joomlaapps['installat']) && intval($joomlaapps['installapp']);
	}
	
	private function validateURL($url=null) {
		if (!$url) return false;
		
		if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url)) {
		  return true;
		}
		
		return false;
	}
	
	private function isSessionOK()
	{
		$joomlaapps = $this->getSessionValues();
		return $joomlaapps['timestamp'] &&
			time() < $joomlaapps['timestamp'] + 18 * 60 * 60 &&
			!is_null($joomlaapps['installat']) &&
			!is_null($joomlaapps['installapp']);
	}
	
	private function getInstallFrom() {
		$joomlaapps = $this->getSessionValues();
		$this->setSessionValuesNull();
		$files = $this->params->get('files', null);
		$files = preg_replace('/\s*=\s*>\s*/', '=>', $files);
		$files = preg_split('/\s+/', $files);
		$installfrom = '';
		foreach ($files as $f) {
			if (preg_match('/^'.$joomlaapps['installapp'].'=>(.+)/', trim($f), $matches)) {
				$installfrom = '&installfrom='.base64_encode($matches[1]);
			}
		}
		return array($joomlaapps, $installfrom);
	}
	
	public function onAfterInitialise()
	{
		if ($this->isDataOK())
		{
			$this->setSessionValues();
			if(JFactory::getUser()->id && $this->isSessionOK())
			{
				$array = $this->getInstallFrom();
				$joomlaapps = $array[0];
				$installfrom = $array[1];
				$app = JFactory::getApplication();
				$app->redirect($joomlaapps['installat'].$installfrom);
			}
			//$app = JFactory::getApplication();
			//$app->redirect(JRoute::_('index.php?option=com_user&view=login'));
		}
	}
	
	public function onUserLogin()
	{
		if ($this->isSessionOK()) {
			$array = $this->getInstallFrom();
			$joomlaapps = $array[0];
			$installfrom = $array[1];
			$app = JFactory::getApplication();
			$app->setUserState('users.login.form.return', $joomlaapps['installat'].$installfrom);
		}
	}
}
