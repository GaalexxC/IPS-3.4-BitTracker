<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit file handling interface
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

interface interface_storage
{
	/**
	 * Remove a file
	 *
	 * @access	public
	 * @param	array		Record data
	 * @return	boolean		File removed successfully
	 */	
	public function remove( $record );

	/**
	 * Stores the uploaded files
	 *
	 * @access	public
	 * @param	array 		File information
	 * @return	int			Error number
	 */	
	public function store( $data=array() );
	
	/**
	 * Undo stored files
	 *
	 * @access	public
	 * @return	bool		Rollback complete
	 */	
	public function rollback();
	
	/**
	 * Finalize the storage
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return	boolean
	 */	
	public function commit( $file_id=0 );

	/**
	 * Check for nfo if its allowed/required?
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function checkForNfo();
	
	/**
	 * Check for at least one screenshot
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function checkForScreenshot();
}