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
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 *
 * @class		admin_bitracker_customize_cfields
 * @brief		IP.download Manager Custom Field Management
 */
class admin_bitracker_customize_cfields extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_cfields' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=customize&amp;section=cfields';
		$this->form_code_js	= $this->html->form_code_js	= 'module=customize&section=cfields';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_manage' );
				$this->_mainForm('add');
			break;
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_manage' );
				$this->_mainSave('add');
			break;
			case 'edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_manage' );
				$this->_mainForm('edit');
			break;
			case 'doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_manage' );
				$this->_mainSave('edit');
			break;
			case 'reorder':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_manage' );
				$this->_doReorder();
			break;
			case 'delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_delete' );
				$this->_deleteForm();
			break;
			case 'dodelete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_cf_delete' );
				$this->_doDelete();
			break;
			default:
				$this->_mainScreen();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Main class entry point
	 *
	 * @return	@e void
	 */
	public function rebuildCache()
	{
		$cache = array();
				
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_cfields', 'order' => 'cf_position' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$cache[ $r['cf_id'] ] = $r;
		}
		
		$this->cache->setCache( 'bit_cfields', $cache, array( 'array' => 1 ) );	
	}
	
	/**
	 * Show delete form
	 *
	 * @return	@e void
	 */
	protected function _deleteForm()
	{
		if ( !$this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['cf_noid'], 1185 );
		}

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_cfields', 'where' => "cf_id=".intval($this->request['id']) ) );
		$this->DB->execute();
		
		if ( ! $field = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['cf_404'], 1186 );
		}

		$this->registry->output->html .= $this->html->deleteForm( $field );
	}
	
	/**
	 * Delete the custom field
	 *
	 * @return	@e void
	 */
	protected function _doDelete()
	{
		if ($this->request['id'] == "")
		{
			$this->registry->output->showError( $this->lang->words['cf_noid'], 1187 );
		}
		
		//-----------------------------------------
		// Verify field existence
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_cfields', 'where' => "cf_id=".intval($this->request['id']) ) );
		$this->DB->execute();
		
		if ( ! $row = $this->DB->fetch() )
		{
			$this->registry->output->showError( $this->lang->words['cf_noid'], 1188 );
		}
		
		/* Drop custom field */
		$this->DB->dropField( 'bitracker_ccontent', "field_{$row['cf_id']}" );
		
		$this->DB->delete( 'bitracker_cfields', "cf_id=" . $row['cf_id'] );
		
		$this->rebuildCache();
		
		/* Still here? Drop it from the categories table too */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'bitracker_categories',
								 'where'  => $this->DB->buildWherePermission( array( $row['cf_id'] ), 'ccfields', false )
						 )		);
		$outer = $this->DB->execute();
		
		if ( $this->DB->getTotalRows($outer) )
		{
			while( $cat = $this->DB->fetch($outer) )
			{
				/* Explode, flip, unset, re-flip and resave! That's it ;) */
				$_tmp = explode( ',', $cat['ccfields'] );
				$_tmp = array_flip( $_tmp );
				
				unset( $_tmp[ $row['cf_id'] ] );
				
				$_tmp = array_flip( $_tmp );
				
				$this->DB->update( 'bitracker_categories', array( 'ccfields' => implode( ',', $_tmp ) ), 'cid='.$cat['cid'] );
			}
		}
		
		$this->cache->rebuildCache( 'bit_cats', 'bitracker' );
		
		/* Output */
		$this->registry->output->global_message = $this->lang->words['cf_deleted'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save added/edited custom field
	 *
	 * @param	string		$type		[add|edit]
	 * @return	@e void
	 */
	protected function _mainSave( $type='edit' )
	{
		$id = intval($this->request['id']);
		
		if ( $this->request['cf_title'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['cf_entertitle'], 1189 );
		}
		
		//-----------------------------------------
		// check-da-motcha
		//-----------------------------------------
		
		if ($type == 'edit')
		{
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['cf_noid'], 11810 );
			}
		}
		
		$content	= "";
		
		if ( $_POST['cf_content'] != "")
		{
			/* Custom Fields Class */
			$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCustomFields.php', 'classCustomFields' );
			$cfields_class	= new $classToLoad( array() );
			
			$content = $cfields_class->formatContentForSave( $_POST['cf_content'] );
		}
		
		$db_string = array( 'cf_title'			=> $this->request['cf_title'],
							'cf_desc'			=> $this->request['cf_desc'],
							'cf_content'		=> IPSText::stripslashes($content),
							'cf_type'			=> $this->request['cf_type'],
							'cf_not_null'		=> intval($this->request['cf_not_null']),
							'cf_max_input'		=> intval($this->request['cf_max_input']),
							'cf_input_format'	=> $this->request['cf_input_format'],
							'cf_file_format'	=> $this->request['cf_file_format'],
							'cf_topic'			=> intval($this->request['cf_topic']),
							'cf_search'			=> intval($this->request['cf_search']),
							'cf_format'			=> IPSText::stripslashes($_POST['cf_format']),
						  );

		if ($type == 'edit')
		{
			$this->DB->update( 'bitracker_cfields', $db_string, 'cf_id=' . $id );

			$this->registry->output->global_message = $this->lang->words['cf_edited'];
		}
		else
		{
			$max = $this->DB->buildAndFetch( array( 'select'	=> 'MAX(cf_position) as newpos',
															'from'	=> 'bitracker_cfields' ) );

			$db_string['cf_position'] = $max['newpos']+1;
			
			$this->DB->insert( 'bitracker_cfields', $db_string );
			
			$id = $this->DB->getInsertId();
			
			$this->DB->addField( 'bitracker_ccontent', "field_{$id}", 'text' );
			
			$this->DB->optimize( 'bitracker_ccontent' );

			$this->registry->output->global_message = $this->lang->words['cf_added'];
		}
		
		$this->rebuildCache();
		
		if( is_array($this->request['cats_apply']) AND count($this->request['cats_apply']) )
		{
			$did_at_least_one = 0;
			
			foreach( $this->registry->getClass('categories')->cat_lookup as $cid => $cdata )
			{
				$cfields = $cdata['ccfields'] ? explode( ',', $cdata['ccfields'] ) : array();
				
				if( !in_array( $id, $cfields ) )
				{
					if( in_array( $cid, $this->request['cats_apply'] ) )
					{
						array_push( $cfields, $id );
						
						$this->DB->update( 'bitracker_categories', array( 'ccfields' => implode( ',', $cfields ) ), 'cid=' . $cid );
						
						$did_at_least_one = 1;
					}
				}
				else
				{
					if( !in_array( $cid, $this->request['cats_apply'] ) )
					{
						$new_cfields = array();
						
						foreach( $cfields as $fid )
						{
							if( $fid != $id )
							{
								$new_cfields[] = $fid;
							}
						}
						
						$this->DB->update( 'bitracker_categories', array( 'ccfields' => implode( ',', $new_cfields ) ), 'cid=' . $cid );
						
						$did_at_least_one = 1;
					}
				}
			}
			
			if( $did_at_least_one )
			{
				$this->registry->getClass('categories')->rebuildCatCache();
			}
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Add/edit custom field
	 *
	 * @param	string		$type		[add|edit]
	 * @return	@e void
	 */
	protected function _mainForm( $type='edit' )
	{
		$this->request['id'] =  intval($this->request['id'] );
		
		$form	= array();
		
		if ( $type == 'edit' )
		{
			if ( ! $this->request['id'] )
			{
				$this->registry->output->showError( $this->lang->words['cf_noid'], 11811 );
			}
			
			$form['code']	= 'doedit';
			$form['button']	= $this->lang->words['field_complete_edit'];

			$fields 		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_cfields', 'where' => "cf_id=" . $this->request['id'] ) );
		}
		else
		{
			$form['code']	= 'doadd';
			$form['button']	= $this->lang->words['field_add_field'];
			
			$fields			= array();
		}

		//-----------------------------------------
		// Format...
		//-----------------------------------------

		/* Custom Fields Class */		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCustomFields.php', 'classCustomFields' );
		$cfields_class	= new $classToLoad( array() );
		
		$fields['cf_content'] 		= $cfields_class->formatContentForEdit($fields['cf_content'] );
		
		$form['cf_title']			= $this->registry->output->formInput("cf_title", $fields['cf_title'] );
		$form['cf_desc']			= $this->registry->output->formInput("cf_desc", $fields['cf_desc'] );
		$form['cf_type']			= $this->registry->output->formDropdown("cf_type",
																					  array(
																							   0 => array( 'text' , $this->lang->words['d_cfield_text'] ),
																							   1 => array( 'drop' , $this->lang->words['d_cfield_drop'] ),
																							   2 => array( 'area' , $this->lang->words['d_cfield_area'] ),
																						   ),
																			$fields['cf_type'] );
		$form['cf_max_input']		= $this->registry->output->formInput("cf_max_input", $fields['cf_max_input'] );
		$form['cf_input_format']	= $this->registry->output->formInput("cf_input_format", $fields['cf_input_format'] );
		$form['cf_content']			= $this->registry->output->formTextarea("cf_content", $fields['cf_content'] );
		$form['cf_not_null']		= $this->registry->output->formYesNo("cf_not_null", $fields['cf_not_null'] );
		$form['cf_topic']			= $this->registry->output->formYesNo("cf_topic", $fields['cf_topic'] );
		$form['cf_search']			= $this->registry->output->formYesNo("cf_search", $fields['cf_search'] );
		$form['cf_format']			= $this->registry->output->formTextarea("cf_format", $fields['cf_format'] );

		//-----------------------------------------
		// Apply to categories
		//-----------------------------------------
		
		$form['categories']			= "<select name='cats_apply[]' size='5' multiple='multiple'>\n";
		$cur						= $this->registry->getClass('categories')->getCatsCfield( $fields['cf_id'] );
		$opts						= $this->registry->getClass('categories')->catJumpList( 1, 'none', $cur );

		if( is_array($opts) AND count($opts) )
		{
			foreach( $opts as $cdata )
			{
				if( is_array($cur) AND in_array( $cdata[0], $cur ) )
				{
					$cdata[2] = " selected='selected'";
				}
				
				$form['categories'] .= "<option value='{$cdata[0]}'{$cdata[2]}>{$cdata[1]}</option>\n";
			}
		}
		
		$form['categories']			.= "</select>";

		$this->registry->output->html .= $this->html->cfieldsForm( $form, $fields );
	}
	
	/**
	 * Reorder custom fields
	 *
	 * @return	@e void
	 */
	protected function _doReorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
		$ajax			= new $classToLoad();
		
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
 		
 		if( is_array($this->request['cfields']) AND count($this->request['cfields']) )
 		{
 			foreach( $this->request['cfields'] as $this_id )
 			{
 				$this->DB->update( 'bitracker_cfields', array( 'cf_position' => $position ), 'cf_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->rebuildCache();

 		$ajax->returnString( 'OK' );
 		exit();
	}	

	/**
	 * Show the splash screen
	 *
	 * @return	@e void
	 */
	protected function _mainScreen()
	{
		/* Init vars */
		$rows = array();

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_cfields', 'order' => 'cf_position' ) );
		$this->DB->execute();
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch() )
			{
				$cat_ids 			= $this->registry->getClass('categories')->getCatsCfield( $r['cf_id'] );
				$r['categories'] 	= '';
				
				if( count($cat_ids) )
				{
					foreach( $cat_ids as $k => $v )
					{
						$r['categories'] .= "&middot; " . $this->registry->getClass('categories')->cat_lookup[ $v ]['cname'] ."<br />";
					}
				}
				else
				{
					$r['categories'] = $this->lang->words['cf_none'];
				}

				$rows[] = $r;
			}
		}
		
		$this->registry->output->html .= $this->html->cfieldsWrapper( $rows );
	}
}