<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Upload handler for swfupload + iframe attach
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

class public_bitracker_post_files extends ipsCommand
{
	/**
	 * AJAX object reference
	 *
	 * @var		object
	 */
	protected $ajax;
	
	/**
	 * Error key
	 *
	 * @var		string
	 */
	protected $error		= '';
	
	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Store AJAX class reference
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$this->ajax		= new $classToLoad();
		
		//-----------------------------------------
		// What now?
		//-----------------------------------------

		switch( $this->request['do'] )
		{	
			case 'flash':
				$this->ajax->returnHtml( $this->getJson() );
			break;
			
			case 'swfUpload':
				$this->uploadFlashFile();
			break;
			
			case 'remove':
				$this->removeFile();
			break;

			case 'iframe':
				$this->showIframeFiles();
			break;
			
			case 'iframeUpload':
			default:
				$this->uploadIframeFile();
			break;
		}
    }
    
	/**
	 * Remove an uploaded file
	 *
	 * @return	@e void
	 */
	public function removeFile()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$record_id = intval( $this->request['record_id'] );

		//-----------------------------------------
		// Remove the file..
		// If no file id or if this is not disk storage, delete file and record.
		// If file id, delete record only, and let
		//	save process handle file.
		//-----------------------------------------
		
		$record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_temp_records', 'where' => 'record_id=' . $record_id ) );
		
		if( $record['record_id'] )
		{
			if( $record['record_file_id'] AND $this->settings['bit_filestorage'] == 'disk' )
			{
				$this->DB->delete( 'bitracker_temp_records', 'record_id=' . $record_id );

			}
			else
			{
				if( $record['record_type'] == 'files' )
				{
					@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $record['record_location'] );
				}
                elseif( $record['record_type'] == 'nfo' ){
                    @unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . '/' . $record['record_location'] );
                }
				else
				{
					@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $record['record_location'] );
				}
				
				$this->DB->delete( 'bitracker_temp_records', 'record_id=' . $record_id );
			}
			
			$this->ajax->returnHtml( $this->getJson( 'attach_removed', 0 ) );
		}
		else
		{
			$this->ajax->returnHtml( $this->getJson( 'remove_failed', 1 ) );
		}
	}
	
	/**
	 * Perform the actual upload
	 *
	 * @return	@e void
	 */
	public function uploadFlashFile()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$post_key			= trim( IPSText::alphanumericalClean( $this->request['post_key'] ) );
		$this->error		= '';
		$insert_id			= 0;

		//-----------------------------------------
		// Upload the file
		//-----------------------------------------

		$insert_id	= $this->_doUpload( $post_key );

		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		if( $this->error )
		{
			$this->ajax->returnHtml( $this->getJson( $this->error, 1, $insert_id ) );
		}
		else
		{
			$this->ajax->returnHtml( $this->getJson( 'upload_ok', 0, $insert_id ) );
		}
	}
	
	/**
	 * Get the JSON to return to JS
	 *
	 * @param	string		$msg
	 * @param	bool		$is_error
	 * @param	integer		$insert_id
	 * @return	@e void
	 */
	public function getJson( $msg="ready", $is_error=0, $insert_id=0 )
	{
		//-----------------------------------------
		// Create JSON array
		//-----------------------------------------
		
		$JSON				= array();
		$JSON['msg']		= $msg;
		$JSON['is_error']	= $is_error;
		$JSON['post_key']	= $post_key		= trim( IPSText::alphanumericalClean( $this->request['post_key'] ) );

		$is_reset = 0;
		
		//-----------------------------------------
		// Set upload domain
		//-----------------------------------------
		
		if( $this->settings['upload_domain'] )
		{
			$is_reset	= 1;
			$original	= $this->settings['base_url'];
			
			if( $this->member->session_type == 'cookie' )
			{
				$this->settings['base_url'] = $this->settings['upload_domain'] . '/index.php?';
			}
			else
			{
				$this->settings['base_url'] = $this->settings['upload_domain'] . '/index.php?s=' . $this->member->session_id . '&amp;';
			}
		}

		//-----------------------------------------
		// Pass insert id if we have one
		//-----------------------------------------
		
		if( $insert_id )
		{
			$JSON['insert_id'] = $insert_id;
		}

		//-----------------------------------------
		// Extra data from upload
		//-----------------------------------------
		
		foreach( $this->request as $k => $v )
		{
			if( preg_match( "#^--ff--#", $k ) )
			{
				$JSON['extra_upload_form_url']	.= '&amp;' . str_replace( '--ff--', '', $k ) . '=' . $v;
				$JSON['extra_upload_form_url']	.= '&amp;' . $k . '=' . $v;
			}
		}

		//-----------------------------------------
		// Query to get the items to show
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select'	=> 'r.*',
								'from'		=> array( 'bitracker_temp_records' => 'r' ),
								'where'		=> "r.record_post_key='{$post_key}' AND r.record_type='{$this->request['type']}'",
								'add_join'	=> array( array(
															'select' => 't.*',
															'from'   => array( 'bitracker_mime' => 't' ),
															'where'  => 't.mime_id=r.record_mime',
															'type'   => 'left' 
													) 	)
											
								)	);
									
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			if( ( $insert_id && $row['record_id'] == $insert_id ) || $this->request['fetch_all'] )
			{
				$_dims	= array( 'width' => 16, 'height' => 16 );
				
				if( $row['record_type'] == 'ss' )
				{
					$_dims	= @getimagesize( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/" . $row['record_location'] );
				}
				
				$JSON['current_items'][ $row['record_id'] ] = array(	$row['record_id']  ,
											 	 						str_replace( array( '[', ']' ), '', $row['record_realname'] ),
																		$row['record_size'],
																		1,
																		$this->settings['base_url'] . 'app=bitracker&module=display&section=screenshot&temp=' . $row['record_id'],
																		$_dims['width'],
																		$_dims['height'],
																	 	$this->settings['public_dir'] . 'style_extra/' . $row['mime_img'],
																	 	$row['record_default'],
																	);
			}
		}

		//-----------------------------------------
		// Charsets >.<
		//-----------------------------------------
		
		array_walk_recursive( $JSON, array( 'IPSText', 'arrayWalkCallbackConvert' ) ); 
		$result	= json_encode( $JSON ); 
		$result	= IPSText::convertCharsets( $result, "UTF-8", IPS_DOC_CHAR_SET );

		//-----------------------------------------
		// Return the JSON object
		//-----------------------------------------
		
		return $result;
	}
	
	/**
	 * Show the iframe'd files
	 *
	 * @param	string	$msg
	 * @param	bool	$is_error
	 * @param	integer	$insert_id
	 * @return	@e void
	 */
	public function showIframeFiles( $msg="ready", $is_error=0, $insert_id=0 )
	{
		//-----------------------------------------
		// Get the JSON
		//-----------------------------------------
		
		$JSON = $this->getJson( $msg, $is_error, $insert_id );

		$this->ajax->returnHtml( $this->registry->output->getTemplate('bitracker_submit')->filesIframe( $JSON, $this->request['type'] ) );
	}
	
	/**
	 * Perform the actual upload from an iframe
	 *
	 * @return	@e void
	 */
	public function uploadIframeFile()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$post_key			= trim( IPSText::alphanumericalClean( $this->request['post_key'] ) );
		$this->error		= '';
		$insert_id			= 0;

		//-----------------------------------------
		// Process upload
		//-----------------------------------------
		
		$insert_id	= $this->_doUpload( $post_key );

		//-----------------------------------------
		// Output result
		//-----------------------------------------
		
		if( $this->error )
		{
			$JSON = $this->getJson( $this->error, 1, $insert_id );
		}
		else
		{
			$JSON = $this->getJson( 'upload_ok', 0, $insert_id );
		}

		$this->ajax->returnHtml( $this->registry->output->getTemplate('bitracker_submit')->filesIframe( $JSON, $this->request['type'] ) );
	}
	
	/**
	 * Actually peform the upload
	 *
	 * @param	string		Post key
	 * @return	int			Insert id
	 */
	protected function _doUpload( $post_key )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$category	= $this->registry->getClass('categories')->cat_lookup[ intval($this->request['category']) ];
		$types		= $this->registry->getClass('bitFunctions')->getAllowedTypes( $category );
		$mimecache	= $this->cache->getCache('bit_mimetypes');
		
		//-----------------------------------------
		// Basic checks
		//-----------------------------------------
		
		if( !$category['cid'] )
		{
			$this->error	= 'invalid_mime_type';
		}
		
		//-----------------------------------------
		// Load the upload library
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classUpload.php', 'classUpload' );
		$_upload		= new $classToLoad();

		$_upload->out_file_name			= md5( uniqid( microtime(), true ) ) . '-' . str_replace( array( " ", "\n", "\r", "\t" ), '_', $this->registry->getClass('bitFunctions')->getFileName( preg_replace( "/[^\w\.]/", '-', IPSText::convertAccents( $_FILES['FILE_UPLOAD']['name'] ) ) ) );
		$_upload->upload_form_field		= 'FILE_UPLOAD';

		//-----------------------------------------
		// Process upload
		//-----------------------------------------
		
		if( $this->request['type'] == 'files' )
		{
			$path_additional				= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) );
			
			$_upload->make_script_safe		= 1;
			$_upload->force_data_ext		= 'ipb';
			$_upload->max_file_size			= ( $category['coptions']['opt_maxfile'] ? $category['coptions']['opt_maxfile'] : $this->settings['bit_default_maxsize'] ) * 1024;
			$_upload->allowed_file_ext		= $types['files'];
			$_upload->out_file_dir			= str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . ( $path_additional ? '/' . $path_additional : '' );
		}
        elseif( $this->request['type'] == 'nfo' ){

			$path_additional				= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) );

			$_upload->max_file_size			= ( $category['coptions']['opt_maxnfo'] ? $category['coptions']['opt_maxnfo'] : $this->settings['bit_default_maxsize'] ) * 1024;
			$_upload->allowed_file_ext		= $types['nfo'];
			$_upload->out_file_dir			= str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . ( $path_additional ? '/' . $path_additional : '' );
        }
		else
		{
			$path_additional				= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) );
			
			$_upload->max_file_size			= ( $category['coptions']['opt_maxss'] ? $category['coptions']['opt_maxss'] : $this->settings['bit_default_maxsize'] ) * 1024;
			$_upload->allowed_file_ext		= $types['ss'];
			$_upload->img_ext				= $types['ss'];
			$_upload->out_file_dir			= str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . ( $path_additional ? '/' . $path_additional : '' );
		}
		
		//-----------------------------------------
		// Upload
		//-----------------------------------------
		
		$_upload->process();
		
		if ( $_upload->error_no )
		{
			switch( $_upload->error_no )
			{
				case 1:
					$this->error = 'upload_no_file';
					return 0;
				break;
				case 2:
					$this->error = 'invalid_mime_type';
					return 0;
				break;
				case 3:
					$this->error = 'upload_too_big';
					return 0;
				break;
				case 4:
					$this->error = 'upload_failed';
					return 0;
				break;
				case 5:
					$this->error = 'upload_failed';
					return 0;
				break;
			}
		}

		//-----------------------------------------
		// Insert the details and return id
		//-----------------------------------------

		$insert	= array(
						'record_post_key'	=> $post_key,
						'record_type'		=> $this->request['type'],
						'record_location'	=> $path_additional . IPSText::convertAccents( $_upload->parsed_file_name ),
						'record_realname'	=> IPSText::stripslashes( $_upload->original_file_name ),
						'record_mime'		=> $mimecache[ $_upload->real_file_extension ]['mime_id'],
						'record_size'		=> @filesize( $_upload->saved_upload_name ) ? @filesize( $_upload->saved_upload_name ) : $_FILES['file']['size'],
						'record_added'		=> time(),
						);

		$this->DB->insert( 'bitracker_temp_records', $insert );

		return $this->DB->getInsertId();
	}
}