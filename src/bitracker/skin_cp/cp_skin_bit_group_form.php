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
 * @class		cp_skin_bit_group_form
 * @brief		IP.bitracker group form skin file
 */
class cp_skin_bit_group_form
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
 * Main form to edit group settings
 *
 * @param	array		$group		Group data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_group_form_main( $group, $tabId ) {

$restrictions	= IPSLib::isSerialized($group['bit_restrictions']) ? unserialize($group['bit_restrictions']) : array();
$group			= array_merge( $group, $restrictions );

$form					= array();
$form['enabled']		= $this->registry->output->formYesNo( "enabled", $group['enabled'] );
$form['limit_sim']		= $this->registry->output->formInput( "limit_sim", $group['limit_sim'] );
$form['min_posts']		= $this->registry->output->formInput( "min_posts", $group['min_posts'] );
$form['posts_per_dl']	= $this->registry->output->formInput( "posts_per_dl", $group['posts_per_dl'] );
$form['daily_bw']		= $this->registry->output->formInput( "daily_bw", $group['daily_bw'] );
$form['weekly_bw']		= $this->registry->output->formInput( "weekly_bw", $group['weekly_bw'] );
$form['monthly_bw']		= $this->registry->output->formInput( "monthly_bw", $group['monthly_bw'] );
$form['daily_dl']		= $this->registry->output->formInput( "daily_dl", $group['daily_dl'] );
$form['weekly_dl']		= $this->registry->output->formInput( "weekly_dl", $group['weekly_dl'] );
$form['monthly_dl']		= $this->registry->output->formInput( "monthly_dl", $group['monthly_dl'] );
$form['add_paid']		= $this->registry->output->formYesNo( "bit_add_paid", $group['bit_add_paid'] );
$form['bypass_paid']	= $this->registry->output->formYesNo( "bit_bypass_paid", $group['bit_bypass_paid'] );
$form['report_files']	= $this->registry->output->formYesNo( "bit_report_files", $group['bit_report_files'] );
$form['view_dls']		= $this->registry->output->formYesNo( "bit_view_bitracker", $group['bit_view_bitracker'] );
$form['bypass_rev']		= $this->registry->output->formYesNo( "bit_bypass_revision", $group['bit_bypass_revision'] );
$form['throttling']		= $this->registry->output->formInput( "bit_throttling", $group['bit_throttling'] );
$form['wait_period']	= $this->registry->output->formInput( "bit_wait_period", $group['bit_wait_period'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<table class='ipsTable double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['b_bitsettings']}</th>
		</tr>
	 	<tr class='guest_legend'>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_b_reports']}</strong>
			</td>
	 		<td class='field_field'>
				{$form['report_files']}<br />
	        	<span class='desctext'>{$this->lang->words['b_reports_desc']}</span>
	        </td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_b_viewdls']}</strong>
			</td>
	 		<td class='field_field'>
				{$form['view_dls']}<br />
				<span class='desctext'>{$this->lang->words['g_b_viewdls_desc']}</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_bypass_revisions']}</strong>
			</td>
			<td class='field_field'>
	 			{$form['bypass_rev']}<br />
				<span class='desctext'>{$this->lang->words['g_b_bypass_rev_desc']}</span>
			</td>
	 	</tr>
	 	<tr>
 			<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_b_throttling']}</strong>
			</td>
			<td class='field_field'>
	 			{$form['throttling']} <br />
				<span class='desctext'>{$this->lang->words['b_throttling_info']}</span>
			</td>
	 	</tr>
	 	<tr>
 			<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_b_waitperiod']}</strong>
			</td>
			<td class='field_field'>
	 			{$form['wait_period']} <br />
				<span class='desctext'>{$this->lang->words['b_waitperiod_info']}</span>
			</td>
	 	</tr>
EOF;

if ( IPSLib::appIsInstalled( 'nexus' ) and ipsRegistry::$settings['bit_nexus_on'] )
{
$IPBHTML .= <<<EOF
	 	<tr>
 			<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_paid_add']}</strong>
			</td>
			<td class='field_field'>
	 			{$form['add_paid']}
	    	</td
	 	</tr>
	 	<tr>
 			<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_paid_bypass']}</strong>
			</td>
			<td class='field_field'>
	 			{$form['bypass_paid']}
	 		</td>
	 	</tr>
EOF;
}

$IPBHTML .= <<<EOF
		<tr>
			<th colspan='2'>{$this->lang->words['b_bitrestrictions']}</th>
		</tr>
	 	<tr>
 			<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_enabler']}</strong>
			</td>
			<td class='field_field'>
	 			{$form['enabled']} <br />
				<span class='desctext'>{$this->lang->words['d_enabler_info']}</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxstim']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['limit_sim']}<br />
				<span class='desctext'>{$this->lang->words['d_maxstim_info']}</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_poststodl']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['min_posts']}<br />
				<span class='desctext'>{$this->lang->words['d_poststodl_info']}</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_postsperdl']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['posts_per_dl']}<br />
				<span class='desctext'>{$this->lang->words['d_postsperdl_info']}</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxbwd']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['daily_bw']}<br />
				<span class='desctext'>{$this->lang->words['d_maxbwd_info']}</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxbww']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['weekly_bw']}<br />
				<span class='desctext'>{$this->lang->words['d_maxbww_info']}</span>
			</td>
	 	</tr>
	 	<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxbwm']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['monthly_bw']}<br />
		    	<span class='desctext'>{$this->lang->words['d_maxbwm_info']}</span>
		    </td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxdld']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['daily_dl']}<br />
				<span class='desctext'>{$this->lang->words['d_maxdld_info']}</span>
		    </td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxdlw']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['weekly_dl']}<br />
				<span class='desctext'>{$this->lang->words['d_maxdlw_info']}</span>
		    </td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>{$this->lang->words['gf_d_maxdlm']}</strong>
			</td>
			<td class='field_field'>
		 		{$form['monthly_dl']}<br />
				<span class='desctext'>{$this->lang->words['d_maxdlm_info']}</span>
		    </td>
	 	</tr>
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Tabs for the group form
 *
 * @param	array		$group		Group data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "<li id='tab_GROUPS_{$tabId}'>" . IPSLib::getAppTitle('bitracker') . "</li>";

return $IPBHTML;
}

}
