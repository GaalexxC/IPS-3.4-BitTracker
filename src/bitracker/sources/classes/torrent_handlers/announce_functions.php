<?php


// We'll protect the namespace of our code
// using a class

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class announce_functions
{

	protected function client_error($msg) {
		   return benc_resp_raw("d".benc_str("failure reason").benc_str($msg)."e");
		}

	protected function benc_str($s) {
			return strlen($s) . ":$s";
		}

	protected function benc_int($i) {
			return "i" . $i . "e";
		}

	protected function benc_resp_raw($x) {
              header("Content-type: text/plain");
              header("Pragma: no-cache");
			  if (extension_loaded('zlib') && !ini_get('zlib.output_compression') && $_SERVER["HTTP_ACCEPT_ENCODING"] == "gzip") {
				header("Content-Encoding: gzip");
				echo gzencode($x, 9, FORCE_GZIP);
			} else
				print($x);

			exit();
		}

	protected function portblacklisted($port) {
			// direct connect
			if ($port >= 411 && $port <= 413) return true;

			// kazaa
			if ($port == 1214) return true;

			// gnutella
			if ($port >= 6346 && $port <= 6347) return true;

			// emule
			if ($port == 4662) return true;

			// winmx
			if ($port == 6699) return true;

			return false;
		}



} // End of class declaration.


?>