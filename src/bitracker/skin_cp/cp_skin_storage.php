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
 
class cp_skin_storage
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

public function storageSplash( ) {

$IPBHTML = "";
//--starthtml--//

$onlineStatus = $this->settings['bit_online'] ? 'accept' : 'delete';


$IPBHTML .= <<<HTML

<div class='acp-box'>
	<h3>Storage Options</h3>
	<table class='ipsTable'>

		<tr>
			<td class='center'>
				In Progress
			</td>
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