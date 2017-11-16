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

class search_engine_bitracker extends search_engine
{
	/**
	 * Categories we have access to
	 * 
	 * @var	array
	 */
 	protected $categories	= array();

	/**
	 * Perform a search.
	 * Returns an array of a total count (total number of matches)
	 * and an array of IDs ( 0 => 1203, 1 => 928, 2 => 2938 ).. matching the required number based on pagination. The ids returned would be based on the filters and type of search
	 *
	 * So if we had 1000 replies, and we are on page 2 of 25 per page, we'd return 25 items offset by 25
	 *
	 * @return array
	 */
	public function search()
	{
		IPSSearchRegistry::set('set.hardLimit', ( ipsRegistry::$settings['search_hardlimit'] ) ? ipsRegistry::$settings['search_hardlimit'] : 200 );

		//-----------------------------------------
		// Get categories
		//-----------------------------------------
		
		if( !count($this->categories) )
		{
			if( $this->registry->isClassLoaded('categories') )
			{
				foreach( $this->registry->categories->member_access['view'] as $id )
				{
					if( in_array( $id, $this->registry->categories->member_access['view'] ) )
					{
						$this->categories[]	= $id;
					}
				}
			}
			else
			{
				$this->DB->build( array( 'select' => 'perm_type_id as category_id', 'from' => 'permission_index', 'where' => "app='bitracker' AND perm_type='cat' AND " . $this->DB->buildRegexp( "perm_view", $this->member->perm_id_array ) ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$this->categories[]	= $r['category_id'];
				}
			}
		}

		//-----------------------------------------
		// Run search
		//-----------------------------------------
		
		if ( IPSSearchRegistry::get('bitracker.searchInKey') == 'files' )
		{
			//-----------------------------------------
			// Tagging
			//-----------------------------------------
			
			if ( ! $this->registry->isClassLoaded('bitrackerTags') )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
				$this->registry->setClass( 'bitrackerTags', classes_tags_bootstrap::run( 'bitracker', 'files' ) );
			}
		
			return $this->_filesSearch();
		}
		else
		{
			return $this->_commentsSearch();
		}
	}
	
	/**
	 * Search files
	 *
	 * @return array
	 */
	public function _filesSearch()
	{
		if( ! $this->request['search_app_filters']['bitracker']['files']['sortKey'] && ipsRegistry::$settings['use_fulltext'] && $this->request['do'] == 'search' && strtolower( $this->DB->connect_vars['mysql_tbl_type'] ) == 'myisam' )
		{
			$sort_by = IPSSearchRegistry::set('in.search_sort_by', 'relevancy');

			$this->request['search_app_filters']['bitracker']['files']['sortKey']	= 'relevancy';
		}

		/* INIT */
		$sort_by		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order		= IPSSearchRegistry::get('in.search_sort_order');
		$search_term	= IPSSearchRegistry::get('in.clean_search_term');
		$search_tags	= IPSSearchRegistry::get('in.raw_search_tags');		
		$sortKey		= '';
		$rows			= array();

		/* Sorting */
		switch( $sort_by )
		{
			default:
			case 'date':
				$sortKey	= 'f.file_submitted';
			break;
			case 'update':
				$sortKey	= 'f.file_updated';
			break;
			case 'title':
				$sortKey	= 'f.file_name';
			break;
			case 'views':
				$sortKey	= 'f.file_views';
			break;
			case 'rating':
				$sortKey	= 'f.file_rating';
			break;
			case 'bitracker':
				$sortKey	= 'f.file_bitracker';
			break;
			case 'relevancy':
				$sortKey	= 'ranking';
			break;
		}

		if( ( $this->request['do'] != 'search' OR !IPSSearchRegistry::get('in.clean_search_term')) AND $sortKey == 'ranking' )
		{
			$sortKey	= 'f.file_submitted';
		}

		$where	= $this->_buildWhereStatement( $search_term, 'files', $search_tags );
		
		/* Query the count */	
		$count = $this->DB->buildAndFetch( array( 'select'		=> 'COUNT(*) as total_results',
												  'from'		=> array( 'bitracker_files' => 'f' ),
 												  'where'		=> $where,
 												  'add_join'	=> array(
 												  						array(
 												  							'from'	=> array( 'bitracker_ccontent' => 'cc' ),
 												  							'where'	=> 'cc.file_id=f.file_id',
 												  							'type'	=> 'left'
																		   )
												   						)
										 )		 );
		
		$rows	= array();
		
		if( $count['total_results'] > 0 )
		{
			$ranking_select = "";
			
			if( ipsRegistry::$settings['use_fulltext'] && $sortKey == 'ranking' AND $search_term )
			{
				if( in_array( IPSSearchRegistry::get('opt.searchType'), array( 'titles', 'both' ) ) )
				{
					$ranking_select = ", " . $this->DB->buildSearchStatement( 'f.file_name', $search_term, true, true, ipsRegistry::$settings['use_fulltext'] );
				}
				else
				{
					$ranking_select = ", " . $this->DB->buildSearchStatement( 'f.file_desc', $search_term, true, true, ipsRegistry::$settings['use_fulltext'] );
				}
			}

			/* Do the search */
			$this->DB->build( array('select'   => "f.*" . $ranking_select,
									'from'	   => array( 'bitracker_files' => 'f' ),
									'where'	   => $where,
									'order'    => $sortKey . ' ' . $sort_order,
									'limit'    => array( IPSSearchRegistry::get('in.start'), IPSSearchRegistry::get('opt.search_per_page') ),
									'add_join' => array(
														array(
															'select' => 'mem.member_id, mem.members_seo_name, mem.members_display_name, mem.member_group_id, mem.mgroup_others',
															'from'   => array( 'members' => 'mem' ),
															'where'  => "mem.member_id=f.file_submitter",
															'type'   => 'left'
															),
								  						array(
								  							'from'	=> array( 'bitracker_ccontent' => 'cc' ),
								  							'where'	=> 'cc.file_id=f.file_id',
								  							'type'	=> 'left'
														   ),
													   $this->registry->bitrackerTags->getCacheJoin( array( 'meta_id_field' => 'f.file_id' ) )
														)
							 )		);
			$this->DB->execute();
			
			/* Sort */
			while( $r = $this->DB->fetch() )
			{
				/* Use the file id as the array index for similar files */
				$rows[ $r['file_id'] ] = $r;
			}
		}

		/* Return it */
		return array( 'count' => $count['total_results'], 'resultSet' => $rows );
	}

	/**
	 * Search comments
	 *
	 * @return array
	 */
	public function _commentsSearch()
	{
		/* INIT */ 
		$sort_by     		= IPSSearchRegistry::get('in.search_sort_by');
		$sort_order         = IPSSearchRegistry::get('in.search_sort_order');
		$search_term        = IPSSearchRegistry::get('in.clean_search_term');
		$rows    			= array();
		$c                  = 0;
		$got     			= 0;
		$sortKey			= 'comment_date';
		$sortType			= '';

		$this->DB->build( array(
								'select'	=> 'c.comment_id, c.comment_date',
								'from'		=> array( 'bitracker_comments' => 'c' ),
 								'where'		=> $this->_buildWhereStatement( $search_term, 'comments' ),
								'limit'		=> array(0, IPSSearchRegistry::get('set.hardLimit') + 1),
								'add_join'	=> array(
									 				array(
														'from'	=> array( 'bitracker_files' => 'f' ),
														'where'	=> 'f.file_id=c.comment_fid',
														'type'	=> 'left'
									 					),
							  						array(
							  							'from'	=> array( 'bitracker_ccontent' => 'cc' ),
							  							'where'	=> 'cc.file_id=f.file_id',
							  							'type'	=> 'left'
													   )
													)													
				)	);
		$this->DB->execute();
		
		/* Fetch count */
		$count = intval( $this->DB->getTotalRows() );
		
		if ( $count > IPSSearchRegistry::get('set.hardLimit') )
		{
			$count = IPSSearchRegistry::get('set.hardLimit');
			
			IPSSearchRegistry::set('set.resultsCutToLimit', true );
		}

		/* Fetch to sort */
		while ( $r = $this->DB->fetch() )
		{
			$_rows[ $r['comment_id'] ] = $r;
		}

		/* Set vars */
		IPSSearch::$ask = $sortKey;
		IPSSearch::$aso = strtolower( $sort_order );
		IPSSearch::$ast = 'numerical';
		
		/* Sort */
		if ( count( $_rows ) )
		{
			usort( $_rows, array("IPSSearch", "usort") );

			/* Build result array */
			foreach( $_rows as $r )
			{
				$c++;

				if ( IPSSearchRegistry::get('in.start') AND IPSSearchRegistry::get('in.start') >= $c )
				{
					continue;
				}

				$rows[ $got ] = $r['comment_id'];
							
				$got++;
				
				/* Done? */
				if ( IPSSearchRegistry::get('opt.search_per_page') AND $got >= IPSSearchRegistry::get('opt.search_per_page') )
				{
					break;
				}
			}
		}

		/* Return it */
		return array( 'count' => $count, 'resultSet' => $rows );
	}
	
	/**
	 * Perform the viewNewContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @access	public
	 * @return	nothin'
	 */
	public function viewNewContent()
	{
		/* Loop through the forums and build a list of forums we're allowed access to */
		$start			= IPSSearchRegistry::get('in.start');
		$perPage		= IPSSearchRegistry::get('opt.search_per_page');
		$seconds		= IPSSearchRegistry::get('in.period_in_seconds');
		$followedOnly	= $this->memberData['member_id'] ? IPSSearchRegistry::get('in.vncFollowFilterOn' ) : false;
		$oldStamp		= $this->registry->getClass('classItemMarking')->fetchOldestUnreadTimestamp( array(), 'bitracker' );
		$check			= IPS_UNIX_TIME_NOW - ( 86400 * $this->settings['topic_marking_keep_days'] );
		
		IPSSearchRegistry::set('in.search_sort_by'   , 'date' );
		IPSSearchRegistry::set('in.search_sort_order', 'desc' );
		IPSSearchRegistry::set('opt.searchType' , 'titles' );
		IPSSearchRegistry::set('opt.noPostPreview'   , true );

		/* Finalize times */
		if ( IPSSearchRegistry::get('in.period_in_seconds') !== false )
		{
			$this->search_begin_timestamp	= intval( IPS_UNIX_TIME_NOW - $seconds );
		}
		else
		{
			if ( intval( $this->memberData['_cache']['gb_mark__bitracker'] ) > $oldStamp )
			{
				$oldStamp = $this->memberData['_cache']['gb_mark__bitracker'];
			}

			/* Finalize times */
			if ( ! $oldStamp OR $oldStamp == IPS_UNIX_TIME_NOW )
			{
				$oldStamp = intval( $this->memberData['last_visit'] );
			}

			/* Older than 3 months.. then limit */
			if ( $oldStamp < $check )
			{
				$oldStamp = $check;
			}

			$fileIds	= $this->registry->getClass('classItemMarking')->fetchReadIds( array(), 'bitracker', true );

			IPSSearchRegistry::set('set.resultCutToDate', $oldStamp );

			$this->search_begin_timestamp	= $oldStamp;
			
			/* Set read tids */
			if ( count( $fileIds ) )
			{
				$this->whereConditions['AND'][] = "f.file_id NOT IN (" . implode( ",", $fileIds ) . ')';
			}
		}

		//-----------------------------------------
		// Only content we are following?
		//-----------------------------------------
		
		if ( $followedOnly )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$like = classes_like::bootstrap( 'bitracker', 'files' );
			
			$followedFiles	= $like->getDataByMemberIdAndArea( $this->memberData['member_id'] );
			$followedFiles = ( $followedFiles === null ) ? array() : array_keys( $followedFiles );
			
			if( !count($followedFiles) )
			{
				return array( 'count' => 0, 'resultSet' => array() );
			}
			else
			{
				$this->whereConditions['AND'][]	= "f.file_id IN(" . implode( ',', $followedFiles ) . ")";
			}
		}

		//-----------------------------------------
		// Only content we have participated in?
		//-----------------------------------------
		
		if( IPSSearchRegistry::get('in.userMode') AND IPSSearchRegistry::get('bitracker.searchInKey') == 'files' )
		{
			switch( IPSSearchRegistry::get('in.userMode') )
			{
				default:
				case 'all': 
					$_fileIds	= $this->_getFileIdsFromComments();
					
					if( count($_fileIds) )
					{
						$this->whereConditions['AND'][]	= "(f.file_submitter=" . $this->memberData['member_id'] . " OR f.file_id IN(" . implode( ',', $_fileIds ) . "))";
					}
					else
					{
						$this->whereConditions['AND'][]	= "f.file_submitter=" . $this->memberData['member_id'];
					}
				break;
				case 'title': 
					$this->whereConditions['AND'][]	= "f.file_submitter=" . $this->memberData['member_id'];
				break;
			}
		}
		
		return $this->search();
	}

	/**
	 * Find files we have commented on
	 *
	 * @return	array
	 */
	protected function _getFileIdsFromComments()
	{
		$ids	= array();
		
		$this->DB->build( array(
								'select'	=> $this->DB->buildDistinct('comment_fid'),
								'from'		=> 'bitracker_comments',
								'where'		=> 'comment_open=1 AND comment_mid=' . $this->memberData['member_id'],
								'limit'		=> array( 0, 200 )
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ids[]	= $r['comment_fid'];
		}
		
		return $ids;
	}

	/**
	 * Perform the viewUserContent search
	 * Populates $this->_count and $this->_results
	 *
	 * @return	array
	 */
	public function viewUserContent( $member )
	{
		/* Search by author */
		if ( IPSSearchRegistry::get('bitracker.searchInKey') == 'files' )
		{
			$this->whereConditions['AND'][]	= "f.file_submitter=" . intval( $member['member_id'] );
		}
		else
		{
			$this->whereConditions['AND'][]	= "c.comment_mid=" . intval( $member['member_id'] );
		}

		return $this->search();
	}
		
	/**
	 * Builds the where portion of a search string
	 *
	 * @param	string	$search_term		The string to use in the search
	 * @param	string	$type				'files' or 'comments'
	 * @param	mixed	$search_tags		Whether to search tags or not
	 * @return	string
	 */
	protected function _buildWhereStatement( $search_term, $type='files', $search_tags=null )
	{		
		/* INI */
		$where_clause = array();
				
		if( $search_term )
		{
			$search_term = str_replace( '&quot;', '"', $search_term );
			
			if( $type == 'files' )
			{
				switch( IPSSearchRegistry::get('opt.searchType') )
				{
					case 'both':
					default:
						$where_clause[] = '(' . $this->DB->buildSearchStatement( 'f.file_name', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] ) . ' OR ' . $this->DB->buildSearchStatement( 'f.file_desc', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] ) . ')';
					break;
					
					case 'titles':
						$where_clause[] = $this->DB->buildSearchStatement( 'f.file_name', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
					break;
					
					case 'content':
						$where_clause[] = $this->DB->buildSearchStatement( 'f.file_desc', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
					break;
				}
			}
			else
			{
				$where_clause[] = $this->DB->buildSearchStatement( 'c.comment_text', $search_term, true, false, ipsRegistry::$settings['use_fulltext'] );
			}
		}
		
		/* Searching tags? */
		if ( $search_tags )
		{
			$_tagIds = array();
			
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$tags	= $this->registry->bitrackerTags->search( $search_tags, array( 'meta_app' => 'bitracker', 'meta_area' => 'files' ) );
			
			if( is_array($tags) AND count($tags) )
			{
				foreach( $tags as $id => $data )
				{
					$_tagIds[] = $data['tag_meta_id'];
				}
			}
			
			//$where_clause[] = $this->DB->buildWherePermission( $_tagIds, 'f.file_id', FALSE );
			if( count($_tagIds) )
			{
				$where_clause[] = 'f.file_id IN(' . implode( ',', $_tagIds ) . ')';
			}
			else
			{
				$where_clause[] = 'f.file_id=0';
			}
		}		
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			if( $type == 'files' )
			{
				$where_clause[] = $this->DB->buildBetween( "f.file_updated", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
			else
			{
				$where_clause[] = $this->DB->buildBetween( "c.comment_date", $this->search_begin_timestamp, $this->search_end_timestamp );
			}
		}
		else
		{
			if( $type == 'files' )
			{
				if( $this->search_begin_timestamp )
				{
					$where_clause[] = "f.file_updated > {$this->search_begin_timestamp}";
				}
				
				if( $this->search_end_timestamp )
				{
					$where_clause[] = "f.file_updated < {$this->search_end_timestamp}";
				}
			}
			else
			{
				if( $this->search_begin_timestamp )
				{
					$where_clause[] = "c.comment_date > {$this->search_begin_timestamp}";
				}
				
				if( $this->search_end_timestamp )
				{
					$where_clause[] = "c.comment_date < {$this->search_end_timestamp}";
				}
			}
		}
		
		/* What categories do we have access to? */
		if( count($this->categories) )
		{
			$categories	= array();
			
			foreach( $this->categories as $category )
			{
				$categories[ $category ]	= $category;
			}
		}
		else
		{
			$categories	= array( 0 );
		}
		
		/* We have categories? We aren't checking permissions here because the i.perm_view filter covers that already */

		if ( ! empty( ipsRegistry::$request['search_app_filters']['bitracker']['bitracker'] ) AND count( ipsRegistry::$request['search_app_filters']['bitracker']['bitracker'] ) )
		{
			foreach( $categories as $cat )
			{
				if( !in_array( $cat, ipsRegistry::$request['search_app_filters']['bitracker']['bitracker'] ) )
				{
					unset( $categories[ $cat ] );
				}
			}
		}

		if( count($categories) )
		{
			$where_clause[]	= "f.file_cat IN(" . implode( ',', $categories ) . ")";
		}
		else
		{
			$where_clause[]	= "f.file_cat=0";
		}
		
		/* Filtering by paid or free? */
		if ( ! empty( ipsRegistry::$request['search_app_filters']['bitracker']['freepaid'] ) )
		{
			switch( ipsRegistry::$request['search_app_filters']['bitracker']['freepaid'] )
			{
				case 'free':
					$where_clause[]	= "f.file_cost=0";
				break;
				
				case 'paid':
					$where_clause[]	= "f.file_cost > 0";
				break;
			}
		}
		
		/* Custom fields? */
		if( is_array($this->request['search_app_filters']['bitracker']) AND count($this->request['search_app_filters']['bitracker']) )
		{
			$cfields	= $this->cache->getCache('bit_cfields');
			
			foreach( $this->request['search_app_filters']['bitracker'] as $k => $v )
			{
				if( $v AND preg_match( "/^field_(\d+)$/", $k, $matches ) )
				{
					if( $cfields[ $matches[1] ]['cf_search'] )
					{
						$where_clause[]	= "cc.field_{$matches[1]} LIKE '%{$v}%'";
					}
				}
			}
		}

		/* Add in AND where conditions */
		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause = array_merge( $where_clause, $this->whereConditions['AND'] );
		}
		
		/* ADD in OR where conditions */
		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[] = '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}

		/* Permissions */
		if( !$this->memberData['g_is_supmod'] )
		{
			$where_clause[]	= "f.file_open=1";
		}
		
		/* Exclude an individual file?  Useful for similar content */
		if( IPSSearchRegistry::get('bitracker.excludeFileId') )
		{
			$where_clause[]	= "f.file_id<>" . intval(IPSSearchRegistry::get('bitracker.excludeFileId'));
		}
	
		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}	
	
	/**
	 * Remap standard columns (Apps can override )
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @return	string				column
	 * @return	@e void
	 */
	public function remapColumn( $column )
	{
		if ( IPSSearchRegistry::get('bitracker.searchInKey') == 'files' )
		{
			$column = $column == 'member_id' ? 'f.file_submitter' : $column;
		}
		else
		{
			$column = $column == 'member_id' ? 'c.comment_mid' : $column;
		}

		return $column;
	}
		
	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 */
	public function buildFilterSQL( $data )
	{
		return array();
	}

	/**
	 * Can handle boolean searching
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function isBoolean()
	{
		return true;
	}
}