<?php
/**
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
 * The author agrees to allow other programmers to modify and improve, while keeping it free to use, the given software with the full knowledge of the original authors copyright.
 * 
 *  The full License is available at devcu.com
 *  http://www.devcu.com/devcu-public-license-dcupl/
 */

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	 * Define classes path
	 */
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'apiCore' ) )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );/*noLibHook*/
}

/**
 * API: BIT
 *
 * This class will pull the last 10 torrent submissions a user has submitted
 *
 * @package		devCU biTracker
 * @author  	PM
 * @version		1.0
 */
class apiBitracker extends apiCore
{
	/**
	 * Returns an array of torrent data
	 *
	 * @access	public
	 * @param	integer	Member id
	 * @param	integer	Max number to pull
	 * @param	integer	Pull even if no member id is set
	 * @param	string	Order by
	 * @param	array 	Additional filters (they are added to where clause AS IS)
	 * @return	array	Array of download data
	 */
	public function returnBitracker( $member_id = 0, $limit = 10, $nomember = 0, $order='', $filters=array() )
	{
		/* App installed? */
		if( !IPSLib::appIsInstalled('bitracker') )
		{
			return array();
		}
		
		/* No member ID? */
		if( !$member_id AND !$nomember )
		{
			return array();
		}
		
		/* Not online? */
		if( $this->settings['bit_online'] == 0 )
		{
			$offline_access = explode( ",", $this->settings['bit_offline_groups'] );
			
			$my_groups = array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroups_other'] )
			{
				$my_groups = array_merge( $my_groups, explode( ",", IPSLib::cleanPermString( $this->memberData['mgroups_other'] ) ) );
			}
			
			$continue = 0;
			
			foreach( $my_groups as $group_id )
			{
				if( in_array( $group_id, $offline_access ) )
				{
					$continue = 1;
					break;
				}
			}
			
			if( $continue == 0 )
			{
				// Offline, and we don't have access
				
				return array();
			}
		}
				
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$files		= array();
		$where		= array();
		
		$member_id	= intval($member_id);
		
		if( $member_id )
		{
			$where[]	= 'file_submitter=' . $member_id;
		}
		
		if( is_array($filters) AND count($filters) )
		{
			$where	= array_merge( $where, $filters );
		}
		
		$order	= $order ? $order : 'file_submitted DESC';

		//-----------------------------------------
		// Load caches - uses external lib if avail
		//-----------------------------------------	
		
		if( !$this->registry->isClassLoaded('categories') )
		{
			define( 'SKIP_ONLINE_CHECK', true );
			ipsRegistry::getAppClass( 'bitracker' );
		}
		
		$categories = $this->registry->getClass('categories')->member_access['show'];

		if( !is_array($categories) OR !count($categories) )
		{
			//No category permissions
			
			return array();
		}
		
		$memberIds	= array();
		
		$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'bitracker_files',
								 'where'	=> ( count($where) ? implode( ' AND ', $where ) . ' AND ' : '' ) . 'file_open=1 AND file_cat IN (' . implode( ',', $categories ) . ')',									 					
								 'order'	=> $order,
								 'limit'	=> array( 0, $limit )
						)		);
										
		$res = $this->DB->execute();
		
		while( $r = $this->DB->fetch($res) )
		{
			$r['_isRead']				= $this->registry->classItemMarking->isRead( array( 'forumID' => $r['file_cat'], 'itemID' => $r['file_id'], 'itemLastUpdate' => $r['file_updated'] ), 'bitracker' );
			$r['members_display_name']	= $r['members_display_name'] ? $r['members_display_name'] : $this->lang->words['global_guestname'];
			$r['category_name']			= $this->registry->getClass('categories')->cat_lookup[ $r['file_cat'] ]['cname'];
			$r['cname_furl']			= $this->registry->getClass('categories')->cat_lookup[ $r['file_cat'] ]['cname_furl'];
			$files[ $r['file_id'] ]		= $r;
			
			$memberIds[ $r['file_submitter'] ]	= $r['file_submitter'];
		}
		
		if( count($memberIds) )
		{
			$members	= IPSMember::load( $memberIds );
			
			// Add in guest
			$members[0] = IPSMember::setUpGuest();
			

			foreach( $files as $k => $v )
			{
				$files[ $k ]	= array_merge( $files[ $k ], IPSMember::buildDisplayData( $members[ $v['file_submitter'] ] )
);
			}
		}

		return $files;
	}	
}