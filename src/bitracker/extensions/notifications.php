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

/**
 * Notification types
 */

ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_bitracker' ), 'bitracker' );

class bitracker_notifications
{
	public function getConfiguration()
	{
		/**
		 * Notification types - Needs to be a method so when require_once is used, $_NOTIFY isn't empty
		 */
		$_NOTIFY	= array(
							array( 'key' => 'updated_file', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_newfile' ),
							array( 'key' => 'new_file', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_newfile' ),
							array( 'key' => 'file_approved', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_fileapproved' ),
							array( 'key' => 'file_broken', 'default' => array( 'inline' ), 'disabled' => array(), 'show_callback' => TRUE, 'icon' => 'notify_diskwarn' ),
							array( 'key' => 'file_mybroken', 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'notify_diskwarn' ),
							array( 'key' => 'file_pending', 'default' => array( 'inline' ), 'disabled' => array(), 'show_callback' => TRUE, 'icon' => 'notify_diskwarn' ),
							);
		
		return $_NOTIFY;
	}
	
	public function file_broken()
	{
		ipsRegistry::getAppClass( 'bitracker' );
		$this->registry	= ipsRegistry::instance();
		
		$appcats	= '';

		if( $this->memberData['g_is_supmod'] )
		{
			$appcats 	= '*';
		}
		else
		{
			if( is_array( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] as $k => $v )
					{
						if( $v['modcanbrok'] )
						{
							$appcats = $v['modcats'];
						}
					}
				}
			}
			else if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['modcanbrok'] )
						{
							$appcats = $v['modcats'];
						}
					}
				}
			}
		}
		
		if( $appcats )
		{
			return true;
		}
		
		return false;
	}

	public function file_pending()
	{
		ipsRegistry::getAppClass( 'bitracker' );
		$this->registry	= ipsRegistry::instance();
		
		$appcats = '';
		
		if( $this->memberData['g_is_supmod'] )
		{
			$appcats = '*';
		}
		else
		{
			if( is_array( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->group_mods[ $this->memberData['member_group_id'] ] as $k => $v )
					{
						if( $v['modcanapp'] )
						{
							$appcats = $v['modcats'];
						}
					}
				}
			}
			else if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['modcanapp'] )
						{
							$appcats = $v['modcats'];
						}
					}
				}
			}
		}
		
		if( $appcats )
		{
			return true;
		}
		
		return false;
	}
}

