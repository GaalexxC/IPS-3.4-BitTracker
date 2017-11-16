<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Operating system definitions
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

$OS['aix'] = array(
						'b_icon'  => "aix",
						'b_title' => "AIX",
						'b_regex' => array( "aix" => "" )
					  );
$OS['amiga'] = array(
						'b_icon'  => "amiga",
						'b_title' => "AmigaOS",
						'b_regex' => array( "Amiga[ ]?OS[ /]([0-9.]{1,10})" => "\\\\1","amiga" => "" )
					  );
$OS['atheos'] = array(
						'b_icon'  => "atheos",
						'b_title' => "AtheOS",
						'b_regex' => array( "atheos" => "" )
					  );
$OS['beos'] = array(
						'b_icon'  => "be",
						'b_title' => "BeOS",
						'b_regex' => array( "beos[ a-z]*([0-9.]{1,10})" => "\\\\1","beos" => "" )
					  );
$OS['darwin'] = array(
						'b_icon'  => "darwin",
						'b_title' => "Darwin",
						'b_regex' => array( "Darwin[ ]?([0-9.]{1,10})" => "\\\\1","Darwin" => "" )
					  );
$OS['digital'] = array(
						'b_icon'  => "digital",
						'b_title' => "Digital",
						'b_regex' => array( "OSF[0-9][ ]?V(4[0-9.]{1,10})" => "\\\\1" )
					  );
$OS['freebsd'] = array(
						'b_icon'  => "freebsd",
						'b_title' => "FreeBSD",
						'b_regex' => array( "free[ \-]?bsd[ /]([a-z0-9.]{1,10})" => "\\\\1","free[ \-]?bsd" => "" )
					  );
$OS['hpux'] = array(
						'b_icon'  => "hp",
						'b_title' => "HPUX",
						'b_regex' => array( "hp[ \-]?ux[ /]([a-z0-9.]{1,10})" => "\\\\1" )
					  );
$OS['irix'] = array(
						'b_icon'  => "irix",
						'b_title' => "IRIX",
						'b_regex' => array( "irix[0-9]*[ /]([0-9.]{1,10})" => "\\\\1","irix" => "" )
					  );
$OS['linux'] = array(
						'b_icon'  => "linux",
						'b_title' => "Linux",
						'b_regex' => array( "mdk for ([0-9.]{1,10})" => "MDK \\\\1","linux[ /\-]([a-z0-9.]{1,10})" => "\\\\1","linux" => "" )
					  );
$OS['macosx'] = array(
						'b_icon'  => "macosx",
						'b_title' => "MacOS X",
						'b_regex' => array( "Mac[ ]?OS[ ]?X" => "" )
					  );
$OS['macppc'] = array(
						'b_icon'  => "macppc",
						'b_title' => "MacOS PPC",
						'b_regex' => array( "Mac(_Power|intosh.+P)PC" => "" )
					  );
$OS['netbsd'] = array(
						'b_icon'  => "netbsd",
						'b_title' => "NetBSD",
						'b_regex' => array( "net[ \-]?bsd[ /]([a-z0-9.]{1,10})" => "\\\\1","net[ \-]?bsd" => "" )
					  );
$OS['os2'] = array(
						'b_icon'  => "os2",
						'b_title' => "OS/2 Warp",
						'b_regex' => array( "warp[ /]?([0-9.]{1,10})" => "\\\\1","os[ /]?2" => "" )
					  );
$OS['openbsd'] = array(
						'b_icon'  => "openbsd",
						'b_title' => "OpenBSD",
						'b_regex' => array( "open[ \-]?bsd[ /]([a-z0-9.]{1,10})" => "\\\\1","open[ \-]?bsd" => "" )
					  );
$OS['openvms'] = array(
						'b_icon'  => "openvms",
						'b_title' => "OpenVMS",
						'b_regex' => array( "Open[ \-]?VMS[ /]([a-z0-9.]{1,10})" => "\\\\1","Open[ \-]?VMS" => "" )
					  );
$OS['palm'] = array(
						'b_icon'  => "palm",
						'b_title' => "PalmOS",
						'b_regex' => array( "Palm[ \-]?(Source|OS)[ /]?([0-9.]{1,10})" => "\\\\2","Palm[ \-]?(Source|OS)" => "" )
					  );
$OS['photon'] = array(
						'b_icon'  => "qnx",
						'b_title' => "QNX Photon",
						'b_regex' => array( "photon" => "" )
					  );
$OS['risc'] = array(
						'b_icon'  => "risc",
						'b_title' => "RiscOS",
						'b_regex' => array( "risc[ \-]?os[ /]?([0-9.]{1,10})" => "\\\\1","risc[ \-]?os" => "" )
					  );
$OS['sun'] = array(
						'b_icon'  => "sun",
						'b_title' => "SunOS",
						'b_regex' => array( "sun[ \-]?os[ /]?([0-9.]{1,10})" => "\\\\1","sun[ \-]?os" => "" )
					  );
$OS['symbian'] = array(
						'b_icon'  => "symbian",
						'b_title' => "Symbian OS",
						'b_regex' => array( "Symbian" => "" )
					  );
$OS['tru64'] = array(
						'b_icon'  => "tru64",
						'b_title' => "Tru64",
						'b_regex' => array( "OSF[0-9][ ]?V(5[0-9.]{1,10})" => "\\\\1" )
					  );
$OS['unixware'] = array(
						'b_icon'  => "sco",
						'b_title' => "UnixWare",
						'b_regex' => array( "unixware[ /]?([0-9.]{1,10})" => "\\\\1","unixware" => "" )
					  );
$OS['windows2003'] = array(
						'b_icon'  => "windowsxp",
						'b_title' => "Windows 2003",
						'b_regex' => array( "wi(n|ndows)[ \-]?(2003|nt[ /]?5\.2)" => "" )
					  );
$OS['windows2k'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows 2000",
						'b_regex' => array( "wi(n|ndows)[ \-]?(2000|nt[ /]?5\.0)" => "" )
					  );
$OS['windows95'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows 95",
						'b_regex' => array( "wi(n|ndows)[ \-]?95" => "" )
					  );
$OS['windowsce'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows CE",
						'b_regex' => array( "wi(n|ndows)[ \-]?ce" => "" )
					  );
$OS['windowsme'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows ME",
						'b_regex' => array( "win 9x 4\.90" => "","wi(n|ndows)[ \-]?me" => "" )
					  );
$OS['windowsxp'] = array(
						'b_icon'  => "windowsxp",
						'b_title' => "Windows XP",
						'b_regex' => array( "Windows XP" => "","wi(n|ndows)[ \-]?nt[ /]?5\.1" => "" )
					  );
$OS['windows7'] = array(
						'b_icon'  => "windowsxp",
						'b_title' => "Windows 7",
						'b_regex' => array( "Windows 7" => "","wi(n|ndows)[ \-]?nt[ /]?6\.1" => "" )
					  );
$OS['bsd'] = array(
						'b_icon'  => "bsd",
						'b_title' => "BSD",
						'b_regex' => array( "bsd" => "" )
					  );
$OS['mac'] = array(
						'b_icon'  => "mac",
						'b_title' => "MacOS",
						'b_regex' => array( "mac[^hk]" => "" )
					  );
$OS['windowsnt'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows NT",
						'b_regex' => array( "wi(n|ndows)[ \-]?nt[ /]?([0-4][0-9.]{1,10})" => "\\\\2","wi(n|ndows)[ \-]?nt" => "" )
					  );
$OS['windows98'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows 98",
						'b_regex' => array( "wi(n|ndows)[ \-]?98" => "" )
					  );
$OS['windows'] = array(
						'b_icon'  => "windows",
						'b_title' => "Windows",
						'b_regex' => array( "wi(n|n32|ndows)" => "" )
					  );
$OS['other'] = array(
						'b_icon'  => "question",
						'b_title' => "other",
						'b_regex' => array( ".*" => "" )
					  );
