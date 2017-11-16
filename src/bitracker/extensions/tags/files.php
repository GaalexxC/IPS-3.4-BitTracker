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

class tags_bitracker_files extends classes_tag_abstract
{
	/**
	 * Cache of files
	 * 
	 * @var	array
	 */
	protected $fileCache	= array();
		
	/**
	 * CONSTRUCTOR
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make registry objects */
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Init
	 *
	 * @return	@e void
	 */
	public function init()
	{
		//-----------------------------------------
		// Load caches - uses external lib if avail
		//-----------------------------------------	
		
		if( !$this->registry->isClassLoaded('categories') )
		{
			define( 'SKIP_ONLINE_CHECK', true );
			ipsRegistry::getAppClass( 'bitracker' );
		}
		
		parent::init();
	}
	
	/**
	 * Force preset tags
	 *
	 * @param	string	view to show
	 * @param	array	Where data to show
	 * @return	string
	 */
	public function render( $what, $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('categories')->cat_lookup[ $where['meta_parent_id'] ];
		}
		
		if ( ! empty( $category['ctags_predefined'] ) )
		{
			/* Turn off open system */
			$this->settings['tags_open_system'] = false;
		}
		
		return parent::render( $what, $where );
	}
	
	/**
	 * Fetches parent ID
	 * 
	 * @param 	array	Where Data
	 * @return	int		Id of parent if one exists or 0
	 */
	public function getParentId( $where )
	{
		$file	= $this->_getFile( $where['meta_id'] );
		
		return intval( $file['file_cat'] );
	}
	
	/**
	 * Fetches permission data
	 * 
	 * @param 	array	Where Data
	 * @return	string	Comma delimiter or *
	 */
	public function getPermissionData( $where )
	{
		if ( isset( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('categories')->cat_lookup[ $where['meta_parent_id'] ];
		}
		else if ( isset( $where['meta_id'] ) )
		{
			$file		= $this->_getFile( $where['meta_id'] );
			$category	= $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		}
		
		return $category['perm_view'];
	}
	
	/**
	 * Basic permission check
	 * 
	 * @param	string	$what (add/remove/edit/create/prefix) [ add = add new tags to items, create = create unique tags, use a tag as a prefix for an item ]
	 * @param	array	$where data
	 */
	public function can( $what, $where )
	{
		/* Check parent */
		$return = parent::can( $what, $where );

		if ( $return === false  )
		{
			return $return;
		}
		
		if ( !empty( $where['meta_id'] ) )
		{
			$file		= $this->_getFile( $where['meta_id'] );
			$category	= $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		}
		else if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('categories')->cat_lookup[ $where['meta_parent_id'] ];
		}
		
		/* Category disabled */
		if ( $category['ctags_disabled'] )
		{
			return false;
		}

		switch ( $what )
		{
			case 'create':
				if ( ! $this->_isOpenSystem() )
				{
					return false;
				}
				
				return true;
			break;
			case 'add':
			case 'prefix':
				if ( in_array( $category['cid'], $this->registry->getClass('categories')->member_access['add'] ) )
				{
					return true;
				}
			break;
			case 'edit':
			case 'remove':
				if ( $this->memberData['member_id'] == $file['file_submitter'] )
				{
					return true;
				}
				else if( $this->memberData['g_is_supmod'] )
				{
					return true;
				}
				else
				{
					if( $what == 'edit' )
					{
						return $this->registry->getClass('bitFunctions')->checkPerms( count($file) ? $file : array( 'file_cat' => $category['cid'] ), 'modcanedit' );
					}
					else if( $what == 'remove' )
					{
						return $this->registry->getClass('bitFunctions')->checkPerms( count($file) ? $file : array( 'file_cat' => $category['cid'] ), 'modcandel' );
					}
				}
			break;
		}
		
		return false;
	}
	
	/**
	 * Is the file visible?
	 * 
	 * @param 	array	Where Data
	 * @return	int		If meta item is visible (not unapproved, etc)
	 */
	public function getIsVisible( $where )
	{
		$file	= $this->_getFile( $where['meta_id'] );
		
		return $file['file_open'];
	}
	
	/**
	 * Search for tags
	 * 
	 * @param	mixed $tags	Array or string
	 * @param	array $options	Array( 'meta_id' (array), 'meta_parent_id' (array), 'olderThan' (int), 'youngerThan' (int), 'limit' (int), 'sortKey' (string) 'sortOrder' (string) )
	 * @return	array
	 */
	public function search( $tags, $options )
	{
		$ok = array();
		
		/* Fix up forum IDs */
		if ( isset( $options['meta_parent_id'] ) )
		{
			if ( is_array( $options['meta_parent_id'] ) )
			{
				foreach( $options['meta_parent_id'] as $id )
				{
					if ( $this->_canSearchCategory( $id ) === true )
					{
						$ok[] = $id;
					}
				}
			}
			else
			{
				if ( $this->_canSearchCategory( $options['meta_parent_id'] ) === true )
				{
					$ok[] = $options['meta_parent_id'];
				}
			}
		}
		else
		{
			$ok = $this->registry->getClass('categories')->member_access['view'];
		}
		
		$options['meta_parent_id'] = $ok;
		
		return parent::search( $tags, $options );
	}
	
	/**
	 * Fetch a list of pre-defined tags
	 * 
	 * @param 	array	Where Data
	 * @return	mixed
	 */
	protected function _getPreDefinedTags( $where=array() )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('categories')->cat_lookup[ $where['meta_parent_id'] ];
		}
		
		$this->settings['tags_predefined']	= ( ! empty( $category['ctags_predefined'] ) ) ? $category['ctags_predefined'] : $this->settings['tags_predefined'];
		
		return parent::_getPreDefinedTags( $where );
	}
	
	/**
	 * Are prefixes enabled in this cat?
	 * 
	 * @param 	array		$where		Where Data
	 * @return 	@e boolean
	 */
	protected function _prefixesEnabled( $where )
	{
		if ( ! empty( $where['meta_parent_id'] ) )
		{
			$category	= $this->registry->getClass('categories')->cat_lookup[ $where['meta_parent_id'] ];
		}
		
		if ( $category['ctags_noprefixes'] )
		{
			return false;
		}
		else
		{
			return parent::_prefixesEnabled( $where );
		}
	}
	
	/**
	 * Check a category for tag searching
	 * 
	 * @param	id		$id		Category ID
	 * @return	@e boolean
	 */
	protected function _canSearchCategory( $id )
	{		
		if( !in_array( $id, $this->registry->getClass('categories')->member_access['view'] ) )
		{
			return false;
		}
		
		$category	= $this->registry->getClass('categories')->cat_lookup[ $id ];
		
		if( !$category['ctags_disabled'] )
		{
			return false;
		}

		return true;
	}
	
	/**
	 * Fetch a file record
	 * 
	 * @param	integer
	 * @return	@e array
	 */
	protected function _getFile( $id )
	{
		if ( ! isset( $this->fileCache[ $id ] ) )
		{
			$this->fileCache[ $id ] = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . intval($id) ) );
		}
		
		return $this->fileCache[ $id ];
	}
}