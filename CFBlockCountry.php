<?php
/**
 * @version		cfBlockCountry.php 1.3-mod
 * @package		Joomla
 * @copyright	Copyright (C) 2018
 * The code has been written by www.CodeFire.in in case of any questions please contact joomla@codefire.in
 * This a a modified version with added whitelist and new a option to allow or deny country codes which
 * can be selected in plugin options in backend. Also "external" geoip is disabled since url is offline.
 * @license		GNU/GPL, see LICENSE.php
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * @package		Joomla
 * @subpackage	System
 */
class plgSystemCFBlockCountry extends JPlugin {
	function onAfterDispatch() {

		//global $mainframe;
		$app =& JFactory::getApplication();
		if ( !$app->isSite() ) return;

		$plugin =& JPluginHelper::getPlugin( 'system', 'CFBlockCountry' );
		//$params = new JParameter( $plugin->params );

		$countryCodes			= $this->params->get( 'country', '' );
		$action					= $this->params->get( 'action', '' );
		$wblist					= $this->params->get( 'wblist', 0 );
		$ipfile					= $this->params->get( 'ipfile', '');
		// external does not work anymore 
		//$external				 = $this->params->get( 'external', 0 );
		$textMsgForBlocked		= $this->params->get( 'blockedText', '' );
		$option					= $this->params->get( 'show_msg', 0 );
		$site					= $this->params->get( 'site', '' );
		$log					= $this->params->get( 'log', '' );
		$country = '';
		$debug = 0;

		$blocked = function($reason, $match) use ($option, $textMsgForBlocked, $site, $log, $log_file) {
			if (isset($log) && $log != '') {
				$data = @date('Y-m-d H:i:s') . " " . str_pad($reason . ":", 11) . $_SERVER['REMOTE_ADDR'] . " ($match)";
				file_put_contents(JPATH_ROOT . $log, $data . PHP_EOL, FILE_APPEND);
			}
			if($option == 0) {
				echo $textMsgForBlocked;
				exit;
			} else {
				if(isset($site) && $site != '') {
					header("Location: $site");
					exit;
				}
			}
		};

		// BEGIN: whitelist blacklist mod, v0.1 2014-06-16
		if ($wblist == 1) {
			if (strpos($_SERVER['REMOTE_ADDR'], ":") === false) {
				function check_ip($low_ip, $high_ip) {
					$l_ip = ip2long("$low_ip");
					$h_ip = ip2long("$high_ip");
					$ip = ip2long($_SERVER['REMOTE_ADDR']);
					return ($ip <= $h_ip && $l_ip <= $ip);
				}

				$iplist = require($ipfile);

				foreach($iplist['whitelist'] as $key=>$val) {
					if(isset($val)) {
						list($low_ip, $high_ip) = explode(" ", $val);
						if (check_ip($low_ip, $high_ip)) { 
							//if (isset($log) && $log != '') {
							//	$data = @date('Y-m-d H:i:s'). " whitelist: " . $_SERVER['REMOTE_ADDR'] . " ($low_ip $high_ip)";
							//	file_put_contents(JPATH_ROOT . $log, $data . PHP_EOL, FILE_APPEND);
							//}
							return;
						}
					}
				}

				foreach($iplist['blacklist'] as $key=>$val) {
					if(isset($val)) {
						list($low_ip, $high_ip) = explode(" ", $val);
						if (check_ip($low_ip, $high_ip)) {
							$blocked("blacklist",  "$low_ip $high_ip");
						}
					}
				}
			}
		}
		// END: whitelist blacklist mod

		/* disabled: external        
		   if(!$external){        
		   $country = $this->getCountryCodeByIP();
		   $country = $country['code'];
		   }
		   else {
		   $country = $this->externalCall();
		   }
		 */

		/* replaced: see $blocked above and allow/deny code below
		   $block = explode(',', $blockedCountryCodes);
		   foreach($block as $key => $val){
		   if(isset($val) && trim($val) != '' && $country == trim($val) ){
		   if($option == 0)
		   {
		   echo $textMsgForBlocked;
		   exit;
		   }else{
		   if(isset($site) && $site != '')
		   header("Location: $site");
		   exit;
		   }
		   }
		   }
		 */

		$country = $this->getCountryCodeByIP();
		$country = $country['code'];
		$ccodes = explode(',', $countryCodes);
		if($action == 'allow') {
			foreach($ccodes as $key => $val) {
				if(isset($val) && trim($val) != '' && $country != trim($val) ) {
					$blocked("cc_allow", "$country");
				} 
			}
		} else {
			foreach($ccodes as $key => $val) {
				if(isset($val) && trim($val) != '' && $country == trim($val) ) {
					$blocked("cc_deny", "$country");
				}
			}
		}

	}

	function getCountryCodeByIP() {
		require(JPATH_LIBRARIES.'/geoip/geoip.inc');

		/* replaced by ipv6 code below
		   $gi = geoip_open(JPATH_LIBRARIES."/geoip/GeoIP.dat",GEOIP_STANDARD);
		   $country = array();	

		   $country['code'] = geoip_country_code_by_addr($gi, $_SERVER['REMOTE_ADDR']);
		   $country['name'] = geoip_country_name_by_addr($gi, $_SERVER['REMOTE_ADDR']);

		// test: $db = (strpos($_SERVER['REMOTE_ADDR'], ":") === false) ? "/geoip/GeoIP.dat" : "/geoip/GeoIPv6.dat";
		 */

		if (strpos($_SERVER['REMOTE_ADDR'], ":") === false) {
			$gi = geoip_open(JPATH_LIBRARIES."/geoip/GeoIP.dat",GEOIP_STANDARD);
			$country = array();	
			$country['code'] = geoip_country_code_by_addr($gi, $_SERVER['REMOTE_ADDR']);
			$country['name'] = geoip_country_name_by_addr($gi, $_SERVER['REMOTE_ADDR']);
		} else {
			$gi = geoip_open(JPATH_LIBRARIES."/geoip/GeoIPv6.dat",GEOIP_STANDARD);
			$country = array();	
			$country['code'] = geoip_country_code_by_addr_v6($gi, $_SERVER['REMOTE_ADDR']);
			$country['name'] = geoip_country_name_by_addr_v6($gi, $_SERVER['REMOTE_ADDR']);
		}

		geoip_close($gi);

		if($debug == 1) {
			if (isset($log) && $log != '') {
				$data = @date('Y-m-d H:i:s') . " DEBUG: " . $_SERVER['REMOTE_ADDR'] . " " . $country['code'] . " " . $country['name'];
				file_put_contents(JPATH_ROOT . $log, $data . PHP_EOL, FILE_APPEND);
			}
		}

		return $country;
	}

	// external does not work anymore, url unreachable (code not used anymore)
	function externalCall() {
		define('POSTURL', 'http://joomla.codefire.in/apps/geoip/index.php');
		define('POSTVARS', 'ip=');  
		$ip =  $_SERVER['REMOTE_ADDR'];
		$ch = curl_init(POSTURL);
		curl_setopt($ch, CURLOPT_POST ,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS    ,POSTVARS.$ip);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
		curl_setopt($ch, CURLOPT_HEADER      ,0);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

}
/* vim: set noai tabstop=4 shiftwidth=4 softtabstop=4 noexpandtab: */