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
 * @class		cp_skin_bulkimport
 * @brief		Bulk import skin file
 */
class cp_skin_bulkimport
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
 * List zip files
 *
 * @param	string		$content		HTML content
 * @return	@e string	HTML
 */
public function zipImportWrapper( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<div class="acp-box">
	<h3>{$this->lang->words['d_zipindir']} {$this->settings['upload_dir']}</h3>
	<table class='ipsTable'>
		<tr>
			<th width='5%'>&nbsp;</th>
			<th width='40%'>{$this->lang->words['d_archivename']}</th>
			<th width='10%'>{$this->lang->words['d_fcount']}</th>
			<th width='10%'>{$this->lang->words['d_archivesize']}</th>
			<th width='35%'>{$this->lang->words['d_options']}</th>
		</tr>
		{$content}
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkZip&amp;op=upload' method='post' enctype='multipart/form-data'>
	<div class='acp-box'>
		<h3>{$this->lang->words['d_uploadafile']}</h3>
EOF;

if ( SAFE_MODE_ON OR $this->settings['safe_mode_skins'] )
{
	$IPBHTML .= <<<EOF
		<div class='warning'>{$this->lang->words['d_safemode']} {$this->settings['upload_dir']}</div>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_selectfile']}</strong>
				</td>
				<td class='field_field'>
					<input type='file' name='zipup' size='30' />
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_upload']}' class='button primary' />
		</div>
EOF;
}

$IPBHTML .= <<<EOF
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Zip file row
 *
 * @param	object		$file		File
 * @param	array		$zip		Zip info
 * @param	array		$info		Info about the zip
 * @return	@e string	HTML
 */
public function zipImportRow( $file, $zip, $info ) {

$comment	= $info['comment'] ? '<br /><i>' . $this->lang->words['d_comment'] . ': ' . $info['comment'] . '</i>' : '';
$size		= IPSLib::sizeFormat( $file->getSize() );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td><img src='{$this->settings['public_dir']}style_extra/mime_types/zip.gif' /></td>
	<td>{$file->getFilename()}{$comment}</td>
	<td>{$info['nb']}</td>
	<td>{$size}</td>
	<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkZip&amp;op=zipListAll&amp;zip={$file->getFilename()}' class='button'>{$this->lang->words['d_importfiles']}</a>&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkZip&amp;op=del&amp;zip={$file->getFilename()}' class='realbutton redbutton'>{$this->lang->words['d_deletezip']}</a></td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List files inside a zip
 *
 * @param	string		$content		HTML content
 * @param	boolean		$checkAll		All checkboxes checked
 * @return	@e string	HTML
 */
public function zipFileListing( $content, $checkAll ) {

$categories 		= $this->registry->getClass('categories')->catJumpList( 1, 'none', array( $this->request['cat'] ) );
$categoryOptions	= array();
$checkboxesAllFull  = $checkAll ? "checked='checked'" : '';

if( count($categories) )
{
	foreach( $categories as $cat )
	{
		$categoryOptions[] = array( $cat[0], $cat[1] );
	}
}

$cats = $this->registry->output->formDropdown( 'cat', $categoryOptions, intval($this->request['cat']) );
$name = $this->registry->output->formInput( 'mem_name', '', '', 40, 'text', "autocomplete='off'" );


$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>

<div class='information-box'>{$this->lang->words['d_check2import']}</div>
<br />

<form name='importZip' id='importZip' action='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkZip&amp;op=zipIndexAdd' method='post'>
	<input type='hidden' name='zip' value='{$this->request['zip']}' />
	
	<div class="acp-box">
		<h3>{$this->lang->words['d_listingfiles']} {$this->request['zip']}</h3>
		
		<table class='ipsTable'>
			<tr>
				<th width='3%' class='center'><input id='checkAll' type='checkbox' {$checkboxesAllFull} title='{$this->lang->words['d_checkuncheck']}' /></th>
				<th width='3%'>&nbsp;</th>
				<th width='50%'>{$this->lang->words['d_fname']}</th>
				<th width='30%'>{$this->lang->words['d_fsize']}</th>
				<th width='10%'>{$this->lang->words['d_imported']}</th>
			</tr>
			{$content}
		</table>
	</div>
	<br />
	<div class="acp-box">
		<h3>{$this->lang->words['d_importoptions']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_selectcat']}</strong>
				</td>
				<td class='field_field'>
					{$cats}<br />
					<span class='desctext'>{$this->lang->words['d_selectcat_info']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fowner']}</strong>
				</td>
				<td class='field_field'>
					{$name}<br />
					<span class='desctext'>{$this->lang->words['d_fowner_info']}</span>
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_import']}' class='button primary' />
		</div>
	</div>
</form>	
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * File inside zip row
 *
 * @param	array		$file		File information
 * @return	@e string	HTML
 */
public function zipFileRow( $file ) {

$checkbox	= $this->registry->output->formCheckbox( 'extract_' . $file['index'], $file['is_checked'], $file['index'], '', '', 'checkAll' );
$size		= IPSLib::sizeFormat( $file['size'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
			<tr>
				<td class='center'>{$checkbox}</td>
				<td class='center'><img src='{$this->settings['public_dir']}style_extra/{$this->caches['bit_mimetypes'][ $file['ext'] ]['mime_img']}' /></td>
				<td>{$file['filename']}</td>
				<td>{$size}</td>
				<td>{$file['exists']}</td>
			</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Bulk directory import wrapper
 *
 * @param	string		$content		HTML content
 * @param	string		$up_a_dir		Up a directory path
 * @return	@e string	HTML
 */
public function bulkImportWrapper( $content, $up_a_dir='../' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class="acp-box">
	<h3>{$this->lang->words['d_dirfrom']}</h3>
	
	<div class='information-box'>{$this->lang->words['d_curdir']}: {$this->request['lookin']}</div>
	
	<table class='ipsTable'>
		<tr>
			<th width='2%'>&nbsp;</th>
			<th width='65%'>{$this->lang->words['d_directory']}</th>
			<th width='5%' class='center'>{$this->lang->words['d_files']}</th>
			<th width='10%'>{$this->lang->words['d_size']}</th>
			<th width='7%' class='center'>{$this->lang->words['d_importable']}</th>
			<th width='5%' class='center'>{$this->lang->words['d_view']}</th>
			<th width='5%' class='center'>{$this->lang->words['d_import']}</th>
		</tr>
		<tr>
			<td><img src='{$this->settings['skin_acp_url']}/images/icons/rep_received.png' /></td>
			<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkDir&amp;lookin={$up_a_dir}' title='{$this->lang->words['d_clickparent']}'>{$this->lang->words['d_goupdir']}</a></td>
			<td colspan='5'>&nbsp;</td>
		</tr>
		{$content}
	</table>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * File row for bulk import
 *
 * @param	object		$file		File
 * @param	array		$data		Data for the file
 * @return	@e string	HTML
 */
public function bulkImportRow( $file, $data ) {

$size		= IPSLib::sizeFormat( $data['size'] );
$canImport	= "<img src='{$this->settings['skin_acp_url']}/images/icons/accept.png' title='{$this->lang->words['d_filescanbe']}' />";
$view		= '&nbsp;';
$import		= '&nbsp;';

if ( $data['importable'] == 1 AND $data['count'] > 0 )
{
	$import 	= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkDir&amp;op=viewDir&amp;directory={$file->getPathname()}' title='{$this->lang->words['d_click2import']}'><span class='ipsBadge badge_green'>{$this->lang->words['d_import']}</span></a>";
	$view 		= "<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkDir&amp;op=viewDirFiles&amp;viewdir={$file->getPathname()}' title='{$this->lang->words['d_click2view']}'><img src='{$this->settings['skin_acp_url']}/images/icons/view.png' /></a>";	
}
elseif ( $data['importable'] != 1 )
{
	$canImport	= "<img src='{$this->settings['skin_acp_url']}/images/icons/cross.png' title='{$this->lang->words['d_filescannotbe']}' />";	
}
					
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
			<tr>
				<td class='center'><img src='{$this->settings['skin_acp_url']}/images/icons/folder.png' /></td>
				<td><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkDir&amp;lookin={$file->getPathname()}/' title='{$this->lang->words['d_click2view']}'>{$file->getFilename()}</a></td>
				<td class='center'>{$data['count']}</td>
				<td>{$size}</td>
				<td class='center'>{$canImport}</td>
				<td class='center'>{$view}</td>
				<td class='center'>{$import}</td>
			</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View files for bulk import wrapper
 *
 * @param	string		$content		Table list of importable files
 * @return	@e string	HTML
 */
public function bulkImportViewWrapper( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<div class="acp-box">
	<h3>{$this->lang->words['d_importablefiles']}</h3>
	<table class='ipsTable'>
EOF;

if( $content )
{
	$IPBHTML .= $content;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td class='no_messages'>{$this->lang->words['d_nonefound']}</td>
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
 * File in bulk import preview
 *
 * @param	object		$file 	File
 * @param	string		$image	Mime type icon path
 * @return	@e string	HTML
 */
public function bulkImportViewRow( $file, $image ) {
				
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td width='2%'><img src='{$this->settings['public_dir']}style_extra/{$image}' /></td>
	<td>{$file->getPathname()}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Directory file listing
 *
 * @return	@e string	HTML
 */
public function dirFileListing() {

$categories 		= $this->registry->getClass('categories')->catJumpList( 1, 'none', array( $this->request['cat'] ) );
$categoryOptions	= array();

if( count($categories) )
{
	foreach( $categories as $cat )
	{
		$categoryOptions[] = array( $cat[0], $cat[1] );
	}
}

$num  = $this->registry->output->formSimpleInput( 'num', 20 );
$move = $this->registry->output->formYesNo( 'remove', 1 );
$cats = $this->registry->output->formDropdown( 'cat', $categoryOptions, intval($this->request['cat']) );
$name = $this->registry->output->formInput( 'mem_name', '', '', 40, 'text', "autocomplete='off'" );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.bitracker.js'></script>

<form name='importDir' id='importDir' action='{$this->settings['base_url']}{$this->form_code}&amp;do=bulkDir&amp;op=doBulkAdd' method='post'>
	<input type='hidden' name='dir' value='{$this->request['directory']}' />
	
	<div class="acp-box">
		<h3>{$this->lang->words['d_importoptions']}</h3>
		<table class='ipsTable double_pad'>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_howmanyfiles']}</strong>
				</td>
				<td class='field_field'>
					{$num}<br />
					<span class='desctext'>{$this->lang->words['d_toohightimeout']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_doremovefiles']}</strong>
				</td>
				<td class='field_field'>
					{$move}<br />
					<span class='desctext'>{$this->lang->words['d_nowillmove']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_selectcat']}</strong>
				</td>
				<td class='field_field'>
					{$cats}<br />
					<span class='desctext'>{$this->lang->words['d_imported2cat']}</span>
				</td>
			</tr>
			<tr>
				<td class='field_title'>
					<strong class='title'>{$this->lang->words['d_fowner']}</strong>
				</td>
				<td class='field_field'>
					{$name}<br />
					<span class='desctext'>{$this->lang->words['d_filesownedby']}</span>
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['d_import']}' class='button primary' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}