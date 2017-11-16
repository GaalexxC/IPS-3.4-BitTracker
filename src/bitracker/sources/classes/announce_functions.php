<?php


// We'll protect the namespace of our code
// using a class

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

//class announce_functions
//{

function benc_str($s) {
	return strlen($s) . ":$s";
}

function hash_where($name, $hash) {
        $shhash = preg_replace('/ *$/s', "", $hash);
        return "($name = '" . addslashes($hash) . "' OR $name = '" . addslashes($shhash) . "' OR $name = '".addslashes(urldecode($hash))."')";
}

function unesc_magic($x) {
        return (get_magic_quotes_gpc()) ? stripslashes($x) : $x;
}

function is_email($email) {
        return preg_match("/^(([A-Za-z0-9]+_+)|([A-Za-z0-9]+\\-+)|([A-Za-z0-9]+\\.+)|([A-Za-z0-9]+\\++))*[A-Za-z0-9]+@((\\w+\\-+)|(\\w+\\.))*\\w{1,63}\\.[a-zA-Z]{2,6}$/",$email);
}

function client_error($msg)
{
	benc_resp(array("failure reason" => array(type => "string", value => $msg)));
	exit();
}
function benc_resp($d)
{
	benc_resp_raw(benc(array(type => "dictionary", value => $d)));
}
function benc($obj) {
	if (!is_array($obj) || !isset($obj["type"]) || !isset($obj["value"]))
		return;
	$c = $obj["value"];
	switch ($obj["type"]) {
		case "string":
			return benc_str($c);
		case "integer":
			return benc_int($c);
		case "list":
			return benc_list($c);
		case "dictionary":
			return benc_dict($c);
		default:
			return;
	}
}
function benc_dict($d) {
	$s = "d";
	$keys = array_keys($d);
	sort($keys);
	foreach ($keys as $k) {
		$v = $d[$k];
		$s .= benc_str($k);
		$s .= benc($v);
	}
	$s .= "e";
	return $s;
}

function hash_pad($hash) {
        return str_pad($hash, 20);
}

function benc_resp_raw($x) {
              header("Content-type: text/plain");
              header("Pragma: no-cache");

				echo($x);

			exit();
		}


//} // End of class declaration.


?>