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

/* Can search with this app */
$CONFIG['can_search']					= 1;

/* Can view new content with this app */
$CONFIG['can_viewNewContent']			= 1;
$CONFIG['can_vnc_unread_content']		= 1;
$CONFIG['can_vnc_filter_by_followed']	= 1;

/* Can fetch user generated content */
$CONFIG['can_userContent']				= 1;

/* Can search tags */
if ( !isset( $_REQUEST['search_app_filters']['bitracker']['searchInKey'] ) or $_REQUEST['search_app_filters']['bitracker']['searchInKey'] == 'files' )
{
	$CONFIG['can_searchTags']		= 1;
}
else
{
	$CONFIG['can_searchTags']		= 0;
}

/* Content types, put the default one first */
$CONFIG['contentTypes']			= array( 'files', 'comments' );

/* Content types for 'follow', default one first */
$CONFIG['followContentTypes']		= array( 'files', 'categories' );