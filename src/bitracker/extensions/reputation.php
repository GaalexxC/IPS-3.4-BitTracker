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

$rep_author_config = array( 
						'comment_id' => array( 'column' => 'comment_mid', 'table'  => 'bitracker_comments' )
					);
					
/*
 * The following config items are for the log viewer in the ACP 
 */

$rep_log_joins = array(
						array(
								'from'   => array( 'bitracker_comments' => 'c' ),
								'where'  => 'r.type="pid" AND r.type_id=c.comment_id AND r.app="bitracker"',
								'type'   => 'left'
							),
						array(
								'select' => 'f.file_name as repContentTitle, f.file_id as repContentID',
								'from'   => array( 'bitracker_files' => 'f' ),
								'where'  => 'c.comment_fid=f.file_id',
								'type'   => 'left'
							),
					);

$rep_log_where = "c.comment_mid=%s";

$rep_log_link = 'app=bitracker&amp;showfile=%d#comment_%d';