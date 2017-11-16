<?php
/**
 *  devCU Software Development
 *  devCU Btracker 1.0.0 Release
 *  Last Updated: $Date: 2012-06-30 09:01:45 -0500 (Sat, 30 June 2012) $
 *
 * @author 		PM
 * @copyright	(c) 2012 devCU Software Development
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

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 *
 * @class		admin_bitracker_information_server
 * @brief		biTracker Manager Tracker Overview
 */
class admin_bitracker_information_server extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';

	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_server' );
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=information&amp;section=server&amp;';
		$this->form_code_js	= $this->html->form_code_js	= 'module=information&section=server&';
		
		$data	= array( 'server' => array() );

		//-----------------------------------------
		// Get primary data
		//-----------------------------------------



		$data['server']['server_name']		= $_SERVER['SERVER_NAME'];
		$data['server']['server_ip']		= $_SERVER['SERVER_ADDR'];
		$data['server']['server_host']		= gethostname();
		$data['server']['server_software']	= $_SERVER['SERVER_SOFTWARE'];
		$data['server']['server_php']		= phpversion();
		$data['server']['server_mysql']		= mysql_get_client_info();

		$data['server']['server_serveros']	= PHP_OS;
        $data['server']['server_servertime']  = date("l, F d, Y h:i" ,time());
		$data['server']['server_serverup']	= exec("uptime", $system);
		$data['server']['server_serverpat']	= str_replace($_SERVER['SCRIPT_NAME'],'', $_SERVER['SCRIPT_FILENAME']);
		$data['server']['server_serveross']	= php_uname();
            
		$data['server']['server_maxfile']	= @ini_get('upload_max_filesize') ? @ini_get('upload_max_filesize') : $this->lang->words['cp_stat_unknown'];
		$data['server']['server_maxpost']	= @ini_get('post_max_size') ? @ini_get('post_max_size') : $this->lang->words['cp_stat_unknown'];
		$data['server']['server_maxtime']	= defined('ORIGINAL_TIME_LIMIT') ? ORIGINAL_TIME_LIMIT : ( @ini_get('max_execution_time') ? @ini_get('max_execution_time') : $this->lang->words['cp_stat_unknown'] );


		$this->registry->output->html .= $this->html->serverSplash( $data );
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
}