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

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @version		$Rev: 10721 $ 
 */
class bitrackerMemberSync
{
	/**
	 * Registry reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}
	
	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	@e void
	 * @todo 	[Future] Handle file voters
	 */
	public function onDelete( $mids )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->registry->DB()->update( 'bitracker_comments', array( 'comment_mid' => 0 ), 'comment_mid' . $mids );
		$this->registry->DB()->update( 'bitracker_bitracker', array( 'dmid' => 0 ), 'dmid' . $mids );
		$this->registry->DB()->update( 'bitracker_files', array( 'file_submitter' => 0 ), 'file_submitter' . $mids );
		$this->registry->DB()->update( 'bitracker_files', array( 'file_approver' => 0 ), 'file_approver' . $mids );
		
		//-----------------------------------------
		// Just delete
		//-----------------------------------------
		
		$this->registry->DB()->delete( 'bitracker_sessions', 'dsess_mid' . $mids );
		
		//-----------------------------------------
		// Handle stored file info
		//-----------------------------------------
		
		# bitracker_files.file_votes
		
		//-----------------------------------------
		// Get rid of moderators
		//-----------------------------------------
		
		$mods	= array();
		$this->registry->DB()->build( array( 'select' => '*', 'from' => 'bitracker_mods', 'where' => 'modtype=1' ) );
		$this->registry->DB()->execute();
		
		while( $r = $this->registry->DB()->fetch() )
		{
			$data			= explode( ':', $r['modgmid'] );
			$r['member_id']	= $data[0];

			$mods[ $r['modid'] ]	= $r['member_id'];
		}
		
		if( count($mods) )
		{
			$check 	= str_replace( " IN (", '', $mids );
			$check	= str_replace( ")", '', $check );
			$check	= IPSText::cleanPermString( $check );
			$ids	= explode( ',', $check );
			
			foreach( $mods as $id => $member_id )
			{
				if( in_array( $member_id, $ids ) )
				{
					$this->registry->DB()->delete( 'bitracker_mods', 'modid=' . $id );
				}
			}
		}
		
		//-----------------------------------------
		// Handle category latest info
		//-----------------------------------------
		
		ipsRegistry::getAppClass('bitracker');
		
		$this->registry->getClass('categories')->rebuildFileinfo('all');
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	@e void
	 * @todo 	[Future] Handle file voters
	 */
	public function onMerge( $member, $member2 )
	{
		//-----------------------------------------
		// Update to guest
		//-----------------------------------------
		
		$this->registry->DB()->update( 'bitracker_comments', array( 'comment_mid' => $member['member_id'], 'comment_author' => $member['members_display_name'] ), 'comment_mid=' . $member2['member_id'] );
		$this->registry->DB()->update( 'bitracker_bitracker', array( 'dmid' => $member['member_id'] ), 'dmid=' . $member2['member_id'] );
		$this->registry->DB()->update( 'bitracker_files', array( 'file_submitter' => $member['member_id'] ), 'file_submitter=' . $member2['member_id'] );
		$this->registry->DB()->update( 'bitracker_files', array( 'file_approver' => $member['member_id'] ), 'file_approver=' . $member2['member_id'] );

		//-----------------------------------------
		// Just delete
		//-----------------------------------------

		$this->registry->DB()->delete( 'bitracker_sessions', 'dsess_mid=' . $member2['member_id'] );
		
		//-----------------------------------------
		// Handle stored file info
		//-----------------------------------------
		
		# file_votes
		# bitracker_files
		
		//-----------------------------------------
		// Update moderators for member2
		// We just remove the record to be safe
		//-----------------------------------------
		
		$mod	= $this->registry->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mods', 'where' => "modtype=1 AND modgmid='{$member2['member_id']}:{$member2['members_display_name']}'" ) );

		if( $mod['modid'] )
		{
			$this->registry->DB()->delete( 'bitracker_mods', 'modid=' . $mod['modid'] );
		}
		
		//-----------------------------------------
		// Handle category latest info
		//-----------------------------------------
		
		ipsRegistry::getAppClass('bitracker');
		
		$this->registry->getClass('categories')->rebuildFileinfo('all');
	}

	/**
	 * This method is run after a users display name is successfully changed
	 *
	 * @access	public
	 * @param	integer	$id			Member ID
	 * @param	string	$new_name	New display name
	 * @return	@e void
	 */
	public function onNameChange( $id, $new_name )
	{
		//-----------------------------------------
		// Fix moderators
		//-----------------------------------------
		
		$mod	= $this->registry->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mods', 'where' => "modtype=1 AND modgmid LIKE '{$id}:%'" ) );

		if( is_array($mod) AND count($mod) )
		{
			$this->registry->DB()->update( 'bitracker_mods', array( 'modgmid' => $id . ':' . $new_name ), 'modid=' . $mod['modid'] );
		}
		
		//-----------------------------------------
		// Fix comments
		//-----------------------------------------
		
		$this->registry->DB()->update( 'bitracker_comments', array( 'comment_author' => $new_name ), 'comment_mid=' . $id );
		
		//-----------------------------------------
		// Handle category latest info
		//-----------------------------------------
		
		ipsRegistry::getAppClass('bitracker');
		
		$this->registry->getClass('categories')->rebuildFileinfo('all');
	}
}