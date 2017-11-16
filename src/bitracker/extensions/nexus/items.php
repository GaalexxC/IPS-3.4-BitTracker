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

class items_bitracker
{
	protected $fileCache = array();

	/**
	 * Get Package Image
	 *
	 * @param	string	App
	 * @param	string	Item type
	 * @param	mixed	Item ID
	 * @return	string	URL to image
	 */
	public function getPackageImage( $app, $type, $id )
	{
		if ( $type != 'file' )
		{
			return NULL;
		}
		
		try
		{
			ipsRegistry::getClass('bitFunctions');
		}
		catch ( Exception $e )
		{
			$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . "/sources/classes/functions.php", 'bitrackerFunctions', 'bitracker' );
			ipsRegistry::setClass( 'bitFunctions', new $classToLoad( ipsRegistry::instance() ) );
		}
		
		if ( !array_key_exists( $id, $this->fileCache ) )
		{
			$this->fileCache[$id] = ipsRegistry::getClass('bitFunctions')->returnScreenshotUrl( $id );
		}
								
		return $this->fileCache[$id];
	}

	/**
	 * Get item types
	 *
	 * @return	array	Items this application provides
	 */
	public function getItems()
	{
		$return['file'] = "File";
		return $return;
	}
	
	/**
	 * Init HTML
	 * Called before any form_* methods so that the skin_cp can be loaded
	 */
	public function init_html()
	{
		$this->html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_bit_nexus', 'bitracker' );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );
	}
	
	/**
	 * Add Item
	 *
	 * @param	invoice	Invoice object
	 * @return	string	HTML to display
	 */
	public function form_file( $invoice )
	{
		//-----------------------------------------
		// How many paid files do we have?
		//-----------------------------------------
	
		$files = array();
		$count = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'count(*) as count', 'from' => 'bitracker_files', 'where' => 'file_cost > 0' ) );
		if ( !$count['count'] )
		{
			ipsRegistry::getClass('output')->showError( ipsRegistry::getClass('class_localization')->words['no_paid_files_acp'], 12345.1 );
		}
		elseif ( $count['count'] < 20 )
		{
			ipsRegistry::DB()->build( array( 'select' => 'file_id, file_name', 'from' => 'bitracker_files', 'where' => 'file_cost > 0' ) );
			ipsRegistry::DB()->execute();
			while ( $r = ipsRegistry::DB()->fetch() )
			{
				$files[] = array( $r['file_id'], $r['file_name'] );
			}
		}
	
		return $this->html->add( $invoice->id, $files );
	}
	
	/**
	 * Save Item
	 *
	 * @param	invoice	Invoice object
	 * @return	array	Data to pass to invoiceModel::addItem without 'app' or 'type'
	 */
	public function save_file( $invoice )
	{
		if ( ipsRegistry::$request['file_id'] )
		{
			$id = intval( ipsRegistry::$request['file_id'] );
			$file = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id='.$id ) );
		}
		else
		{
			$name = ipsRegistry::DB()->addSlashes( ipsRegistry::$request['file_name'] );
			$file = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => "file_name='{$name}'" ) );
		}
		
		if ( !$file['file_id'] )
		{
			ipsRegistry::getClass('output')->showError( ipsRegistry::getClass('class_localization')->words['couldnot_locatepaid'], 12345.2 );
		}
	
		return array(
			'act'		=> 'new',
			'cost'		=> $file['file_cost'],
			'itemName'	=> $file['file_name'],
			'physical'	=> FALSE,
			'itemID'	=> $file['file_id'],
			'itemURI'	=> "app=bitracker&module=display&section=file&id={$file['file_id']}",
			'payTo'		=> $file['file_submitter'],
			'commission'=> ipsRegistry::$settings['bit_nexus_percent'],
			);
	}

}