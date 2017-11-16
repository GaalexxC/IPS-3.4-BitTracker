<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * AJAX mark category as read
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_ajax_markasread extends ipsAjaxCommand
{
	/**
	 * IPS command execution
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$cat_id			= intval( $this->request['catid'] );
        $cat_data		= $this->registry->getClass('categories')->cat_lookup[ $cat_id ];
        $children		= $this->registry->getClass('categories')->getChildren( $cat_data['cid'] );
        $save			= array();

        //-----------------------------------------
        // Check
        //-----------------------------------------
        
        if ( ! $cat_data['cid'] )
        {
        	$this->returnJsonError( 'markread_no_id' );
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


		$this->returnJsonArray( array( 'result' => 'success') );
	}
}