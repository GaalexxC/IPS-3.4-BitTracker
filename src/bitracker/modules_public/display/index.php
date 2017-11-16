<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Index page for bit
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

class public_bitracker_display_index extends ipsCommand
{
	/**
	 * Stored temporary output
	 *
	 * @access	protected
	 * @var 	string 				Page output
	 */
	protected $output				= "";

	/**
	 * Member can add to a category
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $canadd				= false;

	/**
	 * Member can moderate a category
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $canmod				= false;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------
		// Page title and navigation bar
		//-------------------------------------------
		
		$this->registry->output->addNavigation( IPSLib::getAppTitle('bitracker'), 'app=bitracker', 'false', 'app=bitracker' );
		$this->registry->output->setTitle( IPSLib::getAppTitle('bitracker') . ' - ' . $this->settings['board_name'] );

		//-------------------------------------------
		// Check permissions
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
		{
			if( count($this->registry->getClass('categories')->cat_lookup) == 0 )
			{
				$this->registry->output->showError( 'no_bitracker_cats_created', 10871, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_bitracker_permissions', 10872, null, null, 403 );
			}
		}
		else
		{
			if( count( $this->registry->getClass('categories')->member_access['add'] ) > 0 )
			{
				$this->canadd = true;
			}
			
			$this->canmod = $this->registry->getClass('bitFunctions')->isModerator();
		}
		
		if( count($this->registry->getClass('categories')->cat_cache[ 0 ]) == 0 )
		{
			$this->registry->output->showError( 'no_bitracker_categories', 10873, null, null, 403 );
		}
		
		//-----------------------------------------
		// Show portal or index page?
		//-----------------------------------------
		
		if( $this->settings['bit_use_index'] == 2)
		{
			$this->_showPortal();
		}
		elseif( $this->settings['bit_use_index'] == 3 )
		{
			$this->_showClassic();
		}
		elseif( $this->settings['bit_use_index'] == 1 )
		{
			$this->_showIndex();
		}
		
		//-------------------------------------------
		// Grab stats
		//-------------------------------------------
		
		$this->output .= $this->registry->getClass('bitFunctions')->getStats();

		//-------------------------------------------
		// Output
		//-------------------------------------------
		
		IPSCookie::set('modfileids', '', 0);	
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Show portal index
	 *
	 * @return	@e void
	 */	
	protected function _showPortal()
	{
		//-----------------------------------------
		// Categories
		//-----------------------------------------
		
		$category_rows	= $this->getCategoryRows();
		$categories		= $this->registry->getClass('categories')->member_access['show'];
		
		//-----------------------------------------
		// Get bitracker library and API
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/api_core.php', 'apiCore' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/api/api_bit.php', 'apiBitracker', 'bitracker' );
		
		$bit_api		= new $classToLoad();
		$bit_api->init();
		
		//-----------------------------------------
		// Get generic feeds....
		//-----------------------------------------
		
		$feeds	= array();
		
		$feeds['whatsnew']	= $bit_api->returnBitracker( 0, 24, true );
		$_screenshotFileIds	= array_keys( $feeds['whatsnew'] );
		
		if ( $this->settings['rating_feed_enabled'] )
		{
			$feeds['highrated']	= $bit_api->returnBitracker( 0, 24, true, "file_rating DESC, " . $this->DB->buildLength( "file_votes" ) . " DESC" );
			$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['highrated'] ) );
		}
		else
		{
			$feeds['highrated'] = array();
		}

		$searchRange	= time() - ( 86400 * 30);
		
		if( IPSLib::appIsInstalled( 'nexus' ) && $this->settings['bit_nexus_on'] )		
		{			
			//-----------------------------------------
			// We want to order by number of purchases
			//-----------------------------------------
			
			$categories = $this->registry->getClass('categories')->member_access['show'];
			$paidFiles	= array();
			
			$this->DB->build( array(
									'select'	=> "COUNT(*) as purchases, p.ps_item_id", 
									'from'		=> array( 'nexus_purchases' => 'p' ), 
									'group'		=> 'p.ps_item_id',
									'where'		=> "p.ps_app='bitracker' AND p.ps_type='file' AND f.file_open=1 AND f.file_cost != 0 AND f.file_cat IN(" . implode( ',', $categories ) . ") AND f.file_submitted > {$searchRange}", 
									'order'		=> "purchases DESC",
									'limit'		=> array( 0, 5 ),
									'add_join'	=> array(
														array(
															'from'	=> array( 'bitracker_files' => 'f' ),
															'type'	=> 'left',
															'where'	=> 'f.file_id=p.ps_item_id',
															),
														)
							)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$paidFiles[ $r['ps_item_id'] ]	= $r['ps_item_id'];
			}
			
			// If there's no paid files, don't show Top Free & Top Paid, just show Top bitracker
			if( count( $paidFiles ) )
			{
				$feeds['topfree']	= $bit_api->returnBitracker( 0, 5, true, "file_bitracker DESC", array( "file_cost=0", "file_submitted > {$searchRange}" ) );
				$toppaid	= $bit_api->returnBitracker( 0, 5, true, null, array( "file_id IN(" . implode( ',', $paidFiles ) . ')' ) );

				foreach( $paidFiles as $_file )
				{
					foreach( $toppaid as $paidFile )
					{
						if( $paidFile['file_id'] == $_file )
						{
							$feeds['toppaid'][ $paidFile['file_id'] ]	= $paidFile;
						}
					}
				}
				
				$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['topfree'] ) );
				$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['toppaid'] ) );
			}
			else
			{
				$feeds['topfiles']	= $bit_api->returnBitracker( 0, 10, true, "file_bitracker DESC", array( "file_submitted > {$searchRange}" ) );
				
				$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['topfiles'] ) );
			}
		}
		else
		{
			$feeds['topfiles']	= $bit_api->returnBitracker( 0, 10, true, "file_bitracker DESC", array( "file_submitted > {$searchRange}" ) );
			
			$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['topfiles'] ) );
		}
		
		//-----------------------------------------
		// Now get top authors
		//-----------------------------------------
		
		$_authors	= array();
		$_authorIds	= array();
		
		$this->DB->build( array( 'select' => 'file_submitter, SUM(file_bitracker) as totalfiles', 'from' => 'bitracker_files', 'where' => 'file_open=1 AND file_cat IN (' . implode( ',', $categories ) . ') AND file_submitted > ' . $searchRange, 'order' => 'totalfiles DESC', 'limit' => array( 0, 5 ), 'group' => 'file_submitter' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( !$r['file_submitter'] )
			{
				continue;
			}
			
			$_authors[]		= $r;
			$_authorIds[]	= $r['file_submitter'];
		}
		
		$members	= IPSMember::load( $_authorIds, 'core,extendedProfile,groups' );
		
		foreach( $_authors as $_author )
		{
			$_member				= $members[ $_author['file_submitter'] ];
			$_member['total_files']	= $_author['totalfiles'];
			
			$feeds['topusers'][]	= IPSMember::buildDisplayData( $_member, array( 'reputation' => 0, 'warn' => 0 ) );
		}
		
		//-----------------------------------------
		// And get top liked files
		//-----------------------------------------

		$_files		= array();
		$_fileIds	= array();
		
		$this->DB->build( array(
							'select'	=> 'l.like_rel_id, COUNT(*) as totalliked', 
							'from'		=> array( 'core_like' => 'l' ), 
							'order'		=> 'totalliked DESC',
							'where'		=> "l.like_app='bitracker' AND l.like_area='files' AND l.like_visible=1 AND f.file_id " . $this->DB->buildIsNull(false) . ' AND f.file_open=1 AND f.file_cat IN (' . implode( ',', $categories ) . ')',
							'limit'		=> array( 0, 18 ),
							'group'		=> 'like_rel_id',
							'add_join'	=> array(
												array(
													'select'	=> 'f.file_id',
													'from'		=> array( 'bitracker_files' => 'f' ),
													'where'		=> 'f.file_id=l.like_rel_id',
													'type'		=> 'left',
													)
												)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( !$r['file_id'] )
			{
				continue;
			}

			$_files[ $r['like_rel_id'] ]	= array( 'total_liked' => $r['totalliked'] );
			$_fileIds[]						= $r['like_rel_id'];
		}
		
		if( count($_fileIds) )
		{
			$this->DB->build( array( 'select'	=> 'f.*',
									 'from'		=> array( 'bitracker_files' => 'f' ),
									 'where'	=> 'f.file_id IN(' . implode( ',', $_fileIds ) . ') AND f.file_open=1 AND f.file_cat IN (' . implode( ',', $categories ) . ')',
									 'add_join'	=> array( 
										 				array(
										 						'type'		=> 'left',
									 							'select'	=> 'c.cname as category_name, c.cname_furl',
									 							'from'		=> array( 'bitracker_categories' => 'c' ),
									 							'where'		=> "c.cid=f.file_cat",
										 					),
										 				array(
									 							'type'		=> 'left',
									 							'select'	=> 'm.*',
									 							'from'		=> array( 'members' => 'm' ),
									 							'where'		=> "m.member_id=f.file_submitter",
										 					),
										 				array(
									 							'type'		=> 'left',
									 							'select'	=> 'pp.*',
									 							'from'		=> array( 'profile_portal' => 'pp' ),
									 							'where'		=> "m.member_id=pp.pp_member_id",
										 					),
									 					),
							)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$r['_isRead']				= $this->registry->classItemMarking->isRead( array( 'forumID' => $r['file_cat'], 'itemID' => $r['file_id'], 'itemLastUpdate' => $r['file_updated'] ), 'bitracker' );
				$r['members_display_name']	= $r['members_display_name'] ? $r['members_display_name'] : $this->lang->words['global_guestname'];
				//$r							= IPSMember::buildDisplayData( $r );
				$_files[ $r['file_id'] ]	= array_merge( $_files[ $r['file_id'] ], $r );
			}
		}
		
		$feeds['watched']	= $_files;
		
		$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['watched'] ) );
		
		//-----------------------------------------
		// Get featured file
		//-----------------------------------------

		$feeds['featured']	= array();
		$this->DB->build( array(	'select'	=> 'f.*',
									'from'		=> array( 'bitracker_files' => 'f' ),
									'where'		=> 'f.file_featured=1 AND f.file_open=1 AND f.file_cat IN (' . implode( ',', $categories ) . ')',
									'limit'		=> array( 0, 20 ),
									'order'		=> 'f.file_id DESC',
									'add_join'	=> array( 
										 				array(
										 						'type'		=> 'left',
									 							'select'	=> 'c.cname as category_name, c.cname_furl',
									 							'from'		=> array( 'bitracker_categories' => 'c' ),
									 							'where'		=> "c.cid=f.file_cat",
										 					),
										 				array(
									 							'type'		=> 'left',
									 							'select'	=> 'm.*',
									 							'from'		=> array( 'members' => 'm' ),
									 							'where'		=> "m.member_id=f.file_submitter",
										 					),
										 				array(
									 							'type'		=> 'left',
									 							'select'	=> 'pp.*',
									 							'from'		=> array( 'profile_portal' => 'pp' ),
									 							'where'		=> "m.member_id=pp.pp_member_id",
										 					),
									 					),
							)		);
		$outer = $this->DB->execute();

		while( $r = $this->DB->fetch($outer) )
		{
			if( $r['file_id'] )
			{
				$r['_comments']	= $r['file_comments'];
				
				//-----------------------------------------
				// Purchased?
				//-----------------------------------------
				
				$r['_purchased']		= 'NO_PURCHASE';
				$r['_renewal_terms']	= '';
				
				if ( $this->memberData['bit_bypass_paid'] or $r['file_submitter'] == $this->memberData['member_id'] )
				{
					$r['_purchased']	= 'ACTIVE';
				}
				elseif ( ( $r['file_cost'] or $r['file_nexus'] ) and IPSLib::appIsInstalled('nexus') )
				{
					require_once( IPSLib::getAppDir('nexus') . '/sources/nexusApi.php' );/*noLibHook*/
		
					if ( $r['file_cost'] )
					{
						$r['_purchased']	= ( nexusApi::itemIsPurchased( $this->memberData['member_id'], 'bitracker', 'file', $r['file_id'] ) );
					}
					elseif ( $r['file_nexus'] )
					{
						$items	= explode( ',', $r['file_nexus'] );
		
						while ( $r['_purchased'] == 'NO_PURCHASE' and !empty( $items ) )
						{
							$id			= array_pop( $items );
							$r['_purchased']	= ( nexusApi::itemIsPurchased( $this->memberData['member_id'], 'nexus', 'package', $id ) );
						}
					}
					
					if ( $r['file_renewal_term'] )
					{
						$r['_renewal_terms'] = $this->lang->words['renew_term_prefix'] . ipsRegistry::getAppClass( 'nexus' )->formatRenewalTerms( array(
																													'unit'	=> $r['file_renewal_units'],
																													'term'	=> $r['file_renewal_term'],
																													'price'	=> $r['file_renewal_price']
																											)		);
					}
				}
				
				if( $this->settings['bit_dynamic_urls'] )
				{
					$insert	= array(
									'url_id'		=> md5( uniqid( microtime(), true ) ),
									'url_file'		=> $r['file_id'],
									'url_ip'		=> $this->member->ip_address,
									'url_created'	=> time(),
									'url_expires'	=> $this->settings['bit_dynamic_expire'] ? time() + ( 60 * $this->settings['bit_dynamic_expire'] ) : time() + ( 60 * 60 * 24 * 7 ),
									);
			
					$this->DB->insert( 'bitracker_urls', $insert );
					
					$r['_hash']	= $insert['url_id'];
				}
				
				$_screenshotFileIds[]	= $r['file_id'];
				$feeds['featured'][]	= $r;
			}
		}

		
		//-----------------------------------------
		// Grab screenshot info
		//-----------------------------------------
			
		$_screenshotFileIds	= array_unique($_screenshotFileIds);
		
		if( count($_screenshotFileIds) )
		{
			$_recordIds	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id IN(" . implode( ',', $_screenshotFileIds ) . ") AND record_type IN('ssupload','sslink') AND record_backup=0" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( !isset($_recordIds[ $r['record_file_id'] ]) OR $r['record_default'] )
				{
					$_recordIds[ $r['record_file_id'] ]	= $r;
				}
			}
		}


		$this->output	.= $this->registry->getClass('output')->getTemplate('bitracker')->bitrackerPortal( $this->canadd, $this->canmod, $category_rows, $feeds, $_recordIds );
	}

	/**
	 * Show Classic index
	 *
	 * @return	@e void
	 */	
	protected function _showClassic()
	{
		//-----------------------------------------
		// Categories
		//-----------------------------------------
		
		$category_rows	= $this->getCategoryRows();
		$categories		= $this->registry->getClass('categories')->member_access['show'];
		
		//-----------------------------------------
		// Get bitracker library and API
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/api_core.php', 'apiCore' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/api/api_bit.php', 'apiBitracker', 'bitracker' );
		
		$bit_api		= new $classToLoad();
		$bit_api->init();

		//-------------------------------------------
		// Add files from all children to parents....
		//-------------------------------------------
        $c_num = count($category_rows);
		foreach( $category_rows as $cat_rows )
		{
          if($cat_rows['cparent'] == 0){
          $_children = $this->registry->getClass('categories')->getChildren( $cat_rows['cid'] );

		              $this->DB->build( array(	'select'	=> 'f.file_id, f.file_name, f.file_views, f.file_bitracker, f.file_submitted, f.file_updated, f.file_new, f.file_name_furl',
									'from'		=> array( 'bitracker_files' => 'f' ),
									'where'		=> 'f.file_open=1 AND f.file_cat IN (' . implode( ',', $_children ) . ')',
									'limit'		=> array( 0, 5 ),
									'order'		=> 'f.file_submitted DESC',
									'add_join'	=> array( 
														 array(
															    'select'	=> 'm.member_id, m.members_display_name, m.members_seo_name, m.member_group_id',
																'from'	=> array( 'members' => 'm' ),
																'where'	=> 'm.member_id=f.file_submitter',
																'type'	=> 'left',
																	),
														 array(
																'select'=> 't.torrent_id, t.torrent_seeders, t.torrent_leechers, t.torrent_filesize',
																'from'	=> array( 'bitracker_torrent_data' => 't' ),
																'where'	=> 't.torrent_id=f.file_id',
																'type'	=> 'left',
																	),
												
															  )
									)  ); 
                       
		              $outer = $this->DB->execute();
                      $i=0;
		              while( $r = $this->DB->fetch($outer) )
		              {
			            if( $r['file_id'] )
			            { 
						 $r['seeders'] = $this->registry->getClass('bitFunctions')->countPeers($r['file_id']);
			             $r['leechers'] = $this->registry->getClass('bitFunctions')->countPeers($r['file_id'], FALSE );
                         $r['cat_name'] = $cat_rows['cname'];
			             $cat_rows['new_files_' . $i] = $r;
                        }
                      $i++;
                      }
         $category_rows[] = $cat_rows;
         }
        $category_rows['c_count'] = $c_num;    
      }


		
		//-----------------------------------------
		// Get generic feeds....
		//-----------------------------------------
		
		$feeds	= array();		 			
	
		$feeds['whatsnew']	= $bit_api->returnBitracker( 0, 18, true );
		$_screenshotFileIds	= array_keys( $feeds['whatsnew'] );
		
		if ( $this->settings['rating_feed_enabled'] )
		{
			$feeds['highrated']	= $bit_api->returnBitracker( 0, 18, true, "file_rating DESC, " . $this->DB->buildLength( "file_votes" ) . " DESC" );
			$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['highrated'] ) );
		}
		else
		{
			$feeds['highrated'] = array();
		}

		$searchRange	= time() - ( 86400 * 30);
		
		if( IPSLib::appIsInstalled( 'nexus' ) && $this->settings['bit_nexus_on'] )		
		{			
			//-----------------------------------------
			// We want to order by number of purchases
			//-----------------------------------------
			
			$categories = $this->registry->getClass('categories')->member_access['show'];
			$paidFiles	= array();
			
			$this->DB->build( array(
									'select'	=> "COUNT(*) as purchases, p.ps_item_id", 
									'from'		=> array( 'nexus_purchases' => 'p' ), 
									'group'		=> 'p.ps_item_id',
									'where'		=> "p.ps_app='bitracker' AND p.ps_type='file' AND f.file_open=1 AND f.file_cost != 0 AND f.file_cat IN(" . implode( ',', $categories ) . ") AND f.file_submitted > {$searchRange}", 
									'order'		=> "purchases DESC",
									'limit'		=> array( 0, 5 ),
									'add_join'	=> array(
														array(
															'from'	=> array( 'bitracker_files' => 'f' ),
															'type'	=> 'left',
															'where'	=> 'f.file_id=p.ps_item_id',
															),
														)
							)		);
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$paidFiles[ $r['ps_item_id'] ]	= $r['ps_item_id'];
			}
			
			// If there's no paid files, don't show Top Free & Top Paid, just show Top bitracker
			if( count( $paidFiles ) )
			{
				$feeds['topfree']	= $bit_api->returnBitracker( 0, 5, true, "file_bitracker DESC", array( "file_cost=0", "file_submitted > {$searchRange}" ) );
				$toppaid	= $bit_api->returnBitracker( 0, 5, true, null, array( "file_id IN(" . implode( ',', $paidFiles ) . ')' ) );

				foreach( $paidFiles as $_file )
				{
					foreach( $toppaid as $paidFile )
					{
						if( $paidFile['file_id'] == $_file )
						{
							$feeds['toppaid'][ $paidFile['file_id'] ]	= $paidFile;
						}
					}
				}
				
				$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['topfree'] ) );
				$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['toppaid'] ) );
			}
			else
			{
				$feeds['topfiles']	= $bit_api->returnBitracker( 0, 10, true, "file_bitracker DESC", array( "file_submitted > {$searchRange}" ) );
				
				$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['topfiles'] ) );
			}
		}
		else
		{
			$feeds['topfiles']	= $bit_api->returnBitracker( 0, 10, true, "file_bitracker DESC", array( "file_submitted > {$searchRange}" ) );
			
			$_screenshotFileIds	= array_merge( $_screenshotFileIds, array_keys( $feeds['topfiles'] ) );
		}
		
		//-----------------------------------------
		// Now get top authors
		//-----------------------------------------
		
		$_authors	= array();
		$_authorIds	= array();
		
		$this->DB->build( array( 'select' => 'file_submitter, COUNT(file_submitter) as totalfiles', 'from' => 'bitracker_files', 'where' => 'file_open=1 AND file_cat IN (' . implode( ',', $categories ) . ') AND file_submitted > ' . $searchRange, 'order' => 'totalfiles DESC', 'limit' => array( 0, 5 ), 'group' => 'file_submitter' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( !$r['file_submitter'] )
			{
				continue;
			}
			
			$_authors[]		= $r;
			$_authorIds[]	= $r['file_submitter'];
		}
		
		$members	= IPSMember::load( $_authorIds, 'core,extendedProfile,groups' );
		
		foreach( $_authors as $_author )
		{
			$_member				= $members[ $_author['file_submitter'] ];
			$_member['total_files']	= $_author['totalfiles'];
			
			$feeds['topusers'][]	= IPSMember::buildDisplayData( $_member, array( 'reputation' => 0, 'warn' => 0 ) );
		}
		
		//-----------------------------------------
		// And get top liked files
		//-----------------------------------------

		$_files		= array();
		$_fileIds	= array();
		
		$this->DB->build( array(
							'select'	=> 'l.like_rel_id, COUNT(*) as totalliked', 
							'from'		=> array( 'core_like' => 'l' ), 
							'order'		=> 'totalliked DESC',
							'where'		=> "l.like_app='bitracker' AND l.like_area='files' AND l.like_visible=1 AND f.file_id " . $this->DB->buildIsNull(false) . ' AND f.file_open=1 AND f.file_cat IN (' . implode( ',', $categories ) . ')',
							'limit'		=> array( 0, 18 ),
							'group'		=> 'like_rel_id',
							'add_join'	=> array(
												array(
													'select'	=> 'f.file_id',
													'from'		=> array( 'bitracker_files' => 'f' ),
													'where'		=> 'f.file_id=l.like_rel_id',
													'type'		=> 'left',
													)
												)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( !$r['file_id'] )
			{
				continue;
			}

			$_files[ $r['like_rel_id'] ]	= array( 'total_liked' => $r['totalliked'] );
			$_fileIds[]						= $r['like_rel_id'];
		}
		
;
		
		//-----------------------------------------
		// Get featured file
		//-----------------------------------------

		$feeds['featured']	= array();
		$this->DB->build( array(	'select'	=> 'f.*',
									'from'		=> array( 'bitracker_files' => 'f' ),
									'where'		=> 'f.file_featured=1 AND f.file_open=1 AND f.file_cat IN (' . implode( ',', $categories ) . ')',
									'limit'		=> array( 0, 20 ),
									'order'		=> 'f.file_id DESC',
									'add_join'	=> array( 
										 				array(
										 						'type'		=> 'left',
									 							'select'	=> 'c.cname as category_name, c.cname_furl',
									 							'from'		=> array( 'bitracker_categories' => 'c' ),
									 							'where'		=> "c.cid=f.file_cat",
										 					),
										 				array(
									 							'type'		=> 'left',
									 							'select'	=> 'm.*',
									 							'from'		=> array( 'members' => 'm' ),
									 							'where'		=> "m.member_id=f.file_submitter",
										 					),
										 				array(
									 							'type'		=> 'left',
									 							'select'	=> 'pp.*',
									 							'from'		=> array( 'profile_portal' => 'pp' ),
									 							'where'		=> "m.member_id=pp.pp_member_id",
										 					),
									 					),
							)		);
		$outer = $this->DB->execute();

		while( $r = $this->DB->fetch($outer) )
		{
			if( $r['file_id'] )
			{
				$r['_comments']	= $r['file_comments'];
				
				//-----------------------------------------
				// Purchased?
				//-----------------------------------------
				
				$r['_purchased']		= 'NO_PURCHASE';
				$r['_renewal_terms']	= '';
				
				if ( $this->memberData['bit_bypass_paid'] or $r['file_submitter'] == $this->memberData['member_id'] )
				{
					$r['_purchased']	= 'ACTIVE';
				}
				elseif ( ( $r['file_cost'] or $r['file_nexus'] ) and IPSLib::appIsInstalled('nexus') )
				{
					require_once( IPSLib::getAppDir('nexus') . '/sources/nexusApi.php' );/*noLibHook*/
		
					if ( $r['file_cost'] )
					{
						$r['_purchased']	= ( nexusApi::itemIsPurchased( $this->memberData['member_id'], 'bitracker', 'file', $r['file_id'] ) );
					}
					elseif ( $r['file_nexus'] )
					{
						$items	= explode( ',', $r['file_nexus'] );
		
						while ( $r['_purchased'] == 'NO_PURCHASE' and !empty( $items ) )
						{
							$id			= array_pop( $items );
							$r['_purchased']	= ( nexusApi::itemIsPurchased( $this->memberData['member_id'], 'nexus', 'package', $id ) );
						}
					}
					
					if ( $r['file_renewal_term'] )
					{
						$r['_renewal_terms'] = $this->lang->words['renew_term_prefix'] . ipsRegistry::getAppClass( 'nexus' )->formatRenewalTerms( array(
																													'unit'	=> $r['file_renewal_units'],
																													'term'	=> $r['file_renewal_term'],
																													'price'	=> $r['file_renewal_price']
																											)		);
					}
				}
				
				if( $this->settings['bit_dynamic_urls'] )
				{
					$insert	= array(
									'url_id'		=> md5( uniqid( microtime(), true ) ),
									'url_file'		=> $r['file_id'],
									'url_ip'		=> $this->member->ip_address,
									'url_created'	=> time(),
									'url_expires'	=> $this->settings['bit_dynamic_expire'] ? time() + ( 60 * $this->settings['bit_dynamic_expire'] ) : time() + ( 60 * 60 * 24 * 7 ),
									);
			
					$this->DB->insert( 'bitracker_urls', $insert );
					
					$r['_hash']	= $insert['url_id'];
				}
				
				$_screenshotFileIds[]	= $r['file_id'];
				$feeds['featured'][]	= $r;
			}
		}

		
		//-----------------------------------------
		// Grab screenshot info
		//-----------------------------------------
			
		$_screenshotFileIds	= array_unique($_screenshotFileIds);
		
		if( count($_screenshotFileIds) )
		{
			$_recordIds	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id IN(" . implode( ',', $_screenshotFileIds ) . ") AND record_type IN('ssupload','sslink') AND record_backup=0" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( !isset($_recordIds[ $r['record_file_id'] ]) OR $r['record_default'] )
				{
					$_recordIds[ $r['record_file_id'] ]	= $r;
				}
			}
		}

		$this->output	.= $this->registry->getClass('output')->getTemplate('bitracker')->indexClassic( $this->canadd, $this->canmod, $category_rows, $feeds, $_recordIds );
	}


	
	/**
	 * Show category index
	 *
	 * @return	@e void
	 */	
	protected function _showIndex()
	{


		//-----------------------------------------
		// Categories
		//-----------------------------------------
		
		$categoryRows = $this->getCategoryRows( true );
		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker')->indexPage( $this->canadd, $this->canmod, $categoryRows );
	}

	/**
	 * Get the category rows
	 *
	 * @param	boolean		$loadMembers		Loads last submitters data, disabled by default
	 * @return	@e void
	 */	
	protected function getCategoryRows( $loadMembers=false )
	{
		/* Init vars */
		$category_rows	= array();
		$member_ids		= array();
		
		if( count( $this->registry->getClass('categories')->cat_cache[ 0 ] ) > 0 )
		{
			foreach( $this->registry->getClass('categories')->cat_cache[ 0 ] as $cid => $cinfo )
			{
				if( in_array( $cid, $this->registry->getClass('categories')->member_access['show'] ) )
				{
					$cinfo['can_approve']		= $this->registry->getClass('bitFunctions')->checkPerms( array( 'file_cat' => $cid ) );
					$cinfo['subcategories']		= "";
					
					$rtime						= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $cinfo['cid'] ), 'bitracker' );
					
					if( !isset($cinfo['_has_unread']) )
					{
						$cinfo['_has_unread']	= ( $cinfo['cfileinfo']['date'] && $cinfo['cfileinfo']['date'] > $rtime ) ? 1 : 0;
					}

					if( count($this->registry->getClass('categories')->subcat_lookup[$cid]) > 0 )
					{
						$sub_links = array();
						
						foreach( $this->registry->getClass('categories')->subcat_lookup[$cid] as $blank_key => $subcat_id )
						{
							if( in_array( $subcat_id, $this->registry->getClass('categories')->member_access['show'] ) )
							{
								$subcat_data = $this->registry->getClass('categories')->cat_lookup[ $subcat_id ];
							
								if ( is_array( $subcat_data ) )
								{
									$subcattime	= $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $subcat_data['cid'] ), 'bitracker' );
									
									if( !isset($subcat_data['new']) )
									{
										$subcat_data['new']	= ( $subcat_data['cfileinfo']['date'] && $subcat_data['cfileinfo']['date'] > $subcattime ) ? 1 : 0;
									}

									$sub_links[] = $subcat_data;

								}
							}
						}
						
						$cinfo['subcategories'] = $sub_links;
					}
					
					/* Save our member IDs */
					if ( $cinfo['cfileinfo']['fid'] && $cinfo['cfileinfo']['mid'] )
					{
						$member_ids[ $cinfo['cfileinfo']['mid'] ] = $cinfo['cfileinfo']['mid'];
					}
					
					$category_rows[] = $cinfo;
				}
			}

			if( !count($category_rows) )
			{
				$this->registry->output->showError( 'no_permitted_categories', 10874, null, null, 403 );
			}
			
			/* Got members to parse? */
			if ( $loadMembers && count($member_ids) )
			{
				$_members = IPSMember::load( $member_ids, 'members,profile_portal' );
				
				if ( is_array($_members) && count($_members) )
				{
					foreach( $category_rows as $idx => $cdata )
					{
						if ( isset($_members[ $cdata['cfileinfo']['mid'] ]) )
						{
							$category_rows[ $idx ]['cfileinfo']['member'] = IPSMember::buildDisplayData( $_members[ $cdata['cfileinfo']['mid'] ] );
						}
					}
				}
			}
		}
		
		return $category_rows;
	}
}