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

$_LOAD = array(
				'bit_cats'		=> 1,
				'bit_mods'		=> 1,
				);


$valid_reqs = array (
					'index'				=>	array( 'bit_stats' => 1, 'profilefields' => 1, 'ranks' => 1, 'bbcode' => 1, 'badwords' => 1, 'reputation_levels' => 1, 'moderators' => 1, 'emoticons' => 1 ),
					'file'				=>	array( 'bit_cfields' => 1, 'bit_mimetypes' => 1, 'profilefields' => 1, 'ranks' => 1, 'bbcode' => 1, 'badwords' => 1, 'reputation_levels' => 1, 'sharelinks' => 1, 'emoticons' => 1, 'moderators' => 1 ),
					'category'			=>	array( 'bit_mimetypes' => 1, 'emoticons' => 1, 'moderators' => 1 ),
					'ucp'				=>	array( 'bbcode' => 1 ),
					'submit'			=>  array( 'bbcode' => 1, 'bit_cfields' => 1, 'bit_mimetypes' => 1, 'emoticons' => 1, 'badwords' => 1 ),
					'moderate'			=>  array( 'bbcode' => 1, 'bit_mimetypes' => 1, 'emoticons' => 1, 'badwords' => 1 ),
					'search'			=>  array( 'bbcode' => 1, 'emoticons' => 1, 'bit_stats' => 1, 'bit_cfields' => 1 ),
					'download'			=>  array( 'bit_stats' => 1, 'bit_mimetypes' => 1 ),
				 );

$req = ( isset( $valid_reqs[ $_GET['module'] ] ) ? strtolower($_GET['module']) : 'index' );

if( $_GET['showfile'] )
{
	$req	= 'file';
}
else if( $_GET['showcat'] )
{
	$req	= 'category';
}

if ( isset( $valid_reqs[ $req ] ) )
{
	$_LOAD = array_merge( $_LOAD, $valid_reqs[ $req ] );
}
else
{
	$_LOAD = array_merge( $_LOAD, $valid_reqs['index'] );
}

$CACHE['bit_cats']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/categories.php',
								'recache_class'		=> 'class_biTracker_categories',
								'recache_function'	=> 'rebuildCatCache' 
							);

$CACHE['bit_mods']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/categories.php',
								'recache_class'		=> 'class_biTracker_categories',
								'recache_function'	=> 'rebuildModCache' 
							);
						
$CACHE['bit_cfields']	= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'bitracker' ) . '/modules_admin/customize/cfields.php',
								'recache_class'		=> 'admin_bitracker_customize_cfields',
								'recache_function'	=> 'rebuildCache' 
							);

$CACHE['bit_stats']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/categories.php',
								'recache_class'		=> 'class_biTracker_categories',
								'recache_function'	=> 'rebuildStatsCache' 
							);
						    
$CACHE['bit_mimetypes']		= array( 
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'bitracker' ) . '/modules_admin/customize/mimetypes.php',
								'recache_class'		=> 'admin_bitracker_customize_mimetypes',
								'recache_function'	=> 'rebuildCache' 
							);


/**
* Array for holding reset information
*
* Populate the $_RESET array and ipsRegistry will do the rest
*/

$_RESET = array();

###### Redirect requests... ######

# automodule/com
if ( $_REQUEST['automodule'] == 'bitracker' )
{
	$_RESET['app']     = 'bitracker';
}

if ( $_REQUEST['autocom'] == 'bitracker' )
{
	$_RESET['app']     = 'bitracker';
}

# shortcut links
if ( $_REQUEST['showfile'] )
{
	$_RESET['app']		= 'bitracker';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'file';
	$_RESET['id']		= intval( $_REQUEST['showfile'] );
}

if ( $_REQUEST['showcat'] )
{
	$_RESET['app']		= 'bitracker';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'category';
	$_RESET['id']		= intval( $_REQUEST['showcat'] );
	$_RESET['catid']	= intval( $_REQUEST['showcat'] );
}

if ( $_REQUEST['code'] == 'sst' )
{
	$_RESET['app']		= 'bitracker';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'screenshot';
}

if ( $_REQUEST['code'] == 'nff' )
{
	$_RESET['app']		= 'bitracker';
	$_RESET['module']	= 'display';
	$_RESET['section']	= 'nfo';
}

if (  $_REQUEST['app'] == 'bitracker' and $_REQUEST['module'] == 'client' and $_REQUEST['section'] == 'announce' )
{
   define( 'IPS_ENFORCE_ACCESS', TRUE );
}


# ALL
if ( $_REQUEST['CODE'] or $_REQUEST['code'] )
{
	$_RESET['do'] = ( $_REQUEST['CODE'] ) ? $_REQUEST['CODE'] : $_REQUEST['code'];
}


/* Group options */
$_GROUP	= array( 'zero_is_best' => array( 'bit_throttling' ), 'less_is_more' => array( 'bit_wait_period' )  );

