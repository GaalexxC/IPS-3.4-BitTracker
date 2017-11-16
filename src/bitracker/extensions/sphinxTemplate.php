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

$fields	= array();

ipsRegistry::DB()->build( array( 'select' => 'cf_id', 'from' => 'bitracker_cfields' ) );
ipsRegistry::DB()->execute();

while( $r = ipsRegistry::DB()->fetch() )
{
	$fields[]	= $r['cf_id'];
}

$_join	= '';

if( count($fields) )
{
	$_join	= ', cc.field_' . implode( ', cc.field_', $fields );
}

$appSphinxTemplate	= <<<EOF

############################ --- bitracker --- ##############################

source <!--SPHINX_CONF_PREFIX-->bitracker_search_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_bitracker_counter', (SELECT max(file_id) FROM <!--SPHINX_DB_PREFIX-->bitracker_files), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT f.file_id as dont_use_this, f.file_id as search_id, f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc{$_join}, f.* \
					  FROM <!--SPHINX_DB_PREFIX-->bitracker_files f \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->bitracker_ccontent cc ON (cc.file_id=f.file_id)
	
	# Fields	
	sql_attr_uint			= search_id
	sql_attr_uint			= file_id
	sql_attr_uint			= file_cat
	sql_attr_uint			= file_open
	sql_attr_uint			= file_views
	sql_attr_uint			= file_rating
	sql_attr_uint			= file_bitracker
	sql_attr_timestamp		= file_updated
	sql_attr_timestamp		= file_submitted
	sql_attr_uint			= file_submitter
	sql_attr_str2ordinal	= fordinal
	sql_attr_float			= file_cost
	sql_attr_multi			= uint tag_id from query; SELECT tag_meta_id, tag_id FROM <!--SPHINX_DB_PREFIX-->core_tags WHERE tag_meta_app='bitracker' AND tag_meta_area='files'
	
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->bitracker_search_delta : <!--SPHINX_CONF_PREFIX-->bitracker_search_main
{
	# Override the base sql_query_pre
	sql_query_pre	= 
	
	# Query posts for the main source
	sql_query		= SELECT f.file_id as dont_use_this, f.file_id as search_id, f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc, f.*{$_join} \
					  FROM <!--SPHINX_DB_PREFIX-->bitracker_files f \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->bitracker_ccontent cc ON (cc.file_id=f.file_id) \
					  WHERE f.file_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_bitracker_counter' )
}

index <!--SPHINX_CONF_PREFIX-->bitracker_search_main
{
	source			= <!--SPHINX_CONF_PREFIX-->bitracker_search_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->bitracker_search_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0	
}

index <!--SPHINX_CONF_PREFIX-->bitracker_search_delta : <!--SPHINX_CONF_PREFIX-->bitracker_search_main
{
   source			= <!--SPHINX_CONF_PREFIX-->bitracker_search_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->bitracker_search_delta
}

source <!--SPHINX_CONF_PREFIX-->bitracker_comments_main : <!--SPHINX_CONF_PREFIX-->ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_bitracker_comments_counter', (SELECT max(comment_id) FROM <!--SPHINX_DB_PREFIX-->bitracker_comments), 0, UNIX_TIMESTAMP(), 0 )
	
	# Query posts for the main source
	sql_query		= SELECT c.comment_id, c.comment_id as search_id, c.comment_mid as comment_member_id, c.comment_date, c.comment_open, c.comment_text, \
	 						 f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc, f.*{$_join} \
					  FROM <!--SPHINX_DB_PREFIX-->bitracker_comments c \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->bitracker_files f ON ( c.comment_fid=f.file_id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->bitracker_ccontent cc ON (cc.file_id=f.file_id) 
	
	# Fields
	sql_attr_uint			= search_id
	sql_attr_uint			= file_id
	sql_attr_uint			= file_cat
	sql_attr_uint			= file_open
	sql_attr_uint			= file_views
	sql_attr_uint			= file_rating
	sql_attr_uint			= file_bitracker
	sql_attr_uint			= comment_open
	sql_attr_uint			= comment_member_id
	sql_attr_timestamp		= comment_date
	sql_attr_timestamp		= file_updated
	sql_attr_timestamp		= file_submitted
	sql_attr_uint			= file_submitter
	sql_attr_str2ordinal	= fordinal
	sql_attr_float			= file_cost
	sql_ranged_throttle	= 0
}

source <!--SPHINX_CONF_PREFIX-->bitracker_comments_delta : <!--SPHINX_CONF_PREFIX-->bitracker_comments_main
{
	# Override the base sql_query_pre
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT c.comment_id, c.comment_id as search_id, c.comment_mid as comment_member_id, c.comment_date, c.comment_open, c.comment_text, \
	 						 f.file_name as fordinal, REPLACE( f.file_name, '-', '&\#8208') as file_name, REPLACE( f.file_desc, '-', '&\#8208') as file_desc, f.*{$_join} \
					  FROM <!--SPHINX_DB_PREFIX-->bitracker_comments c \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->bitracker_files f ON ( c.comment_fid=f.file_id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->bitracker_ccontent cc ON (cc.file_id=f.file_id) \
					  WHERE c.comment_id <= ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_bitracker_comments_counter' )	
}

index <!--SPHINX_CONF_PREFIX-->bitracker_comments_main
{
	source			= <!--SPHINX_CONF_PREFIX-->bitracker_comments_main
	path			= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->bitracker_comments_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0
	#infix_fields    = comment_text
	#min_infix_len   = 3
	#enable_star     = 1
}

index <!--SPHINX_CONF_PREFIX-->bitracker_comments_delta : <!--SPHINX_CONF_PREFIX-->bitracker_comments_main
{
   source			= <!--SPHINX_CONF_PREFIX-->bitracker_comments_delta
   path				= <!--SPHINX_BASE_PATH-->/<!--SPHINX_CONF_PREFIX-->bitracker_comments_delta
}

EOF;
