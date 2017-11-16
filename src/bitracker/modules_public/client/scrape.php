<?php

/**<pre>
*  devCU Software Development
*  devCU biTracker 1.0.0 Release
*  Last Updated: $Date: 2014-07-13 09:01:45 -0500 (Sunday, 13 July 2014) $
*
* @author 		TG / PM
* @copyright	(c) 2014 devCU Software Development
* @Web	        http://www.devcu.com
* @support       support@devcu.com
* @license		 DCU Public License
*
* DevCU Public License DCUPL Rev 21
* The use of this license is free for all those who choose to program under its guidelines.
* The creation, use, and distribution of software under the terms of this license is aimed at protecting the authors work.
* The license terms are for the free use and distribution of open source projects.
* The author agrees to allow other programmers to modify and improve, while keeping it free to use, the given software with *the full knowledge of the original authors copyright.
*</pre>
*  The full License is available at devcu.com
*  http://www.devcu.com/devcu-public-license-dcupl/
**/

if ( ! defined( 'IN_IPB' ) )
{
print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
exit();
}

class public_bitracker_client_scrape extends ipsCommand
{
/**
* our request user-agent
*
* @var 	string
*/
protected $uagent = '';

/**
* client error string
*
* @var 	string
*/
public $client_error = '';

/**
* log error string
*
* @var 	string
*/
private $log_error = array();

/**
* torrent_data object
*
* @array	object
*/
protected $torrentData = array();

/**
* request_data object
*
* @array	object
*/
protected $request = array();

/**
* Peer object
*
* @array	object
*/
protected $peerData = array();

/**
* Main class entry point
*
* @param	object		ipsRegistry reference
* @return	@e void
*/



public function doExecute( ipsRegistry $registry )
{

//------------------------------------------------------------
// Block access to browsers, bots or 'orrible search 'ingines
//------------------------------------------------------------

$requestAgent = $_SERVER['HTTP_USER_AGENT'];
$reAgent = explode(" ", $requestAgent);
$reAgent_name = preg_replace("/[^a-zA-Z ]/m", "", $reAgent[0]);

//------------------------------------------------------------------
// Load functions and search the IPB useragent database for a match!
//------------------------------------------------------------------

$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php', 'userAgentFunctions' );
$this->userAgentFunctions = new $classToLoad( $registry );

$IPBUAgents = $this->userAgentFunctions->fetchAgents();

foreach ( $IPBUAgents as $value )
{
$userAgents[] = $value['uagent_name'];

if (in_array(strtolower($reAgent_name), array_map('strtolower', $userAgents)))
{
		/* Redirect */
		//$this->registry->getClass('output')->silentRedirect( $this->settings['board_url'] . '/index.php' );
		/* 404 */	
		$this->registry->getClass('output')->showError( 'page_doesnt_exist', 10335, null, null, 404 );	
		exit();
}
}


//-----------------------------
// Load some required methods
//-----------------------------

require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/announce_functions.php' );/*noLibHook*/

//-----------------------------
// _GET the request info
//-----------------------------


if ( $this->request['request_method'] != 'get' ){

$this->registry->bitFunctions->log_error(e100, "Not a GET request");
exit();//Error code: 100

}

//---------------------------------
// $request cleaned already?
//---------------------------------

$this->request = IPSLib::parseIncomingRecursively( $this->request );



//-----------------------------
// Member Permission Checks
//-----------------------------

// IP address checks (IPv4)

if ( isset( $this->request['ip'] ) ){

if ( filter_var( $this->request['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

$this->request['ip'] = trim( $this->request['ip'] );

}else{

$this->registry->bitFunctions->log_error(e101, "Failed IPv4 validation");
client_error("Error:e101 - Contact Support");
exit();

}
}

if ( !isset($this->request['ip']) ||  $this->request['ip'] = '' ){

if ( filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

$this->request['ip'] = trim( $_SERVER['REMOTE_ADDR'] ); //Use Remote address as request IP

}else{

$this->registry->bitFunctions->log_error(e102, "Unable to retrieve a valid IPv4");
client_error("Error:e102 - Contact Support");
exit();

}
}

// IP address checks (IPv6)

if ( isset( $this->request['ipv6'] ) ){

if ( filter_var($this->request['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

$this->request['ipv6'] = trim($this->request['ipv6']); //Use request IPv6 if set for peer data

}else{

$this->request['ipv6'] = NULL;
}
}


//--------------------------------
// Attempt to deny proxy if set?
//--------------------------------

if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ){

$this->registry->bitFunctions->log_error(e103, "Proxy used");
client_error("Error:e103 - Contact Support");
exit();

}

//--------------------------------
// Check we have a valid perm_key
//--------------------------------

if ( strlen($this->request['perm_key'] ) != 32 ){

$this->registry->bitFunctions->log_error(e104, "Invalid permission key length");
client_error("Error:e104 - Contact Support");
exit();
}


$this->request['perm_key'] = IPSText::md5Clean($this->request['perm_key']);

//--------------------------------
// Find our member with perm key
//--------------------------------

$this->member = $this->DB->buildAndFetch( array( 'select' 	=> '*',
'from'		=> 'members',
'where'	=> "perm_key ='{$this->request['perm_key']}'"
)      );

if ( !$this->member && count($this->member) != 1 ){


$this->registry->bitFunctions->log_error(e105, "Unable to match permission key with member");
client_error("Error:e105 - Contact Support");
exit();

}

if ( $this->settings['bit_ip'] )
{

//---------------------------------------------------------------
// Ok got the member, create array of their 3 main IP address'es
//---------------------------------------------------------------

$userIPArray = array( 'mem_regip' => $this->member['ip_address'],
'mem_uaip_1' => $this->member['ip_address2'],
'mem_uaip_2' => $this->member['ip_address3'],
);
//---------------------------------------------------------------
// Now we can check this request ip with this members 3 main ip's
// if we don't find a match we can check ALL recorded ip's for
// this member.
//---------------------------------------------------------------

if ( !in_array( $this->request['ip'], $userIPArray ) )
{
$this->registry->bitFunctions->log_error(e106, "Cannot match the request IP to the members recorded IP address'es");
client_error("Error:e106 - Contact Support");
exit();

}
}



//*******************************************//
//**** START beta testing temp code edit ****//
//*******************************************//



$groups		= array( $this->member['member_group_id'] );

if( $this->member['mgroup_others'] )
{
foreach( explode( ',', $this->member['mgroup_others'] ) as $omg )
{
$groups[] = $omg;
}
}


$offlineGroups	= explode( ',', $this->settings['bit_offline_groups'] );

if( !$this->settings['bit_online'] )
{
$accessOffline	= false;

foreach( $groups as $g )
{
if( in_array( $g, $offlineGroups ) )
{
$accessOffline	= true;
}
}


if( !$accessOffline )
{
$this->registry->bitFunctions->log_error(e1001, "No group access permission denied");
client_error("Error:e1001 - Offline Error");
exit();
}
}

//*****************************************//
//**** END beta testing temp code edit ****//
//*****************************************//

		//------------------------------------------------------
		// IPB doesn't like hashes in urls so take it straight
        // from $_SERVER[REQUEST_URI]
		//------------------------------------------------------
		$this->request['info_hash'] = '';

		$pos_hash = strpos( $_SERVER[REQUEST_URI], 'info_hash=' );
		$strip_hash = substr( $_SERVER[REQUEST_URI], $pos_hash+10 );
		$pieces = explode( '&', $strip_hash );
		$this->request['info_hash'] = (urldecode(trim($pieces[0])));
		if ( strlen( $this->request['info_hash'] ) != 20 )
		{
		$this->registry->bitFunctions->log_error(e107i, "Decoded info_hash is not the correct length");
		client_error("Error:e107i - Contact Support");
		exit();			
		}	
		$this->request['info_hash'] = bin2hex($this->request['info_hash']);
		
		if ( strlen($this->request['info_hash']) != 40 )
		{
		$this->registry->bitFunctions->log_error(e107, "Decoded info_hash is not the correct length");
		client_error("Error:e107 - Contact Support");
		exit();
		}			
		if ( !ctype_alnum($this->request['info_hash']) )
		{
		$this->registry->bitFunctions->log_error(E011, "Torrent info hash value is not alphanumerical!");
		client_error("Error:E011 - Contact Support");
		exit();
		}
		$this->torrent_data = $this->DB->buildAndFetch( array(  'select' 	=> '*',
		                                                         'from'		=> 'bitracker_torrent_data',
		                                                         'where'	=> "torrent_infohash='{$this->request['info_hash']}'"
		)      );
		if ( !$this->torrent_data || empty( $this->torrent_data) )
		{
		$this->registry->bitFunctions->log_error(e109, "Cannot find the torrent with the info hash supplied");
		client_error("Error:E109h - Contact Support");
		exit();
		}

//----------------------
// Get the peer numbers
//----------------------

$sd_num = $this->registry->getClass('bitFunctions')->countPeers($this->torrent_data['torrent_id']);

$lc_num = $this->registry->getClass('bitFunctions')->countPeers($this->torrent_data['torrent_id'], FALSE);

$result="d5:filesd";
$hash = pack("H*", $this->torrent_data['torrent_infohash']);
//  $hash =hex2bin($torrent_data['torrent_infohash']);
$result.="20:".$hash."d";
$result.="8:completei" . $sd_num . "e";
$result.="10:downloadedi".$this->torrent_data['torrent_times_comp']."e";
$result.="10:incompletei" . $lc_num . "e";
//$result.="4:name".strlen($this->torrent_data['torrent_name']) . ":" . $this->torrent_data['torrent_name']."e";
$result.="e";
$result.="ee";



benc_resp_raw($result);
exit();

     }
  }