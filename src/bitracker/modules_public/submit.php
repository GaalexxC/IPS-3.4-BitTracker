<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit main posting library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_post_submit extends ipsCommand
{
	/**
	 * Stored temporary output
	 *
	 * @var 	string 				Page output
	 */
	protected $output			= "";

	/**
	 * Error message encountered
	 *
	 * @var 	string
	 */
	protected $error_message	= '';

	/**
	 * Like object
	 *
	 * @var	object
	 */
	protected $_like;

	/**
	 * Paid file message
	 *
	 * @var	string
	 */
	protected $paid_file_message	= '';

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------
		// Do we have access?
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['view']) == 0 )
		{
			$this->registry->output->showError( 'no_bitracker_permissions', 108107, null, null, 403 );
		}
		
		//-------------------------------------------
		// Page title and navigation bar
		//-------------------------------------------
		
		$this->registry->output->addNavigation( IPSLib::getAppTitle('bitracker'), 'app=bitracker', 'false', 'app=bitracker' );
		
		//-----------------------------------------
		// Nexus
		//-----------------------------------------
		
		$this->paid_file_message = '';

		if ( IPSLib::appIsInstalled('nexus') AND $this->settings['bit_nexus_on'] AND $this->memberData['bit_add_paid'] )
		{
			if ( $this->settings['bit_nexus_percent'] and $this->settings['bit_nexus_transfee'] )
			{
				$this->paid_file_message	= sprintf( $this->lang->words['file_cost_desc_both'], $this->settings['bit_nexus_percent'], $this->lang->formatMoney( $this->settings['bit_nexus_transfee'], FALSE ) );
			}
			elseif ( $this->settings['bit_nexus_percent'] )
			{
				$this->paid_file_message	= sprintf( $this->lang->words['file_cost_desc_percent'], $this->settings['bit_nexus_percent'] );
			}
			elseif ( $this->settings['bit_nexus_transfee'] )
			{
				$this->paid_file_message	= sprintf( $this->lang->words['bit_nexus_transfee'], $this->lang->formatMoney( $this->settings['bit_nexus_transfee'], FALSE ) );
			}
		}
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('bitrackerTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'bitrackerTags', classes_tags_bootstrap::run( 'bitracker', 'files' ) );
		}

		//-----------------------------------------
		// Is member blocked from submitting?
		//-----------------------------------------
		
		if( $this->memberData['_cache']['block_file_submissions'] )
		{
			$this->registry->output->showError( 'no_addtorrent_permissions', 108199, null, null, 403 );
		}
		
		//-------------------------------------------
		// Then what we doing?
		//-------------------------------------------

		switch( $this->request['do'] )
		{
			case 'add_cont':
				$this->_continueForm( 'new' );
			break;
			
			case 'edit_main':
				$this->_continueForm( 'edit' );
			break;
				
			case 'edit_cat':
				$this->_startForm( 'edit' );
			break;
				
			case 'add_comp':
				$this->_mainSave( 'new' );
			break;
				
			case 'edit_comp':
				$this->_mainSave( 'edit' );
			break;

			case 'add_start':
			default:
				$this->_startForm( 'new' );
			break;
		}
		
		//-------------------------------------------
		// Print output
		//-------------------------------------------

        $this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Choose the category
	 *
	 * @param	string		$type		Type of form[new|edit]
	 * @param	string		$error		Error to show in the form
	 * @return	@e void
	 */	
	protected function _startForm( $type='new', $error='' )
	{
		//-------------------------------------------
		// New or edit?
		//-------------------------------------------
		
		if( $type == 'edit' )
		{
			$file_id = intval($this->request['id']);
			
			$file = $this->DB->buildAndFetch( array( 'select' 	=> 'file_id, file_cat, file_name, file_submitter, file_name_furl',
											 		 'from'		=> 'bitracker_files',
											 		 'where'	=> 'file_id=' . $file_id
											)		);

			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanedit', 'bit_allow_edit' ) )
			{
				$this->registry->output->showError( 'not_your_file', 108107, null, null, 403 );
			}

			$file['code'] = 'edit_main';
		}
		else
		{
			$file = array( 'code' => 'add_cont' );
		}
		
		//-------------------------------------------
		// Grab categories for dropdown
		//-------------------------------------------
		
		$file['default_category'] = $this->request['cid'] ? intval($this->request['cid']) : ( $file['file_cat'] ? $file['file_cat'] : 0 );
			
		if( count($this->registry->getClass('categories')->member_access['add']) == 0 )
		{
			if( $type == 'new' )
			{
				$this->registry->output->showError( 'no_addtorrent_permissions', 108108, null, null, 403 );
			}
			else
			{
				$file['categories'] = array( $file['file_cat'], $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ]['cname'] );
			}
		}
		else
		{
			$file['categories'] = $this->registry->getClass('categories')->catJumpList( true, 'add' );
		}

		//-------------------------------------------
		// And output
		//-------------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_submit')->submissionStart( $file, $error );
		
		$this->registry->output->addNavigation( $this->lang->words['file_submit_nav_header'], '' );
		
		$this->registry->output->setTitle( $this->lang->words['file_submit_nav_header'] . ' - ' . $this->settings['board_name'] );
	}

	/**
	 * Main file information submission page
	 *
	 * @param	string		$type		Type of form [new|edit]
	 * @return	@e void
	 */	
	protected function _continueForm( $type='new' )
	{
		//-------------------------------------------
		// If this is new file, make sure we have cat
		//-------------------------------------------
		
		if( $type == 'new' )
		{
			$catid = intval($this->request['file_cat']);

			if( !$catid )
			{
				$this->_startForm( $type, $this->lang->words['no_category_selected'] );
				return;
			}
		}
		
		$links	= array();
		
		//-------------------------------------------
		// Are we editing?
		//-------------------------------------------
		
		if( $type == 'edit' )
		{
			$file_id = intval($this->request['id']);
			
			$file = $this->DB->buildAndFetch( array( 'select' 	=> '*',
												 	 'from'		=> 'bitracker_files',
												 	 'where'	=> 'file_id=' . $file_id
											)		);

			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanedit', 'bit_allow_edit' ) )
			{
				$this->registry->output->showError( 'not_your_file', 108109, null, null, 403 );
			}
			
			//-----------------------------------------
			// Revision or edit?
			//-----------------------------------------
			
			if( !$this->memberData['bit_bypass_revision'] )
			{
				$this->request['bypass_revision']	= 0;
			}
			
			//-------------------------------------------
			// We've been authorized, set some form data
			//-------------------------------------------
			
			$file['code']			= 'edit_comp';
			$file['button']			= $this->lang->words['edit_button'];
			$file['header_lang']	= $this->lang->words['sform_editfile_header'];

			//-------------------------------------------
			// And set category
			//-------------------------------------------
			
			$catid = $this->request['file_cat'] ? $this->request['file_cat'] : $file['file_cat'];
			
			//-----------------------------------------
			// Sort out default content
			//-----------------------------------------

			if( $this->request['do'] == 'edit_main' )	// Only do this when form initially loads
			{
				$this->DB->delete( 'bitracker_temp_records', "record_post_key='{$file['file_post_key']}' AND record_file_id > 0" );
	
				$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_post_key='{$file['file_post_key']}' AND record_backup=0" ) );
				$outer	= $this->DB->execute();
				
				while( $r = $this->DB->fetch($outer) )
				{
					if( $r['record_type'] == 'link' )
					{
						$links['files'][ $r['record_location'] ]	= array( $r['record_location'], $r['record_link_type'] );
					}
					elseif( $r['record_type'] == 'nfolink' )
					{
						$links['nfo'][ $r['record_location'] ]		= array( $r['record_location'], $r['record_link_type'] );
					}
					elseif( $r['record_type'] == 'sslink' )
					{
						$links['ss'][ $r['record_location'] ]		= array( $r['record_location'], $r['record_default'] );
					}
					else
					{
						$monthly		= '';
						$newLocation	= $r['record_location'];
						
						if( $r['record_storagetype'] == 'disk' )
						{
							$newLocation	= $this->registry->bitFunctions->getFileName( preg_replace( "/[^\w\.]/", '-', $r['record_realname'] ) );
							$newExt			= strtolower( str_replace( ".", "", substr( $r['record_location'], strrpos( $r['record_location'], '.' ) ) ) );
							
							$newLocation	= md5( uniqid( microtime() ) ) . '-' . $newLocation . '.' . $newExt;

							if( $r['record_type'] == 'upload' )
							{
								$monthly		= $this->registry->bitFunctions->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) );
								
								@copy( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $r['record_location'], str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $monthly . $newLocation );
							}
                            elseif( $r['record_type'] == 'nfoupload' ){

								$monthly		= $this->registry->bitFunctions->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) );
								
								@copy( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . '/' . $r['record_location'], str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . '/' . $monthly . $newLocation );
                            }
							else
							{
								$monthly		= $this->registry->bitFunctions->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) );
								
								@copy( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $r['record_location'], str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $monthly . $newLocation );
							}
						}
						
						$temp	= array(
										'record_post_key'	=> $r['record_post_key'],
										'record_file_id'	=> $r['record_file_id'],
										'record_location'	=> $monthly . $newLocation,
										'record_realname'	=> $r['record_realname'],
										'record_mime'		=> $r['record_mime'],
										'record_size'		=> $r['record_size'],
										'record_added'		=> time(),
										'record_default'	=> intval($r['record_default']),
										);

                   //------------------------------------------------------
		           // check the record_type and add it to the $temp array
		           //------------------------------------------------------

                      if ($r['record_type'] == 'upload'){
                             $temp['record_type'] = 'files';

                      }elseif ($r['record_type'] == 'nfoupload'){
                             $temp['record_type'] = 'nfo';

                      }else{
                             $temp['record_type'] = 'ss';
                      }    
		
						$this->DB->insert( 'bitracker_temp_records', $temp );
					}
					
					if( $r['record_storagetype'] == 'db' )
					{
						$storage	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_filestorage', 'where' => 'storage_id=' . $r['record_db_id'] ) );
						
						if( $r['record_type'] == 'upload' )
						{
							file_put_contents( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $r['record_location'], base64_decode( $storage['storage_file'] ) );
						}
                        elseif( $r['record_type'] == 'nfoupload' ){

  							file_put_contents( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . '/' . $r['record_location'], base64_decode( $storage['storage_nfo'] ) );                           
                        }
						else
						{
							file_put_contents( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $r['record_location'], base64_decode( $storage['storage_ss'] ) );
						}
					}

					if( $r['record_storagetype'] == 'ftp' )
					{
						if( $this->settings['bit_remoteurl'] AND
							$this->settings['bit_remoteport'] AND
							$this->settings['bit_remoteuser'] AND
							$this->settings['bit_remotepass'] AND
							$this->settings['bit_remotefilepath'] )
						{
							$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFtp.php', 'classFtp' );
							
							try
							{
								classFtp::$transferMode	= FTP_BINARY;

								$_ftpClass		= new $classToLoad( $this->settings['bit_remoteurl'], $this->settings['bit_remoteuser'], $this->settings['bit_remotepass'], $this->settings['bit_remoteport'], '/', true, 999999 );

								if( $r['record_type'] == 'upload' )
								{
									$_ftpClass->chdir( $this->settings['bit_remotefilepath'] );
									$_ftpClass->file( $r['record_location'] )->download( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $monthly . $newLocation );
								}
								else
								{
									$_ftpClass->chdir( $this->settings['bit_remotesspath'] );
									$_ftpClass->file( $r['record_location'] )->download( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $monthly . $newLocation );
								}
							}
							catch( Exception $e )
							{
							}
						}
					}
				}
			}
		}
		
		//-------------------------------------------
		// This is a new file
		//-------------------------------------------
		
		else
		{
			$file = array( 	'code' 				=> 'add_comp',
							'button' 			=> $this->lang->words['add_button'],
							'header_lang'		=> $this->lang->words['sform_addtorrent_header'],
							'file_name'			=> $this->request['file_name'],
							'file_desc'			=> $_POST['Post'],
							'file_changelog'	=> $this->request['file_changelog'],
							'file_version'		=> $this->request['file_version'],
							'file_post_key'		=> $this->request['post_key'] ? $this->request['post_key'] : md5( uniqid( microtime(), true ) ),
						);
		}

		$category = $this->registry->getClass('categories')->cat_lookup[ $catid ];
		
		//-----------------------------------------
		// Clean/set linked file types
		//-----------------------------------------
		
		$this->settings['bit_linked_types']	= explode( "\n", str_replace( "\r", '', $this->settings['bit_linked_types'] ) );
		
		//-------------------------------------------
		// And....more perm checking
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['add']) == 0 )
		{
			if( $type == 'new' )
			{
				$this->registry->output->showError( 'no_addtorrent_permissions', 108110, null, null, 403 );
			}
		}
		else if( !in_array( $catid, $this->registry->getClass('categories')->member_access['add'] ) )
		{
			$_showError	= true;
			
			if( $type == 'edit' )
			{
				if( $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanedit' ) )
				{
					$_showError	= false;
				}
			}
			
			if( $_showError )
			{
				if( $category['coptions']['opt_noperm_add'] )
				{
					$this->registry->output->showError( $category['coptions']['opt_noperm_add'], 108111, null, null, 403 );
				}
				else
				{
					$this->registry->output->showError( 'no_addthiscat_permissions', 108112, null, null, 403 );
				}
			}
		}
		
		//-----------------------------------------
		// Anymore links?
		//-----------------------------------------
		
		if( is_array($this->request['file_url']) AND count($this->request['file_url']) )
		{
			foreach( $this->request['file_url'] as $k => $url )
			{
				if( $url )
				{
					$links['files'][ $url ]	= array( $url, $this->request['file_url_type'][ $k ] );
				}
			}
		}

		if( is_array($this->request['file_nfourl']) AND count($this->request['file_nfourl']) )
		{
			foreach( $this->request['file_nfourl'] as $k => $url )
			{
				if( $url )
				{
					$links['nfo'][ $url ]	= array( $url, $this->request['file_url_type'][ $k ] );
				}
			}
		}
		
		if( is_array($this->request['file_ssurl']) AND count($this->request['file_ssurl']) )
		{
			foreach( $this->request['file_ssurl'] as $k => $url )
			{
				if( $url )
				{
					$links['ss'][ $url ]	= array( $url );
				}
			}
		}

		//-----------------------------------------
		// Set Allowed mime-types for all the files
		//-----------------------------------------
	
		$file['allowed_file']		= "";
		$file['allowed_nfo']		= "";
		$file['allowed_ss']			= "";
		$types						= $this->registry->getClass('bitFunctions')->getAllowedTypes( $category );

		natcasesort($types['files']);
		natcasesort($types['nfo']);
		natcasesort($types['ss']);

		$file['allowed_file']	= implode( ", ", $types['files'] );
		$file['allowed_nfo']	= implode( ", ", $types['nfo'] );
		$file['allowed_ss']		= implode( ", ", $types['ss'] );
		
		//-----------------------------------------
		// Set some form defaults
		//-----------------------------------------
		
		$file['file_name']		= $this->request['file_name'] ? $this->request['file_name'] : $file['file_name'];
		$file['file_desc']		= $_POST['Post'] ? $_POST['Post'] : $file['file_desc'];
		$file['file_version']	= $this->request['file_version'] ? $this->request['file_version'] : $file['file_version'];
		$file['file_changelog']	= $this->request['file_changelog'] ? IPSText::br2nl( $this->request['file_changelog'] ) : $file['file_changelog'];

		$file['can_post_links']	= $this->registry->getClass('bitFunctions')->canSubmitLinks();
		$file['can_post_paths']	= $this->registry->getClass('bitFunctions')->canSubmitPaths();

		/* Show description in editor, get editor */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$editor->setAllowBbcode( $category['coptions']['opt_bbcode'] ? true : false );
		$editor->setAllowSmilies( true );
		$editor->setAllowHtml( $category['coptions']['opt_html'] ? true : false );
		
		/* Set content in editor */
		$file['_editor'] = $editor->show( 'Post', array(), $file['file_desc'] );
		
		//-----------------------------------------
		// Get custom fields
		//-----------------------------------------
		
		if( $category['ccfields'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/cfields.php', 'bit_customFields', 'bitracker' );
    		$fields				= new $classToLoad( $this->registry );
    		
    		if( strpos( $this->request['do'], '_comp' ) !== false )
    		{
    			$fields->file_data	= $this->request;
    		}
    		
    		$fields->file_id	= $file['file_id'];
    		$fields->cat_id		= $category['ccfields'];
    		$fields->cache_data	= $this->cache->getCache('bit_cfields');
    	
    		$fields->init_data( 'edit' );
    		$fields->parseToEdit();
		}


		//-----------------------------------------
		// Force a form action?
		//-----------------------------------------
		
		$is_reset	= false;
		
		if( $this->settings['upload_domain'] )
		{
			$is_reset	= true;
			$original	= $this->settings['base_url'];
			
			$this->settings['base_url'] = $this->settings['upload_domain'] . '/index.php?';
		}
		
		//-----------------------------------------
		// Nexus
		//-----------------------------------------
				
		$nexusPackages		= '';
		$file['nexus']		= '';
		
		if ( IPSLib::appIsInstalled('nexus') AND $this->settings['bit_nexus_on'] AND $this->memberData['bit_add_paid'] )
		{
			if( $this->memberData['g_access_cp'] )
			{
				$nexusPackages = ipsRegistry::getAppClass( 'nexus' )->getPackageSelector( NULL, TRUE, array(), explode( ',', $file['file_nexus'] ) );
			}
			
			if( $this->request['file_cost_type'] )
			{
				$file['nexus']	= $this->request['file_cost_type'] == 'package' ? 'package' : ( $this->request['file_cost_type'] == 'cost' ? 'paid' : 'free' );
			}
			else
			{
				$file['nexus']	= $file['file_nexus'] ? 'package' : ( $file['file_cost'] ? 'paid' : 'free' );
			}
			
			$file['file_cost']		= $this->request['file_cost'] ? $this->request['file_cost'] : $file['file_cost'];
			$file['renewals']		= $this->request['renewals'] ? $this->request['renewals'] : ( !is_null($file['file_renewal_term']) ? 2 : 1 );
			$file['renewal_term']	= $this->request['renewal_term'] ? $this->request['renewal_term'] : $file['file_renewal_term'];
			$file['renewal_units']	= $this->request['renewal_units'] ? $this->request['renewal_units'] : $file['file_renewal_units'];
			$file['renewal_price']	= $this->request['renewal_price'] ? $this->request['renewal_price'] : $file['file_renewal_price'];
		}
		
		//-----------------------------------------
		// Tagging
		//-----------------------------------------

		$file['_tagBox']	= '';

		if( $type == 'edit' )
		{
			$where = array( 'meta_id'		 => $file['file_id'],
						    'meta_parent_id' => $file['file_cat'],
						    'member_id'	     => $file['file_submitter']
							);

			if ( $_REQUEST['ipsTags'] )
			{
				$where['existing_tags']	= explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) );
			}
		
			if ( $this->registry->bitrackerTags->can( 'edit', $where ) )
			{
				$file['_tagBox'] = $this->registry->bitrackerTags->render( 'entryBox', $where );
			}
		}
		else
		{
			$where = array( 'meta_parent_id'	=> $this->request['file_cat'],
							'member_id'			=> $this->memberData['member_id'],
							'existing_tags'		=> explode( ',', IPSText::cleanPermString( $_REQUEST['ipsTags'] ) )
							);
			
			if ( $this->registry->bitrackerTags->can( 'add', $where ) )
			{
				$file['_tagBox'] = $this->registry->bitrackerTags->render( 'entryBox', $where );
			}
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_submit')->mainSubmitForm( $type, $file, $links, $category, $this->request['preview'] ? '' : $this->error_message, $fields, $this->paid_file_message, $nexusPackages );

		//-----------------------------------------
		// Reset forced form action?
		//-----------------------------------------

		
		if( $is_reset )
		{
			$this->settings['base_url'] = $original;
		}
		
		/* Navigation */
		foreach( $this->registry->getClass('categories')->getNav( $category['cid'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
		}
		
		if( $type == 'new' )
		{
			$title = $this->lang->words['sform_filefile'];
		}
		else
		{
			$this->registry->output->addNavigation( $file['file_name'], 'app=bitracker&amp;showfile='.$file['file_id'], $file['file_name_furl'], 'bitshowfile' );
			
			/* Edit or update? */
			$title = $this->request['bypass_revision'] ? $this->lang->words['editfile'] : $this->lang->words['newversion'];
		}
		
		$this->registry->output->addNavigation( $title );
		
		$this->registry->output->setTitle( $title . ' - ' . $this->settings['board_name'] );
	}
	
	/**
	 * Save an added or edited file
	 *
	 * @param	string		[new|edit]
	 * @return	@e void
	 */	
	protected function _mainSave( $type='new' )
	{
		/* Security Check */
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10799, null, null, 403 );
		}

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$_POST['Post'] 	= IPSText::stripslashes( $_POST['Post'] );
		$catid			= intval($this->request['file_cat']);
		$file			= array();

		if( !$catid )
		{
			$this->_startForm( $type );
			return;
		}

		
		//-----------------------------------------
		// Get category and check permissions
		//-----------------------------------------
				
		$category = $this->registry->getClass('categories')->cat_lookup[ $catid ];

		//-----------------------------------------
		// Get our storage library...
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/interface_storage.php' );/*noLibHook*/
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/core.php', 'storageEngine', 'bitracker' );
		
		if( $this->registry->getClass('bitFunctions')->canSubmitPaths() AND $this->request['file_path'] )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/import.php', 'importStorageEngine', 'bitracker' );
			$storageEngine	= new $classToLoad( $this->registry, $category, 'file' );
		}
		else
		{
			switch( $this->settings['bit_filestorage'] )
			{
				case 'disk':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/local.php', 'localStorageEngine', 'bitracker' );
					$storageEngine	= new $classToLoad( $this->registry, $category, 'file' );
				break;
	
				case 'ftp':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/ftp.php', 'ftpStorageEngine', 'bitracker' );
					$storageEngine	= new $classToLoad( $this->registry, $category, 'file' );
				break;
				
				case 'db':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/database.php', 'databaseStorageEngine', 'bitracker' );
					$storageEngine	= new $classToLoad( $this->registry, $category, 'file' );
				break;
			}
		}

		if( $this->registry->getClass('bitFunctions')->canSubmitLinks() )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/url.php', 'urlStorageEngine', 'bitracker' );
			$urlStorageEngine	= new $classToLoad( $this->registry, $category, '' );
		}

		//-----------------------------------------
		// Storage engine for nfo's
		//-----------------------------------------
		
		if( $this->registry->getClass('bitFunctions')->canSubmitPaths() AND $this->request['file_nfopath'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/import.php', 'importStorageEngine', 'bitracker' );
			$nfoStorageEngine	= new $classToLoad( $this->registry, $category, 'nfo' );
		}
		else
		{
			switch( $this->settings['bit_filestorage'] )
			{
				case 'disk':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/local.php', 'localStorageEngine', 'bitracker' );
					$nfoStorageEngine	= new $classToLoad( $this->registry, $category, 'nfo' );
				break;
	
				case 'ftp':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/ftp.php', 'ftpStorageEngine', 'bitracker' );
					$nfoStorageEngine	= new $classToLoad( $this->registry, $category, 'nfo' );
				break;
				
				case 'db':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/database.php', 'databaseStorageEngine', 'bitracker' );
					$nfoStorageEngine	= new $classToLoad( $this->registry, $category, 'nfo' );
				break;
			}
		}
		
		//-----------------------------------------
		// Storage engine for screenshots
		//-----------------------------------------
		
		if( $this->registry->getClass('bitFunctions')->canSubmitPaths() AND $this->request['file_sspath'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/import.php', 'importStorageEngine', 'bitracker' );
			$ssStorageEngine	= new $classToLoad( $this->registry, $category, 'screenshot' );
		}
		else
		{
			switch( $this->settings['bit_filestorage'] )
			{
				case 'disk':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/local.php', 'localStorageEngine', 'bitracker' );
					$ssStorageEngine	= new $classToLoad( $this->registry, $category, 'screenshot' );
				break;
	
				case 'ftp':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/ftp.php', 'ftpStorageEngine', 'bitracker' );
					$ssStorageEngine	= new $classToLoad( $this->registry, $category, 'screenshot' );
				break;
				
				case 'db':
					$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/database.php', 'databaseStorageEngine', 'bitracker' );
					$ssStorageEngine	= new $classToLoad( $this->registry, $category, 'screenshot' );
				break;
			}
		}
		
		//-----------------------------------------
		// Are we editing
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			//-----------------------------------------
			// Get file info and check permissions
			//-----------------------------------------
		
			$file_id = intval($this->request['id']);
			
			$file = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'bitracker_files',
													 'where'  => 'file_id=' . $file_id
											 )		);

			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanedit', 'bit_allow_edit' ) )
			{
				$this->registry->output->showError( 'not_your_file', 108113, null, null, 403 );
			}

			//-----------------------------------------
			// Get existing file records
			//-----------------------------------------
			
			$existing		= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => 'record_file_id=' . $file['file_id'] . ' AND record_backup=0' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$existing[ $r['record_location'] ]	= $r;
			}
			
			$_origFile	= $file;
		}

		if( count($this->registry->getClass('categories')->member_access['add']) == 0 )
		{
			if( $type == 'new' )
			{
				$this->registry->output->showError( 'no_addtorrent_permissions', 108114, null, null, 403 );
			}
			else
			{
				$catid = $file['file_cat'];
			}
		}
		else if( !in_array( $catid, $this->registry->getClass('categories')->member_access['add'] ) )
		{
			$_showError	= true;
			
			if( $type == 'edit' )
			{
				if( $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanedit' ) )
				{
					$_showError	= false;
				}
			}
			
			if( $_showError )
			{
				if( $category['coptions']['opt_noperm_add'] )
				{
					$this->registry->output->showError( $category['coptions']['opt_noperm_add'], 108115, null, null, 403 );
				}
				else
				{
					$this->registry->output->showError( 'no_addthiscat_permissions', 108116, null, null, 403 );
				}
			}
		}

		//-----------------------------------------
		// Some Basic Checks First
		//-----------------------------------------
		
		$file['file_name']		= IPSText::stripslashes( $this->request['file_name'] );
		$file['file_name']		= trim( IPSText::getTextClass('bbcode')->stripBadWords( $file['file_name'] ) );
		$file['file_name_furl']	= IPSText::makeSeoTitle( $file['file_name'] );
		$file['post_key']		= IPSText::md5Clean( $this->request['post_key'] );
		$file['file_desc']		= trim( IPSText::stripslashes( $_POST['Post'] ) );

		
		if( !$file['file_name'] )
		{
			$this->error_message = $this->lang->words['addtorrent_error_torrentname'];
			$this->_continueForm( $type );
			return;
		}

		if( IPSText::mbstrlen( $file['file_desc'] ) < 1 )
		{
			$this->error_message = $this->lang->words['addtorrent_error_torrentdesc'];
			$this->_continueForm( $type );
			return;
		}
				
		//--------------------------------------
		// Nexus
		//--------------------------------------
		
		$file['file_cost']	= 0;
		$file['file_nexus']	= '';

		if ( IPSLib::appIsInstalled( 'nexus' ) && $this->settings['bit_nexus_on'] && $this->memberData['bit_add_paid'] )
		{
			if ( $this->request['file_cost_type'] == 'cost' )
			{
				$file['file_cost'] = is_numeric( $this->request['file_cost'] ) ? $this->request['file_cost'] : 0;
				
				// Sanity check
				if ( !isset( $this->request['error_shown'] ) )
				{
					$comission = round( ( ( $file['file_cost'] / 100 ) * $this->settings['bit_nexus_percent'] ), 2 );
					$comission += $this->settings['bit_nexus_transfee'];
					if ( $comission > $file['file_cost'] )
					{
						$this->error_message = sprintf( $this->lang->words['_error_nexus_fee'], $this->paid_file_message );
						$this->_continueForm( $type );
						return;
					}
				}
			}
			elseif ( $this->memberData['g_access_cp'] AND is_array($this->request['file_package']) AND count($this->request['file_package']) )
			{
				$file['file_nexus'] = implode( ',', array_map( 'intval', $this->request['file_package'] ) );
								
				if ( !$file['file_nexus'] )
				{
					$this->error_message = $this->lang->words['addtorrent_error_nexus'];
					$this->_continueForm( $type );
					return;
				}
			}
		}

		/* Switching uploader? */
		if( $this->request['preview'] )
		{
			$this->_continueForm( $type );
			return;
		}

		/* Nexus stuff */
		if( $type != 'edit' and $this->request['renewals'] == 2 and in_array( $this->request['renewal_units'], array( 'd', 'w', 'm', 'y' ) ) )
		{
			$file['file_renewal_term']	= intval( $this->request['renewal_term'] );
			$file['file_renewal_units']	= $this->request['renewal_units'];
			$file['file_renewal_price'] = floatval( $this->request['renewal_price'] );
		}
				
		/* Format description */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$editor->setAllowBbcode( $category['coptions']['opt_bbcode'] ? true : false );
		$editor->setAllowSmilies( true );
		$editor->setAllowHtml( $category['coptions']['opt_html'] ? true : false );
		
		$file['file_desc'] = $editor->process( $_POST['Post'] );
		
		/* Setup BBCode parser as well */
		IPSText::getTextClass('bbcode')->parse_html			= $category['coptions']['opt_html'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= $category['coptions']['opt_bbcode'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parsing_section	= 'bit_submit';
		
		$file['file_desc'] = IPSText::getTextClass('bbcode')->preDbParse( $file['file_desc'] );

		if ( IPSText::getTextClass( 'bbcode' )->error )
		{
			$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
						
			$this->error_message = $this->lang->words[ IPSText::getTextClass( 'bbcode' )->error ];
			$this->_continueForm( $type );
			return;
		}
		else
		{
			$_test	= IPSText::getTextClass('bbcode')->preDisplayParse( $file['file_desc'] );

			if ( IPSText::getTextClass( 'bbcode' )->error )
			{
				$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
							
				$this->error_message = $this->lang->words[ IPSText::getTextClass( 'bbcode' )->error ];
				$this->_continueForm( $type );
				return;
			}
		}

		//-----------------------------------------
		// Check for path import first
		//-----------------------------------------
		
		$error_number	= 0;
		$_didUrl		= false;
		$_didNfoUrl		= false;
		$_didSsUrl		= false;
		
		if( !$this->request['file_path'] )
		{
			//-----------------------------------------
			// Store any links
			//-----------------------------------------

			if( is_array($this->request['file_url']) AND count($this->request['file_url']) AND is_object($urlStorageEngine) )
			{
				foreach( $this->request['file_url'] as $_index => $_url )
				{
					if( !$_url )
					{
						continue;
					}
					
					$_type	= $this->request['file_url_type'][ $_index ];
					$_error	= $urlStorageEngine->store( array( 'url' => $_url, 'type' => 'file', 'link_type' => $_type, 'post_key' => $file['post_key'], 'index' => $_index ) );

					if( $_error > $error_number )
					{
						$error_number	= $_error;
					}
					else
					{
						$_didUrl		= true;
					}
				}
			}
		}


		if( !$this->request['file_nfopath'] )
		{
			//-----------------------------------------
			// Store any links
			//-----------------------------------------
			
			if( is_array($this->request['file_nfourl']) AND count($this->request['file_nfourl']) AND is_object($urlStorageEngine) )
			{
				foreach( $this->request['file_nfourl'] as $_index => $_url )
				{
					if( !$_url )
					{
						continue;
					}

					$_error	= $urlStorageEngine->store( array( 'url' => $_url, 'type' => 'nfo', 'post_key' => $file['post_key'], 'index' => $_index ) );

					if( $_error > $error_number )
					{
						$error_number	= $_error;
					}
					else
					{
						$_didnfoUrl		= true;
					}
				}
			}
		}

	

		if( !$this->request['file_sspath'] )
		{
			//-----------------------------------------
			// Store any links
			//-----------------------------------------
			
			if( is_array($this->request['file_ssurl']) AND count($this->request['file_ssurl']) AND is_object($urlStorageEngine) )
			{
				foreach( $this->request['file_ssurl'] as $_index => $_url )
				{
					if( !$_url )
					{
						continue;
					}

					$_error	= $urlStorageEngine->store( array( 'url' => $_url, 'type' => 'screenshot', 'post_key' => $file['post_key'], 'index' => $_index ) );

					if( $_error > $error_number )
					{
						$error_number	= $_error;
					}
					else
					{
						$_didSsUrl		= true;
					}
				}
			}
		}
	
		$_error	= $storageEngine->store( $file );

		if( $_error > $error_number )
		{
			if( $_error == 1 AND $_didUrl )
			{
				// If there was no upload but we submitted a link, that's fine
			}
			else
			{
				$error_number = $_error;
			}
		}

		
		//-----------------------------------------
		// If we are editing and error is "1" that's ok
		//-----------------------------------------
							
		if( !$error_number )
		{
			$_error	= ( $ssStorageEngine->store( $file ) || $nfoStorageEngine->store( $file ) );
		}	

		//-----------------------------------------
		// Error?
		//-----------------------------------------

		if ( $error_number )
		{
			if( is_object($urlStorageEngine) )
			{
				$urlStorageEngine->rollback();
			}
			
			$storageEngine->rollback();
			$ssStorageEngine->rollback();
			$nfoStorageEngine->rollback();
			
			$this->error_message = $this->lang->words['addtorrent_upload_error' . $error_number ];
			$this->_continueForm( $type );
			return;
		}



		//-----------------------------------------
		// Before we commit the file to storage 
        // we need to decode and check the metadata
		//-----------------------------------------

            $filepath = (( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] . '/' . $storageEngine->details[0][record_location])));

			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/bit-benc.php', 'benc', 'bitracker' );

    		$decoder			= new $classToLoad( $this->registry, $TorrentInfo = array());

            $this->TorrentInfo = $decoder->ParseTorrent( $filepath );

		//----------------------------------------------
		// Lets check our $TorrentInfo array for errors!
		//----------------------------------------------

           if ( $this->decode_error == 1 ){
                   //Something went wrong with the DEcode

			         $storageEngine->rollback();
			         $ssStorageEngine->rollback();
			         $nfoStorageEngine->rollback();

			         $this->error_message = $this->lang->words['addtorrent_upload_error1'];
			         $this->_continueForm( $type );
			          return;
                       }

           if ( empty($this->TorrentInfo) ){

			         $storageEngine->rollback();
			         $ssStorageEngine->rollback();
			         $nfoStorageEngine->rollback();

			         $this->error_message = $this->lang->words['addtorrent_upload_error1'];
			         $this->_continueForm( $type );
			         return;
                       }

  		//----------------------------------------------
		//  Check the announce ???
		//----------------------------------------------    

             $default_announce = $this->registry->output->buildUrl( 'app=bitracker&module=client&section=announce', 'public', 'true');


             if( $this->TorrentInfo['torrent_announce'] != $default_announce ){

			         $storageEngine->rollback();
			         $ssStorageEngine->rollback();
			         $nfoStorageEngine->rollback();

			         $this->error_message = $this->lang->words['addtorrent_upload_error1'];
			         $this->_continueForm( $type );
			          return;
                 }

  		//------------------------------------------------------
		//  Check the infohash is not already in the records ???
		//------------------------------------------------------

         $infoHash = $this->DB->buildAndFetch( array(    'select' 	=> 'torrent_infohash',
											 		     'from'		=> 'bitracker_torrent_data',
                                                         'where'	=> "torrent_infohash='{$this->TorrentInfo['torrent_infohash']}'"
                                                )      ); 



       if ( $infoHash || isset($infoHash) ){
 
			         $storageEngine->rollback();
			         $ssStorageEngine->rollback();
			         $nfoStorageEngine->rollback();

			         $this->error_message = $this->lang->words['torrent_hash_error'];
			         $this->_continueForm( $type );
			          return;
                         }
          

		//----------------------------------------------
		//  Flying the flag for private trackers ???
		//----------------------------------------------


             if( $this->TorrentInfo['torrent_private_flag'] != 1 ){

			         $storageEngine->rollback();
			         $ssStorageEngine->rollback();
			         $nfoStorageEngine->rollback();

			         $this->error_message = $this->lang->words['torrent_private_error'];
			         $this->_continueForm( $type );
			          return;
                         }

	

		//-----------------------------------------
		// Nfo's required?
		//-----------------------------------------

		if( $category['coptions']['opt_allownfo'] )
		{	
			if( $category['coptions']['opt_reqnfo'] AND !$nfoStorageEngine->checkForNfo() AND (!is_object($urlStorageEngine) OR !$urlStorageEngine->checkForNfo()) )
			{
				if( is_object($urlStorageEngine) )
				{
					$urlStorageEngine->rollback();
				}
				
				$storageEngine->rollback();
				$ssStorageEngine->rollback();
			    $nfoStorageEngine->rollback();
				
				$this->error_message = $this->lang->words['addtorrent_upload_error10'];
				$this->_continueForm( $type );
				return;
			}
		}


		//-----------------------------------------
		// Screenshots required?
		//-----------------------------------------

		if( $category['coptions']['opt_allowss'] )
		{	


			if( $category['coptions']['opt_reqss'] AND !$ssStorageEngine->checkForScreenshot() AND (!is_object($urlStorageEngine) OR !$urlStorageEngine->checkForScreenshot()) )
			{
				if( is_object($urlStorageEngine) )
				{
					$urlStorageEngine->rollback();
				}
				
				$storageEngine->rollback();
				$ssStorageEngine->rollback();
			    $nfoStorageEngine->rollback();
				
				$this->error_message = $this->lang->words['addtorrent_upload_error6'];
				$this->_continueForm( $type );
				return;
			}
		}

		//-----------------------------------------
		// File open?  new?
		//-----------------------------------------
		
		$file_new	= 0;
		
		if( $type == 'new' )
		{
			$open		= in_array( $catid, $this->registry->getClass('categories')->member_access['auto'] ) ? 1 : 0;
			$file_new	= $open ? 0 : 1;
		}
		else
		{
			$open		= !$file['file_open'] ? 0 : ( in_array( $catid, $this->registry->getClass('categories')->member_access['auto'] ) ? 1 : $this->settings['bit_allow_autoedit'] );
			$file_new	= $file['file_new'];
		}

		//-----------------------------------------
		// Create the save array
		//-----------------------------------------
		
		$save_array = array( 'file_name'		=> $file['file_name'],
							 'file_name_furl'	=> $file['file_name_furl'],
							 'file_desc'		=> $file['file_desc'],
							 'file_cat'			=> $catid,
							 'file_open'		=> $open,
							 'file_ipaddress'	=> $this->member->ip_address,
							 'file_updated'		=> time(),
							 'file_new'			=> $file_new,
							 'file_post_key'	=> $file['post_key'],
							 'file_cost'		=> $file['file_cost'],
							 'file_nexus'		=> $file['file_nexus'],
							 'file_version'		=> IPSText::mbsubstr( $this->request['file_version'], 0, 24 ),
							 'file_changelog'	=> trim( IPSText::br2nl( $this->request['file_changelog'] ) ),
							);
		
		/* Changelog bug no version? Naughty uploader! */
		if ( $save_array['file_changelog'] && empty($save_array['file_version']) )
		{
			if( is_object($urlStorageEngine) )
			{
				$urlStorageEngine->rollback();
			}
			
			$storageEngine->rollback();
			$ssStorageEngine->rollback();
			$nfoStorageEngine->rollback();
			
			$this->error_message = $this->lang->words['addtorrent_upload_error_changelog'];
			$this->_continueForm( $type );
			return;
		}		
				
		if( $type != 'edit' and in_array( $this->request['renewal_units'], array( 'd', 'w', 'm', 'y' ) ) AND $save_array['file_cost'] > 0 )
		{
			$save_array['file_renewal_term']	= intval( $file['file_renewal_term'] );
			$save_array['file_renewal_units']	= $file['file_renewal_units'];
			$save_array['file_renewal_price']	= floatval( $file['file_renewal_price'] );
		}
		else if( $type == 'edit' AND $save_array['file_cost'] == 0 )
		{
			$save_array['file_renewal_term']	= 0;
			$save_array['file_renewal_units']	= '';
			$save_array['file_renewal_price']	= 0;
		}
		
		//-----------------------------------------
		// File size...
		//-----------------------------------------
		
		if( is_object($urlStorageEngine) )
		{
			$save_array['file_size']	= $urlStorageEngine->getFileSize();
		}

		$save_array['file_size']	+= $storageEngine->getFileSize();

		//-----------------------------------------
		// Check custom fields
		//-----------------------------------------
		
		if( $category['ccfields'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/cfields.php', 'bit_customFields', 'bitracker' );
    		$fields				= new $classToLoad( $this->registry );
    		$fields->file_id	= 0;
    		$fields->cat_id		= $category['ccfields'];
    		$fields->cache_data	= $this->cache->getCache('bit_cfields');
    	
    		$fields->init_data( 'save' );
    		$fields->parseToSave( $this->request );
    		
			if ( count( $fields->error_fields ) )
			{
				foreach( $fields->error_fields as $_fieldData )
				{
					if( $_fieldData[0] == 'empty' )
					{
						if( is_object($urlStorageEngine) )
						{
							$urlStorageEngine->rollback();
						}
						
						$storageEngine->rollback();
			            $nfoStorageEngine->rollback();
						$ssStorageEngine->rollback();
				
						$this->error_message = $this->lang->words['addtorrent_error_cfield'];
						$this->_continueForm( $type );
						return;
					}
					else if( $_fieldData[0] == 'too_big' )
					{
						if( is_object($urlStorageEngine) )
						{
							$urlStorageEngine->rollback();
						}
						
						$storageEngine->rollback();
			            $nfoStorageEngine->rollback();
						$ssStorageEngine->rollback();
				
						$this->error_message = $this->lang->words['addtorrent_error_cfield1'];
						$this->_continueForm( $type );
						return;
					}
					else if( $_fieldData[0] == 'invalid' )
					{
						if( is_object($urlStorageEngine) )
						{
							$urlStorageEngine->rollback();
						}
						
						$storageEngine->rollback();
			            $nfoStorageEngine->rollback();
						$ssStorageEngine->rollback();
				
						$this->error_message = $this->lang->words['addtorrent_error_cfield2'];
						$this->_continueForm( $type );
						return;
					}
				}
			}
		}

		//-----------------------------------------
		// Remove the old files (if necessary)
		//-----------------------------------------

		if( $type == 'edit' )
		{
			//-----------------------------------------
			// Version control backup file
			//-----------------------------------------
			
			if( $this->settings['bit_versioning'] AND ( !$this->memberData['bit_bypass_revision'] OR !$this->request['bypass_revision'] ) )
			{
				$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/versioning.php', 'versioningLibrary', 'bitracker' );
				$versions 		= new $classToLoad( $this->registry );
				$versions->backup( $_origFile );
			}
			//else
			//{
				//-----------------------------------------
				// Remove previous files
				//-----------------------------------------

				if( count($existing) )
				{
					foreach( $existing as $_oldRecord )
					{
						switch( $_oldRecord['record_storagetype'] )
						{
							case 'disk':
								$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/local.php', 'localStorageEngine', 'bitracker' );
								$oldStorageEngine	= new $classToLoad( $this->registry, $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ] );
							break;
				
							case 'ftp':
								$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/ftp.php', 'ftpStorageEngine', 'bitracker' );
								$oldStorageEngine	= new $classToLoad( $this->registry, $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ] );
							break;
							
							case 'db':
								$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/storage/database.php', 'databaseStorageEngine', 'bitracker' );
								$oldStorageEngine	= new $classToLoad( $this->registry, $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ] );
							break;
						}
						if( !$storageEngine->isCurrent( $_oldRecord ) AND !$ssStorageEngine->isCurrent( $_oldRecord ) AND !$nfoStorageEngine->isCurrent( $_oldRecord ) )
						{
							$oldStorageEngine->remove( $_oldRecord );
						}
					}
				}
			//}

			$this->DB->delete( "bitracker_files_records", 'record_file_id=' . $file['file_id'] . ' AND record_backup=0' );
		}

		
		//-----------------------------------------
		// Check if we're ok with tags
		//-----------------------------------------
		
		$where		= array( 'meta_parent_id'	=> $save_array['file_cat'],
							  'member_id'		=> $this->memberData['member_id'],
							  'existing_tags'	=> explode( ',', IPSText::cleanPermString( $_POST['ipsTags'] ) ) );
									  
		if ( $this->registry->bitrackerTags->can( 'add', $where ) AND $this->settings['tags_enabled'] AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->bitrackerTags->checkAdd( $_POST['ipsTags'], array(
																  'meta_parent_id' => $save_array['file_cat'],
																  'member_id'	   => $this->memberData['member_id'],
																  'meta_visible'   => $save_array['file_open'] ) );

			if ( $this->registry->bitrackerTags->getErrorMsg() )
			{
				$this->lang->loadLanguageFile( array( 'public_post' ), 'forums' );
				
				$storageEngine->rollback();
				$ssStorageEngine->rollback();
				$this->error_message = $this->registry->bitrackerTags->getFormattedError();
				$this->_continueForm( $type );
				return;
			}
		}
		
		//-----------------------------------------
		// Save the file
		//-----------------------------------------

		$cat_stats = array();
		
		if( $type == 'new' )
		{
			$save_array['file_submitted']	= time();
			$save_array['file_submitter']	= $this->memberData['member_id'];
			
			/* Data Hook Location */
			IPSLib::doDataHooks( $save_array, 'trackAddFile' );
			
			$this->DB->insert( "bitracker_files", $save_array );
			
			$file['file_id'] = $this->DB->getInsertId();

			$author_name = $this->memberData['members_display_name'];
		}
		else
		{
			/* Data Hook Location */
			IPSLib::doDataHooks( $save_array, 'bitrackerEditFile' );
			
			$this->DB->update( "bitracker_files", $save_array, "file_id=".$file['file_id'] );
					
			/* Sort out submitter name */
			if( $save_array['file_open'] )
			{
				/* Avoid query? */
				if( $this->memberData['member_id'] == $file['file_submitter'] )
				{
					$author_name = $this->memberData['members_display_name'];
				}
				else
				{
					$name = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => 'member_id=' . $file['file_submitter'] ) );
					
					$author_name = $name['members_display_name'];
				}
			}

			/* Do we have to update any Nexus data? */
			if( $_origFile['file_cost'] AND $_origFile['file_name'] != $save_array['file_name'] )
			{
				/* Only update the name if the admin hasn't renamed the purchase */
				$this->DB->update( 'nexus_purchases', array( 'ps_name' => $save_array['file_name'] ), "ps_app='bitracker' AND ps_type='file' AND ps_item_id={$file['file_id']} AND ps_name='{$_origFile['file_name']}'" );
			}

			if( $_origFile['file_cost'] AND !$save_array['file_cost'] )
			{
				/* Only update the renewal terms if the admin hasn't changed them */
				$this->DB->update( 'nexus_purchases', array( 'ps_renewals' => 0, 'ps_renewal_price' => 0, 'ps_renewal_unit' => '' ), 
						"ps_app='bitracker' AND ps_type='file' AND ps_item_id={$file['file_id']} AND ps_renewals={$_origFile['file_renewal_term']} AND ps_renewal_price={$_origFile['file_renewal_price']} AND ps_renewal_unit='{$_origFile['file_renewal_units']}'" );
			}
		}
		
		//-----------------------------------------
		// Store tags
		//-----------------------------------------

		if( $type == 'edit' )
		{
			if( !empty( $_POST['ipsTags'] ) )
			{
				$this->registry->bitrackerTags->replace( $_POST['ipsTags'], array( 'meta_id'			=> $file['file_id'],
																      				'meta_parent_id'	=> $save_array['file_cat'],
																      				'member_id'			=> $file['file_submitter'],
																      				'meta_visible'		=> $save_array['file_open'] ) );
			}
			else
			{
				$this->registry->bitrackerTags->deleteByMetaId( array( $file['file_id'] ) );
			}
		}
		else if( $type == 'new' AND ( !empty( $_POST['ipsTags'] ) OR $this->settings['tags_min'] ) )
		{
			$this->registry->bitrackerTags->add( $_POST['ipsTags'], array( 'meta_id'			=> $file['file_id'],
														      				'meta_parent_id'	=> $save_array['file_cat'],
														      				'member_id'			=> $this->memberData['member_id'],
														      				'meta_visible'		=> $save_array['file_open'] ) );
		}
				
		//-----------------------------------------
		// Finalize storage
		//-----------------------------------------

		$this->DB->delete( "bitracker_temp_records", "record_post_key='{$save_array['file_post_key']}'" );
		
		if( is_object($urlStorageEngine) )
		{
			$urlStorageEngine->commit( $file['file_id'] );
		}

		//--------------------------------------------------------------
		// Add file_id to torrent data array and prep array for database
		//--------------------------------------------------------------

        $this->TorrentInfo['torrent_id'] = $file['file_id'];

        $this->TorrentInfo['torrent_post_key'] = $save_array['file_post_key'];

        if( $this->TorrentInfo["torrent_file_count"] > 1 )
          {  
             $this->TorrentInfo['torrent_filelist'] = json_encode( $this->TorrentInfo['torrent_filelist'] );
          }
          else
          {
             $this->TorrentInfo['torrent_filelist'] = trim( $this->TorrentInfo["torrent_name"] );
          }

        $this->TorrentInfo['torrent_announce'] = htmlspecialchars( $this->TorrentInfo['torrent_announce'] ) ;
        $this->TorrentInfo['torrent_name'] = $file['file_name'];

		if( $type == 'new' )
		{
		//$this->DB->update( 'bitracker_torrent_data', $save, "cid={$this->request['c']}" );
        //}
        //else
        //{
		$this->DB->insert( 'bitracker_torrent_data', $this->TorrentInfo );
        }		
		$storageEngine->commit( $file['file_id'] );
		$nfoStorageEngine->commit( $file['file_id'] );
		$ssStorageEngine->commit( $file['file_id'] );
		
		//-----------------------------------------
		// Save the custom fields
		//-----------------------------------------

		if( $category['ccfields'] )
		{
			if ( count( $fields->out_fields ) )
			{
				$fields->out_fields['file_id'] = $file['file_id'];
				$this->DB->replace( 'bitracker_ccontent', $fields->out_fields, array( 'file_id' ) );			
			}
		}

		//-----------------------------------------
		// Rebuild category stats
		//-----------------------------------------
		
		$this->registry->getClass('categories')->rebuildFileinfo( $catid );
		
		if( $type == 'edit' AND $catid <> $file['file_cat'] )
		{
			$this->registry->getClass('categories')->rebuildFileinfo( $file['file_cat'] );
		}
		
		$this->registry->getClass('categories')->rebuildStatsCache();

		//---------------------------------------------------------
		// Auto-posting of topics
		//---------------------------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/topics.php', 'topicsLibrary', 'bitracker' );
		$lib_topics		= new $classToLoad( $this->registry );
		
		$file['file_submitter_name'] = $author_name;
		$file['record_storagetype'] = $this->settings['bit_filestorage'];

		$lib_topics->sortTopic( array_merge( $file, $save_array ), $category, $type );
		
		//---------------------------------------------------------
		// Member subscription notifications
		//---------------------------------------------------------
		
		if( $save_array['file_open'] )		
		{
			if( $type == 'edit' )
			{
				$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $save_array['file_name_furl'], 'bitshowfile' );
				
				//-----------------------------------------
				// Like class
				//-----------------------------------------
		
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$this->_like = classes_like::bootstrap( 'bitracker', 'files' );
				$this->_like->sendNotifications( $file['file_id'], array( 'immediate', 'offline' ), array(
																										'notification_key'		=> 'updated_file',
																										'notification_url'		=> $_url,
																										'email_template'		=> 'subsription_notifications',
																										'email_subject'			=> sprintf( $this->lang->words['sub_notice_subject'], $_url, $save_array['file_name'] ),
																										'build_message_array'	=> array(
																																		'NAME'  		=> '-member:members_display_name-',
																																		'AUTHOR'		=> $this->memberData['members_display_name'],
																																		'TITLE' 		=> $save_array['file_name'],
																																		'URL'			=> $_url,
																																		)
																								) 		);

			}
			else
			{
				$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $save_array['file_name_furl'], 'bitshowfile' );
				
				//-----------------------------------------
				// Like class
				//-----------------------------------------
		
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$this->_like = classes_like::bootstrap( 'bitracker', 'categories' );
				$this->_like->sendNotifications( $save_array['file_cat'], array( 'immediate', 'offline' ), array(
																										'notification_key'		=> 'new_file',
																										'notification_url'		=> $_url,
																										'email_template'		=> 'subsription_notifications_new',
																										'email_subject'			=> sprintf( $this->lang->words['sub_notice_subject_new'], $_url, $save_array['file_name'] ),
																										'build_message_array'	=> array(
																																		'NAME'  		=> '-member:members_display_name-',
																																		'AUTHOR'		=> $this->memberData['members_display_name'],
																																		'TITLE' 		=> $save_array['file_name'],
																																		'URL'			=> $_url,
																																		)
																								) 		);
			}
		}
		else
		{
			$moderators	= $this->registry->getClass('bitFunctions')->returnModerators();
			
			if( is_array($moderators) AND count($moderators) )
			{
				/* @link http://bugs.---.com/tracker/issue-35571-duplicate-notifications-when-editing-a-pending-file */
				
				if( $type != 'edit' OR ( $type == 'edit' AND $file['file_open'] == 1 ) )
				{
					$_url		= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $save_array['file_name_furl'], 'bitshowfile' );
					// Don't use &amp; here or it breaks the link in the emails
					$_modPanel	= $this->registry->output->buildSEOUrl( 'app=core&module=modcp&fromapp=bitracker&tab=unapprovedfiles', 'public' );
					
					$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
					$notifyLibrary		= new $classToLoad( $this->registry );
					
					foreach( $moderators as $moderator )
					{
						//-----------------------------------------
						// Don't send notification to yourself
						//-----------------------------------------
						
						if( $moderator['member_id'] == $this->memberData['member_id'] )
						{
							continue;
						}
		
						$notifyLibrary->setMember( $moderator );
						$notifyLibrary->setFrom( $this->memberData );
						$notifyLibrary->setNotificationKey( 'file_pending' );
						$notifyLibrary->setNotificationUrl( $_url );
						$notifyLibrary->setNotificationText( sprintf( $this->lang->words['moderate_filepending'], $moderator['members_display_name'], $save_array['file_name'], $_modPanel ) );
						$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['moderate_filependingsub'], $_url, $save_array['file_name'] ) );
						try
						{
							$notifyLibrary->sendNotification();
						}
						catch( Exception $e ){}
					}
				}
			}
		}

		//-----------------------------------------
		// Still here?  Wippii...success
		//-----------------------------------------
		
		$lang	= $save_array['file_open'] ? $this->lang->words['submission_live'] : $this->lang->words['submission_approve'];
		
		if ( $save_array['file_open'] || $this->memberData['member_id'])
		{
			$this->registry->output->redirectScreen( $lang, $this->settings['base_url'] . "app=bitracker&showfile={$file['file_id']}", $save_array['file_name_furl'], 'bitshowfile' );
		}
		else
		{
			$this->registry->output->redirectScreen( $lang, $this->settings['base_url'] . "app=bitracker&showcat={$category['cid']}", $category['cname_furl'], 'bitshowcat' );
		}
	}
}