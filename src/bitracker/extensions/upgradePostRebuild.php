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
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class postRebuild_bitracker
{
	/**
	 * New content parser
	 *
	 * @access	public
	 * @var		object
	 */
	public $parser;

	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
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
	/**#@-*/
	
	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		ipsRegistry::getAppClass( 'bitracker' );
	}
	
	/**
	 * Grab the dropdown options
	 *
	 * @access	public
	 * @return	array 		Multidimensional array of contents we can rebuild
	 */
	public function getDropdown()
	{
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );

		$return		= array( array( 'bit_files', ipsRegistry::getClass('class_localization')->words['rebuild_bit_files'] ) );
		$return[]	= array( 'bit_comments', ipsRegistry::getClass('class_localization')->words['rebuild_bit_comms'] );
	    return $return;
	}
	
	/**
	 * Find out if there are any more
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @return	integer
	 */
	public function getMax( $type, $dis )
	{
		switch( $type )
		{
			case 'bit_files':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'file_id as nextid', 'from' => 'bitracker_files', 'where' => 'file_id > ' . $dis, 'order' => 'file_id ASC', 'limit' => array(1)  ) );
			break;
			
			case 'bit_comments':
				$tmp = $this->DB->buildAndFetch( array( 'select' => 'comment_id as nextid', 'from' => 'bitracker_comments', 'where' => 'comment_id > ' . $dis, 'order' => 'comment_id ASC', 'limit' => array(1)  ) );
			break;
		}
		
		return intval( $tmp['nextid'] );
	}
	
	/**
	 * Execute the database query to return the results
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	integer		Start point
	 * @param	integer		End point
	 * @return	integer
	 */
	public function executeQuery( $type, $start, $end )
	{
		switch( $type )
		{
			case 'bit_files':
				$this->DB->build( array( 'select' 	=> 'f.*',
														 'from' 	=> array( 'bitracker_files' => 'f' ),
														 'order' 	=> 'f.file_id ASC',
														 'where'	=> 'f.file_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=f.file_submitter"
														  						)	)
												) 		);
			break;
			
			case 'bit_comments':
				$this->DB->build( array( 'select' 	=> 'c.*',
														 'from' 	=> array( 'bitracker_comments' => 'c' ),
														 'order' 	=> 'c.comment_id ASC',
														 'where'	=> 'c.comment_id > ' . $start,
														 'limit' 	=> array($end),
														 'add_join'	=> array( 	1 => array( 'type'		=> 'left',
														  									'select'	=> 'm.member_group_id, m.mgroup_others',
														  								  	'from'		=> array( 'members' => 'm' ),
														  								  	'where' 	=> "m.member_id=c.comment_mid"
														  						),
														  						2 => array( 'type'		=> 'left',
														  									'select'	=> 'f.file_cat',
														  								  	'from'		=> array( 'bitracker_files' => 'f' ),
														  								  	'where' 	=> "f.file_id=c.comment_fid"
														  						)	)
												) 		);
			break;
		}
	}
	
	/**
	 * Get preEditParse of the content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @return	string		Content preEditParse
	 */
	public function getRawPost( $type, $r )
	{
		$category	= $this->registry->getClass('categories')->cat_lookup[ $r['file_cat'] ];

		$this->parser->parse_smilies	= 1;
		$this->parser->parse_html		= $category['coptions']['opt_html'] ? 1 : 0;
		$this->parser->parse_bbcode		= $category['coptions']['opt_bbcode'] ? 1 : 0;
		$this->parser->parse_nl2br		= 1;

		switch( $type )
		{
			case 'bit_files':
				$this->parser->parsing_section	= 'bit_submit';

				$rawpost = $this->parser->preEditParse( $r['file_desc'] );
			break;
			
			case 'bit_comments':
				$this->parser->parsing_section	= 'bit_comment';

				$rawpost = $this->parser->preEditParse( $r['comment_text'] );
			break;
		}

		return $rawpost;
	}
	
	/**
	 * Store the newly converted content
	 *
	 * @access	public
	 * @param	string		Content type we are rebuilding ( key 0 in arrays from getDropdown() )
	 * @param	array 		Database record from while loop
	 * @param	string		Newly parsed post
	 * @return	string		Content preEditParse
	 */
	public function storeNewPost( $type, $r, $newpost )
	{
		$lastId	= 0;
		
		switch( $type )
		{
			case 'bit_files':
				$this->DB->update( 'bitracker_files', array( 'file_desc' => $newpost ), 'file_id=' . $r['file_id'] );
				$lastId = $r['file_id'];
			break;
			
			case 'bit_comments':
				$this->DB->update( 'bitracker_comments', array( 'comment_text' => $newpost ), 'comment_id=' . $r['comment_id'] );
				$lastId = $r['comment_id'];
			break;
		}

		return $lastId;
	}
}