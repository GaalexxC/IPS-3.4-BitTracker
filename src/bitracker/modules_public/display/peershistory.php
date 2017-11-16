<?php
/**
 *  devCU Software Development
 *  devCU biTracker 1.0.0 Release
 *  Last Updated: $Date: 2014-07-13 09:01:45 -0500 (Sunday, 13 July 2014) $
 *
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

class public_bitracker_display_peershistory extends ipsCommand
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
	 * Got multi files?
	 *
	 * @access	protected
	 * @var 	boolean
	 */
	protected $hasMultiTorrents = false;

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
		// Get the file and torrent data
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
																			'select'	=> 'tor.*',
																			'from'		=> array( 'bitracker_torrent_data' => 'tor' ),
																			'where'		=> 'tor.torrent_id=f.file_id',
																			'type'		=> 'left' ),
                                            $this->registry->bitrackerTags->getCacheJoin( array( 'meta_id_field' => 'f.file_id' ) ),
											$this->registry->classItemMarking->getSqlJoin( array( 'item_app_key_1' => 'f.file_cat' ), 'bitracker' ),     
	                                                                  array(
																			'select'	=> 'nfo.record_realname, nfo.record_location, nfo.record_size',
																			'from'		=> array( 'bitracker_files_records' => 'nfo' ),
																			'where' => 'nfo.record_file_id=f.file_id AND nfo.record_type IN("nfoupload","nfolink") AND nfo.record_backup=0',
																			'type'		=> 'left' ),
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
		// if we have a filelist format it for display
		//-------------------------------------------

        $file['torrent_filelist'] = '';
        

        if( $file['torrent_file_count'] > 1 ){

            $file['torrent_filelist']	= json_decode($file['torrent_filelist'], true);

            foreach ( $file['torrent_filelist'] as $files ){

            $file['files_name'][] .= $files['path'][0] . '(' . IPSLib::sizeFormat($files['length']) . ')';
        
            $hasMultiTorrents = true;

               }
          }

		//---------------------------------------------
		// Get all the historic peers for this torrent?
		//---------------------------------------------

          $this->DB->build( array( 'select' 	=> '*',
								   'from'		=> 'bitracker_torrent_peers_history',
                                   'where'	=> "torrent_id='{$file['file_id']}'"
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

          if( $peers_his[$peer_his]['seeder'] == yes && $peers_his[$peer_his]['downloaded'] = 0 )
           {
               $peers_his[$peer_his]['f_ratio'] = $peers_his[$peer_his]['uploaded'] / $file['torrent_filesize'];
           }

            //$peers_his[$peer_his]['f_ratio'] = (($peers_his[$peer_his]['downloaded'] > 0) ? ($peers_his[$peer_his]['uploaded'] / $peers_his[$peer_his]['downloaded']) : 1);
            $peers_his[$peer_his]['f_ratio'] = $peers_his[$peer_his]['uploaded'] / $peers_his[$peer_his]['downloaded'];
            $peers_his[$peer_his]['f_ratio'] = number_format($peers_his[$peer_his]['f_ratio'], 2, '.', '');

            if( $peers_his[$peer_his]['seeder'] == yes )
              {

               $peers_his[$peer_his]['p_done'] = 100;
               $peers_his[$peer_his]['f_ratio'] = $peers_his[$peer_his]['uploaded'] / $file['torrent_filesize'];
               
                 if($file['file_submitter'] == $peers_his[$peer_his]['member_id'])
                  {
                     $peers_his[$peer_his]['uploader'] = TRUE;
                  }
               

              }else{

               $peers_his[$peer_his]['p_done'] = $peers_his[$peer_his]['downloaded'] / $file['torrent_filesize'];
               $peers_his[$peer_his]['p_done'] = $peers_his[$peer_his]['p_done'] * 100;
               $peers_his[$peer_his]['p_done'] = number_format($peers_his[$peer_his]['p_done'], 1, '.', '');

              }
                      
            }

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
		// Parse member info
		//-------------------------------------------
		
		$file = IPSMember::buildDisplayData( $file );

		
		//-------------------------------------------
		// Output
		//-------------------------------------------

		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_file')->torrentPeershist($peers_his);


		//-------------------------------------------
		// Output
		//-------------------------------------------
		
		IPSCookie::set('modfileids', '', 0);	
		
		foreach( $this->registry->getClass('categories')->getNav( $category['cid'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
		}
		
		$this->registry->output->addNavigation( 'live peers - ' . $file['file_name'], '' );

        $this->registry->output->setTitle( $file['file_name'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
}