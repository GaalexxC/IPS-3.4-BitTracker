<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit FTP file storage handling
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

class ftpStorageEngine extends storageEngine implements interface_storage
{
	/**
	 * FTP object
	 *
	 * @access	protected
	 * @var 	object
	 */
	protected $connection				= null;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @param	array 		Category information
	 * @param	string		Type of engine
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $category=array(), $type='file' )
	{
		parent::__construct( $registry, $category, $type );
		
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

				$this->connection		= new $classToLoad( $this->settings['bit_remoteurl'], $this->settings['bit_remoteuser'], $this->settings['bit_remotepass'], $this->settings['bit_remoteport'], '/', true, 999999 );
			}
			catch( Exception $e )
			{
				$this->registry->output->showError( 'addfile_ftp_error1', 10827, true );
			}
		}
		else
		{
			$this->registry->output->showError( 'addfile_ftp_error1', 10829 );
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
		$path	= $record['record_type'] == 'upload' ? $this->settings['bit_remotefilepath'] : $this->settings['bit_remotesspath'];

		try
		{
			$this->connection->chdir( $path );
			$this->connection->file( $record['record_location'] )->delete();
			
			if( $record['record_type'] == 'ssupload' )
			{
				$this->connection->file( 'thumb-' . $record['record_location'] )->delete();
			}
		}
		catch( Exception $e )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Stores the uploaded files
	 *
	 * @access	public
	 * @param	array 		File information
	 * @return	bool		Record stored ok
	 */	
	public function store( $data=array() )
	{
		//-----------------------------------------
		// Get all the temp records
		//-----------------------------------------
		
		$_where	= $this->type == 'file' ? 'files' : 'ss';
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_temp_records', 'where' => "record_type='{$_where}' AND record_post_key='{$data['post_key']}'" ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			//-----------------------------------------
			// Fix extension
			//-----------------------------------------
			
			$extension	= '';
			
			foreach( $this->mimecache as $ext => $data )
			{
				if( $data['mime_id'] == $r['record_mime'] )
				{
					$extension	= $ext;
				}
			}
			
			$newLocation	= preg_replace( "#^(.+?)\.ipb$#", "\\1.{$extension}", $r['record_location'] );
			
			//-----------------------------------------
			// Transfer
			//-----------------------------------------

			try
			{
				if( $r['record_type'] == 'ss' )
				{
					$this->connection->chdir( $this->settings['bit_remotesspath'] );
					$this->connection->upload( $this->image_path . "/" . $r['record_location'], $newLocation );
				}
				else
				{
					$this->connection->chdir( $this->settings['bit_remotefilepath'] );
					$this->connection->upload( $this->file_path . "/" . $r['record_location'], $newLocation );
				}
			}
			catch( Exception $e )
			{
				return false;
			}

			//-----------------------------------------
			// Set the new details
			//-----------------------------------------
	
			$this->details[]	= array(
										'record_post_key'		=> $r['record_post_key'],
										'record_file_id'		=> $data['file_id'],
										'record_type'			=> $r['record_type'] == 'ss' ? 'ssupload' : 'upload',
										'record_location'		=> $newLocation,
										'record_db_id'			=> 0,
										'record_thumb'			=> '',
										'record_storagetype'	=> $this->settings['bit_filestorage'],
										'record_realname'		=> $r['record_realname'],
										'record_link_type'		=> '',
										'record_mime'			=> $r['record_mime'],
										'record_size'			=> $r['record_size'],
										'record_backup'			=> 0,
										'record_default'		=> ( $r['record_type'] == 'ss' AND $r['record_id'] == $this->primaryScreenshot ) ? 1 : 0,
										'_real_location'		=> $r['record_location'],
										);
		}

		return 0;
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
				$this->remove( $_details );
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
	
	/**
	 * Destructor
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function __destruct()
	{
		unset( $this->connection );
		
		$this->_clearUploadsDirectory();
	}
}