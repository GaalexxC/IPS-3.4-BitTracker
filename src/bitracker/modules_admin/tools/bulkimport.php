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
 * @class		admin_bitracker_tools_bulkimport
 * @brief		IP.download Manager Bulk Import
 */
class admin_bitracker_tools_bulkimport extends ipsCommand
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
	 * Last timestamp (prevents duplicates)
	 *
	 * @var		$lasttime
	 */	
	protected $lasttime;
	
	/**
	 * Array of valid file tpyes
	 *
	 * @var		$valid_types
	 */	
	protected $valid_types = array();

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_bulkimport' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=bulkimport';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=bulkimport';
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ) );

		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_bulk' );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'bulkZip':
				//-----------------------------
				// Get the zip library
				//-----------------------------
				
				define( 'PCLZIP_TEMPORARY_DIR', DOC_IPS_ROOT_PATH . 'cache/tmp/' );

				switch( $this->request['op'] )
				{
					case 'del':
						$this->deleteZipFile();
					break;
					
					case 'upload':
						$this->uploadZipFile();
					break;
					
					case 'zipListAll':
						$this->zipListAll();
					break;
					
					case 'zipIndexAdd':
						$this->zipIndexAdd();
					break;
					
					default:
						$this->zipFile();
					break;
				}
			break;

			case 'bulkDir':
			default:
				switch( $this->request['op'] )
				{
					case 'doBulkAdd':
						$this->doBulkAdd();
					break;
					
					case 'viewDir':
						$this->bulkViewDir();
					break;
					
					case 'viewDirFiles':
						$this->bulkViewFiles();
					break;
					
					default:
						$this->bulkAddForm();
					break;
				}
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();	
	}

	/**
	 * Upload a zip file for processing
	 *
	 * @return	@e void
	 */
	protected function uploadZipFile()
	{
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
		$upload			= new $classToLoad();
		
		$upload->upload_form_field 	= 'zipup';
		$upload->allowed_file_ext	= array( 'zip' );
		$upload->out_file_dir 		= $this->settings['upload_dir'];
		
		$upload->process();
		
		if ( $upload->error_no )
		{
			switch( $upload->error_no )
			{		
				case 1:
					$this->registry->output->global_message = $this->lang->words['b_nofile'];
				break;
				
				case 2:
					$this->registry->output->global_message = $this->lang->words['b_onlyzip'];
				break;
				
				case 4:
					$this->registry->output->global_message = $this->lang->words['b_moveissue'] . $this->settings['upload_dir'];
				break;
				
				default:
					$this->registry->output->global_message = $this->lang->words['b_genericprob'];
				break;
			}
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['b_verygood'];
		}
		
		$this->zipFile();
	}
	
	/**
	 * Delete a previously uploaded zip file
	 *
	 * @return	@e void
	 */
	protected function deleteZipFile()
	{
	 	@unlink( $this->settings['upload_dir'] . '/' . basename($this->request['zip']) );
	 	
	 	$this->registry->output->global_message = $this->lang->words['b_zipdel'];
	 	
	 	$this->zipFile();
 	}
	
	/**
	 * Zip file management overview screen
	 *
	 * @return	@e void
	 */
	protected function zipFile()
	{
		//-----------------------------
		// Find zip files
		//-----------------------------
		
		$cat	= $this->request['cat'] ? '&amp;cat=' . $this->request['cat'] : '';
		$rows	= '';
		
		if( is_dir( $this->settings['upload_dir'] ) ) 
		{
			try
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/pclzip.lib.php', 'PclZip', 'bitracker' );
				
		 		foreach( new DirectoryIterator( $this->settings['upload_dir'] ) as $file )
		 		{
				 	if( $file->isFile() )
				 	{
				 		$extension = explode( ".", $file->getFilename() );
				 		
						if( strtolower( array_pop( $extension ) ) == 'zip' )
						{
							$zip   = new $classToLoad( $file->getPathname() );
							$info  = $zip->properties();
							$rows .= $this->html->zipImportRow( $file, $zip, $info );
						}
					}
				}
			} catch ( Exception $e ) {}
		}
		else
		{
		 	$this->registry->output->showError( $this->lang->words['b_whereohwhere'], 11819 );
		}
		
		$this->registry->output->html .= $this->html->zipImportWrapper( $rows );
	}
	
	/**
	 * Zip file review files to import
	 *
	 * @return	@e void
	 */
	protected function zipListAll()
	{
		$this->request['zip'] = trim( basename($this->request['zip']) );

		//-----------------------------
		// Get the zip library
		//-----------------------------
		
		$rows		= '';
		$checkAll	= true;
		$contents	= '';

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/pclzip.lib.php', 'PclZip', 'bitracker' );
		$zip			= new $classToLoad( $this->settings['upload_dir'] . '/' . $this->request['zip'] );
		$contents		= $zip->listContent();

		if( is_array($contents) AND count($contents) )
		{
			foreach( $contents as $file )
			{
				//-----------------------------
			 	// Is this a valid file type?
			 	//-----------------------------
			 	
			 	$extension		= explode( ".", $file['filename'] );
			 	$file['ext']	= strtolower( array_pop( $extension ) );
	
			 	if( !$this->_isValidType( $file['filename'] ) )
			 	{
				 	continue;
			 	}
	
			 	//-----------------------------
			 	// Folders inside zip?
			 	//-----------------------------
			 	
			 	$folder				= explode( "/", $file['filename'] );
			 	$file['filename']	= array_pop( $folder );

			 	$i = $this->DB->buildAndFetch( array( 'select' => 'record_file_id, record_size', 'from' => 'bitracker_files_records', 'where' => "record_realname='" . IPSText::parseCleanValue( $file['filename'] ) . "'" ) );

			 	if( $i['record_file_id'] )
			 	{
				 	$txt				= ( $i['record_size'] == $file['size'] ) ? $this->lang->words['b_yes'] : $this->lang->words['b_maybe'];
				 	$file['exists']		= "<img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' /> <a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$i['record_file_id']}' title='{$txt}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$txt}' /></a>";
				 	$file['is_checked']	= 0;
				 	$checkAll			= false;
			 	}
			 	else
			 	{
				 	$file['exists']		= "<img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='' />";
				 	$file['is_checked']	= 1;
				}
				
				$rows .= $this->html->zipFileRow( $file );
			}
		}

		$this->registry->output->html .= $this->html->zipFileListing( $rows, $checkAll );
	}
	
	/**
	 * Zip file import selected files
	 *
	 * @return	@e void
	 */
	protected function zipIndexAdd()
	{
		/* Check for erors */
		if( !$this->request['cat'] )
		{
		 	$this->registry->output->showError( $this->lang->words['b_nocat'], 11820 );
		}
		
		if( !$this->request['mem_name'] )
		{
			$this->registry->output->showError( $this->lang->words['b_nomember'], 11821 );
		}
		
		/* Init vars */
		$this->request['num']		= intval($this->request['num'] > 0 ? intval($this->request['num']) : 10 );
		$this->request['st']		= intval($this->request['st'] > 0 ? intval($this->request['st']) : 0 );
		$this->request['remove']	= 0;
		$this->request['cat']		= intval($this->request['cat']);
		
		$dir		= $this->settings['upload_dir'] . '/';
		$files_dir	= $dir . 'temp/';
		
		if( !$this->request['st'] )
		{
			$extract = array();
			
			foreach( $_POST as $k => $v )
			{
				if( preg_match( "/^extract_(\d+?)$/", $k, $matches ) )
				{
					$extract[] = $v;
				}
			}
			
			if( !count($extract) )
			{
			 	$this->registry->output->showError( $this->lang->words['b_nofiles'], 11822 );
			}
			
			$zip = $this->request['zip'];
			
			if ( file_exists( $files_dir ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
				$fileManagement	= new $classToLoad();
				$fileManagement->removeDirectory( $files_dir );
			}
			
			@mkdir( $files_dir );
			@chmod( $files_dir, IPS_FOLDER_PERMISSION );
			
			//-----------------------------
			// Get the zip library
			//-----------------------------
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/pclzip.lib.php', 'PclZip', 'bitracker' );
			$zip			= new $classToLoad( $dir . $zip );
			
			foreach( $extract as $idx )
			{
			 	$zip->extractByIndex( $idx, PCLZIP_OPT_PATH, $files_dir );
			}
		}
		
		//-----------------------------
		// Take a look in the directory
		//-----------------------------
		
		$zipfiles	= $this->_grabFilesFromDirectory( $files_dir );
		$files		= array();
		
		if( is_array($zipfiles) AND count($zipfiles) )
		{
			foreach( $zipfiles as $id => $name )
			{
				if( !in_array( str_replace( $files_dir, '', $name ), $files ) )
				{
					$files[] = $name;
				}
			}
		}
		
		$mem		= array();
		$category	= $this->registry->getClass('categories')->cat_lookup[ $this->request['cat'] ];
		
	 	if( !$category['coptions']['opt_disfiles'] )
	 	{
		 	$this->registry->output->showError( $this->lang->words['b_anothercat'], 11823 );
	 	}

		if ( !count( $mem ) AND $this->request['mem_name'] )
		{
			$mem = $this->DB->buildAndFetch( array( 'select'	=> 'member_id, members_display_name',
													'from'	=> 'members',
													'where'	=> "members_l_display_name='" . strtolower($this->request['mem_name']) . "'" 
											) 		);

			if( !$mem['member_id'] )
			{
				$this->registry->output->showError( $this->lang->words['b_nomember'], 11824 );
			}
		}
		
		//-----------------------------
		// Start importing
		//-----------------------------

		$processed = $this->_processFiles( $category, $mem, $files );
		
		if( $processed == 0 )
		{
			// All done
			
			$this->registry->getClass('categories')->rebuildFileinfo( $category['cid'] );
			$this->registry->getClass('categories')->rebuildCatCache();
			$this->registry->getClass('categories')->rebuildStatsCache();
			
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFileManagement.php', 'classFileManagement' );
			$fileManagement	= new $classToLoad();
			$fileManagement->removeDirectory( $files_dir );
		
			$this->registry->output->global_message		= $this->request['st'] . $this->lang->words['b_filesgood'];
			$this->registry->output->persistent_message = true;
		
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=bulkZip' );
		}
		else
		{
			$this->request[ 'st'] =  $this->request['st'] + $processed ;
			
			$this->registry->output->redirect( "{$this->settings['base_url']}{$this->form_code}&amp;do=bulkZip&amp;op=zipIndexAdd&amp;cat={$this->request['cat']}&amp;mem_name={$this->request['mem_name']}&amp;num={$this->request['num']}&amp;st={$this->request['st']}", 
												'<b>' . sprintf( $this->lang->words['b_uptofiles'], $this->request['st'] ) . '</b>', 2, false, true );
		}
	}
	
	/**
	 * Bulk directory import form
	 *
	 * @return	@e void
	 */
	protected function bulkAddForm()
	{
		$this->request['lookin'] = str_replace( "&#46;", ".", $this->request['lookin'] );
		$this->request['lookin'] = str_replace( "&#092;", "/", $this->request['lookin'] );
		$this->request['lookin'] = str_replace( "&#036;", "$", $this->request['lookin'] );
		$this->request['lookin'] = $this->request['lookin'] ? $this->request['lookin'] : DOC_IPS_ROOT_PATH;

		if( $this->request['lookin'] == "../" )
		{
			$up_a_dir = "../../";
		}
		else if( $this->request['lookin'] == '../../' )
		{
			$up_a_dir = $this->request['lookin'];
		}
		else
		{
			if( substr( $this->request['lookin'], -1, 1 ) == '/' )
			{
				$this->request['lookin'] =  substr( $this->request['lookin'], 0, -1  );
			}
			
			$so_far_dirs = explode( '/', $this->request['lookin'] );
			
			array_pop($so_far_dirs);
			
			$up_a_dir = implode( '/', $so_far_dirs ) . '/';
		}
		
		if( substr( $up_a_dir, -2, 2 ) == '//' )
		{
			$up_a_dir = substr( $up_a_dir, 0, -1 );
		}

		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 AND $up_a_dir == '/' )
		{
			$up_a_dir	= $this->request['lookin'];
		}

		$rows	= '';
		
		if( is_dir( $this->request['lookin'] ) )
		{
			try
			{
				foreach( new DirectoryIterator( $this->request['lookin'] ) as $directory )
				{
					if( $directory->isDir() AND !$directory->isDot() )
					{
						$data		= array(
											'count'			=> 0,
											'size'			=> 0,
											'importable'	=> $directory->isWritable()
											);
						
						foreach( new DirectoryIterator( $directory->getPathname() ) as $file )
						{
							if( $file->isFile() AND $this->_isValidType( $file->getFilename() ) )
							{
								$data['count']++;
								
								$data['size'] += $file->getSize();
								
								if( !$file->isWritable() )
								{
									$data['importable'] = 0;
								}
							}
						}
            	
						$rows .= $this->html->bulkImportRow( $directory, $data );
					}
				}
			} catch ( Exception $e ) {}
		}
		
		$this->registry->output->html .= $this->html->bulkImportWrapper( $rows, $up_a_dir );
	}
	
	/**
	 * View importable files in a directory
	 *
	 * @return	@e void
	 */
	protected function bulkViewFiles()
	{
		$this->request['viewdir'] = str_replace( "&#092;", "/", $this->request['viewdir'] );
		$this->request['viewdir'] = str_replace( "&#036;", "$", $this->request['viewdir'] );
		
		$dir	= str_replace( "&#46;", ".", $this->request['viewdir'] );
		$rows	= '';

		$files = array();

		if( is_dir( $dir ) )
		{
			try
			{
				foreach( new DirectoryIterator( $dir ) as $file )
				{
					if( $file->isFile() AND $this->_isValidType( $file->getFilename() ) )
					{
						$extension	= explode( ".", $file->getFilename() );
						$image		= $this->caches['bit_mimetypes'][ strtolower( array_pop( $extension ) ) ]['mime_img'];
						
						$rows		.= $this->html->bulkImportViewRow( $file, $image );
					}
				}
			} catch ( Exception $e ) {}
		}
		
		$this->registry->output->html .= $this->html->bulkImportViewWrapper( $rows );
	}	

	/**
	 * Form to complete bulk import
	 *
	 * @return	@e void
	 */
	protected function bulkViewDir()
	{
		$this->request['directory'] = str_replace( "&#46;", ".", $this->request['directory'] );

		$this->registry->output->html .= $this->html->dirFileListing();
	}
	
	/**
	 * Directory bulk import processing
	 *
	 * @return	@e void
	 */
	protected function doBulkAdd()
	{
		//-----------------------------
		// Let's check the input first
		//-----------------------------
		
		if( !$this->request['cat'] )
		{
		 	$this->registry->output->showError( $this->lang->words['b_nocat'], 11825 );
		}

		$this->request['dir']	= str_replace( '&#092;', '/', $this->request['dir'] );

		if( ! $this->request['dir'] )
		{
		 	$this->registry->output->showError( $this->lang->words['b_nodir'], 11826 /* No dur dur! */ );
		}

		$this->request['dir'] = str_replace( '&#46;', '.', $this->request['dir'] );
		$this->request['dir'] = str_replace( '&#092;', '\\', $this->request['dir'] );

		if( ! is_dir( $this->request['dir'] ) )
		{
		 	$this->registry->output->showError( $this->lang->words['b_dir404'], 11827 );
		}

		if( ! is_writable( $this->request['dir'] ) )
		{
		 	$this->registry->output->showError( $this->lang->words['b_dir0777'], 11828 );
		}
		
		$this->request['num']		= intval( $this->request['num'] > 0 ? intval($this->request['num']) : 5 );
		$this->request['st']		= intval( $this->request['st'] > 0 ? intval($this->request['st']) : 0 );
		$this->request['remove']	= intval( $this->request['remove'] > 0 ? 1 : 0 );

		$files = array();
		
		try
		{
			foreach( new DirectoryIterator( $this->request['dir'] ) as $file )
			{
				if( $file->isFile() AND $this->_isValidType( $file->getFilename() ) )
				{
					$files[] = $file->getPathname();
				}
			}
		} catch ( Exception $e ) {}
		
		//-----------------------------
		// Get the category information
		//-----------------------------
		
		$mem		= array();

		$this->request['cat'] = intval($this->request['cat']);
		
	 	$category = $this->registry->getClass('categories')->cat_lookup[ $this->request['cat'] ];
	 	
	 	if( !$category['coptions']['opt_disfiles'] )
	 	{
		 	$this->registry->output->showError( $this->lang->words['b_anothercat'], 11829 );
	 	}

		if ( !count( $mem ) AND $this->request['mem_name'] )
		{
			$mem = $this->DB->buildAndFetch( array( 'select'	=> 'member_id, members_display_name',
													'from'		=> 'members',
													'where'		=> "members_l_display_name='" . strtolower($this->request['mem_name']) . "'" 
													) 		);

			if( !$mem['member_id'] )
			{
				$this->registry->output->showError( $this->lang->words['b_nomember'], 11830 );
			}
		}
		
		$processed = $this->_processFiles( $category, $mem, $files );
		
		if( $processed == 0 )
		{
			// All done
			
			$this->registry->getClass('categories')->rebuildFileinfo( $category['cid'] );
			$this->registry->getClass('categories')->rebuildCatCache();
			$this->registry->getClass('categories')->rebuildStatsCache();
		
			$this->registry->output->global_message		= $this->request['st'] . $this->lang->words['b_totalfiles'];
			$this->registry->output->persistent_message = true;
		
			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&amp;do=bulkDir' );
		}
		else
		{
			$this->request[ 'st'] =  $this->request['st'] + $processed ;
			
			$this->registry->output->redirect( "{$this->settings['base_url']}{$this->form_code}&amp;do=bulkDir&amp;op=doBulkAdd&amp;dir={$this->request['dir']}&amp;cat={$this->request['cat']}&amp;mem_name={$this->request['mem_name']}&amp;num={$this->request['num']}&amp;st={$this->request['st']}&amp;remove={$this->request['remove']}", 
												'<b>' . sprintf( $this->lang->words['b_uptofiles'], $this->request['st'] ) . '</b>', 2, false, true );
		}
	}
	
	/**
	 * Process a file to be imported
	 *
	 * @param	array		$category		Category information
	 * @param	array		$mem			Member information
	 * @param	array		$files			Array of files to import
	 * @return	@e integer	Number of files successfully imported
	 */
	protected function _processFiles( $category, $mem, $files )
	{
		$this->registry->class_localization->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );
		
		//-----------------------------
		// Start importing
		//-----------------------------
		
		$trans 		= array();
		$processed 	= 0;
		$i 			= 0;

		//-----------------------------
		// FTP Storage?
		//-----------------------------
		
		if( $this->settings['bit_filestorage'] == 'ftp' )
		{
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
				}
				catch( Exception $e )
				{
					$this->registry->output->showError( $this->lang->words['b_ftpnoconnect'], 11831 );
				}
			}
			else
			{
				$this->registry->output->showError( $this->lang->words['b_ftpallinfo'], 11833 );
			}
		}
		
		if( $category['coptions']['opt_topice'] == 1 )
		{
			IPSText::getTextClass('bbcode')->parse_bbcode	= 1;
			IPSText::getTextClass('bbcode')->parse_smilies	= 1;			
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/topics.php', 'topicsLibrary', 'bitracker' );
			$lib_topics		= new $classToLoad( $this->registry );
		}	
		
		if( is_array( $files ) )
		{
		 	foreach( $files as $file )
		 	{
			 	$i++;
			 	
			 	if( $this->request['remove'] )
			 	{
				 	if( $i > $this->request['num'] )
				 	{
					 	break;
				 	}
			 	}
			 	else
			 	{
				 	if( $i <= $this->request['st'] )
				 	{
					 	continue;
				 	}
				 	
				 	if( $i > ($this->request['num'] + $this->request['st']) )
				 	{
					 	break;
				 	}
			 	}
			 	
			 	$processed++;
			 	
			 	$file = str_replace( '\\', '/', $file );
			 	
			 	if( strpos( $file, '/' ) !== false )
			 	{
			 		$filename	= explode( '/', $file );
			 		$filename	= array_pop( $filename );
			 	}

			 	$extension	= explode( ".", $file );
			 	$filetype	= strtolower( array_pop( $extension ) );
			 	
			 	$_filesize	= @filesize( $file );
			 	$_postKey	= md5( uniqid( microtime(), true ) );
			 	
			 	$tempFile	= array( 	
			 						'file_name'				=> IPSText::parseCleanValue( $filename ),
			 						'file_cat'				=> $category['cid'],
			 						'file_open'				=> 1,
			 						'file_submitted'		=> time(),
			 						'file_updated'			=> time(),
									'file_size'				=> $_filesize,
									'file_desc'				=> $this->lang->words['imported_desc'],
									'file_submitter'		=> $mem['member_id'],
									'file_ipaddress'		=> $this->member->ip_address,
									'file_new'				=> 0,
									'file_post_key'			=> $_postKey,
									'file_name_furl'		=> IPSText::makeSeoTitle( IPSText::parseCleanValue( $filename ) ),
									);

				$tempStore	= array(
									'record_post_key'		=> $_postKey,
									'record_file_id'		=> 0,
									'record_type'			=> 'upload',
									'record_location'		=> $filename,
									'record_db_id'			=> 0,
									'record_thumb'			=> '',
									'record_storagetype'	=> $this->settings['bit_filestorage'],
									'record_realname'		=> IPSText::parseCleanValue( $filename ),
									'record_link_type'		=> '',
									'record_mime'			=> $this->caches['bit_mimetypes'][ $filetype ]['mime_id'],
									'record_size'			=> $_filesize,
									'record_backup'			=> 0,
									);
								
				if( $tempFile['file_submitted'] <= $this->lasttime AND $this->lasttime > 0 )
				{
					$tempFile['file_submitted'] = $this->lasttime + 1;
					$tempFile['file_updated']   = $this->lasttime + 1;
				}
				
				$this->lasttime = $tempFile['file_submitted'];

				switch( $this->settings['bit_filestorage'] )
				{
					case 'disk':
						$additional_path				= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) );
						
						$tempStore['record_location']	= $additional_path . $tempStore['record_location'];
						
						if( $this->request['remove'] )
						{
							if( !rename( $file, str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/" . $tempStore['record_location'] ) )
							{
								$this->registry->output->showError( $this->lang->words['b_nomove'], 11834 );
							}
						}
						else
						{
							if( !copy( $file, str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/" . $tempStore['record_location'] ) )
							{
								$this->registry->output->showError( $this->lang->words['b_nomove'], 11835 );
							}
						}
							
						@chmod( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/" . $tempStore['record_location'], IPS_FILE_PERMISSION );
					break;
									
					case 'ftp':
						try
						{
							$_ftpClass->chdir( $this->settings['bit_remotefilepath'] );
							$_ftpClass->upload( $file, $tempStore['record_location'] );
							
							if( $this->request['remove'] )
							{
								@unlink( $file );
							}
						}
						catch( Exception $e )
						{
							continue;
						}
					break;
						
					case 'db':
						// Get file data first
						$filedata = base64_encode( file_get_contents( $file ) );

						if( $this->request['remove'] )
						{
							@unlink( $file );
						}
					break;
				}				

				$this->DB->insert( 'bitracker_files', $tempFile );
				
				$tempFile['file_id']			= $this->DB->getInsertId();
				$tempStore['record_file_id']	= $tempFile['file_id'];

				if( $category['ccfields'] )
				{
					$this->DB->insert( 'bitracker_ccontent', array( 'file_id' => $tempFile['file_id'] ) );
				}

				if( $this->settings['bit_filestorage'] == 'db' )
				{
					$this->DB->insert( "bitracker_filestorage", array( 	'storage_id'	=> $tempFile['file_id'],
																		'storage_file'	=> $filedata 
											)							);

					$tempStore['record_db_id']	= $this->DB->getInsertId();
				}
				
				$this->DB->insert( "bitracker_files_records", $tempStore );
				
				$tempFile['file_submitter_name'] = $mem['members_display_name'];

				if( $category['coptions']['opt_topice'] == 1 )
				{
					$lib_topics->sortTopic( $tempFile, $category, 'new', 1 );
				}
				
				usleep( 10 );
		 	}
		}
		
		if( $this->settings['bit_filestorage'] == 'ftp' )
		{
			unset($_ftpClass);
		}
		
		return $processed;
	}
	
	/**
	 * Check if the file is allowed in IP.bitracker
	 *
	 * @param	string		$filename		Filename
	 * @return	@e boolean	File is allowed
	 */
	protected function _isValidType( $filename )
	{
		if( !count($this->valid_types) )
		{
			if( count( $this->cache->getCache('bit_mimetypes') ) )
			{
				foreach( $this->cache->getCache('bit_mimetypes') as $k => $v )
				{
					$this->valid_types[] = $v['mime_extension'];
				}
			}
		}

		$exploded	= explode( ".", $filename );
		$type		= strtolower( array_pop( $exploded ) );

		return in_array( $type, $this->valid_types );
	}
	
	/**
	 * Recursively pull files from a directory
	 *
	 * @param	string		$files_dir		Directory
	 * @return	@e array	Files in the directory we can import
	 */
	protected function _grabFilesFromDirectory( $files_dir )
	{
		$files		= array();
		$files_dir	= rtrim( $files_dir, '/' );
		
		if( !is_dir($files_dir) )
		{
			return array();
		}
		
		try
		{
			foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $files_dir ) ) as $file )
			{
				if( $file->isFile() AND $this->_isValidType( $file->getFilename() ) )
				{
					$files[] = $file->getPathname();
				}
			}
		} catch ( Exception $e ) {}

		return $files;
	}
}