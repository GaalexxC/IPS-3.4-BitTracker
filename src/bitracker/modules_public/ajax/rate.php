<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.bitracker save rating
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_ajax_rate extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$id 	= intval($this->request['id']);
		$vote 	= intval($this->request['rating']);
		
		$vote = $vote > 5 ? 5 : ( $vote < 1 ? 1 : $vote );

		if( !$id OR !$vote )
		{
			if( $this->request['xml'] == 1 )
			{
				$this->returnJsonError( $this->lang->words['ajax_rate_error'] );
				exit;
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url_with_app'] );
			}
		}
		
		$file = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			if( $this->request['xml'] == 1 )
			{
				$this->returnJsonError( $this->lang->words['ajax_rate_error'] );
				exit;
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url_with_app'] );
			}
		}
		
		if( count($this->registry->getClass('categories')->member_access['rate']) == 0 OR !in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['rate'] ) )
		{
			if( $this->request['xml'] == 1 )
			{
				$this->returnJsonError( $this->lang->words['ajax_rate_error'] );
				exit;
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['cannot_rate_file'], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
			}
		}
		
		if( $this->memberData['member_id'] && $this->settings['must_dl_rate'] )
		{
			$download = $this->DB->buildAndFetch( array( 'select' => "count(*) as count", 'from' => 'bitracker_bitracker', 'where' => "dfid=" . $file['file_id'] . " AND dmid=" . $this->memberData['member_id'] ) );
			
			if( !$download['count'] )
			{
				if( $this->request['xml'] == 1 )
				{
					$this->returnJsonError( $this->lang->words['ajax_rate_error'] );
					exit;
				}
				else
				{
					$this->registry->output->redirectScreen( $this->lang->words['cannot_rate_file'], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
				}				
			}			
		}			
		
		$the_votes	= array();
		$type		= 'new';
		
		if( $file['file_votes'] )
		{
			$the_votes = unserialize( $file['file_votes'] );
		}
		
		if( is_array($the_votes) AND count($the_votes) > 0 )
		{
			if( !isset($the_votes[ $this->memberData['member_id'] ]) )
			{
				$the_votes[ $this->memberData['member_id'] ] = $vote;
			}
			else
			{
				if( $this->memberData['g_topic_rate_setting'] == 2 )
				{
					$the_votes[ $this->memberData['member_id'] ] = $vote;
					$type	= 'updated';
				}
				else
				{
					if( $this->request['xml'] == 1 )
					{
						$this->returnJsonArray( array( 'error_key' => 'topic_rated_already' ) );
					}
					else
					{
						$this->registry->output->redirectScreen( sprintf( $this->lang->words['already_voted'], $the_votes[ $this->memberData['member_id'] ] ), $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
					}
				}
			}
		}
		else
		{
			$the_votes[ $this->memberData['member_id'] ] = $vote;
		}
		
		$num_votes	= count($the_votes);
		$vote_ttl	= array_sum($the_votes);
		$final_vote	= 0;

		if( $num_votes > 0 )
		{
			$final_vote = round( $vote_ttl / $num_votes );
		}
		
		$vote_string = serialize($the_votes);
		
		$this->DB->update( "bitracker_files", array( 'file_rating' => $final_vote, 'file_votes' => $vote_string ), "file_id=" . $id );
		
		if( $this->request['xml'] == 1 )
		{
			$return	= array(
							'rating'	=> $vote_ttl,
							'total'		=> $num_votes,
							'average'	=> $final_vote,
							'rated'		=> $type
							);
			$this->returnJsonArray( $return );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['thanks_for_voting'], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
		}
	}
}