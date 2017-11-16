<?php


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class topicsLibrary
{
	/**
	 * Enable debug mode - useful when topics or comments are not posting to the forums correctly
	 * 
	 * @var	bool
	 */
 	protected $debugMode	= false;

	/**
	 * Posting library
	 *
	 * @var 	object
	 */
	protected $post;
	
	/**
	 * Forum data
	 *
	 * @var 	array
	 */
	protected $forum		= array();

	/**
	 * Current topic data
	 *
	 * @var 	array
	 */
	protected $topic		= array();
	
	/**
	 * Base URL
	 *
	 * @var 	string
	 */
	protected $base_url		= "";
	
	/**#@+
	 * Registry objects
	 *
	 * @var		object
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
	/**#@-*/
	
	/**
	 * Custom fields content for the post
	 *
	 * @var		string
	 */	
	protected $cfields;	
	
	/**
	 * Current type
	 *
	 * @var		string
	 */	
	protected $type;	
		
	/**
	 * Constructor
	 *
	 * @param	object		Registry object
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
	 * Sort out the topic
	 *
	 * @param	array 		File information
	 * @param	array 		Category information
	 * @param 	string		Type [new|edit]
	 * @param	boolean		Do not set mid to current member's id if type=new
	 * @return	@e boolean	Posted successfully
	 */	
	public function sortTopic( $file, $category, $type = 'new', $mid_override = 0 )
	{
		//---------------------------------------------------------
		// Some init
		//---------------------------------------------------------
		
		$this->base_url	= $this->settings['board_url'] . '/index.php?';
		$this->cfields	= '';
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_global' ), 'core' );

		//---------------------------------------------------------
		// Is file open?
		//---------------------------------------------------------

		if( !$file['file_open'] )
		{
			return false;
		}
		
		//---------------------------------------------------------
		// Custom fields added to topic?
		//---------------------------------------------------------

		if( $category['ccfields'] )
		{
			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/cfields.php', 'bit_customFields', 'bitracker' );
    		$fields				= new $classToLoad( $this->registry );
    		$fields->file_id	= $file['file_id'];
    		$fields->cat_id		= $category['ccfields'];
    		$fields->cache_data	= $this->cache->getCache('bit_cfields');
    	
    		$fields->init_data( 'view' );
    		$fields->parseToView();
    		
    		foreach( $fields->out_fields as $id => $data )
    		{
	    		if( $fields->cache_data[ $id ]['cf_topic'] )
	    		{
		    		$data = $data ? $data : $this->lang->words['cat_no_info'];
		    		
					$this->cfields .= '[b]' . $fields->field_names[ $id ] . '[/b]: ' . $data . "<br />";
				}
    		}
		}
		
		//---------------------------------------------------------
		// Should topic be posted at all?
		//---------------------------------------------------------

		if( $category['coptions']['opt_topice'] == 1 )
		{
			if( $category['coptions']['opt_topicf'] )
			{
				//---------------------------------------------------------
				// Get some libraries we need
				//---------------------------------------------------------

				ipsRegistry::getAppClass( 'forums' );

				$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php', 'classPost', 'forums' );
				$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );
				$this->post				= new $classToLoad( $this->registry );

				//---------------------------------------------------------
				// Format prefix/suffix
				//---------------------------------------------------------
		
				$category['coptions']['opt_topics'] = str_replace( "{catname}", $category['cname'], $category['coptions']['opt_topics'] );
				$category['coptions']['opt_topicp'] = str_replace( "{catname}", $category['cname'], $category['coptions']['opt_topicp'] );
				
				//---------------------------------------------------------
				// Verify topic "poster"
				//---------------------------------------------------------

				if( !$mid_override )
				{
					$file['file_submitter'] = ( $type == 'new' ) ? $this->memberData['member_id'] : $file['file_submitter'];
				}
				
				if( $file['file_submitter'] == 0 AND !$file['file_submitter_name'] )
				{
					$file['file_submitter_name'] = $this->lang->words['global_guestname'];
				}

				$member			= $file['file_submitter'] ? IPSMember::load( $file['file_submitter'] ) : IPSMember::setUpGuest();

				if( !$member['member_id'] )
				{
					$this->request['UserName']	= $file['file_submitter_name'];
				}

				//-----------------------------------------
				// Retrieve tags, in case this is not a form submit
				//-----------------------------------------

				if( !$_POST['ipsTags'] )
				{
					$this->DB->build( array( 'select' => '*', 'from' => 'core_tags', 'where' => "tag_meta_app='bitracker' AND tag_meta_area='files' AND tag_meta_id=" . $file['file_id'] ) );
					$this->DB->execute();
					
					while( $r = $this->DB->fetch() )
					{
						$_POST['ipsTags'][]	= $r['tag_text'];

						if( $r['tag_prefix'] )
						{
							$_REQUEST['ipsTags_prefix']	= 1;
						}
					}
				}

				$_backupMember		= $this->memberData;
				$this->memberData	= $member;

				//---------------------------------------------------------
				// Update topic or post a new one?
				//---------------------------------------------------------
				
				if(	$type == 'new' )
				{
					$this->_postNewTopic( $file, $category, $member );
				}
				else
				{
					$this->_postUpdatedTopic( $file, $category, $member );
				}

				$this->memberData	= $_backupMember;
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Post a new topic
	 *
	 * @param	array 		File information
	 * @param	array 		Category information
	 * @param	array 		Member information
	 * @return	@e boolean
	 */	
	protected function _postNewTopic( $file, $category, $member )
	{							
		$ttitle			= IPSText::truncate( IPSText::UNhtmlspecialchars( $file['file_name'] ), $this->settings['topic_title_max_len'] - intval(strlen($category['coptions']['opt_topicp'])) - intval(strlen($category['coptions']['opt_topics'])) );
		
		if( $category['coptions']['opt_topicp'] )
		{
			$ttitle		= $category['coptions']['opt_topicp'] . $ttitle;
		}
		if( $category['coptions']['opt_topics'] )
		{
			$ttitle		.= $category['coptions']['opt_topics'];
		}					
		
		$post_content	= $this->_buildPostContent( $file, $category );

		try
		{
			$this->post->setBypassPermissionCheck( true );
			$this->post->setIsAjax( false );
			$this->post->setPublished( true );
			$this->post->setForumID( $category['coptions']['opt_topicf'] );
			$this->post->setAuthor( $member );
			$this->post->setPostContentPreFormatted( $post_content );
			$this->post->setTopicTitle( $ttitle );
			$this->post->setSettings( array( 'enableSignature' => 1,
									   'enableEmoticons' => 1,
									   'post_htmlstatus' => 0 ) );
			
			if( $this->post->addTopic() === false )
			{
				if( $this->debugMode )
				{
					print_r($this->post->getPostError());exit;
				}

				return false;
			}
			
			$topic = $this->post->getTopicData();
			
			$this->DB->update( "bitracker_files", array( 'file_topicid' => $topic['tid'], 'file_topicseoname' => $topic['title_seo'] ), "file_id=" . $file['file_id'] );
		}
		catch( Exception $e )
		{
			if( $this->debugMode )
			{
				print $e->getMessage();exit;
			}

			return false;
		}
		
		return true;
	}
	
	/**
	 * Update an existing topic
	 *
	 * @param	array 		File information
	 * @param	array 		Category information
	 * @param	array 		Member information
	 * @return	boolean
	 */	
	protected function _postUpdatedTopic( $file, $category, $member )
	{
		$tid	= $file['file_topicid'];

		if( $tid > 0 && $file['file_open'] )
		{			
			$ttitle		= IPSText::truncate( IPSText::UNhtmlspecialchars( $file['file_name'] ), $this->settings['topic_title_max_len'] - intval(strlen($category['coptions']['opt_topicp'])) - intval(strlen($category['coptions']['opt_topics'])) );
			
			if( $category['coptions']['opt_topicp'] )
			{
				$ttitle	= $category['coptions']['opt_topicp'] . $ttitle;
			}
			if( $category['coptions']['opt_topics'] )
			{
				$ttitle	.= $category['coptions']['opt_topics'];
			}
			
			try
			{
				$firstpost	= $this->DB->buildAndFetch( array( 'select'	=> '*',
																'from'	=> 'topics',
																'where'	=> 'tid=' . $tid
														)		);

				if( $firstpost['topic_firstpost'] )
				{
					$post_content	= $this->_buildPostContent( $file, $category );
					
					$_settings = array( 'enableSignature' => 1,
										'enableEmoticons' => 1,
										'post_htmlstatus' => 1,
									  );
					
					/* Are we following? */
					if ( $this->memberData['auto_track'] )
					{
						$_settings['enableTracker'] = 1;
					}
					else
					{
						require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
						$_like	= classes_like::bootstrap( 'forums','topics' );
						$_settings['enableTracker'] = $_like->isLiked( $firstpost['tid'], $this->memberData['member_id'] );
					}

					$this->post->setBypassPermissionCheck( true );
					$this->post->setIsAjax( false );
					$this->post->setPublished( $firstpost['approved'] ? true : false );
					$this->post->setPostID( $firstpost['topic_firstpost'] );
					$this->post->setTopicData( $firstpost );
					$this->post->setTopicID( $tid );
					$this->post->setTopicTitle( $ttitle );
					$this->post->setForumID( $category['coptions']['opt_topicf'] );
					$this->post->setAuthor( $member );
					$this->post->setPostContentPreFormatted( $post_content );
					$this->post->setSettings( $_settings );
					
					if( $this->post->editPost() === false )
					{
						if( $this->debugMode )
						{
							print_r($this->post->getPostError());exit;
						}

						return false;
					}
				}
			}
			catch( Exception $e )
			{
				if( $this->debugMode )
				{
					print $e->getMessage();exit;
				}

				return false;
			}
		}

		return true;
	}
	
	/**
	 * Build the actual post content
	 *
	 * @access	protected
	 * @param	array 		File information
	 * @param	array 		Category information
	 * @param	boolean		Whether or not to add "updated" flag
	 * @return	boolean
	 */	
	protected function _buildPostContent( $file, $category, $addUpdated=false )
	{
		$post_content = "";

		if( $category['coptions']['opt_topicss'] )
		{
			//-----------------------------------------
			// SS data is not present, so we need to
			// query to see if there is a SS
			//-----------------------------------------
			
			$_check	= $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as screenshots', 'from' => 'bitracker_files_records', 'where' => "record_file_id={$file['file_id']} AND record_backup=0 AND record_type IN ('sslink','ssupload')" ) );


       	$post_content			.= "<div style='width:100%' class='container'>";


			if( $_check['screenshots'] )
			{

       	$post_content			.= "[center]<span rel='lightbox'><img class='bbc_img' src=" . $this->registry->bitFunctions->returnScreenshotUrl( $file ) . "></span>[/center]<br /><br />";

			}
		}

		
		$post_content			.= "<h2>{$file['file_name']}</h2><br />";

		$post_content			.= "[url=" . $this->registry->output->formatUrl( $this->base_url . "app=bitracker&showfile={$file['file_id']}", $file['file_name_furl'], 'bitshowfile' ) . "]<button type='button' class='btn btn-default'><span class='glyphicon glyphicon-info-sign'></span>&nbsp;&nbsp;More Info</button>[/url]<br /><br />";


		$post_content			.= "<ul class='nav nav-pills nav-justified'>";

		$post_content			.= "<li class='active'>";

		$post_content			.= "<a href='#tab_details' data-toggle='pill'>Torrent Details</a>";

		$post_content			.= "</li>";

		$post_content			.= "<li>";

		$post_content			.= "<a href='#tab_specs' data-toggle='pill'>Technical Specs</a>";

		$post_content			.= "</li>";

		$post_content			.= "<li>";

		$post_content			.= "<a href='#tab_info' data-toggle='pill'>Additional Information</a>";

		$post_content			.= "</li>";

		$post_content			.= "</ul>";

		$post_content			.= "<div class='row'>";

		$post_content			.= "<div class='col-md-6'>";

		$post_content			.= "<h3>Torrent Details</h3>";
		if( $file['file_submitter'] )
		{
			$post_content	.= "<p>[b]{$this->lang->words['t_torrentauthor']}[/b]: [url=" . $this->registry->output->formatUrl( $this->base_url . "showuser={$file['file_submitter']}", IPSText::makeSeoTitle( $file['file_submitter_name'] ), 'showuser' ) . "]{$file['file_submitter_name']}[/url]<br />";
		}
		else
		{
		$post_content	.= "<p>[b]{$this->lang->words['t_torrentauthor']}[/b]: {$file['file_submitter_name']}</p>";
		}

		$post_content			.= "[b]{$this->lang->words['t_torrentversion']}[/b]: [b]{$file['file_version']}[/b]<br />";
		
		$post_content			.= "[b]{$this->lang->words['t_torrentsize']}[/b]: " . IPSLib::sizeFormat( $file['torrent_filesize'] ) . "<br />";

		$post_content			.= "[b]{$this->lang->words['t_torrenthash']}[/b]: " . $file['torrent_infohash'] . "<br />";

		$post_content			.= "[b]{$this->lang->words['t_submitted']}[/b]: " . $this->registry->getClass('class_localization')->getDate( $file['file_submitted'], 'DATE', true ) . "<br />";
		

		$post_content		.= "[b]{$this->lang->words['t_updated']}[/b]: [i]" . $this->registry->getClass('class_localization')->getDate( $file['file_updated'], 'DATE', true ) . "[/i]<br />";


		$post_content			.= "[b]{$this->lang->words['t_category']}[/b]: [url=" . $this->registry->output->formatUrl( $this->base_url . "app=bitracker&showcat={$file['file_cat']}", $category['cname_furl'], 'bitshowcat' ) . "]" . $category['cname'] . "[/url]<br />";
		
		
		if( $this->cfields )
		{
			$post_content	.= $this->cfields;
		}

		$post_content			.= "</div>";

		$post_content			.= "<div class='col-md-6'>";

		$post_content			.= "<h3>Torrent Stats</h3>";

		$post_content			.= "</div>";

		$post_content			.= "</div>";

		$post_content			.= "<br /><div class='tab-content'>";

		$post_content			.= "<div class='tab-pane fade in active' id='tab_details'>";

		$post_content			.= "<br /><div class='well'>";
		
		// Need a newline after custom fields

		$post_content			.= "{$file['file_desc']}";

		$post_content			.= "</div>";

		$post_content			.= "</div>";

		$post_content			.= "<div class='tab-pane fade' id='tab_specs'>";

		$post_content			.= "<br /><div class='well'>";

		$post_content			.= "<h3>Technical Specs</h3>";

		$post_content			.= "{$file['file_tech']}";

		$post_content			.= "</div>";

		$post_content			.= "</div>";

		$post_content			.= "<div class='tab-pane fade' id='tab_info'>";

		$post_content			.= "<br /><div class='well'>";

		$post_content			.= "<h3>Additional Information</h3>";

		$post_content			.= "{$file['file_extra']}";

		$post_content			.= "</div>";

		$post_content			.= "</div>";

		$post_content			.= "</div>";

		$post_content			.= "[hide][center]{$this->registry->getClass('output')->getTemplate('bitracker_external')->downlink( $file )}[/center][/hide]";


		IPSText::getTextClass('bbcode')->parse_html			= $this->registry->getClass('class_forums')->allForums[ $category['coptions']['opt_topicf'] ]['use_html'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
		IPSText::getTextClass('bbcode')->parse_smilies		= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode		= $this->registry->getClass('class_forums')->allForums[ $category['coptions']['opt_topicf'] ]['use_ibc'] ? 1 : 0;
		IPSText::getTextClass('bbcode')->parsing_section	= 'bit_submit';

		$post_content = IPSText::getTextClass('bbcode')->preDbParse( $post_content );
		
		return $post_content;
	}

}