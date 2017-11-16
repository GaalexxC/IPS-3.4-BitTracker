<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.download Manager Custom Fields Library
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

class bit_customFields
{
	/**
	 * File id
	 *
	 * @access	public
	 * @var		integer
	 */	
	public $file_id			= 0;

	/**
	 * Category id
	 *
	 * @access	public
	 * @var		integer
	 */	
	public $cat_id			= 0;

	/**
	 * Has been initialized
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $init			= false;

	/**
	 * In fields
	 *
	 * @access	public
	 * @var		array
	 */	
	public $in_fields		= array();

	/**
	 * Out fields
	 *
	 * @access	public
	 * @var		array
	 */	
	public $out_fields		= array();

	/**
	 * Chosen fields
	 *
	 * @access	public
	 * @var		array
	 */	
	public $out_chosen		= array();

	/**
	 * Temporary fields
	 *
	 * @access	public
	 * @var		array
	 */	
	public $tmp_fields		= array();

	/**
	 * Array of fields to use
	 *
	 * @access	public
	 * @var		array
	 */	
	public $use_fields		= array();

	/**
	 * Cached data
	 *
	 * @access	public
	 * @var		array
	 */	
	public $cache_data		= array();

	/**
	 * File data
	 *
	 * @access	public
	 * @var		array
	 */	
	public $file_data		= array();

	/**
	 * Field names
	 *
	 * @access	public
	 * @var		array
	 */	
	public $field_names		= array();

	/**
	 * Field descriptions
	 *
	 * @access	public
	 * @var		array
	 */	
	public $field_desc		= array();

	/**
	 * Kill HTML
	 *
	 * @access	public
	 * @var		boolean
	 */	
	public $kill_html		= false;

	/**
	 * Fields with errors
	 *
	 * @access	public
	 * @var		array
	 */	
	public $error_fields	= array( 'toobig' => array(), 'empty' => array(), 'invalid' => array() );

	/**
	 * Custom fields kernel object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cfields;	
	
	/**
	 * Current type
	 *
	 * @access	protected
	 * @var		string
	 */	
	protected $type;	
	
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
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Initialization
	 *
	 * @access	public
	 * @param	string	[view|edit]
	 * @return	@e void
	 * 
	 * @todo	[Future] Check the caching data query and update it to load ONLY the needed fields with an IN clause. On board with lots of fields might become an issue.
	 */
	public function init_data( $type='view' )
	{
		$_search	= false;
		
		if( $type == 'search' )
		{
			$_search	= true;
			$type		= 'edit';
		}

		$this->type	= $type;

		if ( ! $this->init )
		{
			if( $this->cat_id )
			{
				$this->use_fields = explode( ",", $this->cat_id );
				
				if( !is_array($this->use_fields) OR !count($this->use_fields) )
				{
					$this->use_fields = array();
				}
			}
			
			//-----------------------------------------
			// Cache data...
			//-----------------------------------------
			
			if ( !is_array( $this->cache_data ) )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_cfields', 'order' => 'cf_position' ) );
				$this->DB->execute();
				
				while ( $r = $this->DB->fetch() )
				{
					if( count($this->use_fields) )
					{
						if( !in_array( $r['cf_id'], $this->use_fields ) )
						{
							continue;
						}
					}
					
					if( $_search AND $r['cf_type'] == 'drop' )
					{
						$r['cf_content']	= '0=' . $this->lang->words['cfield_drop_selectone'] . '|' . $r['cf_content'];
					}
					
					$this->cache_data[ $r['cf_id'] ] = $r;
				}
			}
			else
			{
				$cache_data			= $this->cache_data;
				$this->cache_data	= array();
				
				if ( count($cache_data) )
				{
					foreach( $cache_data as $k => $v )
					{
						if( count($this->use_fields) )
						{
							if( !in_array( $k, $this->use_fields ) )
							{
								continue;
							}
						}
						
						if( $_search AND $v['cf_type'] == 'drop' )
						{
							$v['cf_content']	= '0=' . $this->lang->words['cfield_drop_selectone'] . '|' . $v['cf_content'];
						}
						
						$this->cache_data[ $k ] = $v;
					}
				}
			}

			//-----------------------------------------
			// Get names...
			//-----------------------------------------
			
			if ( is_array($this->cache_data) and count($this->cache_data) )
			{
				foreach( $this->cache_data as $id => $data )
				{
					$this->field_names[ $id ] = $data['cf_title'];
					$this->field_desc[ $id ]  = $data['cf_desc'];
				}
			}
		}
		
		$this->out_fields = array();
		$this->tmp_fields = array();
		$this->out_chosen = array();
		
		//-----------------------------------------
		// Get content...
		//-----------------------------------------
		
		if ( ! count( $this->file_data ) and $this->file_id )
		{
			$this->file_data = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_ccontent', 'where' => 'file_id=' . intval($this->file_id) ) );
		}
		
		if ( !empty( $this->file_data ) )
		{
			$this->file_id = $this->file_data['id'];
			
			foreach( $this->file_data as $k => $v )
			{
				//-----------------------------------------
				// The data is RE-cleaned in the cfields obj
				// @see http://bugs.---.com/tracker/issue-18242-minor-symbol-bug/
				// Input could be an array if we're here due to a submit form error
				//-----------------------------------------
				
				if( !is_array($v) )
				{
					$this->file_data[ $k ]	= html_entity_decode( $v, ENT_QUOTES );
				}
			}
		}

		//-----------------------------------------
		// Format data
		//-----------------------------------------
		
		if ( is_array($this->cache_data) and count( $this->cache_data ) )
		{
			foreach( $this->cache_data as $k => $v )
			{
				/* Parse into in fields */
				$this->in_fields[ $k ] = $this->file_data[ 'field_' . $k ];
				
				/* Field Info */
				$this->cache_data[ $k ]['id']			= $v['cf_id'];
				$this->cache_data[ $k ]['type']			= $v['cf_type'] == 'text' ? 'input' : ( $v['cf_type'] == 'area' ? 'textarea' : $v['cf_type'] );
				$this->cache_data[ $k ]['data']			= $v['cf_content'];
				$this->cache_data[ $k ]['value']		= $this->in_fields[ $k ];
				$this->cache_data[ $k ]['attributes']	= array();
				
				/* Field Restrictions */
				$this->cache_data[ $k ]['restrictions'] = array(
						
																'max_size' => $v['cf_max_input'],
																'min_size' => 0,
																'not_null' => $v['cf_not_null'],
																'format'   => $v['cf_input_format'],
															);
			}
		}
		//print_r($this->cache_data);exit;
		/* Kernel profile field class */
		$classToLoad		= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCustomFields.php', 'classCustomFields' );
		$this->cfields_obj	= new $classToLoad( $this->cache_data, $type );
		$this->cfields		= $this->cfields_obj->cfields;

		$this->init = 1;
	}
	
	/**
	 * Parses fields for saving into the database, results are stored in $this->out_fields
	 *
	 * @access	public
	 * @param	array  	$field_data		Array that contains the fields to parse, usually $this->request
	 * @return	@e void
	 */
	public function parseToSave( $field_data )
	{
		/* Parse the fields */
		$save_fields = $this->cfields_obj->getFieldsToSave( $field_data );

		/* ERROR CHECKING HERE */
		$this->error_fields = $save_fields['errors'];

		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if( ! $this->_checkFieldAuth( $field ) )
			{
				continue;		
			}
			
			$this->out_fields[ 'field_' . $id ] = $save_fields['save_array']['field_' . $id ];
		}
	}

	/**
	 * Parses fields for viewing, results are stored in $this->out_fields
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function parseToView()
	{
		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if( ! $this->_checkFieldAuth( $field ) )
			{
				continue;		
			}

			if( $field->getValue() )
			{
				$this->out_fields[ $id ] = str_replace( '{value}', $field->getValue(), $this->cache_data[ $id ]['cf_format'] ? $this->cache_data[ $id ]['cf_format'] : '{value}' );
			}
		}
	}
	
	/**
	 * Parses fields for editing, results are stored in $this->out_fields
	 *
	 * @access	public
	 * @param	bool	Only show fields allowed in search
	 * @return	void
	 */
	public function parseToEdit( $search=false )
	{
		/* Loop through our custom fields */
		foreach( $this->cfields as $id => $field )
		{
			/* Can we view this field? */
			if( ! $this->_checkFieldAuth( $field ) )
			{
				continue;		
			}
			
			/* Are we only showing search fields? */
			if( $search AND !$this->cache_data[ $id ]['cf_search'] )
			{
				continue;
			}

			$this->out_fields[ $id ] = $field->getValue();
		}		
	}
	
	/**
	 * Checks to see if the field is viewable by the current user
	 *
	 * @access protected
	 * @param  array  $field  Array of field data
	 * @return bool
	 */
	protected function _checkFieldAuth( $field )
	{
		return true;	
	}

}
