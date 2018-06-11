<?php
/**
* @version		cfBlockCountry.php 1.1
* @package		Joomla
* @copyright	Copyright (C) 2018
* The code has been written by www.CodeFire.in in case of any questions please contact joomla@codefire.in
* This a a modified version with added whitelist (see below) and new option to allow or deny country codes
* which can be selected in plugin options in backend. Also "external" geoip is disabled since url is offline.
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
		$action              	= $this->params->get( 'action', 'deny' );
		$wblist              	= $this->params->get( 'wblist', 0 );
		$ipfile					= $this->params->get( 'ipfile', 'CFBlockCountryIPList.php');
		$textMsgForBlocked      = $this->params->get( 'blockedText', '' );
		$option                 = $this->params->get( 'show_msg', 0 );
		$site                   = $this->params->get( 'site', '' );
		// external does not work anymore 
		//$external               = $this->params->get( 'external', 0 );
		$country = '';
        
		$blocked = function() use ($option, $textMsgForBlocked, $site) {
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
    						return;
    					}
    				}
    			}

    			foreach($iplist['blacklist'] as $key=>$val) {
    				if(isset($val)) {
    					list($low_ip, $high_ip) = explode(" ", $val);
    					if (check_ip($low_ip, $high_ip)) {
    						$blocked();
    					}
    				}
    			}
			}
		}
		// END: whitelist blacklist mod
	        
		$country = $this->getCountryCodeByIP();
    	$country = $country['code'];
    	$ccodes = explode(',', $countryCodes);
    	if($action == 'allow') {
    		foreach($ccodes as $key => $val) {
    			if(isset($val) && trim($val) != '' && $country != trim($val) ) {
    				$blocked();
    			} 
    		}
    	} else {
    		foreach($ccodes as $key => $val) {
    			if(isset($val) && trim($val) != '' && $country == trim($val) ) {
    				$blocked();
    			}
    		}
    	}

	}

	function getCountryCodeByIP() {
		require(JPATH_LIBRARIES.'/geoip/geoip.inc');

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

		return $country;
	}

}
