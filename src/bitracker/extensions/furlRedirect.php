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

class furlRedirect_bitracker
{	
	/**
	 * Key type: Type of action (topic/forum)
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_type = '';
	
	/**
	 * Key ID
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_id = 0;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	registry
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry =  $registry;
		$this->DB       =  $registry->DB();
		$this->settings =& $registry->fetchSettings();
	}

	/**
	 * Set the key ID
	 * <code>furlRedirect_forums::setKey( 'topic', 12 );</code>
	 *
	 * @access	public
	 * @param	string	Type
	 * @param	mixed	Value
	 */
	public function setKey( $name, $value )
	{
		$this->_type = $name;
		$this->_id   = $value;
	}
	
	/**
	 * Set up the key by URI
	 *
	 * @access	public
	 * @param	string		URI (example: index.php?showtopic=5&view=getlastpost)
	 * @return	@e void
	 */
	public function setKeyByUri( $uri )
	{
		if( IN_ACP )
		{
			return FALSE;
		}
		
		$uri = str_replace( '&amp;', '&', $uri );

		if ( strstr( $uri, '?' ) )
		{
			list( $_chaff, $uri ) = explode( '?', $uri );
		}
		
		if( $uri == 'app=bitracker' )
		{
			$this->setKey( 'app', 'bitracker' );
			return TRUE;			
		}
		else
		{
			$_section	= '';

			foreach( explode( '&', $uri ) as $bits )
			{
				list( $k, $v ) = explode( '=', $bits );

				if ( $k )
				{
					if ( $k == 'showcat' )
					{
						$this->setKey( 'cat', intval( $v ) );
						return TRUE;
					}
					else if ( $k == 'showfile' )
					{
						$this->setKey( 'file', intval( $v ) );
						return TRUE;
					}
					else if( $k == 'section' )
					{
						$_section	= $v;
					}
					else if( $k == 'id' AND $_section == 'file' )
					{
						$this->setKey( 'file', intval( $v ) );
						return TRUE;
					}
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Return the SEO title
	 *
	 * @access	public
	 * @return	string		The SEO friendly name
	 */
	public function fetchSeoTitle()
	{
		switch ( $this->_type )
		{
			default:
				return FALSE;
			break;
			case 'app':
				return $this->_fetchSeoTitle_app();
			break;
			case 'cat':
				return $this->_fetchSeoTitle_cat();
			break;
			case 'file':
				return $this->_fetchSeoTitle_file();
			break;
		}
	}

	/**
	 * Return the base bit SEO title
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_app()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Try to figure out what is used in furlTemplates.php */
			$_SEOTEMPLATES = array();
			@include( IPSLib::getAppDir( 'bitracker' ) . '/extensions/furlTemplates.php' );/*noLibHook*/
			
			if( $_SEOTEMPLATES['app=bitracker']['out'][1] )
			{
				return $_SEOTEMPLATES['app=bitracker']['out'][1];
			}
			else
			{
				return 'tracker/';
			}
		}
	}
	
	/**
	 * Return the bit seo title for cat
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_cat()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Query the cat */
			ipsRegistry::getAppClass( 'bitracker' );
			$cat	= $this->registry->categories->cat_lookup[ $this->_id ];
	
			/* Make sure we have an image */
			if( $cat['cid'] )
			{
				return $cat['cname_furl'] ? $cat['cname_furl'] : IPSText::makeSeoTitle( $cat['cname'] );
			}
		}
	}
	
	/**
	 * Return the bit seo title for torrent file
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_file()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Query the file */
			$file	= $this->DB->buildAndFetch( array( 'select' => 'file_id, file_name, file_name_furl', 'from' => 'bitracker_files', 'where' => "file_id={$this->_id}" ) );
	
			/* Make sure we have an image */
			if( $file['file_id'] )
			{
				return $file['file_name_furl'] ? $file['file_name_furl'] : IPSText::makeSeoTitle( $file['file_name'] );
			}
		}
	}

	/**
	 * Return the bit seo title for torrent announce
	 *
	 * @access	public
	 * @return	string
	 */
	public function _fetchSeoTitle_ann()
	{
		if ( ipsRegistry::$request['request_method'] != 'post' )
		{
			/* Query the member */
			$ann	= $this->DB->buildAndFetch( array( 'select' => 'perm_key', 'from' => 'members', 'where' => "member_id={$this->_id}" ) );
	
			/* Make sure we have an image */
			if( $ann['member_id'] )
			{
				return $ann['perm_key'] ? $ann['perm_key'] : IPSText::makeSeoTitle( $ann['perm_key'] );
			}
		}
	}
}