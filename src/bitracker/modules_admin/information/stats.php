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
 * @class		admin_bitracker_information_stats
 * @brief		IP.download Manager Statistics
 */
class admin_bitracker_information_stats extends ipsCommand
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
	 * Traffic library object
	 *
	 * @var		$traffic
	 */	
	protected $traffic;

	/**
	 * Max results
	 *
	 * @var		$limit
	 */	
	protected $limit = 50;

	/**
	 * Query type
	 *
	 * @var		$type
	 */	
	protected $type;

	/**
	 * Text to display
	 *
	 * @var		$text
	 */	
	protected $text			= array( 'fid' => "File", 'os' => "Operating System", 'browsers' => "Browser", 'ip' => "IP Address", 'time' => "Date Period" );

	/**
	 * Acceptable stat types
	 *
	 * @var		$acceptable
	 */	
	protected $acceptable	= array( 'browsers', 'ip', 'os', 'fid', 'time' );

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_stats' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=information&amp;section=stats';
		$this->form_code_js	= $this->html->form_code_js	= 'module=information&section=stats';
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );
		
		//-----------------------------------------
		// Language keys for the text up there in $text
		// Always making things difficult for the poor language abstraction people like me...
		//-----------------------------------------

		foreach( $this->text as $k => $v ) 
		{
			if( !empty($this->lang->words[ 's_'.$k ]) )
			{
				$this->text[ $k ] = $this->lang->words[ 's_'.$k ];
			}
		}

		//-----------------------------------------
		// Get traffic library
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/traffic.php', 'trafficLibrary', 'bitracker' );
		$this->traffic	= new $classToLoad( $this->registry );
		$this->traffic->loadLibraries();
		
		//-----------------------------------------
		// Running a report?
		//-----------------------------------------
		
		if( $this->request['do'] == "report" )
		{
			$this->_runReport();
			return;
		}
		
		//-----------------------------------------
		// Some init
		//-----------------------------------------
		
		$this->limit = $this->request['limit'] ? intval($this->request['limit']) : 10;
		$this->type	 = $this->request['type'] == 'bw' ? "SUM(dsize) as num" : "COUNT(*) as num";

		//-----------------------------------------
		// Display images?
		//-----------------------------------------
		
		if( $this->request['pieimg'] )
		{
			$this->_getPieImage();
			exit;
		}
		
		//-----------------------------------------
		// And some more init..
		//-----------------------------------------
		
		$groupby		= in_array( $this->request['groupby'], $this->acceptable ) ? $this->request['groupby'] : 'browsers';
		$groupbyOptions	= array();
		$form			= array();
		
		foreach( $this->text as $k => $v )
		{
			$groupbyOptions[] = array( $k, $v );
		}

		$form['type']		= $this->registry->output->formDropdown( "type", array( array( 'dl', $this->lang->words['s_onthedl'] ), array( 'bw', $this->lang->words['s_bw'] ) ), $this->request['type'] );
		$form['groupby']	= $this->registry->output->formDropdown( "groupby", $groupbyOptions, $groupby );
		$form['limit']		= $this->registry->output->formSimpleInput( "limit", $this->limit );
		
		//-----------------------------------------
		// Grab stats data
		//-----------------------------------------
		
		if( $groupby == 'time' )
		{
			$this->DB->build( array('select'	=> "d.did,d.dip,d.dtime,d.dmid,d.dua,d.dbrowsers,d.dos,d.dsize, {$this->type}, d.dtime as indicator, " . $this->DB->buildFromUnixtime( 'd.dtime', '%M %Y' ) . " as dtime",
									'from'		=> array( 'bitracker_bitracker'  => 'd' ),
									'group'		=> 'dtime',
									'order'		=> 'num DESC',
									'limit'		=> array( 0, $this->limit ),
									'add_join'	=> array( array('select'	=> 'f.file_name',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=d.dfid',
																'type'		=> 'left' ) )
							)		);
		}
		else
		{
			$this->DB->build( array('select'	=> "d.did,d.dip,d.dtime,d.dmid,d.dua,d.dbrowsers,d.dos,d.dsize, {$this->type}, d.d{$groupby} as indicator",
									'from'		=> array( 'bitracker_bitracker'  => 'd' ),
									'group'		=> 'd.d' . $groupby,
									'order'		=> 'num DESC',
									'limit'		=> array( 0, $this->limit ),
									'add_join'	=> array( array('select'	=> 'f.file_name',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=d.dfid',
																'type'		=> 'left' ) )
							)		);
		}

		$outer = $this->DB->execute();			

		$results	= array();
		$i			= 1;
		
		while( $row = $this->DB->fetch($outer) )
		{
			if( $row['num'] > 0 )
			{
				$results[ $row['num'] . $i ] = $row;
				$i++;
			}
		}
		
		//-----------------------------------------
		// Sort out the stats
		//-----------------------------------------
				
		$totals	= array_keys($results);
		$total	= 0;
		$cnt	= 1;

		if( count($totals) )
		{
			foreach( $totals as $k => $v )
			{
				$total += substr( $v, 0, -(strlen($cnt)) );
			}
		}

		if( count($results) > 0 )
		{
			foreach( $results as $k => $row )
			{
				if( $groupby == 'browsers' OR $groupby == 'os' )
				{
					$data = $this->traffic->returnStatData( $row );
					
					$imgfile = $this->traffic->getItemImage( $groupby, $data["stat_{$groupby}_key"] );

					$form['image']	= "<img src='{$this->settings['public_dir']}bitracker_traffic_images/" . $imgfile . "' alt='[*]' />";
					$form['text']	= $data['stat_'.$groupby];
				}
				else
				{
					if( $groupby == 'fid' )
					{
						if( $row['file_name'] == '' )
						{
							$form['text'] = $this->lang->words['s_deleted'];
						}
						else
						{
							$form['text'] = "<a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$row['dfid']}'>{$row['file_name']}</a>";
						}
					}
					else
					{
						$form['text'] = $row['d' . $groupby ];
					}
				}
				
				if( $total > 0 )
				{
					$width = substr( $k, 0, -(strlen($cnt)) ) == 0 ? 0 : substr( $k, 0, -(strlen($cnt)) ) / $total * 100;
					$width = $width > 0 ? intval($width * 2) : 0;

					$form['pip'] = "<img src='{$this->settings['skin_acp_url']}/images/bar_left.gif' height='11' alt='[*]' /><img src='{$this->settings['skin_acp_url']}/images/bar.gif' width='{$width}' height='11' alt='[*]' /><img src='{$this->settings['skin_acp_url']}/images/bar_right.gif' height='11' alt='[*]' />";
				}

				$form['num'] = $this->request['type'] == 'bw' ? IPSLib::sizeFormat($row['num']) : $row['num'];

				$cnt++;
			}
		}

		if( $cnt > 1 )
		{
			$form['graphcharts']	= 1;
			$form['piechart']		= "{$this->settings['base_url']}{$this->form_code}&amp;pieimg={$groupby}&amp;limit={$this->limit}&amp;type={$this->request['type']}";
		}
		
		//-----------------------------------------
		// And some more init..
		// Die, register globals, die
		//-----------------------------------------
		
		$topbitracker	= array();
		$topViews		= array();
		$topSubmitters	= array();
		$toptrackers	= array();
		
		//-----------------------------------------
		// Top 10 tracked files
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'f.*',
								 'from'		=> array('bitracker_files' => 'f'),
								 'where'	=> 'f.file_open=1',
								 'order'	=> 'f.file_bitracker DESC',
								 'limit'	=> array(0,10),
								 'add_join'	=> array( array('select'	=> 'm.members_display_name',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> "f.file_submitter=m.member_id",
															'type'		=> 'left' ) )
						 )		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$topbitracker[]	= $r;
		}
		
		//-----------------------------------------
		// Top 10 viewed files
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> 'f.*',
								 'from'		=> array('bitracker_files' => 'f'),
								 'where'	=> 'f.file_open=1',
								 'order'	=> 'f.file_views DESC',
								 'limit'	=> array(0,10),
								 'add_join'	=> array( array('select'	=> 'm.members_display_name',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> "f.file_submitter=m.member_id",
															'type'		=> 'left' ) )
						 )		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$topViews[]	= $r;
		}
		
		//-----------------------------------------
		// Top 10 submitters
		//-----------------------------------------
		
		$this->DB->build( array('select'	=> 'COUNT(file_id) as submissions, MAX(file_id) as last_id, file_submitter',
								'from'		=> 'bitracker_files',
								'group'		=> 'file_submitter',
								'order'		=> 'submissions DESC',
								'limit'		=> array( 10 )
						 )		);
		$this->DB->execute();
		
		$unique_authors	= array();
		$ids			= array();
		
		while( $submitters = $this->DB->fetch() )
		{
			$unique_authors[ $submitters['file_submitter'] ] = $submitters;
			
			$ids[] = $submitters['last_id'];
		}

		if( count( $ids ) )
		{
			$this->DB->build( array(
									'select'	=> 'f.*' ,
									'from'		=> array( 'bitracker_files' => 'f' ),
									'where'		=> 'f.file_id IN(' . implode( ',', $ids ) . ')',
									'add_join'	=> array(
														array(
															'select'	=> 'm.member_id, m.members_display_name, m.last_activity',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=f.file_submitter',
															'type'		=> 'left'
															)
														)
								)		);
			$outer = $this->DB->execute();

			while( $r = $this->DB->fetch( $outer ) )
			{
				$r['submissions']	= $unique_authors[ $r['member_id'] ]['submissions'];
				
				$topSubmitters[]	= $r;
			}
		}
		
		usort( $topSubmitters, array( $this, '_usortSubmitters' ) );
		
		//-----------------------------------------
		// Top 10 trackers
		//-----------------------------------------
		
		$this->DB->build( array('select'	=> 'COUNT(did) as bitracker, MAX(did) as the_id, dmid',
								'from'		=> 'bitracker_bitracker',
								'group'		=> 'dmid',
								'order'		=> 'bitracker DESC',
								'limit'		=> array( 10 )
						 )		);
		$this->DB->execute();
		
		$unique_dlers	= array();
		$ids			= array();
		
		while( $trackers = $this->DB->fetch() )
		{
			$unique_dlers[ $trackers['dmid'] ] = $trackers;
			
			$ids[] = $trackers['the_id'];
		}
		
		if( count( $ids ) )
		{
			$this->DB->build( array(
										'select'	=> 'd.dfid, d.dtime, d.did, d.dmid' ,
										'from'		=> array( 'bitracker_bitracker' => 'd' ),
										'where'		=> 'd.did IN(' . implode( ',', $ids ) . ')',
										'add_join'	=> array(
															array(
																'select'	=> 'm.member_id, m.members_display_name, m.last_activity',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=d.dmid',
																'type'		=> 'left'
																),
															array(
																'select'	=> 'f.*',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=d.dfid',
																'type'		=> 'left'
																)
															)
								)		);
			$outer = $this->DB->execute();

			while( $r = $this->DB->fetch( $outer ) )
			{
				$r['bitracker']	= $unique_dlers[ $r['member_id'] ]['bitracker'];
				
				$toptrackers[]	= $r;
			}
		}
		
		usort( $toptrackers, array( $this, '_usorttrackers' ) );

		$this->registry->output->html .= $this->html->statsScreen( $form, $topbitracker, $topViews, $topSubmitters, $toptrackers );
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Custom sort function: submitters
	 *
	 * @param	array		$a		First submitter data
	 * @param	array		$b		Second submitter data
	 * @return	@e integer
	 */
	protected function _usortSubmitters( $a, $b )
	{
		if( $a['submissions'] == $b['submissions'] )
		{
			return 0;
		}
		
		return $a['submissions'] > $b['submissions'] ? -1 : 1;
	}
	
	/**
	 * Custom sort function: trackers
	 *
	 * @param	array		$a		First submitter data
	 * @param	array		$b		Second submitter data
	 * @return	@e integer
	 */
	protected function _usorttrackers( $a, $b )
	{
		if( $a['bitracker'] == $b['bitracker'] )
		{
			return 0;
		}
		
		return $a['bitracker'] > $b['bitracker'] ? -1 : 1;
	}
	
	/**
	 * Run a report
	 *
	 * @return	@e void
	 */
	protected function _runReport()
	{
		//-----------------------------------------
		// Time to run?
		//-----------------------------------------
		
		if( $this->request['viewfile'] )
		{
			$this->_generateFileReport( $this->request['viewfile'] );
			return;
		}
		
		if( $this->request['viewmember'] )
		{
			$this->_generateMemberReport( $this->request['viewmember'] );
			return;
		}
		
		//-----------------------------------------
		// Doing file report?
		//-----------------------------------------
		
		if( $this->request['file'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_rep_file' );
			
			//-----------------------------------------
			// Got file? (tm)
			//-----------------------------------------

			$this->DB->build( array( 'select'		=> 'f.*',
											'from'		=> array( 'bitracker_files' => 'f' ),
											'where'		=> $this->DB->buildLower('f.file_name') . " LIKE '%" . strtolower($this->request['file']) . "%'",
											'add_join'	=> array(
																array( 'select'		=> 'm.member_id, m.members_display_name',
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> 'm.member_id=f.file_submitter',
																		'type'		=> 'left'
																	)
																)
											)		);
			$outer = $this->DB->execute();
			
			if( $this->DB->getTotalRows($outer) < 1 )
			{
				$this->registry->output->html .= $this->html->zeroResults();

				//-----------------------------------------
				// Pass to CP output hander
				//-----------------------------------------
				
				$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
				$this->registry->getClass('output')->sendOutput();
			}
			else if( $this->DB->getTotalRows($outer) > 1 )
			{
				$count	= $this->DB->getTotalRows($outer);
				$files	= array();

			 	while( $i = $this->DB->fetch( $outer ) )
			 	{
				 	$files[] = $i;
			 	}
	
				$this->registry->output->html .= $this->html->filesResults( $count, $files );
				
				//-----------------------------------------
				// Pass to CP output hander
				//-----------------------------------------
				
				$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
				$this->registry->getClass('output')->sendOutput();
			}
			else
			{
			 	$i = $this->DB->fetch($outer);
			 	$this->_generateFileReport( $i['file_id'] );
			}
				
			return;							
		}
		
		//-----------------------------------------
		// Doing member report?
		//-----------------------------------------
		
		else if( $this->request['member'] )
		{
			$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_rep_mem' );
			
			// Check for member existence
			
			$this->DB->build( array( 'select'	=> 'member_id, members_display_name',
									 'from'		=> 'members',
									 'where'	=> "members_l_display_name LIKE '%" . strtolower($this->request['member']) . "%'" 
							)		);
			$outer = $this->DB->execute();
			
			if( $this->DB->getTotalRows($outer) < 1 )
			{
				$this->registry->output->html .= $this->html->zeroResults();

				//-----------------------------------------
				// Pass to CP output hander
				//-----------------------------------------
				
				$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
				$this->registry->getClass('output')->sendOutput();
			}
			else if( $this->DB->getTotalRows($outer) > 1 )
			{
				$count	= $this->DB->getTotalRows($outer);
				$files	= array();

			 	while( $i = $this->DB->fetch( $outer ) )
			 	{
				 	$bitracker 	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as dls', 'from' => 'bitracker_bitracker', 'where' => "dmid={$i['member_id']}" ) );
				 	$subs 		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as files', 'from' => 'bitracker_files', 'where' => "file_submitter={$i['member_id']}" ) );

					$i['bitracker']		= $bitracker['dls'];
					$i['submissions']	= $subs['files'];

				 	$members[] = $i;
			 	}
	
				$this->registry->output->html .= $this->html->membersResults( $count, $members );

				//-----------------------------------------
				// Pass to CP output hander
				//-----------------------------------------
				
				$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
				$this->registry->getClass('output')->sendOutput();
			}
			else
			{
			 	$i = $this->DB->fetch($outer);
			 	$this->_generateMemberReport( $i['member_id'] );
			}
				
			return;	
		}
		
		//-----------------------------------------
		// Ooops
		//-----------------------------------------
		
		else
		{
			$this->registry->output->showError( $this->lang->words['s_runningnot'], 11816 );
		}
	}
	
	/**
	 * Generate pie graph
	 *
	 * @return	@e void
	 */
	protected function _getPieImage()
	{
		$groupby = in_array( $this->request['pieimg'], $this->acceptable ) ? $this->request['pieimg'] : 'browsers';

		if( $groupby == 'browsers' )
		{
			require( IPSLib::getAppDir('bitracker') . '/sources/classes/traffic_browsers.php' );/*noLibHook*/
		}
		
		if( $groupby == 'time' )
		{
			$this->DB->build( array(
									'select'	=> 'd.did,d.dip,d.dtime,d.dmid,d.dua,d.dbrowsers,d.dos,d.dsize,' . $this->type . ',d.d' . $groupby . ' as indicator,' . $this->DB->buildFromUnixtime( 'd.dtime', '%M %Y' ) . ' as mygrouping',
									'from'		=> array( 'bitracker_bitracker' => 'd' ),
									'group'		=> 'mygrouping',
									'order'		=> 'num DESC',
									'limit'		=> array( 0, $this->limit ),
									'add_join'	=> array(
														array(
															'select'	=> 'f.file_name',
															'from'		=> array( 'bitracker_files' => 'f' ),
															'where'		=> 'f.file_id=d.dfid'
															)
														)
							)		);
		}
		else
		{
			$this->DB->build( array(
									'select'	=> 'd.did,d.dip,d.dtime,d.dmid,d.dua,d.dbrowsers,d.dos,d.dsize,' . $this->type . ',d.d' . $groupby . ' as indicator',
									'from'		=> array( 'bitracker_bitracker' => 'd' ),
									'group'		=> 'd.d' . $groupby,
									'order'		=> 'num DESC',
									'limit'		=> array( 0, $this->limit ),
									'add_join'	=> array(
														array(
															'select'	=> 'f.file_name',
															'from'		=> array( 'bitracker_files' => 'f' ),
															'where'		=> 'f.file_id=d.dfid'
															)
														)
							)		);
		}

		$outer = $this->DB->execute();
		
		$num 		= 0;	
		$records	= array();
		$labels		= array();
		
		while( $row = $this->DB->fetch($outer) )
		{
			if( $row['num'] > 0 )
			{
				if( $groupby == 'browsers' OR $groupby == 'os' )
				{
					$data	= $this->traffic->returnStatData( $row );
					$key	= $groupby == 'browsers' ? $BROWSERS[ $data['stat_browser_key'] ]['b_title'] : $data['stat_os'];
				}
				else
				{
					if( $groupby == 'fid' )
					{
						$key = $row['file_name'] ? $row['file_name'] : $this->lang->words['s_deleted'];
					}
					else if( $groupby == 'time' )
					{
						$key = $row['mygrouping'];
					}
					else
					{
						$key = $row['d'.$groupby];
					}
				}
				
				$records[$key] = $row['num'];
				$num++;
				
				$labels[$key] = $key;
			}
		}

		if( $this->request['type'] == 'bw' )
		{
			$title = $this->lang->words['s_bwusage'];
		}
		else
		{
			$title = $this->lang->words['s_bitracker'];
		}

		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classGraph.php', 'classGraph' );
		$graph			= new $classToLoad();
		$graph->options['title'] = sprintf( $this->lang->words['piechart_title'], $title, $this->text[$groupby] );
		$graph->options['width'] = 650;
		$graph->options['height'] = $num < 14 ? '400' : $num*25;
		$graph->options['style3D'] = 1;
		$graph->options['font']			= IPS_PUBLIC_PATH . 'style_captcha/captcha_fonts/DejaVuSans.ttf';
		
		$graph->addLabels( $labels );
		$graph->addSeries( 'test', $records );

		$graph->options['charttype'] = 'Pie';
		$graph->display();
	}

	/**
	 * Generate member report
	 *
	 * @param	integer		$mid		Member ID
	 * @return	@e void
	 */
	protected function _generateMemberReport( $mid )
	{
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_rep_mem' );
		
		$mid	= intval( $mid );
		$member = IPSMember::load( $mid );

		if( !$member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['s_mem404'], 11817 );
		}
		
		$member['_cache']	= IPSMember::unpackMemberCache( $member['members_cache'] );

		//-----------------------------------------
		// We changing file submission permission?
		//-----------------------------------------
		
		if( $this->request['change'] == 1 )
		{
			$allowed	= intval($this->request['allow_submit']);

			IPSMember::packMemberCache( $member['member_id'], array( 'block_file_submissions' => $allowed ? 0 : 1 ), $member['_cache'] );
			
			$member['_cache']['block_file_submissions']	= $allowed ? 0 : 1;
			
			$this->registry->output->global_message = $this->lang->words['mem_updated_success'];
		}
		
		$stats		= array();
				
		//-----------------------------------------
		// Global stats
		//-----------------------------------------
		
		$stats = $this->DB->buildAndFetch( array(	'select'	=> 'SUM( file_size ) as total_size, AVG( file_size ) as total_avg_size, COUNT( file_size ) as total_uploads',
													'from'		=> 'bitracker_files' 
												) 		);

		$stats = array_merge( $stats,
								$this->DB->buildAndFetch( array(	'select'	=> 'SUM( dsize ) as total_transfer, COUNT( dsize ) as total_viewed',
																	'from'		=> 'bitracker_bitracker' 
														) 		)
							);

		$stats = array_merge( $stats,
								$this->DB->buildAndFetch( array(	'select' 	=> 'SUM( file_size ) as user_size, AVG( file_size ) as user_avg_size, COUNT( file_size ) as user_uploads',
																	'from'		=> 'bitracker_files',
																	'where'		=> "file_submitter={$mid}" 
														) 		)
							);

		$stats = array_merge( $stats,
								$this->DB->buildAndFetch( array(	'select' 	=> 'SUM( dsize ) as user_transfer, COUNT( dsize ) as user_viewed',
																	'from'		=> 'bitracker_bitracker',
																	'where'		=> "dmid={$mid}" 
														) 		)
							);
							
		$stats['diskspace_percent']	= $stats['total_size'] ? ( round( $stats['user_size'] / $stats['total_size'], 2 ) * 100 ) . '%' : '0%';
		$stats['uploads_percent']	= $stats['total_uploads'] ? ( round( $stats['user_uploads'] / $stats['total_uploads'], 2 ) * 100 ) . '%' : '0%';
		
		if( $this->settings['bit_logallbitracker'] )
		{
		 	$stats['transfer_percent']	= $stats['total_transfer'] ? ( round( $stats['user_transfer'] / $stats['total_transfer'], 2 ) * 100 ) . '%' : '0%';
		 	$stats['bitracker_percent']	= $stats['total_viewed'] ? ( round( $stats['user_viewed'] / $stats['total_viewed'], 2 ) * 100 ) . '%' : '0%';
		}

		//-----------------------------------------
		// User Submissions
		//-----------------------------------------

		$submissions	= '';
		$bitracker		= '';
		$_usbms			= intval($this->request['st']);
		
		$_count			= $this->DB->buildAndFetch( array( 'select' => 'count(*) as files', 'from' => 'bitracker_files', 'where' => "file_submitter={$mid}" ) );
		
	 	$this->DB->build( array( 'select'	=> '*',
								 'from'		=> 'bitracker_files',
								 'where'	=> "file_submitter={$mid}",
								 'order'	=> 'file_submitted DESC',
								 'limit'	=> array( $_usbms, 50 ),
							)		); 
	 	$outer = $this->DB->execute();

	 	while( $i = $this->DB->fetch($outer) )
	 	{
		 	$i['track_percent']	= $stats['total_viewed'] ? round( $i['file_bitracker'] / $stats['total_viewed'], 2 ) * 100 : 0;
		 	$i['broken']			= $i['file_broken'] ? "<img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='X' />" : "<img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' alt='X' />";
		 	
		 	$submissions .= $this->html->memberSubmissions( $i );
	 	}
	 	
	 	$_usPages		= $this->registry->output->generatePagination( array( 
																			'totalItems'		=> $_count['files'],
																			'itemsPerPage'		=> 50,
																			'currentStartValue'	=> $_usbms,
																			'baseUrl'			=> "{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$mid}",
																)	 );

		//-----------------------------------------
		// User bitracker
		//-----------------------------------------

	 	if( $this->settings['bit_logallbitracker'] )
		{
			$_usdls			= intval($this->request['dls']);
			$_count			= $this->DB->buildAndFetch( array( 'select' => 'count(*) as bitracker', 'from' => 'bitracker_bitracker', 'where' => "dmid={$mid}" ) );
			
		 	$this->DB->build( array( 'select'		=> 'd.*',
									 'from'			=> array( 'bitracker_bitracker' => 'd' ),
									 'where'		=> "dmid={$mid}",
									 'order'		=> 'dtime DESC',
									 'limit'		=> array( $_usdls, 50 ),
									 'add_join'		=> array(
														array( 'select'		=> 'f.file_name, f.file_bitracker',
																'from'		=> array( 'bitracker_files' => 'f' ),
																'where'		=> 'f.file_id=d.dfid',
																'type'		=> 'left',
															)
														)
								)		); 
		 	$outer = $this->DB->execute();

		 	while( $i = $this->DB->fetch($outer) )
		 	{
			 	$i['transfer_percent']	= $stats['user_transfer'] ? round( $i['dsize'] / $stats['user_transfer'], 2 ) * 100 : 0;
			 	
			 	$data 		= $this->traffic->returnStatData( $i );

			 	$i['browser_img']		= $this->traffic->getItemImage( 'browsers', $data['stat_browser_key'] );
			 	$i['browser_txt']		= $data['stat_browser'];
			 	
			 	$i['os_img']			= $this->traffic->getItemImage( 'os', $data['stat_os_key'] );
			 	$i['os_txt']			= $data['stat_os'];
				
			 	$bitracker	.= $this->html->memberbitracker( $i );
		 	}
		 	
		 	$_dlPages		= $this->registry->output->generatePagination( array( 
																				'totalItems'		=> $_count['bitracker'],
																				'itemsPerPage'		=> 50,
																				'currentStartValue'	=> $_usdls,
																				'startValueKey'		=> 'dls',
																				'baseUrl'			=> "{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$mid}",
																	)	 );
	 	}

		$this->registry->output->html .= $this->html->membersReport( $member, $stats, $submissions, $bitracker, $_usPages, $_dlPages );
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Generate file report
	 *
	 * @param	integer		$fid		File ID
	 * @return	@e void
	 */
	protected function _generateFileReport( $fid )
	{
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_rep_file' );
		
		$fid 		= intval( $fid );
		$bandwidth	= array();
		$bitracker	= '';

		//-----------------------------------------
		// We changing file ownership?
		//-----------------------------------------
		
		if( $this->request['change'] == 1 )
		{
			$name = trim(strtolower($this->request['member']));

			$member = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_display_name='{$name}'" ) );

			if( $member['member_id'] )
			{
				$this->DB->update( 'bitracker_files', array( 'file_submitter' => $member['member_id'] ), "file_id={$fid}" );
				
				$this->registry->categories->rebuildFileinfo();
				$this->cache->rebuildCache( 'bit_stats', 'bitracker' );
				$this->cache->rebuildCache( 'bit_cats', 'bitracker' );
			}
			else
			{
				$this->registry->output->global_message = $this->lang->words['s_mem404'];
			}
		}
		
		//-----------------------------------------
		// Get the file
		//-----------------------------------------
		
		$file = $this->DB->buildAndFetch( array( 	'select' 	=> 'f.*',
													'from'		=> array( 'bitracker_files' => 'f' ),
													'where' 	=> "f.file_id={$fid}",
													'add_join'	=> array(
																		array( 'select'		=> 'm.members_display_name',
																				'from'		=> array( 'members' => 'm' ),
																				'where'		=> 'm.member_id=f.file_submitter',
																				'type'		=> 'left'
																			),
																		array( 'select'		=> 'mm.members_display_name as app_name',
																				'from'		=> array( 'members' => 'mm' ),
																				'where'		=> 'mm.member_id=f.file_approver',
																				'type'		=> 'left'
																			),
																		array( 'select'		=> 'c.cname',
																				'from'		=> array( 'bitracker_categories' => 'c' ),
																				'where'		=> 'c.cid=f.file_cat',
																				'type'		=> 'left'
																			),
																		)
												) 		);

		if( !$file['file_id'] )
		{
			$this->registry->output->showError( $this->lang->words['s_filereport_bad'], 11818 );
		}
		
		//-----------------------------------------
		// We changing file ownership?
		//-----------------------------------------
		
		if( $this->request['change'] == 1 AND $file['file_topicid'] > 0 AND $member['member_id'] )
		{
			$topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $file['file_topicid'] ) );
			
			if( $topic['tid'] )
			{
				$this->DB->update( 'posts', array( 'author_id' => $member['member_id'], 'author_name' => $file['members_display_name'] ), 'pid=' . $topic['topic_firstpost'] );

				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
				$mod	=  new $classToLoad( $this->registry );
				$mod->init( $this->registry->class_forums->forum_by_id[ $topic['forum_id'] ], $topic );
				$mod->rebuildTopic( $topic['tid'], 1 );
			}
		}
		
		//-----------------------------------------
		// More general data
		//-----------------------------------------

		if( $this->settings['bit_logallbitracker'] )
		{
		 	$bandwidth = $this->DB->buildAndFetch( array( 	'select' 	=> 'COUNT( * ) AS bitracker, SUM( dsize ) AS transfer',
															'from'		=> 'bitracker_bitracker',
															'where' 	=> "dfid='{$file['file_id']}'" 
													) 		);
		}

		//-----------------------------------------
		// Get logged bitracker
		//-----------------------------------------

	 	if( $this->settings['bit_logallbitracker'] )
		{
			$_usdls			= intval($this->request['dls']);
			$_count			= $this->DB->buildAndFetch( array( 'select' => 'count(*) as bitracker', 'from' => 'bitracker_bitracker', 'where' => "dfid={$fid}" ) );

		 	$this->DB->build( array( 'select'		=> 'd.*',
									 'from'			=> array( 'bitracker_bitracker' => 'd' ),
									 'where'		=> "dfid={$fid}",
									 'order'		=> 'dtime DESC',
									  'limit'		=> array( $_usdls, 50 ),
									 'add_join'		=> array(
														array( 'select'		=> 'm.members_display_name',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=d.dmid',
																'type'		=> 'left',
															)
														)
								)		); 
		 	$outer = $this->DB->execute();

		 	while( $i = $this->DB->fetch($outer) )
		 	{
			 	$data 		= $this->traffic->returnStatData( $i );

			 	$i['browser_img']		= $this->traffic->getItemImage( 'browsers', $data['stat_browser_key'] );
			 	$i['browser_txt']		= $data['stat_browser'];
			 	
			 	$i['os_img']			= $this->traffic->getItemImage( 'os', $data['stat_os_key'] );
			 	$i['os_txt']			= $data['stat_os'];
				
			 	$bitracker	.= $this->html->filebitracker( $i );
		 	}
		 	
		 	$_dlPages = $this->registry->output->generatePagination( array( 'totalItems'		=> $_count['bitracker'],
																			'itemsPerPage'		=> 50,
																			'currentStartValue'	=> $_usdls,
																			'startValueKey'		=> 'dls',
																			'baseUrl'			=> "{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewfile={$fid}",
																	)		);
	 	}
	 	
	 	$this->registry->output->html .= $this->html->fileReport( $file, $bandwidth, $bitracker, $_dlPages );
	 	
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
}