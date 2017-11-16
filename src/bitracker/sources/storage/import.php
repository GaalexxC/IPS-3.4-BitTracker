<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit import a local file
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

class importStorageEngine extends storageEngine implements interface_storage
{
	/**
	 * Stores the uploaded files
	 *
	 * @access	public
	 * @param	array 		File information
	 * @return	array		File details for final storage
	 */	
	public function store( $data=array() )
	{
		if( !$this->registry->getClass('bitFunctions')->canSubmitPaths() )
		{
			return 1;
		}

		//-----------------------------------------
		// Got a file?
		//-----------------------------------------
		
		if( $this->type == 'file' )
		{
			if( $this->request['file_path'] )
			{
				$this->request['file_path'] =  str_replace( "&#46;&#46;/"	, "../"	, $this->request['file_path']  );
				$this->request['file_path'] =  str_replace( "&#92;"			, "/"	, $this->request['file_path']  );
				$this->request['file_path'] =  str_replace( "\\"			, "/"	, $this->request['file_path']  );
	
				$FILE_NAME	= pathinfo( $this->request['file_path'], PATHINFO_BASENAME );
	
				//-----------------------------------------
				// Does the file exist
				//-----------------------------------------
				
				if( !is_file( $this->file_path . '/' . $this->request['file_path'] ) )
				{
					return 1;
				}
				
				//-----------------------------------------
				// Extension ok?
				//----------------------------------------- 
	
				$extension		= IPSText::getFileExtension( $this->request['file_path'] );
	
				if ( !$extension )
				{
					return 2;
				}
				else
				{
					if ( ! in_array( $extension, $this->types['files'] ) )
					{
						return 2;
					}
				}
				
				//-----------------------------------------
				// Set the new details
				//-----------------------------------------
	
				$_details				= array();
				$_details['realname']	= $FILE_NAME;
				$_details['mime']		= $this->mimecache[ $extension ]['mime_id'];
	
				//-----------------------------------------
				// Set filename and make file safe
				//----------------------------------------- 
	
				$FILE_NAME						= preg_replace( "/[^\w\.]/", "_", $FILE_NAME );
				$new_file_name					= md5( uniqid( microtime(), true ) ) . '-' . str_replace( array( " ", "\n", "\r", "\t" ), '_', $this->registry->getClass('bitFunctions')->getFileName($FILE_NAME) );
				$additional_path				= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( $this->file_path );
	
				if ( preg_match( "/\.(cgi|pl|js|asp|php|html|htm|jsp|jar)/i", $FILE_NAME ) )
				{
					$FILE_TYPE		= 'text/plain';
					$extension		= 'ipb';
				}
				
				$final_destination				= $this->file_path . '/' . $additional_path . $new_file_name . '.' . $extension;
	
				//-----------------------------------------
				// And move..
				//----------------------------------------- 
				
				if ( ! @rename( $this->file_path . '/' . $this->request['file_path'], $final_destination ) )
				{
					return 4;
				}
				else
				{
					@chmod( $final_destination, IPS_FILE_PERMISSION );
	
					if ( in_array( $extension, array( 'jpg', 'jpeg', 'png', 'gif' ) ) )
					{
						//-------------------------------------------------
						// Are we making sure its an image?
						//-------------------------------------------------
	
						$img_attributes	= @getimagesize( $final_destination );
						
						if ( ! is_array( $img_attributes ) or ! count( $img_attributes ) )
						{
							@unlink( $final_destination );
							return 5;
						}
						else if ( ! $img_attributes[2] )
						{
							@unlink( $final_destination );
							return 5;
						}
						else if ( $img_attributes[2] == 1 AND ( $extension == 'jpg' OR $extension == 'jpeg' ) )
						{
							// Potential XSS attack with a fake GIF header in a JPEG
							@unlink( $final_destination );
							return 5;
						}
					}
				}
				
				//-----------------------------------------
				// Set the new details
				//-----------------------------------------
	
				$this->details[]	= array(
											'record_post_key'		=> $data['post_key'],
											'record_file_id'		=> $data['file_id'],
											'record_type'			=> 'upload',
											'record_location'		=> $additional_path . $new_file_name . '.' . $extension,
											'record_db_id'			=> 0,
											'record_thumb'			=> '',
											'record_storagetype'	=> $this->settings['bit_filestorage'],
											'record_realname'		=> $_details['realname'],
											'record_link_type'		=> '',
											'record_mime'			=> $_details['mime'],
											'record_size'			=> filesize( $final_destination ),
											'record_backup'			=> 0,
											);
			}
			else
			{
				return 1;
			}
		}
		
		//-----------------------------------------
		// Got a screenshot?
		//-----------------------------------------
			
		else
		{	
			if( $this->request['file_sspath'] AND $this->category['coptions']['opt_allowss'] )
			{
				$this->request['file_sspath'] =  str_replace( "&#46;&#46;/"	, "../"	, $this->request['file_sspath']  );
				$this->request['file_sspath'] =  str_replace( "&#92;"		, "/"	, $this->request['file_sspath']  );
				$this->request['file_sspath'] =  str_replace( "\\"			, "/"	, $this->request['file_sspath']  );
	
				$FILE_NAME	= pathinfo( $this->request['file_sspath'], PATHINFO_BASENAME );
	
				//-----------------------------------------
				// Does the file exist
				//-----------------------------------------
				
				if( !is_file( $this->image_path . '/' . $this->request['file_sspath'] ) )
				{
					return 1;
				}
				
				//-----------------------------------------
				// Extension ok?
				//----------------------------------------- 
	
				$extension		= IPSText::getFileExtension( $this->request['file_sspath'] );
				
				if ( !$extension )
				{
					return 2;
				}
				else
				{
					if ( ! in_array( $extension, $this->types['ss'] ) )
					{
						return 2;
					}
				}
	
				//-----------------------------------------
				// Set the new details
				//-----------------------------------------
	
				$_details				= array();
				$_details['realname']	= $FILE_NAME;
				$_details['mime']		= $this->mimecache[ $extension ]['mime_id'];
	
				//-----------------------------------------
				// Set filename and make file safe
				//----------------------------------------- 
	
				$FILE_NAME						= preg_replace( "/[^\w\.]/", "_", $FILE_NAME );
				$new_file_name					= md5( uniqid( microtime(), true ) ) . '-' . str_replace( array( " ", "\n", "\r", "\t" ), '_', $this->registry->getClass('bitFunctions')->getFileName($FILE_NAME) );
				$additional_path				= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( $this->image_path );
	
				if ( preg_match( "/\.(cgi|pl|js|asp|php|html|htm|jsp|jar)/i", $FILE_NAME ) )
				{
					$FILE_TYPE		= 'text/plain';
					$extension		= 'ipb';
				}
				
				$final_destination				= $this->image_path . '/' . $additional_path . $new_file_name . '.' . $extension;
	
				//-----------------------------------------
				// And move..
				//----------------------------------------- 
				
				if ( ! @rename( $this->image_path . '/' . $this->request['file_sspath'], $final_destination ) )
				{
					return 4;
				}
				else
				{
					@chmod( $final_destination, IPS_FILE_PERMISSION );
	
					if ( in_array( $extension, array( 'jpg', 'jpeg', 'png', 'gif' ) ) )
					{
						//-------------------------------------------------
						// Are we making sure its an image?
						//-------------------------------------------------
	
						$img_attributes	= @getimagesize( $final_destination );
						
						if ( ! is_array( $img_attributes ) or ! count( $img_attributes ) )
						{
							@unlink( $final_destination );
							return 5;
						}
						else if ( ! $img_attributes[2] )
						{
							@unlink( $final_destination );
							return 5;
						}
						else if ( $img_attributes[2] == 1 AND ( $extension == 'jpg' OR $extension == 'jpeg' ) )
						{
							// Potential XSS attack with a fake GIF header in a JPEG
							@unlink( $final_destination );
							return 5;
						}
					}
				}
				
				//-----------------------------------------
				// Set the new details
				//-----------------------------------------
	
				$this->details[]	= array(
											'record_post_key'		=> $data['post_key'],
											'record_file_id'		=> $data['file_id'],
											'record_type'			=> 'ssupload',
											'record_location'		=> $additional_path . $new_file_name . '.' . $extension,
											'record_db_id'			=> 0,
											'record_thumb'			=> '',
											'record_storagetype'	=> $this->settings['bit_filestorage'],
											'record_realname'		=> $_details['realname'],
											'record_link_type'		=> '',
											'record_mime'			=> $_details['mime'],
											'record_size'			=> filesize( $final_destination ),
											'record_backup'			=> 0,
											);
	
			}
		}
	}
	
	/**
	 * Remove a file
	 *
	 * @access	public
	 * @param	array		Record data
	 * @return	boolean		File removed successfully
	 */	
	public function remove( $record )
	{
		//-----------------------------------------
		// This storage engine identifies as local,
		// so it shouldn't be called for remove()
		//-----------------------------------------
		
		return false;
	}
	
	/**
	 * Undo stored files
	 *
	 * @access	public
	 * @return	bool		Rollback complete
	 */	
	public function rollback()
	{
		if( count($this->details) )
		{
			foreach( $this->details as $_details )
			{
				if( $_details['record_type'] == 'ssupload' )
				{
					@rename( $this->image_path . '/' . $_details['record_location'], $this->image_path . '/' . $this->request['file_sspath'] );
				}
				else
				{
					@rename( $this->file_path . '/' . $_details['record_location'], $this->file_path . '/' . $this->request['file_path'] );
				}
				
				//@unlink( $_details['record_type'] == 'ssupload' ? $this->image_path . '/' . $_details['record_location'] : $this->file_path . '/' . $_details['record_location'] );
			}
		}
		
		unset($this->details);
	}
	
	/**
	 * Finalize the storage
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return	boolean
	 */	
	public function commit( $file_id=0 )
	{
		parent::commit( $file_id );
	}
}