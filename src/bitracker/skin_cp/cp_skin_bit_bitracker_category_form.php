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
 * @brief		IP.bitracker example category form skin file
 */
class cp_skin_bit_track_category_form
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
 * @param	array		$category		Category data
 * @param	mixed		$tabId			Tab ID
 * @return	@e string	HTML
 */
public function acp_bitracker_category_form_main( $category, $tabId ) {

$form					  = array();
$form['example_yesno']    = $this->registry->output->formYesNo( 'example_yesno', $category['example_yesno'] );
$form['example_input']    = $this->registry->output->formInput( 'example_input', $category['example_input'] );
$form['example_textarea'] = $this->registry->output->formTextarea( 'example_textarea', $category['example_textarea'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_CustomTab{$tabId}_content'>
	<table class='ipsTable double_pad'>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>Yes/No field example</strong>
			</td>
			<td class='field_field'>
		 		{$form['example_yesno']}
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>Input field example</strong>
			</td>
			<td class='field_field'>
		 		{$form['example_input']}<br />
				<span class='desctext'>Input field example description</span>
			</td>
	 	</tr>
	 	<tr>
	 		<td class='field_title'>
				<strong class='title'>Textarea field example</strong>
			</td>
			<td class='field_field'>
		 		{$form['example_textarea']}<br />
				<span class='desctext'>Textarea field example description</span>
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
 * @param	array		$category		Category data
 * @param	mixed		$tabId			Tab ID
 * @return	@e string	HTML
 */
public function acp_bitracker_category_form_tabs( $category, $tabId ) {

$IPBHTML = "<li id='tab_CustomTab{$tabId}'>" . IPSLib::getAppTitle('bitracker') . "</li>";

return $IPBHTML;
}

}
