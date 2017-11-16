<?php

class benc
{

	/**
	 * Decoded Torrent info
	 *
	 * @access	public
	 * @var		array
	 */	
	public $TorrentInfo	= array();

	/**
	 * Torrent path
	 *
	 * @access	public
	 * @var		var
	 */	
	public $filepath;

	/**
	 * Raw decoded torrent
	 *
	 * @access	public
	 * @var		var
	 */	
	public $decoded;

	/**
	 * Error?
	 *
	 * @access	public
	 * @var		var
	 */	
	public $decode_error = 0;

	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $TorrentInfo=array() )
	{
		/* Make object */
        $this->TorrentInfo = $TorrentInfo;
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

public function ParseTorrent( $filepath ) {


	require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/torrent_handlers/BDecode.php' );/*noLibHook*/
	require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/torrent_handlers/BEncode.php' );/*noLibHook*/

		$parseme = @file_get_contents($filepath);

        $decode_error = 0;

	if (empty ($parseme) ){

		$this->decode_error = 1;
        return $this->decode_error; 

	}else{
 
		$Decoded = BDecode($parseme);

			if(!@count($Decoded['info'])){

	        	$this->decode_error = 1;
                 return $this->decode_error;
 
			}else{

				//Get Announce URL
				$this->TorrentInfo["torrent_announce"] = $Decoded["announce"];

				//Get Announce List Array
				if (isset( $Decoded["announce-list"] ) ){
					$this->TorrentInfo["torrent_announce_list"] = $Decoded["announce-list"];
				}else{
					$this->TorrentInfo["torrent_announce_list"] = false;
                     }

				if (isset( $Decoded["created by"] ) ){
					$this->TorrentInfo["torrent_created_by"] = $Decoded["created by"];
				}

				// Get date created
				$this->TorrentInfo["torrent_created_date"] = $Decoded["creation date"];

				//Read info, store as (infovariable)
				$infovariable = $Decoded["info"];

				// Calculates SHA1 Hash
				$this->TorrentInfo["torrent_infohash"] = sha1(BEncode($infovariable));

				if (isset($infovariable["private"])){
					$this->TorrentInfo["torrent_private_flag"] = $infovariable["private"];
				}

				if (isset($infovariable["piece length"])){
					$this->TorrentInfo["piece_length"] = $infovariable["piece length"];
				}

				if (isset($infovariable["pieces"])){
					$this->TorrentInfo["pieces"] = mb_strlen($infovariable["pieces"]);
				}

				// The name of the torrent is different to the file name
				$this->TorrentInfo["torrent_name"] = $infovariable["name"] ;

				//Get File List
				if (isset($infovariable["files"]))  {
					// Multi File Torrent
					$filecount = "";

					//Get filenames here
					$this->TorrentInfo["torrent_filelist"] = $infovariable["files"];

					foreach ($infovariable["files"] as $file) {
						$filecount += "1";
						$multiname = $file['path'];//Not needed here really
						$multitorrentsize = $file['length'];
						$torrentsize += $file['length'];
					}
					$this->TorrentInfo["torrent_filesize"] = $torrentsize;  //Add all parts sizes to get total
					$this->TorrentInfo["torrent_file_count"] = $filecount;  //Get file count
				}else {
					// Single File Torrent
					$torrentsize = $infovariable['length'];
					$this->TorrentInfo["torrent_name"] = $infovariable["name"];
					$this->TorrentInfo["torrent_filesize"] = $torrentsize;//Get file count
					$this->TorrentInfo["torrent_file_count"] = "1";
				}

				// Get Torrent Comment
				if(isset($Decoded['comment'])) {
					 $this->TorrentInfo["torrent_comment"] = $Decoded['comment'];
				}

				// Get Encode format
				if(isset($Decoded['encoding'])) {
					 $this->TorrentInfo["torrent_encoding"] = $Decoded['encoding'];
				}

                 }
         }


   return $this->TorrentInfo;

}

public function DecodeTorrentRaw( $filepath ) {

	require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/torrent_handlers/BDecode.php' );/*noLibHook*/
	require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/torrent_handlers/BEncode.php' );/*noLibHook*/

		$decode = @file_get_contents($filepath);

	if (empty ($decode) ){

		$this->decode_error = 1;
        return $this->decode_error; 

	}else{
 
		$decoded = BDecode($decode);

    }

   return $decoded;

}


} // End of class declaration.

?>