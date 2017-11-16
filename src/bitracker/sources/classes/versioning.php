<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit versioning library
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

class versioningLibrary
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
	
	/**
	 * Error string
	 *
	 * @access	public
	 * @var		string
	 */	
	public $error			= "";
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
		
	/**
	 * Grab the file information
	 *
	 * @access	protected
	 * @param	mixed		File id or File array
	 * @return	array 		File array
	 */
	protected function _extractFile( $file )
	{
		if( !$file )
		{
			return array();
		}
		
		if( is_array($file) )
		{
			return $file;
		}
		else if( intval($file) == $file )
		{
			return $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . intval($file) ) );
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Make a backup of a file
	 *
	 * @access	public
	 * @param	mixed		File id or File array
	 * @param	bool		Bypass version count check
	 * @return	boolean		Backup made
	 */
	public function backup( $file, $bypass=false )
	{
		//-----------------------------------------
		// Get file data
		//-----------------------------------------
		
		$file = $this->_extractFile( $file );
		
		if( !$file )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}

		//-----------------------------------------
		// Get records for this revision
		//-----------------------------------------
		
		$_files	= array();
		$type	= '';
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => 'record_file_id=' . $file['file_id'] . ' AND record_backup=0' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_files[ $r['record_id'] ]	= $r;
			$type						= $r['record_storagetype'];
		}
		
		if( $type != 'disk' )
		{
			$this->error = 'ONLY_LOCAL_BACKUP';
			return false;
		}
		
		//-----------------------------------------
		// Limit number of revisions
		//-----------------------------------------

		if( $this->settings['bit_versioning_limit'] > 0 AND !$bypass )
		{
			$stored = $this->DB->buildAndFetch( array( 'select' => 'count(*) as num', 'from' => 'bitracker_filebackup', 'where' => 'b_fileid=' . $file['file_id'] ) );
			
			if( $stored['num'] >= $this->settings['bit_versioning_limit'] )
			{
				$oldest	= $this->DB->buildAndFetch( array( 'select'	=> '*',
															'from'	=> 'bitracker_filebackup',
															'where' => 'b_fileid=' . $file['file_id'], 
															'order' => 'b_backup ASC', 
															'limit' => array( ( $stored['num'] - $this->settings['bit_versioning_limit'] ) + 1 )
												)		);

				$this->remove( $file, $oldest['b_id'], $oldest );
			}
		}
		
		//-----------------------------------------
		// Make backups of the file records
		//-----------------------------------------
		
		$backupIds	= array();
		
		foreach( $_files as $id => $_file )
		{
			//-----------------------------------------
			// Some basics
			//-----------------------------------------
			
			unset( $_file['record_id'] );
			$_file['record_backup']	= 1;
			
			//-----------------------------------------
			// Get file extension
			//-----------------------------------------
			
			$_fileBits	= explode( '.', $_file['record_location'] );
			$extension	= strtolower( array_pop( $_fileBits ) );
			
			//-----------------------------------------
			// Copy the file...
			//-----------------------------------------
			
			if( $_file['record_type'] == 'ssupload' )
			{
				$_newFilename	= md5( uniqid( microtime(), true ) ) . '.' . $extension;
				$_monthly		= $this->registry->bitFunctions->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) );
				
				@copy( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_file['record_location'], 
						str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_monthly . $_newFilename );
				@chmod( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_monthly . $_newFilename, IPS_FILE_PERMISSION );
				
				$_file['record_location']	= $_monthly . $_newFilename;
				
				if( $_file['record_thumb'] )
				{
					$_newFilename	= 'thumb-' . md5( uniqid( microtime(), true ) ) . '.' . $extension;
					$_monthly		= $this->registry->bitFunctions->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) );
					
					@copy( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_file['record_thumb'], 
							str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_monthly . $_newFilename );
					@chmod( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_monthly . $_newFilename, IPS_FILE_PERMISSION );
					
					$_file['record_thumb']	= $_monthly . $_newFilename;
				}
			}
			else
			{
				$_newFilename	= md5( uniqid( microtime(), true ) ) . '.' . $extension;
				$_monthly		= $this->registry->bitFunctions->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) );
				
				@copy( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $_file['record_location'], 
						str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $_monthly . $_newFilename );
				@chmod( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $_monthly . $_newFilename, IPS_FILE_PERMISSION );
				
				$_file['record_location']	= $_monthly . $_newFilename;
			}
			
			$this->DB->insert( 'bitracker_files_records', $_file );
			
			$backupIds[]	= $this->DB->getInsertId();
		}
		
		//-----------------------------------------
		// Insert revision
		//-----------------------------------------
		
		$to_insert = array( 'b_fileid'			=> $file['file_id'],
							'b_filetitle'		=> $file['file_name'],
							'b_filedesc'		=> $file['file_desc'],
							'b_hidden'			=> 0,
							'b_backup'			=> time(),
							'b_updated'			=> $file['file_updated'],
							'b_records'			=> implode( ',', $backupIds ),
							'b_version'			=> $file['file_version'],
							'b_changelog'		=> $file['file_changelog'],
						  );

		$this->DB->insert( 'bitracker_filebackup', $to_insert );

		return TRUE;
	}
	
	/**
	 * Restore an older revision
	 *
	 * @access	public
	 * @param	mixed		File id or File array
	 * @param	integer		Revision ID to restore
	 * @return	boolean		Restore done
	 */
	public function restore( $file, $id = 0 )
	{
		//-----------------------------------------
		// Check file
		//-----------------------------------------
		
		$file = $this->_extractFile( $file );
		
		if( !$file )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}
		
		//-----------------------------------------
		// Get revision to restore
		//-----------------------------------------
		
		$id = intval($id);
		
		if( !$id > 0 )
		{
			$this->error = 'NO_ID_PASSED';
			return false;
		}	

		$restore = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => 'b_id=' . $id . ' AND b_fileid=' . $file['file_id'] ) );
		
		if( !$restore['b_fileid'] )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}
		
		//-----------------------------------------
		// Get records for this revision
		//-----------------------------------------
		
		$_files	= array();
		$type	= '';
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => 'record_id IN(' . $restore['b_records'] . ')' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_files[ $r['record_id'] ]	= $r;
			$type						= $r['record_storagetype'];
		}
		
		if( $type != 'disk' )
		{
			$this->error = 'ONLY_LOCAL_BACKUP';
			return false;
		}

		//-----------------------------------------
		// Backup current version
		//-----------------------------------------
		
		$this->backup( $file, true );

		//-----------------------------------------
		// Restore revision now
		//-----------------------------------------

		$file_size	= 0;
		
		foreach( $_files as $_file )
		{
			$file_size	+= $_file['record_size'];
		}
		
		//-----------------------------------------
		// Get existing file records
		//-----------------------------------------
		
		$existing		= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => 'record_file_id=' . $file['file_id'] . ' AND record_backup=0' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$existing[ $r['record_location'] ]	= $r;
		}	
			
		//-----------------------------------------
		// Remove previous files
		//-----------------------------------------
		
		if( count($existing) )
		{
			require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/interface_storage.php' );/*noLibHook*/
			
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/core.php', 'storageEngine', 'bitracker' );
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/local.php', 'localStorageEngine', 'bitracker' );
		
			foreach( $existing as $_oldRecord )
			{
				$oldStorageEngine	= new $classToLoad( $this->registry, $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ] );
				$oldStorageEngine->remove( $_oldRecord );
			}
		}

		//-----------------------------------------
		// Now clean up database
		//-----------------------------------------
		
		$this->DB->update( 'bitracker_files', array( 
													'file_size'			=> $file_size, 
													'file_name'			=> $restore['b_filetitle'], 
													'file_desc'			=> $restore['b_filedesc'], 
													'file_version'		=> $restore['b_version'], 
													'file_changelog'	=> $restore['b_changelog'] 
													), 'file_id=' . $file['file_id'] );
		
		$this->DB->delete( 'bitracker_files_records', 'record_file_id=' . $file['file_id'] . ' AND record_backup=0' );
		$this->DB->update( 'bitracker_files_records', array( 'record_backup' => 0 ), 'record_id IN(' . $restore['b_records'] . ')' );

		//-----------------------------------------
		// Now delete the revision record
		//-----------------------------------------
		
		$this->DB->delete( 'bitracker_filebackup', 'b_id=' . $id );
		
		//---------------------------------------------------------
		// Auto-posting of topics
		//---------------------------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/topics.php', 'topicsLibrary', 'bitracker' );
		$lib_topics		= new $classToLoad( $this->registry );
		
		$file = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => "file_id={$file['file_id']}" ) );
		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		$lib_topics->sortTopic( $file, $category, 'edit' );
		
		
		return TRUE;
	}
	
	/**
	 * Remove a revision
	 *
	 * @access	public
	 * @param	mixed		File id or File array
	 * @param	integer		Revision ID to remove
	 * @param	array 		Revision data to remove [Optional: saves a query]
	 * @return	boolean		Revision removed
	 */
	public function remove( $file, $id = 0, $remove=array() )
	{
		//-----------------------------------------
		// Get file data
		//-----------------------------------------

		$file = $this->_extractFile( $file );
		
		if( !$file )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}
		
		//-----------------------------------------
		// Get revision data
		//-----------------------------------------
		
		$id = intval($id);
		
		if( !$id > 0 )
		{
			$this->error = 'NO_ID_PASSED';
			return false;
		}
		
		if( !is_array($remove) OR !count($remove) )
		{
			$remove = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => 'b_id=' . $id . ' AND b_fileid=' . $file['file_id'] ) );
		}
		
		if( !$remove['b_fileid'] )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}
		
		//-----------------------------------------
		// Get records for this revision
		//-----------------------------------------
		
		$_files	= array();
		$type	= '';

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => 'record_id IN(' . $remove['b_records'] . ')' ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			$_files[ $r['record_id'] ]	= $r;
			$type						= $r['record_storagetype'];
		}

		if( $type != 'disk' )
		{
			$this->error = 'ONLY_LOCAL_BACKUP';
			return false;
		}

		//-----------------------------------------
		// Loop over files.  Any that are unused, delete
		//-----------------------------------------

		foreach( $_files as $_file )
		{
			if( $_file['record_type'] == 'upload' OR $_file['record_type'] == 'ssupload' )
			{
				$check	= $this->DB->buildAndFetch( array( 'select' => 'max(record_id) as for_file', 'from' => 'bitracker_files_records', 'where' => "record_type='{$_file['record_type']}' AND record_location='{$_file['record_location']}' AND record_id<>{$_file['record_id']}" ) );
				
				if( !$check['for_file'] )
				{
					if( $_file['record_type'] == 'upload' )
					{
						@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/" . $_file['record_location'] );
					}
					else
					{
						@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/" . $_file['record_location'] );
						
						if( $_file['record_thumb'] )
						{
							@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/" . $_file['record_thumb'] );
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Delete the revision records
		//-----------------------------------------
		
		$this->DB->delete( "bitracker_filebackup", "b_id=" . $id );
		$this->DB->delete( 'bitracker_files_records', 'record_id IN(' . $remove['b_records'] . ')' );
		
		return TRUE;
	}
	
	/**
	 * Hide a revision
	 *
	 * @access	public
	 * @param	mixed		File id or File array
	 * @param	integer		Revision ID to restore
	 * @return	boolean		Revision removed
	 */
	public function hide( $file, $id = 0 )
	{
		//-----------------------------------------
		// Get file data
		//-----------------------------------------
		
		$file = $this->_extractFile( $file );
		
		if( !$file )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}

		//-----------------------------------------
		// Hide a revision
		//-----------------------------------------
		
		$id = intval($id);
		
		if( !$id > 0 )
		{
			$this->error = 'NO_ID_PASSED';
			return false;
		}	
		
		$this->DB->update( "bitracker_filebackup", array( 'b_hidden' => 1 ), "b_id=" . $id );
		
		return TRUE;
	}
	
	/**
	 * Unhide a revision
	 *
	 * @access	public
	 * @param	mixed		File id or File array
	 * @param	integer		Revision ID to restore
	 * @return	boolean		Revision removed
	 */
	public function unhide( $file, $id = 0 )
	{
		//-----------------------------------------
		// Get file data
		//-----------------------------------------
		
		$file = $this->_extractFile( $file );
		
		if( !$file )
		{
			$this->error = 'FILE_NOT_FOUND';
			return false;
		}

		//-----------------------------------------
		// Unhide a revision
		//-----------------------------------------
		
		$id = intval($id);
		
		if( !$id > 0 )
		{
			$this->error = 'NO_ID_PASSED';
			return false;
		}	
		
		$this->DB->update( "bitracker_filebackup", array( 'b_hidden' => 0 ), "b_id=".$id );
		
		return TRUE;
	}	
	
	/**
	 * Retrieve all revisions for a file
	 *
	 * @access	public
	 * @param	mixed		File id or File array
	 * @return	array		Revisions for the file
	 */
	public function retrieveVersions( $file )
	{
		//-----------------------------------------
		// Get all revisions of a file
		//-----------------------------------------
		
		$file = $this->_extractFile( $file );
		
		if( !$file )
		{
			$this->error = 'FILE_NOT_FOUND';
			return array();
		}
		
		//-----------------------------------------
		// Permissions
		//-----------------------------------------

		$_where	= !$this->registry->getClass('bitFunctions')->checkPerms( $file ) ? " AND b_hidden=0" : '';

		$versions = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => 'b_fileid=' . $file['file_id'] . $_where ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$versions[ $r['b_updated'] ] = $r;
		}
		
		return $versions;
	}

}
