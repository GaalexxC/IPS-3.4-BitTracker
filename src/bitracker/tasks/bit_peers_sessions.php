<?php
/**
 * @file		bit_temp_records.php 	Task to clear out dynamic download urls
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 *
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-02-08 17:20:18 -0500 (Tue, 08 Feb 2011) $
 * @version		v2.5.4
 * $Revision: 7750 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to clear out dynamic download urls
 *
 */
class task_item
{
	/**
	 * Object that stores the parent task manager class
	 *
	 * @var		$class
	 */
	protected $class;
	
	/**
	 * Array that stores the task data
	 *
	 * @var		$task
	 */
	protected $task = array();
	
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $lang;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @param	object		$class			Task manager class object
	 * @param	array		$task			Array with the task data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $class, $task )
	{
		/* Make registry objects */
		$this->registry	= $registry;
		$this->DB		= $this->registry->DB();
		$this->settings	=& $this->registry->fetchSettings();
		$this->lang		= $this->registry->getClass('class_localization');
		
		$this->class	= $class;
		$this->task		= $task;
	}
	
	/**
	 * Run this task
	 *
	 * @return	@e void
	 */
	public function runTask()
	{

            $time = time() - ( 20 * 60 );

			$this->DB->delete( 'bitracker_torrent_peers', "last_action < '{$time}' AND seeder='yes'"  );
			
			$deletedSeeds = intval( $this->DB->getAffectedRows() );

			$this->DB->delete( 'bitracker_torrent_peers', "last_action < '{$time}' AND seeder='no'"  );
			
			$deletedLeechs = intval( $this->DB->getAffectedRows() );

            $deleted = $deletedSeeds + $deletedLeechs;

            if ( $deletedSeeds >= 1)
               {
                 $this->DB->update( 'bitracker_torrent_data', "torrent_seeders=torrent_seeders-'{$deletedSeeds}'", "torrent_infohash='{$this->request['info_hash']}'", false, true );
               }

            if ( $deletedLeechs >= 1)
               {
                 $this->DB->update( 'bitracker_torrent_data', "torrent_leechers=torrent_leechers-'{$deletedLeechs}'", "torrent_infohash='{$this->request['info_hash']}'", false, true );
               }
			
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_bitpeers'], $deleted ) );
		

		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}