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
 * @class		admin_bitracker_customize_mimetypes
 * @brief		IP.download Manager Mimetype Management
 */
class admin_bitracker_customize_mimetypes extends ipsCommand
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_mimetypes' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=customize&amp;section=mimetypes';
		$this->form_code_js	= $this->html->form_code_js	= 'module=customize&section=mimetypes';
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			// Mime-Type Stuff
			case 'types':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeStart();
			break;
			case 'mime_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeForm('add');
			break;
			case 'mime_doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeSave('add');
			break;
			case 'mime_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeForm('edit');
			break;
			case 'mime_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_delete' );
				$this->_mimeTypeDelete();
			break;
			case 'mime_doedit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeSave('edit');
			break;
			case 'mime_export':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeExport();
			break;
			case 'mime_import':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_mimeTypeImport();
			break;

			// Masks
			case 'mask_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mimes_delete' );
				$this->_deleteMask();
			break;
			case 'mask_add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_maskAdd();
			break;
			case 'do_mask_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_maskEdit();
			break;
			case 'mask_splash':
			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_mime_manage' );
				$this->_maskSplash();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();	
	}
	
	/**
	 * Add a new mask
	 *
	 * @return	@e void
	 */
	protected function _maskAdd()
	{
		$this->request['new_mask_name']	= trim($this->request['new_mask_name']);
		
		if ( !$this->request['new_mask_name'] )
		{
			$this->registry->output->showError( $this->lang->words['m_entername'], 11812 );
		}
		
		$copy_id = intval($this->request['new_mask_copy']);
		
		//-----------------------------------------
		// UPDATE DB
		//-----------------------------------------
		
		$this->DB->insert( 'bitracker_mimemask', array( 'mime_masktitle' => $this->request['new_mask_name'] ) );
		
		$new_id = $this->DB->getInsertId();
		
		if ( $copy_id )
		{
			$old_id = intval($copy_id);
			
			if ( $new_id && $old_id )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mime' ) );
				$get = $this->DB->execute();
				
				while( $r = $this->DB->fetch($get) )
				{
					$files = explode( ",", $r['mime_file'] );
					
					if( is_array( $files ) )
					{
						if( in_array( $old_id, $files ) )
						{
							$files[] = $new_id;
						}
					}

					$nfos = explode( ",", $r['mime_nfo'] );
					
					if( is_array( $nfos ) )
					{
						if( in_array( $old_id, $nfos ) )
						{
							$nfos[] = $new_id;
						}
					}
					
					$screenshots = explode( ",", $r['mime_screenshot'] );
					
					if( is_array( $screenshots ) )
					{
						if( in_array( $old_id, $screenshots ) )
						{
							$screenshots[] = $new_id;
						}
					}
					
					$inline = explode( ",", $r['mime_inline'] );
					
					if( is_array( $inline ) )
					{
						if( in_array( $old_id, $inline ) )
						{
							$inline[] = $new_id;
						}
					}
					
					$this->DB->update( "bitracker_mime", array( 'mime_file'			=> implode( ",", array_unique($files) ),
                                                                'mime_nfo'			=> implode( ",", array_unique($nfos) ),
																'mime_screenshot'	=> implode( ",", array_unique($screenshots) ),
																'mime_inline'		=> implode( ",", array_unique($inline) ) ), "mime_id='{$r['mime_id']}'" );
				}
			}
		}
		
		/* Recache and redirect */
		$this->rebuildCache();
		
		$this->registry->output->global_message = $this->lang->words['m_newmaskadded'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}
	
	/**
	 * Save edits to a mask
	 *
	 * @return	@e void
	 */
	protected function _maskEdit()
	{
		$this->request['new_mask_name'] = trim($this->request['new_mask_name']);
		
		if( $this->request['new_mask_name'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['m_entername'], 11813 );
		}
		
		if( !$this->request['mask_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11814 );
		}
		
		$id = intval($this->request['mask_id']);
		
		//-----------------------------------------
		// UPDATE DB
		//-----------------------------------------
		
		$this->DB->update( 'bitracker_mimemask', array( 'mime_masktitle' => $this->request['new_mask_name'] ), "mime_maskid=".$id );
		
		$this->rebuildCache();
		
		$this->request['id'] = $id;
				
		$this->_mimeTypeStart();
	}	
	
	/**
	 * Delete a mask
	 *
	 * @return	@e void
	 */
	protected function _deleteMask()
	{
		//-----------------------------------------
		// Check for a valid ID
		//-----------------------------------------
		
		if ( !$this->request['id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11815 );
		}
		
		$this->DB->delete( 'bitracker_mimemask', "mime_maskid=" . intval($this->request['id']) );
		
		$old_id = intval($this->request['id']);
		
		//-----------------------------------------
		// Remove from mime-types...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mime' ) );
		$get = $this->DB->execute();
		
		while( $r = $this->DB->fetch($get) )
		{
			$new_files	= array();
			$files		= explode( ",", $r['mime_file'] );
					
			if( is_array( $files ) )
			{
				foreach( $files as $k => $v )
				{
					if( $v != $old_id )
					{
						$new_files[] = $v;
					}
				}
			}

			$new_nfos	= array();
			$nfos		= explode( ",", $r['mime_nfo'] );

			if( is_array( $nfos ) )
			{
				foreach( $nfos as $k => $v )
				{
					if( $v != $old_id )
					{
						$new_nfos[] = $v;
					}
				}
			}
			
			$new_screenshots	= array();	
			$screenshots		= explode( ",", $r['mime_screenshot'] );
					
			if( is_array( $screenshots ) )
			{
				foreach( $screenshots as $k => $v )
				{
					if( $v != $old_id )
					{
						$new_screenshots[] = $v;
					}
				}
			}
			
			$new_inline	= array();
			$inline		= explode( ",", $r['mime_inline'] );
					
			if( is_array( $inline ) )
			{
				foreach( $inline as $k => $v )
				{
					if( $v != $old_id )
					{
						$new_inline[] = $v;
					}
				}
			}			
					
			$this->DB->update( "bitracker_mime", array( 'mime_file'			=> implode( ",", array_unique($new_files) ),
                                                         'mime_nfo'			=> implode( ",", array_unique($new_nfos) ),
															'mime_screenshot'	=> implode( ",", array_unique($new_screenshots) ),
															'mime_inline'		=> implode( ",", array_unique($new_inline) ) )
													, "mime_id='{$r['mime_id']}'" );

		}
		
		/* Recache and redirect */
		$this->rebuildCache();
		
		$this->registry->output->global_message = $this->lang->words['m_newmaskdeleted'];
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}	
	
	/**
	 * Mimetype masks splash screen
	 *
	 * @return	@e void
	 */
	protected function _maskSplash()
	{
		$rows	= array();
		$dlist	= array();
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mimemask', 'order' => 'mime_masktitle' ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			/* Save our option for the DD list */
			$dlist[] = array( $r['mime_maskid'], $r['mime_masktitle'] );
			
			/* Sort some stuff */
			$r['categories']	= '';
			$cat_ids			= $this->registry->getClass('categories')->getCatsMimemask( $r['mime_maskid'] );
			$r['_delete']		= false;
			
			/* This mask is in use? */
			if( is_array($cat_ids) && count($cat_ids) )
			{
				foreach( $cat_ids as $k => $v )
				{
					$r['categories'] .= "&middot; " . $this->registry->getClass('categories')->cat_lookup[ $v ]['cname'] ."<br />";
				}
			}
			else
			{
				$r['categories'] = $this->lang->words['m_none'];
				$r['_delete']    = true;
			}

			$rows[] = $r;
		}
		
		$this->registry->output->html .= $this->html->masksWrapper( $rows, $dlist );
	}
	
	/**
	 * Import mimetypes
	 *
	 * @return	@e void
	 */
	protected function _mimeTypeImport()
	{
		$content = $this->registry->getClass('adminFunctions')->importXml( 'bit_mimetypes.xml' );

		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->registry->output->global_message = $this->lang->words['m_uploadfail'];
			$this->_mimeTypeStart();
			return;
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		//-----------------------------------------
		// Get current mime types
		//-----------------------------------------
		
		$types = array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mime', 'order' => "mime_extension" ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$types[ $r['mime_extension'] ] = 1;
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->fetchElements('mimetype') as $mimetype )
		{
			$entry  = $xml->fetchElementsFromRecord( $mimetype );

			$insert_array = array( 'mime_extension' 	=> $entry['mime_extension'],
								   'mime_mimetype'  	=> $entry['mime_mimetype'],
								   'mime_file'      	=> 0,
								   'mime_nfo'      	    => 0,
								   'mime_screenshot'    => 0,
								   'mime_inline'		=> 0,
								   'mime_img'       	=> $entry['mime_img']
								 );
			
			if ( $types[ $entry['mime_extension'] ] )
			{
				continue;
			}
			
			if ( $entry['mime_extension'] and $entry['mime_mimetype'] )
			{
				$this->DB->insert( 'bitracker_mime', $insert_array );
			}
		}
		
		$this->rebuildCache();
                    
		$this->registry->output->global_message = $this->lang->words['m_trackxml'];
		
		$this->_mimeTypeStart();
	}
	
	/**
	 * Export mimetypes
	 *
	 * @return	@e void
	 */
	protected function _mimeTypeExport()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classXML.php', 'classXML' );
		$xml			= new $classToLoad( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------

		$xml->newXMLDocument();
		$xml->addElement( 'mimetypesexport' );
		$xml->addElement( 'mimetypesgroup', 'mimetypesexport' );
		
		//-----------------------------------------
		// Get group
		//-----------------------------------------

		$this->DB->build( array('select'	=> 'mime_extension,mime_mimetype,mime_img',
								'from'	=> 'bitracker_mime',
								'order'	=> "mime_extension"
						)		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$xml->addElementAsRecord( 'mimetypesgroup', 'mimetype', $r );
		}
		
		$xmlData = $xml->fetchDocument();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->registry->output->showtrack( $xmlData, 'bit_mimetypes.xml' );
	}
	
	/**
	 * Delete a mimetype
	 *
	 * @return	@e void
	 */
	protected function _mimeTypeDelete()
	{
		$this->DB->delete( 'bitracker_mime', 'mime_id=' . intval($this->request['mid']) );
		
		$this->rebuildCache();
		
		$this->registry->output->global_message = $this->lang->words['m_deleted'];
		
		$this->_mimeTypeStart();
	}
	
	/**
	 * Save a mimetype
	 *
	 * @param	string		$type		type [add|edit]
	 * @return	@e void
	 */
	protected function _mimeTypeSave( $type='add' )
	{
		$this->request['mid'] = intval($this->request['mid']);
		
		//-----------------------------------------
		// Check basics
		//-----------------------------------------
		
		if ( ! $this->request['mime_extension'] or ! $this->request['mime_mimetype'] )
		{
			$this->registry->output->global_message = $this->lang->words['m_mustenter'];
			$this->_mimeTypeForm( $type );
		}
		
		$save_array = array( 'mime_extension' 	=> ( substr( $this->request['mime_extension'], 0, 1 ) == '.' ) ? ( substr( $this->request['mime_extension'], 1 ) ) : $this->request['mime_extension'],
							 'mime_mimetype'  	=> $this->request['mime_mimetype'],
							 'mime_file'      	=> $this->request['mime_file'] == 1 ? intval($this->request['id']) : 0,
							 'mime_nfo'      	=> $this->request['mime_nfo'] == 1 ? intval($this->request['id']) : 0,
							 'mime_screenshot'  => $this->request['mime_screenshot'] == 1 ? intval($this->request['id']) : 0,
							 'mime_inline'		=> $this->request['mime_inline'] == 1 ? intval($this->request['id']) : 0,
							 'mime_img'       	=> $this->request['mime_img']
						   );
		
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Check for existing..
			//-----------------------------------------
			
			$mime = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mime', 'where' => "mime_extension='" . $save_array['mime_extension'] . "'" ) );
			
			if ( $mime['mime_id'] )
			{
				$this->registry->output->global_message = "The extension '{$save_array['mime_extension']}' already exists, please choose another extension.";
				$this->_mimeTypeForm($type);
			}
			
			$this->DB->insert( 'bitracker_mime', $save_array );
			
			$this->registry->output->global_message = $this->lang->words['m_added'];
		}
		else
		{
			$r = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mime', 'where' => "mime_extension='" . $save_array['mime_extension'] . "'" ) );

			$new_files	= array();
			$files		= explode( ",", $r['mime_file'] );
					
			if( is_array( $files ) )
			{
				foreach( $files as $k => $v )
				{
					// Blank cats cause problems?
					if( !$v )
					{
						continue;
					}
					
					if( $v == $this->request['id'] )
					{
						if( $this->request['mime_file'] != 1 )
						{
							continue;
						}
					}
					
					$new_files[] = $v;
				}
			}
						
			if( $this->request['mime_file'] == 1 )
			{
				$new_files[] = intval($this->request['id']);
			}
			
			if( count($new_files) < 1 )
			{
				$new_files[] = 0;
			}

			$new_nfos	= array();
			$nfos		= explode( ",", $r['mime_nfo'] );
					
			if( is_array( $nfos ) )
			{
				foreach( $nfos as $k => $v )
				{
					// Blank cats cause problems?
					if( !$v )
					{
						continue;
					}
					
					if( $v == $this->request['id'] )
					{
						if( $this->request['mime_nfo'] != 1 )
						{
							continue;
						}
					}
					
					$new_nfos[] = $v;
				}
			}
						
			if( $this->request['mime_nfo'] == 1 )
			{
				$new_nfos[] = intval($this->request['id']);
			}
			
			if( count($new_nfos) < 1 )
			{
				$new_nfos[] = 0;
			}			

			$new_screenshots	= array();
			$screenshots		= explode( ",", $r['mime_screenshot'] );
					
			if( is_array( $screenshots ) )
			{
				foreach( $screenshots as $k => $v )
				{
					if( !$v )
					{
						continue;
					}
					
					if( $v == $this->request['id'] )
					{
						if( $this->request['mime_screenshot'] != 1 )
						{
							continue;
						}
					}
					
					$new_screenshots[] = $v;
				}
			}
						
			if( $this->request['mime_screenshot'] == 1 )
			{
				$new_screenshots[] = intval($this->request['id']);
			}
			
			if( count($new_screenshots) < 1 )
			{
				$new_screenshots[] = 0;
			}
			
			$new_inline	= array();
			$inline		= explode( ",", $r['mime_inline'] );
					
			if( is_array( $inline ) )
			{
				foreach( $inline as $k => $v )
				{
					if( !$v )
					{
						continue;
					}
					
					if( $v == $this->request['id'] )
					{
						if( $this->request['mime_inline'] != 1 )
						{
							continue;
						}
					}
					
					$new_inline[] = $v;
				}
			}
						
			if( $this->request['mime_inline'] == 1 )
			{
				$new_inline[] = intval($this->request['id']);
			}
			
			$save_array['mime_file'] 		= implode( ",", array_unique($new_files) );
			$save_array['mime_nfo'] 		= implode( ",", array_unique($new_nfos) );
			$save_array['mime_screenshot']	= implode( ",", array_unique($new_screenshots) );
			$save_array['mime_inline']		= implode( ",", array_unique($new_inline) );
			
			$this->DB->update( 'bitracker_mime', $save_array, 'mime_id=' . $this->request['mid'] );
			
			$this->registry->output->global_message = $this->lang->words['m_edited'];
		}
		
		$this->rebuildCache();
		
		$this->_mimeTypeStart();
	}
	
	/**
	 * Mimetype form
	 *
	 * @param	string		$type		type [add|edit]
	 * @return	@e void
	 */
	protected function _mimeTypeForm( $type='add' )
	{
		$this->request['mid']		= intval($this->request['mid']);
		$this->request['baseon']	= intval($this->request['baseon']);
		
		$form			= array();
		$mime			= array();
		
		$form['baseon']	= '';
		
		if ( $type == 'add' )
		{
			$form['code']	= 'mime_doadd';
			$form['button']	= $this->lang->words['d_addnewtype'];
			
			if ( $this->request['baseon'] )
			{
				$mime = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mime', 'where' => 'mime_id='.$this->request['baseon'] ) );

				$files 					 = explode( ",", $mime['mime_file'] );
				$mime['mime_file'] 		 = in_array( $this->request['id'], $files ) ? 1 : 0;

				$nfos 					 = explode( ",", $mime['mime_nfo'] );
				$mime['mime_nfo'] 		 = in_array( $this->request['id'], $nfos ) ? 1 : 0;

				$screenshots 			 = explode( ",", $mime['mime_screenshot'] );
				$mime['mime_screenshot'] = in_array( $this->request['id'], $screenshots ) ? 1 : 0;

				$inline 				 = explode( ",", $mime['mime_inline'] );
				$mime['mime_inline'] 	 = in_array( $this->request['id'], $inline ) ? 1 : 0;								
			}
			
			/* Generate 'base on' */
			$_baseOn = array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mime', 'order' => 'mime_extension' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$_baseOn[] = array( $r['mime_id'], $r['mime_extension'] );
			}
			
			$form['baseon'] = $this->registry->output->formDropdown( 'baseon', $_baseOn, $this->request['baseon'] );
		}
		else
		{
			$form['code']	= 'mime_doedit';
			$form['button']	= $this->lang->words['d_edittype'];

			$mime = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_mime', 'where' => 'mime_id='.$this->request['mid'] ) );
		
			if ( ! $mime['mime_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['m_noid'];
				$this->_mimeTypeStart();
				return;
			}
			
			$files 					 = explode( ",", $mime['mime_file'] );
			$mime['mime_file'] 		 = in_array( $this->request['id'], $files ) ? 1 : 0;

			$nfos 					 = explode( ",", $mime['mime_nfo'] );
			$mime['mime_nfo'] 		 = in_array( $this->request['id'], $nfos ) ? 1 : 0;

			$screenshots 			 = explode( ",", $mime['mime_screenshot'] );
			$mime['mime_screenshot'] = in_array( $this->request['id'], $screenshots ) ? 1 : 0;

			$inline 				 = explode( ",", $mime['mime_inline'] );
			$mime['mime_inline'] 	 = in_array( $this->request['id'], $inline ) ? 1 : 0;
		}

		//-----------------------------------------
		// FORM
		//-----------------------------------------
		
		$form['mime_extension']		= $this->registry->output->formSimpleInput( 'mime_extension', $_POST['mime_extension'] ? $_POST['mime_extension'] : $mime['mime_extension'], 10 );
		$form['mime_type']			= $this->registry->output->formSimpleInput( 'mime_mimetype', $_POST['mime_mimetype'] ? $_POST['mime_mimetype'] : $mime['mime_mimetype'], 40 );
		$form['mime_file']			= $this->registry->output->formYesNo( 'mime_file', $_POST['mime_file'] ? $_POST['mime_file'] : $mime['mime_file'] );
		$form['mime_nfo']			= $this->registry->output->formYesNo( 'mime_nfo', $_POST['mime_nfo'] ? $_POST['mime_nfo'] : $mime['mime_nfo'] );
		$form['mime_screenshot']	= $this->registry->output->formYesNo( 'mime_screenshot', $_POST['mime_screenshot'] ? $_POST['mime_screenshot'] : $mime['mime_screenshot'] );
		$form['mime_inline']		= $this->registry->output->formYesNo( 'mime_inline', $_POST['mime_inline'] ? $_POST['mime_inline'] : $mime['mime_inline'] );
		$form['mime_img']			= $this->registry->output->formSimpleInput( 'mime_img', $_POST['mime_img'] ? $_POST['mime_img'] : $mime['mime_img'], 40 );
		
		$this->registry->output->html .= $this->html->mimeForm( $form, $mime );
	}
	
	/**
	 * Mimetypes overview screen
	 *
	 * @return	@e void
	 */
	protected function _mimeTypeStart()
	{
		/* Init vars */
		$mimeRows = array();
		
		$mask = $this->DB->buildAndFetch( array( 'select' => 'mime_masktitle',
												 'from'   => 'bitracker_mimemask',
												 'where'  => 'mime_maskid=' . intval($this->request['id'])
										 )		);
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_mime', 'order' => 'mime_extension' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$files 					= explode( ",", $r['mime_file'] );
			$r['mime_file'] 		= in_array( $this->request['id'], $files ) ? 1 : 0;

			$nfos 					= explode( ",", $r['mime_nfo'] );
			$r['mime_nfo'] 		= in_array( $this->request['id'], $nfos ) ? 1 : 0;

			$screenshots 			= explode( ",", $r['mime_screenshot'] );
			$r['mime_screenshot'] 	= in_array( $this->request['id'], $screenshots ) ? 1 : 0;

			$inline 				= explode( ",", $r['mime_inline'] );
			$r['mime_inline'] 	 	= in_array( $this->request['id'], $inline ) ? 1 : 0;
						
			$mimeRows[] = $r;
		}

		$this->registry->output->html .= $this->html->mimesWrapper( $mimeRows, $mask );
	}
	
	/**
	 * Mimetypes overview screen
	 *
	 * @return	@e void
	 */
	public function rebuildCache()
	{
		$cache = array();
			
		$this->DB->build( array( 'select' => 'mime_id,mime_extension,mime_mimetype,mime_file,mime_nfo,mime_screenshot,mime_inline,mime_img', 'from' => 'bitracker_mime' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$cache[ $r['mime_extension'] ] = $r;
		}
		
		$this->cache->setCache( 'bit_mimetypes', $cache, array( 'array' => 1 ) );
	}
}