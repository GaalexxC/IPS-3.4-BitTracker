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
 
class cp_skin_server
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}

public function serverSplash( $data ) {

$webserver          = $_SERVER['SERVER_SOFTWARE'];

function get_server_memory_usage(){
 
	$free = shell_exec('free');
	$free = (string)trim($free);
	$free_arr = explode("\n", $free);
	$mem = explode(" ", $free_arr[1]);
	$mem = array_filter($mem);
	$mem = array_merge($mem);
	$memory_usage = $mem[2]/$mem[1]*100;
 
	return $memory_usage;
}

$memory             = number_format(get_server_memory_usage(),3);
    
function get_server_cpu_usage(){
 
	$load = sys_getloadavg();
	return $load[0];
 
}
    
$serverload = number_format(get_server_cpu_usage(),2);

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<HTML

<div class='section_title'>
	<h2>{$this->lang->words['d_server']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['d_serverweb']}</h3>
	<table class='ipsTable'>

		<tr>
			<td><strong class='title'>Domain Name</strong></td>
			<td>{$data['server']['server_name']}</td>
			<td><strong class='title'>IP Address</strong></td>
			<td>{$data['server']['server_ip']}</td>
		</tr>
		<tr>
			<td><strong class='title'>Host Name</strong></td>
			<td>{$data['server']['server_host']}</td>
			<td><strong class='title'>Web Server</strong></td>
			<td>{$data['server']['server_software']}</td>
		</tr>
		<tr>
			<td><strong class='title'>MySQL Version</strong></td>
			<td>{$data['server']['server_mysql']}</td>
			<td><strong class='title'>{$this->lang->words['ov_serveros']}</strong></td>
			<td>{$data['server']['server_serveros']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['ov_phpmaxuploadsize']}</strong></td>
			<td>{$data['server']['server_maxfile']}</td>
			<td><strong class='title'>PHP Version</strong></td>
			<td>{$data['server']['server_php']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['ov_phpmaxtimelimit']}</strong></td>
			<td>{$data['server']['server_maxtime']}</td>
			<td><strong class='title'>{$this->lang->words['ov_phpmaxpostsize']}</strong></td>
			<td>{$data['server']['server_maxpost']}</td>
		</tr>

$IPBHTML
	</table>
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['d_serverproc']}</h3>
	<table class='ipsTable'>

		<tr>
			<td><strong class='title'>{$this->lang->words['ov_servtime']}</strong></td>
			<td>{$data['server']['server_servertime']}</td>
			<td><strong class='title'>{$this->lang->words['ov_serverup']}</strong></td>
			<td>{$data['server']['server_serverup']}</td>
		</tr>
		<tr>
			<td><strong class='title'>Memory Usage</strong></td>
			<td>{$memory}%</td>
			<td><strong class='title'>Server Load</strong></td>
			<td>{$serverload}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['ov_servpath']}</strong></td>
			<td>{$data['server']['server_serverpat']}</td>

		</tr>
		<tr>
			<td><strong class='title'>--</strong></td>
			<td>{$data['server']['server_serveross']}</td>
		</tr>
		<tr>
			<td><strong class='title'>{$this->lang->words['ov_t']}</strong></td>
			<td>{$data['server']['server_c']}</td>
            <td></td>
            <td></td>
		</tr>

$IPBHTML
	</table>
</div>
<br />
HTML;


//--endhtml--//
return $IPBHTML;
}

}