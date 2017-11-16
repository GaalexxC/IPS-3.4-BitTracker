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
 
class cp_skin_membersview extends output
{
	
/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}


public function acp_member_form_tabs( $member, $tabID ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tab_MEMBERS_{$tabID}' class=''>{$this->lang->words['mem_tab_bitracker']}</li>
EOF;

return $IPBHTML;
}

public function acp_member_form_main( $member, $bitracker, $tabID ) {

$up_ban 	    = ipsRegistry::getClass('output')->formYesNo( 'up_ban', $member['up_ban'] );

$down_ban 	= ipsRegistry::getClass('output')->formYesNo( 'down_ban', $member['down_ban'] );

$full_ban 	= ipsRegistry::getClass('output')->formYesNo( 'full_ban', $member['full_ban'] );

$torrent_counter 	= ipsRegistry::getClass('class_localization')->formatNumber( $member['tor_counter'] );	

$uploaded_counter 	    = $member['upl_counter'];

$downloaded_counter 	= $member['dow_counter'];

$ratio_counter 	    = $member['rat_counter'];	


$IPBHTML = "";

$IPBHTML .= <<<HTML
<div id='tab_MEMBERS_{$tabID}_content'>
	
	<table class='ipsTable double_pad'>			
		<tr>
			<th colspan='2'>{$this->lang->words['mem_form_bitracker_th_header']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_torrent_counter']}</strong></td>
			<td class='field_field'>
				<span id='MF__referred_counter'>{$torrent_counter}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_updown_counter']}</strong></td>
			<td class='field_field'>
				<span id='MF__referred_counter'>{$uploaded_counter} / {$downloaded_counter}</span>
			</td>
		</tr>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_ratio_counter']}</strong></td>
			<td class='field_field'>
				<span id='MF__referred_counter'>{$ratio_counter}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_upload_enable']}</strong>
			</td>
			<td class='field_field'>
				<span id='MF__p3_rs_menable'>{$up_ban}</span>
				<br /><span class='desctext'>{$this->lang->words['mem_upload_enable_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_download_enable']}</strong>
			</td>
			<td class='field_field'>
				<span id='MF__p3_rs_menable'>{$down_ban}</span>
				<br /><span class='desctext'>{$this->lang->words['mem_download_enable_desc']}</span>
			</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['mem_tracker_banned']}</strong>				
			</td>
			<td class='field_field'>
				<span id='MF__p3_rs_banned'>{$full_ban}</span>
				<br /><span class='desctext'>{$this->lang->words['mem_tracker_banned_desc']}</span>
			</td>
		</tr>						
	</table>
HTML;

$IPBHTML .= <<<HTML
<br />
	<h3>{$this->lang->words['seeding_overview']}</h3>
		<div class='topic_controls'>
			{$page_links}
		</div>
		<table class='ipsTable double_pad'>
		<tr class='header'>
			<th scope='col' width="45%">{$this->lang->words['mem_filename']}</th>
			<th scope='col' width="15%">{$this->lang->words['mem_submitted']}</th>
			<th scope='col' width="10%">{$this->lang->words['mem_uploaded']}</th>
			<th scope='col' width="10%">{$this->lang->words['mem_ratio']}</th>
			<th scope='col' width="10%">{$this->lang->words['mem_seeds']}</th>
			<th scope='col' width="10%">{$this->lang->words['mem_leeches']}</th>
		</tr>						
HTML;

		if( is_array( $bitracker ) AND count( $bitracker ) )
		{
			foreach( $bitracker as $mbit )
			{
			
			$IPBHTML .= <<<HTML
			<tr>
				<td style='text-align:center;'>{$mbit['t_name']}</td>									
				<td style='text-align:center;'>{$mbit['t_submit_date']}</td>
				<td style='text-align:center;'>{$mbit['t_uploaded']}</td>
				<td style='text-align:center;'>{$mbit['t_ratio']}</td>
				<td style='text-align:center;'>{$mbit['t_seeds']}</td>									
				<td style='text-align:center;'>{$mbit['t_leeches']}</td>									
			</tr>
HTML;
			}	
		}
		else
		{
			$IPBHTML .= <<<HTML
			<tr>
				<td colspan='6' class='no_messages'>
					{$this->lang->words['no_seeds_yet']} 
	            </td>				
			</tr>			
HTML;
		}


			$IPBHTML .= <<<HTML
		</table>

		<div class='topic_controls'>
			{$page_links}
		</div>

</div>
HTML;

return $IPBHTML;
}

}