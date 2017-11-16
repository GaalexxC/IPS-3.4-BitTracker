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

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		admin_bitracker_categories_categories
 * @brief		devCU bitracker management
 */
class admin_bitracker_categories_categories extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	protected $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_categories' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=categories&amp;section=categories';
		$this->form_code_js	= $this->html->form_code_js	= 'module=categories&section=categories';
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{
			case 'main':
			default:
				$this->_mainScreen();
			break;
							
			case 'new':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_manage' );
				$this->_mainForm('new');
			break;
			case 'donew':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_manage' );
				$this->_mainSave('new');
			break;

			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_manage' );
				$this->_mainForm('edit');
			break;
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_manage' );
				$this->_mainSave('edit');
			break;

			case 'doreorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_manage' );
				$this->_doReorder();
			break;

			case 'modform':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_mods' );
				$this->_showModForm('add');
			break;
			case 'editmod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_mods' );
				$this->_showModForm('edit');
			break;				
			case 'domod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_mods' );
				$this->_doModerator('add');
			break;
			case 'doeditmod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_mods' );
				$this->_doModerator('edit');
			break;
			case 'delmod':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_mods' );
				$this->_deleteModerator();
			break;				

			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_delete' );
				$this->_doDelete();
			break;
			case 'resynch':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_manage' );
				$this->_recount();
			break;
			case 'doempty':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cat_empty' );
				$this->_doEmpty();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();	
	}
	
	/**
	 * Category overview screen
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function _mainScreen()
	{
		/* Navigation */
		if ( $this->request['c'] )
		{
			$nav = $this->registry->getClass('categories')->getNav( $this->request['c'], $this->form_code . '&amp;c=', true );
			
			if ( is_array($nav) and count($nav) > 1 )
			{
				array_shift($nav);
				
				foreach( $nav as $_nav )
				{
					$this->registry->output->extra_nav[] = $_nav;
				}
			}
		}
		
		//-----------------------------------------
		// Print screen
		//-----------------------------------------

		if( count( $this->registry->getClass('categories')->cat_cache[ 0 ] ) )
		{
			if( $this->request['c'] AND $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]  )
			{
				$depth_guide = $this->registry->getClass('categories')->parent_lookup[ $this->request['c'] ];
			}
			else
			{
				$depth_guide = 0;
			}
			
			foreach( $this->registry->getClass('categories')->cat_cache[ $depth_guide ] as $id => $outer_data )
			{
				$tempHtml = '';
				$modData  = '';
				
				/* Subcategories */
				if ( is_array( $this->registry->getClass('categories')->cat_cache[ $outer_data['cid'] ] ) && count( $this->registry->getClass('categories')->cat_cache[ $outer_data['cid'] ] ) )
				{
					$tempHtml = $this->html->subCategories( $this->registry->getClass('categories')->cat_cache[ $outer_data['cid'] ] );
				}
				
				/* Moderators */
				if ( is_array( $this->registry->getClass('categories')->cat_mods[ $outer_data['cid'] ] ) && count( $this->registry->getClass('categories')->cat_mods[ $outer_data['cid'] ] ) )
				{
					$_mods = array();
					
					foreach( $this->registry->getClass('categories')->cat_mods[ $outer_data['cid'] ] as $_mid => $data )
					{
						if ( $data['modtype'] == 1 )
						{
							$data['_fullname'] = $data['mem_name'];
						}
						else
						{
							$data['_fullname'] = $this->lang->words['fc_group_prefix'] . $data['group_name'];
						}
						
						$data['randId']	= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );
						$data['cid'] = $outer_data['cid'];
						
						$_mods[] = $data;
					}
					
					if( count($_mods) )
					{
						$modData = $this->html->renderModeratorEntry( $_mods );
					}
				}					
				
				$categories .= $this->html->renderCategory( $tempHtml, $outer_data, $modData );
			}
		}

		$this->registry->output->html .= $this->html->mainScreen( $categories );
	}
	
	/**
	 * Actually perform the reorder
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function _doReorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad			= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax					= new $classToLoad();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['cats']) AND count($this->request['cats']) )
 		{
 			foreach( $this->request['cats'] as $this_id )
 			{
 				$this->DB->update( 'bitracker_categories', array( 'cposition' => $position ), 'cid=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->registry->getClass('categories')->rebuildCatCache();

 		$ajax->returnString( 'OK' );
 		exit();
	}		
	
	/**
	 * Recount the category
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function _recount()
	{
		$this->registry->getClass('categories')->rebuildFileinfo( intval($this->request['c']) );
 		
 		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&c=' . $this->request['c'] );
	}
	
	/**
	 * Empty the category
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function _doEmpty()
	{
		$catid	= intval($this->request['c']);
		$cnt	= 0;
		
		if( !$catid )
		{
			$this->registry->output->global_message = $this->lang->words['c_nocat_empty'];
			$this->_mainScreen();
			return;
		}
		
		$file_ids = array();
		
		$this->DB->build( array( 'select' => 'file_id', 'from' => 'bitracker_files', 'where' => 'file_cat='.$catid )	);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$file_ids[ $r['file_id'] ] = $r['file_id'];
		}
		
		if( count($file_ids) > 0 )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
			$mod			= new $classToLoad( $this->registry );
			$cnt			= $mod->doMultiDelete( $file_ids );
		}

		$this->registry->getClass('categories')->rebuildFileinfo( $catid );
		$this->registry->getClass('categories')->rebuildStatsCache();
 		
 		$this->registry->output->global_message = sprintf( $this->lang->words['c_emptydel'], $this->registry->getClass('categories')->cat_lookup[$catid]['cname'], $cnt );
 		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['c_emptydel'], $this->registry->getClass('categories')->cat_lookup[$catid]['cname'], $cnt ) );

 		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}	
	
	/**
	 * Delete the category
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function _doDelete()
	{
		$catid	= intval($this->request['c']);
		$cnt	= 0;
		
		if( !$catid )
		{
			$this->registry->output->global_message = $this->lang->words['c_nocat_del'];
			$this->_mainScreen();
			return;
		}
		
		//-----------------------------------------
		// Subcategories?
		//-----------------------------------------
		
		$children	= $this->registry->getClass('categories')->getChildren( $catid );
		
		$_where		= array_merge( array( $catid ), $children );
		
		$file_ids = array();
		
		$this->DB->build( array( 'select' => 'file_id', 'from' => 'bitracker_files', 'where' => 'file_cat IN ('. implode( ',', $_where ) . ')' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$file_ids[ $r['file_id'] ] = $r['file_id'];
		}
		
		if( count($file_ids) > 0 )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
			$mod			= new $classToLoad( $this->registry );
			$cnt			= $mod->doMultiDelete( $file_ids );
		}

		$this->DB->delete( 'bitracker_categories', 'cid IN ('. implode( ',', $_where ) . ')' );
		$this->DB->delete( 'permission_index', "app='bitracker' AND perm_type='cat' AND perm_type_id IN (". implode( ',', $_where ) . ')' );
		$this->DB->delete( 'core_like', "like_app='bitracker' AND like_area='categories' AND like_rel_id IN (". implode( ',', $_where ) . ')' );
		$this->DB->delete( 'core_like_cache', "like_cache_app='bitracker' AND like_cache_area='categories' AND like_cache_rel_id IN (". implode( ',', $_where ) . ')' );
		
		$this->registry->getClass('categories')->rebuildCatCache();
		$this->registry->getClass('categories')->rebuildStatsCache();

 		$this->registry->output->global_message = sprintf( $this->lang->words['c_delrem'], $this->registry->getClass('categories')->cat_lookup[$catid]['cname'], $cnt );
 		$this->registry->getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['c_delrem'], $this->registry->getClass('categories')->cat_lookup[$catid]['cname'], $cnt ));
 		
 		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}	
	
	/**
	 * Save add/edit category
	 *
	 * @param	string		$type		Action type [new|edit]
	 * @return	@e void [Outputs to screen]
	 */
	protected function _mainSave( $type='new' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['cname'] = trim( $this->request['cname'] );
		$this->request['c']		= intval( $this->request['c'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ($this->request['cname'] == "")
		{
			$this->registry->output->global_message = $this->lang->words['c_entername'];
			$this->_mainForm( $type );
			return;
		}

		if ( $this->request['cparent'] != $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cparent'] )
		{
			if( $this->request['cparent'] != 0 AND $this->request['c'] != 0 )
			{
				$ids	= $this->registry->getClass('categories')->getChildren( $this->request['c'] );
				$ids[]	= $this->request['c'];
				
				if ( in_array( $this->request['cparent'], $ids ) )
				{
					$this->registry->output->global_message = $this->lang->words['c_invparent'];
					$this->_mainForm( $type );
					return;
				}
			}
		}
		
		//-----------------------------------------
		// Check topic generation config
		//-----------------------------------------
		
		if( $this->request['opt_topice'] )
		{
			if( !$this->request['opt_topicf'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_selectforum'];
				$this->_mainForm( $type );
				return;
			}
			
			$forum = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'forums', 'where' => 'id=' . intval($this->request['opt_topicf']) ) );
			
			if( !$forum['id'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_forum404'];
				$this->_mainForm( $type );
				return;
			}

			if( !$forum['sub_can_post'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_noroot'];
				$this->_mainForm( $type );
				return;
			}
			
			if( $forum['redirect_on'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_noredirect'];
				$this->_mainForm( $type );
				return;
			}			
		}
		
		/* This code works, however sometimes people try to configure cats before raising the limit and then have to reconfigure
			every category, so we'll just let them enter what they want

		$max_upload = @ini_get('upload_max_filesize') ? @ini_get('upload_max_filesize') : 0;
		
		if( substr( $max_upload, -1, 1 ) == "M" )
		{
			$max_upload = substr( $max_upload, 0, -1 );
			$max_upload = $max_upload*1024;
		}
		
		if( $this->request['opt_maxfile'] > $max_upload || $this->request['opt_maxss'] > $max_upload )
		{
			$this->registry->output->global_message = "The maximum upload file size you can specify is {$max_upload}.  If you require larger file uploads, please contact your host to have this limit raised in your php.ini configuration file.";
			$this->_mainForm( $type );
			return;
		}*/
		
		//-----------------------------------------
		// Other options
		//-----------------------------------------
		
		$options = array(	'opt_mimemask'		=> $this->request['opt_mimemask'],
							'opt_bbcode'		=> intval($this->request['opt_bbcode']),
							'opt_html'			=> intval($this->request['opt_html']),
							'opt_catss'			=> intval($this->request['opt_catss']),
							'opt_comments'		=> intval($this->request['opt_comments']),
							'opt_allowss'		=> intval($this->request['opt_allowss']),
							'opt_reqss'			=> intval($this->request['opt_reqss']),
							'opt_allownfo'		=> intval($this->request['opt_allownfo']),
							'opt_reqnfo'		=> intval($this->request['opt_reqnfo']),
							'opt_sortorder'		=> $this->request['opt_sortorder'],
							'opt_sortby'		=> $this->request['opt_sortby'],
							'opt_maxfile'		=> intval($this->request['opt_maxfile']),
							'opt_maxnfo'		=> intval($this->request['opt_maxnfo']),
							'opt_maxss'			=> intval($this->request['opt_maxss']),
							'opt_thumb_x'		=> intval($this->request['opt_thumb_x']),
							'opt_topice'		=> intval($this->request['opt_topice']),
							'opt_topicf'		=> intval($this->request['opt_topicf']),
							'opt_topicp'		=> $this->request['opt_topicp'],
							'opt_topics'		=> $this->request['opt_topics'],
							'opt_topicd'		=> intval($this->request['opt_topicd']),
							'opt_topicnfo'		=> intval($this->request['opt_topicnfo']),
							'opt_topicss'		=> intval($this->request['opt_topicss']),
							'opt_disfiles'		=> intval($this->request['opt_disfiles']),
							'opt_noperm_view'	=> nl2br( IPSText::stripslashes($_POST['opt_noperm_view']) ),
							'opt_noperm_add'	=> nl2br( IPSText::stripslashes($_POST['opt_noperm_add']) ),
							'opt_noperm_dl'		=> nl2br( IPSText::stripslashes($_POST['opt_noperm_dl']) ),
						);
		
		$coptions = serialize( $options );

		//-----------------------------------------
		// Check custom fields
		//-----------------------------------------
		
		$ccfields = ( is_array($this->request['ccfields']) && count($this->request['ccfields']) ) ? implode( ',', $this->request['ccfields'] ) : '';

		//-----------------------------------------
		// Save array
		//-----------------------------------------
  
		$save = array(  'cname' 			=> $this->request['cname'],
						'cname_furl'		=> IPSText::makeSeoTitle( $this->request['cname'] ),
						'cdesc'  			=> nl2br( IPSText::stripslashes($_POST['cdesc']) ),
						'cdisclaimer'		=> nl2br( IPSText::stripslashes($_POST['cdisclaimer']) ),
						'copen'				=> intval($this->request['copen']),
						'cparent'			=> intval($this->request['cparent']),
						'coptions'			=> $coptions,
						'ccfields'			=> $ccfields,
						'ctags_disabled'	=> intval($this->request['ctags_disabled']),
						'ctags_noprefixes'	=> intval($this->request['ctags_noprefixes']),
						'ctags_predefined'	=> IPSText::stripslashes($_POST['ctags_predefined'])
					 );
		
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------

		IPSLib::loadInterface( 'admin/bitracker_category_form.php' );
		
		$_formPlugins = array();
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/bitracker_category_form.php' ) )
			{
				$_class  = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/bitracker_category_form.php', 'admin_bitracker_category_form__'.$app_dir, $app_dir );
				$_formPlugins[ $_class ] = new $_class( $this->registry );
				
				$remote  = $_formPlugins[ $_class ]->getForSave();

				$save = array_merge( $save, $remote );
			}
		}
		
		//-----------------------------------------
		// ADD
		//-----------------------------------------
		
		if ( $type == 'new' )
		{
			$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(cid) as maxcid', 'from' => 'bitracker_categories' ) );

			$max['maxcid']			= intval($max['maxcid']) + 1;
			$save['cposition']		= $max['maxcid'];
			$save['cfileinfo']      = '';
			$save['cperms']         = '';
			
			$this->DB->insert( 'bitracker_categories', $save );
			
			$category_id = $this->DB->getInsertId();
			
			$this->registry->output->global_message = sprintf( $this->lang->words['c_createdlog'], $save['cname'] );
			
			$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['c_createdlog'], $save['cname'] ) );
		}
		else
		{
			$this->DB->update( 'bitracker_categories', $save, "cid={$this->request['c']}" );
			
			$category_id = $this->request['c'];
			
			$this->registry->output->global_message = sprintf( $this->lang->words['c_editedlog'], $save['cname'] );
			
			$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['c_editedlog'], $save['cname'] ) );
		}

		//-----------------------------------------
		// Permissions
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( ipsRegistry::instance() );
		$permissions->savePermMatrix( $this->request['perms'], $type == 'new' ? $category_id : $this->request['c'], 'cat', 'bitracker' );
		
		$this->registry->getClass('categories')->rebuildCatCache();
		$this->registry->getClass('categories')->rebuildStatsCache();
		
		//-----------------------------------------
		// Post save callbacks
		//-----------------------------------------
		
		if( count($_formPlugins) )
		{
			foreach( $_formPlugins as $_className => $_object )
			{
				if( method_exists( $_object, 'postSave' ) )
				{
					$_object->postSave( $category_id );
				}
			}
		}
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&c=' . $category_id );
	}
	
	/**
	 * Save add/edit category
	 *
	 * @param	string		$type		Action type [new|edit]
	 * @return	@e void [Outputs to screen]
	 */
	protected function _mainForm( $type='edit' )
	{
		/* Navigation */
		$this->request['c'] = empty($this->request['c']) ? intval($this->request['p']) : intval($this->request['c']);
		
		if ( $this->request['c'] )
		{
			$nav = $this->registry->getClass('categories')->getNav( $this->request['c'], $this->form_code . '&amp;c=', true );
			
			if ( is_array($nav) and count($nav) > 1 )
			{
				array_shift($nav);
				
				foreach( $nav as $_nav )
				{
					$this->registry->output->extra_nav[] = $_nav;
				}
				
				/* Add also current category ;) */
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code. '&amp;c=' . $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cid'], $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cname'] );
			}
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$form			= array();
		$cat_id			= intval($this->request['c']);
		$parentid		= intval($this->request['p']) ? intval($this->request['p']) : 0;
		$cname			= IPSText::parseCleanValue(urldecode($_REQUEST['cname']));
		$firstTab		= empty($this->request['_initTab']) ? 'Basic' : trim($this->request['_initTab']);
		$perm_matrix	= '';
		
		$dd_order		= array( 
							 0 => array( 'submitted', $this->lang->words['c_subdate'] ),
							 1 => array( 'updated'	, $this->lang->words['c_lastupdate'] ),
							 2 => array( 'name'    	, $this->lang->words['c_filetitle'] ),
							 3 => array( 'bitracker', $this->lang->words['c_bitracker'] ),
							 4 => array( 'views'    , $this->lang->words['c_views'] ),
							 5 => array( 'rating'	, $this->lang->words['c_rating'] ),
							 6 => array( 'comments'	, $this->lang->words['c_comments'] ),
							);
		
		$dd_by			= array(
							 0 => array( 'Z-A', $this->lang->words['c_dsc'] ),
							 1 => array( 'A-Z', $this->lang->words['c_asc'] )
							);

		//-----------------------------------------
		// ini_get max upload size?
		//-----------------------------------------
				
		$max_upload			= @ini_get('upload_max_filesize') ? @ini_get('upload_max_filesize') : 0;
		$max_upload_display	= $max_upload != '0' ? $max_upload : $this->lang->words['c_phpini'];
		
		if( substr( $max_upload, -1, 1 ) == "M" )
		{
			$max_upload = substr( $max_upload, 0, -1 );
			$max_upload = $max_upload*1024;
		}
		
		//-----------------------------------------
		// EDIT
		//-----------------------------------------
		
		if ( $type == 'edit' )
		{
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ( ! $cat_id )
			{
				$this->registry->output->showError( $this->lang->words['c_nocat'], 1182 );
			}
			
			//-----------------------------------------
			// Get this forum
			//-----------------------------------------
			
			$cat = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_categories', 'where' => 'cid=' . $cat_id ) );
			
			//-----------------------------------------
			// Check
			//-----------------------------------------
			
			if ( !$cat['cid'] )
			{
				$this->registry->output->showError( sprintf( $this->lang->words['c_nocatid'], $cat_id ), 1183 );
			}
			
			$coptions	= unserialize( $cat['coptions'] );
			$coptions	= ( is_array($coptions) && count($coptions) ) ? $coptions : array();
			$cat 		= array_merge( $cat, $coptions );
			
			$cat['ccfields'] = explode( ",", $cat['ccfields'] );
			
			//-----------------------------------------
			// Set up code buttons
			//-----------------------------------------
			
			$form['form_title']		= sprintf( $this->lang->words['c_edittitle'], $cat['cname'] );
			$form['form_button']	= $this->lang->words['c_editbutton'];
			$form['form_code']		= 'doedit';
		}
		
		//-----------------------------------------
		// NEW
		//-----------------------------------------
		
		else
		{
			$cat = array( 'cid'				=> 0,
						  'sub_can_post'	=> 1,
						  'cname'			=> $cname ? $cname : $this->lang->words['c_newcat'],
						  'cparent'			=> $parentid,
						  'opt_html'		=> 0,
						  'opt_bbcode'		=> 1,
						  'sort_key'		=> 'updated',
						  'sort_order'		=> 'Z-A',
						  'ccfields'		=> $_POST['ccfields'],
						  'copen'			=> 1,
						  'opt_disfiles'	=> 1,
						);
			
			$form['form_title']		= $this->lang->words['c_addtitle'];
			$form['form_button']	= $this->lang->words['c_addbutton'];
			$form['form_code']		= 'donew';
		}
		
		//-----------------------------------------
		// Build forumlist for topic submission
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('forums') . '/sources/classes/forums/class_forums.php' );/*noLibHook*/
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('forums') . '/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );
		$forumsFuncs = new $classToLoad( $this->registry );
		
		$forumsFuncs->forumsInit();
		$dropdown = $forumsFuncs->adForumsForumList(1);
		
		//-----------------------------------------
		// Build category list for parent cat
		//-----------------------------------------
		
		$catlist = $this->registry->getClass('categories')->catJumpList();
		
		//-----------------------------------------
		// Build Mime-type masks
		//-----------------------------------------		

		$masks = array();

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mimemask' ) );
		$this->DB->execute();

		while( $getmasks = $this->DB->fetch() )
		{
			$masks[] = array( $getmasks['mime_maskid'], $getmasks['mime_masktitle'] );
		}

		//-----------------------------------------
		// Build per-cat Custom Fields
		//-----------------------------------------

		$cfields = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_cfields', 'order' => 'cf_position' ) );
		$this->DB->execute();

		while( $fields = $this->DB->fetch() )
		{
			$cfields[] = array( $fields['cf_id'], $fields['cf_title'] );
		}

		//-----------------------------------------
		// Generate form items
		//-----------------------------------------

		# Main settings
		$form['cname']     		= $this->registry->output->formInput(			"cname"			, $_POST['cname'] ? IPSText::parseCleanValue( $_POST['cname'] ) : $cat['cname'] );
		$form['cdesc']  		= $this->registry->output->formTextarea(		"cdesc" 		, IPSText::br2nl( IPSText::stripslashes( $_POST['cdesc'] ? $_POST['cdesc'] : $cat['cdesc'] ) ) );
		$form['cparent']    	= $this->registry->output->formDropdown(		"cparent"		, $catlist, $_POST['cparent'] ? $_POST['cparent'] : $cat['cparent'] );
		$form['copen']       	= $this->registry->output->formYesNo(			"copen"			, $_POST['copen'] ? $_POST['copen'] : $cat['copen'] );
		$form['opt_disfiles'] 	= $this->registry->output->formYesNo(			"opt_disfiles"	, $_POST['opt_disfiles'] ? $_POST['opt_disfiles'] : $cat['opt_disfiles'] );
		$form['cdisclaimer']	= $this->registry->output->formTextarea(		"cdisclaimer"	, IPSText::br2nl( IPSText::stripslashes( $_POST['cdisclaimer'] ? $_POST['cdisclaimer'] : $cat['cdisclaimer'] ) ) );

		# Per-Cat Options
		$form['opt_mimemask']  	= $this->registry->output->formDropdown(		"opt_mimemask"	, $masks, $_POST['opt_mimemask'] ? $_POST['opt_mimemask'] : $cat['opt_mimemask'] );
		$form['ccfields']  		= $this->registry->output->formMultiDropdown(	"ccfields[]"	, $cfields, $cat['ccfields'], '6' );
		$form['opt_bbcode']  	= $this->registry->output->formYesNo(			"opt_bbcode"	, $_POST['opt_bbcode'] ? $_POST['opt_bbcode'] : $cat['opt_bbcode'] );
		$form['opt_html']   	= $this->registry->output->formYesNo(			"opt_html"		, $_POST['opt_html'] ? $_POST['opt_html'] : $cat['opt_html'] );
		$form['opt_catss'] 		= $this->registry->output->formYesNo(			"opt_catss"		, $_POST['opt_catss'] ? $_POST['opt_catss'] : $cat['opt_catss'] );
		$form['opt_comments']	= $this->registry->output->formYesNo(			"opt_comments"	, $_POST['opt_comments'] ? $_POST['opt_comments'] : $cat['opt_comments'] );
		$form['opt_allowss']	= $this->registry->output->formYesNo(			"opt_allowss"	, $_POST['opt_allowss'] ? $_POST['opt_allowss'] : $cat['opt_allowss'] );
		$form['opt_reqss']		= $this->registry->output->formYesNo(			"opt_reqss"		, $_POST['opt_reqss'] ? $_POST['opt_reqss'] : $cat['opt_reqss'] );
		$form['opt_allownfo']	= $this->registry->output->formYesNo(			"opt_allownfo"	, $_POST['opt_allownfo'] ? $_POST['opt_allownfo'] : $cat['opt_allownfo'] );
		$form['opt_reqnfo']		= $this->registry->output->formYesNo(			"opt_reqnfo"	, $_POST['opt_reqnfo'] ? $_POST['opt_reqnfo'] : $cat['opt_reqnfo'] );
		$form['opt_sortorder']	= $this->registry->output->formDropdown(		"opt_sortorder"	, $dd_order, $_POST['opt_sortorder'] ? $_POST['opt_sortorder'] : $cat['opt_sortorder'] );
		$form['opt_sortby']		= $this->registry->output->formDropdown(		"opt_sortby"	, $dd_by, $_POST['opt_sortby'] ? $_POST['opt_sortby'] : $cat['opt_sortby'] );
		$form['opt_maxfile']	= $this->registry->output->formInput(			"opt_maxfile"	, $_POST['opt_maxfile'] ? intval($_POST['opt_maxfile']) : $cat['opt_maxfile']   );
		$form['opt_maxnfo']		= $this->registry->output->formInput(			"opt_maxnfo"	, $_POST['opt_maxnfo'] ? intval($_POST['opt_maxnfo']) : $cat['opt_maxnfo'] );
		$form['opt_maxss']		= $this->registry->output->formInput(			"opt_maxss"		, $_POST['opt_maxss'] ? intval($_POST['opt_maxss']) : $cat['opt_maxss'] );
		$form['opt_thumb_x']	= $this->registry->output->formSimpleInput(	"opt_thumb_x"	, $_POST['opt_thumb_x'] ? intval($_POST['opt_thumb_x']) : $cat['opt_thumb_x'] );

		# Topic auto-generation
		$form['opt_topice'] 	= $this->registry->output->formYesNo(			"opt_topice"	, $_POST['opt_topice'] ? $_POST['opt_topice'] : $cat['opt_topice'] );
		$form['opt_topicf'] 	= $this->registry->output->formDropdown(		"opt_topicf"	, $dropdown, $_POST['opt_topicf'] ? $_POST['opt_topicf'] : $cat['opt_topicf'] );
		$form['opt_topicp'] 	= $this->registry->output->formInput(			"opt_topicp"	, IPSText::parseCleanValue( $_POST['opt_topicp'] ) ? IPSText::parseCleanValue( $_POST['opt_topicp'] ) : $cat['opt_topicp'] );
		$form['opt_topics'] 	= $this->registry->output->formInput(			"opt_topics"	, IPSText::parseCleanValue( $_POST['opt_topics'] ) ? IPSText::parseCleanValue( $_POST['opt_topics'] ) : $cat['opt_topics'] );
		$form['opt_topicd'] 	= $this->registry->output->formYesNo(			"opt_topicd"	, $_POST['opt_topicd'] ? $_POST['opt_topicd'] : $cat['opt_topicd'] );
		$form['opt_topicnfo'] 	= $this->registry->output->formYesNo(			"opt_topicnfo"	, $_POST['opt_topicnfo'] ? $_POST['opt_topicnfo'] : $cat['opt_topicnfo'] );
		$form['opt_topicss'] 	= $this->registry->output->formYesNo(			"opt_topicss"	, $_POST['opt_topicss'] ? $_POST['opt_topicss'] : $cat['opt_topicss'] );
		$form['opt_topicnfo'] 	= $this->registry->output->formYesNo(			"opt_topicnfo"	, $_POST['opt_topicnfo'] ? $_POST['opt_topicnfo'] : $cat['opt_topicnfo'] );

		# Custom permission denied messages		
		$form['opt_noperm_view']	= $this->registry->output->formTextarea(	"opt_noperm_view"	, IPSText::br2nl( IPSText::stripslashes( $_POST['opt_noperm_view'] ? $_POST['opt_noperm_view'] : $cat['opt_noperm_view'] ) ) );
		$form['opt_noperm_add']		= $this->registry->output->formTextarea(	"opt_noperm_add"	, IPSText::br2nl( IPSText::stripslashes( $_POST['opt_noperm_add'] ? $_POST['opt_noperm_add'] : $cat['opt_noperm_add'] ) ) );
		$form['opt_noperm_dl']		= $this->registry->output->formTextarea(	"opt_noperm_dl"		, IPSText::br2nl( IPSText::stripslashes( $_POST['opt_noperm_dl'] ? $_POST['opt_noperm_dl'] : $cat['opt_noperm_dl'] ) ) );
		
		# Tagging
		$form['ctags_disabled'] 	= $this->registry->output->formYesNo(		"ctags_disabled"	, isset($_POST['ctags_disabled']) ? $_POST['ctags_disabled'] : $cat['ctags_disabled'] );
		$form['ctags_noprefixes'] 	= $this->registry->output->formYesNo(		"ctags_noprefixes"	, isset($_POST['ctags_noprefixes']) ? $_POST['ctags_noprefixes'] : $cat['ctags_noprefixes'] );
		$form['ctags_predefined']	= $this->registry->output->formTextarea(	"ctags_predefined"	, IPSText::stripslashes( isset($_POST['ctags_predefined']) ? $_POST['ctags_predefined'] : $cat['ctags_predefined'] ) );

		#Permissions

		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/class_public_permissions.php', 'classPublicPermissions' );
		$permissions	= new $classToLoad( ipsRegistry::instance() );
		$form['permissions']	= $permissions->adminPermMatrix( 'cat', $this->registry->getClass('categories')->cat_lookup[ $cat['cid'] ], 'bitracker', '', false );
		
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------
		
		$blocks	= array( 'tabs' => array(), 'area' => array() );

		IPSLib::loadInterface( 'admin/bitracker_category_form.php' );
		
		$tabsUsed = 2;
		
		foreach( IPSLib::getEnabledApplications() as $app_dir => $app_data )
		{
			if ( is_file( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/bitracker_category_form.php' ) )
			{
				$_class = IPSLib::loadLibrary( IPSLib::getAppDir( $app_dir ) . '/extensions/admin/bitracker_category_form.php', 'admin_bitracker_category_form__'.$app_dir, $app_dir );
				
				if ( class_exists( $_class ) )
				{
					$_object = new $_class( $this->registry );
					
					$data = $_object->getDisplayContent( $cat, $tabsUsed );
					$blocks['area'][ $app_dir ] = $data['content'];
					$blocks['tabs'][ $app_dir ] = $data['tabs'];
					
					$tabsUsed = $data['tabsUsed'] ? ( $tabsUsed + $data['tabsUsed'] ) : ( $tabsUsed + 1 );
					
					if ( !empty($this->request['_initTab']) && $this->request['_initTab'] == $app_dir )
					{
						$firstTab = 'CustomTab' . $tabsUsed;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Nav and print
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( '', $form['form_button'] );
		
		$this->registry->output->html .= $this->html->categoryForm( $cat, $form, $max_upload_display, $blocks, $firstTab );
	}
		
	/**
	 * Show the moderator form
	 *
	 * @param	string		$type		Action type [add|edit]
	 * @return	@e void [Outputs to screen]
	 */
	protected function _showModForm( $type='add' )
	{
		/* Navigation */
		if ( $this->request['c'] )
		{
			$nav = $this->registry->getClass('categories')->getNav( $this->request['c'], $this->form_code . '&amp;c=', true );
			
			if ( is_array($nav) and count($nav) > 1 )
			{
				array_shift($nav);
				
				foreach( $nav as $_nav )
				{
					$this->registry->output->extra_nav[] = $_nav;
				}
				
				/* Add also current category ;) */
				$this->registry->output->extra_nav[] = array( $this->settings['base_url'] . $this->form_code. '&amp;c=' . $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cid'], $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cname'] );
			}
		}
		
		//-----------------------------------------
		// Some init
		//-----------------------------------------
		
		$catlist	= $this->registry->getClass('categories')->catJumpList(true);
		$form		= array();
		
		$mod_cats[]	= $this->request['c'];
		
		//-----------------------------------------
		// Add or edit
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mods', 'where' => 'modid=' . intval($this->request['modid']) ) );
			
			if( $row['modid'] )
			{
				$thiscats = explode( ",", $row['modcats'] );
				
				if( count($thiscats) )
				{
					foreach( $thiscats as $k => $v )
					{
						$mod_cats[] = $v;
					}
				}
			}
			
			$form['code'] = 'doeditmod';
		}
		else
		{
			$form['code'] = 'domod';
		}

		if( !$catlist )
		{
			$this->registry->output->showError( $this->lang->words['c_addmodcat'], 1184 );
		}
		
		//-----------------------------------------
		// Generate form
		//-----------------------------------------

		$dropdown = array( array( 1, $this->lang->words['c_member'] ), array( 0, $this->lang->words['c_group'] ) );
		
		$groups[] = array( 0, $this->lang->words['c_member'] );
		
		$this->DB->build( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'order' => 'g_title' ) );
		$this->DB->execute();
		
		while( $g = $this->DB->fetch() )
		{
			$groups[] = array( $g['g_id'], $g['g_title'] );
		}
		
		if( $type == 'edit' )
		{
			$formdefault = explode( ":", $row['modgmid'] );
		}

		$form['modtype']		= $this->registry->output->formDropdown( "modtype", $dropdown, $_POST['modtype'] ? $_POST['modtype'] : $row['modtype'] );		
		$form['modgid']			= $this->registry->output->formDropdown( "modgid", $groups, $_POST['modgid'] ? $_POST['modgid'] : ( $row['modtype'] == 0 ? $formdefault[0] : 0 ) );
		$form['modmid']			= $this->registry->output->formInput( "modmid", IPSText::parseCleanValue( $_POST['modmid'] ? $_POST['modmid'] : ( $row['modtype'] == 1 ? $formdefault[1] : '' ) ) );
		$form['modcanedit']		= $this->registry->output->formYesNo( "modcanedit", $_POST['modcanedit'] ? $_POST['modcanedit'] : $row['modcanedit'] );
		$form['modcandel']		= $this->registry->output->formYesNo( "modcandel", $_POST['modcandel'] ? $_POST['modcandel'] : $row['modcandel'] );
		$form['modcanapp']		= $this->registry->output->formYesNo( "modcanapp", $_POST['modcanapp'] ? $_POST['modcanapp'] : $row['modcanapp'] );
		$form['modcanbrok']		= $this->registry->output->formYesNo( "modcanbrok", $_POST['modcanbrok'] ? $_POST['modcanbrok'] : $row['modcanbrok'] );
		$form['modcancomm']		= $this->registry->output->formYesNo( "modcancomments", $_POST['modcancomments'] ? $_POST['modcancomments'] : $row['modcancomments'] );
		$form['modcats']		= $this->registry->output->formMultiDropdown( "modcats[]", $catlist, $_POST['modcats'] ? $_POST['modcats'] : $mod_cats, "8");
		$form['modchangeauth']	= $this->registry->output->formYesNo( "modchangeauthor", $_POST['modchangeauthor'] ? $_POST['modchangeauthor'] : $row['modchangeauthor'] );
		$form['modusefeature']	= $this->registry->output->formYesNo( "modusefeature", $_POST['modusefeature'] ? $_POST['modusefeature'] : $row['modusefeature'] );
		$form['modcanpin']		= $this->registry->output->formYesNo( "modcanpin", $_POST['modcanpin'] ? $_POST['modcanpin'] : $row['modcanpin'] );

		$this->registry->output->html .= $this->html->moderatorForm( $form );
	}
	
	/**
	 * Save the moderator
	 *
	 * @param	string		$type		Action type [add|edit]
	 * @return	@e void [Outputs to screen]
	 */
	protected function _doModerator( $type='add' )
	{
		$moderator = array();
		
		if( $type == 'edit' && !$this->request['modid'] )
		{
			$this->registry->output->global_message = $this->lang->words['c_probmod'];
			$this->_showModForm( $type );
			return;
		}
		
		if( $type == 'edit' && $this->request['modid'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mods', 'where' => "modid=".intval($this->request['modid']) ) );
			$this->DB->execute();
			
			if (! $this->DB->getTotalRows() )
			{
				$this->registry->output->global_message = $this->lang->words['c_whatmod'];
				$this->_showModForm( $type );
				return;
			}
			else
			{
				$moderator = $this->DB->fetch();
			}
		}		
					
		if( count($this->request['modcats']) == 0 )
		{
			$this->registry->output->global_message = $this->lang->words['c_nocatsmod'];
			$this->_showModForm( $type );
			return;
		}
		
		if( $this->request['modtype'] == 0 && !$this->request['modgid'] )
		{
			$this->registry->output->global_message = $this->lang->words['c_nogroup'];
			$this->_showModForm( $type );
			return;
		}			
		
		if( $this->request['modtype'] == 1 && !$this->request['modmid'] )
		{
			$this->registry->output->global_message = $this->lang->words['c_nomember'];
			$this->_showModForm( $type );
			return;
		}
		
		if( $this->request['modtype'] == 0 )
		{
			$group = $this->DB->buildAndFetch( array( 'select' => 'g_id, g_title', 'from' => 'groups', 'where' => 'g_id=' . intval($this->request['modgid']) ) ); 
			
			if( !$group['g_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_invgroup'];
				$this->_showModForm( $type );
				return;
			}
		}
		else
		{
			$member = IPSMember::load( $this->request['modmid'], 'core', 'displayname' );
			
			if (! $member['member_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['c_invname'];
				$this->_showModForm( $type );
				return;
			}
		}
		
		$cats = implode( ",", $this->request['modcats'] );
		
		$save_array = array( 'modtype'			=> intval($this->request['modtype']),
							 'modgmid'			=> count($member)? $member['member_id'] . ":" . $member['members_display_name'] : $group['g_id'] . ':' . $group['g_title'],
							 'modcanedit'		=> intval($this->request['modcanedit']),
							 'modcandel'		=> intval($this->request['modcandel']),
							 'modcanapp'		=> intval($this->request['modcanapp']),
							 'modcanbrok'		=> intval($this->request['modcanbrok']),
							 'modcancomments'	=> intval($this->request['modcancomments']),
							 'modchangeauthor'	=> intval($this->request['modchangeauthor']),
							 'modusefeature'	=> intval($this->request['modusefeature']),
							 'modcanpin'		=> intval($this->request['modcanpin']),
							 'modcats'			=> $cats,
							);
							
		if( $type == 'add' )
		{
			$this->DB->insert( "bitracker_mods", $save_array );
		}
		else
		{
			$this->DB->update( "bitracker_mods", $save_array, "modid=" . intval($this->request['modid']) );
		}
		
		$this->registry->getClass('categories')->rebuildModCache();
		
		$this->registry->output->global_message = $this->lang->words['c_modsaved'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save the moderator
	 *
	 * @return	@e void [Outputs to screen]
	 */
	protected function _deleteModerator( )
	{
		if( !$this->request['modid'] )
		{
			$this->registry->output->global_message = $this->lang->words['c_probdel'];
			$this->_mainScreen();
			return;
		}
		
		if( $this->request['modid'] )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mods', 'where' => "modid=" . intval($this->request['modid']) ) );
			$this->DB->execute();
			
			if (! $this->DB->getTotalRows() )
			{
				$this->registry->output->global_message = $this->lang->words['c_404moddel'];
				$this->_mainScreen();
				return;
			}
			else
			{
				$moderator = $this->DB->fetch();
				
				$cats = explode( ',', $moderator['modcats'] );
				
				if( count($cats) == 1 && $this->request['c'] == $cats[0] )
				{
					$this->DB->delete( 'bitracker_mods', 'modid=' . intval($this->request['modid']) );
				}
				else if( count($cats) > 1 && $this->request['c'] )
				{
					$new_cats = array();
					
					foreach( $cats as $k => $v )
					{
						if( $v != $this->request['c'] )
						{
							$new_cats[] = $v;
						}
					}
					
					if( count($new_cats) > 0 )
					{
						$this->DB->update( 'bitracker_mods', array( 'modcats' => implode( ',', $new_cats ) ), 'modid=' . intval($this->request['modid']) );
					}
				}
				else
				{
					$this->DB->delete( 'bitracker_mods', 'modid=' . intval($this->request['modid']) );
				}
			}
		}		
					
		$this->registry->getClass('categories')->rebuildModCache();
		
		$this->registry->output->global_message = $this->lang->words['mod_success_delete'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&c=' . $this->request['c'] );
	}
}