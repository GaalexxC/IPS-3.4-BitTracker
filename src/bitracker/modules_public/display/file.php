<?php

/**
 * <pre>
 *  devCU Software Development
 *  devCU biTracker 1.0.0 Release
 *  Last Updated: $Date: 2014-08-07 09:01:45 -0500 (Thursday, 07 August 2014) $
 * </pre>
 * @author 		TG / PM
 * @copyright	(c) 2014 devCU Software Development
 * @Web	        http://www.devcu.com
 * @support       support@devcu.com
 * @license		 DCU Public License
 *
 * DevCU Public License DCUPL Rev 21
 * The use of this license is free for all those who choose to program under its guidelines. 
 * The creation, use, and distribution of software under the terms of this license is aimed at protecting the authors work. 
 * The license terms are for the free use and distribution of open source projects. 
 * The author agrees to allow other programmers to modify and improve, while keeping it free to use, the given software with the full knowledge of the original authors copyright.
 * 
 *  The full License is available at devcu.com
 *  http://www.devcu.com/devcu-public-license-dcupl/
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
																	array(
																			'select'	=> 'tor.*',
																			'from'		=> array( 'bitracker_torrent_data' => 'tor' ),
																			'where'		=> 'tor.torrent_id=f.file_id',
																			'type'		=> 'left' ),
	                                                                  array(
																			'select'	=> 'nfo.*',
																			'from'		=> array( 'bitracker_files_records' => 'nfo' ),
																			'where' => 'nfo.record_file_id=f.file_id AND nfo.record_type IN("nfoupload","nfolink") AND nfo.record_backup=0',
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
		// If we have a filelist format it for display
		//-------------------------------------------
       
        $file['torrent_filelist'] = json_decode($file['torrent_filelist'], true);
        $file['torrent_infohash'] = strtoupper($file['torrent_infohash']);

     	//-------------------------------------------
		// Check FURL
		//-------------------------------------------
		
		$this->registry->getClass('output')->checkPermalink( $file['file_name_furl'] );


     	//-------------------------------------------
		// Give and Take
		//-------------------------------------------


		$sdcnt = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'bitracker_torrent_peers', 'where' => 'torrent=' . $file['file_id'] . " AND seeder='yes'" ) );


		$file['seed_count'] = $sdcnt['total'];  


		$lccnt = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'bitracker_torrent_peers', 'where' => 'torrent=' . $file['file_id'] . " AND seeder='no'" ) );


		$file['leech_count'] = $lccnt['total']; 

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

		$file['file_extra'] = IPSText::getTextClass('bbcode')->preDisplayParse( $file['file_extra'] );

		$file['file_tech'] = IPSText::getTextClass('bbcode')->preDisplayParse( $file['file_tech'] );

		$file['field_2'] = IPSText::getTextClass('bbcode')->preDisplayParse( $file['field_2'] );
		
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

		//-------------------------------------------
		// Get the Peers
		//-------------------------------------------

		$this->DB->build( array( 'select' 	=> '*',
                                 'from'		=> 'bitracker_torrent_peers',
                                 'where'	=> "torrent='{$file['file_id']}'",
                                 'order'    => 'uploaded DESC'
						)       );	


		$result = $this->DB->execute();

		while( $row = $this->DB->fetch( $result ) )
		{

   			$peers[] = $row;

		}

		if(empty($peers))
		{

   			$peers = array();

		}else{

 			foreach(array_keys($peers) as $peer){

 				$displayName = $this->DB->buildAndFetch( array( 'select' 	=> 'members_display_name',
                                                                'from'		=> 'members',
                                                                'where'	=> "member_id ='{$peers[$peer]['mem_id']}'"
                                        			  )		);

    	$peers[$peer]['members_display_name'] = $displayName['members_display_name'];

	   //---------------------------------------
	   // Set some numbers
	   //---------------------------------------


		$peers[$peer]['f_ratio'] = (($peers[$peer]['downloaded'] > 0) ? ($peers[$peer]['uploaded'] / $peers[$peer]['downloaded']) : 0);	
        $peers[$peer]['f_ratio'] = number_format($peers[$peer]['f_ratio'], 2, '.', '');	

		if( $peers[$peer]['seeder'] == yes)
 		{
            //$this->DB->update( 'bitracker_torrent_peers_history', array( 'history_completed' => yes, 'history_date_completed' => time() ), "torrent_id='{$peers[$peer]['torrent']}' AND member_id='{$peers[$peer]['mem_id']}'" );                                                                                                                                                                                                                                         
			$peers[$peer]['p_done'] = 100;
            $peers[$peer]['downloaded'] = $file['torrent_filesize'];
            $peers[$peer]['f_ratio'] = $peers[$peer]['uploaded'] / $file['torrent_filesize'];
            $peers[$peer]['f_ratio'] = number_format($peers[$peer]['f_ratio'], 2, '.', '');

		if($file['file_submitter'] == $peers[$peer]['mem_id'])
 		{
   			$peers[$peer]['uploader'] = TRUE;
 		}

 		}else{

 			$peers[$peer]['p_done'] = $peers[$peer]['downloaded'] / $file['torrent_filesize'];
 			$peers[$peer]['p_done'] = $peers[$peer]['p_done'] * 100;
 			$peers[$peer]['p_done'] = number_format($peers[$peer]['p_done'], 1, '.', '');

  		    }

 	     }

      }



		//---------------------------------------------
		// Get all the historic peers for this torrent?
		//---------------------------------------------

          $this->DB->build( array( 'select' 	=> '*',
								   'from'		=> 'bitracker_torrent_peers_history',
                                   'where'	=> "torrent_id='{$file['file_id']}'",
                                   'order'    => 'uploaded DESC'
				    	)		);

		   $result = $this->DB->execute();

		   while( $row = $this->DB->fetch( $result ) )
			{
				$peers_his[] = $row;

			}

           if(empty($peers_his)){

            $peers_his = array();         

           }else{

           foreach(array_keys($peers_his) as $peer_his){

            $displayName = $this->DB->buildAndFetch( array( 'select' 	=> 'members_display_name',
											 		                       'from'		=> 'members',
                                                                           'where'	=> "member_id ='{$peers_his[$peer_his]['member_id']}'"
										    	)		);

            
            $peers_his[$peer_his]['members_display_name'] = $displayName['members_display_name'];


         //---------------------------------------
         // Set some numbers
         //---------------------------------------


	

		if( $peers_his[$peer_his]['history_completed'] == yes && $peers_his[$peer_his]['history_left'] == 0)
 		{
			$peers_his[$peer_his]['h_done'] = 100;
            $peers_his[$peer_his]['downloaded'] = $file['torrent_filesize'];
            $peers_his[$peer_his]['h_ratio'] = $peers_his[$peer_his]['uploaded'] / $peers_his[$peer_his]['downloaded'];
            $peers_his[$peer_his]['h_ratio'] = number_format($peers_his[$peer_his]['h_ratio'], 2, '.', '');

		if($file['file_submitter'] == $peers_his[$peer_his]['member_id'])
 		{
   			$peers_his[$peer_his]['uploader'] = TRUE;
 		}

 		}else{
            $peers_his[$peer_his]['downloaded'] = $file['torrent_filesize'] - $peers_his[$peer_his]['history_left'];
		    $peers_his[$peer_his]['h_ratio'] = (($peers_his[$peer_his]['downloaded'] > 0) ? ($peers_his[$peer_his]['uploaded'] / $peers_his[$peer_his]['downloaded']) : 0);	
            $peers_his[$peer_his]['h_ratio'] = number_format($peers_his[$peer_his]['h_ratio'], 2, '.', '');
 			$peers_his[$peer_his]['h_done'] = $peers_his[$peer_his]['downloaded'] / $file['torrent_filesize'];
 			$peers_his[$peer_his]['h_done'] = $peers_his[$peer_his]['h_done'] * 100;
 			$peers_his[$peer_his]['h_done'] = number_format($peers_his[$peer_his]['h_done'], 1, '.', '');

  		    }           
           }
         }

		//-----------------------------------------
		// Get nfo and convert for display
		//-----------------------------------------

        $path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . "/" . $file['record_location'];

		$content = @file_get_contents( $path );
					
		  	if( !$content )
				{
					$this->_safeExit();
				}

         $nfo_file = $this->convertNfoforDisplay( $content );

          // $nfo_file = $this->registry->getClass('bitFunctions')->output_nfo_image($path);


		
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
		// Have we downloaded?
		//-------------------------------------------
		
		if( $this->memberData['member_id'] && $this->settings['must_dl_rate'] )
		{
			$dl_count = $this->DB->buildAndFetch( array( 'select' => "count(*) as count", 'from' => 'bitracker_bitracker', 'where' => "dfid=" . $file['file_id'] . " AND dmid=" . $this->memberData['member_id'] ) );
			$tracked = $dl_count['count'];
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

		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_file')->fileDisplay( 
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
                                                                                                        $peers,
                                                                                                        $peers_his,
                                                                                                        $nfo_file,
																										$screenshots,
																										$old_versions,
																										$hash,
																										$this->_like->render( 'summary', $file['file_id'] ),
																										array( 'html' => $comment_output, 'count' => $comment_count ),
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
		
	//$string = $this->registry->output->addNavigation( $file['file_name'], '' );

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

	/**
	 * Convert NFO for display in a template
	 * @param	array	nfo display
	 * @note	
	 */
     public function convertNfoforDisplay( $nfo )
       {
        $trans = array(
        "\x80" => "&#199;", "\x81" => "&#252;", "\x82" => "&#233;", "\x83" => "&#226;", "\x84" => "&#228;", "\x85" => "&#224;", "\x86" => "&#229;", "\x87" => "&#231;", "\x88" => "&#234;", "\x89" => "&#235;", "\x8a" => "&#232;", "\x8b" => "&#239;", "\x8c" => "&#238;", "\x8d" => "&#236;", "\x8e" => "&#196;", "\x8f" => "&#197;", "\x90" => "&#201;",
        "\x91" => "&#230;", "\x92" => "&#198;", "\x93" => "&#244;", "\x94" => "&#246;", "\x95" => "&#242;", "\x96" => "&#251;", "\x97" => "&#249;", "\x98" => "&#255;", "\x99" => "&#214;", "\x9a" => "&#220;", "\x9b" => "&#162;", "\x9c" => "&#163;", "\x9d" => "&#165;", "\x9e" => "&#8359;", "\x9f" => "&#402;", "\xa0" => "&#225;", "\xa1" => "&#237;",
        "\xa2" => "&#243;", "\xa3" => "&#250;", "\xa4" => "&#241;", "\xa5" => "&#209;", "\xa6" => "&#170;", "\xa7" => "&#186;", "\xa8" => "&#191;", "\xa9" => "&#8976;", "\xaa" => "&#172;", "\xab" => "&#189;", "\xac" => "&#188;", "\xad" => "&#161;", "\xae" => "&#171;", "\xaf" => "&#187;", "\xb0" => "&#9617;", "\xb1" => "&#9618;", "\xb2" => "&#9619;",
        "\xb3" => "&#9474;", "\xb4" => "&#9508;", "\xb5" => "&#9569;", "\xb6" => "&#9570;", "\xb7" => "&#9558;", "\xb8" => "&#9557;", "\xb9" => "&#9571;", "\xba" => "&#9553;", "\xbb" => "&#9559;", "\xbc" => "&#9565;", "\xbd" => "&#9564;", "\xbe" => "&#9563;", "\xbf" => "&#9488;", "\xc0" => "&#9492;", "\xc1" => "&#9524;", "\xc2" => "&#9516;", "\xc3" => "&#9500;",
        "\xc4" => "&#9472;", "\xc5" => "&#9532;", "\xc6" => "&#9566;", "\xc7" => "&#9567;", "\xc8" => "&#9562;", "\xc9" => "&#9556;", "\xca" => "&#9577;", "\xcb" => "&#9574;", "\xcc" => "&#9568;", "\xcd" => "&#9552;", "\xce" => "&#9580;", "\xcf" => "&#9575;", "\xd0" => "&#9576;", "\xd1" => "&#9572;", "\xd2" => "&#9573;", "\xd3" => "&#9561;", "\xd4" => "&#9560;",
        "\xd5" => "&#9554;", "\xd6" => "&#9555;", "\xd7" => "&#9579;", "\xd8" => "&#9578;", "\xd9" => "&#9496;", "\xda" => "&#9484;", "\xdb" => "&#9608;", "\xdc" => "&#9604;", "\xdd" => "&#9612;", "\xde" => "&#9616;", "\xdf" => "&#9600;", "\xe0" => "&#945;", "\xe1" => "&#223;", "\xe2" => "&#915;", "\xe3" => "&#960;", "\xe4" => "&#931;", "\xe5" => "&#963;",
        "\xe6" => "&#181;", "\xe7" => "&#964;", "\xe8" => "&#934;", "\xe9" => "&#920;", "\xea" => "&#937;", "\xeb" => "&#948;", "\xec" => "&#8734;", "\xed" => "&#966;", "\xee" => "&#949;", "\xef" => "&#8745;", "\xf0" => "&#8801;", "\xf1" => "&#177;", "\xf2" => "&#8805;", "\xf3" => "&#8804;", "\xf4" => "&#8992;", "\xf5" => "&#8993;", "\xf6" => "&#247;",
        "\xf7" => "&#8776;", "\xf8" => "&#176;", "\xf9" => "&#8729;", "\xfa" => "&#183;", "\xfb" => "&#8730;", "\xfc" => "&#8319;", "\xfd" => "&#178;", "\xfe" => "&#9632;", "\xff" => "&#160;",
        );
        $trans2 = array("\xe4" => "&auml;",        "\xF6" => "&ouml;",        "\xFC" => "&uuml;",        "\xC4" => "&Auml;",        "\xD6" => "&Ouml;",        "\xDC" => "&Uuml;",        "\xDF" => "&szlig;");
        $all_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $last_was_ascii = False;
        $tmp = "";
        $nfo = $nfo . "\00";
        for ($i = 0; $i < (strlen($nfo) - 1); $i++)
        {
                $char = $nfo[$i];
                if (isset($trans2[$char]) and ($last_was_ascii or strpos($all_chars, ($nfo[$i + 1]))))
                {
                        $tmp = $tmp . $trans2[$char];
                        $last_was_ascii = True;
                }
                else
                {
                        if (isset($trans[$char]))
                        {
                                $tmp = $tmp . $trans[$char];
                        }
                        else
                        {
                            $tmp = $tmp . $char;
                        }
                        $last_was_ascii = strpos($all_chars, $char);
                }
        }

        return $tmp;

        }

}