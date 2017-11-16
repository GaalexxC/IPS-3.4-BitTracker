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
*</pre>
* DevCU Public License DCUPL Rev 21
* The use of this license is free for all those who choose to program under its guidelines.
* The creation, use, and distribution of software under the terms of this license is aimed at protecting the authors work.
* The license terms are for the free use and distribution of open source projects.
* The author agrees to allow other programmers to modify and improve, while keeping it free to use, the given software with the full knowledge of the original authors copyright.
*
*  The full License is available at devcu.com
*  http://www.devcu.com/devcu-public-license-dcupl/
*/
if ( ! defined( 'IN_IPB' ) )
{
print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
exit();
}
class public_bitracker_client_announce extends ipsCommand
	{
	/**
	* our request user-agent
	*
	* @var 	string
	*/
	protected $uagent		= '';
	/**
	* client error string
	*
	* @var 	string
	*/
	public $client_error		= '';
	/**
	* client error string
	*
	* @var 	string
	*/
	private $log_error	   = '';

	/**
	* Torrent_data object
	*
	* @array	object
	*/
	protected $torrent_data = array();
	/**
	* Torrent_data object
	*
	* @array	object
	*/
	protected $request = array();
	/**
	* Incoming request array
	*
	* @array	object
	*/
	protected $member = array();
	/**
	* member array
	*
	* @array	object
	*/
	protected $peer = array();
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
		//---------------------------------
		// $request cleaned already?
		//---------------------------------
		$this->request = IPSLib::parseIncomingRecursively( $this->request );
		//-----------------------------
		// _GET the request info
		//-----------------------------
		if ( $this->request['request_method'] != 'get' ){
		$this->registry->bitFunctions->log_error(e100, "Not a GET request");
		exit();//Error code: 100
		}
		//-----------------------------
		// Load some required methods
		//-----------------------------
		require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/announce_functions.php' );/*noLibHook*/

		//--------------------------------------------------------
		// Check we have a valid perm_key if [settings] require one
		//--------------------------------------------------------
		if( $this->settings['bit_private'] && $this->settings['bit_passkey'] )
		{
		if ( strlen($this->request['perm_key'] ) != 32 ){
		$this->registry->bitFunctions->log_error(e104, "Invalid permission key length or no permission key set");
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
		if ( !$this->member && count($this->member) != 1 )
		{
		$this->registry->bitFunctions->log_error(e105, "Unable to match permission key with member");
		client_error("Error:e105 - Contact Support");
		exit();
		}
		}
		// IP address checks (IPv4)
		if ( isset( $this->request['ip'] ) ){

		if ( filter_var( trim($this->request['ip']), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$this->request['ip'] = $this->request['ip'];
		}else{
		$this->registry->bitFunctions->log_error(e101, "Failed IPv4 validation");
		client_error("Error:e101 - Contact Support");
		exit();

		}
		}
		if ( !isset($this->request['ip']) ||  $this->request['ip'] = '' ){

		if ( filter_var( trim($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$this->request['ip'] = $_SERVER['REMOTE_ADDR']; //Use Remote address as request IP
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
		//-------------------------------------------------------------------------------
		// Check the IP matches one that IPB has on record, only if [settings] require it
		//-------------------------------------------------------------------------------
		if ( $this->settings['bit_ip'] )
		{
		//------------------------------------
		// Are we using ip list restrictions?
		//------------------------------------			
	    if($this->settings['bit_ip_blacklist'])
		{
		$ip_blacklist = explode( ',', $this->settings['bit_ip_blacklist']);
		 if(in_array( $this->request['ip'], $ip_blacklist ))
		 {
		    $this->registry->bitFunctions->log_error(E050, "Request IP match found in blacklist");
		    client_error("Error:E050 - Contact Support");
		    exit();			 
		 }
		}			
	    if($this->settings['bit_ip_whitelist'])
		{
		$ip_whitelist = explode( ',', $this->settings['bit_ip_whitelist']);
		}
		//---------------------------------------------------------------
		// Ok got the member, create array of their 3 main IP address'es
		//---------------------------------------------------------------
		$userIPArray = array( 'mem_regip' => $this->member['ip_address'],
		                       'mem_uaip_1' => $this->member['ip_address2'],
		                       'mem_uaip_2' => $this->member['ip_address3'],
		);
		
		//---------------------------------------------------------------
		// Check that this members request IP matches?
		//---------------------------------------------------------------

		if ( !in_array( $this->request['ip'], $userIPArray ) )
		{
			if(!in_array( $this->request['ip'], $ip_whitelist ))
			{
		      $this->registry->bitFunctions->log_error(e106, "Cannot match the request IP to the members recorded IP address'es");
		      client_error("Error:e106 - Contact Support");
		      exit();
		    }
		}

		}

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
		//------------------------------------------------------
		// Treat the peer id the same...!
		//------------------------------------------------------
		$this->request['peer_id'] = '';
		
		$pos_id = strpos( $_SERVER[REQUEST_URI], 'peer_id=' );
		$strip_id = substr( $_SERVER[REQUEST_URI], $pos_id+8 );
		$pieces = explode( '&', $strip_id );
		$this->request['peer_id'] = (urldecode(trim($pieces[0])));
		if ( strlen( $this->request['peer_id'] ) != 20 )
		{
		$this->registry->bitFunctions->log_error(e1012i, "Decoded info_hash is not the correct length");
		client_error("Error:e1012i - Contact Support");
		exit();			
		}
		$this->request['peer_id'] = bin2hex( $this->request['peer_id'] );
		
		if ( strlen($this->request['peer_id']) != 40 )
		{
		$this->registry->bitFunctions->log_error(e1012, "Decoded peer_id is not the correct length");
		client_error("Error:e1012 - Contact Support");
		exit();
		}		
		if ( !ctype_alnum($this->request['peer_id']) )
		{
		$this->registry->bitFunctions->log_error(E013, "Client peer_id value is not alphanumerical!");
		client_error("Error:E013 - Contact Support");
		exit();
		}		
		//----------------------------------------
		// Leeeets get ready to... ruuuumble...!!
		//----------------------------------------
		if ( $this->request['left'] == 0 ){
		$this->request['seeder'] = yes;
		}else{
		$this->request['seeder'] = no;
		}
		if ( isset($this->request['event']) && !empty($this->request['event']) )
		{
		$this->request['event'] = trim($this->request['event']);
		}
		//if( !is_numeric ($this->request['numwant']) || !is_numeric($this->request['left']) || !is_numeric($this->request['uploaded'])  || !is_numeric($this->request['downloaded']))
		//{
			//$this->registry->bitFunctions->log_error(E200n, "Invalid numerical request fields");
		    // client_error("Error:E200n - Contact Support");
		     //exit();
		//}
		if (isset($this->request['numwant']) )
		{
		$this->request['numwant'] = intval($this->request['numwant']);			
		}
		if ( isset($_SERVER['HTTP_USER_AGENT']) ){
		$this->request['client'] = $_SERVER['HTTP_USER_AGENT'];
		}else{
		$this->request['client'] = 'UNKOWN CLIENT';
		}		
		$this->request['left'] = intval($this->request['left']);
		$this->request['uploaded'] = intval($this->request['uploaded']);
		$this->request['downloaded'] = intval($this->request['downloaded']);		
		$this->request['no_peer_id'] = intval($this->request['no_peer_id']);
		$this->request['compact'] = intval($this->request['compact']);
		//--------------------------------
		// Quick NAT check - Not accurate?
		//--------------------------------
		$nat = $this->registry->bitFunctions->checkifFirewalled( $this->request['ip'], $this->request['port'] );
		if ( $nat || $nat == 1 ){
		$this->request['connectable'] = no;
		}else{
		$this->request['connectable'] = yes;
		}
		if(isset($this->request['key']) && !empty($this->request['key']))
		{
            $this->request['session_key'] = strtoupper(IPSText::alphanumericalClean(trim($this->request['key'])));
        }



		//--------------------------------
		// Is there a session already?
		//--------------------------------	
		$this->peerSession = $this->DB->buildAndFetch( array( 'select' => '*',
		                                                      'from'	=> 'bitracker_torrent_peers',
		                                                      'where'	=> "peer_ip='{$this->request['ip']}' AND torrent='{$this->torrent_data['torrent_id']}' AND mem_id='{$this->member['member_id']}'"
		                                             )      );
		
		if ( !$this->peerSession OR empty($this->peerSession) )
		{		
          $this->addNewPeer( $this->request, $this->torrent_data['torrent_id'], $this->torrent_data['torrent_infohash'] );
		  if( $this->request['seeder'] == yes )
		  {
            $this->DB->update( "bitracker_torrent_data", 'torrent_seeders=torrent_seeders+1', 'torrent_id=' . $this->torrent_data['torrent_id'], false, true );
	
          }else{

            $this->DB->update( "bitracker_torrent_data", 'torrent_leechers=torrent_leechers+1', 'torrent_id=' . $this->torrent_data['torrent_id'], false, true );		
		  }	
		  if( $this->request['compact'] == 1)
		   {
		    $this->sendCompactPeerList();
		    // Check for cache and either create or update
		    exit();
		   }else{
		    $this->sendPeerList();
		   // Check for cache and either create or update		
		   exit();		
		   }
		}else{
		
		$this->updatePeerStats( $this->request, $this->peerSession, $this->torrent_data['torrent_id'], $this->torrent_data['torrent_infohash'] );
		
        if ( $this->request['event'] == 'stopped' )
		{
		  if( $this->request['seeder'] == yes )
		  {
             $this->DB->update( "bitracker_torrent_data", 'torrent_seeders=torrent_seeders-1', 'torrent_id=' . $this->torrent_data['torrent_id'], false, true );

          }elseif( $this->request['seeder'] == no ){

             $this->DB->update( "bitracker_torrent_data", 'torrent_leechers=torrent_leechers-1', 'torrent_id=' . $this->torrent_data['torrent_id'], false, true );	
		  }
		  
		  $this->killPeerSess( $this->peerSession );		  
		}
        if ( $this->request['event'] == 'completed' )
		{
			if ( $this->torrent_data['torrent_leechers'] > 0 )
			{
			  $this->DB->query("UPDATE ".ipsRegistry::dbFunctions()->getPrefix()."bitracker_torrent_data SET torrent_seeders=torrent_seeders+1, torrent_leechers=torrent_leechers-1, torrent_times_comp=torrent_times_comp+1 WHERE torrent_id={$this->torrent_data['torrent_id']}");
			}else{
			  $this->DB->query("UPDATE ".ipsRegistry::dbFunctions()->getPrefix()."bitracker_torrent_data SET torrent_seeders=torrent_seeders+1, torrent_times_comp=torrent_times_comp+1 WHERE torrent_id={$this->torrent_data['torrent_id']}");
			}
        }
				
		if( $this->request['compact'] == 1)
		{
		$this->sendCompactPeerList();
		// Check for cache and either create or update
		exit();
		}else{
		$this->sendPeerList();
		// Check for cache and either create or update		
		exit();		
		}
		}
		}	
		/**
		* Send a compact peers list to the client
		*
		* @return
		*/
		private function sendCompactPeerList()
		{

		$this->DB->build(array(	'select'	=>  "peer_ip, peer_port",
		                                        'from'		=> 'bitracker_torrent_peers',
		                                        'where'		=> "torrent='{$this->torrent_data['torrent_id']}'",
		                                        'order'		=>  $this->DB->buildRandomOrder(),
		                                        'limit'		=>  array(0, 20)
		));	
		
		$result = $this->DB->execute();
		
          $resp = "d" . benc_str("interval") . "i" . 1200 ."e" . benc_str("min interval") . "i" . 100 . "e5:"."peers" ;		
		  while( $record = $this->DB->fetch($result) )
		  {
		    $peer_ip = explode('.', $record["peer_ip"]);
		    $plist .= pack("C*", $peer_ip[0], $peer_ip[1], $peer_ip[2], $peer_ip[3]). pack("n*", (int)$record["peer_port"]);
		  }
        $resp .= benc_str($plist) . "ee";
		benc_resp_raw($resp);
		return;	  
		}
		/**
		* Send a peers list to the client when compact=0
		*
		* @return
		*/
		private function sendPeerList()
		{

		$this->DB->build(array(	'select'	=>  "peer_ip, peer_port",
		                                        'from'		=> 'bitracker_torrent_peers',
		                                        'where'		=> "torrent='{$this->torrent_data['torrent_id']}'",
		                                        'order'		=>  $this->DB->buildRandomOrder(),
		                                        'limit'		=>  array(0, $this->request['numwant'])
		));	
		
		$result = $this->DB->execute();
		
		$resp = "d" . benc_str("interval") . "i300e" . benc_str("peers") . "l";	
		$_peer = array();
		$_peer_num = 0;
		while( $record = $this->DB->fetch($result) )
		{
		$record["peer_id"] = str_pad($record["peer_id"], 20);
		$resp .= "d" . benc_str("ip") . benc_str($record["ip"]);
		if (!$this->request['no_peer_id']) 
		{
		$resp .= benc_str("peer id") . benc_str($record["peer_id"]);
		}
		$resp .= benc_str("port") . "i" . $record["peer_port"] . "e" . "e";
		}
		$resp .= "e7:privatei1ee";
		benc_resp_raw($resp);
		return;
		}		
		/**
		* Does all the number updates for this session
		*
		* @param	array		$this->request
		* @param	array		$this->peerSession	
		* @param	md5 hash    session_key
        * @param    array       torrent id and hash		
		* @return	bool        true/false
		*/
		protected function updatePeerStats( $request=array(), $peerStats=array(), $torrentId='0', $torrentInfoHash='')
		{
		
		  if ( !$torrentId || !$torrentInfoHash )
              {
			    $this->registry->bitFunctions->log_error(E200s, "Update stats failed!");
		        client_error("Error:E200u - Contact Support");
		        exit();
              }
		
		   $_toUpdate = array();
		   $_toUpdate['upload'] = 0;
		   $_toUpdate['download'] = 0;
		
		  if( $request['uploaded'] > $peerStats['uploaded'] )
		  {
		     $_toUpdate['upload'] = $request['uploaded'] - $peerStats['uploaded'];
		  }
		  if( $request['downloaded'] > $peerStats['downloaded'] )
		  {
		     $_toUpdate['download'] = $request['downloaded'] - $peerStats['downloaded'];
		  }		  
			  
			if ( $request['event'] == 'stopped' )
			{
			  if ( $_toUpdate['upload'] > 0 || $_toUpdate['download'] > 0 || $request['left'] > 0 )
			  {
			    /* Update the historical */
		         $_historyStats = $this->DB->buildAndFetch( array(  'select' 	=> 'id, uploaded, downloaded, history_left',
		                                                            'from'		=> 'bitracker_torrent_peers_history',
		                                                            'where'	=> "torrent_id='{$torrentId}' AND member_id='{$this->member['member_id']}'"
		                                                           )      
													        );
                 $_toUpdate['upload_his'] = $_toUpdate['upload'] + $_historyStats['uploaded'];
                 $_toUpdate['download_his'] = $_toUpdate['download'] + $_historyStats['downloaded'];
				 
			     $his_totals = array( 'uploaded' => $_toUpdate['upload_his'],
				                      'downloaded'   => $_toUpdate['download_his'],
				                      'history_left'   => $request['left'],
								      'last_updated' => time()
				                    );
                 $this->DB->update( 'bitracker_torrent_peers_history', $his_totals, "id='{$_historyStats['id']}'" );
				 
				 $_toUpdate['upload_mem'] = $this->member['upload_total'] + $_toUpdate['upload'];
				 $_toUpdate['download_mem'] = $this->member['download_total'] + $_toUpdate['download'];
				 
			     $mem_totals = array( 'upload_total' => $_toUpdate['upload_mem'],
				                   'download_total'   => $_toUpdate['download_mem']
				                     );
                 $this->DB->update( 'members', $mem_totals, "member_id='{$this->member['member_id']}'" );
                 return;				 
		      }
			  
			}else{
				
		    $peer_update= array('peer_id'	 => trim($request['peer_id']),
								'compact'	 => $request['compact'],
								'peer_ip'	 => $request['ip'],
								'peer_ipv6'	 => $request['ipv6'],
								'peer_port'	 => intval($request['port']),
								'uploaded'	 => intval($request['uploaded']),
								'downloaded' => intval($request['downloaded']),							
								'to_go'		 => intval($request['left']),
								'seeder'	 => $request['seeder'],
								'last_action'=> time(),
								'connectable'=> $request['connectable'],
								'client'	 => trim($request['client']),
							    'session_key'	 => trim($request['session_key'])
								);		  

			/* Update the active */		
            $this->DB->update( 'bitracker_torrent_peers', $peer_update, "peer_id='{$request['peer_id']}' AND torrent='{$torrentId}' AND mem_id='{$this->member['member_id']}'" );
			
			    /* Update the historical */
			  if ( $_toUpdate['upload'] > 0 || $_toUpdate['download'] > 0 || $request['left'] > 0)
			  {
			    /* Update the historical */
		         $_historyStats = $this->DB->buildAndFetch( array(  'select' 	=> 'id, uploaded, downloaded, history_left',
		                                                            'from'		=> 'bitracker_torrent_peers_history',
		                                                            'where'	=> "torrent_id='{$torrentId}' AND member_id='{$this->member['member_id']}'"
		                                                           )      
													        );
                 $_toUpdate['upload_his'] = $_toUpdate['upload'] + $_historyStats['uploaded'];
                 $_toUpdate['download_his'] = $_toUpdate['download'] + $_historyStats['downloaded'];
			     if($request['event'] == 'completed')
				 {
			      $his_totals = array( 'uploaded' => $_toUpdate['upload_his'],
				                       'downloaded'   => $_toUpdate['download_his'],
                                       'history_left' => $request['left'],
								       'last_updated' => time(),
									   'history_completed' => yes,
									   'history_date_completed' => time()
				                    );					 
				 }else{
			      $his_totals = array( 'uploaded' => $_toUpdate['upload_his'],
				                        'downloaded'   => $_toUpdate['download_his'],
                                        'history_left' => $request['left'],
								        'last_updated' => time()
				                    );
				 }
                 $this->DB->update( 'bitracker_torrent_peers_history', $his_totals, "id='{$_historyStats['id']}'" );
				 
				 $_toUpdate['upload_mem'] = $this->member['upload_total'] + $_toUpdate['upload'];
				 $_toUpdate['download_mem'] = $this->member['download_total'] + $_toUpdate['download'];
				 
			     $mem_totals = array( 'upload_total' => $_toUpdate['upload_mem'],
				                   'download_total'   => $_toUpdate['download_mem']
				                 );
                 $this->DB->update( 'members', $mem_totals, "member_id='{$this->member['member_id']}'" );
                 return;				 
		      }				
			}

            unset($peer_update);
            unset($this->hist_data);
			unset($new_totals);
			unset($sessUpdate);
			return;
			
		}
		/**
		* Updates stats for orphaned sessions
		*
		* @param	array		old session data
		* @return	bool        true/false
		*/
		private function updateOldSession( $oldSess=array() )
		{

		    $this->_memRecord = $this->DB->buildAndFetch( array(  'select' 	=> 'id, uploaded, downloaded',
		                                                         'from'		=> 'bitracker_torrent_peers_history',
		                                                         'where'	=> "torrent_id='{$oldSess['torrent']}' AND member_id='{$this->member['member_id']}'"
		                                                       )      
													   );
            if (!$this->_memRecord || empty($this->_memRecord))
              {
			    	$this->registry->bitFunctions->log_error(E600s, "Old session cleanup failed, no session found");
		             client_error("Error:E600s - Contact Support");
		             exit();
              }
            $sessUpdate['uploaded'] = $this->_memRecord['uploaded'] + $oldSess['uploaded'];
            $sessUpdate['downloaded'] = $this->_memRecord['downloaded'] + $oldSess['downloaded'];	
	
			$new_totals = array( 'uploaded'     => $sessUpdate['uploaded'],
				                  'downloaded'   => $sessUpdate['downloaded'],
								  'last_updated' => time()
				                );
	
		    $this->DB->update( 'bitracker_torrent_peers_history', $new_totals, "id='{$this->_memRecord['id']}'" );
			unset($this->_memRecord);
			unset($new_totals);
			unset($sessUpdate);		
			return;
		}		
		/**
		* Kills a peer session
		*
		* @param	var		session key	
		* @return	bool        true/false
		*/
		private function killPeerSess( $session )
		{
		
		$this->DB->delete( 'bitracker_torrent_peers', "peer_ip='{$session['peer_ip']}' AND torrent='{$session['torrent']}' AND mem_id='{$session['mem_id']}'");
        return;		
			
		}
		/**
		* Adds a new peer
		*
		* @param	array		request			
		* @param	var		    torrent id
		* @param	var		    torrent infohash			
		* @return	bool        true/false
		*/
		protected function addNewPeer( $request=array(), $torrentId=0, $torrentInfoHash='' )
		{
            if (!$torrentId	|| !$torrentInfoHash)
              {
			    	$this->registry->bitFunctions->log_error(E200s, "Update stats failed!");
		             client_error("Error:E200u - Contact Support");
		             exit();
              }	
                   $peer_Add= array('torrent'	 => intval($torrentId),
		                            'peer_id'	 => trim($request['peer_id']),
		                            'compact'	 => $request['compact'],
		                            'peer_ip'	 => $request['ip'],
		                            'peer_ipv6'	 => $request['ipv6'],
		                            'peer_port'	 => intval($request['port']),
		                            'uploaded'	 => intval($request['uploaded']),
		                            'downloaded' => intval($request['downloaded']),
		                            'to_go'		 => intval($request['left']),
		                            'seeder'	 => $request['seeder'],
		                            'started'	 => time(),
		                            'last_action'=> time(),
		                            'connectable'=> $request['connectable'],
		                            'client'	 => trim($request['client']),
		                            'mem_id'	 => intval($this->member['member_id']),
		                            'perm_key'	 => trim($request['perm_key']),
		                            'session_key'=> trim($request['session_key'])	
                                   );									

			/* active */		
            $this->DB->insert( 'bitracker_torrent_peers', $peer_Add );
			
			/* historical */
					$mHasRecord = $this->DB->buildAndFetch( array(  'select' 	=> '*',
		                                                               'from'		=> 'bitracker_torrent_peers_history',
		                                                               'where'	=> "member_id='{$this->member['member_id']}' AND torrent_id='{$torrentId}'"
		                                                     )      );
														 
			if( !$mHasRecord || empty($mHasRecord) )
			{
            $this->DB->insert( 'bitracker_torrent_peers_history', array( 'member_id' => $this->member['member_id'],
			                                                        'uploaded'   => intval($request['uploaded']),
																    'downloaded' => intval($request['downloaded']),
                                                                    'history_left' => intval($request['left']),
																    'torrent_id' => $torrentId,
																    'info_hash'   => $torrentInfoHash,
																    'last_updated' => time()
			                                                     ) 
							);
            }
			
            unset($peer_Add);
		    unset($mHasRecord);
            return;			
			
		}		


	}