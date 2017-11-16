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
 * @class		cp_skin_mimetypes
 * @brief		IP.bitracker mymetypes skin file
 */
class cp_skin_mimetypes
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
 * Masks wrapper
 *
 * @param	array		$rows		Masks data
 * @param	array		$dlist		Dropdown list options
 * @return	@e string	HTML
 */
public function masksWrapper( $rows, $dlist ) {

$IPBHTML = "";
//--starthtml--//

$maskName = $this->registry->output->formInput( 'new_mask_name' );
$masksDD  = $this->registry->output->formDropdown( 'new_mask_copy', $dlist );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['m_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_mimemask']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='30%'>{$this->lang->words['d_maskname']}</th>
			<th width='65%'>{$this->lang->words['d_usedbycats']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
EOF;

foreach( $rows as $row )
{
	$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
			<td>{$row['mime_masktitle']}</td>
			<td>{$row['categories']}</td>
			<td class='col_buttons'>
				<ul class='ipsControlStrip'>
					<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=types&amp;id={$row['mime_maskid']}' title='{$this->lang->words['d_editmask']}'>{$this->lang->words['d_editmask']}</a></li>
EOF;

	if ( $row['_delete'] )
	{
		$IPBHTML .= <<<EOF
					<li class='i_delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=mask_delete&id={$row['mime_maskid']}");' title='{$this->lang->words['m_delete']}'>{$this->lang->words['m_delete']}</a></li>
EOF;
	}

	$IPBHTML .= <<<EOF
				</ul>
			</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=mask_add' method='post'>
<div class="acp-box">
	<h3>{$this->lang->words['d_createmask']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['d_strongmaskname']}</strong>
			</td>
			<td class='field_field'>
				{$maskName}
			</td>
		</tr>
		<tr>
			<td class='field_title'>
				<strong class='title'>{$this->lang->words['d_inheritmask']}</strong>
			</td>
			<td class='field_field'>
				{$masksDD}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['d_create']}' id='button' class='button primary' />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Mime types form
 *
 * @param	array		$form		Form elements
 * @param	array		$mime		Mime type data
 * @return	@e string	HTML
 */
public function mimeForm( $form, $mime ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$form['button']}</h2>
</div>
EOF;

if ( $form['baseon'] )
{
	$IPBHTML .= <<<EOF
<form name='baseonForm' class='information-box' style='margin-bottom:10px;' action='{$this->settings['base_url']}{$this->form_code}&amp;do=mime_add&amp;id={$this->request['id']}' method='post'>
	<span class='right'>
		{$this->lang->words['m_baseon']} {$form['baseon']} <input type='submit' name='submit' value='{$this->lang->words['d_goright']}' class='button primary' />
	</span>
	<br class='clear' /> 
</form>
EOF;
}

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$form['button']}</h3>

	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['code']}' method='post'>
		<input type='hidden' name='id' value='{$this->request['id']}' />
		<input type='hidden' name='mid' value='{$this->request['mid']}' />
		
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fileex']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_extension']}<br />
					<span class='desctext'>{$this->lang->words['d_fileext_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_mimetype']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_type']}<br />
					<span class='desctext'>{$this->lang->words['d_mimetype_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_allowmime']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_file']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_allownfo']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_nfo']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_allowss']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_screenshot']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fileinline']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_inline']}<br />
					<span class='desctext'>{$this->lang->words['d_fileinline_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_mimeimg']}</strong>
				</td>
				<td class='field_field'>
					{$form['mime_img']}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$form['button']}' class='button primary' />
		</div>
	</form>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Mime types wrapper listing
 *
 * @param	array		$rows		Mime-types data
 * @param	array		$mask		Mime-type mask data
 * @return	@e string	HTML
 */
public function mimesWrapper( $rows, $mask ) {

$title    = sprintf( $this->lang->words['m_mimename'], $mask['mime_masktitle'] );
$maskName = $this->registry->output->formInput( "new_mask_name", $mask['mime_masktitle']  );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=mime_add&amp;id={$this->request['id']}'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['d_addnewtype']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=mime_export'><img src='{$this->settings['skin_acp_url']}/images/icons/export.png' alt='' /> {$this->lang->words['d_export']}</a>
			</li>
		</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	
	<div id='tabstrip_mimemask' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_List'>{$this->lang->words['d_types']}</li>
			<li id='tab_Rename'>{$this->lang->words['d_renamemask']}</li>
			<li id='tab_Import'>{$this->lang->words['d_importtypes']}</li>
		</ul>
	</div>
	
	<div id='tabstrip_mimemask_content' class='ipsTabBar_content'>
		
		<!-- LIST -->
		<div id='tab_List_content'>
			<table class='ipsTable'>
				<tr>
					<th width='5%'>&nbsp;</th>
					<th width='25%'>{$this->lang->words['d_extension']}</th>
					<th width='35%'>{$this->lang->words['d_mimetype']}</th>
					<th width='10%' class='center'>+{$this->lang->words['d_file']}</th>
					<th width='10%' class='center'>+{$this->lang->words['d_nfo']}</th>
					<th width='10%' class='center'>+{$this->lang->words['d_screenshot']}</th>
					<th width='10%' class='center'>+{$this->lang->words['d_inline']}</th>
					<th class='col_buttons'>&nbsp;</th>
				</tr>
EOF;
			
			foreach( $rows as $row )
			{
				$checked_img	= "<img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' alt='' />";
				$file_checked	= $row['mime_file']  ? $checked_img : '&nbsp;';
				$nfo_checked	= $row['mime_nfo']  ? $checked_img : '&nbsp;';
				$ss_checked		= $row['mime_screenshot'] ? $checked_img : '&nbsp;';
				$inline_checked	= $row['mime_inline'] ? $checked_img : '&nbsp;';
				
				$IPBHTML .= <<<EOF
				<tr class='ipsControlRow'>
					<td class='center'><img src='{$this->settings['public_dir']}style_extra/{$row['mime_img']}' /></td>
					<td><strong class='title'>.{$row['mime_extension']}</strong></td>
					<td>{$row['mime_mimetype']}</td>
					<td class='center'>{$file_checked}</td>
					<td class='center'>{$nfo_checked}</td>
					<td class='center'>{$ss_checked}</td>
					<td class='center'>{$inline_checked}</td>
					<td class='col_buttons'>
						<ul class='ipsControlStrip'>
							<li class='i_edit'>
								<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=mime_edit&amp;mid={$row['mime_id']}&amp;id={$this->request['id']}' title='{$this->lang->words['d_edittype']}'>{$this->lang->words['d_edittype']}</a>
							</li>
							<li class='i_delete'>
								<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=mime_delete&amp;mid={$row['mime_id']}&amp;id={$this->request['id']}");' title='{$this->lang->words['d_deletetype']}'>{$this->lang->words['d_deletetype']}</a>
							</li>
						</ul>
					</td>
				</tr>
EOF;
			}
			
			$IPBHTML .= <<<EOF
			</table>
		</div>
		
		<!-- RENAME -->
		<div id='tab_Rename_content'>
			<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_mask_edit' method='post'>
				<input type='hidden' name='mask_id' value='{$this->request['id']}' />
				
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['d_newmask']}</strong>
						</td>
						<td class='field_field'>
							{$maskName}
						</td>
					</tr>
				</table>
				<div class='acp-actionbar'>
					<input type='submit' value='{$this->lang->words['d_rename']}' class='button primary' />
				</div>
			</form>
		</div>
		
		<!-- IMPORT -->
		<div id='tab_Import_content'>
			<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=mime_import' method='post' enctype='multipart/form-data'>
				<input type='hidden' name='id' value='{$this->request['id']}' />
				
				<table class='ipsTable double_pad'>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['d_uploadtypes']}</strong>
						</td>
						<td class='field_field'>
							<input type='file' name='FILE_UPLOAD' />
						</td>
					</tr>
				</table>
				<div class='acp-actionbar'>
					<input type='submit' value='{$this->lang->words['d_import']}' class='button primary' />
				</div>
			</form>
		</div>
	</div>
	
	<script type='text/javascript'>
		jQ("#tabstrip_mimemask").ipsTabBar({ tabWrap: "#tabstrip_mimemask_content" });
	</script>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

}