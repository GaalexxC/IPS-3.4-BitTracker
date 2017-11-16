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
 * @class		cp_skin_categories
 * @brief		IP.bitracker categories skin file
 */
class cp_skin_categories
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
 * Home screen
 *
 * @param	string		$categories		Categories data
 * @return	@e string	HTML
 */
public function mainScreen( $categories ) {

$IPBHTML = "";

$title = $this->request['c'] ? sprintf( $this->lang->words['d_subcats_for'], $this->registry->getClass('categories')->cat_lookup[ $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cparent'] ]['cname'] ) : $this->lang->words['d_categories'];

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['c_title']}</h2>
	<div class='ipsActionBar clearfix'>
		<ul>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new&amp;p=0'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['d_addnewcat']}</a>
			</li>
			<li class='ipsActionButton'>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=modform'><img src='{$this->settings['skin_acp_url']}/images/icons/key.png' alt='' /> {$this->lang->words['d_addmod']}</a>
			</li>
		</ul>
	</div>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>
<div class='acp-box'>
	<h3>{$title}</h3>
	<table class='ipsTable' id='track_categories'>
		<tr>
			<th width='2%'>&nbsp;</th>
			<th width='95%'>{$this->lang->words['d_catname']}</th>
			<th class="col_buttons">&nbsp;</th>
		</tr>
EOF;

if ( $categories )
{
	$IPBHTML .= $categories;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='3' class='no_messages center'>{$this->lang->words['d_nocats']}</th>
		</tr>
EOF;
}


$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

if ( $categories )
{
	$IPBHTML .= <<<EOF
<script type='text/javascript'>
	jQ("#track_categories").ipsSortable('table', { 
		url: "{$this->settings['base_url']}{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ),
		serializeOptions: { key: 'cats[]' }
	} );
</script>
EOF;
}

return $IPBHTML;
}

/**
 * Subcategories HTML
 *
 * @access	public
 * @param	array 	Categories
 * @return	string	HTML
 */
public function subCategories( $categories ) {

$sub		= array();
$IPBHTML	= "";

foreach( $categories as $id => $data )
{
	$sub[] = "<a href='{$this->settings['base_url']}{$this->form_code}&amp;c={$data['cid']}'>" . $this->registry->getClass('categories')->cat_lookup[ $data['cid'] ]['cname'] . "</a>";
}

$IPBHTML .= "<fieldset class='subforums'><legend>{$this->lang->words['d_subcats']}</legend>" . implode( ', ', $sub ) . "</legend></fieldset>";


return $IPBHTML;
}

/**
 * Display single category moderator entry
 *
 * @param	array		$data		Moderator data
 * @return	@e string	HTML
 */
public function renderModeratorEntry( $data=array() ) {

$IPBHTML = "";

if( count($data) )
{
	$c = count($data);
	
	$IPBHTML .= <<<HTML
<ul class='right multi_menu' id='modmenu{$data[0]['randId']}'>
	<li>
		<a href='#' class='ipsBadge badge_green'>{$c} {$this->lang->words['d_moderators']}</a>
		<ul class='acp-menu'>
HTML;
	
	foreach( $data as $i => $d )
	{
		$IPBHTML .= <<<HTML
			<li>
				<span class='clickable'>{$d['_fullname']} </span>
				<ul class='acp-menu'>
					<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=editmod&amp;modid={$d['modid']}'>{$this->lang->words['d_edit']}</a></li>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;c={$d['cid']}&amp;do=delmod&amp;modid={$d['modid']}");'>{$this->lang->words['d_remove']}</a></li>
				</ul>
			</li>
HTML;
	}
	
	$IPBHTML .= <<<HTML
		</ul>
	</li>
</ul>
<script type='text/javascript'>
	jQ("#modmenu{$data[0]['randId']}").ipsMultiMenu();
</script>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Category row
 *
 * @param	string		$content		Subcategories data
 * @param	array		$cat			Category data
 * @param	string		$modData		Moderators data
 * @return	string	HTML
 */
public function renderCategory( $content, $cat, $modData='' )
{
$bar_id		= $this->request['c'] ? $this->registry->getClass('categories')->cat_lookup[ $this->request['c'] ]['cparent'] : 0;
$no_root	= count( $this->registry->getClass('categories')->cat_cache[ $bar_id ] );

$cat['cdesc'] = $cat['cdesc'] ? "<br /><span class='desctext'>{$cat['cdesc']}</span>" : '';

$IPBHTML .= <<<EOF
<tr class='ipsControlRow isDraggable' id='cat_{$cat['cid']}'>
	<td class='col_drag' title='{$this->lang->words['t_id']}: {$cat['cid']}'>
		<span class='draghandle'>&nbsp;</span>
	</td>
	<td>
		{$modData}
		<strong class='title'>{$cat['cname']}</strong>{$cat['cdesc']}
		{$content}
	</td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
			<li class='i_add'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new&amp;p={$cat['cid']}' title='{$this->lang->words['d_newcat']}'>{$this->lang->words['d_newcat']}</a></li>
			<li class='i_edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;c={$cat['cid']}' title='{$this->lang->words['d_editsettings']}'>{$this->lang->words['d_editsettings']}</a></li>
			<li class='ipsControlStrip_more ipbmenu' id='menu_{$cat['cid']}'><a href='#'>&nbsp;</a></li>
		</ul>						
		<ul class='acp-menu' id='menu_{$cat['cid']}_menucontent' style='display: none'>
			<li class='icon password'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=modform&amp;c={$cat['cid']}'>{$this->lang->words['d_addmod']}</a></li>
			<li class='icon refresh'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=resynch&amp;c={$cat['cid']}'>{$this->lang->words['d_resynchcat']}</a></li>
			<li class='icon delete'><a href='#' onclick='ACPbitracker.confirmEmpty({$cat['cid']})'>{$this->lang->words['d_emptycat']}</a></li>
			<li class='icon delete'><a href='#' onclick='ACPbitracker.confirmDelete({$cat['cid']})'>{$this->lang->words['d_deletecat']}</a></li>
		</ul>
	</td>
</tr>
EOF;

return $IPBHTML;
}

/**
 * Form to add/edit a category
 *
 * @param	array		$category		Category data
 * @param	array		$form			Form elements
 * @param	integer		$max_upload		Max upload size
 * @param	array		$moreTabs		Additional tabs data from plugins
 * @param	string		$firstTab		First tab to highlight
 * @return	string	HTML
 */
public function categoryForm( $category, $form, $max_upload=0, $moreTabs=array(), $firstTab='' ) {

$IPBHTML = "";

$screenieWarning	= ( empty($this->settings['bit_screenshot_url']) && !in_array( 'php' , explode( ',', $this->settings['img_ext']) ) ) ? "<br /><div class='information-box'><strong>{$this->lang->words['warning_no_screenies']}</strong></div>" : "";

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$form['form_title']}</h2>
</div>

<form id='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['form_code']}&amp;c={$category['cid']}' method='post'>
<div class='acp-box'>
	<h3>{$form['form_title']}</h3>
	
	<div id='tabstrip_categoryForm' class='ipsTabBar with_left with_right'>
		<span class='tab_left'>&laquo;</span>
		<span class='tab_right'>&raquo;</span>
		<ul>
			<li id='tab_Basic'>{$this->lang->words['d_basics']}</li>
			<li id='tab_Options'>{$this->lang->words['d_catoptions']}</li>
			<li id='tab_Topic'>{$this->lang->words['d_topicgeneration']}</li>
			<li id='tab_Errors'>{$this->lang->words['d_customerrors']}</li>
EOF;

if ( is_array($moreTabs['tabs']) && count($moreTabs['tabs']) )
{
	foreach( $moreTabs['tabs'] as $_tab )
	{
		$IPBHTML .= "\n{$_tab}\n"; // new lines needed to prevent attached tabs..
	}
}

$IPBHTML .= <<<EOF
			<li id='tab_Permissions'>{$this->lang->words['d_permissions']}</li>
		</ul>
	</div>
	
	<div id='tabstrip_categoryForm_content' class='ipsTabBar_content'>
		
		<!-- BASIC -->
		<div id='tab_Basic_content'>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_catname']}</strong>
					</td>
					<td class='field_field'>
						{$form['cname']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_catdesc']}</strong>
					</td>
					<td class='field_field'>
						{$form['cdesc']}<br />
						<span class='desctext'>{$this->lang->words['d_catdesc_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_parentcat']}</strong>
					</td>
					<td class='field_field'>
						{$form['cparent']}<br />
						<span class='desctext'>{$this->lang->words['d_parentcat_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_catopen']}</strong>
					</td>
					<td class='field_field'>
						{$form['copen']}<br />
						<span class='desctext'>{$this->lang->words['d_catopen_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_showfile']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_disfiles']}<br />
						<span class='desctext'>{$this->lang->words['d_showfile_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_disclaim']}</strong>
					</td>
					<td class='field_field'>
						{$form['cdisclaimer']}<br />
						<span class='desctext'>{$this->lang->words['d_disclaim_info']}</span>
					</td>
				</tr>
	 		</table>
		</div>
		
		<!-- OPTIONS -->
		<div id='tab_Options_content'>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_mimemask']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_mimemask']}<br />
						<span class='desctext'>{$this->lang->words['d_mimemask_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_customfields']}</strong>
					</td>
					<td class='field_field'>
						{$form['ccfields']}
					</td>
				</tr>
					<tr>
						<th colspan='2'>
							{$this->lang->words['d_fperms_title']}
						</th>
					</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_allowbbcode']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_bbcode']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_allowhtml']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_html']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_allownfo']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_allownfo']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_requirenfo']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_reqnfo']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_allowscreens']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_allowss']}{$screenieWarning}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_requiress']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_reqss']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_displaythumb']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_catss']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_usecomment']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_comments']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_sortorder']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_sortorder']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_sortbyorder']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_sortby']}
					</td>
				</tr>
					<tr>
						<th colspan='2'>
							{$this->lang->words['d_filesize_title']}
						</th>
					</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_maxsize']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_maxfile']}<br />
						<span class='desctext'>{$this->lang->words['d_maxsize_info']} {$max_upload}</span>
					</td>
				</tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_maxnfosize']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_maxnfo']}<br />
						<span class='desctext'>{$this->lang->words['d_maxsize_info']} {$max_upload}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_maxsssize']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_maxss']}&nbsp;&nbsp; {$this->lang->words['d_thumbwidth']} {$form['opt_thumb_x']}px<br />
						<span class='desctext'>{$this->lang->words['d_maxsize_info']} {$max_upload}</span>
					</td>
				</tr>
				
					<tr>
						<th colspan='2'>
							{$this->lang->words['d_tagging_title']}
						</th>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['d_disable_tagging']}</strong>
						</td>
						<td class='field_field'>
							{$form['ctags_disabled']}
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['d_disable_prefixes']}</strong>
						</td>
						<td class='field_field'>
							{$form['ctags_noprefixes']}<br />
							<span class="desctext">{$this->lang->words['d_disable_prefixes_desc']}</span>
						</td>
					</tr>
					<tr>
						<td class='field_title'>
							<strong class='title'>{$this->lang->words['d_predefined_tags']}</strong>
						</td>
						<td class='field_field'>
							{$form['ctags_predefined']}<br />
							<span class="desctext">{$this->lang->words['d_tag_predefined_desc']}</span>
						</td>
					</tr>
	 		</table>
		</div>
		
		<!-- TOPIC OPTIONS -->
		<div id='tab_Topic_content'>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_createtopic']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topice']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_postinwhich']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topicf']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_prefix']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topicp']}<br />
						<span class='desctext'>{$this->lang->words['d_prefix_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_suffix']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topics']}<br />
						<span class='desctext'>{$this->lang->words['d_prefix_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_autodelete']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topicd']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_includenfo']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topicnfo']}<br />
						<span class='desctext'>{$this->lang->words['d_includenfo_info']}</span>
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_includess']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_topicss']}<br />
						<span class='desctext'>{$this->lang->words['d_includess_info']}</span>
					</td>
				</tr>
	 		</table>
		</div>
		
		<!-- ERRORS -->
		<div id='tab_Errors_content'>
			<table class='ipsTable double_pad'>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_cannotviewmsg']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_noperm_view']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_cannotaddmsg']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_noperm_add']}
					</td>
				</tr>
				<tr>
					<td class='field_title'>
						<strong class='title'>{$this->lang->words['d_cannotdlmsg']}</strong>
					</td>
					<td class='field_field'>
						{$form['opt_noperm_dl']}
					</td>
				</tr>
	 		</table>
		</div>
EOF;

if ( is_array($moreTabs['area']) && count($moreTabs['area']) )
{
	foreach( $moreTabs['area'] as $_area )
	{
		$IPBHTML .= $_area;
	}
}

$IPBHTML .= <<<EOF
		<!-- PERMISSIONS -->
		<div id='tab_Permissions_content'>
			{$form['permissions']}
		</div>
		
		<script type='text/javascript'>
			jQ("#tabstrip_categoryForm").ipsTabBar({ tabWrap: "#tabstrip_categoryForm_content", defaultTab: "tab_{$firstTab}" });
		</script>
	</div>
		
	<div class='acp-actionbar'>
		<input type='submit' value='{$form['form_button']}' class='button primary' />
	</div>
</div>
</form>
EOF;


return $IPBHTML;
}

/**
 * Form to add/edit moderator
 *
 * @param	array		$form		Form elements
 * @return	@e string	HTML
 */
public function moderatorForm( $form ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['c_title_mod']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$form['code']}' method='post'>
	<input type='hidden' name='modid' value='{$this->request['modid']}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['d_modsett']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_modtype']}</strong>
				</td>
				<td class='field_field'>
					{$form['modtype']}<br />
					<span class='desctext'>{$this->lang->words['d_modtype_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_groupormem']}</strong>
				</td>
				<td class='field_field'>
					{$form['modgid']} {$this->lang->words['d_or']} {$form['modmid']}<br />
					<span class='desctext'>{$this->lang->words['d_modtype_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_categories']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcats']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_canedit']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcanedit']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_candelete']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcandel']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_canapprove']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcanapp']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_canmanagebrok']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcanbrok']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_canmanagecom']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcancomm']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_canpinunpin']}</strong>
				</td>
				<td class='field_field'>
					{$form['modcanpin']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_canchangeauthor']}</strong>
				</td>
				<td class='field_field'>
					{$form['modchangeauth']}
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_featureunfeature']}</strong>
				</td>
				<td class='field_field'>
					{$form['modusefeature']}
				</td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_submit']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

return $IPBHTML;
}

}