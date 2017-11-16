<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.download Manager category library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		1st April 2004
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_bitcategories
{
	/**
	 * Category cache array
	 *
	 * @var		array
	 */
	public $cat_cache			= array();

	/**
	 * Direct id => data mapping
	 *
	 * @var		array
	 */
	public $cat_lookup			= array();

	/**
	 * Parent/child relationship array
	 *
	 * @var		array
	 */
	public $parent_lookup		= array();

	/**
	 * Library initialized
	 *
	 * @var		boolean
	 */
	protected $init				= false;
	
	/**
	 * Stats correct merged
	 *
	 * @var		boolean
	 */
	protected $statsMerged		= false;

	/**
	 * Library initialization failed
	 *
	 * @var		boolean
	 */
	protected $init_failed		= false;

	/**
	 * Categories member can access
	 *
	 * @var		array
	 */
	public $member_access		= array();

	/**
	 * Member moderators
	 *
	 * @var		array
	 */
	public $mem_mods			= array();

	/**
	 * Category moderators
	 *
	 * @var		array
	 */
	public $cat_mods			= array();

	/**
	 * Group moderators
	 *
	 * @var		array
	 */
	public $group_mods			= array();
	
	/**#@+
	 * Registry objects
	 *
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
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
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
	 * Main initialization
	 *
	 * @return	@e void
	 */
	public function normalInit()
	{
		if ( ! $this->init )
		{
			//-----------------------------------------
			// Have cache data?
			//-----------------------------------------
			
			if ( ! is_array( $this->cache->getCache( 'bit_cats' ) ) )
			{
				$this->init_failed = 1;
				$this->fullInit( true );
			}
			
			//-----------------------------------------
			// If yes, set our data store
			//-----------------------------------------
			
			else
			{
				foreach( $this->cache->getCache( 'bit_cats' ) as $parentid => $cid )
				{
					foreach( $cid as $catid => $info )
					{
						$this->cat_cache[ $parentid ][ $catid ]			= $info;
						$this->subcat_lookup[ $parentid ][] 			= $catid;
						$this->parent_lookup[ $catid ] 					= $info['cparent'];
						$this->cat_lookup[ $catid ]						= $info;
					}
				}
			}
		}
		
		//-----------------------------------------
		// No data store, full init
		//-----------------------------------------

		if( empty( $this->cat_cache ) )
		{
			$this->init_failed = 1;
			$this->fullInit( true );
		}
		
		//-----------------------------------------
		// Build moderators
		//-----------------------------------------
				
		$this->buildModerators(true);
		
		$this->init = 1;
		
		//-----------------------------------------
		// Merge stats as needed
		//-----------------------------------------
		
		$this->mergeStats();
	}

	/**
	 * Full initialization
	 *
	 * @param	bool	Skip merge stats call
	 * @return	@e void
	 */
	public function fullInit( $skipMerge=false )
	{
		if ( ! $this->init )
		{
			$this->cat_lookup	= array();

			//-----------------------------------------
			// Build data store from DB
			//-----------------------------------------
			
			$this->DB->build( array( 
											'select'   => 'c.*',
											'from'     => array( 'bitracker_categories' => 'c' ),
											'order'    => 'c.cparent, c.cposition',
											'add_join' => array(
																array(
																		'select' => 'p.*',
																		'from'   => array( 'permission_index' => 'p' ),
																		'where'  => "p.perm_type='cat' AND p.perm_type_id=c.cid AND p.app='bitracker'",
																		'type'   => 'left',
																	)
												)
									)	);
			$this->DB->execute();
			
			$cache	= array();
			
			while( $cat = $this->DB->fetch() )
			{
				$cat['cfileinfo']	= unserialize( $cat['cfileinfo'] );
				$cat['coptions']	= unserialize( $cat['coptions'] );
			
				$cache[ $cat['cparent'] ][ $cat['cid'] ] = $cat;
				
				$this->cat_cache[ $cat['cparent'] ][ $cat['cid'] ]	= $cat;
				$this->subcat_lookup[ $cat['cparent'] ][] 			= $cat['cid'];
				$this->parent_lookup[ $cat['cid'] ] 				= $cat['cparent'];
				$this->cat_lookup[ $cat['cid'] ]					= $cat;
			}
			
			//-----------------------------------------
			// Set the "cache"
			//-----------------------------------------
			
			$this->cache->updateCacheWithoutSaving( 'bit_cats', $cache );
			
			//-----------------------------------------
			// And fix the real cache if normal init failed
			//-----------------------------------------
			
			if( $this->init_failed )
			{
				$this->cache->setCache( 'bit_cats', $cache, array( 'array' => 1 ) );
				$this->init_failed = 0;
			}
		}
		
		//-----------------------------------------
		// Build moderators
		//-----------------------------------------
		
		$this->buildModerators(false);
		
		$this->init = 1;
		
		//-----------------------------------------
		// Merge stats as needed
		//-----------------------------------------
		
		if( !$skipMerge )
		{
			$this->mergeStats();
		}
	}
	
	/**
	 * Merge stats from children categories into their parents
	 *
	 * @return	@e void
	 */
	protected function mergeStats()
	{
		if( $this->statsMerged )
		{
			return;
		}
		
		$this->setMemberPermissions();
		
		foreach( $this->cat_lookup as $id => $cat )
		{
			$children	= $this->getChildren( $id );

			if( count($children) )
			{
				foreach( $children as $_child )
				{
					if( in_array( $_child, $this->member_access['show'] ) )
					{
						$_childCat	= $this->cat_lookup[ $_child ];
	
						$cat['cfileinfo']['total_views']		+= $_childCat['cfileinfo']['total_views'];
						$cat['cfileinfo']['total_bitracker']	+= $_childCat['cfileinfo']['total_bitracker'];
						$cat['cfileinfo']['total_files']		+= $_childCat['cfileinfo']['total_files'];
						$cat['cfileinfo']['pending_files']		+= $_childCat['cfileinfo']['pending_files'];
						$cat['cfileinfo']['broken_files']		+= $_childCat['cfileinfo']['broken_files'];
						
						if( $_childCat['cfileinfo']['date'] > $cat['cfileinfo']['date'] )
						{
							$cat['cfileinfo']['date']		= $_childCat['cfileinfo']['date'];
							$cat['cfileinfo']['mid']		= $_childCat['cfileinfo']['mid'];
							$cat['cfileinfo']['fid']		= $_childCat['cfileinfo']['fid'];
							$cat['cfileinfo']['fname']		= $_childCat['cfileinfo']['fname'];
							$cat['cfileinfo']['fname_furl']	= $_childCat['cfileinfo']['fname_furl'];
							$cat['cfileinfo']['mname']		= $_childCat['cfileinfo']['mname'];
							$cat['cfileinfo']['seoname']	= $_childCat['cfileinfo']['seoname'];
							$cat['cfileinfo']['updated']	= $_childCat['cfileinfo']['updated'];
						}
					}
				}
				
				$this->cat_lookup[ $id ]					= $cat;
				$this->cat_cache[ $cat['cparent'] ][ $id ]	= $cat;
			}
		}
		
		$this->statsMerged	= true;
	}
	
	/**
	 * Build moderators array
	 *
	 * @param	boolean		Try Cache
	 * @return	@e void
	 */
	public function buildModerators( $try_cache=true )
	{
		$use	= array();
		$loaded	= false;
		
		//-----------------------------------------
		// Trying cache?
		//-----------------------------------------

		if( $try_cache )
		{
			if( $this->cache->exists('bit_mods') )
			{
				$use	= $this->cache->getCache('bit_mods');
				$loaded	= true;
			}
		}

		//-----------------------------------------
		// Pull from DB?
		//-----------------------------------------
		
		if( !$loaded )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mods' ) );
			$this->DB->execute();
			
			while( $v = $this->DB->fetch() )
			{
				$use[] = $v;
			}
		}
		
		//-----------------------------------------
		// Loop and store
		//-----------------------------------------
		
		foreach( $use as $v )
		{
			$temp = explode( ":", $v['modgmid'] );
			
			if( $v['modtype'] == 0 )
			{
				$v['group_name'] = $temp[1];
				$v['group_id']   = $temp[0];
				
				$this->group_mods[ $v['group_id'] ][] = $v;
			}
			else
			{
				$v['mem_name'] = $temp[1];
				$v['mem_id']   = $temp[0];
				
				$this->mem_mods[ $v['mem_id'] ][] = $v;
			}
			
			$cats = explode( ",", $v['modcats'] );
			
			if( count($cats) )
			{
				foreach( $cats as $j => $l )
				{
					$key = "";
					
					if( $v['modtype'] == 0 )
					{
						$key = "g" . $v['group_id'];
					}
					else
					{
						$key = "m" . $v['mem_id'];
					}
					
					$this->cat_mods[ $l ][$key] = $v;
				}
			}
			
			unset($temp);
		}
	}
		
	/**
	 * Get parents of a category
	 *
	 * @param	integer		Category id
	 * @param	array 		Parent ids
	 * @return	array 		Parent ids
	 */
	public function getParents( $catid, $parent_ids=array() )
	{
		if( is_array($this->cat_lookup[ $catid ]) )
		{
			if( $this->parent_lookup[ $catid ] > 0 )
			{
				$parent_ids		= $this->getParents( $this->parent_lookup[ $catid ], $parent_ids );
				$parent_ids[]	= $this->parent_lookup[ $catid ];
			}
		}
		
		
		return array_unique($parent_ids);
	}
	
	/**
	 * Get children ids of a category
	 *
	 * @param	integer		Category id
	 * @param	array 		Children ids
	 * @return	array 		Children ids
	 */
	public function getChildren( $catid, $child_ids=array() )
	{
		$final_ids	= array();

		if ( isset($this->cat_cache[ $catid ]) AND is_array( $this->cat_cache[ $catid ] ) )
		{
			$final_ids = array_merge( $child_ids, $this->subcat_lookup[ $catid ]);

			foreach( $this->cat_cache[ $catid ] as $id => $data )
			{
				$subchild_ids = $this->getChildren( $data['cid'], $final_ids );
				
				if( is_array($subchild_ids) AND count($subchild_ids) )
				{
					$final_ids = array_unique(array_merge($final_ids, $subchild_ids));
				}
			}
		}
		
		return $final_ids;
	}	
	
	/**
	 * Rebuild category cache
	 *
	 * @return	@e void
	 */
	public function rebuildCatCache( )
	{
		$cache = array();
		
		$this->DB->build( array( 
								'select'	=> 'c.*',
								'from'		=> array( 'bitracker_categories' => 'c' ),
								'order'		=> 'c.cparent, c.cposition',
								'add_join'	=> array(
													array(
															'select' => 'p.*',
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.perm_type='cat' AND p.perm_type_id=c.cid AND p.app='bitracker'",
															'type'   => 'left',
														)
									)
						)	);

		$this->DB->execute();
		
		while( $cat = $this->DB->fetch() )
		{
			$cat['cfileinfo']	= unserialize( $cat['cfileinfo'] );
			$cat['coptions']	= unserialize( $cat['coptions'] );
			
			$cache[ $cat['cparent'] ][ $cat['cid'] ] = $cat;
		}

		$this->cache->setCache( 'bit_cats', $cache, array( 'array' => 1, 'donow' => 1 ) );
		
		//-----------------------------------------
		// Re-initialize
		//-----------------------------------------
		
		$this->init	= false;
		$this->fullInit();		
	}
	
	/**
	 * Rebuild cached category information (latest file, etc.)
	 *
	 * @param	mixed		Category id or 'all'
	 * @return	boolean 	Successful
	 */
	public function rebuildFileinfo( $catid='all' )
	{
		//-----------------------------------------
		// Not rebuilding all categories?
		//-----------------------------------------
		
		if( $catid != 'all' )
		{
			if( $catid == 0 )
			{
				return false;
			}
			
			//-----------------------------------------
			// Rebuild the category
			//-----------------------------------------
		
			$this->_rebuildCategoryFileinfo( $catid );
	 		
			//-----------------------------------------
			// And it's parents
			// --We are no longer caching child data in parent categories, so we don't need to
			//   rebuild them here
			//-----------------------------------------
	 		
	 		//if( $this->cat_lookup[$catid]['cparent'] != 0 )
	 		//{
	 		//	$this->rebuildFileinfo( $this->cat_lookup[ $catid ]['cparent'] );
 			//}
 		}
 		else
 		{
			//-----------------------------------------
			// Rebuild every category
			//-----------------------------------------
			
	 		foreach( $this->cat_lookup as $catid => $catdata )
	 		{
		 		$this->_rebuildCategoryFileinfo( $catid );
			}
		}
 		
		//-----------------------------------------
		// And do the cache..
		//-----------------------------------------
			
 		$this->rebuildCatCache();
 		return TRUE;
	}
	
	/**
	 * Rebuild a specific category
	 *
	 * @param	mixed		Category id or 'all'
	 * @return	boolean 	Successful
	 */
	public function _rebuildCategoryFileinfo( $catid='all' )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$stats_array = array( 'total_views' => 0, 'total_files' => 0, 'total_bitracker' => 0, 'date' => 0 );
		
		if( $catid == 0 )
		{
			return false;
		}
		
		//-----------------------------------------
		// Get the children
		//-----------------------------------------
		
		$final_string = $catid;
		
		/**
		 * By including children, you potentially get "latest info" for children you can't see.
		 * We need to store ONLY the category's info in the cache, then combine the caches for children
		 * categories at runtime.
		 * @link	http://bugs.---.com/tracker/issue-25418-members-can-view-file-from-child-category-in-latest-file-they-have-no-permission-to-view/
		 */
		//$children = $this->getChildren( $catid );
		//$children_string = "";
		
		//if( is_array($children) AND count($children) > 0 )
		//{
		//	$children_string = implode( ",", $children );
		//}
		
		//if( $children_string )
		//{
		//	$final_string = $catid . "," . $children_string;
		//}
		//else
		//{
		//	$final_string = $catid;
		//}
 		
		//-----------------------------------------
		// Basic stats
		//-----------------------------------------
		
		$stats = $this->DB->buildAndFetch( array( 'select' => 'COUNT(file_id) as files, SUM(file_bitracker) as bitracker, SUM(file_views) as views',
												  'from'   => 'bitracker_files',
												  'where'  => 'file_cat IN(' . $final_string . ') AND file_open=1'
										  )		 );
		
		$stats_array['total_views'] 	= $stats['views'];
		$stats_array['total_bitracker'] = $stats['bitracker'];
		$stats_array['total_files'] 	= $stats['files'];
		
		//-----------------------------------------
		// Pending files
		//-----------------------------------------
		
		$pend = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as files',
												 'from'   => 'bitracker_files',
												 'where'  => 'file_cat IN(' . $final_string . ') AND file_open=0'
										 )		);
		
		$stats_array['pending_files'] 	= $pend['files'];	
		
		//-----------------------------------------
		// Broken files
		//-----------------------------------------
		
		$broken = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as files',
												   'from'   => 'bitracker_files',
												   'where'  => 'file_cat IN(' . $final_string . ') AND file_broken=1'
										   )	  );
		
		$stats_array['broken_files'] 	= $broken['files'];			
		
		//-----------------------------------------
		// And the latest file
		//-----------------------------------------
		
		if ( $stats_array['total_files'] )
		{
			$stats = $this->DB->buildAndFetch( array( 'select'	=> 'CASE WHEN f.file_updated > f.file_submitted THEN f.file_updated ELSE f.file_submitted END as highest_date, f.file_id, f.file_cat, f.file_name, f.file_name_furl, f.file_updated, f.file_submitted, f.file_submitter' ,
													  'from'		=> array( 'bitracker_files' => 'f' ),
													  'where'		=> 'f.file_cat IN (' . $final_string . ') AND f.file_open=1',
													  'order'		=> 'highest_date DESC',
													  'limit'		=> array( 1 ),
													  'add_join'	=> array( array( 'select'	=> 'm.members_display_name, m.members_seo_name',
																					 'where'	=> 'm.member_id=f.file_submitter',
																					 'from'		=> array( 'members' => 'm' ),
																					 'type'		=> 'left' ) )
											  )		 );
			
			$stats_array['date'] 		= $stats['file_updated'] ? $stats['file_updated'] : $stats['file_submitted'];
			$stats_array['mid'] 		= $stats['file_submitter'];
			$stats_array['fid'] 		= $stats['file_id'];
			$stats_array['fname']		= $stats['file_name'];
			$stats_array['fname_furl']	= $stats['file_name_furl'];
			$stats_array['mname']		= $stats['members_display_name'];
			$stats_array['seoname']		= $stats['members_seo_name'];
			$stats_array['updated'] 	= $stats['file_updated'] ? 1 : 0;
		}
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $stats_array, 'trackUpdateCategoryInfo' );
		
 		$this->DB->update( 'bitracker_categories', array( 'cfileinfo' => serialize($stats_array) ), 'cid=' . $catid );
 		
 		return true;
 	}
	
	/**
	 * Rebuild the stats cache
	 *
	 * @return	boolean 	Successful
	 */
	public function rebuildStatsCache()
	{
		//-----------------------------
		// INIT
		//-----------------------------
		
		$cache = array();
		
		if( !count($this->cat_lookup) )
		{
			$this->fullInit();
		}
		
		//-----------------------------
		// Get total file count
		//-----------------------------
				
		$filecnt = $this->DB->buildAndFetch( array( 'select' => 'COUNT(file_id) as files',
													'from'   => 'bitracker_files',
													'where'  => 'file_open=1'
											)		);

		$cache['total_files'] = $filecnt['files'];

		//-----------------------------
		// Get total category count
		//-----------------------------

		$cnt = count( $this->cat_lookup );
		
		$cache['total_categories'] = $cnt;
		
		//-----------------------------
		// Get total download count
		//-----------------------------
				
		$dlcnt = $this->DB->buildAndFetch( array( 'select' => 'SUM(file_bitracker) as dls',
												  'from'   => 'bitracker_files',
												  'where'  => 'file_open=1'
										  )		 );
												
		$cache['total_bitracker'] = $dlcnt['dls'];

		//-----------------------------
		// Get distinct author count
		//-----------------------------		
				
		$authors = $this->DB->buildAndFetch( array( 'select' => 'COUNT(' . $this->DB->buildDistinct('file_submitter') . ') as authors',
													'from'   => 'bitracker_files',
													'where'  => 'file_open=1'
											)		);

		$cache['total_authors'] = $authors['authors'];

		//-----------------------------
		// Get latest file info
		//-----------------------------
		
		$fileinfo = $this->DB->buildAndFetch( array( 'select' 	=> 'f.file_id, f.file_name, f.file_name_furl, f.file_submitter, f.file_submitted',
													 'from'		=> array( 'bitracker_files' => 'f' ),
													 'where'	=> 'f.file_open=1',
													 'order'	=> 'f.file_submitted DESC',
													 'limit'	=> array( 1 ),
													 'add_join'	=> array(
													 					array(
													 						'select'	=> 'm.members_display_name',
													 						'from'		=> array( 'members' => 'm' ),
													 						'where'		=> 'm.member_id=f.file_submitter',
													 						'type'		=> 'left'
													 						)
													 					)
											)		);

		$cache['latest_fid']		= $fileinfo['file_id'];
		$cache['latest_fname']		= $fileinfo['file_name'];
		$cache['latest_fname_furl']	= $fileinfo['file_name_furl'];
		$cache['latest_mid']		= $fileinfo['file_submitter'];
		$cache['latest_mname']		= $fileinfo['members_display_name'];
		$cache['latest_date']		= $fileinfo['file_submitted'];
		
		/* Data Hook Location */
		IPSLib::doDataHooks( $cache, 'trackRebuildStatsCache' );
		
		$this->cache->setCache( 'bit_stats', $cache, array( 'array' => 1, 'donow' => 0 ) );
		return TRUE;
	}
	
	/**
	 * Rebuild the moderator cache
	 *
	 * @return	@e void
	 */
	public function rebuildModCache()
	{
		$cache = array();
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'bitracker_mods',
								 'order'  => 'modid'
				   		)		);
		$this->DB->execute();
		
		while( $mod = $this->DB->fetch() )
		{
			$cache[ $mod['modid'] ] = $mod;
		}
		
		$this->cache->setCache( 'bit_mods', $cache, array( 'array' => 1, 'donow' => 1 ) );		
	}
		
	/**
	 * Retrieve the navigation bar
	 *
	 * @param	integer		Current category id
	 * @param	string		Query string to use in URL
	 * @param	boolean		Currently in ACP
	 * @return	array 		Navigation entries
	 */
	public function getNav( $catid, $querybit='app=bitracker&amp;showcat=', $acp=false )
	{
		if( $acp )
		{
			$nav_array[] = array( $this->registry->output->buildSEOUrl( $this->settings['base_url'] . $querybit . $catid, $this->cat_lookup[ $this->cat_lookup[ $catid ]['cparent'] ]['cname_furl'] ), $this->cat_lookup[ $this->cat_lookup[ $catid ]['cparent'] ]['cname'] );
		}
		else
		{
			$nav_array[] = array( 0 => $this->cat_lookup[ $catid ]['cname'], 1 => $querybit . $catid, 2 => $this->cat_lookup[ $catid ]['cname_furl'] );
		}
		
		$parent_ids = $this->getParents( $catid );
		
		if ( is_array($parent_ids) and count($parent_ids) )
		{
			$parent_ids = array_reverse($parent_ids);
			
			foreach( $parent_ids as $id )
			{
				if( $id > 0 )
				{
					if( $acp )
					{
						$nav_array[] = array( $this->registry->output->buildSEOUrl( $this->settings['base_url'] . $querybit . $this->cat_lookup[ $id ]['cid'], $this->cat_lookup[ $id ]['cname_furl'] ), $this->cat_lookup[ $this->cat_lookup[ $id ]['cparent'] ]['cname'] );	
					}
					else
					{
						$nav_array[] = array( 0 => $this->cat_lookup[ $id ]['cname'], 1 => $querybit . $this->cat_lookup[ $id ]['cid'], $this->cat_lookup[ $id ]['cname_furl']  );
					}
				}
			}
		}
		
		return array_reverse($nav_array);
	}
	
	/**
	 * Retrieve the categories using a particular mimemask
	 *
	 * @param	integer		Mime mask id
	 * @return	array 		Categories using the mimemask
	 */
	public function getCatsMimemask( $mask_id )
	{
		$return_ids = array();
		
		if( ! $mask_id )
		{
			return;
		}
		
		if( ! is_array( $this->cat_lookup ) )
		{
			return;
		}
		
		if( ! count( $this->cat_lookup ) )
		{
			return;
		}
		
		foreach( $this->cat_lookup as $catid => $catinfo )
		{
			if( $catinfo['coptions']['opt_mimemask'] == $mask_id )
			{
				$return_ids[] = $catid;
			}
		}
		
		return $return_ids;
	}
	
	/**
	 * Retrieve the categories using a particular custom field
	 *
	 * @param	integer		Custom field id
	 * @return	array 		Categories using the custom field
	 */
	public function getCatsCfield( $field_id )
	{
		$return_ids = array();
		
		if( ! $field_id )
		{
			return;
		}
		
		if( ! is_array( $this->cat_lookup ) )
		{
			return;
		}
		
		if( ! count( $this->cat_lookup ) )
		{
			return;
		}
		
		foreach( $this->cat_lookup as $catid => $catinfo )
		{
			$cfields = explode( ',', $catinfo['ccfields'] );
			
			if( in_array( $field_id, $cfields ) )
			{
				$return_ids[] = $catid;
			}
		}
		
		return $return_ids;
	}
	
	/**
	 * Category dropdown/multi-select list generation
	 * Does not return the select HTML tag, just the options
	 *
	 * @param	boolean		Add a "root category" option
	 * @param 	string		Which permissions key to check
	 * @param	array 		Selected options
	 * @return	array 		Categories dropdown/multiselect options
	 */
	public function catJumpList( $restrict=false, $live='none', $sel=array() )
	{
		if ( !$restrict )
		{
			if( !empty(ipsRegistry::getClass('class_localization')->words['default_root_category']) )
			{
				$jump_array[] = array( '0', ipsRegistry::getClass('class_localization')->words['default_root_category'] );
			}
			else
			{
				$jump_array[] = array( '0', '(Root Category)' );
			}
		}
		else
		{
			$jump_array = array();
		}

		if( count( $this->cat_cache[0] ) > 0 )
		{
			foreach( $this->cat_cache[0] as $id => $cat_data )
			{
				$disabled = "";
				
				if( $live != 'none' )
				{
					if( $cat_data['copen'] == 0 AND !$this->memberData[ 'g_access_cp' ] )
					{
						continue;
					}
					
					if( ! in_array( $cat_data['cid'], $this->member_access[ $live ] ) )
					{
						if( in_array( $cat_data['cid'], $this->member_access[ 'show' ] ) )
						{
							$disabled = " disabled='disabled'";
						}
						else
						{
							continue;
						}
					}

					if( is_array($sel) AND count($sel) )
					{
						if( in_array( $cat_data['cid'], $sel ) && !$disabled )
						{
							$disabled = " selected='selected'";
						}
					}
					else if( $this->request['c'] == $cat_data['cid'] && !$disabled )
					{
						$disabled = " selected='selected'";
					}
				}
					
				$jump_array[] = array( $cat_data['cid'], $cat_data['cname'], $disabled );
			
				$depth_guide = "--";
			
				if ( is_array( $this->cat_cache[ $cat_data['cid'] ] ) )
				{
					foreach( $this->cat_cache[ $cat_data['cid'] ] as $id => $cat_data )
					{
						$disabled = "";
						
						if( $live != 'none' )
						{
							if( $cat_data['copen'] == 0 AND !$this->memberData[ 'g_access_cp' ] )
							{
								continue;
							}
											
							if( ! in_array( $cat_data['cid'], $this->member_access[ $live ] ) )
							{
								if( in_array( $cat_data['cid'], $this->member_access[ 'show' ] ) )
								{
									$disabled = " disabled='disabled'";
								}
								else
								{
									continue;
								}
							}
							
							if( is_array($sel) AND count($sel) )
							{
								if( in_array( $cat_data['cid'], $sel ) && !$disabled)
								{
									$disabled = " selected='selected'";
								}
							}
							else if( $this->request['c'] == $cat_data['cid'] && !$disabled)
							{
								$disabled = " selected='selected'";
							}
						}
						$jump_array[] = array( $cat_data['cid'], $depth_guide.$cat_data['cname'], $disabled );
						$jump_array = $this->_internalCatJumpList( $cat_data['cid'], $jump_array, $depth_guide . "--", $live, $sel );
					}
				}
			}
		}
		
		return $jump_array;
	}
	
	/**
	 * Category dropdown/multi-select list generation
	 * Does not return the select HTML tag, just the options
	 *
	 * @param	integer		Category id to start at
	 * @param 	array		Currently stored entries
	 * @param	string		Depth guide
	 * @param	string		Permission key to check
	 * @param	array 		Selected options
	 * @return	array 		Categories dropdown/multiselect options
	 */
	protected function _internalCatJumpList( $root_id, $jump_array=array(), $depth_guide="", $live='none', $sel=array() )
	{
		if ( is_array( $this->cat_cache[ $root_id ] ) )
		{
			foreach( $this->cat_cache[ $root_id ] as $id => $cat_data )
			{
				$disabled = "";
				
				if( $live != 'none' )
				{
					if( $cat_data['copen'] == 0 AND !$this->memberData[ 'g_access_cp' ] )
					{
						continue;
					}
										
					if( ! in_array( $cat_data['cid'], $this->member_access[ $live ] ) )
					{
						if( in_array( $cat_data['cid'], $this->member_access[ 'show' ] ) )
						{
							$disabled = " disabled='disabled'";
						}
						else
						{
							continue;
						}
					}
					
					if( is_array($sel) AND count($sel) )
					{
						if( in_array( $cat_data['cid'], $sel ) && !$disabled )
						{
							$disabled = " selected='selected'";
						}
					}
					else if( $this->request['c'] == $cat_data['cid'] && !$disabled )
					{
						$disabled = " selected='selected'";
					}					
				}
								
				$jump_array[] = array( $cat_data['cid'], $depth_guide.$cat_data['cname'], $disabled );
				$jump_array = $this->_internalCatJumpList( $cat_data['cid'], $jump_array, $depth_guide . "--", $live, $sel );
			}
		}
		
		return $jump_array;
	}
	
	/**
	 * Retrieve the open categories
	 *
	 * @param	integer		Category id to start from
	 * @return	array 		Open categories
	 */
	public function getOpenCats( $catid=0 )
	{
		$open_cats = array();
		
		if( ! $catid )
		{
			foreach( $this->cat_lookup as $cid => $cinfo )
			{
				if( $cinfo['copen'] == 1 OR $this->memberData[ 'g_access_cp' ] )
				{
					$open_cats[] = $cid;
				}
			}
		}
		else
		{
			foreach( $this->subcat_lookup[$catid] as $blank_key => $cid )
			{
				if( $this->cat_lookup[$cid]['copen'] == 1 OR $this->memberData[ 'g_access_cp' ] )
				{
					$open_cats[] = $cid;
				}
			}
		}
		
		return $open_cats;
	}
	
	/**
	 * Sort out the member's permissions
	 *
	 * @param	integer		Member id to check (defaults to viewing member)
	 * @return	array 		Member permissions
	 */
	public function setMemberPermissions( $memid="" )
	{
		$no_update = 0;
		
		$member_perms = array(	'show' 		=> array(),
								'view' 		=> array(),
								'add'		=> array(),
								'download'	=> array(),
								'rate'		=> array(),
								'comment'	=> array(),
								'auto'		=> array() );

		$member_masks = array();
		
		$open_cats = $this->getOpenCats();

		if( !$memid )
		{
			if( $this->memberData[ 'org_perm_id' ] )
			{
				$member_masks = explode( ",", IPSText::cleanPermString( $this->memberData[ 'org_perm_id' ] ) );
			}
			else
			{
				if( strpos( $this->memberData[ 'g_perm_id' ], "," ) )
				{
					$member_masks = explode( ",", $this->memberData[ 'g_perm_id' ] );
				}
				else
				{
					$member_masks[] = $this->memberData[ 'g_perm_id' ];
				}
			}
		}
		else
		{
			$no_update = 1;
			
			$groups = $this->DB->buildAndFetch( array( 'select'	=> 'member_group_id, org_perm_id, mgroup_others',
															  	'from'	=> 'members',
															  	'where'	=> 'member_id=' . intval($memid),
													)		);

			if( !$groups['org_perm_id'] )
			{
				$checkGroups[] = $groups['member_group_id'];
				
				if( strpos($groups['mgroup_others'], "," ) )
				{
					$checkGroups	= array_merge( $checkGroups, explode( ',', IPSText::cleanPermString( $groups['mgroup_others'] ) ) );
				}
				
				foreach( $this->caches['group_cache'] as $gid => $masks )
				{
					if( in_array( $gid, $checkGroups ) )
					{
						$these_masks = array();

						if( strpos( $masks['g_perm_id'], "," ) )
						{
							$these_masks	= explode( ",", IPSText::cleanPermString( $masks['g_perm_id'] ) );
							$member_masks	= array_merge( $member_masks, $these_masks );
						}
						else
						{
							$member_masks[]	= $masks['g_perm_id'];
						}
					}
				}
			}
			else
			{
				$member_masks = explode( ",", IPSText::cleanPermString( $groups['org_perm_id']) );
			}
		}
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/extensions/coreExtensions.php', 'bitrackerPermMappingCat', 'bitracker' );
		$permissions	= new $classToLoad();
		$mapping		= $permissions->getMapping();
		
		foreach( $this->cat_lookup as $cid => $cinfo )
		{
			if( ! in_array( $cid, $open_cats ) )
			{
				continue;
			}
			
			foreach( $mapping as $k => $v )
			{
				if( $cinfo[ $v ] == '*' )
				{
					$member_perms[ $k ][ $cid ]		= $cid;
				}
				else if( $cinfo[ $v ] )
				{
					$forum_masks = explode( ",", IPSText::cleanPermString( $cinfo[ $v ] ) );
					
					foreach( $forum_masks as $mask_id )
					{
						if( in_array( $mask_id, $member_masks ) )
						{
							$member_perms[ $k ][ $cid ]		= $cid;
							break;
						}
					}
				}
			}
			
			if ( !$cinfo['coptions']['opt_disfiles'] )
			{
				unset( $member_perms['add'][ $cinfo['cid'] ] );
			}
		}
		
		foreach( $member_perms as $k => $v )
		{
			if( is_array( $member_perms[$k] ) )
			{
				$member_perms[$k] = array_unique($member_perms[$k]);
			}
		}
		
		if( !$no_update )
		{
			$this->member_access = $member_perms;

			return $member_perms;
		}
		else
		{
			return $member_perms;
		}
	}

}