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

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

/**
 *
 * @class		plugin_bitracker_files
 * @brief		Provide ability to share download manager files via editor
 */
class plugin_bitracker_files
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
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------

		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang			= $this->registry->class_localization;
		
		$this->lang->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );
		
		ipsRegistry::getAppClass( 'bitracker' );
	}
	
	/**
	 * Return the tab title
	 *
	 * @return	@e string
	 */
	public function getTab()
	{
		if( $this->memberData['member_id'] )
		{
			return $this->lang->words['sharedmedia_bitracker'];
		}
	}
	
	/**
	 * Return the HTML to display the tab
	 *
	 * @return	@e string
	 */
	public function showTab( $string )
	{
		//-----------------------------------------
		// Are we a member?
		//-----------------------------------------
		
		if( !$this->memberData['member_id'] )
		{
			return '';
		}

		//-----------------------------------------
		// How many approved events do we have?
		//-----------------------------------------
		
		$st		= intval($this->request['st']);
		$each	= 30;
		$where	= '';
		
		if( $string )
		{
			$where	= " AND file_name LIKE '%{$string}%'";
		}

		$count	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'bitracker_files', 'where' => "file_open=1 AND file_submitter={$this->memberData['member_id']}" . $where ) );
		$rows	= array();

		$pages	= $this->registry->output->generatePagination( array(	'totalItems'		=> $count['total'],
																		'itemsPerPage'		=> $each,
																		'currentStartValue'	=> $st,
																		'seoTitle'			=> '',
																		'method'			=> 'nextPrevious',
																		'noDropdown'		=> true,
																		'ajaxLoad'			=> 'mymedia_content',
																		'baseUrl'			=> "app=core&amp;module=ajax&amp;section=media&amp;do=loadtab&amp;tabapp=bitracker&amp;tabplugin=files&amp;search=" . urlencode($string) )	);

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => "file_open=1 AND file_submitter={$this->memberData['member_id']}" . $where, 'order' => 'file_updated DESC', 'limit' => array( $st, $each ) ) );
		$outer	= $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$rows[]	= array(
							'image'		=> $this->registry->bitFunctions->returnScreenshotUrl( $r ),
							'width'		=> 0,
							'height'	=> 0,
							'title'		=> IPSText::truncate( $r['file_name'], 25 ),
							'desc'		=> IPSText::truncate( strip_tags( IPSText::stripAttachTag( IPSText::getTextClass('bbcode')->stripAllTags( $r['file_desc'] ) ), '<br>' ), 100 ),
							'insert'	=> "bitracker:files:" . $r['file_id'],
							);
		}

		return $this->registry->output->getTemplate('editors')->mediaGenericWrapper( $rows, $pages, 'bitracker', 'files' );
	}

	/**
	 * Return the HTML output to display
	 *
	 * @param	int		$fileId		File ID to show
	 * @return	@e string
	 */
	public function getOutput( $fileId=0 )
	{
		$fileId	= intval($fileId);
		
		if( !$fileId )
		{
			return '';
		}

		$file	= $this->DB->buildAndFetch( array(
												'select'	=> 'f.*',
												'from'		=> array( 'bitracker_files' => 'f' ),
												'where'		=> 'f.file_open=1 AND f.file_id=' . $fileId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'c.*',
																		'from'		=> array( 'bitracker_categories' => 'c' ),
																		'where'		=> 'f.file_cat=c.cid',
																		'type'		=> 'left',
																		)
																	)
										)		);

		return $this->registry->output->getTemplate('bitracker_external')->bbCodeFile( $file );
	}
	
	/**
	 * Verify current user has permission to post this
	 *
	 * @param	int		$fileId	File ID to show
	 * @return	@e bool
	 */
	public function checkPostPermission( $fileId )
	{
		$fileId	= intval($fileId);
		
		if( !$fileId )
		{
			return '';
		}
		
		if( $this->memberData['g_is_supmod'] OR $this->memberData['is_mod'] )
		{
			return '';
		}
		
		$file	= $this->DB->buildAndFetch( array(
												'select'	=> 'f.*',
												'from'		=> array( 'bitracker_files' => 'f' ),
												'where'		=> 'f.file_open=1 AND f.file_id=' . $fileId,
												'add_join'	=> array(
																	array(
																		'select'	=> 'c.*',
																		'from'		=> array( 'bitracker_categories' => 'c' ),
																		'where'		=> 'f.file_cat=c.cid',
																		'type'		=> 'left',
																		)
																	)
										)		);
		
		if( $this->memberData['member_id'] AND $file['file_submitter'] == $this->memberData['member_id'] )
		{
			return '';
		}
		
		return 'no_permission_shared';
	}
}