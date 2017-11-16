<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.bitracker update sidebar
 * Last Updated: $Date: 2013-02-06 16:33:34 -0500 (Wed, 06 Feb 2013) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2012 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		Friday 28th September 2012 (17:00)
 * @version		$Revision: 11947 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_ajax_classicsidebar extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$items 		= array();
		$type 		= $this->request['type'];
		$period 	= $this->request['period'];
		$template 	= 'file';
		$no_lang	= ( $template == 'file' ) ? $this->lang->words['portal_no_bitracker'] : $this->lang->words['portal_no_users'] ;
		
		//-----------------------------------------
		// Get bitracker library and API
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/api_core.php', 'apiCore' );
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/api/api_bit.php', 'apiBitracker', 'bitracker' );
		
		$bit_api		= new $classToLoad();
		$bit_api->init();
		
		$categories = $this->registry->getClass('categories')->member_access['show'];
		
		//-----------------------------------------
		// Build the date range
		//-----------------------------------------
		
		switch( $period )
		{
			default:
			case 'year':
				$searchRange = time() - ( 86400 * 365);
				break;
			case 'month':
				$searchRange = time() - ( 86400 * 30);
				break;
			case 'week':
				$searchRange = time() - ( 86400 * 7);
				break;
			case 'all':
				$searchRange = 0;
				break;
		}	
		
		//-----------------------------------------
		// Let's grab the results
		//-----------------------------------------
		
		switch( $type )
		{
			default:
			case 'all':
				$items  = $bit_api->returnBitracker( 0, 10, true, "file_bitracker DESC", array( "file_submitted > {$searchRange}" ) );
			break;
			case 'free':
				$items  = $bit_api->returnBitracker( 0, 5, true, "file_bitracker DESC", array( "file_cost=0", "file_submitted > {$searchRange}" ) );
				break;
			case 'paid':				
				if( IPSLib::appIsInstalled( 'nexus' ) && $this->settings['bit_nexus_on'] )
				{
						
					$paidFiles	= array();
						
					$this->DB->build( array(
							'select'	=> "COUNT(*) as purchases, p.ps_item_id",
							'from'		=> array( 'nexus_purchases' => 'p' ),
							'group'		=> 'p.ps_item_id',
							'where'		=> "p.ps_app='bitracker' AND p.ps_type='file' AND f.file_open=1 AND f.file_cost !=0 AND f.file_cat IN(" . implode( ',', $categories ) . ") AND file_submitted > {$searchRange}",
							'order'		=> "purchases DESC",
							'limit'		=> array( 0, 5 ),
							'add_join'	=> array(
									array(
											'from'	=> array( 'bitracker_files' => 'f' ),
											'type'	=> 'left',
											'where'	=> 'f.file_id=p.ps_item_id',
									),
							)
					)		);
					$this->DB->execute();
						
					while( $r = $this->DB->fetch() )
					{
						$paidFiles[ $r['ps_item_id'] ]	= $r['ps_item_id'];
					}
						
					if( count( $paidFiles ) )
					{
						$files	= $bit_api->returnBitracker( 0, 5, true, null, array( "file_id IN(" . implode( ',', $paidFiles ) . ')' ) );			
					
						foreach( $paidFiles as $_file )
						{
							foreach( $files as $paidFile )
							{
								if( $paidFile['file_id'] == $_file )
								{
									$items[]	= $paidFile;
								}
							}
						}			
					}

				}
			break;
			case 'author':			
				$_authors	= array();
				$_authorIds	= array();
				$template = 'author';
				
				$this->DB->build( array( 'select' => 'file_submitter, COUNT(file_submitter) as totalfiles', 'from' => 'bitracker_files', 'where' => 'file_open=1 AND file_cat IN (' . implode( ',', $categories ) . ') AND file_submitted > ' . $searchRange, 'order' => 'totalfiles DESC', 'limit' => array( 0, 5 ), 'group' => 'file_submitter' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					if( !$r['file_submitter'] )
					{
						continue;
					}
						
					$_authors[]		= $r;
					$_authorIds[]	= $r['file_submitter'];
				}
				
				$members	= IPSMember::load( $_authorIds, 'core,extendedProfile,groups' );
				
				foreach( $_authors as $_author )
				{
					$_member				= $members[ $_author['file_submitter'] ];
					$_member['total_files']	= $_author['totalfiles'];
						
					$items[]	= IPSMember::buildDisplayData( $_member, array( 'reputation' => 0, 'warn' => 0 ) );
				}
			break;
		}

		//-----------------------------------------
		// Grab screenshot info if needed
		//-----------------------------------------
		
		if ($template == 'file')
		{
			$_screenshotFileIds	= array_keys( $items );
			
			$_screenshotFileIds	= array_unique($_screenshotFileIds);
			
			if( count($_screenshotFileIds) )
			{
				$_recordIds	= array();
					
				$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id IN(" . implode( ',', $_screenshotFileIds ) . ") AND record_type IN('ssupload','sslink') AND record_backup=0" ) );
				$this->DB->execute();
					
				while( $r = $this->DB->fetch() )
				{
					if( !isset($_recordIds[ $r['record_file_id'] ]) OR $r['record_default'] )
					{
						$_recordIds[ $r['record_file_id'] ]	= $r;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Let's go
		//-----------------------------------------		

		$this->returnJsonArray( array( 'type' => $type, 'data' => preg_replace('/<!--(.|\s)*?-->/', '', $this->registry->getClass('output')->getTemplate('bitracker')->classicSidebarBlockInsert( $no_lang, $items, $template, $recordIds=array() ) ) ) );
	}
}