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


class usercpForms_bitracker extends public_core_usercp_manualResolver implements interface_usercp
{
	/**
	 * Tab name
	 * This can be left blank and the application title will
	 * be used
	 *
	 * @access	public
	 * @var		string
	 */
	public $tab_name						= '';
	
	/**
	 * Default area code
	 *
	 * @access	public
	 * @var		string
	 */
	public $defaultAreaCode					= 'overview';
	
	/**
	 * OK Message
	 * This is an optional message to return back to the framework
	 * to replace the standard 'Settings saved' message
	 *
	 * @access	public
	 * @var		string
	 */
	public $ok_message						= '';
	
	/**
	 * Hide 'save' button and form elements
	 * Useful if you have custom output that doesn't
	 * need to use it
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $hide_form_and_save_button		= true;
	
	/**
	 * If you wish to allow uploads, set a value for this
	 *
	 * @access	public
	 * @var		int
	 */
	public $uploadFormMax					= 0;

	/**
	 * Flag to indicate compatibility
	 * 
	 * @var		int
	 */
 	public $version	= 34;	
	
	/**
	 * Initiate this module
	 *
	 * @access	public
	 * @return	@e void
	 */

	public function init()
	{
		/* INIT */
		
		$this->request['st'] = intval( $this->request['st'] );	
		
		/* Load library */
				
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/ucplibrary.php', 'bitrackerSystemLibrary', 'bitracker' );
		$this->bitrackerSystemLibrary = new $classToLoad( $this->registry );		 
		 		 
		/* Check access */
		
		if( $this->bitrackerSystemLibrary->checkAccess() && $this->request['tab'] == 'bitracker' )
		{
			$this->registry->output->showError( 'no_bitracker_permissions', 'RS_P016' );
		}				
	}
	
	/**
	 * Return links for this tab
	 * You may return an empty array or FALSE to not have
	 * any links show in the tab.
	 *
	 * The links must have 'area=xxxxx'. The rest of the URL
	 * is added automatically.
	 * 'area' can only be a-z A-Z 0-9 - _
	 *
	 * @access	public
	 * @return	array 		array of links
	 */

	public function getLinks()
	{
		$array = array();

		$array[] = array( 'url'		=> 'area=overview',
						  'title'	=> $this->lang->words['ucp_overview_bitracker'],
						  'area'	=> 'overview',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'overview' ? 1 : 0 );
		
		$array[] = array( 'url'		=> 'area=settings',
						  'title'	=> $this->lang->words['ucp_settings_bitracker'],
						  'area'	=> 'settings',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'settings' ? 1 : 0 );

		$array[] = array( 'url'		=> 'area=active',
						  'title'	=> $this->lang->words['ucp_active_bitracker'],
						  'area'	=> 'active',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'active' ? 1 : 0 );

		$array[] = array( 'url'		=> 'area=uploaded',
						  'title'	=> $this->lang->words['ucp_uploaded_bitracker'],
						  'area'	=> 'uploaded',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'uploaded' ? 1 : 0 );	

		$array[] = array( 'url'		=> 'area=downloaded',
						  'title'	=> $this->lang->words['ucp_downloaded_bitracker'],
						  'area'	=> 'downloaded',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'downloaded' ? 1 : 0 );
		
		$array[] = array( 'url'		=> 'area=reseed',
						  'title'	=> $this->lang->words['ucp_reseed_bitracker'],
						  'area'	=> 'reseed',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'reseed' ? 1 : 0 );

		$array[] = array( 'url'		=> 'area=stats',
						  'title'	=> $this->lang->words['ucp_stats_bitracker'],
						  'area'	=> 'stats',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'stats' ? 1 : 0 );	

		$array[] = array( 'url'		=> 'area=logs',
						  'title'	=> $this->lang->words['ucp_logs_bitracker'],
						  'area'	=> 'logs',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'logs' ? 1 : 0 );	

		$array[] = array( 'url'		=> 'area=notifications',
						  'title'	=> $this->lang->words['ucp_notifications_bitracker'],
						  'area'	=> 'notifications',
						  'active'	=> $this->request['tab'] == 'bitracker' && $this->request['area'] == 'notifications' ? 1 : 0 );		

						  				
		return $array;
	}
	
	
	/**
	 * Run custom event
	 *
	 * If you pass a 'do' in the URL / post form that is not either:
	 * save / save_form or show / show_form then this function is loaded
	 * instead. You can return a HTML chunk to be used in the UserCP (the
	 * tabs and footer are auto loaded) or redirect to a link.
	 *
	 * If you are returning HTML, you can use $this->hide_form_and_save_button = 1;
	 * to remove the form and save button that is automatically placed there.
	 *
	 * @access	public
	 * @param	string				Current 'area' variable (area=xxxx from the URL)
	 * @return	mixed				html or void
	 */
	public function runCustomEvent( $currentArea )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$html = '';
		
		if( count($this->registry->getClass('categories')->member_access['show']) == 0 )
		{
			if( count($this->registry->getClass('categories')->cat_lookup) == 0 )
			{
				$this->registry->output->showError( 'no_bitracker_cats_created', 10871, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'no_bitracker_permissions', 10872, null, null, 403 );
			}
		}
		else
		{
			if( count( $this->registry->getClass('categories')->member_access['add'] ) > 0 )
			{
				$this->canadd = true;
			}
			
			$this->canmod = $this->registry->getClass('bitFunctions')->isModerator();
		}
		
		if( count($this->registry->getClass('categories')->cat_cache[ 0 ]) == 0 )
		{
			$this->registry->output->showError( 'no_bitracker_categories', 10873, null, null, 403 );
		}


		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $html;
	}

	/**
	 * UserCP Form Show
	 *
	 * @access	public
	 * @param	string	Current area as defined by 'get_links'
	 * @param	array 	Errors
	 * @return	string	Processed HTML
	 */



	public function showForm( $current_area, $errors=array() )
	{		
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
	      
		switch( $current_area )
		{
			case 'overview':
				return $this->_showOverviewForm();
			break;
			
			case 'settings':
				return $this->_showSettingsForm();
			break;

			case 'active':
				return $this->_showActiveForm();
			break;

			case 'uploaded':
				return $this->_showUploadedForm();
			break;

			case 'downloaded':
				return $this->_showDownloadedForm();
			break;

			case 'reseed':
				return $this->_showReseedForm();
			break;

			case 'stats':
				return $this->_showStatsForm();
			break;

			case 'logs':
				return $this->_showLogsForm();
			break;

			case 'notifications':
				return $this->_showNotificationsForm();
			break;


			default:
				return $this->_showOverviewForm();	

		}
	}
	
	/**
	 * Show Linked Form
	 *
	 * @access	private
	 * @author	DawPi
	 * @return	string  		Processed HTML
	 */
	private function _showOverviewForm()
	{

		    return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPoverview( $page_links, $records );
	}


	private function _showSettingsForm()
	{

        //--------------------------------
        // Update the all important IP
        //--------------------------------

        $currIP = filter_var( trim($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
        $currHost = @gethostbyaddr($currIP);
	
		$records	= array( 
                          'ip_address'  => $this->memberData['ip_address'], 
                          'ip_address2' => $this->memberData['ip_address2'],
                          'ip_address3' => $this->memberData['ip_address3'],
                          'curr_ip'     => $currIP,
                          'host'        => $currHost

                       );

		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPsettings( $records );
	}


	private function _showActiveForm()
	{



		$orderby 	= $this->request['order'] && in_array( $this->request['order'], 
							array( 'file_submitted', 'file_updated', 'file_bitracker', 'file_rating', 'file_views', 'file_name' ) ) ?
							$this->request['order'] : 'file_name';

		$ordertype 	= $this->request['ascdesc'] && in_array( $this->request['ascdesc'],
							array( 'asc', 'desc' ) ) ? $this->request['ascdesc'] : 'asc';

		$st			= intval($this->request['st']) > 0 ? intval($this->request['st']) : 0;
		
		$records	= array();
							
		$this->request['ascdesc'] =  $ordertype == 'asc' ? 'desc' : 'asc' ;
		
		$cnt = $this->DB->buildAndFetch( array (	'select'	=> 'count(*) as num',
									  				'from'		=> array( 'bitracker_bitracker' => 'df' ),
									  				'where'		=> "df.dmid={$this->memberData['member_id']} AND f.file_id IS NOT NULL",
									  				'add_join'	=> array(
									  									array(
									  										'from'	=> array( 'bitracker_files' => 'f' ),
									  										'where'	=> 'f.file_id=df.dfid',
									  										'type'	=> 'left',
									  										)
									  									)
												)		);

		$page_links	= $this->registry->output->generatePagination( array(	'totalItems'		=> $cnt['num'],
																   			'itemsPerPage'		=> 20,
																   			'currentStartValue'	=> $st,
																   			'baseUrl'			=> "app=core&amp;module=usercp&amp;tab=bitracker&amp;area=mybitracker&amp;order={$orderby}&amp;ascdesc={$ordertype}",
																  )	  	 );		

		$this->DB->build( array ( 'select'	=> 'f.*',
						  				'from'		=> array( 'bitracker_bitracker' => 'd' ),
						  				'where'		=> "d.dmid={$this->memberData['member_id']} AND f.file_id IS NOT NULL",
						  				'order'		=> 'f.' . $orderby . " " . $ordertype,
						  				'limit'		=> array( $st, 20 ),
						  				'add_join'	=> array(
						  									array(
						  										'select'	=> 'd.*',
						  										'from'		=> array( 'bitracker_files' => 'f' ),
						  										'where'		=> 'd.dfid=f.file_id',
						  										'type'		=> 'left'
						  										)
						  									)
								)		);
		$files = $this->DB->execute();
		
		while( $file = $this->DB->fetch($files) )
		{
			$records[] = $file;
		}

	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPactive( $page_links, $records );
	}

    }

	private function _showUploadedForm()
	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPuploaded( $page_links, $records );
	}


	private function _showDownloadedForm()
	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPdownloaded( $page_links, $records );
	}


	private function _showReseedForm()
	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPreseed( $page_links, $records );
	}


	private function _showStatsForm()
	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPstats( $page_links, $records );
	}


	private function _showLogsForm()
	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPlogs( $page_links, $records );
	}


	private function _showNotificationsForm()
	{
		       	return $this->registry->getClass('output')->getTemplate('bitracker_usercp')->UCPnotifications( $page_links, $records );
	}


	/**
	 * UserCP Form Check
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	string	Current area as defined by 'get_links'
	 * @return	string	Processed HTML
	 */
	public function saveForm( $current_area )
	{
		//-----------------------------------------
		// Where to go, what to see?
		//-----------------------------------------
		
		return '';
	}
}