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
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search_form_bitracker
{
	/**
	 * Construct
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );

		/* Set some params for template */
		if( !$this->request['search_app_filters']['bitracker']['searchInKey'] )
		{
			IPSSearchRegistry::set( 'bitracker.searchInKey', 'files' );
			$this->request['search_app_filters']['bitracker']['searchInKey']	= 'files';
		}
	}
	
	/**
	 * Return sort drop down
	 * 
	 * @return	array
	 */
	public function fetchSortDropDown()
	{
		$array = array(
						'files'		=> array( 
											'date'		=> $this->lang->words['search_sort_submitted'],
											'update'	=> $this->lang->words['search_sort_updated'],
										    'title'		=> $this->lang->words['search_sort_title'],
										    'views'		=> $this->lang->words['search_sort_views'],
										    'bitracker'	=> $this->lang->words['search_sort_bitracker'],
										    'rating'	=> $this->lang->words['search_sort_rating'],
					    					),
					    'comments'	=> array(
   											'date'  => $this->lang->words['s_search_type_0'],
					    					)
					);
		
		if( ipsRegistry::$settings['use_fulltext'] )
		{
			$array['files']['relevancy'] = $this->lang->words['search_sort_relevancy']; 
		}

		return $array;
	}

	/**
	 * Return sort in
	 * Optional function to allow apps to define searchable 'sub-apps'.
	 * 
	 * @return	array
	 */
	public function fetchSortIn()
	{
		if( $this->request['search_tags'] )
		{
			return false;
		}

		$array = array( 
						array( 'files',		$this->lang->words['torrent_torrents_search'] ),
					    array( 'comments',	$this->lang->words['torrent_comments_search'] ) 
					);
		
		return $array;
	}

	/**
	 * Retuns the html for displaying the forum category filter on the advanced search page
	 *
	 * @return	string	Filter HTML
	 */
	public function getHtml()
	{
		/* Make sure class_forums is setup */
		if( ipsRegistry::isClassLoaded('categories') !== TRUE )
		{
			/* Get category class */
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . "/sources/classes/categories.php", 'class_bitcategories', 'bitracker' );
			
			$this->registry->setClass( 'categories', new $classToLoad( $this->registry ) );
			$this->registry->getClass('categories')->normalInit();
			$this->registry->getClass('categories')->setMemberPermissions();
		}
		
		$fields	= null;
		
		if( $this->cache->getCache('bit_cfields') )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/cfields.php', 'bit_customFields', 'bitracker' );
    		$fields				= new $classToLoad( $this->registry );
    		
    		if( strpos( $this->request['do'], '_comp' ) !== false )
    		{
    			$fields->file_data	= $this->request;
    		}
    		
    		$fields->cache_data	= $this->cache->getCache('bit_cfields');
    	
    		$fields->init_data( 'search' );
    		$fields->parseToEdit( true );
   		}

		return array( 'title' => IPSLib::getAppTitle('bitracker'), 'html' => ipsRegistry::getClass('output')->getTemplate('bitracker_external')->bitrackerAdvancedSearchFilters( $this->registry->getClass('categories')->catJumpList( true, 'show' ), $fields ) );
	}
}
