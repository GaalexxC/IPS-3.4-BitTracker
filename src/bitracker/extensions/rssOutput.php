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

class rss_output_bitracker
{
	/**
	 * Expiration date
	 *
	 * @access	protected
	 * @var		integer			Expiration timestamp
	 */
	protected $expires			= 0;
	
	/**
	 * RSS object
	 *
	 * @access	public
	 * @var		object
	 */
	public $class_rss;

	/**
	 * Grab the RSS links
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function getRssLinks()
	{		
		$return	= array();

		if( ipsRegistry::$settings['bit_rss'] )
		{
			/* Lang */
			ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );

	        $return[] = array( 'title' => ipsRegistry::getClass('class_localization')->words['bit_rss_title'], 'url' => ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=core&amp;module=global&amp;section=rss&amp;type=bitracker", true, 'section=rss' ) );
	    }

	    return $return;
	}
	
	/**
	 * Grab the RSS document content and return it
	 *
	 * @access	public
	 * @return	string		RSS document
	 */
	public function returnRSSDocument()
	{
		/* Lang */
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );

		//--------------------------------------------
		// Require classes
		//--------------------------------------------
		
		$classToLoad				= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classRss.php', 'classRss' );
		$this->class_rss			= new $classToLoad();
		$this->class_rss->doc_type	= ipsRegistry::$settings['gb_char_set'];
		
		//-----------------------------------------
		// Enabled?
		//-----------------------------------------
		
		if( !ipsRegistry::$settings['bit_rss'] )
		{
			return $this->_returnError( $this->lang->words['rss_disabled'] );
		}
		
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 0;
        
		$channel_id = $this->class_rss->createNewChannel( array( 'title'		=> ipsRegistry::getClass('class_localization')->words['bit_rss_title'],
																 'link'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . '/index.php?app=bitracker&amp;module=search&amp;section=search&amp;do=last_ten' ),
																 'pubDate'		=> $this->class_rss->formatDate( time() ),
																 'ttl'			=> 30 * 60,
																 'description'	=> ipsRegistry::getClass('class_localization')->words['bit_rss_desc']
													)      );

		$_cache	= ipsRegistry::cache()->getCache('group_cache');
		
		ipsRegistry::DB()->build( array( 'select' 	=> 'f.*',
										 'from'		=> array('bitracker_files' => 'f'),
										 'add_join'	=> array( 
															array(
																	'select'	=> 'm.members_display_name',
																	'from'		=> array( 'members' => 'm' ),
																	'where'		=> "f.file_submitter=m.member_id",
																	'type'		=> 'left'
																),
															array(
																	'select'	=> 'c.coptions',
																	'from'		=> array( 'bitracker_categories' => 'c' ),
																	'where'		=> "c.cid=f.file_cat",
																	'type'		=> 'left'
																),
															array(
																	'from'		=> array( 'permission_index' => 'i' ),
																	'where'		=> "i.app='bitracker' AND i.perm_type='cat' AND i.perm_type_id=f.file_cat",
																	'type'		=> 'left'
																),
															),
										 'where'	=> 'f.file_open=1 AND ' . ipsRegistry::DB()->buildRegexp( "i.perm_view", explode( ',', $_cache[ ipsRegistry::$settings['guest_group'] ]['g_perm_id'] ) ),
										 'order'	=> 'f.file_submitted DESC',
										 'limit'	=> array( 0,10 )	
								)		);
		$outer = ipsRegistry::DB()->execute();
		
		while( $r = ipsRegistry::DB()->fetch($outer) )
		{
			$category_opts	= unserialize( $r['coptions'] );

			IPSText::getTextClass( 'bbcode' )->parse_bbcode		= $category_opts['opt_bbcode'];
			IPSText::getTextClass( 'bbcode' )->parse_html		= $category_opts['opt_html'];
			IPSText::getTextClass( 'bbcode' )->parse_nl2br		= 1;
			
			$r['file_desc'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $r['file_desc'] );
			
			$this->class_rss->addItemToChannel( $channel_id, array( 'title'			=> $r['file_name'],
																	'link'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . '/index.php?app=bitracker&amp;showfile=' . $r['file_id'], $r['file_name_furl'], 'bitshowfile' ),
																	'description'	=> $r['file_desc'],
																	'pubDate'		=> $this->class_rss->formatDate( $r['file_submitted'] ),
																	'guid'			=> $r['file_id']
									  )                    );
		}
		
		$this->class_rss->createRssDocument();
		
		$this->class_rss->rss_document = ipsRegistry::getClass('output')->replaceMacros( $this->class_rss->rss_document );

		return $this->class_rss->rss_document;
	}
	
	/**
	 * Grab the RSS document expiration timestamp
	 *
	 * @access	public
	 * @return	integer		Expiration timestamp
	 */
	public function grabExpiryDate()
	{
		// Generated on the fly, so just return expiry of one hour
		return time() + 3600;
	}
	
	/**
	 * Return an error document
	 *
	 * @access	protected
	 * @param	string			Error message
	 * @return	string			XML error document for RSS request
	 */
	protected function _returnError( $error='' )
	{
		$channel_id = $this->class_rss->createNewChannel( array( 
															'title'			=> ipsRegistry::getClass('class_localization')->words['rss_disabled'],
															'link'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=bitracker", 'false', 'app=bitracker' ),
				 											'description'	=> ipsRegistry::getClass('class_localization')->words['rss_disabled'],
				 											'pubDate'		=> $this->class_rss->formatDate( time() ),
				 											'webMaster'		=> ipsRegistry::$settings['email_in'] . " (" . ipsRegistry::$settings['board_name'] . ")",
				 											'generator'		=> 'IP.bitracker'
				 										)		);

		$this->class_rss->addItemToChannel( $channel_id, array( 
														'title'			=> ipsRegistry::getClass('class_localization')->words['rss_error_message'],
			 										    'link'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=bitracker", 'false', 'app=bitracker' ),
			 										    'description'	=> $error,
			 										    'pubDate'		=> $this->class_rss->formatDate( time() ),
			 										    'guid'			=> ipsRegistry::getClass('output')->formatUrl( ipsRegistry::$settings['board_url'] . "/index.php?app=bitracker&error=1", 'false', 'app=bitracker' ) ) );

		//-----------------------------------------
		// Do the output
		//-----------------------------------------

		$this->class_rss->createRssDocument();

		return $this->class_rss->rss_document;
	}
}