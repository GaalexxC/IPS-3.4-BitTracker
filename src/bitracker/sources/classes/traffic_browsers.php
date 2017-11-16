<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Browser definitions
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

$BROWSERS['abrowse'] = array(
							  'b_icon'  => "abrowse",
							  'b_title' => "ABrowse",
							  'b_regex' => array( "abrowse[ /\-]([0-9.]{1,10})" => "\\\\1","^abrowse" => "" )
							);
$BROWSERS['amaya'] = array(
							  'b_icon'  => "amaya",
							  'b_title' => "Amaya",
							  'b_regex' => array( "amaya/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['aol'] = array(
							  'b_icon'  => "aol",
							  'b_title' => "AOL",
							  'b_regex' => array( "aol[ /\-]([0-9.]{1,10})" => "\\\\1","aol[ /\-]?browser" => "" )
							);
$BROWSERS['avantbrowser'] = array(
							  'b_icon'  => "avantbrowser",
							  'b_title' => "Avant Browser",
							  'b_regex' => array( "Avant[ ]?Browser" => "" )
							);
$BROWSERS['avantgo'] = array(
							  'b_icon'  => "avantgo",
							  'b_title' => "AvantGo",
							  'b_regex' => array( "AvantGo[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['aweb'] = array(
							  'b_icon'  => "aweb",
							  'b_title' => "Aweb",
							  'b_regex' => array( "Aweb[/ ]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['beonex'] = array(
							  'b_icon'  => "beonex",
							  'b_title' => "Beonex",
							  'b_regex' => array( "beonex/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['blazer'] = array(
							  'b_icon'  => "blazer",
							  'b_title' => "Blazer",
							  'b_regex' => array( "Blazer[/ ]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['camino'] = array(
							  'b_icon'  => "camino",
							  'b_title' => "Camino",
							  'b_regex' => array( "camino/([0-9.+]{1,10})" => "\\\\1" )
							);
$BROWSERS['chimera'] = array(
							  'b_icon'  => "chimera",
							  'b_title' => "Chimera",
							  'b_regex' => array( "chimera/([0-9.+]{1,10})" => "\\\\1" )
							);
$BROWSERS['chrome'] = array(
							  'b_icon'	=> 'chrome',
							  'b_title' => "Chrome",
							  'b_regex' => array( "Chrome/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['columbus'] = array(
							  'b_icon'  => "columbus",
							  'b_title' => "Columbus",
							  'b_regex' => array( "columbus[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['crazybrowser'] = array(
							  'b_icon'  => "crazybrowser",
							  'b_title' => "Crazy Browser",
							  'b_regex' => array( "Crazy Browser[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['curl'] = array(
							  'b_icon'  => "curl",
							  'b_title' => "Curl",
							  'b_regex' => array( "curl[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['deepnet'] = array(
							  'b_icon'  => "deepnet",
							  'b_title' => "Deepnet Explorer",
							  'b_regex' => array( " Deepnet Explorer[\);]" => "" )
							);
$BROWSERS['dillo'] = array(
							  'b_icon'  => "dillo",
							  'b_title' => "Dillo",
							  'b_regex' => array( "dillo/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['doris'] = array(
							  'b_icon'  => "doris",
							  'b_title' => "Doris",
							  'b_regex' => array( "Doris/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['Elinks'] = array(
							  'b_icon'  => "links",
							  'b_title' => "ELinks",
							  'b_regex' => array( "ELinks[ /][\(]*([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['Epiphany'] = array(
							  'b_icon'  => "epiphany",
							  'b_title' => "Epiphany",
							  'b_regex' => array( "Epiphany/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['firebird'] = array(
							  'b_icon'  => "firebird",
							  'b_title' => "Firebird",
							  'b_regex' => array( "Firebird/([0-9.+]{1,10})" => "\\\\1" )
							);
$BROWSERS['firefox'] = array(
							  'b_icon'  => "firefox",
							  'b_title' => "Firefox",
							  'b_regex' => array( "Firefox/([0-9.+]{1,10})" => "\\\\1" )
							);
$BROWSERS['galeon'] = array(
							  'b_icon'  => "galeon",
							  'b_title' => "Galeon",
							  'b_regex' => array( "galeon/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['ibrowse'] = array(
							  'b_icon'  => "ibrowse",
							  'b_title' => "IBrowse",
							  'b_regex' => array( "ibrowse[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['icab'] = array(
							  'b_icon'  => "icab",
							  'b_title' => "iCab",
							  'b_regex' => array( "icab/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['isilox'] = array(
							  'b_icon'  => "isilox",
							  'b_title' => "iSiloX",
							  'b_regex' => array( "iSilox/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['lotus'] = array(
							  'b_icon'  => "lotus",
							  'b_title' => "Lotus Notes",
							  'b_regex' => array( "Lotus[ \-]?Notes[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['k-meleon'] = array(
							  'b_icon'  => "k-meleon",
							  'b_title' => "K-Meleon",
							  'b_regex' => array( "K-Meleon[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['konqueror'] = array(
							  'b_icon'  => "konqueror",
							  'b_title' => "Konqueror",
							  'b_regex' => array( "konqueror/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['links'] = array(
							  'b_icon'  => "links",
							  'b_title' => "Links",
							  'b_regex' => array( "Links[ /]\(([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['lunascape'] = array(
							  'b_icon'  => "lunascape",
							  'b_title' => "Lunascape",
							  'b_regex' => array( "Lunascape[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['lynx'] = array(
							  'b_icon'  => "lynx",
							  'b_title' => "Lynx",
							  'b_regex' => array( "lynx/([0-9a-z.]{1,10})" => "\\\\1" )
							);
$BROWSERS['maxthon'] = array(
							  'b_icon'  => "maxthon",
							  'b_title' => "Maxthon",
							  'b_regex' => array( " Maxthon[\);]" => "" )
							);
$BROWSERS['mbrowser'] = array(
							  'b_icon'  => "mbrowser",
							  'b_title' => "mBrowser",
							  'b_regex' => array( "mBrowser[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['mosaic'] = array(
							  'b_icon'  => "mosaic",
							  'b_title' => "Mosaic",
							  'b_regex' => array( "mosaic[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['multibrowser'] = array(
							  'b_icon'  => "multibrowser",
							  'b_title' => "Multi-Browser",
							  'b_regex' => array( "Multi-Browser[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['myie2'] = array(
							  'b_icon'  => "myie2",
							  'b_title' => "MyIE2",
							  'b_regex' => array( " MyIE2[\);]" => "" )
							);
$BROWSERS['nautilus'] = array(
							  'b_icon'  => "nautilus",
							  'b_title' => "Nautilus",
							  'b_regex' => array( "(gnome[ \-]?vfs|nautilus)/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['netcaptor'] = array(
							  'b_icon'  => "netcaptor",
							  'b_title' => "Netcaptor",
							  'b_regex' => array( "netcaptor[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['netfront'] = array(
							  'b_icon'  => "netfront",
							  'b_title' => "NetFront",
							  'b_regex' => array( "NetFront[ /]([0-9.]{1,10})$" => "\\\\1" )
							);
$BROWSERS['netpositive'] = array(
							  'b_icon'  => "netpositive",
							  'b_title' => "NetPositive",
							  'b_regex' => array( "netpositive[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['omniweb'] = array(
							  'b_icon'  => "omniweb",
							  'b_title' => "OmniWeb",
							  'b_regex' => array( "omniweb/[ a-z]?([0-9.]{1,10})$" => "\\\\1" )
							);
$BROWSERS['opera'] = array(
							  'b_icon'  => "opera",
							  'b_title' => "Opera",
							  'b_regex' => array( "opera[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['oregano'] = array(
							  'b_icon'  => "oregano",
							  'b_title' => "Oregano",
							  'b_regex' => array( "Oregano[0-9]?[ /]([0-9.]{1,10})$" => "\\\\1" )
							);
$BROWSERS['plink'] = array(
							  'b_icon'  => "plink",
							  'b_title' => "PLink",
							  'b_regex' => array( "PLink[ /]([0-9a-z.]{1,10})" => "\\\\1" )
							);
$BROWSERS['phoenix'] = array(
							  'b_icon'  => "phoenix",
							  'b_title' => "Phoenix",
							  'b_regex' => array( "Phoenix/([0-9.+]{1,10})" => "\\\\1" )
							);
$BROWSERS['proxomitron'] = array(
							  'b_icon'  => "proxomitron",
							  'b_title' => "Proxomitron",
							  'b_regex' => array( "Space[ ]?Bison/[0-9.]{1,10}" => "" )
							);
$BROWSERS['safari'] = array(
							  'b_icon'  => "safari",
							  'b_title' => "Safari",
							  'b_regex' => array( "safari/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['shiira'] = array(
							  'b_icon'  => "shiira",
							  'b_title' => "Shiira",
							  'b_regex' => array( "Shiira/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['sleipnir'] = array(
							  'b_icon'  => "sleipnir",
							  'b_title' => "Sleipnir",
							  'b_regex' => array( "Sleipnir( Version)?[ /]([0-9.]{1,10})" => "\\\\2" )
							);
$BROWSERS['slimbrowser'] = array(
							  'b_icon'  => "slimbrowser",
							  'b_title' => "SlimBrowser",
							  'b_regex' => array( "Slimbrowser" => "" )
							);
$BROWSERS['staroffice'] = array(
							  'b_icon'  => "staroffice",
							  'b_title' => "StarOffice",
							  'b_regex' => array( "staroffice[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['voyager'] = array(
							  'b_icon'  => "voyager",
							  'b_title' => "Voyager",
							  'b_regex' => array( "voyager[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['w3m'] = array(
							  'b_icon'  => "w3m",
							  'b_title' => "w3m",
							  'b_regex' => array( "w3m/([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['webtv'] = array(
							  'b_icon'  => "webtv",
							  'b_title' => "Webtv",
							  'b_regex' => array( "webtv[ /]([0-9.]{1,10})" => "\\\\1","webtv" => "" )
							);
$BROWSERS['xiino'] = array(
							  'b_icon'  => "xiino",
							  'b_title' => "Xiino",
							  'b_regex' => array( "^Xiino[ /]([0-9a-z.]{1,10})" => "\\\\1" )
							);
$BROWSERS['explorer'] = array(
							  'b_icon'  => "explorer",
							  'b_title' => "Explorer",
							  'b_regex' => array( "\(compatible; MSIE[ /]([0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['netscape'] = array(
							  'b_icon'  => "netscape",
							  'b_title' => "Netscape",
							  'b_regex' => array( "netscape[0-9]?/([0-9.]{1,10})" => "\\\\1","^mozilla/([0-4]\.[0-9.]{1,10})" => "\\\\1" )
							);
$BROWSERS['mozilla'] = array(
							  'b_icon'  => "mozilla",
							  'b_title' => "Mozilla",
							  'b_regex' => array( "^mozilla/[5-9]\.[0-9.]{1,10}.+rv:([0-9a-z.+]{1,10})" => "\\\\1","^mozilla/([5-9]\.[0-9a-z.]{1,10})" => "\\\\1" )
							);
$BROWSERS['other'] = array(
							  'b_icon'  => "question",
							  'b_title' => "other",
							  'b_regex' => array( ".*" => "" )
							);