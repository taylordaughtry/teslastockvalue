<?php

namespace Craft;

use CURLFile;

class Lamplighter_ApiService extends BaseApplicationComponent
{

	/**
	 * The add-on API URL.
	 * @var string
	 */
	private $apiUrl = 'https://app.lamplighter.io/api';


	/**
	 * Register the user's site token with Lamplighter.
	 * @param string $apiKey
	 * @param array $settings
	 * @return array
	 */
	public function registerKey($apiKey, $settings)
	{
		$apiKey        = explode(':', $apiKey);
		$actionTrigger = craft()->config->get('actionTrigger');
		$apiUrl        = craft()->request->getHostInfo().'/'.$actionTrigger.'/lamplighter/api/request';
		if (!isset($apiKey[1])) {
			return 0;
		}
		$registerUrl   = $this->apiUrl.'/register/' . $apiKey[0];
		$data          = array('api_key' => $apiKey[1], 'api_url' => $apiUrl);
		$return        = json_decode($this->_curl_request($registerUrl, $data));

		return isset($return->status) ? array('status' => $return->status, 'message' => $return->message) : array('status' => 'error', 'message' => 'Unable to process the response from Lamplighter app.');
	}


	/**
	 * Remove the user's site token to unregister the site.
	 * @param array $settings
	 * @return integer
	 */
	public function unregisterKey($settings)
	{
		$apiKey = explode(':', $settings['apiKey']);
		if (!isset($apiKey[1])) {
			return 0;
		}
		$apiUrl = $this->apiUrl . '/unregister/' . $apiKey[0];
		$data   = array('api_key' => $apiKey[1]);
		$return = json_decode($this->_curl_request($apiUrl, $data));
		return isset($return->status) && $return->status == 'success' ? 1 : 0;
	}


	/**
	 * Send the site's info to Lamplighter.
	 * @param array $settings
	 * @return integer
	 */
	public function sendInfo($settings)
	{
		$info = array(
			'addons' => $this->getPlugins(),
			'phpinfo' => $this->getPHPInfo(),
			'version' => $this->getCraftVersion(),
		);
		$apiKey = explode(':', $settings['apiKey']);
		if (!isset($apiKey[1])) return 0;
		$apiUrl = $this->apiUrl.'/send/'.$apiKey[0].'?api_key='.$apiKey[1];
		$return = json_decode($this->_curl_request($apiUrl, $info));
		return isset($return->status) && $return->status == 'success' ? 1 : 0;
	}


	/**
	 * Retrieve Craft's version.
	 * @return string
	 */
	public function getCraftVersion()
	{
		return craft()->getVersion() .'.'. craft()->getBuild();
	}


	/**
	 * Get our plug-in list.
	 * @return string
	 */
	public function getPlugins()
	{
		$plugins = craft()->plugins->getPlugins(false);
		$responseData = array();
		foreach ($plugins AS $pluginName => $pluginData) {
			$pluginInfo = craft()->plugins->getPluginInfo($pluginData);
			$responseData[$pluginName]['info'] = $pluginInfo;
			$responseData[$pluginName]['obj'] = $pluginData;
		}
		return json_encode($responseData);
	}


	/**
	 * Create a back-up using Craft's db backup.
	 * @param array $settings
	 * @return integer
	 */
	public function getBackup($settings)
	{
		// Create a back-up in craft/storage (.sql)
		$file = craft()->db->backup();

		// Make sure back-up exists.
		if (IOHelper::fileExists($file)) {

			// Get the file name, without a .sql, plus a .zip
			$destZip = craft()->path->getTempPath().IOHelper::getFileName($file, false).'.zip';

			// Check if the file already exists -- if it does, delete it.
			if (IOHelper::fileExists($destZip)) {
				IOHelper::deleteFile($destZip, true);
			}

			// Create the .zip file
			IOHelper::createFile($destZip);

			// Add the contents of the .sql file to the .zip
			if (Zip::add($destZip, $file, craft()->path->getDbBackupPath())) {
				// Assuming everything went well, send backup
				return $this->sendBackup($destZip, $settings);
			}

		}

		// Something went wrong.  Return 0 (status => error)
		return 0;
	}


	/**
	 * Send this back-up to Lamplighter.
	 * @param string $file
	 * @param array $settings
	 * @return integer
	 */
	public function sendBackup($file, $settings)
	{
		// Make sure this file actually exists.
		if (!IOHelper::fileExists($file)) {
			return 0;
		}

		// Get the filename from the full file name / path
		$filename = (IOHelper::getFileName($file, true));

		// Generate our API url
		$apiKey = explode(':', $settings['apiKey']);
		if (!isset($apiKey[1])) return 0;
		$apiUrl = $this->apiUrl.'/backup/'.$apiKey[0].'?api_key='.$apiKey[1];

		$args = array('rand' => rand(), 'backup' => '@' . $file .';filename='.$filename);

		if (class_exists('CURLFile')) {
			$args['backup'] = new CURLFile($file, 'application/zip', $filename);
		}

		// Create our cURL request
		$curl = $this->_curl_request($apiUrl, $args, 1);

		// Decode our return
		$return = json_decode($curl);

		// Return the status
		return $return->status == 'success' ? 1 : 0;
	}


	/**
	 * Get our PHP/MySQL/Server information.
	 * @return string
	 */
	function getPHPInfo() {
		$row = craft()->db->createCommand('SELECT VERSION() AS version')->queryRow();
		$tables = craft()->db->createCommand('SHOW TABLE STATUS')->queryAll();
		$dbSize = 0;
		foreach ($tables AS $table) {
			$dbSize += (integer) $table['Data_length'] + (integer) $table['Index_length'];
		}

		ob_start();
		phpinfo(-1);

		$pi = preg_replace(
		array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
		'#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
		"#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
		'#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
		.'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
		'#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
		'#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
		"# +#", '#<tr>#', '#</tr>#'),
		array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
		'<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
		"\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
		'<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
		'<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
		'<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
		ob_get_clean());

		$sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
		unset($sections[0]);

		$pi = array();
		foreach($sections as $section){
			$n = substr($section, 0, strpos($section, '</h2>'));
			preg_match_all(
			'#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
			 $section, $askapache, PREG_SET_ORDER);
			foreach($askapache as $m) {
					if (!isset($m[2]))
						continue;
				$pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
			}
		}

		$return = array();

		if (isset($pi['mysql'])) {
			$return['mysql'] = $pi['mysql'];
		} else if (isset($pi['mysqli'])) {
			$return['mysql'] = $pi['mysqli'];
		} else {
			$return['mysql'] = '';
		}

		$return['php']            = isset($pi['PHP Configuration']) ? $pi['PHP Configuration'] : '';
		$return['core']           = isset($pi['Core']) ? $pi['Core'] : '';
		$return['apache']         = isset($pi['apache2handler']) ? $pi['apache2handler'] : '';
		$return['server']         = isset($_SERVER["SERVER_SOFTWARE"]) ? $_SERVER["SERVER_SOFTWARE"] : '';
		$return['os_name']        = function_exists('php_uname') ? php_uname('s') : '';
		$return['os_version']     = function_exists('php_uname') ? php_uname('r') : '';
		$return['getrusage']      = function_exists('getrusage') ? getrusage() : '';
		$return['temp_dir']       = function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : '';
		$return['system_load']    = function_exists('sys_getloadavg') ? sys_getloadavg() : array();
		$return['mysql_version']  = $row['version'];
		$return['apache_version'] = function_exists('apache_get_version') ? apache_get_version() : '';
		$return['php_version']    = phpversion();
		$return['db_size']        = $dbSize;
		return json_encode($return);
	}


	/**
	 * POST a cURL request w/ (array) $data as POSTed fields.
	 * @param string $apiUrl
	 * @param mixed $data
	 * @param mixed $file
	 */
	public function _curl_request($apiUrl, $data, $file = 0)
	{
		$ch = curl_init();
		$fields_string = '';
		if (!$file) {
			foreach($data as $key => $value) {
				$fields_string .= $key .'='.urlencode($value).'&';
			}
		}
		rtrim($fields_string, '&');
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
		curl_setopt($ch, CURLOPT_POST, count($data));
		if ($file) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} else {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		}
		$curl = curl_exec($ch);
		return $curl;
	}

}
