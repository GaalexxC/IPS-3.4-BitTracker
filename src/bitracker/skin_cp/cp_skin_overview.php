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
 * @class		cp_skin_overview
 * @brief		Overview skin file
 */
class cp_skin_overview
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
 * Overview screen
 *
 * @param	array		$data		Data to show
 * @param	array		$cerror		Connection Errors
 * @param	array		$latest		Latest files
 * @param	array		$pending	Pending files
 * @param	array		$broken		Broken files
 * @return	@e string	HTML
 */
public function overviewSplash( $data, $cerror=array(), $latest=array(), $pending=array(), $broken=array() ) {

$IPBHTML = "";
//--starthtml--//

$onlineStatus = $this->settings['bit_online'] ? 'accept' : 'delete';



$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['d_overview']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_information']}</h3>
					
	<table class='ipsTable'>
		<tr>
			<td width='20%'><strong class='title'>{$this->lang->words['d_sysonline']}</strong></td>
			<td width='20%'><img src='{$this->settings['skin_acp_url']}/images/icons/{$onlineStatus}.png' alt='' /></td>
			<td width='30%'><strong class='title'>{$this->lang->words['d_totalbw']}</strong></td>
			<td width='30%'>{$data['overview']['total_bw']}</td>		
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaldisk']}</strong></td>
			<td>{$data['overview']['total_size']}</td>
			<td><strong class='title'>{$this->lang->words['d_currentbw']}</strong></td>
			<td>{$data['overview']['this_bw']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totalfiles']}</strong></td>
			<td>{$data['overview']['total_files']}</td>
			<td><strong class='title'>{$this->lang->words['d_largest']} ({$data['overview']['largest_file_size']})</strong></td>
			<td>{$data['overview']['largest_file_name']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totaldls']}</strong></td>
			<td>{$data['overview']['total_bitracker']}</td>
			<td><strong class='title'>{$this->lang->words['d_mostviewed']} ({$data['overview']['views_file_views']})</strong></td>
			<td>{$data['overview']['views_file_name']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['d_totalviews']}</strong></td>
			<td>{$data['overview']['total_views']}</td>
			<td><strong class='title'>{$this->lang->words['d_mostdl']} ({$data['overview']['dls_file_bitracker']})</strong></td>
			<td>{$data['overview']['dls_file_name']}</td>
			<td></td>
			<td></td>
		</tr>
	</table>
</div>
<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_runreports']}</h3>
	
	<form action='{$this->settings['base_url']}&amp;module=information&amp;section=stats&amp;do=report' method='post' id='runReport'>
		<table class='ipsTable double_pad'>
			<tr>
				<td width='20%' align='right'><strong class='title'>{$this->lang->words['d_memreport']}</strong></td>
				<td width='20%'>{$data['reports']['member']}</td>
				<td width='20%' align='right'><strong class='title'>{$this->lang->words['d_filereport']}</strong></td>
				<td width='40%'>{$data['reports']['file']}</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_runbutton']}' class='button primary' />
		</div>
	</form>
</div>
<br />
<div class="acp-box">
	<h3>Last 5 connection errors</h3>
	<table class='ipsTable'>
		<tr>
			<th width='12%'>Connection Date</th>
			<th width='12%'>Member name</th>
			<th width='12%'>Connection IP</th>
			<th width='12%'>Torrent file</th>
			<th width='12%'>Connection Client</th>
			<th width='40%'>Connection Error</th>
		</tr>
HTML;

foreach( $cerror as $row )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['date']}</td>
			<td>{$row['user_link']}</td>
			<td><a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$row['request_ip']}'>{$row['request_ip']}</a></td>
			<td>{$row['torrent_name']}</td>
			<td>{$row['request_client']}</td>
			<td>{$row['error_string']}</td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>

<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_last5']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='30%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='10%'>{$this->lang->words['d_approved']}</th>
		</tr>
HTML;

foreach( $latest as $row )
{
	$_image = $row['file_open'] ? 'accept' : 'cross';
	
	$IPBHTML .= <<<HTML
		<tr>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$row['file_id']}'>{$row['file_name']}</a></td>
			<td>{$row['user_link']}</td>
			<td>{$row['date']}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/{$_image}.png' alt='' /></td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>

<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_pendapprove']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='30%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='10%'>{$this->lang->words['d_approvequest']}</th>
		</tr>
HTML;

foreach( $pending as $row )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$row['file_id']}'>{$row['file_name']}</a></td>
			<td>{$row['user_link']}</td>
			<td>{$row['date']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;module=moderate&amp;section=moderate&amp;do=togglefile&amp;id={$row['file_id']}&amp;secure_key={$this->member->form_hash}'><img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' /></a></td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>

<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_reportbroke']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_fname']}</th>
			<th width='30%'>{$this->lang->words['d_fauthor']}</th>
			<th width='30%'>{$this->lang->words['d_submitted']}</th>
			<th width='10%'>{$this->lang->words['d_removequest']}</th>
		</tr>
HTML;

foreach( $broken as $row )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$row['file_id']}'>{$row['file_name']}</a></td>
			<td>{$row['user_link']}</td>
			<td>{$row['date']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;module=moderate&amp;section=moderate&amp;do=delete&amp;id={$row['file_id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' /></a></td>
		</tr>
HTML;
}
$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

}