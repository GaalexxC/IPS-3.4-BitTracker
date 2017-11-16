<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit category listing
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

class public_bitracker_display_file extends ipsCommand
{
	/**
	 * Stored temporary output
	 *
	 * @var 	string 				Page output
	 */
	protected $output				= "";

	/**
	 * Member can add to a category
	 *
	 * @var 	boolean
	 */
	protected $canadd				= false;

	/**
	 * Member can moderate a category
	 *
	 * @var 	boolean
	 */
	protected $canmod				= false;
	
	/**
	 * Like object
	 *
	 * @var	object
	 */
	protected $_like;

	/**
	 * Comments object
	 *
	 * @var	object
	 */
	protected $_comments;

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------
		// Page title and navigation bar
		//-------------------------------------------
		
		$this->registry->output->addNavigation( IPSLib::getAppTitle('bitracker'), 'app=bitracker', 'false', 'app=bitracker' );

		//-------------------------------------------
		// Check permissions
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
		{
			if( count($this->registry->getClass('categories')->cat_lookup) == 0 )
			{
				$this->registry->output->showError( 'no_bitracker_cats_created', 10864, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_bitracker_permissions', 10865, null, null, 403 );
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
		
		//-----------------------------------------
		// Like class
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$this->_like = classes_like::bootstrap( 'bitracker', 'files' );
		
		//-----------------------------------------
		// Comments class
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/comments/bootstrap.php' );/*noLibHook*/
		$this->_comments = classes_comments_bootstrap::controller( 'bitracker-files' );
		$this->lang->loadLanguageFile( array( 'public_editors' ), 'core' );
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('bitrackerTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'bitrackerTags', classes_tags_bootstrap::run( 'bitracker', 'files' ) );
		}

		//-----------------------------------------
		// Overwrite some comment lang strings
		//-----------------------------------------
		
		foreach( $this->lang->words as $k => $v )
		{
			if( strpos( $k, 'COM_' ) === 0 )
			{
				$this->lang->words[ substr( $k, 4 ) ]	= $v;
			}
		}

		//-------------------------------------------
		// Get the file
		//-------------------------------------------
		
		$file_id = intval($this->request['id']);

		if( !$file_id )
		{
			$this->registry->output->showError( 'file_not_found', 10866, null, null, 404 );
		}
		
		$file = $this->DB->buildAndFetch( array('select'	=> 'f.*, f.file_id as real_file_id',
												'from'		=> array( 'bitracker_files' => 'f' ),
												'where'		=> "f.file_id=" . $file_id,
												'add_join'	=> array(
																	array(
																			'select'	=> 'mem.*',
																			'from'		=> array( 'members' => 'mem' ),
																			'where'		=> 'mem.member_id=f.file_submitter',
																			'type'		=> 'left' ),
																	array(
																			'select'	=> 'pp.*',
																			'from'		=> array( 'profile_portal' => 'pp' ),
																			'where'		=> 'pp.pp_member_id=mem.member_id',
																			'type'		=> 'left' ),
																	array(
																			'select'	=> 'mim.members_display_name as approver_name, mim.members_seo_name as file_approver_seoname, mim.member_group_id as file_approver_group',
																			'from'		=> array( 'members' => 'mim' ),
																			'where'		=> 'mim.member_id=f.file_approver',
																			'type'		=> 'left' ),
																	array(
																			'select'	=> 'cc.*',
																			'from'		=> array( 'bitracker_ccontent' => 'cc' ),
																			'where'		=> 'cc.file_id=f.file_id',
																			'type'		=> 'left' ),
																	$this->registry->bitrackerTags->getCacheJoin( array( 'meta_id_field' => 'f.file_id' ) ),
																	$this->registry->classItemMarking->getSqlJoin( array( 'item_app_key_1' => 'f.file_cat' ), 'bitracker' ),
																	)
											)		);

		$file		= $this->registry->classItemMarking->setFromSqlJoin( $file, 'bitracker' );

		/* Just in case bitracker_ccontent wipes it out */
		$file['file_id']	= $file['real_file_id'];
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( 'file_not_found', 10867, null, null, 404 );
		}

		//-------------------------------------------
		// Check FURL
		//-------------------------------------------
		
		$this->registry->getClass('output')->checkPermalink( $file['file_name_furl'] );

		//-------------------------------------------
		// Verify we can view
		//-------------------------------------------
		
		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		
		if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) OR ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['show'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_view'], 10868, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_permitted_categories', 10869, null, null, 403 );
			}
		}
		
		$canapp		= $this->registry->getClass('bitFunctions')->checkPerms( $file );
		$canedit	= $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanedit', 'bit_allow_edit' );
		$candel		= $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcandel', 'bit_allow_delete' );
		$canbrok	= $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanbrok' );

		if( !$file['file_open'] )
		{
			if( !$canapp AND $this->memberData['member_id'] != $file['file_submitter'] OR !$this->memberData['member_id']  )
			{
				$this->registry->output->showError( 'file_not_found', 10870, null, null, 403 );
			}
		}
		
		//-------------------------------------------
		// Parse out description
		//-------------------------------------------
		
		IPSText::getTextClass( 'bbcode' )->parse_html				= $category['coptions']['opt_html'];
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $category['coptions']['opt_bbcode'];
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'bit_submit';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $file['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $file['mgroup_others'];

		$file['file_desc'] = IPSText::getTextClass('bbcode')->preDisplayParse( $file['file_desc'] );
		
		if( $file['file_broken_reason'] )
		{
			$file['file_broken_reason'] = IPSText::getTextClass('bbcode')->preDisplayParse( $file['file_broken_reason'] );
		}
		
		//-------------------------------------------
		// Parse member info
		//-------------------------------------------
		
		$file = IPSMember::buildDisplayData( $file );

		//-------------------------------------------
		// Rating information
		//-------------------------------------------
		
		if( in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['rate'] ) )
		{
			$file['_allow_rate'] = 1;
		}

		if( $file['file_votes'] )
		{
			$votes		= unserialize($file['file_votes']);
			$totalRate	= 0;
			
			if( is_array($votes) AND count($votes) > 0 )
			{
				foreach( $votes as $k => $v )
				{
					$totalRate	+= $v;
					
					if( $k == $this->memberData['member_id'] )
					{			
						$file['_rating_value'] 	= $v;
						$file['_allow_rate']	= 0;
					}
				}
			}
		}
		
		$file['_rate_cnt']		= count($votes);
		$file['_total_rating']	= ( count( $votes ) ) ? intval( $totalRate / count( $votes ) ) : 0;
		
		$file['file_changelog']	= trim($file['file_changelog']);

		//-------------------------------------------
		// Custom Fields
		//-------------------------------------------
		
    	$file['custom_fields']		= array();
    	
		if( $category['ccfields'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/cfields.php', 'bit_customFields', 'bitracker' );
    		$fields				= new $classToLoad( $this->registry );
    		$fields->file_id	= $file['file_id'];
    		$fields->cat_id		= $category['ccfields'];
    		$fields->cache_data	= $this->cache->getCache('bit_cfields');
    		$fields->file_data	= $file;
    	
    		$fields->init_data( 'view' );
    		$fields->parseToView();
    		
    		foreach( $fields->out_fields as $id => $data )
    		{
	    		$data = $data ? $data : $this->lang->words['cat_no_info'];
	    		
				$file['custom_fields'][] = array( 'title' => $fields->field_names[ $id ], 'data' => $data );
    		}
		}
		
		//-----------------------------------------
		// Get screenshots
		//-----------------------------------------
		
		$screenshots	= array();
		$hasPrimary		= false;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => 'record_file_id=' . $file['file_id'] . " AND record_type IN('ssupload','sslink') AND record_backup=0", 'order' => 'record_id' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( $r['record_default'] )
			{
				$hasPrimary	= true;
			}

			$screenshots[]	= $r;
		}
		
		if( count($screenshots) AND !$hasPrimary )
		{
			$screenshots[0]['record_default']	= 1;
		}

		//-------------------------------------------
		// Versioning
		//-------------------------------------------
		
		$old_versions			= array();
		
		if( $this->settings['bit_versioning'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/versioning.php', 'versioningLibrary', 'bitracker' );
			$versions 			= new $classToLoad( $this->registry );
			$old_versions		= $versions->retrieveVersions( $file );
			
			if( is_array($old_versions) AND count($old_versions) )
			{
				krsort($old_versions);
				
				$file['_last_revision']	= reset($old_versions);	// Reset has a nifty feature that returns the first value of the array
			}
			else
			{
				$file['_last_revision']	= 0;
			}
		}
		
		//-------------------------------------------
		// Get tags
		//-------------------------------------------
		
		if ( ! empty( $file['tag_cache_key'] ) )
		{
			$file['tags'] = $this->registry->bitrackerTags->formatCacheJoinData( $file );
		}

		//-----------------------------------------
		// Mark as read
		//-----------------------------------------

		$this->registry->classItemMarking->markRead( array( 'forumID' => $file['file_cat'], 'itemID' => $file['file_id'] ), 'bitracker' );

		//-----------------------------------------
		// Dynamic download links
		//-----------------------------------------
		
		$hash	= '';
		
		if( $this->settings['bit_dynamic_urls'] )
		{
			$hash	= $this->_createDynamictrack( $file );
		}
		
		//-------------------------------------------
		// Have we tracked?
		//-------------------------------------------
		
		if( $this->memberData['member_id'] && $this->settings['must_dl_rate'] )
		{
			$dl_count = $this->DB->buildAndFetch( array( 'select' => "count(*) as count", 'from' => 'bitracker_bitracker', 'where' => "dfid=" . $file['file_id'] . " AND dmid=" . $this->memberData['member_id'] ) );
			$tracked = $dl_count['count'];
		}
		
		//-------------------------------------------
		// Show meh teh money!11!!
		//-------------------------------------------
		
		$purchases		= array();
		$renewal_terms	= '';
				
		if ( ( $file['file_cost'] or $file['file_nexus'] ) and IPSLib::appIsInstalled('nexus') )
		{
			require_once IPSLib::getAppDir('nexus') . '/sources/packageCore.php';
		
			$where = array();

			if ( $file['file_cost'] )
			{
				$where[] = "( ps_app='bitracker' AND ps_type='file' AND ps_item_id=" . $file['file_id'] . ' )';
			}

			if ( $file['file_nexus'] )
			{
				$where[] = "( ps_app='nexus' AND " . package::sqlIn( 'ps_type' ) . ' AND ' . $this->DB->buildWherePermission( explode( ',', $file['file_nexus'] ), 'ps_item_id', FALSE ) . ' )';
			}

			$where = implode( ' OR ', $where );
		
			$total	= $this->DB->buildAndFetch( array( 'select' => "COUNT(*) as purchases", 'from' => 'nexus_purchases', 'where' => $where ) );
			$file['file_purchases']	= intval( $total['purchases'] );

			if( $this->memberData['member_id'] )
			{
				$where = '(' . $where . ") AND ps_member={$this->memberData['member_id']}";

				$this->DB->build( array( 'select' => '*', 'from' => 'nexus_purchases', 'where' => $where ) );				
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$purchases[] = $r;
				}
				
				if ( $file['file_renewal_term'] )
				{
					$renewal_terms = $this->lang->words['renew_term_prefix'] . ipsRegistry::getAppClass('nexus')->formatRenewalTerms( array(
							'unit'	=> $file['file_renewal_units'],
							'term'	=> $file['file_renewal_term'],
							'price'	=> $file['file_renewal_price']
					)		);
				}
			}
		}
		
		//-------------------------------------------
		// Show the comments inline
		//-------------------------------------------

		if( $category['coptions']['opt_comments'] )
		{
			$this->cache->updateCacheWithoutSaving( 'bit_file_' . $file['file_id'], $file );

			$comment_output		= $this->_comments->fetchFormatted( $file, array( 'offset' => intval( $this->request['st'] ) ) );
			$comment_count		= $this->_comments->count( $file );
		}
		
		//-------------------------------------------
		// Retrieve similar files, if configured
		//-------------------------------------------

		$similar	= $this->getSimilarFiles( $file['file_name'], $file['file_id'] );
		
		//-------------------------------------------
		// Output
		//-------------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker')->fileDisplay( 
																									array(
																										'can_add'		=> $this->canadd,
																										'can_moderate'	=> $this->canmod,
																										'can_broken'	=> $canbrok,
																										'can_approve'	=> $canapp,
																										'can_edit'		=> $canedit,
																										'can_delete'	=> $candel,
																										'can_feature'	=> $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modusefeature' ),
																										'can_pin'		=> $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanpin' ),
																										),
																										$file,
																										$category,
																										$screenshots,
																										$old_versions,
																										$hash,
																										$purchases,
																										$this->_like->render( 'summary', $file['file_id'] ),
																										array( 'html' => $comment_output, 'count' => $comment_count ),
																										$renewal_terms,
																										$similar,
																										$tracked
																									);

		//-------------------------------------------
		// Update file views
		//-------------------------------------------
			
		if( $this->settings['bit_updateviews'] == 1 )
		{
			$this->DB->update( "bitracker_files", 'file_views=file_views+1', "file_id=" . $file['file_id'], false, true );
			$this->registry->getClass('categories')->rebuildFileinfo($file['file_cat']);
		}
		else
		{
			$this->DB->insert( "bitracker_fileviews", array( 'view_fid' => $file['file_id'] ), true );
		}

		//-------------------------------------------
		// Grab stats
		//-------------------------------------------
		
		$this->output .= $this->registry->getClass('bitFunctions')->getStats();

		//-------------------------------------------
		// Output
		//-------------------------------------------
		
		IPSCookie::set('modfileids', '', 0);	
		
		foreach( $this->registry->getClass('categories')->getNav( $category['cid'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
		}
		
		$this->registry->output->addNavigation( $file['file_name'], '' );

        $this->registry->output->setTitle( $file['file_name'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Retrieve similar files
	 * 
	 * @param	string	File name to search
	 * @param	id		Current file id to exclude, if present
	 * @return	array	Files
	 */
 	public function getSimilarFiles( $filename, $fileId=0 )
 	{
 		$_results	= array( 'results' => array(), 'records' => array() );
 		
 		if( !$filename )
 		{
 			return $_results;
 		}
 		
 		if( !$this->settings['bit_similar_files'] )
 		{
 			return $_results;
 		}
 		
 		$this->settings['search_method'] = ( $this->settings['search_method'] == 'traditional' ) ? 'sql' : $this->settings['search_method'];

		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH. 'sources/classes/search/controller.php', 'IPSSearch' );
		
		try
		{
			$searchController = new $classToLoad( $this->registry, $this->settings['search_method'], 'bitracker' );
		}
		catch( Exception $error )
		{
			return $_results;
		}

		$this->request['andor_type']	= 'or';
				
		$filename	= $searchController->formatSearchTerm( $filename );
		$filename	= $filename['search_term'];

		IPSSearchRegistry::set('in.search_app'			, 'bitracker' );
		IPSSearchRegistry::set('in.raw_search_term'		, $filename );
		IPSSearchRegistry::set('in.clean_search_term'	, $filename );
		IPSSearchRegistry::set('in.raw_search_tags'		, '' );
		IPSSearchRegistry::set('in.search_higlight'		, '' );
		IPSSearchRegistry::set('in.search_date_end'		, '' );
		IPSSearchRegistry::set('in.search_date_start'	, '' );
		IPSSearchRegistry::set('in.search_author'		, '' );
		IPSSearchRegistry::set('set.resultsCutToLimit'	, false );
		IPSSearchRegistry::set('set.resultsAsForum'		, false );
		IPSSearchRegistry::set('opt.searchType'			, 'both' );
		IPSSearchRegistry::set('bitracker.searchInKey'	, 'files' );
		IPSSearchRegistry::set('in.start'				, 0 );
		IPSSearchRegistry::set('opt.search_per_page'	, 8 );
		IPSSearchRegistry::set('in.search_sort_order'	, 'desc' );
		
		if( $fileId )
		{
			IPSSearchRegistry::set('bitracker.excludeFileId'	, $fileId );
		}

		$searchController->search();

		$results = $searchController->getRawResultSet();

		if( count($results) )
		{
			//-----------------------------------------
			// Grab screenshot info
			//-----------------------------------------
				
			$_screenshotFileIds	= array_unique( array_keys( $results ) );
			
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
		}

		return array( 'results' => $results, 'records' => $_recordIds );
 	}

	/**
	 * Create a dynamic download URL
	 *
	 * @param	array 		File info
	 * @return	string		MD5 hash
	 */
	protected function _createDynamictrack( $file )
	{
		$insert	= array(
						'url_id'		=> md5( uniqid( microtime(), true ) ),
						'url_file'		=> $file['file_id'],
						'url_ip'		=> $this->member->ip_address,
						'url_created'	=> time(),
						'url_expires'	=> $this->settings['bit_dynamic_expire'] ? time() + ( 60 * $this->settings['bit_dynamic_expire'] ) : time() + ( 60 * 60 * 24 * 7 ),
						);

		$this->DB->insert( 'bitracker_urls', $insert );
		
		return $insert['url_id'];
	}
}