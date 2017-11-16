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
 * @class		cp_skin_cfields
 * @brief		Custom fields skin file
 */
class cp_skin_cfields
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
 * Delete custom fields form
 *
 * @param	array		$field		Custom field data
 * @return	@e string	HTML
 */
public function deleteForm( $field ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['cf_title_del']}</h2>
</div>

<div class='warning'>{$this->lang->words['cf_delete_warning']}</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['d_removeconf']}</h3>
	
	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=dodelete' method='post'>
		<input type='hidden' name='id' value='{$this->request['id']}' />
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'><strong class='title'>{$this->lang->words['d_fieldtorem']}</strong></td>
				<td class='field_field'>{$field['cf_title']}</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_delfield']}' class='button primary' />
		</div>
	</form>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Field to add/edit custom field
 *
 * @param	array		$form		Form elements
 * @param	array		$field		Custom field information
 * @return	@e string	HTML
 */
public function cfieldsForm( $form, $field ) {

$IPBHTML = "";
//--starthtml--//

if( $form['code'] == 'doedit' )
{
	$title	= $this->lang->words['cf_editing'] . $field['cf_title'];
}
else
{
	$title	= $this->lang->words['cf_adding'];
}

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['code']}' method='post'>
	<input type='hidden' name='id' value='{$this->request['id']}' />
	
	<div class="acp-box">
		<h3>{$this->lang->words['d_fieldset']}</h3>
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fieldtitle']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_title']}<br />
					<span class='desctext'>{$this->lang->words['d_maxchar']} 200</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_description']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_desc']}<br />
					<span class='desctext'>{$this->lang->words['d_maxchar']} 250</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_useincats']}</strong>
				</td>
				<td class='field_field'>
					{$form['categories']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fieldtype']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_type']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_maxinput']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_max_input']}<br />
					<span class='desctext'>{$this->lang->words['d_maxinput_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_expectedin']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_input_format']}<br />
					<span class='desctext'>{$this->lang->words['d_expectedin_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_optcontent']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_content']}<br />
					<span class='desctext'>{$this->lang->words['d_optcontent_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_mustcomplete']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_not_null']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fieldformat']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_format']}<br />
					<span class='desctext'>{$this->lang->words['d_fieldformat_desc']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fieldauto']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_topic']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fieldsearch']}</strong>
				</td>
				<td class='field_field'>
					{$form['cf_search']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$form['button']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Custom fields wrapper
 *
 * @param	array		$fields		Custom fields data
 * @return	@e string	HTML
 */
public function cfieldsWrapper( $fields ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['cf_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['d_addnewfield']}</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_fieldmanage']}</h3>
EOF;

if ( is_array($fields) && count($fields) )
{
	$IPBHTML .= <<<EOF
	<table class='ipsTable'>
		<tr>
			<th class='col_drag'>&nbsp;</th>
			<th style='width: 55%'>{$this->lang->words['cf_title']}</th>
			<th style='width: 35%;'>{$this->lang->words['d_usedbycats']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
	</table>
	<ul id='sortable_handle'>
EOF;
	
	foreach( $fields as $row )
	{
		$IPBHTML .= <<<EOF
		<li id='cfield_{$row['cf_id']}'>
			<table class='ipsTable'>
				<tr class='ipsControlRow isDraggable'>
					<td class='col_drag'>
						<span class='draghandle'>&nbsp;</span>
					</td>
					<td style='width: 55%'>
						<strong class='title'>{$row['cf_title']}</strong><br />
						<span class='desctext'>{$row['cf_desc']}</span>
					</td>
					<td style='width: 35%;'>
						{$row['categories']}
					</td>
					<td class='col_buttons'>
						<ul class='ipsControlStrip'>
							<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$row['cf_id']}' title='{$this->lang->words['d_editfield']}'>{$this->lang->words['d_editfield']}</a></li>
							<li class='i_delete'><a href='{$this->settings['base_url']}{$this->form_code_js}&amp;do=delete&amp;id={$row['cf_id']}' title='{$this->lang->words['d_deletefield']}'>{$this->lang->words['d_deletefield']}</a></li>
						</ul>
					</td>
				</tr>
			</table>
		</li>
EOF;
	}

	$IPBHTML .= <<<EOF
	</ul>
</div>

<script type="text/javascript">
window.onload = function() {
	Sortable.create( 'sortable_handle', { revert: true, format: 'cfield_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'cfields' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};
</script>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
<table class='ipsTable'>
	<tr>
		<td class='center'>{$this->lang->words['d_nofields']}</td>
	</tr>
</table>
</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

}