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

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 *
 * @class		admin_bitracker_information_overview
 * @brief		IP.download Manager Overview
 */
class admin_bitracker_information_overview extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_overview' );
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=information&amp;section=overview&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=information&section=overview&';
		
		$data	= array( 'overview' => array() );

		//-----------------------------------------
		// Get primary data
		//-----------------------------------------
		
		$disk  = $this->DB->buildAndFetch( array( 'select' => 'SUM(file_bitracker) as total_bitracker, SUM(file_views) as total_views, COUNT(*) as total_files',
												  'from'   => 'bitracker_files'
										  )		 );

		$disk1 = $this->DB->buildAndFetch( array( 'select' => 'SUM(record_size) as total_size',
												  'from'   => 'bitracker_files_records'
										  )		 );

		$data['overview']['total_size']			= IPSLib::sizeFormat( $disk1['total_size'] ? $disk1['total_size'] : 0 );
		$data['overview']['total_files']		= intval($disk['total_files']);
		$data['overview']['total_bitracker']	= intval($disk['total_bitracker']);
		$data['overview']['total_views']		= intval($disk['total_views']);

		if( $this->settings['bit_logallbitracker'] )
		{
			$bw = $this->DB->buildAndFetch( array( 'select' => 'SUM(dsize) as total_bw', 'from' => 'bitracker_bitracker' ) );
			
			$data['overview']['total_bw']		= IPSLib::sizeFormat( $bw['total_bw'] ? $bw['total_bw'] : 0 );
			
			$st_time = mktime( 0, 0, 0, date( "n" ), 1, date("Y") );
			
			$cur_bw = $this->DB->buildAndFetch( array( 'select' => 'SUM(dsize) as this_bw', 'from' => 'bitracker_bitracker', 'where' => "dtime > {$st_time}" ) );
			
			$data['overview']['this_bw']		= IPSLib::sizeFormat( $cur_bw['this_bw'] ? $cur_bw['this_bw'] : 0 );
		}
		else
		{
			$bw = $this->DB->buildAndFetch( array( 'select' => 'SUM(file_bitracker*file_size) as total_bw', 'from' => "bitracker_files" ) );
			
			$data['overview']['total_bw']		= IPSLib::sizeFormat( $bw['total_bw'] ? $bw['total_bw'] : 0 );
			$data['overview']['this_bw']		= $this->lang->words['o_notavail'];
		}
		
		$largest = $this->DB->buildAndFetch( array( 'select'	=> 'file_id, file_name, file_size', 
													'from'	=> 'bitracker_files',
													'order'	=> 'file_size DESC',
													'limit'	=> array( 0, 1 ) 
											)		);
													
		$views = $this->DB->buildAndFetch( array( 'select'	=> 'file_id, file_name, file_views', 
												  'from'	=> 'bitracker_files',
												  'order'	=> 'file_views DESC',
												  'limit'	=> array( 0, 1 )
										  )		 );
												
		$bitracker = $this->DB->buildAndFetch( array( 'select'	=> 'file_id, file_name, file_bitracker', 
													  'from'	=> 'bitracker_files',
													  'order'	=> 'file_bitracker DESC',
													  'limit'	=> array( 0, 1 )
											  )		 );

		$data['overview']['largest_file_size']	= IPSLib::sizeFormat( $largest['file_size'] ? $largest['file_size'] : 0 );
		$data['overview']['largest_file_name']	= $largest['file_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$largest['file_id']}'>{$largest['file_name']}</a>" : $this->lang->words['o_nofiles'];

		$data['overview']['views_file_views']	= intval($views['file_views']);
		$data['overview']['views_file_name']	= $views['file_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=bitracker&showfile={$views['file_id']}'>{$views['file_name']}</a>" : $this->lang->words['o_nofiles'];

		$data['overview']['dls_file_bitracker']	= intval($bitracker['file_bitracker']);
		$data['overview']['dls_file_name']		= $bitracker['file_id'] ? "<a href='{$this->settings['board_url']}/index.php?app=bitracker&showfile={$bitracker['file_id']}' target='_blank'>{$bitracker['file_name']}</a>" : $this->lang->words['o_nofiles'];

		$data['reports']['file']				= $this->registry->output->formInput( 'file', $this->request['file'] );
		$data['reports']['member']				= $this->registry->output->formInput( 'member', $this->request['member'], '', 0, 'text', "autocomplete='off'" );

		$cerror		= array();		
		$latest		= array();
		$pending	= array();
		$broken		= array();

		//-----------------------------------------
		// Connection Errors
		//-----------------------------------------
		
		$this->DB->build( array('select'	=> 'e.request_ip, e.request_client, e.request_infohash, e.request_perm_key, e.request_time, e.error_string',
								'from'		=> array( 'bitracker_announce_error_log' => 'e' ),
								'order'		=> 'e.request_time DESC',
								'limit'		=> array( 0, 5 ),
								'add_join'	=> array( array( 'select'	=> 't.torrent_name',
															 'from'		=> array( 'bitracker_torrent_data' => 't' ),
															 'where'	=> 't.torrent_infohash=e.request_infohash',
															 'type'		=> 'left', 
                                                           ),
                                                      array( 
                                                             'select'	=> 'm.members_display_name, m.member_id',
															 'from'		=> array( 'members' => 'm' ),
															 'where'	=> 'm.perm_key=e.request_perm_key',
															 'type'		=> 'left', 
                                                           ), 
                                                    )
						)		);
													
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['date'] 		= $this->registry->getClass('class_localization')->getDate( $row['request_time'], 'SHORT' );
			$row['user_link']	= $row['members_display_name'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['member_id']}'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			
			$cerror[]			= $row;
		}


		//-----------------------------------------
		// Latest files
		//-----------------------------------------
		
		$this->DB->build( array('select'	=> 'f.file_id, f.file_open, f.file_name, f.file_submitter, f.file_submitted',
								'from'		=> array( 'bitracker_files' => 'f' ),
								'order'		=> 'f.file_submitted DESC',
								'limit'		=> array( 0, 5 ),
								'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
															 'from'		=> array( 'members' => 'm' ),
															 'where'	=> 'm.member_id=f.file_submitter',
															 'type'		=> 'left' ) )
						)		);
													
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['user_link']	= $row['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['file_submitter']}'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			$row['date'] 		= $this->registry->getClass('class_localization')->getDate( $row['file_submitted'], 'SHORT' );
			
			$latest[]			= $row;
		}
		
		//-----------------------------------------
		// Pending files
		//-----------------------------------------
				
		$this->DB->build( array( 'select' => 'f.file_id, f.file_open, f.file_name, f.file_submitter, f.file_submitted', 
								 'from' 	=> array( 'bitracker_files' => 'f' ), 
								 'where' 	=> 'f.file_open=0', 
								 'order' 	=> 'f.file_submitted ASC',
								 'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
								 							 'from'		=> array( 'members' => 'm' ),
								 							 'where'	=> 'm.member_id=f.file_submitter',
								 							 'type'		=> 'left' ) )
						 )		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['user_link']	= $row['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['file_submitter']}' target='_blank'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			$row['date'] 		= $this->registry->getClass('class_localization')->getDate( $row['file_submitted'], 'SHORT' );

			$pending[]			= $row;
		}
		
		//-----------------------------------------
		// Broken files (poor file id 59...)
		//-----------------------------------------
		
		$this->DB->build( array( 'select' 	=> 'f.file_id, f.file_open, f.file_name, f.file_submitter, f.file_submitted', 
								 'from' 	=> array( 'bitracker_files' => 'f' ), 
								 'where' 	=> 'f.file_broken=1', 
								 'order' 	=> 'f.file_name ASC',
								 'add_join'	=> array( array( 'select'	=> 'm.members_display_name',
								 							 'from'		=> array( 'members' => 'm' ),
								 							 'where'	=> 'm.member_id=f.file_submitter',
															 'type'		=> 'left' ) )
						 )		);
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$row['user_link']	= $row['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$row['file_submitter']}' target='_blank'>{$row['members_display_name']}</a>" : $this->lang->words['o_guest'];
			$row['date'] 		= $this->registry->class_localization->getDate( $row['file_submitted'], 'SHORT' );

			$broken[]			= $row;
		}

		$this->registry->output->html .= $this->html->overviewSplash( $data, $cerror, $latest, $pending, $broken );
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
}