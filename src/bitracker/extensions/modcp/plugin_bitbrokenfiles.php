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
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_bitracker_bitbrokenfiles
 * @brief		Moderator control panel plugin: show bit files reported broken
 */
class plugin_bitracker_bitbrokenfiles
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Cat permissions
	 *
	 * @var	string
	 */
	protected $_cats	= '';

	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		//-----------------------------------------
		// Other stuff
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'bitracker' );
	}
	
	/**
	 * Returns the primary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getPrimaryTab()
	{
		return 'bitbrokefiles';
	}
	
	/**
	 * Returns the secondary tab key for the navigation bar
	 * 
	 * @return	@e string
	 */
	public function getSecondaryTab()
	{
		return 'bitbrokenfiles';
	}

	/**
	 * Determine if we can view tab
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e bool
	 */
	public function canView( $permissions )
	{
		$this->_cats	= $this->_getCats();
		
		if( $this->_cats )
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Execute plugin
	 *
	 * @param	array 	$permissions	Moderator permissions
	 * @return	@e string
	 */
	public function executePlugin( $permissions )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------

		if( !$this->canView( $permissions ) )
		{
			return '';
		}

		//----------------------------------
		// Get Files Pending Approval
		//----------------------------------
		
		$limiter	= $this->_cats == '*' ? '' : " AND f.file_cat IN({$this->_cats})";
		$results	= array();

		$this->DB->build( array(
									'select'	=> 'f.*',
									'from'		=> array( 'bitracker_files' => 'f' ),
									'where'		=> "f.file_broken=1" . $limiter,
									'add_join'	=> array(
														array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=f.file_submitter',
																'type'		=> 'left',
															),
														array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'm.member_id=pp.pp_member_id',
																'type'		=> 'left',
															),
														)
							)		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['_isRead']	= $this->registry->classItemMarking->isRead( array( 'forumID' => $row['file_cat'], 'itemID' => $row['file_id'], 'itemLastUpdate' => $row['file_updated'] ), 'bitracker' );
			
			$results[] = IPSMember::buildDisplayData( $row );
		}
		
		return $this->registry->getClass('output')->getTemplate('bitracker_other')->moderatorPanel( 'broken', $results );
	}
	
	/**
	 * Get categories we can approve files in
	 *
	 * @return	@e string
	 */
	protected function _getCats()
	{
		$appcats	= '';

		if( $this->memberData['g_is_supmod'] )
		{
			$appcats 	= '*';
		}
		else
		{
			if( is_array( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] as $k => $v )
					{
						if( $v['modcanbrok'] )
						{
							if( $appcats )
							{
								$appcats = $appcats . ',' . $v['modcats'];
							}
							else
							{
								$appcats = $v['modcats'];
							}
						}
					}
				}
			}
			else if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['modcanbrok'] )
						{
							if( $appcats )
							{
								$appcats = $appcats . ',' . $v['modcats'];
							}
							else
							{
								$appcats = $v['modcats'];
							}
						}
					}
				}
			}
		}
		
		return $appcats;
	}
}