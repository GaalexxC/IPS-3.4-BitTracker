<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.1.1
 * bit output screenshot
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

class public_bitracker_display_screenshot extends ipsCommand
{
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
		// Don't update session
		//-------------------------------------------
		
		$this->DB->obj['shutdown_queries']	= array();
		
		//-------------------------------------------
		// Block item markers from updating in destructor
		//-------------------------------------------
		
		$this->member->is_not_human			= true;
		
		//-------------------------------------------
		// Have an id?
		//-------------------------------------------
		
		if( !$this->request['id'] AND !$this->request['temp'] )
		{
			$this->_safeExit();
		}
		
		$file_id	= intval($this->request['id']);
		$record_id	= intval($this->request['record']);
		$temp_id	= intval($this->request['temp']);
		
		//-------------------------------------------
		// Clear output buffer
		//-------------------------------------------
		
		ob_end_clean();
		
		//-----------------------------------------
		// Grab image library
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classImage.php' );/*noLibHook*/
		require_once( IPS_KERNEL_PATH . 'classImageGd.php' );/*noLibHook*/
		$image = new classImageGd();
		
		//-------------------------------------------
		// Get file
		//-------------------------------------------
		
		$where = "r.record_file_id=" . $file_id;
		
		if( $record_id )
		{
			$where	.= " AND r.record_id=" . $record_id;
		}
		
		if( $temp_id )
		{
			$ss = $this->DB->buildAndFetch( array(
												'select'	=> 'r.*',
												'from'		=> array( 'bitracker_temp_records' => 'r' ),
												'where'		=> "record_id=" . $temp_id,
												'limit'		=> array( 1 ),
												'add_join'	=> array(
																	array(
																			'select'	=> 'm.mime_mimetype',
																			'from'		=> array( 'bitracker_mime' => 'm' ),
																			'where'		=> 'm.mime_id=r.record_mime',
																			'type'		=> 'left'
																		),
																	)
												)		);

			$ss['record_storagetype']	= 'disk';
		}
		else
		{
			if( !$record_id )
			{
				//-----------------------------------------
				// Try to load "default" if none specified
				//-----------------------------------------
				
				$ss = $this->DB->buildAndFetch( array(
													'select'	=> 'r.*',
													'from'		=> array( 'bitracker_files_records' => 'r' ),
													'where'		=> $where . " AND record_default=1 AND record_type IN('ssupload','sslink') AND record_backup=0",
													'add_join'	=> array(
																		array(
																				'select'	=> 's.storage_ss, s.storage_thumb',
																				'from'		=> array( 'bitracker_filestorage' => 's' ),
																				'where'		=> 's.storage_id=r.record_db_id',
																				'type'		=> 'left'
																			),
																		array(
																				'select'	=> 'f.file_cat',
																				'from'		=> array( 'bitracker_files' => 'f' ),
																				'where'		=> 'f.file_id=r.record_file_id',
																				'type'		=> 'left'
																			),
																		array(
																				'select'	=> 'm.mime_mimetype',
																				'from'		=> array( 'bitracker_mime' => 'm' ),
																				'where'		=> 'm.mime_id=r.record_mime',
																				'type'		=> 'left'
																			),
																		)
													)		);
				if( !$ss['record_id'] )
				{
					$ss = $this->DB->buildAndFetch( array(
														'select'	=> 'r.*',
														'from'		=> array( 'bitracker_files_records' => 'r' ),
														'where'		=> $where . " AND record_type IN('ssupload','sslink') AND record_backup=0",
														'limit'		=> array( 1 ),
														'add_join'	=> array(
																			array(
																					'select'	=> 's.storage_ss, s.storage_thumb',
																					'from'		=> array( 'bitracker_filestorage' => 's' ),
																					'where'		=> 's.storage_id=r.record_db_id',
																					'type'		=> 'left'
																				),
																			array(
																					'select'	=> 'f.file_cat',
																					'from'		=> array( 'bitracker_files' => 'f' ),
																					'where'		=> 'f.file_id=r.record_file_id',
																					'type'		=> 'left'
																				),
																			array(
																					'select'	=> 'm.mime_mimetype',
																					'from'		=> array( 'bitracker_mime' => 'm' ),
																					'where'		=> 'm.mime_id=r.record_mime',
																					'type'		=> 'left'
																				),
																			)
														)		);
				}
			}
			else
			{
				$ss = $this->DB->buildAndFetch( array(
													'select'	=> 'r.*',
													'from'		=> array( 'bitracker_files_records' => 'r' ),
													'where'		=> $where . " AND record_type IN('ssupload','sslink') AND record_backup=0",
													'limit'		=> array( 1 ),
													'add_join'	=> array(
																		array(
																				'select'	=> 's.storage_ss, s.storage_thumb',
																				'from'		=> array( 'bitracker_filestorage' => 's' ),
																				'where'		=> 's.storage_id=r.record_db_id',
																				'type'		=> 'left'
																			),
																		array(
																				'select'	=> 'f.file_cat',
																				'from'		=> array( 'bitracker_files' => 'f' ),
																				'where'		=> 'f.file_id=r.record_file_id',
																				'type'		=> 'left'
																			),
																		array(
																				'select'	=> 'm.mime_mimetype',
																				'from'		=> array( 'bitracker_mime' => 'm' ),
																				'where'		=> 'm.mime_id=r.record_mime',
																				'type'		=> 'left'
																			),
																		)
													)		);
			}
		}

		//-------------------------------------------
		// Switch on the storage type...
		//-------------------------------------------
		
		switch( $ss['record_storagetype'] )
		{
			case 'disk':
				if( $ss['record_type'] == 'sslink' )
				{
					$content	= @file_get_contents( $ss['record_location'] );
					
					if( !$content )
					{
						$this->_safeExit();
					}

					$ext		= strtolower( str_replace( ".", "", substr( $ss['record_location'], strrpos( $ss['record_location'], '.' ) ) ) );
					$path		= $this->settings['upload_dir'];
					$thumb		= md5( uniqid( microtime() ) ) . '.' . $ext;
					$using_full	= true;
					
					$fh = @fopen( $path . '/' . $thumb, 'wb' );
					@fputs ($fh, $content, strlen($content) );
					@fclose($fh);
				}
				else
				{
					if( $ss['record_thumb'] )
					{
						$thumb = $this->request['full'] ? $ss['record_location'] : $ss['record_thumb'];
					}
					else if( $ss['record_location'] )
					{
						$using_full	= true;
						$thumb		= $ss['record_location'];
					}
					else
					{
						$this->_safeExit();
					}
					
					$path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/";
				}
			break;
				
			case 'db':
				if( $ss['storage_thumb'] )
				{
					$content = $this->request['full'] ? base64_decode($ss['storage_ss']) : base64_decode($ss['storage_thumb']);
				}
				else if( $ss['storage_ss'] )
				{
					$using_full	= true;
					$content	= base64_decode($ss['storage_ss']);
				}
				
				if( !$content )
				{
					$this->_safeExit();
				}
				
				$bits		= explode( '.', $ss['record_location'] );
				$extension	= strtolower( array_pop( $bits ) );
				$path		= $this->settings['upload_dir'];
				$thumb		= md5( uniqid( microtime() ) ) . '.' . $extension;
				
				$fh = @fopen( $path . '/' . $thumb, 'wb' );
				@fputs ($fh, $content, strlen($content) );
				@fclose($fh);
			break;

			case 'ftp':
				if( $ss['record_thumb'] )
				{
					$thumb = $this->request['full'] ? $ss['record_location'] : $ss['record_thumb'];
				}
				else if( $ss['record_location'] )
				{
					$using_full	= true;
					$thumb		= $ss['record_location'];
				}
				else
				{
					$this->_safeExit();
				}
				
				$path	= $this->settings['upload_dir'];
				
				if( $this->settings['bit_remoteurl'] AND
					$this->settings['bit_remoteport'] AND
					$this->settings['bit_remoteuser'] AND
					$this->settings['bit_remotepass'] AND
					$this->settings['bit_remotefilepath'] )
				{
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFtp.php', 'classFtp' );
					
					try
					{
						classFtp::$transferMode	= FTP_BINARY;
	
						$_ftpClass		= new $classToLoad( $this->settings['bit_remoteurl'], $this->settings['bit_remoteuser'], $this->settings['bit_remotepass'], $this->settings['bit_remoteport'], '/', true, 999999 );
						
						$_ftpClass->chdir( $this->settings['bit_remotesspath'] );
						$_ftpClass->file( $thumb )->download( $path . '/' . $thumb );
						
						unset( $_ftpClass );
					}
					catch( Exception $e )
					{
						$this->_safeExit();
					}
				}
			break;					
		}

		if( !$path AND !$thumb )
		{
			$this->_safeExit();
		}


		//-----------------------------------------
		// Watermark/copyright text
		//-----------------------------------------
				
		if( ($this->request['full'] OR $using_full == true) AND ($this->settings['bit_addwatermark'] OR $this->settings['bit_addcopyright']) )
		{
			//-----------------------------------------
			// Initialize library
			//-----------------------------------------

			if( !$image->init( array( 'image_path' => $path, 'image_file' => $thumb ) ) )
			{
				if( in_array( $ss['record_storagetype'], array( 'db', 'ftp' ) ) OR $ss['record_type'] == 'sslink' )
				{
					$this->_removeTempImage( $path, $thumb );
				}
				
				$this->_safeExit();
			}
		
			if( $this->settings['bit_addwatermark'] )
			{
				$image->addWatermark( $this->settings['bit_watermarkpath'] );
			}
			else if( $this->settings['bit_addcopyright'] )
			{
				$image->addCopyrightText( ( strpos( $this->settings['bit_copyrighttext'], '%s' ) !== false ) ? sprintf( $this->settings['bit_copyrighttext'], date('Y') ) : $this->settings['bit_copyrighttext'] );
			}
			
			//-----------------------------------------
			// Remove temp image
			//-----------------------------------------
			
			if( in_array( $ss['record_storagetype'], array( 'db', 'ftp' ) ) OR $ss['record_type'] == 'sslink' )
			{
				$this->_removeTempImage( $path, $thumb );
			}
			
			//-----------------------------------------
			// Display
			//-----------------------------------------
            $image->resizeImage( 600, 480 );
			$image->displayImage(); 

		}
		
		//-----------------------------------------
		// Just print the image from disk
		//-----------------------------------------
		
		else
		{
			if( !$this->request['full'] AND !$using_full )
			{
				//-----------------------------------------
				// Resize linked screenshots...
				//-----------------------------------------
				
				/* Check for cache */
				$_fullPath	= $path ? $path . '/' . $thumb : $thumb;
			
				if( ! is_file( $_fullPath ) )
				{
					$category	= $this->registry->getClass('categories')->cat_lookup[ $ss['file_cat'] ];
					$_default	= intval($this->settings['bit_default_dimensions']);
					
					if( !$category['coptions']['opt_thumb_x'] )
					{
						$category['coptions']['opt_thumb_x']	= $_default;
					}
	
					if( $category['coptions']['opt_thumb_x'] )
					{
						if( $image->init( array( 'image_path' => $path, 'image_file' => $thumb ) ) )
						{							
							if( $this->settings['bit_addwatermark'] )
							{
								$image->addWatermark( $this->settings['bit_watermarkpath'] );
							}
							else if( $this->settings['bit_addcopyright'] )
							{
								$image->addCopyrightText( ( strpos( $this->settings['bit_copyrighttext'], '%s' ) !== false ) ? sprintf( $this->settings['bit_copyrighttext'], date('Y') ) : $this->settings['bit_copyrighttext'] );
							}
							
							$return = $image->croppedResize( $category['coptions']['opt_thumb_x'], $category['coptions']['opt_thumb_x'] );
							
							if( !$image->writeImage( $path . '/' . $thumb ) )
							{
								if( in_array( $ss['record_storagetype'], array( 'db', 'ftp' ) ) OR $ss['record_type'] == 'sslink' )
								{
									$this->_removeTempImage( $path, $thumb );
								}
								
								$this->_safeExit();
							}
						}
					}
				}
			}
			
			$_fullPath	= $path ? $path . '/' . $thumb : $thumb;
			
			if( !is_file( $_fullPath ) )
			{ 
				$this->_safeExit();
			}

			//-----------------------------------------
			// Headers
			//-----------------------------------------
			
			header( "Expires: " . gmdate( "D, d M Y H:i:s", time() + 86400 ) . " GMT" );
			header( "Cache-Control:  public, max-age=86400" );
			header( "Content-Type: " . $ss['mime_mimetype'] );
			header( "Content-Disposition: inline; filename=\"" . $thumb . "\"" );
			
			if( @filesize( $_fullPath ) )
			{
				header( "Content-Length: " . (string)(@filesize( $_fullPath ) ) );
			}
			
			//-----------------------------------------
			// Open and display the file..
			//-----------------------------------------
			
			$fh = fopen( $_fullPath, 'rb' );
			fpassthru( $fh );
			@fclose( $fh );
			
			//-----------------------------------------
			// Remove temp image
			//-----------------------------------------
			
			if( in_array( $ss['record_storagetype'], array( 'db', 'ftp' ) ) OR $ss['record_type'] == 'sslink' )
			{
				$this->_removeTempImage( $path, $thumb );
			}
		}
		
		exit;
	}
	
	/**
	 * Remove a temporary image
	 *
	 * @access	protected
	 * @param	string		Path to image
	 * @param	string		Image filename
	 * @return	boolean
	 */	
	protected function _removeTempImage( $path, $thumb )
	{
		if( !$path OR !$thumb )
		{
			return false;
		}
		
		if( is_file( $path . '/' . $thumb ) )
		{
			@unlink( $path . '/' . $thumb );
		}
		
		return true;
	}

	/**
	 * Print a 1x1 transparent gif and safely exist
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _safeExit()
	{
		if( is_file( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png' ) )
		{
			$content	= file_get_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png' );
			header( "Content-type: image/png" );
		}
		else
		{
			$content	= base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
			header( "Content-type: image/gif" );
		}

		header( "Connection: Close" );
		header( "Cache-Control:  public, max-age=86400" );
		header( "Expires: " . gmdate( "D, d M Y, H:i:s", time() + 86400 ) . " GMT" );
		print $content;
		flush();
		exit;
	}
}