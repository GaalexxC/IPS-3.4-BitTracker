<?php
/**
 * @file		bit_views.php 	Task to update file views from the temporary table
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 *
 * $Author: bfarber $
 * @since		-
 * $LastChangedDate: 2012-04-16 20:49:42 -0400 (Mon, 16 Apr 2012) $
 * @version		v2.5.4
 * $Revision: 10598 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to update file views from the temporary table
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
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
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
		//-----------------------------------------
		// Attempt to prevent timeout...
		//-----------------------------------------
		
		$timeStart	= time();
		$ids		= array();
		$complete	= true;

		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );
		
		$this->DB->build( array( 'select'	=> 'COUNT(*) as views, view_fid',
								 'from'		=> 'bitracker_fileviews',
								 'group'	=> 'view_fid',
								)		);
		$outer	= $this->DB->execute();

		while( $row = $this->DB->fetch( $outer ) )
		{
			$this->DB->update( "bitracker_files", 'file_views=file_views+' . intval($row['views']), 'file_id=' . $row['view_fid'], false, true );

			$ids[ $row['view_fid'] ]	= $row['view_fid'];
			
			//-----------------------------------------
			// Running longer than 30 seconds?
			//-----------------------------------------
			
			if( time() - $timeStart > 30 )
			{
				$complete	= false;
				break;
			}
		}
		
		//-----------------------------------------
		// Delete from table
		//-----------------------------------------
		
		if( !$complete )
		{
			if( count($ids) )
			{
				$this->DB->delete( 'bitracker_fileviews', 'view_fid IN(' . implode( ',', $ids ) . ')' );
			}
		}
		else
		{
			$this->DB->delete( 'bitracker_fileviews' );
		}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------

		$this->class->appendTaskLog( $this->task, $this->lang->words['task_bitviews'] );

		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}