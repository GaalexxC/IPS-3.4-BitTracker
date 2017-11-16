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

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class profile_bit extends profile_plugin_parent
{
	/**
	 * return HTML block
	 *
	 * @param	array		$member		Member data
	 * @return	@e string	HTML block
	 */
	public function return_html_block( $member=array() ) 
	{
		/* Load Language */
		$this->lang->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );

		//-----------------------------------------
		// Get bitracker library and API
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/api_core.php', 'apiCore' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/api/api_bit.php', 'apiBitracker', 'bitracker' );

		//-----------------------------------------
		// Create API Object
		//-----------------------------------------
		
		$bit_api = new $classToLoad();
		$bit_api->init();

		//-----------------------------------------
		// Get images
		//-----------------------------------------
		
		$files = array();
		$files = $bit_api->returnBitracker( $member['member_id'], 10 );
		
		//-----------------------------------------
		// Ready to pull formatted stuff?
		//-----------------------------------------
		
		if( count($files) )
		{
			$data = array();

			foreach( $files as $row )
			{
				$row['navigation']	= array();
				$navigation			= $this->registry->getClass('categories')->getNav( $row['file_cat'] );
				
				foreach( $navigation as $nav )
				{
					$row['navigation'][]	= "<a href='" . $this->registry->getClass('output')->buildSEOUrl( $nav[1], 'public', $nav[2], 'bitshowcat' ) . "' title='{$nav[0]}'>{$nav[0]}</a>";
				}

				$data[] = $row;
			}
			
			$output = $this->registry->getClass('output')->getTemplate('bitracker_profile')->profileDisplay( $data );
		}
		else
		{
			$output = $this->registry->getClass('output')->getTemplate('bitracker_profile')->tabNoContent( 'error_profile_no_torrents' );
		}
		      
		//-----------------------------------------
		// Macros...
		//-----------------------------------------
		
		$output = $this->registry->output->replaceMacros( $output );

		return $output;
	}
}