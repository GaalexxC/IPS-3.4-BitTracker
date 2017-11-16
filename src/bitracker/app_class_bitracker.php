<?php
/**
 *  devCU Software Development
 *  devCU biTracker 1.0.0 Release
 *  Last Updated: $Date: 2014-08-07 09:01:45 -0500 (Thursday, 07 August 2014) $
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

define( 'BIT_VERSION'	, '1.0.0' );
define( 'BIT_RVERSION'	, '10000'	);
define( 'BIT_LINK'		, 'http://devcu.com/' );

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Bitracker Manager class loader
 * @package	bitracker
 */
class app_class_bitracker
{
	/**
	 * Constructor
	 *
	 * @param	object	ipsRegistry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Fix settings in case ../ was used
		//-----------------------------------------
		
		ipsRegistry::$settings['bit_localsspath']	= str_replace( "&#46;&#46;/", '../', ipsRegistry::$settings['bit_localsspath'] );
		ipsRegistry::$settings['bit_localnfopath']	= str_replace( "&#46;&#46;/", '../', ipsRegistry::$settings['bit_localnfopath'] );		
		ipsRegistry::$settings['bit_localfilepath']	= str_replace( "&#46;&#46;/", '../', ipsRegistry::$settings['bit_localfilepath'] );
		
		//-----------------------------------------
		// Make sure caches were loaded
		//-----------------------------------------
		
		$registry->cache()->getCache( array( 'bit_mods', 'bit_cats' ) );
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . "/sources/classes/categories.php", 'class_bitcategories', 'bitracker' );
		
		$registry->setClass( 'categories', new $classToLoad( $registry ) );
		
		if( IN_ACP )
		{
			$registry->getClass('categories')->fullInit();
			
			/* Set a default module */
			if( ! ipsRegistry::$request['module'] )
			{
				ipsRegistry::$request['module']	= 'information';
			}
		}
		else
		{
			$registry->getClass('categories')->normalInit();
			
			$registry->getClass('class_localization')->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );
		}

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . "/sources/classes/functions.php", 'bitrackerFunctions', 'bitracker' );

		$registry->setClass( 'bitFunctions', new $classToLoad( $registry ) );
		
		//-----------------------------------------
		// Nexus currency
		//-----------------------------------------
		
		if ( IPSLib::appIsInstalled('nexus') and ipsRegistry::$settings['nexus_currency_locale'] )
		{
			setlocale( LC_MONETARY, ipsRegistry::$settings['nexus_currency_locale'] );
			ipsRegistry::getClass('class_localization')->local_data = localeconv();
		}
		
	}
	
	/**
	 * After output initialization
	 *
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function afterOutputInit( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Check if we are online
		//-----------------------------------------

		if( !IN_ACP )
		{	
			if( !defined('SKIP_ONLINE_CHECK') OR !SKIP_ONLINE_CHECK )
			{
				$registry->getClass('bitFunctions')->checkOnline();
			}
		}
		
		if( ipsRegistry::$request['showcat'] )
		{
			$category	= $registry->getClass('categories')->cat_lookup[ $_GET['showcat'] ];

			$registry->getClass('output')->checkPermalink( $category['cname_furl'] );
		}

		if( ipsRegistry::$request['request_method'] == 'get' )
		{
			if( $_GET['autocom'] == 'bitracker' or $_GET['automodule'] == 'bitracker' )
			{
				$registry->output->silentRedirect( ipsRegistry::$settings['base_url'] . "app=bitracker", 'false', true, 'app=bitracker' );
			}
		}
	}
}