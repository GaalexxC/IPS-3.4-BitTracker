<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Bounces a user to the right comment
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.Board
 * @subpackage  Forums 
 *
 * @version		$Rev: 10721 $
 * @since		14th April 2004
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_display_findpost extends ipsCommand
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void		[redirects]
	 */
	public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
		// Find me the comment
		//-----------------------------------------
		
		$pid = intval($this->request['id']);
		
		if ( ! $pid )
		{
			$this->registry->getClass('output')->showError( 'missing_cid_find', 10834, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get topic...
		//-----------------------------------------
		
		$post = $this->DB->buildAndFetch( array( 'select'	=> 'c.*', 
												 'from'		=> array( 'bitracker_comments' => 'c' ), 
												 'where'	=> 'c.comment_id=' . $pid,
												 'add_join'	=> array(
												 					array(
												 						'select'	=> 'f.file_name_furl',
												 						'from'		=> array( 'bitracker_files' => 'f' ),
												 						'where'		=> 'f.file_id=c.comment_fid',
												 						'type'		=> 'left',
												 						)
												 					)
										)		);
		
		if ( ! $post['comment_fid'] )
		{
			$this->registry->getClass('output')->showError( 'missing_cid_find', 10835, null, null, 404 );
		}
		
		$cposts = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'bitracker_comments', 'where' => "comment_fid={$post['comment_fid']} AND comment_id <= {$pid}" ) );							
		
		if ( (($cposts['posts']) % $this->settings['bit_comments_num']) == 0 )
		{
			$pages	= ($cposts['posts']) / $this->settings['bit_comments_num'];
		}
		else
		{
			$number	= ( ($cposts['posts']) / $this->settings['bit_comments_num'] );
			$pages	= ceil( $number);
		}
		
		$st = ($pages - 1) * $this->settings['bit_comments_num'];

		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=bitracker&amp;showfile=" . $post['comment_fid'] . "&amp;st={$st}#comment_" . $pid, $post['file_name_furl'], false, 'bitshowfile' );
 	}
}