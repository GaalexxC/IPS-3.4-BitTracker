<?php
/**
 *  devCU Software Development
 *  devCU Bitracker 1.0.0 Release
 *  Last Updated: $Date: 2013-07-11 09:01:45 -0500 (Thurs, 11 July 2013) $
 *
 * @author 		PM
 * @copyright	(c) 2012 devCU Software Development
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

class feed_bitracker implements feedBlockInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $registry;
	protected $caches;
	protected $request;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @param	string		Additional info (database id;type)
	 * @return	array
	 */
	public function getTags( $info='' )
	{
		$_return			= array();
		$_noinfoColumns		= array();

		//-----------------------------------------
		// Switch on type
		//-----------------------------------------
		
		switch( $info )
		{
			case 'files':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__bitfileurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__bitfiledate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__bitfiletitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__bitfilecontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'bitracker_files' ) as $_column )
				{
					if( $this->lang->words['col__bitracker_files_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__bitracker_files_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'bitracker_categories' ) as $_column )
				{
					if( $this->lang->words['col__bitracker_categories_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__bitracker_categories_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(	
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_bitfiles']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__bitfiles'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'cats':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__bitcaturl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__bitcatdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__bitcattitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__bitcatcontent'] ),
											);

				foreach( $this->DB->getFieldNames( 'bitracker_categories' ) as $_column )
				{
					if( $this->lang->words['col__bitracker_categories_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__bitracker_categories_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$_finalColumns['cinfo_opt_catss']		= array( "&#36;r['coptions']['opt_catss']", $this->lang->words['col__bitcat_coptions_opt_catss'] );
				$_finalColumns['cinfo_opt_filess']		= array( "&#36;r['coptions']['opt_filess']", $this->lang->words['col__bitcat_coptions_opt_filess'] );
				$_finalColumns['cinfo_opt_comments']	= array( "&#36;r['coptions']['opt_comments']", $this->lang->words['col__bitcat_coptions_opt_comments'] );
				$_finalColumns['cinfo_opt_allowss']		= array( "&#36;r['coptions']['opt_allowss']", $this->lang->words['col__bitcat_coptions_opt_allowss'] );
				$_finalColumns['cinfo_opt_reqss']		= array( "&#36;r['coptions']['opt_reqss']", $this->lang->words['col__bitcat_coptions_opt_reqss'] );
				$_finalColumns['cinfo_opt_disfiles']	= array( "&#36;r['coptions']['opt_disfiles']", $this->lang->words['col__bitcat_coptions_opt_disfiles'] );
				
				$_finalColumns['cinfo_total_views']		= array( "&#36;r['cfileinfo']['total_views']", $this->lang->words['col__bitcat_coptions_total_views'] );
				$_finalColumns['cinfo_total_files']		= array( "&#36;r['cfileinfo']['total_files']", $this->lang->words['col__bitcat_coptions_total_files'] );
				$_finalColumns['cinfo_total_bitracker']	= array( "&#36;r['cfileinfo']['total_bitracker']", $this->lang->words['col__bitcat_coptions_total_bitracker'] );
				$_finalColumns['cinfo_date']			= array( "&#36;r['cfileinfo']['date']", $this->lang->words['col__bitcat_coptions_date'] );
				$_finalColumns['cinfo_pending_files']	= array( "&#36;r['cfileinfo']['pending_files']", $this->lang->words['col__bitcat_coptions_pending_files'] );
				$_finalColumns['cinfo_broken_files']	= array( "&#36;r['cfileinfo']['broken_files']", $this->lang->words['col__bitcat_coptions_broken_files'] );
				$_finalColumns['cinfo_mid']				= array( "&#36;r['cfileinfo']['mid']", $this->lang->words['col__bitcat_coptions_mid'] );
				$_finalColumns['cinfo_fid']				= array( "&#36;r['cfileinfo']['fid']", $this->lang->words['col__bitcat_coptions_fid'] );
				$_finalColumns['cinfo_fname']			= array( "&#36;r['cfileinfo']['fname']", $this->lang->words['col__bitcat_coptions_fname'] );
				$_finalColumns['cinfo_fname_furl']		= array( "&#36;r['cfileinfo']['fname_furl']", $this->lang->words['col__bitcat_coptions_fname_furl'] );
				$_finalColumns['cinfo_mname']			= array( "&#36;r['cfileinfo']['mname']", $this->lang->words['col__bitcat_coptions_mname'] );
				$_finalColumns['cinfo_seoname']			= array( "&#36;r['cfileinfo']['seoname']", $this->lang->words['col__bitcat_coptions_seoname'] );
				$_finalColumns['cinfo_updated']			= array( "&#36;r['cfileinfo']['updated']", $this->lang->words['col__bitcat_coptions_updated'] );

				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_bitcats']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__bitcats'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
			
			case 'comments':
				$_finalColumns		= array(
											'url'		=> array( "&#36;r['url']", $this->lang->words['block_feed__bitfilecurl'] ),
											'date'		=> array( "&#36;r['date']", $this->lang->words['block_feed__bitfilecdate'] ),
											'title'		=> array( "&#36;r['title']", $this->lang->words['block_feed__bitfiletitle'] ),
											'content'	=> array( "&#36;r['content']", $this->lang->words['block_feed__bitfileccontent'] ),
											);
				
				foreach( $this->DB->getFieldNames( 'bitracker_comments' ) as $_column )
				{
					if( $this->lang->words['col__bitracker_comments_' . $_column ] )
					{
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__bitracker_comments_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}

				foreach( $this->DB->getFieldNames( 'bitracker_files' ) as $_column )
				{
					if( $this->lang->words['col__bitracker_files_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__bitracker_files_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				foreach( $this->DB->getFieldNames( 'bitracker_categories' ) as $_column )
				{
					if( $this->lang->words['col__bitracker_categories_' . $_column ] )
					{
						unset($_finalColumns[ $_column ]);
						$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__bitracker_categories_' . $_column ] );
					}
					else
					{
						$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
					}
				}
				
				$this->_addMemberColumns( $_finalColumns, $_noinfoColumns );
		
				$_return	= array(
							$this->lang->words['block_feed__generic']	=> array( 
																				array( '&#36;title', $this->lang->words['block_feed__title'] ) ,
																				),	
								
							$this->lang->words['block_feed_bitcomments']	=> array(
																				array( '&#36;records', $this->lang->words['block_feed__bitcomments'], IPSLib::mergeArrays( $_finalColumns, $_noinfoColumns ) ),
																				),
							);
			break;
		}

		return $_return;
	}
	
	/**
	 * Appends member columns to existing arrays
	 *
	 * @access	protected
	 * @param	array 		Columns that have descriptions
	 * @param	array 		Columns that do not have descriptions
	 * @return	@e void		[Params are passed by reference and modified]
	 */
	protected function _addMemberColumns( &$_finalColumns, &$_noinfoColumns )
	{
		foreach( $this->DB->getFieldNames( 'sessions' ) as $_column )
		{
			if( $this->lang->words['col__sessions_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__sessions_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}

		foreach( $this->DB->getFieldNames( 'members' ) as $_column )
		{
			if( $this->lang->words['col__members_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__members_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		$_fieldInfo	= array();
		
		$this->DB->buildAndFetch( array( 'select' => 'pf_id,pf_title,pf_desc', 'from' => 'pfields_data' ) );
		$this->DB->execute();
		
		while( $r= $this->DB->fetch() )
		{
			$_fieldInfo[ $r['pf_id'] ]	= $r;
		}

		foreach( $this->DB->getFieldNames( 'pfields_content' ) as $_column )
		{
			if( $this->lang->words['col__pfields_content_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__pfields_content_' . $_column ] );
			}
			else if( preg_match( "/^field_(\d+)$/", $_column, $matches ) AND isset( $_fieldInfo[ $matches[1] ] ) )
			{
				unset($_finalColumns[ $_column ]);
				$_column					= str_replace( 'field_', 'user_field_', $_column );
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $_fieldInfo[ $matches[1] ]['pf_title'] . ( $_fieldInfo[ $matches[1] ]['pf_desc'] ? ': ' . $_fieldInfo[ $matches[1] ]['pf_desc'] : '' ) );
			}
			else
			{
				$_column					= str_replace( 'field_', 'user_field_', $_column );
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}
		
		foreach( $this->DB->getFieldNames( 'profile_portal' ) as $_column )
		{
			if( $this->lang->words['col__profile_portal_' . $_column ] )
			{
				unset($_finalColumns[ $_column ]);
				$_finalColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['col__profile_portal_' . $_column ] );
			}
			else
			{
				$_noinfoColumns[ $_column ]	= array( "&#36;r['" . $_column . "']", $this->lang->words['notaghelpinfoavailable'], true );
			}
		}

		$_finalColumns['pp_main_photo']		= array( "&#36;r['pp_main_photo']", $this->lang->words['col__special_pp_main_photo'] );
		$_finalColumns['_has_photo']		= array( "&#36;r['_has_photo']", $this->lang->words['col__special__has_photo'] );
		$_finalColumns['pp_small_photo']	= array( "&#36;r['pp_small_photo']", $this->lang->words['col__special_pp_small_photo'] );
		$_finalColumns['pp_mini_photo']		= array( "&#36;r['pp_mini_photo']", $this->lang->words['col__special_pp_mini_photo'] );
		$_finalColumns['member_rank_img_i']	= array( "&#36;r['member_rank_img_i']", $this->lang->words['col__special_member_rank_img_i'] );
		$_finalColumns['member_rank_img']	= array( "&#36;r['member_rank_img']", $this->lang->words['col__special_member_rank_img'] );
	}
	
	/**
	 * Provides the ability to modify the feed type or content type values
	 * before they are passed into the gallery template search query
	 *
	 * @access 	public
	 * @param 	string 		Current feed type 
	 * @param 	string 		Current content type
	 * @return 	array 		Array with two keys: feed_type and content_type
	 */
	public function returnTemplateGalleryKeys( $feed_type, $content_type )
	{
		return array( 'feed_type' => $feed_type, 'content_type' => $content_type );
	}

	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (key (folder name), associated app, name, description, hasFilters, templateBit)
	 */
	public function returnFeedInfo()
	{
		return array(
					'key'			=> 'bitracker',
					'app'			=> 'bitracker',
					'name'			=> $this->lang->words['feed_name__bitracker'],
					'description'	=> $this->lang->words['feed_description__bitracker'],
					'hasFilters'	=> true,
					'templateBit'	=> 'feed__generic',
					'inactiveSteps'	=> array( ),
					);
	}
	
	/**
	 * Get the feed's available content types.  Returns form elements and data
	 *
	 * @param	array 			Session data
	 * @param	array 			true: Return an HTML radio list; false: return an array of types
	 * @return	array 			Form data
	 */
	public function returnContentTypes( $session = array(), $asHTML = true )
	{
		$_types		= array(
							array( 'files', $this->lang->words['ct_bit_files'] ),
							array( 'comments', $this->lang->words['ct_bit_comments'] ),
							array( 'cats', $this->lang->words['ct_bit_cats'] ),
							);
		$_html		= array();
		
		if( !$asHTML )
		{
			return $_types;
		}
		
		foreach( $_types as $_type )
		{
			$_html[]	= "<input type='radio' name='content_type' id='content_type_{$_type[0]}' value='{$_type[0]}'" . ( $session['config_data']['content_type'] == $_type[0] ? " checked='checked'" : '' ) . " /> <label for='content_type_{$_type[0]}'>{$_type[1]}</label>"; 
		}
		
		return array(
					array(
						'label'			=> $this->lang->words['generic__select_contenttype'],
						'description'	=> '',
						'field'			=> '<ul style="line-height: 1.6"><li>' . implode( '</li><li>', $_html ) . '</ul>',
						)
					);
	}
	
	/**
	 * Check the feed content type selection
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedContentTypes( $data )
	{
		if( !in_array( $data['content_type'], array( 'cats', 'files', 'comments' ) ) )
		{
			$data['content_type']	= 'files';
		}

		return array( true, $data['content_type'] );
	}
	
	/**
	 * Get the feed's available filter options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnFilters( $session )
	{
		$filters	= array();
		
		//-----------------------------------------
		// For all the content types, we allow to filter by forums
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'bitracker' );
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_bit__cats'],
							'description'	=> $this->lang->words['feed_bit__cats_desc'],
							'field'			=> $this->registry->output->formMultiDropdown( 'filter_cats[]', $this->registry->categories->catJumpList( true ), explode( ',', $session['config_data']['filters']['filter_cats'] ), 10 ),
							);

		switch( $session['config_data']['content_type'] )
		{
			case 'files':
			default:
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_broken']		= $session['config_data']['filters']['filter_broken'] ? $session['config_data']['filters']['filter_broken'] : 'either';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';
				$session['config_data']['filters']['filter_submitter']	= $session['config_data']['filters']['filter_submitter'] ? $session['config_data']['filters']['filter_submitter'] : '';
				$session['config_data']['filters']['filter_featured']	= $session['config_data']['filters']['filter_featured'] ? $session['config_data']['filters']['filter_featured'] : 0;
				$session['config_data']['filters']['filter_pinned']		= $session['config_data']['filters']['filter_pinned'] ? $session['config_data']['filters']['filter_pinned'] : 0;
				$session['config_data']['filters']['filter_paid']		= $session['config_data']['filters']['filter_paid'] ? $session['config_data']['filters']['filter_paid'] : 'either';
				
				$visibility	= array( array( 'open', $this->lang->words['bit_status__open'] ), array( 'closed', $this->lang->words['bit_status__closed'] ), array( 'either', $this->lang->words['bit_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__visibility'],
									'description'	=> $this->lang->words['feed_bit__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);
									
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__featured'],
									'description'	=> $this->lang->words['feed_bit__featured_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_featured', $session['config_data']['filters']['filter_featured'] ),
									);
									
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__pinned'],
									'description'	=> $this->lang->words['feed_bit__pinned_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_pinned', $session['config_data']['filters']['filter_pinned'] ),
									);

				$broken		= array( array( 'broken', $this->lang->words['broken__yes'] ), array( 'unbroken', $this->lang->words['broken__no'] ), array( 'either', $this->lang->words['broken__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__broken'],
									'description'	=> $this->lang->words['feed_bit__broken_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_broken', $broken, $session['config_data']['filters']['filter_broken'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__posted'],
									'description'	=> $this->lang->words['feed_bit__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__submitter'],
									'description'	=> $this->lang->words['feed_bit__submitter_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitter', $session['config_data']['filters']['filter_submitter'] ),
									);

				if( IPSLib::appIsInstalled('nexus') )
				{
					$paid		= array( array( 'paid', $this->lang->words['paid__yes'] ), array( 'free', $this->lang->words['paid__no'] ), array( 'either', $this->lang->words['paid__either'] ) );
					$filters[]	= array(
										'label'			=> $this->lang->words['feed_bit__paid'],
										'description'	=> $this->lang->words['feed_bit__paid_desc'],
										'field'			=> $this->registry->output->formDropdown( 'filter_paid', $paid, $session['config_data']['filters']['filter_paid'] ),
										);
				}

			break;
			
			case 'comments':
				$session['config_data']['filters']['filter_visibility']	= $session['config_data']['filters']['filter_visibility'] ? $session['config_data']['filters']['filter_visibility'] : 'open';
				$session['config_data']['filters']['filter_submitted']	= $session['config_data']['filters']['filter_submitted'] ? $session['config_data']['filters']['filter_submitted'] : '';

				$visibility	= array( array( 'open', $this->lang->words['bitc_status__open'] ), array( 'closed', $this->lang->words['bitc_status__closed'] ), array( 'either', $this->lang->words['bitc_status__either'] ) );
				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bitc__visibility'],
									'description'	=> $this->lang->words['feed_bitc__visibility_desc'],
									'field'			=> $this->registry->output->formDropdown( 'filter_visibility', $visibility, $session['config_data']['filters']['filter_visibility'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bitc__posted'],
									'description'	=> $this->lang->words['feed_bitc__posted_desc'],
									'field'			=> $this->registry->output->formInput( 'filter_submitted', $session['config_data']['filters']['filter_submitted'] ),
									);
			break;
			
			case 'cats':
				$session['config_data']['filters']['filter_root']	= $session['config_data']['filters']['filter_root'] ? $session['config_data']['filters']['filter_root'] : 1;

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__root'],
									'description'	=> $this->lang->words['feed_bit__root_desc'],
									'field'			=> $this->registry->output->formYesNo( 'filter_root', $session['config_data']['filters']['filter_root'] ),
									);
			break;
		}
		
		return $filters;
	}
	
	/**
	 * Check the feed filters selection
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Content type data to use )
	 */
	public function checkFeedFilters( $session, $data )
	{
		$filters	= array();
		
		$filters['filter_cats']	= is_array($data['filter_cats']) ? implode( ',', $data['filter_cats'] ) : '';

		switch( $session['config_data']['content_type'] )
		{
			case 'files':
			default:
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_broken']		= $data['filter_broken'] ? $data['filter_broken'] : 'either';
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
				$filters['filter_submitter']	= $data['filter_submitter'] ? $data['filter_submitter'] : '';
				$filters['filter_featured']		= $data['filter_featured'] ? $data['filter_featured'] : 0;
				$filters['filter_pinned']		= $data['filter_pinned'] ? $data['filter_pinned'] : 0;
				$filters['filter_paid']			= $data['filter_paid'] ? $data['filter_paid'] : 'either';
			break;
			
			case 'comments':
				$filters['filter_visibility']	= $data['filter_visibility'] ? $data['filter_visibility'] : 'open';
				$filters['filter_submitted']	= $data['filter_submitted'] ? $data['filter_submitted'] : '';
			break;
			
			case 'cats':
				$filters['filter_root']			= $data['filter_root'] ? $data['filter_root'] : 1;
			break;
		}
		
		return array( true, $filters );
	}
	
	/**
	 * Get the feed's available ordering options.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnOrdering( $session )
	{
		$session['config_data']['sortorder']	= $session['config_data']['sortorder'] ? $session['config_data']['sortorder'] : 'desc';
		$session['config_data']['offset_start']	= $session['config_data']['offset_start'] ? $session['config_data']['offset_start'] : 0;
		$session['config_data']['offset_end']	= $session['config_data']['offset_end'] ? $session['config_data']['offset_end'] : 10;

		$filters	= array();

		switch( $session['config_data']['content_type'] )
		{
			case 'files':
			default:
				$session['config_data']['sortby']					= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'submitted';
				$session['config_data']['filters']['sortby_pinned']	= $session['config_data']['filters']['sortby_pinned'] ? $session['config_data']['filters']['sortby_pinned'] : 0;

				$sortby	= array( 
								array( 'title', $this->lang->words['sort_bit__title'] ), 
								array( 'views', $this->lang->words['sort_bit__views'] ), 
								array( 'submitted', $this->lang->words['sort_bit__submitted'] ),
								array( 'updated', $this->lang->words['sort_bit__updated'] ),
								array( 'bitracker', $this->lang->words['sort_bit__bitracker'] ),
								array( 'size', $this->lang->words['sort_bit__size'] ),
								array( 'rate', $this->lang->words['sort_bit__rate'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_bit__sort_pinned'],
									'description'	=> $this->lang->words['feed_bit__sort_pinned_desc'],
									'field'			=> $this->registry->output->formYesNo( 'sortby_pinned', $session['config_data']['filters']['sortby_pinned'] ),
									);
			break;
			
			case 'comments':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'post_date';

				$sortby	= array( 
								array( 'post_date', $this->lang->words['sort_bitc__postdate'] ), 
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
			
			case 'cats':
				$session['config_data']['sortby']	= $session['config_data']['sortby'] ? $session['config_data']['sortby'] : 'position';

				$sortby	= array( 
								array( 'name', $this->lang->words['sort_bitcat__name'] ), 
								array( 'files', $this->lang->words['sort_bitcat__files'] ), 
								array( 'last_file', $this->lang->words['sort_bitcat__lastdate'] ),
								array( 'position', $this->lang->words['sort_bitcat__position'] ),
								array( 'rand', $this->lang->words['sort_generic__rand'] )
								);

				$filters[]	= array(
									'label'			=> $this->lang->words['feed_sort_by'],
									'description'	=> $this->lang->words['feed_sort_by_desc'],
									'field'			=> $this->registry->output->formDropdown( 'sortby', $sortby, $session['config_data']['sortby'] ),
									);
			break;
		}
		
		$filters[]	= array(
							'label'			=> $this->lang->words['feed_order_direction'],
							'description'	=> $this->lang->words['feed_order_direction_desc'],
							'field'			=> $this->registry->output->formDropdown( 'sortorder', array( array( 'desc', 'DESC' ), array( 'asc', 'ASC' ) ), $session['config_data']['sortorder'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_start'],
							'description'	=> $this->lang->words['feed_limit_offset_start_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_start', $session['config_data']['offset_start'] ),
							);

		$filters[]	= array(
							'label'			=> $this->lang->words['feed_limit_offset_end'],
							'description'	=> $this->lang->words['feed_limit_offset_end_desc'],
							'field'			=> $this->registry->output->formInput( 'offset_end', $session['config_data']['offset_end'] ),
							);
		
		return $filters;
	}
	
	/**
	 * Check the feed ordering options
	 *
	 * @access	public
	 * @param	array 			Submitted data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Ordering data to use )
	 */
	public function checkFeedOrdering( $data, $session )
	{
		$limits		= array();
		
		$limits['sortorder']		= in_array( $data['sortorder'], array( 'desc', 'asc' ) ) ? $data['sortorder'] : 'desc';
		$limits['offset_start']		= intval($data['offset_start']);
		$limits['offset_end']		= intval($data['offset_end']);

		switch( $session['config_data']['content_type'] )
		{
			case 'files':
			default:
				$sortby	= array( 'title', 'views', 'submitted', 'updated', 'bitracker', 'size', 'rate', 'rand' );
				$limits['sortby']			= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'submitted';
				
				$limits['filters']['sortby_pinned']	= $data['sortby_pinned'] ? $data['sortby_pinned'] : 0;
			break;
			
			case 'comments':
				$sortby					= array( 'post_date' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'post_date';
			break;
			
			case 'cats':
				$sortby					= array( 'name', 'last_file', 'files', 'position', 'rand' );
				$limits['sortby']		= in_array( $data['sortby'], $sortby ) ? $data['sortby'] : 'position';
			break;
		}

		return array( true, $limits );
	}
	
	/**
	 * Execute the feed and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	bool		Preview mode
	 * @return	string		Block HTML to display or cache
	 */
	public function executeFeed( $block, $previewMode=false )
	{
		$this->lang->loadLanguageFile( array( 'public_ccs' ), 'ccs' );
		
		$config	= unserialize( $block['block_config'] );
		$where	= array();

		//-----------------------------------------
		// Set up filtering clauses
		//-----------------------------------------

		if( $config['filters']['filter_cats'] )
		{
			if( $config['content'] != 'cats' )
			{
				$where[]	= "f.file_cat IN(" . $config['filters']['filter_cats'] . ")";
			}
		}

		switch( $config['content'] )
		{
			case 'files':
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "f.file_open=" . ( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}

				if( $config['filters']['filter_broken'] != 'either' )
				{
					$where[]	= "f.file_broken=" . ( $config['filters']['filter_broken'] == 'broken' ? 1 : 0 );
				}

				if( IPSLib::appIsInstalled('nexus') AND $config['filters']['filter_paid'] != 'either' )
				{
					if( $config['filters']['filter_paid'] == 'paid' )
					{
						$where[]	= "( f.file_cost > 0 OR (f.file_nexus != 0 AND f.file_nexus != '' AND f.file_nexus " . $this->DB->buildIsNull( false ) . ") )";
					}
					else
					{
						$where[]	= "( f.file_cost=0 AND (f.file_nexus = 0 OR f.file_nexus = '' OR f.file_nexus " . $this->DB->buildIsNull( true ) . ") )";
					}
				}
				
				if( $config['filters']['filter_featured'] )
				{
					$where[]	= "f.file_featured=1";
				}
				
				if( $config['filters']['filter_pinned'] )
				{
					$where[]	= "f.file_pinned=1";
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "f.file_submitted > " . $timestamp;
					}
				}
				
				if( $config['filters']['filter_submitter'] == 'myself' )
				{
					$where[]	= "f.file_submitter = " . $this->memberData['member_id'];
				}
				else if( $config['filters']['filter_submitter'] == 'friends' )
				{
					//-----------------------------------------
					// Get page builder for friends
					//-----------------------------------------
					
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php', 'pageBuilder', 'ccs' );
					$pageBuilder	= new $classToLoad( $this->registry );
					$friends		= $pageBuilder->getFriends();
					
					if( count($friends) )
					{
						$where[]	= "f.file_submitter IN( " . implode( ',', $friends ) . ")";
					}
					else
					{
						return '';
					}
				}
				else if( $config['filters']['filter_submitter'] == '@request' )
				{
					$where[]	= "f.file_submitter = " . intval($this->request['author']);
				}
				else if( $config['filters']['filter_submitter'] != '' )
				{
					
					$member	= IPSMember::load( $config['filters']['filter_submitter'], 'basic', 'displayname' );
					
					if( $member['member_id'] )
					{
						$where[]	= "f.file_submitter = " . $member['member_id'];
					}
					else
					{
						return '';
					}
				}
			break;
			
			case 'comments':
				if( $config['filters']['filter_visibility'] != 'either' )
				{
					$where[]	= "c.comment_open=" . ( $config['filters']['filter_visibility'] == 'open' ? 1 : 0 );
				}

				if( $config['filters']['filter_submitted'] )
				{
					$timestamp	= @strtotime( $config['filters']['filter_submitted'] );
					
					if( $timestamp )
					{
						$where[]	= "c.comment_date > " . $timestamp;
					}
				}
			break;
		}

		$order	= '';

		switch( $config['content'] )
		{
			case 'files':
				switch( $config['sortby'] )
				{
					case 'title':
						$order	.=	"f.file_name ";
					break;
		
					case 'views':
						$order	.=	"f.file_views ";
					break;
					
					default:
					case 'submitted':
						$order	.=	"f.file_submitted ";
					break;
		
					case 'updated':
						$where[]	= "f.file_updated > 0 ";
						$order		.=	"f.file_updated ";
					break;

					case 'bitracker':
						$order	.=	"f.file_bitracker ";
					break;

					case 'size':
						$order	.=	"f.file_size ";
					break;

					case 'rate':
						$order	.=	"f.file_rating ";
					break;

					case 'rand':
						$order	.=	$this->DB->buildRandomOrder() . ' ';
					break;
				}
				
				if( $config['filters']['sortby_pinned'] )
				{
					$order	= "f.file_pinned DESC, " . $order;
				}
			break;
			
			case 'comments':
				switch( $config['sortby'] )
				{
					default:
					case 'post_date':
						$order	.=	"c.comment_date ";
					break;
				}
			break;
		}

		$order	.= $config['sortorder'];

		//-----------------------------------------
		// Run the query and get the results
		//-----------------------------------------
		
		$content	= array();

		switch( $config['content'] )
		{
			case 'files':
				$this->DB->build( array(
										'select'	=> 'f.*',
										'from'		=> array( 'bitracker_files' => 'f' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'c.*',
																'from'		=> array( 'bitracker_categories' => 'c' ),
																'where'		=> 'c.cid=f.file_cat',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=f.file_submitter',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					if( !$r['mid'] )
					{
						$r			= array_merge( $r, IPSMember::setUpGuest() );
					}

					$r['member_id']	= $r['mid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=bitracker&amp;showfile=' . $r['file_id'], 'none', $r['file_name_furl'] ? $r['file_name_furl'] : IPSText::makeSeoTitle( $r['file_name'] ), 'bitshowfile' );
					$r['date']		= $r['file_submitted'];
					$r['content']	= $r['file_desc'];
					$r['title']		= $r['file_name'];
					
					$coptions	= unserialize($r['coptions']);
					IPSText::getTextClass( 'bbcode' )->parse_html				= $coptions['opt_html'];
					IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $coptions['opt_bbcode'];
					IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section			= 'bit_submit';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
			
					$r['content']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['content'] );
					
					$r				= IPSMember::buildDisplayData( $r );
					
					$content[]		= $r;
				}
			break;
			
			case 'comments':
				$this->DB->build( array(
										'select'	=> 'c.*',
										'from'		=> array( 'bitracker_comments' => 'c' ),
										'where'		=> implode( ' AND ', $where ),
										'order'		=> $order,
										'limit'		=> array( $config['offset_a'], $config['offset_b'] ),
										'add_join'	=> array(
															array(
																'select'	=> 'f.*',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=c.comment_fid',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'cc.*',
																'from'		=> array( 'bitracker_categories' => 'cc' ),
																'where'		=> 'cc.cid=f.file_cat',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'm.*, m.member_id as mid',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=c.comment_mid',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> 'pp.pp_member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 'pf.*',
																'from'		=> array( 'pfields_content' => 'pf' ),
																'where'		=> 'pf.member_id=m.member_id',
																'type'		=> 'left',
																),
															array(
																'select'	=> 's.*',
																'from'		=> array( 'sessions' => 's' ),
																'where'		=> 's.member_id=m.member_id AND s.running_time > ' . ( time() - ( 60 * 60 ) ),
																'type'		=> 'left',
																),
															)
								)		);
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------
					
					if( !$r['mid'] )
					{
						$r			= array_merge( $r, IPSMember::setUpGuest() );
					}

					$r['member_id']	= $r['mid'];
					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=bitracker&amp;module=display&amp;section=findpost&amp;id=' . $r['comment_id'], 'none' );
					$r['date']		= $r['comment_date'];
					$r['content']	= $r['comment_text'];
					$r['title']		= $r['file_name'];
					
					$r				= IPSMember::buildDisplayData( $r );
					
					IPSText::getTextClass('bbcode')->parse_html 				= 0;
					IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
					IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
					IPSText::getTextClass('bbcode')->parse_smilies				= $r['use_emo'];
					IPSText::getTextClass('bbcode')->parsing_section			= 'bit_comment';
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
					IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];
					
					$r['content']	= IPSText::getTextClass('bbcode')->preDisplayParse( $r['content'] );

					$content[]		= $r;
				}
			break;
			
			case 'cats':
				ipsRegistry::getAppClass( 'bitracker' );
				
				$cats	= array();
				$filter	= array();
				
				if( $config['filter_cats'] )
				{
					$filter	= explode( ',', $config['filter_cats'] );
				}
				
				foreach( $this->registry->categories->cat_lookup as $cid => $category )
				{
					if( count($filter) AND !in_array( $cid, $filter ) )
					{
						continue;
					}
					
					if( $config['filter_root'] AND $category['cparent'] > 0 )
					{
						continue;
					}
					
					switch( $config['sortby'] )
					{
						case 'name':
							$cats[ $category['cname'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
			
						case 'last_file':
							$cats[ $category['cfileinfo']['date'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
						
						case 'files':
							$cats[ $category['cfileinfo']['total_files'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
	
						case 'position':
							$cats[ $category['cposition'] . '_' . rand( 100, 999 ) ]	= $category;
						break;
	
						case 'rand':
							$cats[ rand( 10000, 99999 ) ]	= $category;
						break;
					}
				}

				if( $config['sortorder'] == 'desc' )
				{
					krsort($cats);
				}
				else
				{
					ksort($cats);
				}

				$cats		= array_slice( $cats, $config['offset_a'], $config['offset_b'] );
				$finalCats	= array();

				foreach( $cats as $r )
				{
					//-----------------------------------------
					// Normalization
					//-----------------------------------------

					$r['url']		= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?app=bitracker&amp;showcat=' . $r['cid'], 'none', $r['cname_furl'] ? $r['cname_furl'] : IPSText::makeSeoTitle( $r['cname'] ), 'bitshowcat' );
					$r['title']		= $r['cname'];
					$r['date']		= $r['cfileinfo']['date'];
					$r['content']	= $r['cdesc'];

					$content[]		= $r;
				}
			break;
		}
		
		//-----------------------------------------
		// Return formatted content
		//-----------------------------------------
		
		$feedConfig		= $this->returnFeedInfo();
		
		// Using a gallery template, or custom?
		if( ( $block['block_template'] && $block['tpb_name'] ) || $previewMode == true )
		{
			$templateBit = $block['tpb_name'];
		}
		else
		{
			$templateBit	= $feedConfig['templateBit'] . '_' . $block['block_id'];
		}
		
		if( $config['hide_empty'] AND !count($content) )
		{
			return '';
		}
		
		ob_start();
		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit( $block['block_name'], $content );
		ob_end_clean();
		return $_return;
	}
}