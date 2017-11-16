<?php
/**
 * @file		timestamp.php 	Provides method related to timestamps for bitracker
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 *
 * $Author: ips_terabyte $
 * @since		06 March 2012
 * $LastChangedDate: 2012-04-09 12:41:32 -0400 (Mon, 09 Apr 2012) $
 * @version		v2.5.4
 * $Revision: 10581 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * @class		public_bitracker_ajax_timestamp
 * @brief		Provides method related to timestamps for bitracker
 */
class public_bitracker_ajax_timestamp extends ipsAjaxCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->returnString( IPS_UNIX_TIME_NOW );
	}
}