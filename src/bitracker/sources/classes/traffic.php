<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.download Manager traffic stat library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		1st April 2004
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class trafficLibrary
{
	/**
	 * Browsers
	 *
	 * @access	public
	 * @var		array
	 */	
	public $BROWSERS;

	/**
	 * Operating Systems
	 *
	 * @access	public
	 * @var		array
	 */	
	public $OS;

	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Load libraries
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function loadLibraries()
	{
		/* Init vars */
		$BROWSERS	= array();
		$OS			= array();
		
		require ( IPSLib::getAppDir('bitracker') . '/sources/classes/traffic_os.php' );/*noLibHook*/
		require ( IPSLib::getAppDir('bitracker') . '/sources/classes/traffic_browsers.php' );/*noLibHook*/
		
		$this->BROWSERS    = $BROWSERS;
		$this->OS          = $OS;
		
		unset( $BROWSERS );
		unset( $OS );
	}
	
	/**
	 * Retrieve the image for the item
	 *
	 * @access	public
	 * @param	string		Type of stat item to check
	 * @param	string		Title to check
	 * @return	string		Icon or language string
	 */
	public function getItemImage( $type, $title )
	{
		if ( ! array( $this->BROWSERS ) or ! count( $this->BROWSERS ) )
		{
			$this->loadLibraries();
		}
		
		switch( $type )
		{
			case 'browsers':
				return 'browser_' . $this->BROWSERS[$title]['b_icon'] . '.png';
				break;
			case 'os':
				return 'os_' . $this->OS[$title]['b_icon'] . '.png';
				break;
		}
	}
	
	/**
	 * Return statistical data
	 *
	 * @access	public
	 * @param	array 		Raw stat data
	 * @return	array 		Formatted stats
	 */
	public function returnStatData( $raw_data )
	{
		$log_entry = array();
		
		//-----------------------------------------
		// Get robot, browser, OS
		//-----------------------------------------
		
		$tmp = $this->_getBrowserAndOS( $raw_data );
		
		$log_entry['stat_browser']		= $tmp['stat_browser'];
		$log_entry['stat_browsers']		= $tmp['stat_browser'];
		$log_entry['stat_browser_key']	= $tmp['stat_browser_key'];
		$log_entry['stat_browsers_key']	= $tmp['stat_browser_key'];
		$log_entry['stat_os']			= $tmp['stat_os'];
		$log_entry['stat_os_key']		= $tmp['stat_os_key'];
		$log_entry['stat_ip_address']	= $raw_data['dip'];

		//-----------------------------------------
		// Others...
		//-----------------------------------------
		
		$log_entry['stat_date'] 	= $raw_data['dtime'];
		$log_entry['stat_file'] 	= $raw_data['dfid'];
		$log_entry['stat_filesize'] = $raw_data['dsize'];
		$log_entry['stat_member'] 	= $raw_data['dmid'];
		
		return $log_entry;
	}
		
	/**
	 * Return statistical data
	 *
	 * @access	protected
	 * @param	array 		Raw stat data
	 * @return	array 		Formatted stats
	 */
	protected function _getBrowserAndOS( $raw_data )
	{
		$return   = array();
		
		//-----------------------------------------
		// Check for browser
		//-----------------------------------------
		
		foreach( $this->BROWSERS as $title => $array )
		{
			foreach( $array['b_regex'] as $left => $right )
			{
				if ( ! preg_match( "#" . $left . "#i", $raw_data['dua'], $matches ) )
				{
					continue;
				}
				else
				{
					//-----------------------------------------
					// Okay, we got a match - finalize
					//-----------------------------------------
					
					if ( preg_match( "/\\\\[0-9]{1}/", $right ) )
					{
						 $version = ' ' . preg_replace( ":\\\\([0-9]{1}):e", "\$matches[\\1]", $right );
					}
					else
					{
						$version = "";
					}
				
					$return['stat_browser']      = $array['b_title'] . stripslashes($version);
					$return['stat_browser_key']  = $title;
					break 2;
				}
			}
		}
		
		//-----------------------------------------
		// Check for OS
		//-----------------------------------------
		
		foreach( $this->OS as $title => $array )
		{
			foreach( $array['b_regex'] as $left => $right )
			{
				if ( ! preg_match( "#" . $left . "#i", $raw_data['dua'], $matches ) )
				{
					continue;
				}
				else
				{
					//-----------------------------------------
					// Okay, we got a match - finalize
					//-----------------------------------------
					
					if ( preg_match( "/\\\\[0-9]{1}/", $right ) )
					{
						 $version = ' ' . preg_replace( ":\\\\([0-9]{1}):e", "\$matches[\\1]", $right );
					}
					else
					{
						$version = "";
					}
					
					$return['stat_os']      = $array['b_title'].stripslashes($version);
					$return['stat_os_key']  = $title;
					break 2;
				}
			}
		}
		
		return $return;
	}
}