<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit local file storage handling
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

class localStorageEngine extends storageEngine implements interface_storage
{
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

    if ( $this->type == 'file' ){

             $_where = 'files';

           }elseif ( $this->type == 'nfo' ){ 

             $_where = 'nfo';

           }else{

              $_where = 'ss';

           }		
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_temp_records', 'where' => "record_type='{$_where}' AND record_post_key='{$data['post_key']}'" ) );
		$outer	= $this->DB->execute();


		while( $r = $this->DB->fetch($outer) )         
		{


    if ($r['record_type'] == 'ss'){

          $r['record_type'] = 'ssupload';

      }elseif($r['record_type'] == 'nfo'){

          $r['record_type'] = 'nfoupload';

      }else{

          $r['record_type'] = 'upload';

     }


			//-----------------------------------------
			// Set the new details
			//-----------------------------------------
	
			$this->details[]	= array(
										'record_post_key'		=> $r['record_post_key'],
										'record_file_id'		=> 0,
										'record_type'           => $r['record_type'],
										'record_location'		=> $r['record_location'],
										'record_db_id'			=> 0,
										'record_thumb'			=> '',
										'record_storagetype'	=> $this->settings['bit_filestorage'],
										'record_realname'		=> $r['record_realname'],
										'record_link_type'		=> '',
										'record_mime'			=> $r['record_mime'],
										'record_size'			=> $r['record_size'],
										'record_backup'			=> 0,
										'record_default'		=> ( $r['record_type'] == 'ssupload' AND $r['record_id'] == $this->primaryScreenshot ) ? 1 : 0,
										);
      }

		if( !count($this->details) )
		{

			return 1;
		}

	return 0;

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

       if ($record['record_type'] == 'upload' ){

           $path	= $this->file_path;

       }elseif ($record['record_type'] == 'nfoupload' ){

           $path	= $this->nfo_path;

       }else{

           $path	= $this->image_path;
       }
		
		if( !is_file( $path . '/' . $record['record_location'] ) )
		{
			return false;
		}

		@unlink( $path . '/' . $record['record_location'] );

		if( $record['record_type'] == 'ssupload' AND $record['record_thumb'] )
		{
			@unlink( $path . '/' . $record['record_thumb'] );
		}
		
		return true;
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