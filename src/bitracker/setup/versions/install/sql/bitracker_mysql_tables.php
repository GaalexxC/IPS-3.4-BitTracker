<?php
/**
* Installation Schematic File
* Generated on Thu, 19 Feb 2009 08:15:47 +0000 GMT
*/
$TABLE[] = "CREATE TABLE bitracker_categories (
  cid int(10) NOT NULL auto_increment,
  cparent int(10) NOT NULL default '0',
  cname varchar(255) NOT NULL default '',
  cdesc mediumtext,
  copen tinyint(1) NOT NULL default '0',
  cposition int(10) NOT NULL default '0',
  cperms text,
  coptions text,
  ccfields text,
  cfileinfo text,
  cdisclaimer mediumtext,
  cname_furl varchar( 255 ) NULL default NULL,
  ctags_disabled TINYINT NOT NULL DEFAULT '0',
  ctags_noprefixes TINYINT NOT NULL DEFAULT '0',
  ctags_predefined TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (cid),
  KEY cparent (cparent),
  KEY position_order ( cparent , cposition )
);";

$TABLE[] = "CREATE TABLE bitracker_ccontent (
  file_id mediumint(8) NOT NULL default '0',
  updated int(10) default '0',
  PRIMARY KEY  (file_id)
);";

$TABLE[] = "CREATE TABLE bitracker_cfields (
  cf_id smallint(5) NOT NULL auto_increment,
  cf_title varchar(250) NOT NULL default '',
  cf_desc varchar(250) NOT NULL default '',
  cf_content text,
  cf_type varchar(250) NOT NULL default '',
  cf_not_null tinyint(1) NOT NULL default '0',
  cf_max_input smallint(6) NOT NULL default '0',
  cf_input_format text,
  cf_file_format mediumtext,
  cf_position smallint(6) NOT NULL default '0',
  cf_topic tinyint(1) NOT NULL default '0',
  cf_search tinyint(1) NOT NULL default '0',
  cf_format TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (cf_id),
  KEY cf_position (cf_position)
);";

$TABLE[] = "CREATE TABLE bitracker_comments (
  comment_id int(10) NOT NULL auto_increment,
  comment_fid int(10) NOT NULL default '0',
  comment_mid mediumint(8) NOT NULL default '0',
  comment_date int(10) NOT NULL default '0',
  comment_open tinyint(1) NOT NULL default '0',
  comment_text mediumtext,
  comment_append_edit tinyint(1) NOT NULL default '0',
  comment_edit_time int(10) NOT NULL default '0',
  comment_edit_name varchar(255) default NULL,
  ip_address varchar(46) default NULL,
  use_sig tinyint(1) NOT NULL default '1',
  use_emo tinyint(1) NOT NULL default '1',
  comment_author VARCHAR( 255 ) NULL DEFAULT NULL,
  PRIMARY KEY  (comment_id),
  KEY comment_fid ( comment_fid , comment_date )
);";

$TABLE[] = "CREATE TABLE bitracker_bitracker (
  did int(10) NOT NULL auto_increment,
  dfid int(10) NOT NULL default '0',
  dtime int(10) NOT NULL default '0',
  dip varchar(55) NOT NULL default '0',
  dmid mediumint(8) NOT NULL default '0',
  dsize bigint NOT NULL default '0',
  dua varchar(255) default NULL,
  dbrowsers varchar(25) NOT NULL default '',
  dos varchar(25) NOT NULL default '',
  PRIMARY KEY  (did),
  KEY dfid (dfid,dsize),
  KEY dtime(dtime),
  KEY dmid (dmid)
);";

$TABLE[] = "CREATE TABLE bitracker_filebackup (
  b_id int(10) NOT NULL auto_increment,
  b_fileid int(10) NOT NULL default '0',
  b_filetitle varchar(255) NOT NULL default '0',
  b_filedesc text,
  b_hidden tinyint(1) NOT NULL default '0',
  b_backup int(10) NOT NULL default '0',
  b_updated int(10) NOT NULL default '0',
  b_records TEXT NULL DEFAULT NULL,
  b_version VARCHAR( 32 ) NULL DEFAULT NULL,
  b_changelog TEXT NULL DEFAULT NULL,
  PRIMARY KEY  (b_id),
  KEY b_fileid (b_fileid)
);";

$TABLE[] = "CREATE TABLE bitracker_files (
  file_id int(10) NOT NULL auto_increment,
  file_name varchar(255) NOT NULL default '0',
  file_cat mediumint(8) NOT NULL default '0',
  file_open tinyint(1) NOT NULL default '0',
  file_broken tinyint(1) NOT NULL default '0',
  file_broken_reason text,
  file_broken_info varchar(255) default NULL,
  file_views int(10) NOT NULL default '0',
  file_bitracker int(10) NOT NULL default '0',
  file_submitted int(10) NOT NULL default '0',
  file_updated int(10) NOT NULL default '0',
  file_desc text,
  file_size BIGINT NOT NULL default '0',
  file_submitter mediumint(8) NOT NULL default '0',
  file_approver mediumint(8) NOT NULL default '0',
  file_approvedon int(10) NOT NULL default '0',
  file_topicid int(10) NOT NULL default '0',
  file_pendcomments smallint(4) NOT NULL default '0',
  file_ipaddress varchar(46) NOT NULL default '0',
  file_votes text,
  file_rating smallint(5) NOT NULL default '0',
  file_new tinyint(1) NOT NULL default '0',
  file_name_furl varchar( 255 ) NULL default NULL,
  file_topicseoname varchar( 255 ) NULL default NULL,
  file_post_key varchar( 32 ) NULL default NULL,
  file_cost FLOAT NOT NULL DEFAULT '0.00',
  file_nexus TEXT NULL DEFAULT NULL,
  file_version VARCHAR( 32 ) NULL DEFAULT NULL,
  file_changelog TEXT NULL DEFAULT NULL,
  file_renewal_term INT(5) NOT NULL DEFAULT 0,
  file_renewal_units CHAR(1) NULL DEFAULT NULL,
  file_renewal_price FLOAT NOT NULL DEFAULT '0.00',
  file_featured TINYINT( 1 ) NOT NULL DEFAULT 0,
  file_pinned TINYINT( 1 ) NOT NULL DEFAULT '0',
  file_comments INT NOT NULL DEFAULT '0',
  PRIMARY KEY  (file_id),
  KEY file_views (file_views),
  KEY file_bitracker (file_bitracker),
  KEY file_cat ( file_cat , file_updated ),
  KEY file_submitter (file_submitter, file_open, file_updated),
  KEY file_broken (file_broken),
  KEY file_open ( file_open , file_cat , file_submitted ),
  KEY file_rating (file_rating),
  KEY file_post_key ( file_post_key ),
  KEY file_featured ( file_featured )
);";

$TABLE[] = "CREATE TABLE bitracker_filestorage (
  storage_id INT( 10 ) NOT NULL AUTO_INCREMENT,
  storage_file LONGBLOB NULL DEFAULT NULL,
  storage_ss LONGBLOB NULL DEFAULT NULL,
  storage_thumb LONGBLOB NULL DEFAULT NULL,
  PRIMARY KEY (storage_id)
);";

$TABLE[] = "CREATE TABLE bitracker_fileviews (
  view_id mediumint(10) NOT NULL auto_increment,
  view_fid int(10) NOT NULL default '0',
  PRIMARY KEY  (view_id)
);";

$TABLE[] = "CREATE TABLE bitracker_files_records (
  record_id int(11) NOT NULL auto_increment,
  record_post_key varchar(32) default NULL,
  record_file_id int(11) NOT NULL default '0',
  record_type varchar(32) NOT NULL default 'file',
  record_location text null,
  record_db_id int(11) NOT NULL default '0',
  record_thumb text null,
  record_storagetype varchar(24) NOT NULL default 'disk',
  record_realname varchar(255) default NULL,
  record_link_type varchar(255) default NULL,
  record_mime smallint(6) NOT NULL default '0',
  record_size bigint NOT NULL default '0',
  record_backup tinyint(1) NOT NULL default '0',
  record_default TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (record_id),
  KEY record_post_key (record_post_key),
  KEY record_file_id (record_file_id),
  KEY record_db_id (record_db_id),
  KEY record_realname ( record_realname ),
  KEY record_type ( record_type , record_file_id , record_backup )
);";

$TABLE[] = "CREATE TABLE bitracker_temp_records (
  record_id int(11) NOT NULL auto_increment,
  record_post_key varchar(32) default NULL,
  record_file_id int(11) NOT NULL default '0',
  record_type varchar(32) NOT NULL default 'file',
  record_location text null,
  record_realname varchar(255) default NULL,
  record_mime smallint(6) NOT NULL default '0',
  record_size int(11) NOT NULL default '0',
  record_added int(10) NOT NULL default '0',
  record_default TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (record_id),
  KEY record_post_key (record_post_key),
  KEY record_file_id (record_file_id),
  KEY record_added ( record_added )
);";

$TABLE[] = "CREATE TABLE bitracker_mime (
  mime_id int(10) NOT NULL auto_increment,
  mime_extension varchar(18) NOT NULL default '',
  mime_mimetype varchar(255) NOT NULL default '',
  mime_file text,
  mime_nfo text,
  mime_screenshot text,
  mime_inline text,
  mime_img text,
  PRIMARY KEY  (mime_id)
);";

$TABLE[] = "CREATE TABLE bitracker_mimemask (
  mime_maskid int(10) NOT NULL auto_increment,
  mime_masktitle varchar(255) NOT NULL default '0',
  PRIMARY KEY  (mime_maskid)
);";

$TABLE[] = "CREATE TABLE bitracker_mods (
  modid mediumint(8) NOT NULL auto_increment,
  modtype tinyint(1) NOT NULL default '0',
  modgmid varchar(255) NOT NULL default '0',
  modcanedit tinyint(1) NOT NULL default '0',
  modcandel tinyint(1) NOT NULL default '0',
  modcanapp tinyint(1) NOT NULL default '0',
  modcanbrok tinyint(1) NOT NULL default '0',
  modcancomments tinyint(1) NOT NULL default '0',
  modcats mediumtext,
  modchangeauthor TINYINT( 1 ) NOT NULL DEFAULT '0',
  modusefeature TINYINT( 1 ) NOT NULL DEFAULT '0',
  modcanpin TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (modid)
);";

$TABLE[] = "CREATE TABLE bitracker_sessions (
  dsess_id varchar(32) NOT NULL,
  dsess_mid int(10) NOT NULL default '0',
  dsess_ip varchar(46) default NULL,
  dsess_file int(10) NOT NULL default '0',
  dsess_start int(10) NOT NULL default '0',
  dsess_end int(10) NOT NULL default '0',
  PRIMARY KEY  (dsess_id),
  KEY dsess_mid (dsess_mid,dsess_ip),
  KEY (dsess_start)
);";

$TABLE[] = "CREATE TABLE bitracker_urls (
  url_id VARCHAR( 32 ) NOT NULL ,
  url_file INT NOT NULL DEFAULT '0',
  url_ip VARCHAR( 46 ) NULL DEFAULT NULL ,
  url_created int(10) NOT NULL DEFAULT '0',
  url_expires int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY ( url_id ),
  KEY ( url_file ),
  KEY ( url_expires )
);";

$TABLE[] = "CREATE TABLE bitracker_announce_error_log (
  log_id int(11) NOT NULL AUTO_INCREMENT,
  request_ip varchar(64) NOT NULL,
  request_client varchar(255) NOT NULL,
  request_infohash varchar(40) NOT NULL,
  request_perm_key varchar(32) NOT NULL,
  request_time varchar(10) NOT NULL,
  error_code varchar(4) NOT NULL,
  error_string varchar(255) NOT NULL,
  PRIMARY KEY ( log_id ),
  KEY ( log_id )
);";

$TABLE[] = "CREATE TABLE bitracker_torrent_data (
  torrent_id int(11) NOT NULL,
  torrent_post_key varchar(32) NOT NULL DEFAULT '',
  torrent_name varchar(255) NOT NULL DEFAULT '0',
  torrent_infohash varchar(40) NOT NULL DEFAULT '',
  torrent_filelist mediumtext,
  torrent_filesize bigint(20) NOT NULL DEFAULT '0',
  torrent_file_count tinyint(2) NOT NULL DEFAULT '0',
  torrent_comment mediumtext,
  torrent_seeders int(10) NOT NULL DEFAULT '0',
  torrent_leechers int(10) NOT NULL DEFAULT '0',
  torrent_times_comp int(10) NOT NULL DEFAULT '0',
  torrent_announce text,
  torrent_announce_list mediumtext,
  torrent_private_flag tinyint(1) NOT NULL DEFAULT '0',
  torrent_encoding tinytext,
  torrent_created_date int(10) NOT NULL DEFAULT '0',
  torrent_created_by text,
  piece_length int(20) NOT NULL DEFAULT '0',
  pieces int(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`torrent_id`,`torrent_infohash`,`torrent_post_key`),
  KEY ( torrent_infohash ),
  KEY ( torrent_post_key )
);";

$TABLE[] = "CREATE TABLE bitracker_torrent_peers (
  id int(10) NOT NULL AUTO_INCREMENT,
  torrent int(10) NOT NULL DEFAULT '0',
  peer_id varchar(40) NOT NULL DEFAULT '',
  compact enum('yes','no') NOT NULL DEFAULT 'no',
  peer_ip varchar(64) NOT NULL DEFAULT '',
  peer_ipv6 varbinary(16) DEFAULT NULL,
  peer_port smallint(5) NOT NULL DEFAULT '0',
  uploaded bigint(20) NOT NULL DEFAULT '0',
  downloaded bigint(20) NOT NULL DEFAULT '0',
  to_go bigint(20) NOT NULL DEFAULT '0',
  seeder enum('yes','no') NOT NULL DEFAULT 'no',
  started int(10) unsigned NOT NULL,
  last_action int(10) unsigned NOT NULL,
  connectable enum('yes','no') NOT NULL DEFAULT 'yes',
  client varchar(60) NOT NULL DEFAULT '',
  mem_id varchar(32) NOT NULL DEFAULT '',
  perm_key varchar(32) NOT NULL DEFAULT '',
  session_key varchar(8) DEFAULT NULL,
  PRIMARY KEY ( id ),
  KEY ( mem_id )
);";

$TABLE[] = "ALTER TABLE members ADD perm_key VARCHAR( 32 ) NULL;";
$TABLE[] = "ALTER TABLE members ADD download_total MEDIUMINT( 12 ) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE members ADD upload_total MEDIUMINT( 12 ) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD bit_restrictions TEXT NULL;";
$TABLE[] = "ALTER TABLE groups ADD bit_bypass_paid TINYINT( 1 ) NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD bit_add_paid TINYINT( 1 ) NOT NULL;";
$TABLE[] = "ALTER TABLE groups ADD bit_view_bitracker TINYINT( 1 ) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD bit_report_files TINYINT( 1 ) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD bit_bypass_revision TINYINT( 1 ) NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD bit_throttling INT NOT NULL DEFAULT '0';";
$TABLE[] = "ALTER TABLE groups ADD bit_wait_period INT NOT NULL DEFAULT '0';";
