<?php
/**
 * @version		cfBlockCountry.php 1.4-mod
 * @package		Joomla
 * @copyright	Copyright (C) 2018
 * The code has been written by www.CodeFire.in in case of any questions please contact joomla@codefire.in
 * This a a modified version with added whitelist and new a option to allow or deny country codes which
 * can be selected in plugin options in backend. Also "external" geoip is disabled since url is offline.
 * @license		GNU/GPL, see LICENSE.php
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

$geoip2_phar = JPATH_LIBRARIES . '/geoip/geoip2.phar';
if(file_exists($geoip2_phar)) {
	require_once($geoip2_phar);
}
use GeoIp2\Database\Reader;

/**
 * @package		Joomla
 * @subpackage	System
 */
class plgSystemCFBlockCountry extends JPlugin {
  	function onAfterDispatch() {

   		//global $mainframe;
   		//$params = new JParameter( $plugin->params );
   		//if ( !$app->isSite() ) return;

   		$plugin =& JPluginHelper::getPlugin( 'system', 'CFBlockCountry' );
   		$app =& JFactory::getApplication();
   		$layer = $this->params->get( 'layer', '' );
   		if ($app->isSite()) { $scope = 'site'; } elseif ($app->isAdmin()) { $scope = 'admin'; }
   		if ( $layer == 'frontend' && $scope != 'site' ) return;
   		if ( $layer == 'backend' && $scope != 'admin' ) return;

		global $geoip_version;

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
		$geoip_version			= $this->params->get( 'geoip_version', 1 );

		global $debug;
		$debug = 0;
		
		global $valid_ip;
		$valid_ip = $this->get_ip_address();
		$country = '';

		$blocked = function($reason, $match) use ($scope, $valid_ip, $option, $textMsgForBlocked, $site, $log) {
			if (isset($log) && $log != '') {
				$data = @date('Y-m-d H:i:s') . " " . str_pad($scope, 6) . str_pad($reason . ":", 11) . $valid_ip . " ($match)";
				file_put_contents(JPATH_ROOT . $log, $data . PHP_EOL, FILE_APPEND);
			}
			if ($option == 0) {
				echo $textMsgForBlocked;
				exit;
			} else {
				if (isset($site) && $site != '') {
					header("Location: $site");
					exit;
				}
			}
		};

		// BEGIN: whitelist blacklist mod, v0.1 2014-06-16
		if ($wblist == 1) {
			if (strpos($valid_ip, ":") === false) {
				function check_ip($low_ip, $high_ip) {
					global $valid_ip;
					if ((!isset($valid_ip)) || (empty($valid_ip)) || (is_null($valid_ip))) {
						$valid_ip = $this->get_ip_address();
					}
					$ip = ip2long($valid_ip);
					$lo_ip = ip2long("$low_ip");
					$hi_ip = ip2long("$high_ip");
					return ($ip <= $hi_ip && $lo_ip <= $ip);
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

		/* 
		 * Disabled: external
 		 *
		   if(!$external){        
			   $country = $this->getCountryCodeByIP();
			   $country = $country['code'];
		   }
		   else {
			   $country = $this->externalCall();
		   }
		*/

		/*
		 * Replaced: see $blocked above and allow/deny code below
		 *
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
		global $geoip_version;
		global $valid_ip;
		if ((!isset($valid_ip)) || (empty($valid_ip)) || (is_null($valid_ip))) {
			$valid_ip = $this->get_ip_address();
		}
		$country = array();	
		
		// GeoIP legacy
		if ($geoip_version == 1) {
			require(JPATH_LIBRARIES.'/geoip/geoip.inc');
			/*
			 * Replaced by ipv6 aware code block below which also uses GeoIPv6.dat
			 *
	  		   $gi = geoip_open(JPATH_LIBRARIES."/geoip/GeoIP.dat",GEOIP_STANDARD);
	  		   $country = array();	

	  		   $country['code'] = geoip_country_code_by_addr($gi, $_SERVER['REMOTE_ADDR']);
	  		   $country['name'] = geoip_country_name_by_addr($gi, $_SERVER['REMOTE_ADDR']);

	  		// test: $db = (strpos($_SERVER['REMOTE_ADDR'], ":") === false) ? "/geoip/GeoIP.dat" : "/geoip/GeoIPv6.dat";
			*/
	  		if (strpos($valid_ip, ":") === false) {
	  			$gi = geoip_open(JPATH_LIBRARIES."/geoip/GeoIP.dat",GEOIP_STANDARD);
	  			$country['code'] = geoip_country_code_by_addr($gi, $valid_ip);
	  			$country['name'] = geoip_country_name_by_addr($gi, $valid_ip);
	  		} else {
	  			$gi = geoip_open(JPATH_LIBRARIES."/geoip/GeoIPv6.dat",GEOIP_STANDARD);
	  			$country['code'] = geoip_country_code_by_addr_v6($gi, $valid_ip);
	  			$country['name'] = geoip_country_name_by_addr_v6($gi, $valid_ip);
	  		}
	  		geoip_close($gi);

		// GeoIP2
		} elseif ($geoip_version == 2) {
			$reader = new Reader(JPATH_LIBRARIES . '/geoip/GeoLite2-Country.mmdb');
			$record = $reader->country($valid_ip);
			$country = array();	
			$country['code'] = $record->country->isoCode;
			$country['name'] = $record->country->name;
		}

		global $debug;
  		if($debug == 1) {
			$log = $this->params->get( 'log', '' );
  			if (isset($log) && $log != '') {
  				$data = @date('Y-m-d H:i:s') . " DEBUG: " . $valid_ip . " " . $country['code'] . " " . $country['name'];
  				file_put_contents(JPATH_ROOT . $log, $data . PHP_EOL, FILE_APPEND);
  			}
  		}

  		return $country;
  	}
  	
  	/*
	 * External does not work anymore, url is inreachable
	 * The code block below is no longer used
	 *
  	function externalCall() {
  		define('POSTURL', 'http://joomla.codefire.in/apps/geoip/index.php');
  		define('POSTVARS', 'ip=');  
  		$ip = $_SERVER['REMOTE_ADDR'];
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
  	*/

    /*
	 * Get valid IP address
	 * https://gist.github.com/cballou/2201933
    */
    function get_ip_address() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR', 'HTTP_X_REAL_IP');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if ($this->validate_ip($ip)) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }
    /*
	 * Ensures an ip address is both a valid IP and does not fall within
	 * a private network range.
    */
    function validate_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    }

}
/* vim: set noai tabstop=4 shiftwidth=4 softtabstop=4 noexpandtab: */	