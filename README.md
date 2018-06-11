## cfBlockCountry (Joomla Plugin)

The plugin cfBlockCountry can be used to block IP address from certain countries. For example if you want to block access of the site from any IP in United States you can use this plugin.

### Some important points:

1.	It only blocks access to website and not the admin interface so that if you accidently block your own country you can reset the country list
2.	We use free DB from MaxMind (http://www.maxmind.com/app/geolitecountry). As per the MaxMind this DB is 99.5% accurate.
3.	~~~There are 2 options in the plugin,~~~ if you want to use geoip database from local server, you can select the Local option after installing the plugin. But before you select local option please upload geoip folder in the plugin zip file to /libraries/ folder of joomla installation. If this operation is not performed and local option is selected this will cause error on Joomla and you may not be able to access joomla site unless plugin is disabled from DB.
4.	The benefit of choosing local option is that you can buy the latest more accurate DB from http://www.maxmind.com/ and use that DB

### Configuration:

1.	Country Codes: This requires comma (,) separated list of Country codes that need to be blocked. For example US, IN, FR to block IP Address from United States, India and France.

2.	Verification: ~~~External (default) or~~~ Local. ~~~External will use CodeFire.in Server to validate the country. We use latest Free DB from MaxMind http://www.maxmind.com/app/geolitecountry.~~~ In case you want to use local Verification you will need to install the geoip DB on your server. Please do not enable Local without installing the DB. (Refer below for installing the DB)

3.	Message or Redirect: In case a user from blocked country accesses the site, you can display an error message or redirect them to some other site.

4.	Text Message: This is the error message that will be displayed.

5.	Site: You need to set url for the site where you want to redirect the user example http://www.CodeFire.in

### Install GeoIP DB for local option.

1.	Extract the geoip folder from the plugin zip.
2.	Upload the folder geoip to libraries/ folder of Joomla installation
3.	Get the latest GeoIP.dat from http://www.maxmind.com/app/geolitecountry. and replace the existing (blank file with same name)one in /library/geoip folder
4.	Enable Local option in plugin settings.

### cfBlockCountry-mod changes ##

 cfBlockCountry-mod has a few new feautures: 

- Whitelist/blacklist ip ranges
- Allow or deny country codes
- IPv6 support (for country codes *only*, not white/blacklist)

"External" geoip lookup is disabled since url is offline.

#### Whitelist/blacklist usage:
The default file whitelist/blacklist is [CFBlockCountryIPList.php](). As example search engines Google, Bings, Duckduckgo and Yahoo are whitelisted and IANA TEST-NET are blacklisted. Be careful when editting:

**CAUTION! We do not verify anything! Any mistake will lead to unreachable site!**

- Enter IPv4 ranges like this: ```'192.168.0.1 192.168.0.255'```
- Make sure the last ip ranges does not have a "```,```" at the end of the line.

cfBlockCountry-mod has been tested with Joomla 2.5
