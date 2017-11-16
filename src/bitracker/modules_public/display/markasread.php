<?php
/**
 * @file		markasread.php 	Mark category as read for IP.bitracker
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 *
 * $Author: ips_terabyte $
 * @since		1st March 2005
 * $LastChangedDate: 2011-07-08 15:07:09 -0400 (Fri, 08 Jul 2011) $
 * @version		v2.5.4
 * $Revision: 9189 $
 */
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}


/**
 *
 * @class		public_bitracker_display_markasread
 * @brief		Mark category as read for IP.bitracker
 */
class public_bitracker_display_markasread extends ipsCommand
{
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch( $this->request['marktype'] )
		{
			default:
			case 'cat':
				return $this->markCategoryAsRead();
			break;
		}
	}
	
	/**
	 * Mark a category (and possibly subcategories) as read
	 *
	 * @return	@e void
	 */
	public function markCategoryAsRead()
	{
		//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$cat_id			= intval( $this->request['catid'] );
        $return_to_id	= intval( $this->request['returntocatid'] );
        $cat_data		= $this->registry->getClass('categories')->cat_lookup[ $cat_id ];
        $children		= $this->registry->getClass('categories')->getChildren( $cat_data['cid'] );
        $save			= array();
        
        //-----------------------------------------
        // Check
        //-----------------------------------------
        
        if ( ! $cat_data['cid'] )
        {
        	$this->registry->getClass('output')->showError( 'markread_no_id', 10340, null, null, 404 );
        }
        
        //-----------------------------------------
        // Come from the index? Add kids
        //-----------------------------------------
       
        if ( $this->request['i'] )
        {
			if ( is_array( $children ) and count($children) )
			{
				foreach( $children as $id )
				{
					$this->registry->classItemMarking->markRead( array( 'forumID' => $id ) );
				}
			}
        }
        
        //-----------------------------------------
        // Add in the current forum...
        //-----------------------------------------
        
        $this->registry->classItemMarking->markRead( array( 'forumID' => $cat_id ) );
        
        //-----------------------------------------
        // Save it...
        //-----------------------------------------
        
        if ( $this->memberData['member_id'] )
        {
        	$this->registry->classItemMarking->writeMyMarkersToDB();
        }
		
		//-----------------------------------------	
        // Where are we going back to?
        //-----------------------------------------
        
        if ( $return_to_id )
        {
        	//-----------------------------------------
        	// Its a sub forum, lets go redirect to parent forum
        	//-----------------------------------------
        	
        	$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=bitracker&amp;showcat=" . $return_to_id );
        }
        else
        {
        	$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=bitracker" );
        }
	}
}