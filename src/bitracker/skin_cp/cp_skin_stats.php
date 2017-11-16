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

/**
 *
 * @class		cp_skin_stats
 * @brief		Stats skin file
 */

class cp_skin_stats
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
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
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}

/**
 * Stats screen
 *
 * @param	array		$form				Form elements
 * @param	array		$topbitracker		Top bitracker
 * @param	array		$topViews			Top views
 * @param	array		$topSubmitters		Top submitters
 * @param	array		$toptrackers		Top trackers
 * @return	@e string	HTML
 */
public function statsScreen( $form, $topbitracker=array(), $topViews=array(), $topSubmitters=array(), $toptrackers=array() ) {

$filereport		= $this->registry->output->formInput( "file", $this->request['file'] );
$showstats		= sprintf ( $this->lang->words['d_showstats'], $form['type'], $form['groupby'], $form['limit'] );
$form['num']	= $form['num'] ? '[ ' . $form['num'] . ' ]' : '';

$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['d_stats']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_statistics']}</h3>
	<table class='ipsTable'>
		<tr>
			<th>
				<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
					{$showstats}&nbsp;<input type='submit' value='{$this->lang->words['d_update']}' class='button primary' />
				</form>
			</th>
		</tr>
EOF;

if( $form['graphcharts'] )
{
	$IPBHTML .= <<<EOF
		<tr>
			<td class='center'>
				<img src='{$form['piechart']}' alt='{$this->lang->words['d_piechart']}' id='piechart' />
			</td>
		</tr>
EOF;
}

$_fileOwner = $this->registry->output->formInput( 'member', $this->request['member'], '', 0, 'text', "autocomplete='off'" );

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=report' id='runReport' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['d_runreports']}</h3>
	<table class='ipsTable'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['d_filereport']}</strong>
			</td>
			<td class='field_field'>
				{$filereport}<br />
				<span class='desctext'>{$this->lang->words['d_filereport_info']}</span> 
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['d_memreport']}</strong>
			</td>
			<td class='field_field'>
				{$_fileOwner}<br />
				<span class='desctext'>{$this->lang->words['d_memreport_info']}</span> 
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['d_runbutton']}' class='button primary' />
	</div>
</div>
</form>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['d_top10tracked']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='20%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='20%'>{$this->lang->words['c_bitracker']}</th>
		</tr>
EOF;

if( is_array($topbitracker) AND count($topbitracker) )
{
	foreach( $topbitracker as $r )
	{
		$user_link	= $r['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$r['file_submitter']}'>{$r['members_display_name']}</a>" : $this->lang->words['o_guest'];
		
		$IPBHTML .= <<<EOF
		<tr>
			<td width='30%'><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$r['file_id']}'>{$r['file_name']}</a></td>
			<td width='20%'>{$user_link}</td>
			<td width='30%'>{$this->lang->getDate( $r['file_submitted'], 'SHORT' )}</td>
			<td width='20%'>{$r['file_bitracker']}</td>
		</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['d_top10viewed']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='20%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='20%'>{$this->lang->words['d_views']}</th>
		</tr>
EOF;

if( is_array($topViews) AND count($topViews) )
{
	foreach( $topViews as $r )
	{
		$user_link	= $r['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$r['file_submitter']}'>{$r['members_display_name']}</a>" : $this->lang->words['o_guest'];
		
		$IPBHTML .= <<<EOF
		<tr>
			<td width='30%'><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$r['file_id']}'>{$r['file_name']}</a></td>
			<td width='20%'>{$user_link}</td>
			<td width='30%'>{$this->lang->getDate( $r['file_submitted'], 'SHORT' )}</td>
			<td width='20%'>{$r['file_views']}</td>
		</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['d_top10submitters']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['c_member']}</th>
			<th width='10%'>{$this->lang->words['d_submissions']}</th>
			<th width='50%'>{$this->lang->words['d_lastsubmit']}</th>
			<th width='20%'>{$this->lang->words['d_lastactivity']}</th>
		</tr>
EOF;

if( is_array($topSubmitters) AND count($topSubmitters) )
{
	foreach( $topSubmitters as $r )
	{
		$user_link	= $r['file_submitter'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$r['file_submitter']}'>{$r['members_display_name']}</a>" : $this->lang->words['o_guest'];
		
		$IPBHTML .= <<<EOF
		<tr>
			<td>{$user_link}</td>
			<td>{$r['submissions']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$r['file_id']}'>{$r['file_name']}</a> ({$this->lang->words['d_submittedon']} {$this->lang->getDate( $r['file_submitted'], 'TINY' )})</td>
			<td>{$this->lang->getDate( $r['last_activity'], 'TINY' )}</td>
		</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['d_top10track']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='20%'>{$this->lang->words['c_member']}</th>
			<th width='10%'>{$this->lang->words['c_bitracker']}</th>
			<th width='50%'>{$this->lang->words['d_lasttrack']}</th>
			<th width='20%'>{$this->lang->words['d_lastactivity']}</th>
		</tr>
EOF;

if( is_array($toptrackers) AND count($toptrackers) )
{
	foreach( $toptrackers as $r )
	{
		$user_link	= $r['member_id'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$r['member_id']}'>{$r['members_display_name']}</a>" : $this->lang->words['o_guest'];
		
		$IPBHTML .= <<<EOF
		<tr>
			<td>{$user_link}</td>
			<td>{$r['bitracker']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$r['file_id']}'>{$r['file_name']}</a> ({$this->lang->words['d_trackedon']} {$this->lang->getDate( $r['dtime'], 'TINY' )})</td>
			<td>{$this->lang->getDate( $r['last_activity'], 'TINY' )}</td>
		</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * No results row
 *
 * @return	@e string	HTML
 */
public function zeroResults() {

if ( $this->request['file'] )
{
	$title	= sprintf( $this->lang->words['s_running'], $this->request['file'] );
}
elseif ( $this->request['member'] )
{
	$title	= sprintf( $this->lang->words['s_runningmem'], $this->request['member'] );
}

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_0results']}</h3>
	<table class='ipsTable'>
		<tr>
			<td>{$this->lang->words['d_noresults']}</td>
		</tr>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * File report search results
 *
 * @param	integer		$count		Count
 * @param	array		$files		Files
 * @return	@e string	HTML
 */
public function filesResults( $count, $files ) {

if( $this->request['file'] )
{
	$title	= sprintf( $this->lang->words['s_running'], $this->request['file'] );
}
else if( $this->request['member'] )
{
	$title	= sprintf( $this->lang->words['s_runningmem'], $this->request['member'] );
}

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$count} {$this->lang->words['d_results']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='10%'>{$this->lang->words['c_bitracker']}</th>
			<th width='10%'>{$this->lang->words['d_views']}</th>
			<th width='25%'>{$this->lang->words['d_fauthor']}</th>
			<th width='25%'>{$this->lang->words['d_submittedon']}</th>
		</tr>
EOF;

foreach( $files as $r )
{
	$user_link	= $r['member_id'] ? "<a href='{$this->settings['board_url']}/index.php?showuser={$r['member_id']}' target='_blank'>{$r['members_display_name']}</a>" : $this->lang->words['o_guest'];
	
	$IPBHTML .= <<<EOF
	<tr>
		<td><a href='{$this->settings['base_url']}&amp;app=bitracker&amp;module=information&amp;section=stats&amp;do=report&amp;viewfile={$r['file_id']}'><b>{$r['file_name']}</b></a></td>
		<td>{$r['file_bitracker']}</td>
		<td>{$r['file_views']}</td>
		<td>{$user_link}</td>
		<td>{$this->lang->getDate( $r['file_submitted'], 'TINY' )}</td>
	</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Member report search results
 *
 * @param	integer		$count		Count
 * @param	array		$members	Members
 * @return	@e string	HTML
 */
public function membersResults( $count, $members ) {

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$count} {$this->lang->words['d_results']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='50%'>{$this->lang->words['d_membername']}</th>
			<th width='25%'>{$this->lang->words['c_bitracker']}</th>
			<th width='25%'>{$this->lang->words['d_submissions']}</th>
		</tr>
EOF;

foreach( $members as $r )
{
	$IPBHTML .= <<<EOF
	<tr>
		<td><a href='{$this->settings['base_url']}&amp;app=bitracker&amp;module=information&amp;section=stats&amp;do=report&amp;viewmember={$r['member_id']}'><b>{$r['members_display_name']}</b></a></td>
		<td>{$r['bitracker']}</td>
		<td>{$r['submissions']}</td>
	</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member report
 *
 * @param	array		$member			Member data
 * @param	array		$stats			Stats
 * @param	array		$submissions	Submissions
 * @param	array		$bitracker		bitracker
 * @param	string		$_usPages		Page links for user submissions
 * @param	string		$_dlPages		Page links for user bitracker
 * @return	@e string	HTML
 */
public function membersReport( $member, $stats, $submissions, $bitracker, $_usPages='', $_dlPages='' ) {

$stats['user_size']			= IPSLib::sizeFormat( intval($stats['user_size']) );
$stats['user_avg_size']		= IPSLib::sizeFormat( intval($stats['user_avg_size']) );
$stats['total_avg_size']	= IPSLib::sizeFormat( intval($stats['total_avg_size']) );
$stats['user_transfer']		= IPSLib::sizeFormat( intval($stats['user_transfer']) );
$title						= sprintf( $this->lang->words['s_memreport'], $member['members_display_name'] );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_usageoverview']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['d_suboverview']}</th>
		<tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaldiskaaaa']} {$member['members_display_name']}</strong></td>
			<td>{$stats['user_size']}</td>
			<td><strong class='title'>{$this->lang->words['d_percentdisk']}</strong></td>
			<td>{$stats['diskspace_percent']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaluploads']} {$member['members_display_name']}</strong></td>
			<td>{$stats['user_uploads']}</td>
			<td><strong class='title'>{$this->lang->words['d_percentupload']}</strong></td>
			<td>{$stats['uploads_percent']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_averagesize']} {$member['members_display_name']}</strong></td>
			<td>{$stats['user_avg_size']}</td>
			<td><strong class='title'>{$this->lang->words['d_averagesizeuser']}</strong></td>
			<td>{$stats['total_avg_size']}</td>
		</tr>
EOF;

if( $this->settings['bit_logallbitracker'] )
{
	$IPBHTML .= <<<EOF
		<tr>
			<th colspan='4'>{$this->lang->words['d_banoverview']}</th>
		<tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaltransfer']} {$member['members_display_name']}</strong></td>
			<td>{$stats['user_transfer']}</td>
			<td><strong class='title'>{$this->lang->words['d_percenttransfer']}</strong></td>
			<td>{$stats['transfer_percent']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaldown']}</strong></td>
			<td>{$stats['user_viewed']}</td>
			<td><strong class='title'>{$this->lang->words['d_percentdown']}</strong></td>
			<td>{$stats['bitracker_percent']}</td>
		</tr>
	
EOF;
}

$_allowed			= $member['_cache']['block_file_submissions'] ? 0 : 1;
$_allowSubmissions	= $this->registry->output->formYesNo( 'allow_submit', $_allowed );

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$member['member_id']}&amp;change=1' id='runReport' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['d_changecansubmit']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_allow_submissions']}</strong>
				</td>
				<td class='field_field'>
					{$_allowSubmissions}&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' class='realbutton' value='{$this->lang->words['d_changesubmitstat']}' /> 
				</td>
			</tr>
		</table>
	</div>
</form>
<br />

<div>{$_usPages}</div>
<br class='clear' />
<div class='acp-box'>
	<h3>{$this->lang->words['d_usersubmission']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='10%'>{$this->lang->words['d_fsize']}</th>
			<th width='10%'>{$this->lang->words['c_bitracker']}</th>
			<th width='15%'>{$this->lang->words['d_percentofdown']}</th>
			<th width='7%'>{$this->lang->words['d_views']}</th>
			<th width='8%'>{$this->lang->words['d_rating']}</th>
			<th width='10%'>{$this->lang->words['d_broken']}</th>
			<th width='10%'>{$this->lang->words['d_local']}</th>
		</tr>
		{$submissions}
	</table>
</div>
<br />
<div>{$_usPages}</div>
EOF;

if( $this->settings['bit_logallbitracker'] )
{
	$IPBHTML .= <<<EOF
<br class='clear' />
<div>{$_dlPages}</div>
<br class='clear' />
<div class='acp-box'>
	<h3>{$this->lang->words['d_userbitracker']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='35%'>{$this->lang->words['d_fname']}</th>
			<th width='8%'>{$this->lang->words['d_fsize']}</th>
			<th width='12%'>{$this->lang->words['d_transferpercent']}</th>
			<th width='8%'>{$this->lang->words['c_bitracker']}</th>
			<th width='15%'>{$this->lang->words['s_browsers']}</th>
			<th width='15%'>{$this->lang->words['d_os']}</th>
			<th width='10%'>{$this->lang->words['s_ip']}</th>
		</tr>
		{$bitracker}
	</table>
</div>
<br />
<div>{$_dlPages}</div>
<br class='clear' />
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Member submissions row
 *
 * @param	array		$row		File data
 * @return	@e string	HTML
 */
public function memberSubmissions( $row ) {

$filesize = IPSLib::sizeFormat( $row['file_size'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewfile={$row['file_id']}' title='{$this->lang->words['d_viewreport']}'><b>{$row['file_name']}</b></a> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewfile={$row['file_id']}' title='{$this->lang->words['d_viewreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['d_byfile']}'></a></td>
	<td>{$filesize}</td>
	<td>{$row['file_bitracker']}</td>
	<td>{$row['track_percent']}%</td>
	<td>{$row['file_views']}</td>
	<td>{$row['file_rating']}</td>
	<td>{$row['broken']}</td>
	<td>{$row['local']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member bitracker row
 *
 * @param	array		$row	File data
 * @return	@e string	HTML
 */
public function memberbitracker( $row ) {

$filesize	= IPSLib::sizeFormat( $row['dsize'] );


$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewfile={$row['dfid']}' title='{$this->lang->words['d_viewreport']}'><b>{$row['file_name']}</b></a> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewfile={$row['dfid']}' title='{$this->lang->words['d_viewreport']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['d_byreport']}'></a></td>
	<td>{$filesize}</td>
	<td>{$row['transfer_percent']}</td>
	<td>{$row['file_bitracker']}%</td>
	<td><img src='{$this->settings['public_dir']}style_extra/bitracker_traffic_images/{$row['browser_img']}' alt='{$this->lang->words['d_img']}' /> (<i>{$row['browser_txt']}</i>)</td>
	<td><img src='{$this->settings['public_dir']}style_extra/bitracker_traffic_images/{$row['os_img']}' alt='{$this->lang->words['d_img']}' /> (<i>{$row['os_txt']}</i>)</td>
	<td>{$row['dip']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * File report
 *
 * @param	array		$file			File data
 * @param	array		$bandwidth		Bandwidth data
 * @param	array		$bitracker		Users who have tracked
 * @param	string		$_dlPages		Page links
 * @return	@e string	HTML
 */
public function fileReport( $file, $bandwidth, $bitracker, $_dlPages='' ) {

$file['file_size']			= IPSLib::sizeFormat( $file['file_size'] );
$bandwidth['transfer']		= IPSLib::sizeFormat( intval($bandwidth['transfer']) );
$bandwidth['bitracker']		= intval($bandwidth['bitracker']);

$user_link					= $file['file_submitter'] ? "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$file['file_submitter']}' title='{$this->lang->words['d_viewreportm']}'>{$file['members_display_name']}</a> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$file['file_submitter']}' title='{$this->lang->words['d_viewreportm']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['d_bysender']}'></a>" : $this->lang->words['o_guest'];
$approver_link				= $file['app_name'] ? "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$file['file_approver']}' title='{$this->lang->words['d_viewreportm']}'>{$file['app_name']}</a> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$file['file_approver']}' title='{$this->lang->words['d_viewreportm']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['d_bysender']}'></a>" : $this->lang->words['d_na'];
$approved_text				= $file['file_open'] ? $this->lang->words['d_yes'] : $this->lang->words['d_no'];
$broken_text				= $file['file_broken'] ? $this->lang->words['d_yes'] : $this->lang->words['d_no'];

$votes 						= unserialize( $file['file_votes'] );
$total_votes				= is_array($votes) ? count($votes) : 0;

$title						= sprintf( $this->lang->words['s_filereport'], $file['file_name'] );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>							
<div class='acp-box'>
	<h3>{$this->lang->words['d_fileoverview']}</h3>
	<table class='ipsTable'>
		<tr>
			<th colspan='4'>{$this->lang->words['d_geninfo']}</th>
		<tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_uploadedby']}</strong></td>
			<td>{$user_link}</td>
			<td><strong class='title'>{$this->lang->words['d_approvedby']}</strong></td>
			<td>{$approver_link}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_fname']}</strong></td>
			<td>{$file['file_name']}</td>
			<td><strong class='title'>{$this->lang->words['d_incategory']}</strong></td>
			<td>{$file['cname']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_approved']}</strong></td>
			<td>{$approved_text}</td>
			<td><strong class='title'>{$this->lang->words['d_broken']}</strong></td>
			<td>{$broken_text}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_fsize']}</strong></td>
			<td>{$file['file_size']}</td>
			<td><strong class='title'>{$this->lang->words['d_ftype']}</strong></td>
			<td>{$file['mime_extension']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_fsubmitted']}</strong></td>
			<td>{$this->lang->getDate( $file['file_submitted'], 'LONG' )}</td>
			<td><strong class='title'>{$this->lang->words['d_lastupdated']}</strong></td>
			<td>{$this->lang->getDate( $file['file_updated'], 'LONG' )}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['c_bitracker']}</strong></td>
			<td>{$file['file_bitracker']}</td>
			<td><strong class='title'>{$this->lang->words['d_views']}</strong></td>
			<td>{$file['file_views']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['c_rating']}</strong></td>
			<td>{$file['file_rating']}</td>
			<td><strong class='title'>{$this->lang->words['d_numrating']}</strong></td>
			<td>{$total_votes}</td>
		</tr>

EOF;

if( $this->settings['bit_logallbitracker'] )
{
	$IPBHTML .= <<<EOF
		<tr>
			<th colspan='4'>{$this->lang->words['d_bndover']}</th>
		<tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['c_bitracker']}</strong></td>
			<td>{$bandwidth['bitracker']}</td>
			<td><strong class='title'>{$this->lang->words['d_bwusage']}</strong></td>
			<td>{$bandwidth['transfer']}</td>
		</tr>
	
EOF;
}

$_fileOwner = $this->registry->output->formInput( 'member', $this->request['member'], '', 0, 'text', "autocomplete='off'" );

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewfile={$file['file_id']}&amp;change=1' id='runReport' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['d_changefowner']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_changeto']}</strong>
				</td>
				<td class='field_field'>
					{$_fileOwner}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_changeowner']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

if( $this->settings['bit_logallbitracker'] )
{
	$IPBHTML .= <<<EOF
<br />
<div>{$_dlPages}</div>
<br class='clear' />
<div class='acp-box'>
	<h3>{$this->lang->words['d_fbitracker']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='35%'>{$this->lang->words['c_member']}</th>
			<th width='25%'>{$this->lang->words['d_date']}</th>
			<th width='15%'>{$this->lang->words['s_browsers']}</th>
			<th width='15%'>{$this->lang->words['d_os']}</th>
			<th width='10%'>{$this->lang->words['s_ip']}</th>
		</tr>
		{$bitracker}
	</table>
</div>
<br />
<div>{$_dlPages}</div>
<br class='clear' />
EOF;
}


//--endhtml--//
return $IPBHTML;
}

/**
 * trackers row
 *
 * @param	array		$row	download result
 * @return	@e string	HTML
 */
public function filebitracker( $row ) {

$filesize	= IPSLib::sizeFormat( $row['dsize'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$row['dmid']}' title='{$this->lang->words['d_viewreportm']}'><b>{$row['members_display_name']}</b></a> <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=report&amp;viewmember={$row['dmid']}' title='{$this->lang->words['d_viewreportm']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' alt='{$this->lang->words['d_bymember']}'></a></td>
	<td>{$this->lang->getDate( $row['dtime'], 'SHORT' )}</td>
	<td><img src='{$this->settings['public_dir']}style_extra/bitracker_traffic_images/{$row['browser_img']}' alt='{$this->lang->words['d_img']}' /> (<i>{$row['browser_txt']}</i>)</td>
	<td><img src='{$this->settings['public_dir']}style_extra/bitracker_traffic_images/{$row['os_img']}' alt='{$this->lang->words['d_img']}' /> (<i>{$row['os_txt']}</i>)</td>
	<td>{$row['dip']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

}