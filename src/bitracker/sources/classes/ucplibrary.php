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
 

if ( !defined('IN_IPB') )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class bitrackerSystemLibrary
{
	/**
	 * Registry objects
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData;
	public $cache;
	public $caches;	
	
	/* App title */
	
	public $title 			= 'bitracker';

	/* SQL fields */
	
	public $table			= '';
	public $column			= '';	
	public $mem_column_id	= '';

	#Day :D
	public $day 			= 86400;
	
	/* Debug mode */
	
	public $debug			= false;

	/**
    * Constructor
    *
    * @access	public
    * @param	object		ipsRegistry reference
    * @return	void
    */
	public function __construct( $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Load lang */
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );	
   }

	public function checkAccess()
	{
		$groups		= array( $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
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
				$this->registry->member()->finalizePublicMember();
				$this->registry->getClass('output')->showError( $this->settings['bit_offline_msg'], null, null, 403 );
			}
		}

  }

}