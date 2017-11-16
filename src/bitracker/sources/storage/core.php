<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit core storage engine
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

class storageEngine
{
	/**#@+
	 * IPB objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Category information
	 *
	 * @access	protected
	 * @var		array
	 */	
	protected $category		= array();
	
	/**
	 * Allowed mime-types
	 *
	 * @access	protected
	 * @var		array
	 */	
	protected $types		= array();
	
	/**
	 * New records to store @ commit
	 *
	 * @access	public
	 * @var		array
	 */	
	public $details			= array();

	/**
	 * File path
	 *
	 * @access	protected
	 * @var 	string
	 */
	protected $file_path		= '';

	/**
	 * Nfo path
	 *
	 * @access	protected
	 * @var 	string
	 */
	protected $nfo_path		= '';
	
	/**
	 * Images path
	 *
	 * @access	protected
	 * @var 	string
	 */
	protected $image_path		= '';
	
	/**
	 * Engine type
	 *
	 * @access	protected
	 * @var		string		(file|screenshot|nfo)
	 */
	protected $type				= '';
	
	/**
	 * Primary screenshot ID (from temp table)
	 *
	 * @var		int
	 */
	protected $primaryScreenshot	= 0;

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
		
		//-----------------------------------------
		// Store category and mime-type info
		//-----------------------------------------

		$this->category		= $category;
		$this->types		= $this->registry->getClass('bitFunctions')->getAllowedTypes( $this->category );
		$this->mimecache	= $this->cache->getCache('bit_mimetypes');
		
		$this->file_path	= str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] );
		$this->nfo_path	    = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] );
		$this->image_path	= str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] );
		
		$this->type			= $type;
		
		//-----------------------------------------
		// Figure out primary screenshot ID
		//-----------------------------------------
		
		if( is_array($this->request['primary']) AND count($this->request['primary']) )
		{
			foreach( $this->request['primary'] as $k => $v )
			{
				if( intval(trim($k)) > 0 )
				{
					$this->primaryScreenshot	= intval($k);
				}
				else if( strpos( $k, "ss_cur_" ) === 0 )
				{
					$this->primaryScreenshot	= intval( str_replace( 'ss_cur_', '', $k ) );
				}
				else if( strpos( $k, "l_" ) === 0 )
				{
					$this->primaryScreenshot	= intval( str_replace( 'l_', '', $k ) );
				}
			}
		}
	}

	/**
	 * Check for nfo if its allowed/required?
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function checkForNfo()
	{
		if( count($this->details) )
		{
			foreach( $this->details as $_record )
			{
				if( $_record['record_type'] == 'nfoupload' OR $_record['record_type'] == 'nfolink' )
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Check for at least one screenshot
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function checkForScreenshot()
	{
		if( count($this->details) )
		{
			foreach( $this->details as $_record )
			{
				if( $_record['record_type'] == 'ssupload' OR $_record['record_type'] == 'sslink' )
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Retrieve collective file size
	 *
	 * @access	public
	 * @return	int			Summed file size
	 */	
	public function getFileSize()
	{
		$file_size	= 0;
		
		if( count($this->details) )
		{
			foreach( $this->details as $_record )
			{
				if( $_record['record_type'] != 'ssupload' AND $_record['record_type'] != 'sslink' )
				{
					$file_size	+= $_record['record_size'];
				}
			}
		}

		return $file_size;
	}
	
	/**
	 * Commit the files to the database
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return	boolean
	 */	
	public function commit( $file_id=0 )
	{
		//-----------------------------------------
		// Loop over all the files to save
		//-----------------------------------------

		if( count($this->details) )
		{

			foreach( $this->details as $_record )
			{
				$_record['record_file_id']	= $file_id;

				if( $_record['_real_location'] )
				{
					unset($_record['_real_location']);
				}

				
				$this->DB->insert( "bitracker_files_records", $_record );
				
				$_record['record_id']	= $this->DB->getInsertId();
				
				//-----------------------------------------
				// Build a thumbnail if appropriate
				//-----------------------------------------
				
				if( $_record['record_type'] == 'ssupload' )
				{
		 			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
					$moderate		= new $classToLoad( $this->registry );
		
					$thumbnail	= $moderate->buildThumbnail(
															array_merge(
																		$_record,
																		array( 
																			'file_cat'			=> $this->category['cid'],
																			'file_id'			=> $file_id,
																			)
																		)
															);
				}

			}



		}

				//-----------------------------------------
				// Store the torrent details!
				//-----------------------------------------

	return true;

	}
	
	/**
	 * Verify if a file is a current entry
	 *
	 * @access	public
	 * @param	array 		File info
	 * @return	bool
	 */
	public function isCurrent( $record )
	{
		if( count($this->details) )
		{
			foreach( $this->details as $_details )
			{
				if( $record['record_location'] == $_details['record_location'] )
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Get the file name
	 *
	 * @access	protected
	 * @param	string		Filename
	 * @return	@e void
	 */
	protected function getFileName( $_filename )
	{
		return md5( uniqid( microtime(), true ) ) . '-' . str_replace( array( " ", "\n", "\r", "\t" ), '_', $this->registry->getClass('bitFunctions')->getFileName( $_filename ) );
	}

	/**
	 * Clean up temp folder
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _clearUploadsDirectory()
	{
		if( count($this->details) )
		{
			foreach( $this->details as $_record )
			{
				if( $_record['_real_location'] )
				{
					$_record['record_location']	= $_record['_real_location'];
				}

				if( $_record['record_type'] == 'ssupload' )
				{
					if( is_file( $this->image_path . '/' . $_record['record_location'] ) )
					{
						@unlink( $this->image_path . '/' . $_record['record_location'] );
					}
				}
                elseif( $_record['record_type'] == 'nfoupload' )
                {
					if( is_file( $this->nfo_path . '/' . $_record['record_location'] ) )
					{
						@unlink( $this->nfo_path . '/' . $_record['record_location'] );
					}
                }
				else
				{
					if( is_file( $this->file_path . '/' . $_record['record_location'] ) )
					{
						@unlink( $this->file_path . '/' . $_record['record_location'] );
					}
				}
			}
		}
	}

}