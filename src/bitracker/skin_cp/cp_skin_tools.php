<?php
/**
 * @file		cp_skin_tools.php 	IP.bitracker Tools skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 *
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2011-04-22 10:21:31 -0400 (Fri, 22 Apr 2011) $
 * @version		v2.5.4
 * $Revision: 8448 $
 */

/**
 *
 * @class		cp_skin_tools
 * @brief		IP.bitracker Tools skin file
 */
class cp_skin_tools
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
 * Tools overview screen
 *
 * @return	@e string	HTML
 */
public function overviewScreen() {

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['t_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_filemanage']}</h3>
	<table class='ipsTable'>
		<tr>
			<td width='75%'>
				{$this->lang->words['d_bulkimport']}<br />
				<span class='desctext'>{$this->lang->words['d_bulk_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}module=tools&amp;section=bulkimport'>{$this->lang->words['d_fromdir']}</a> | <a href='{$this->settings['base_url']}module=tools&amp;section=bulkimport&amp;do=bulkZip'>{$this->lang->words['d_fromzip']}</a>
			</td>
		</tr>
		<tr>
			<td>
				{$this->lang->words['d_topiccheck']}<br />
				<span class='desctext'>{$this->lang->words['d_topic_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=check_topics'>{$this->lang->words['d_checktopic']}</a> | <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=do_topics&amp;all=1&amp;limit=20'>{$this->lang->words['d_fixalltopic']}</a>
			</td>
		</tr>
		<tr>
			<td>
				{$this->lang->words['d_latestfile']}<br />
				<span class='desctext'>{$this->lang->words['d_latest_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=do_cats'>{$this->lang->words['d_updateallcats']}</a>
			</td>
		</tr>
		<tr>
			<td>
				{$this->lang->words['d_dlcount']}<br />
				<span class='desctext'>{$this->lang->words['d_dlcount_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=recount_dlcounts'>{$this->lang->words['d_dlcountbutton']}</a>
			</td>
		</tr>
		<tr>
			<td>
				{$this->lang->words['d_thumbs']}<br />
				<span class='desctext'>{$this->lang->words['d_thumbs_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=thumbs'>{$this->lang->words['d_thumbs_button']}</a>
			</td>
		</tr>
		<tr>
			<td>
				{$this->lang->words['d_fixorph']}<br />
				<span class='desctext'>{$this->lang->words['d_fixorph_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=check_orph'>{$this->lang->words['d_fixorph_button']}</a>
			</td>
		</tr>
		<tr>
			<td>
				{$this->lang->words['d_fixbrok']}<br />
				<span class='desctext'>{$this->lang->words['d_fixbrok_info']}</span>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=check_broken'>{$this->lang->words['d_fixbrok_button']}</a>
			</td>
		</tr>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List broken files
 *
 * @param	string	No file
 * @param	string	No image
 * @return	@e string	HTML
 */
public function brokenFileListing( $noFile, $noImg ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form name='adminForm' action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_broken&amp;type=file' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['d_norecord']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='2%'>&nbsp;</th>
			<th width='95%'>{$this->lang->words['d_fname']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
EOF;

if( $noFile )
{
	$IPBHTML .= $noFile;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4' class='no_messages'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
		<tr>
	</table>
	<div class='acp-actionbar'>
		{$this->lang->words['bf_with_selected']}:
		<select name='action'>
			<option value='del'>{$this->lang->words['d_deletefiles']}</option>
			<option value='hide'>{$this->lang->words['d_hidefiles']}</option>
		</select>
		<input type='submit' value='{$this->lang->words['d_goright']}' class='button primary' />
	</div>
</div>
</form>
<br />

<form name='adminForm' action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_broken&amp;type=imgs' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['d_ssrecord']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='2%'>&nbsp;</th>
			<th width='90%'>{$this->lang->words['d_fname']}</th>
			<th class='col_buttons'>&nbsp;</th>
		</tr>
EOF;

if( $noImg )
{
	$IPBHTML .= $noImg;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='4' class='no_messages'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>
		{$this->lang->words['bf_with_selected']}:
		<select name='action'>
			<option value='del'>{$this->lang->words['d_deletefiles']}</option>
			<option value='hide'>{$this->lang->words['d_hidefiles']}</option>
			<option value='rem'>{$this->lang->words['d_removeimgs']}</option>
		</select>
		<input type='submit' value='{$this->lang->words['d_goright']}' class='button primary' />
	</div>
</div>
</form>
<br />
			
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Broken file row
 *
 * @param	array		$file		File info
 * @param	string		$type		Type (file or screenshot)
 * @return	@e string	HTML
 */
public function brokenFileRow( $file, $type='file' ) {

$checkbox	= $this->registry->output->formCheckbox( $type . '_' . $file['record_id'], true );
$text		= $this->lang->words['t_' . $type ];

/* Get badge color */
switch( $type )
{
	case 'ss':
		$_badge = 'green';
		break;
	case 'thumb':
		$_badge = 'purple';
		break;
	default:
		$_badge = 'red';
		break;
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr class='ipsControlRow'>
	<td class='center'><span class='ipsBadge badge_{$_badge}'>{$text}</span></td>
	<td class='center'>{$checkbox}</td>
	<td><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;showfile={$file['file_id']}'>{$file['file_name']}</a></td>
	<td class='col_buttons'>
		<ul class='ipsControlStrip'>
			<li class='i_edit'><a href='{$this->settings['board_url']}/index.php?app=bitracker&amp;module=post&amp;section=submit&amp;do=edit_main&amp;id={$file['file_id']}' target='_blank' title='{$this->lang->words['d_editfile']}'>{$this->lang->words['d_editfile']}</a></li>
		</ul>
	</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show orphaned files
 *
 * @param	string		$noFile		No files list
 * @return	@e string	HTML
 */
public function orphanedFileListing( $noFile ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<form name='adminForm' action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_orph' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['d_orphlist']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='5%'>&nbsp;</th>
			<th width='90%'{$this->lang->words['d_fname']}/th>
			<th width='5%'>&nbsp;</th>
		</tr>
EOF;

if( $noFile )
{
	$IPBHTML .= $noFile;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='3' class='no_messages'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['d_removeselected']}' id='button' class='button primary' />
	</div>
</div>
</form>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Orphaned file row
 *
 * @param	array		$record		Data about the file
 * @param	string		$type		Type of orphaned file
 * @return	@e string	HTML
 */
public function orphanedFileRow( $record, $type='file' ) {

$checkbox	= $this->registry->output->formCheckbox( $type . '_' . urlencode($record), 1, urlencode($record) );
$text		= $this->lang->words['t_' . $type ];

/* Get badge color */
switch( $type )
{
	case 'ss':
		$_badge = 'green';
		break;
	case 'thumb':
		$_badge = 'purple';
		break;
	default:
		$_badge = 'red';
		break;
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td class='center'><span class='ipsBadge badge_{$_badge}'>{$text}</span></td>
	<td>{$record}</td>
	<td>{$checkbox}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Topics listing
 *
 * @param	string		$topics		Rows
 * @param	string		$missing	Missing topics
 * @return	@e string	HTML
 */
public function topicsListing( $topics, $missing ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form name='adminForm' action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_topics' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['d_topiclinked']}</h3>
	<table class='ipsTable double_pad'>
		<tr>
			<th width='70%'>{$this->lang->words['d_fname']}</th>
			<th width='20%'>{$this->lang->words['d_topic']}</th>
			<th width='10%'>&nbsp;</th>
		</tr>
EOF;

if( $topics )
{
	$IPBHTML .= $topics;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='3' class='no_messages'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
		<tr>
			<th colspan='3'>{$this->lang->words['d_notopiclinked']}</th>
		</tr>
EOF;

if( $missing )
{
	$IPBHTML .= $missing;
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
			<td colspan='3' class='no_messages'>{$this->lang->words['d_nonefound']}</td>
		</tr>
EOF;
}

$_createField  = $this->registry->output->formInput( 'limit', 10, '', 3 );
$_createTopics = sprintf( $this->lang->words['d_createtopics'], $_createField );

$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>
		{$_createTopics}&nbsp;&nbsp;<input class='button primary' type='submit' value='{$this->lang->words['d_goright']}' />
	</div>
</div>
</form>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Topics row
 *
 * @param	array		$row		File data
 * @return	@e string	HTML
 */
public function topicsRow( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td><strong class='title'>{$row['file_name']}</strong></td>
	<td>{$row['file_topicid']}</td>
	<td><input type='checkbox' checked='checked' name='file_{$row['file_id']}' value='1' /></td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

}