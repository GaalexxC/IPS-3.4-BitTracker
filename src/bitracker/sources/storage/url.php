<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit url file storage handling
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

class urlStorageEngine extends storageEngine implements interface_storage
{
	/**
	 * Blacklisted domains
	 * 
	 * @var	array
	 */
 	protected $blacklisted	= array();

	/**
	 * Constructor
	 *
	 * @param	object		Registry object
	 * @param	array 		Category information
	 * @param	string		Type of engine
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $category=array(), $type='file' )
	{
		parent::__construct( $registry, $category, $type );
		
		if( $this->settings['bit_link_blacklist'] )
		{
			$this->blacklisted	= explode( "\n", str_replace( "\r", '', trim( $this->settings['bit_link_blacklist'] ) ) );
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
		// Nothing to "remove"
		//-----------------------------------------
		
		return true;
	}

	/**
	 * Stores the uploaded files
	 *
	 * @access	public
	 * @param	array 		File information
	 * @return	array		File details for final storage
	 */	
	public function store( $data=array() )
	{
		if( !$this->registry->getClass('bitFunctions')->canSubmitLinks() )
		{
			return 1;
		}

		//-----------------------------------------
		// What kind of url...
		//-----------------------------------------
		
		switch( $data['type'] )
		{
			case 'file':
				if( $data['url'] AND IPSText::xssCheckUrl( $data['url'] ) )
				{
					$url_pieces	= explode( "/", trim($data['url']) );
					$file_data	= array_pop( $url_pieces );
					$extension	= IPSText::getFileExtension( $file_data );

					if( !$this->settings['bit_ignore_mime_link'] )
					{
						if( !$extension )
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
					}
					
					/* Check if it is blacklisted */
					if( count($this->blacklisted) )
					{
						foreach( $this->blacklisted as $_url )
						{
							if( !trim($_url) )
							{
								continue;
							}
	
							$_url = preg_quote( $_url, '/' );
							$_url = str_replace( '\*', "(.*?)", $_url );

							if( preg_match( '/' . $_url . '/i', $data['url'] ) )
							{
								return 9;
							}
						}
					}
					
					//-----------------------------------------
					// Set the new details
					//-----------------------------------------

					$this->details[]	= array(
												'record_post_key'		=> $data['post_key'],
												'record_file_id'		=> 0,
												'record_type'			=> 'link',
												'record_location'		=> trim($data['url']),
												'record_db_id'			=> 0,
												'record_thumb'			=> '',
												'record_storagetype'	=> 'disk',
												'record_realname'		=> $file_data,
												'record_link_type'		=> $data['link_type'],
												'record_mime'			=> intval($this->mimecache[ $extension ]['mime_id']),
												'record_size'			=> $this->registry->getClass('bitFunctions')->obtainRemoteFileSize( trim($data['url']) ),
												'record_backup'			=> 0,
												);

					return 0;
				}
				else
				{
					return 1;
				}
			break;
			
			case 'screenshot':
				if( $data['url'] AND IPSText::xssCheckUrl( $data['url'] ) )
				{
					if( !$this->settings['bit_ignore_mime_link'] )
					{
						$url_pieces	= explode( "/", trim($data['url']) );
						$file_data	= array_pop( $url_pieces );
						$extension	= IPSText::getFileExtension( $file_data );
						
						if( !$extension )
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
					}
					
					//-----------------------------------------
					// Set the new details
					//-----------------------------------------

					$this->details[]	= array(
												'record_post_key'		=> $data['post_key'],
												'record_file_id'		=> 0,
												'record_type'			=> 'sslink',
												'record_location'		=> trim($data['url']),
												'record_db_id'			=> 0,
												'record_thumb'			=> '',
												'record_storagetype'	=> 'disk',
												'record_realname'		=> $file_data,
												'record_link_type'		=> '',
												'record_mime'			=> intval($this->mimecache[ $extension ]['mime_id']),
												'record_size'			=> $this->registry->getClass('bitFunctions')->obtainRemoteFileSize( trim($data['url']) ),
												'record_backup'			=> 0,
												'record_default'		=> ( $data['index'] == $this->primaryScreenshot ) ? 1 : 0,
												);

					return 0;
				}
				else
				{
					return 1;
				}
			break;
		}
	}
	
	/**
	 * Undo stored files
	 *
	 * @access	public
	 * @return	bool		Rollback complete
	 */	
	public function rollback()
	{
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