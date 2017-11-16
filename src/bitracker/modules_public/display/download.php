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
 * @package		IP.bitrackers
 *
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_display_download extends ipsCommand
{
	/**
	 * Stored temporary output
	 *
	 * @var 	string 				Page output
	 */
	protected $output				= "";
	
	/**
	 * Stored temporary page title
	 *
	 * @var 	string 				Page title
	 */
	protected $page_title			= "";

	/**
	 * Member is restricted
	 *
	 * @var 	boolean
	 */
	protected $restricted			= false;

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
		// Do we have access?
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['view']) == 0 )
		{
			$this->registry->output->showError( 'no_bitracker_permissions', 10844, null, null, 403 );
		}

		//-----------------------------------------
		// If we were redirected from login, force to visit the file
		// @link 
		//-----------------------------------------
		
		if( !empty($_SERVER['HTTP_REFERER']) )
		{
			$host		= parse_url($_SERVER['HTTP_REFERER']);

			if( str_replace( 'www.', '', $host['host'] ) == str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) )
			{
				if( strpos( $_SERVER['HTTP_REFERER'], "section=login" ) )
				{
					$id		= $this->_getFileId();
					$file	= $this->DB->buildAndFetch( array( 'select' => 'file_id, file_name_furl', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
					
					$this->registry->output->silentRedirect( $this->registry->output->buildSEOUrl( "app=bitracker&amp;showfile=" . $file['file_id'], "public", $file['file_name_furl'], "bitshowfile" ) );
				}
			}
		}
		
		//-------------------------------------------
		// What are we doing?
		//-------------------------------------------

		switch( $this->request['do'] )
		{
			case 'buy':
				$this->_buy();
				break;
						
			case 'do_download':
				//-----------------------------------------
				// Get file id (and check dynamic url)
				//-----------------------------------------
				
				$id	= $this->_getFileId();
				
				if( !$id )
				{
					$this->registry->output->showError( 'error_generic', 10850.1, null, null, 404 );
				}
				
				$this->_doDownload( $id );
			break;
				
			case 'version_download':
				$this->_doVersionDownload();
			break;
				
			default:
			case 'confirm_download':
				$this->_displayConfirm( );
			break;
		}
		
		//-------------------------------------------
		// Print output
		//-------------------------------------------

        $this->registry->output->setTitle( $this->page_title . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}	
	
	/**
	 * Given a file information array, return rating info
	 *
	 * @param	array
	 * @return	array
	 */
	protected function _getRatingInfo( $file )
	{
		if( !is_array($file) OR !count($file) )
		{
			return $file;
		}
		
		//-------------------------------------------
		// Rating information
		//-------------------------------------------
		
		$file['_allow_rate']	= 0;

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
		$file['_total_rating']	= $file['file_rating'];
		
		return $file;
	}
	
	/**
	 * Get the file id.  Takes into account dynamic download urls
	 *
	 * @return	integer
	 */
	protected function _getFileId()
	{
		if( $this->settings['bit_dynamic_urls'] )
		{
			$hash	= IPSText::md5Clean( $this->request['hash'] );
			
			if( $hash )
			{
				$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_urls', 'where' => "url_id='{$hash}'" ) );
				
				if( $session['url_file'] AND time() < $session['url_expires'] AND $this->member->ip_address == $session['url_ip'] )
				{
					//-----------------------------------------
					// This is the do_download request
					//-----------------------------------------
					
					if( $this->request['id'] )
					{
						return intval($this->request['id']);
					}

					//-----------------------------------------
					// Confirm
					//-----------------------------------------
					
					return $session['url_file'];
				}
			}
		}
		else
		{
			return intval($this->request['id']);
		}
		
		//-----------------------------------------
		// This is a fallback so "" isn't returned
		// since everything expects an integer
		//-----------------------------------------
		
		return 0;
	}
	
	/**
	 * Display the download confirmation page
	 *
	 * @return	@e void
	 */	
	protected function _displayConfirm()
	{
		//-----------------------------------------
		// Get file id (and check dynamic url)
		//-----------------------------------------
		
		$id	= $this->_getFileId();
		
		if( !$id )
		{
			$this->registry->output->showError( 'error_generic', 10850.2, null, null, 404 );
		}
		
		//-------------------------------------------
		// Our file and category...
		//-------------------------------------------
		
		$info		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => "bitracker_files", 'where' => 'file_id=' . $id ) );

		$files		= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_backup=0 AND record_file_id=" . $id . " AND record_type IN('upload','link')", 'order' => 'record_realname' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$files[]	= $r;
		}

		$category	= $this->registry->getClass('categories')->cat_lookup[ $info['file_cat'] ];
		
		//-------------------------------------------
		// Can we download?
		//-------------------------------------------
		
		$this->_canDownload( $info, $category );
		
		//-----------------------------------------
		// Get rating info
		//-----------------------------------------
		
		$info	= $this->_getRatingInfo( $info );

		//-------------------------------------------
		// If no disclaimer and only one file, just download
		//-------------------------------------------
		
		if( !$category['cdisclaimer'] AND count($files) == 1 AND !$this->memberData['bit_wait_period'] )
		{
			$this->_doDownload( $files[0]['record_id'] );
		}

		//-------------------------------------------
		// Else show the disclaimer
		//-------------------------------------------
		
		foreach( $this->registry->getClass('categories')->getNav( $info['file_cat'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
		}
		
		$this->registry->output->addNavigation( $info['file_name'], '' );

		$this->page_title = $info['file_name'];
		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_other')->confirmtrack( $info, $files, $category );
	}
	
	/**
	 * Perform the download
	 *
	 * @param	int			File id
	 * @return	@e void
	 */	
	protected function _doDownload( $id=0 )
	{
		//-------------------------------------------
		// Verify we waited, if we were supposed to
		//-------------------------------------------
		
		$this->_checkAndClearTimer();
		
		//-------------------------------------------
		// Get our file and category
		//-------------------------------------------
		
		if( $this->settings['bit_filestorage'] == 'db' )
		{
			$this->DB->build( array(
									'select'	=> 'r.*',
									'from'		=> array( 'bitracker_files_records' => 'r' ),
									'where'		=> 'r.record_id=' . $id,
									'add_join'	=> array(
														array(
																'select'	=> 'f.*',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=r.record_file_id',
																'type'		=> 'left'
															),
														array(
																'select'	=> 's.*',
																'from'		=> array( 'bitracker_filestorage' => 's' ),
																'where'		=> 's.storage_id=r.record_db_id',
																'type'		=> 'left'
															),
														array(
																'select'	=> 'm.mime_mimetype, m.mime_extension',
																'from'		=> array( 'bitracker_mime' => 'm' ),
																'where'		=> 'r.record_mime=m.mime_id',
																'type'		=> 'left'
															),
														)
								)		);
		}
		else
		{
			$this->DB->build( array(
									'select'	=> 'r.*',
									'from'		=> array( 'bitracker_files_records' => 'r' ),
									'where'		=> 'r.record_id=' . $id,
									'add_join'	=> array(
														array(
																'select'	=> 'f.*',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=r.record_file_id',
																'type'		=> 'left'
															),
														array(
																'select'	=> 'm.mime_mimetype, m.mime_extension',
																'from'		=> array( 'bitracker_mime' => 'm' ),
																'where'		=> 'r.record_mime=m.mime_id',
																'type'		=> 'left'
															),
														)
								)		);
		}			
		
		$this->DB->execute();
		$info = $this->DB->fetch();
		
		$category = $this->registry->getClass('categories')->cat_lookup[ $info['file_cat'] ];
		
		//-------------------------------------------
		// Antileech check
		//-------------------------------------------
				
		$this->_checkAntiLeech( $info );
		
		//-------------------------------------------
		// Is member restricted?
		//-------------------------------------------
				
		$this->_sortRestrictions( $info, $info['record_size'] );
		
		if( $this->restricted )
		{
			$this->registry->output->showError( 'bitracker_member_restricted', 10830, null, null, 403 );
		}
		
		//-------------------------------------------
		// Permission checking
		//-------------------------------------------

		$this->_canDownload( $info, $category );

		//-------------------------------------------
		// Download sessions
		//-------------------------------------------
		
		$this->_startSession( $info );
		
		//-------------------------------------------
		// If this is a url, rebuild data and send
		//-------------------------------------------
		
		if( $info['record_type'] == 'link' )
		{
			$this->registry->getClass('categories')->rebuildFileinfo($info['file_cat']);
			$this->registry->getClass('categories')->rebuildStatsCache();
		
			$info['record_location']	= IPSText::UNhtmlspecialchars( $info['record_location'] );
			
			$this->_logDownload( $info['file_id'], $info['record_size'], $info );
			
			$this->registry->output->silentRedirect( $info['record_location'] );
		}
		
		//-------------------------------------------
		// Display file inline or download?
		//-------------------------------------------
		
		$disposition = $this->_getDisposition( $info['mime_mimetype'], $category );
		
		//-------------------------------------------
		// Set the filename
		//-------------------------------------------

		$info['file_downloadasname'] = $this->_getDownloadName( 'record_realname', $info );
		
		//-----------------------------------------
		// Reset timeout for large files
		//-----------------------------------------
		
		if ( @function_exists("set_time_limit") == 1 and SAFE_MODE_ON == 0 )
		{
			@set_time_limit( 0 );
		}
		
		//-------------------------------------------
		// Do the download
		//-------------------------------------------
		
		switch( $info['record_storagetype'] )
		{
			case 'db':
				//-------------------------------------------
			https://yourdomain.com/files/download/2347-revo-uninstaller-pro/	// Log the download
				//-------------------------------------------
						
				$this->_logDownload( $info['file_id'], $info['record_size'], $info );

				$content = base64_decode($info['storage_file']);
				
				header( "Content-Type: ". $info['mime_mimetype'] );
				
				$this->_sendDispositionHeader( $disposition, $info['file_downloadasname'] );
				
				if( !ini_get('zlib.output_compression') OR ini_get('zlib.output_compression') == 'off' )
				{
					header( "Content-Length: ".(string)(strlen( $content ) ) );
				}
				
				print $content;
			break;
				
			case 'ftp':
				//-------------------------------------------
				// Log the download
				//-------------------------------------------
						
				$this->_logDownload( $info['file_id'], $info['record_size'], $info );

				$this->registry->getClass('categories')->rebuildFileinfo($info['file_cat']);
				$this->registry->getClass('categories')->rebuildStatsCache();

				$path = $this->settings['bit_remotefileurl'] . "/" . $info['record_location'];
				
				$this->registry->output->silentRedirect( $path );
			break;
				
			case 'disk':
				$path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/";
				
				$to_send = array(	'name' 			=> $info['file_downloadasname'],
									'mimetype' 		=> $info['mime_mimetype'],
									'disposition'	=> $disposition,
									'true_file'		=> $path . $info['record_location'],
									'size'			=> $info['record_size'],
									'id'			=> $info['file_id'],
									'file'			=> $info,
								);

				if( !$this->_downloadLocalFile( $to_send ) )   
				{
					$this->registry->output->showError( 'file_not_found', 10845, null, null, 404 );
				}

			break;
		}
		
		//-------------------------------------------
		// Rebuild stats and exit
		//-------------------------------------------
		
		$this->registry->getClass('categories')->rebuildFileinfo($info['file_cat']);
		$this->registry->getClass('categories')->rebuildStatsCache();
		
		exit();
	}

	/**
	 * Download a previous version of a file
	 *
	 * @param	array 		File information
	 * @param	array 		Category information
	 * @return	@e void		Determine if user can download file and show error if so
	 */	
	protected function _doVersionDownload()
	{
		if( !$this->settings['bit_versioning'] )
		{
			$this->registry->output->showError( 'no_bitracker_permissions', 10846, null, null, 403 );
		}

		//-------------------------------------------
		// Get the file
		//-------------------------------------------
		
		$id		= intval($this->request['id']);
		$record	= intval($this->request['record']);
		
		$info	= $this->DB->buildAndFetch( array( 'select' 	=> 'b.*', 
													'from' 		=> array( 'bitracker_filebackup' => 'b' ), 
													'where' 	=> 'b.b_id=' . $id,
													'add_join'	=> array(
																		array( 'select' => 'f.*',
																				'from'	=> array( 'bitracker_files' => 'f' ),
																				'where'	=> 'f.file_id=b.b_fileid',
																				'type'	=> 'left'
																			)
																		)
												) 		);

		if( !$this->registry->getClass('bitFunctions')->checkPerms( $info ) AND $info['b_hidden'] )
		{
			$this->registry->output->showError( 'no_bitracker_permissions', "10849.B", null, null, 403 );
		}
		
		$category = $this->registry->getClass('categories')->cat_lookup[ $info['file_cat'] ];

		//-------------------------------------------
		// Permission checking
		//-------------------------------------------

		$this->_canDownload( $info, $category );

		//-----------------------------------------
		// Have we specified which record?
		//-----------------------------------------
		
		$files		= array();
		$records	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_type IN('upload','link') AND record_id IN(" . $info['b_records'] . ")", 'order' => 'record_id' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$files[ $r['record_id'] ]	= $r;
			$records[]					= $r['record_id'];
		}

		//-----------------------------------------
		// Show confirm if not
		//-----------------------------------------
		
		if( !$record AND ( $category['cdisclaimer'] or count($records) > 1 ) )
		{
			foreach( $this->registry->getClass('categories')->getNav( $info['file_cat'] ) as $navigation )
			{
				$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
			}
			
			$this->registry->output->addNavigation( $info['file_name'], '' );
	
			$this->page_title = $info['file_name'];
			
			//-----------------------------------------
			// Get rating info
			//-----------------------------------------
			
			$info	= $this->_getRatingInfo( $info );
			
			$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_other')->confirmVersiontrack( $info, $files, $category );
			return;
		}
		else
		{
			$record	= $record ? $record : $records[0];
			
			if( !in_array( $record, $records ) )
			{
				$this->registry->output->showError( 'file_not_found', 108119, null, null, 404 );
			}
		}

		$info	= array_merge( $info, $files[ $record ] );

		//-------------------------------------------
		// Verify we waited, if we were supposed to
		//-------------------------------------------
		
		$this->_checkAndClearTimer();

		//-------------------------------------------
		// Is member restricted?
		//-------------------------------------------
				
		$this->_sortRestrictions( $info, $info['record_size'] );
		
		if( $this->restricted )
		{
			$this->registry->output->showError( 'bitracker_member_restricted', 10830, null, null, 403 );
		}
		
		//-------------------------------------------
		// Antileech check
		//-------------------------------------------
				
		$this->_checkAntiLeech( $info );
		
		//-------------------------------------------
		// Start session
		//-------------------------------------------
		
		$this->_startSession( $info );
		
		//-------------------------------------------
		// If this is a url, rebuild data and send
		//-------------------------------------------
		
		if( $info['record_type'] == 'link' )
		{
			$this->registry->getClass('categories')->rebuildFileinfo($info['file_cat']);
			$this->registry->getClass('categories')->rebuildStatsCache();
		
			$info['record_location']	= IPSText::UNhtmlspecialchars( $info['record_location'] );
			
			$this->_logDownload( $info['file_id'], $info['record_size'], $info );
			
			$this->registry->output->silentRedirect( $info['record_location'] );
		}
		
		//-------------------------------------------
		// Get disposition
		//-------------------------------------------
		
		$disposition = $this->_getDisposition( $info['record_mime'], $category );
		
		//-------------------------------------------
		// Get "download as" name
		//-------------------------------------------

		$info['file_downloadasname'] = $this->_getDownloadName( 'record_realname', $info );
		
		//-------------------------------------------
		// Set path and send download
		//-------------------------------------------

		$path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/";
		
		//-----------------------------------------
		// Reset timeout for large files
		//-----------------------------------------
		
		if ( @function_exists("set_time_limit") == 1 and SAFE_MODE_ON == 0 )
		{
			@set_time_limit( 0 );
		}
		
		$to_send = array(	'name' 			=> $info['file_downloadasname'],
							'mimetype' 		=> $info['record_mime'],
							'disposition'	=> $disposition,
							'true_file'		=> $path . $info['record_location'],
							'size'			=> $files[ $record ]['record_size'],
							'id'			=> $info['file_id'],
							'file'			=> $info,
						);

		if( !$this->_downloadLocalFile( $to_send ) )   
		{
			$this->registry->output->showError( 'file_not_found', 10847, null, null, 404 );
		}
		
		//-------------------------------------------
		// Rebuild stats
		//-------------------------------------------
		
		$this->registry->getClass('categories')->rebuildFileinfo($info['file_cat']);
		$this->registry->getClass('categories')->rebuildStatsCache();

		exit();
	}
	
	/**
	 * Check if user can download the file
	 *
	 * @param	array 		File information
	 * @param	array 		Category information
	 * @param	bool		If false, will ignore paid status
	 * @return	@e void		Determine if user can download file and show error if so
	 */	
	protected function _canDownload( $info, $category, $checkPaid=TRUE )
	{
		//-------------------------------------------
		// Can we download anything?
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['download']) == 0 )
		{
			if( $category['coptions']['opt_noperm_dl'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_dl'], 10848, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_bitracker_permissions', 10849, null, null, 403 );
			}
		}
		
		//-------------------------------------------
		// Got a file id?
		//-------------------------------------------
		
		if( !$info['file_id'] )
		{
			$this->registry->output->showError( 'error_generic', 10850.3, null, null, 404 );
		}

		//-------------------------------------------
		// Can we download?
		//-------------------------------------------
		
		if( !in_array( $info['file_cat'], $this->registry->getClass('categories')->member_access['download'] ) )
		{
			if( $category['coptions']['opt_noperm_dl'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_dl'], 10851, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'cannot_do_download', 10852, null, null, 403 );
			}
		}
		
		if( ! in_array( $info['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) OR ! in_array( $info['file_cat'], $this->registry->getClass('categories')->member_access['show'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_view'], 10851.1, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_permitted_categories', 10852.1, null, null, 403 );
			}
		}
		
		if( !$info['file_open'] AND $this->memberData['member_id'] != $info['file_submitter'] )
		{
			if( ! $this->registry->getClass('bitFunctions')->checkPerms( $info ) )
			{
				if( $category['coptions']['opt_noperm_dl'] )
				{
					$this->registry->output->showError( $category['coptions']['opt_noperm_dl'], 10853, null, null, 403 );
				}
				else
				{
					$this->registry->output->showError( 'cannot_do_download', 10854, null, null, 403 );
				}
			}
		}
		
		//-----------------------------------------
		// Paid?
		//-----------------------------------------
				
		if ( $checkPaid and IPSLib::appIsInstalled('nexus') and $this->settings['bit_nexus_on'] and !$this->memberData['bit_bypass_paid'] and $info['file_submitter'] != $this->memberData['member_id'] )
		{
			require_once( IPSLib::getAppDir('nexus') . '/sources/nexusApi.php' );/*noLibHook*/
			
			$check = FALSE;
			if ( $info['file_cost'] )
			{
				$check = nexusApi::itemIsPurchased( $this->memberData['member_id'], 'bitracker', 'file', $info['file_id'] );
			}
			elseif ( $info['file_nexus'] )
			{
				foreach ( explode( ',', $info['file_nexus'] ) as $id )
				{
					$check = nexusApi::itemIsPurchased( $this->memberData['member_id'], 'nexus', 'package', $id );
					if ( $check == 'ACTIVE' )
					{
						break;
					}
				}
			}
			else
			{
				$check = 'ACTIVE';
			}
						
			if ( $check != 'ACTIVE' )
			{
				$this->_buy( $info );
				$this->registry->output->setTitle( $this->page_title . ' - ' . $this->settings['board_name'] );
				$this->registry->output->addContent( $this->output );
				$this->registry->output->sendOutput();
			}
		}
	}
	
	/**
	 * Check anti-leech
	 *
	 * @param	array 		File info
	 * @return	@e void
	 */	
	protected function _checkAntiLeech( $file )
	{
		if( $this->settings['bit_antileech'] && isset($_SERVER['HTTP_REFERER']) )
		{
			$referer	= $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : getenv("HTTP_REFERER");
			$host		= parse_url($referer);

			if( str_replace( 'www.', '', $host['host'] ) != str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) )
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=bitracker&amp;showfile={$file['file_id']}", $file['file_name_furl'], true, 'bitshowfile' );
			}
		}
	}
	
	/**
	 * Start a download session
	 *
	 * @param	array 	File data
	 * @return	@e void
	 */	
	protected function _startSession( $info )
	{
		$dsess_id = md5( uniqid( microtime(), true ) );

		$this->DB->insert( 'bitracker_sessions', array( 'dsess_id' 		=> $dsess_id,
														'dsess_mid'		=> $this->memberData['member_id'],
														'dsess_ip'		=> $this->member->ip_address,
														'dsess_file'	=> $info['record_id'],
														'dsess_start'	=> time()
							)							);

		//-----------------------------------------
		// Shutdown query to remove session once done
		//-----------------------------------------
		
		$this->DB->delete( 'bitracker_sessions', "dsess_id='{$dsess_id}'", '', array(), true );
	} 
	
	/**
	 * Get download disposition
	 *
	 * @param	string		File mimetype
	 * @param	array 		Category
	 * @return	string		inline / attachment
	 */	
	protected function _getDisposition( $mime, $category )
	{
		$disposition	= 'attachment';
		$types			= array( 'inline'	=> array() );
		
		if( count( $this->cache->getCache('bit_mimetypes') ) )
		{
			foreach( $this->cache->getCache('bit_mimetypes') as $k => $v )
			{
				$inline = explode( ",", $v['mime_inline'] );

				if( in_array( $category['coptions']['opt_mimemask'], $inline ) )
				{
					$types['inline'][] = $v['mime_mimetype'];
				}
			}
		}
		
		if( in_array( $mime, $types['inline'] ) )
		{
			$disposition = "inline";
		}
		
		return $disposition;
	}
	
	/**
	 * Send the content-disposition header.  This is wrapped in a function to attempt to handle some utf-8 fun stuff.
	 *
	 * @link	http://bugs.---.com/tracker/issue-22711-utf-8-file-name-bug/
	 * @link	http://tools.ietf.org/html/rfc2231
	 * @link	http://greenbytes.de/tech/tc2231/#attwithfn2231utf8
	 * @param	string		Disposition
	 * @param	string		File name
	 * @return	@e void
	 */
	protected function _sendDispositionHeader( $disposition, $name )
	{
		if( in_array( $this->memberData['userAgentKey'], array( 'firefox', 'opera' ) ) )
		{
			@header( 'Content-Disposition: ' . $disposition . "; filename*={$this->settings['gb_char_set']}''" . rawurlencode($name) );
		}
		else if( in_array( $this->memberData['userAgentKey'], array( 'explorer' ) ) )
		{
			@header( 'Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($name) . '"' );
		}
		else
		{
			@header( 'Content-Disposition: ' . $disposition . '; filename="' . $name . '"' );
		}
	}
	
	/**
	 * Log the download
	 *
	 * @param	integer		File id
	 * @param	integer		File size
	 * @param	array 		File information
	 * @return	@e void
	 */	
	protected function _logDownload( $file_id, $file_size, $info )
	{
		if( $this->settings['bit_logallbitracker'] )
		{
			$range_head	= $this->settings['bit_range_support'] ? $_SERVER['HTTP_RANGE'] : null;
			
			//-----------------------------------------
			// If this is a range (above 0), do not log
			//-----------------------------------------
			
			if( $range_head ) 
			{
				list( $a, $range ) 					= explode( "=", $range_head );
				$ranges								= explode( ",", $range );
				
			    //------------------------------------------
			    // Just one range?
			    //------------------------------------------
			    
				if( count($ranges) == 1 )
				{
					list( $start_range, $end_range ) 	= explode( "-", $range );

				    //------------------------------------------
				    // No start (last x bytes of file)
				    //------------------------------------------
				    
					if( !$start_range )
					{
						$size	= $file_size - 1;
					}
					else if( !$end_range )
					{
						$size	= $file_size - 1;
					}
					else
					{
						$size	= $end_range;
					}
		
				    //------------------------------------------
				    // If range is valid, and start is higher
				    // than 1, just return
				    //------------------------------------------
				    
					if( $start_range < $size AND $end_range < $size )
					{
						if( $start_range > 1 )
						{
							return;
						}
					}
				}
				
			    //------------------------------------------
			    // Multiple ranges...
			    //------------------------------------------
		    
				else
				{
					//-----------------------------------------
					// Loop over ranges and make sure none are
					// for start range.
					//-----------------------------------------
					
					$shouldLog		= false;
					
					foreach( $ranges as $arange )
					{
					    //------------------------------------------
					    // Get start and end range request
					    //------------------------------------------
				    
						list( $start_range, $end_range ) 	= explode( "-", $arange );
						
					    //------------------------------------------
					    // No start (last x bytes of file)
					    //------------------------------------------
					    
						if( !$start_range )
						{
							$size	= $file_size - 1;
						}
						else if( !$end_range )
						{
							$size	= $file_size - 1;
						}
						else
						{
							$size	= $end_range;
						}
			
					    //------------------------------------------
					    // If range is valid, and start is higher
					    // than 1, just return
					    //------------------------------------------
					    
						if( $start_range < $size AND $end_range < $size )
						{
							if( $start_range > 1 )
							{
								continue;
							}
						}
						
						$shouldLog	= true;
						break;
					}
					
					if( !$shouldLog )
					{
						return;
					}
				}
			}

			//-----------------------------------------
			// Fix log data
			//-----------------------------------------
			
			if( $this->member->operating_system == 'unknown' )
			{
				if( strstr( strtolower($_SERVER['HTTP_USER_AGENT']), 'linux' ) )
				{
					$this->member->operating_system = 'linux';
				}
			}
			
			$to_insert = array( 'dfid' 		=> $file_id,
								'dtime'		=> time(),
								'dip'		=> $this->member->ip_address,
								'dmid'		=> $this->memberData['member_id'],
								'dsize'		=> $file_size,
								'dua'		=> substr( IPSText::parseCleanValue( $_SERVER['HTTP_USER_AGENT'] ), 0, 255 )
								);
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/traffic.php', 'trafficLibrary', 'bitracker' );
			$traffic		= new $classToLoad( $this->registry );
			$traffic->loadLibraries();
			
			$parsed_visitor = $traffic->returnStatData( $to_insert );
			
			$to_insert['dbrowsers']	= $parsed_visitor['stat_browser_key'];
			$to_insert['dos']		= $parsed_visitor['stat_os_key'];
			
			$this->DB->insert( "bitracker_bitracker", $to_insert );
		}
		
		$this->DB->update( "bitracker_files", 'file_bitracker=file_bitracker+1', 'file_id=' . $file_id, false, true );

		/* Log Nexus download */
		if ( IPSLib::appIsInstalled('nexus') and ( $info['file_cost'] or $info['file_nexus'] ) )
		{
			require_once( IPSLib::getAppDir('nexus') . '/sources/customer.php' );/*noLibHook*/
			customer::loggedIn()->logAction( 'download', array( 'type' => 'bit', 'id' => $info['file_id'], 'name' => $info['file_name'] ) );
		}
	}

	/**
	 * Get the "download as" name
	 *
	 * @param	string		Key to look for original name in
	 * @param	array 		File data
	 * @return	string		Name to show for "download as"
	 */	
	protected function _getDownloadName( $key, $info )
	{
		if( $info[ $key ] )
		{
			return str_replace( '&amp;', '&', $info[ $key ] );
		}
		else
		{
			$filename = $info['record_location'];
			
			$curr_ext = strrchr( $filename, "." );
			
			if( $curr_ext == ".txt" AND $curr_ext != "." . $info['mime_extension'] )
			{
				$filename = str_replace( $curr_ext, "." . $info['mime_extension'], $filename );
			}
	
			$filename = str_replace( $info['file_id'] . "-", "", $filename );
			$filename = preg_replace( "/^\d{10,11}\-(.+?)$/", "\\1", $filename );
			
			return $filename;
		}
	}
	
	/**
	 * Is member restricted?
	 *
	 * @param	array 		File data
	 * @param	integer		Bytes of this file
	 * @return	@e void		Shows an error if member is restricted
	 */	
	protected function _sortRestrictions( $info, $fileSize=0 )
    {
	    $my_groups = array( $this->memberData['member_group_id'] );
	    
	    if( $this->memberData['mgroup_others'] )
	    {
		    $other_mgroups = explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
		    
		    $my_groups = array_merge( $my_groups, $other_mgroups );
	    }
	    
	    $can_download = 1;
	    
	    // First, loop through groups and determine what restrictions are placed on member (better overrides worse)
	    // Then, loop through the restrictions and see if they're blocked
	    // If blocked, set can_download to 0, break loop, and show error
	    
	    $my_restrictions 	= array();
	    $less_is_more		= array( 'min_posts', 'posts_per_dl' );
	    $group_cache		= $this->cache->getCache('group_cache');
	    
	    foreach( $my_groups as $gid )
	    {
		    $group = $group_cache[ $gid ];
		   	
		    $this_restrictions	= array();
		    $this_restrictions	= unserialize( $group['bit_restrictions'] );
		    
		    if( is_array( $this_restrictions ) AND count( $this_restrictions ) )
		    {
			    if( $this_restrictions['enabled'] == 1 )
			    {
				    foreach( $this_restrictions as $k => $v )
				    {
					    if( isset($my_restrictions[$k]) AND $my_restrictions[$k] == 0 )
					    {
						    // Zero is always best - it means no restriction
						    continue;
					    }
					    else if( in_array( $k, $less_is_more ) )
					    {
						    // Lower the better for post-based restrictions
						    
						    if( isset( $my_restrictions[$k] ) )
						    {
							    if( $v < $my_restrictions[$k] )
							    {
								    $my_restrictions[$k] = $v;
							    }
						    }
						    else
						    {
							    $my_restrictions[$k] = $v;
						    }
					    }
					    else
					    {
						    // Higher the better for bw/dl restrictions
						    
						    if( $v > intval($my_restrictions[$k]) )
						    {
							    $my_restrictions[$k] = $v;
						    }
						    
						    // 0 is best however
						    
						    else if( $v === 0 )
						    {
						    	$my_restrictions[$k] = $v;
						    }
					    }
				    }
			    }
		    }
	    }

	    // Now we should have this member's restrictions in place.
	    // Let's check...if all are 0, go ahead and return now
	    
	    if( !is_array($my_restrictions) OR !count($my_restrictions) )
	    {
		    // No restrictions
		    return;
	    }
	    else
	    {
		    $at_least_one = 0;
		    
		    foreach( $my_restrictions as $k => $v )
		    {
			    if( $v > 0 )
			    {
				    $at_least_one = 1;
				    break;
			    }
		    }
		    
		    if( $at_least_one == 0 )
		    {
			    // All restrictions disabled
			    return;
		    }
	    }
	    
	    // Still here?  Ok, check restrictions
	    // Before we loop, let's get the counts we'll need (easier to do this in three queries)
	    // If this is a guest, check IP too
	    
		$ip_check = !$this->memberData['member_id'] ? " AND dip='{$this->member->ip_address}'" : '';
	    
	    $one_day	= time() - 86400;
	    $daily 		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as dl, SUM(dsize) as bw', 'from' => 'bitracker_bitracker', 'where' => 'dmid='.$this->memberData['member_id'].' AND dtime > '.$one_day . $ip_check ) );
	    
	    $one_week	= time() - 604800;
	    $weekly		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as dl, SUM(dsize) as bw', 'from' => 'bitracker_bitracker', 'where' => 'dmid='.$this->memberData['member_id'].' AND dtime > '.$one_week . $ip_check ) );
	    
	    $one_month	= time() - 2592000;
	    $monthly	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as dl, SUM(dsize) as bw', 'from' => 'bitracker_bitracker', 'where' => 'dmid='.$this->memberData['member_id'].' AND dtime > '.$one_month . $ip_check ) );
	    
	    // If we have fileSize parameter to the function, add it to the total
	    // counts.  We don't want to be able to go over restrictions
	    // Example: 15MB restriction, I download a 500MB file - should be restricted
	    
	    if( $fileSize )
	    {
	    	$daily['bw']	= intval($daily['bw']) + $fileSize;
	    	$weekly['bw']	= intval($weekly['bw']) + $fileSize;
	    	$monthly['bw']	= intval($monthly['bw']) + $fileSize;
    	}
	    
	    foreach( $my_restrictions as $k => $v )
	    {
		    if( $v > 0 )
		    {
			    if( $k == 'min_posts' AND $v )
			    {
				    if( $this->memberData['posts'] < $v )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_min_posts', 10855, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'posts_per_dl' AND $v )
			    {
				    // Get last download stamp
				    
				    $download = $this->DB->buildAndFetch( array( 'select' => 'MAX(dtime) as dtime', 'from' => 'bitracker_bitracker', 'where' => 'dmid=' . $this->memberData['member_id'] . $ip_check ) );
				    
				    if( $download['dtime'] )
				    {
					    $posts = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as num', 'from' => 'posts', 'where' => 'author_id=' . $this->memberData['member_id'] . ' AND post_date>' . $download['dtime'] ) );
					    
					    if( $posts['num'] < $v )
					    {
					    	$this->restricted = 1;
					    	
					    	$this->registry->output->showError( 'dl_restrict_posts_p_dl', 10856, null, null, 403 );
					    }
				    }
			    }
			    
			    if( $k == 'daily_bw' AND $daily['bw'] AND $v )
			    {
				    if( $daily['bw'] >= ($v*1024) )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_daily_bw', 10857, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'weekly_bw' AND $weekly['bw'] AND $v )
			    {
				    if( $weekly['bw'] >= ($v*1024) )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_weekly_bw', 10858, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'monthly_bw' AND $monthly['bw'] AND $v )
			    {
				    if( $monthly['bw'] >= ($v*1024) )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_monthly_bw', 10859, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'daily_dl' AND $daily['dl'] AND $v )
			    {
				    if( $daily['dl'] >= $v )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_daily_dl', 10860, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'weekly_dl' AND $weekly['dl'] AND $v )
			    {
				    if( $weekly['dl'] >= $v )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_weekly_dl', 10861, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'monthly_dl' AND $monthly['dl'] AND $v )
			    {
				    if( $monthly['dl'] >= $v )
				    {
				    	$this->restricted = 1;
				    	
				    	$this->registry->output->showError( 'dl_restrict_monthly_dl', 10862, null, null, 403 );
				    }
			    }
			    
			    if( $k == 'limit_sim' AND $v )
			    {
				    $this->DB->build( array( 'select' => '*', 'from' => 'bitracker_sessions', 'where' => "dsess_mid={$this->memberData['member_id']}" . ( !$this->memberData['member_id'] ? " AND dsess_ip='{$this->member->ip_address}'" : '' ) ) );
				    $this->DB->execute();
				    
				    while( $r = $this->DB->fetch() )
				    {
					    $dl_sessions[] = $r;
				    }

				    $sess_count = 0;
				    
					if( count($dl_sessions) )
					{
						foreach( $dl_sessions as $session )
						{
							// If this is a request for the same file and the HTTP_RANGE header is sent don't count
							// It's probably a download manager.  If HTTP_RANGE isn't set, member is trying to download two copies simultaneously
							
							if( $info['record_id'] == $session['dsess_file'] AND $_SERVER['HTTP_RANGE'] )
							{
								continue;
							}
							
							$sess_count++;
						}
					}
					
					if( $sess_count >= $v )
					{
				    	$this->restricted = 1;

				    	$this->registry->output->showError( 'dl_restrict_sim', 10863, null, null, 403 );
				    }
			    }
		    }
	    }
    }
	
	/**
	 * Download a file stored on the local disk
	 *
	 * @param	array 		File information
	 * @return	mixed		Prints the file data to the browser for download or false if there is a problem
	 */	
	protected function _downloadLocalFile( $file=array() )
    {
	    if( !is_array($file) OR !count($file) OR !$file['true_file'] )
	    {
		    return FALSE;
	    }
	    
	    if( !is_file( $file['true_file'] ) )
	    {
		    return FALSE;
	    }

	    //------------------------------------------
	    // To ensure this members IP is upto date in
	    // the database lets update it now.
	    //------------------------------------------

           //if ( isset($_SERVER['REMOTE_ADDR']) ){
                
            // if ( $_SERVER['REMOTE_ADDR'] != $this->memberData['ip_address'] ){
                      
                 // Update members IP address
				 
                 // $this->DB->update( 'members', array( 'ip_address' => $_SERVER['REMOTE_ADDR'] ), 'member_id=' . $this->memberData['member_id'] ); 
				  
                 // }
            //  }

	    //------------------------------------------
	    // Check the member has their permission key
	    // set? if not make one and set it.
	    //------------------------------------------

         if ( $this->memberData['perm_key'] == ''){

          $stringToHash = $this->memberData['ip_address'] . $this->memberData['joined'] . $this->memberData['member_id'];

            $hash = md5( $stringToHash );

             $memberPermKey = IPSMember::generateCompiledPasshash($this->memberData['members_pass_salt'], $hash);

            $this->DB->update( 'members', array( 'perm_key' => $memberPermKey ), 'member_id=' . $this->memberData['member_id'] );

           $this->memberData['perm_key'] = $memberPermKey;

          }	

		//------------------------------------------------------------------
		// Private Tracker = Add this members permission_key to the announce.
		//------------------------------------------------------------------

			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/bit-benc.php', 'benc', 'bitracker' );

    		$decoder			= new $classToLoad( $this->registry, $TorrentInfo = array());

            $this->TorrentInfo = $decoder->DecodeTorrentRaw( $file['true_file'] );

        // We need the cookie info

        //    $ipsConnCookie = IPSCookie::get( "ipsconnect_" . md5( $this->settings['board_url'] . '/interface/ipsconnect/ipsconnect.php' ) );

        //   $ipsConn = md5($ipsConnCookie);
               
                 if(strpos($this->TorrentInfo['announce'],'?') !== false){

                       $this->TorrentInfo['announce'] .= "&perm_key={$this->memberData['perm_key']}";

                     } else {

                       $this->TorrentInfo['announce'] .= "-{$this->memberData['perm_key']}";

                     }

		//---------------------------------
		// wrap it up and send it packing!!
		//---------------------------------

	         require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/torrent_handlers/BEncode.php' );/*noLibHook*/

             $this->TorrentInfo = BEncode( $this->TorrentInfo );
	    
	    //-----------------------------------------
	    // Multiple periods in filename in IE cause brackets
	    // i.e. file[1].something.ext
	    //-----------------------------------------

	    if( $this->memberData['userAgentKey'] == 'explorer' )
	    {
		    $file['name'] = preg_replace( '/\./', '%2e', $file['name'], substr_count( $file['name'], '.' ) - 1 );
	    }
	    

			//-------------------------------------------
			// Log download
			//-------------------------------------------
			
			$this->_logDownload( $file['id'], $file['size'], $file );

			$size = $filesize - 1;

			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) AND strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) )
			{
				@header( "HTTP/1.0 200 OK" );
			}
			else
			{
				@header( "HTTP/1.1 200 OK" );
			}

			if( !ini_get('zlib.output_compression') OR ini_get('zlib.output_compression') == 'off' )
			{
				@header( "Content-Length: {$length}" );
			}
			
			@header( "Content-Transfer-Encoding: binary" );
			
			if( $this->settings['bit_range_support'] )
			{
				@header( "Content-Range: bytes 0-{$size}/{$filesize}" );
			}
		
	    //------------------------------------------
	    // Not requesting multiple ranges, set
	    //	filetype and disposition properly
	    //------------------------------------------

			@header( "Content-Type: ".$file['mimetype'] );
			
			$this->_sendDispositionHeader( $file['disposition'], $file['name'] );

		//-----------------------------------------
		// Clean output buffer
		//-----------------------------------------
		
		@ob_end_clean();
		
	    //------------------------------------------
	    // Throttle download speed?
	    //------------------------------------------
	    
	    $_throttle	= false;
	    $_max		= 4096;
	    
	    if( $this->memberData['bit_throttling'] )
	    {
	    	$_throttle	= true;
	    	$_max		= $this->memberData['bit_throttling'] * 1024;
	    }


			
		echo ( $this->TorrentInfo );

		
		flush();
	    @ob_flush();

		/* If we are throttling, wait 1 second, for the kb/sec*/
			if( $_throttle )
				{
				sleep(1);
				}		
		return true;

		}
		
    
    /**
	 * Purchase a paid file
	 *
	 * @param	array 		File info (optional)
	 * @return	@e void		[Redirects]
	 */
	protected function _buy( $file=array() )
	{
		if ( !IPSLib::appIsInstalled('nexus') )
		{
			$this->registry->output->showError( 'error_generic', 10870, null, null, 500 );
		}
	
		//-----------------------------------------
		// Get file info
		//-----------------------------------------
		
		if( !$file['file_id'] )
		{
			$id		= intval( $this->request['id'] );
			
			$file	= $this->DB->buildAndFetch( array( 'select'		=> '*',
														'from'		=> "bitracker_files", 
														'where'		=> 'file_id=' . $id
												)		);
												
			if( !$file['file_id'] )
			{
				$this->registry->output->showError( 'error_generic', 10871, null, null, 404 );
			}
		}
		
		//-----------------------------------------
		// Is it even a paid file?
		//-----------------------------------------
		
		if ( !$file['file_nexus'] and !$file['file_cost'] )
		{
			$this->_doDownload( $file['file_id'] );
			return;
		}
		
		/* Can download? */
		$this->_canDownload( $file, $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ], FALSE );
		
		//-----------------------------------------
		// Disclaimer
		//-----------------------------------------
		
		if ( !$this->request['confirm'] )
		{
			$category	= $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
			if( $category['cdisclaimer'] )
			{
				/* Sort out navigation and page title */
				foreach( $this->registry->getClass('categories')->getNav( $file['file_cat'] ) as $navigation )
				{
					$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
				}
				
				$this->registry->output->addNavigation( $file['file_name'], '' );
				
				$this->page_title = $file['file_name'];
				
				$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_other')->confirmToBuy( $file, $category );
				return;
			}
		}
						
		//-----------------------------------------
		// Generate Invoice
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('nexus') . '/sources/nexusApi.php' );/*noLibHook*/
		
		// Boink to store?
		if ( $file['file_nexus'] )
		{
			$items = explode( ',', $file['file_nexus'] );
			if ( count( $items ) == 1 )
			{
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=nexus&module=payments&section=store&do=item&id=" . array_shift( $items ) );
			}
			else
			{
				$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . "app=nexus&module=payments&section=store&file={$file['file_id']}" );
			}
			return;
		}
		
		try
		{		
			$invoice = nexusApi::generateInvoice( $file['file_name'], $this->memberData['member_id'], array(
				array(
					'act'			=> 'new',
					'app'			=> 'bitracker',
					'type'			=> 'file',
					'cost'			=> $file['file_cost'],
					'tax'			=> $this->settings['bit_nexus_tax'],
					'renew_term'	=> $file['file_renewal_term'],
					'renew_units'	=> $file['file_renewal_units'],
					'renew_cost'	=> $file['file_renewal_price'],
					'physical'		=> FALSE,
					'itemName'		=> $file['file_name'],
					'itemID'		=> $file['file_id'],
					'itemURI'		=> "app=bitracker&module=display&section=file&id={$file['file_id']}",
					'payTo'			=> $file['file_submitter'],
					'commission'	=> $this->settings['bit_nexus_percent'],
					'fee'			=> $this->settings['bit_nexus_transfee'],
					)
				), "app=bitracker&module=display&section=file&id={$file['file_id']}" );
		}
		catch ( Exception $e )
		{
			$this->registry->output->showError( sprintf( $this->lang->words['error_generating_invoice'], $e->getMessage() ), 12345 );
		}
			
		//-----------------------------------------
		// Boink
		//-----------------------------------------

		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . $this->registry->getClass('output')->buildUrl( 'app=nexus&amp;module=payments&amp;section=pay&amp;id=' . $invoice, $this->settings['nexus_https'] ) );
	}

    /**
	 * Verify user waited if they were supposed to
	 *
	 * @return	@e void
	 */
	protected function _checkAndClearTimer()
	{
		if( $this->memberData['bit_wait_period'] )
		{
			$timestamp	= IPSCookie::get( 'bit_wait_period' );
			
			/* No cookie, but we are supposed to wait.  Set now and show error */
			if( !$timestamp )
			{
				IPSCookie::set( 'bit_wait_period', time() );
				
				$this->registry->output->showError( sprintf( $this->lang->words['wait_period_remaining'], $this->memberData['bit_wait_period'], $this->memberData['bit_wait_period'] ), 1087991 );
			}
			else
			{
				/* Have we waited long enough yet? */
				$_period	= time() - $timestamp;
				
				if( $_period < $this->memberData['bit_wait_period'] )
				{
					$this->registry->output->showError( sprintf( $this->lang->words['wait_period_remaining'], $_period, $_period ), 1087992 );
				}
				
				IPSCookie::set( 'bit_wait_period', 0 );
			}
		}
	}
}
