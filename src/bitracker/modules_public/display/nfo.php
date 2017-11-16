<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.1.1
 * bit output screenshot
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_display_nfo extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{

		//-------------------------------------------
		// Don't update session
		//-------------------------------------------
		
		$this->DB->obj['shutdown_queries']	= array();
		
		//-------------------------------------------
		// Block item markers from updating in destructor
		//-------------------------------------------
		
		$this->member->is_not_human			= true;
		
		//-------------------------------------------
		// Have an id?
		//-------------------------------------------
		
		if( !$this->request['id'] )
		{
			$this->_safeExit();
		}
		
		$file_id	= intval($this->request['id']);
		
		//-------------------------------------------
		// Clear output buffer
		//-------------------------------------------
		
		ob_end_clean();
			
		//-------------------------------------------
		// Get file
		//-------------------------------------------
		
		$where = "r.record_file_id=" . $file_id;
		
		
	    $nfo = $this->DB->buildAndFetch( array(
													'select'	=> 'r.*',
													'from'		=> array( 'bitracker_files_records' => 'r' ),
													'where'		=> $where . " AND record_type IN('nfoupload','nfolink') AND record_backup=0",
													'limit'		=> array( 1 ),
													'add_join'	=> array(
																		array(
																				'select'	=> 'f.file_cat',
																				'from'		=> array( 'bitracker_files' => 'f' ),
																				'where'		=> 'f.file_id=r.record_file_id',
																				'type'		=> 'left'
																			),
																		array(
																				'select'	=> 'm.mime_mimetype',
																				'from'		=> array( 'bitracker_mime' => 'm' ),
																				'where'		=> 'm.mime_id=r.record_mime',
																				'type'		=> 'left'
																			),
																		)
													)		);


		if( !$nfo )
		{
			$this->_safeExit();
		}


		//-------------------------------------------
		// Switch on the storage type...
		//-------------------------------------------
		
		switch( $nfo['record_storagetype'] )
		{
			case 'disk':

                    $path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . "/" . $nfo['record_location'];

					$content = @file_get_contents( $path );
					
					if( !$content )
					{
						$this->_safeExit();
					}

                    $nfoDisplay = $this->convertNfoforDisplay( $content );

                    $HTMLOUT = '';
//$HTMLOUT = $this->registry->getClass('bitFunctions')->output_nfo_image($path);
	                 
	                 $HTMLOUT .= "<center>\n";   	     
	                 $HTMLOUT .= "<table border='0' cellspacing='0' cellpadding='0'><tr><td class='text'>\n";
	                 $HTMLOUT .= "<pre style='display:block'>$nfoDisplay</pre>\n";
	                 $HTMLOUT .= "</td></tr></table>\n";
	                 $HTMLOUT .= "</center>\n"; 

                  print $HTMLOUT;
                    					
			break;
				
			case 'db':
				if( $nfo['storage_thumb'] )
				{
					$content = $this->request['full'] ? base64_decode($nfo['storage_nfo']) : base64_decode($nfo['storage_thumb']);
				}
				else if( $nfo['storage_nfo'] )
				{
					$using_full	= true;
					$content	= base64_decode($nfo['storage_nfo']);
				}
				
				if( !$content )
				{
					$this->_safeExit();
				}
				
				$bits		= explode( '.', $nfo['record_location'] );
				$extension	= strtolower( array_pop( $bits ) );
				$path		= $this->settings['upload_dir'];
				$thumb		= md5( uniqid( microtime() ) ) . '.' . $extension;
				
				$fh = @fopen( $path . '/' . $thumb, 'wb' );
				@fputs ($fh, $content, strlen($content) );
				@fclose($fh);
			break;

			case 'ftp':
				if( $nfo['record_thumb'] )
				{
					$thumb = $this->request['full'] ? $nfo['record_location'] : $nfo['record_thumb'];
				}
				else if( $nfo['record_location'] )
				{
					$using_full	= true;
					$thumb		= $nfo['record_location'];
				}
				else
				{
					$this->_safeExit();
				}
				
				$path	= $this->settings['upload_dir'];
				
				if( $this->settings['bit_remoteurl'] AND
					$this->settings['bit_remoteport'] AND
					$this->settings['bit_remoteuser'] AND
					$this->settings['bit_remotepass'] AND
					$this->settings['bit_remotefilepath'] )
				{
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFtp.php', 'classFtp' );
					
					try
					{
						classFtp::$transferMode	= FTP_BINARY;
	
						$_ftpClass		= new $classToLoad( $this->settings['bit_remoteurl'], $this->settings['bit_remoteuser'], $this->settings['bit_remotepass'], $this->settings['bit_remoteport'], '/', true, 999999 );
						
						$_ftpClass->chdir( $this->settings['bit_remotenfopath'] );
						$_ftpClass->file( $thumb )->download( $path . '/' . $thumb );
						
						unset( $_ftpClass );
					}
					catch( Exception $e )
					{
						$this->_safeExit();
					}
				}
			break;					
		}

		exit;

		}
	

	/**
	 * On client announce error retrieves the error code and string and puts it into the log DB
	 * @param	array	Error info
	 * @param	bool	Whether we have already checked for the screenshot (prevents duplicate DB query)
	 * @note	
	 */
     public function convertNfoforDisplay( $nfo )
       {
        $trans = array(
        "\x80" => "&#199;", "\x81" => "&#252;", "\x82" => "&#233;", "\x83" => "&#226;", "\x84" => "&#228;", "\x85" => "&#224;", "\x86" => "&#229;", "\x87" => "&#231;", "\x88" => "&#234;", "\x89" => "&#235;", "\x8a" => "&#232;", "\x8b" => "&#239;", "\x8c" => "&#238;", "\x8d" => "&#236;", "\x8e" => "&#196;", "\x8f" => "&#197;", "\x90" => "&#201;",
        "\x91" => "&#230;", "\x92" => "&#198;", "\x93" => "&#244;", "\x94" => "&#246;", "\x95" => "&#242;", "\x96" => "&#251;", "\x97" => "&#249;", "\x98" => "&#255;", "\x99" => "&#214;", "\x9a" => "&#220;", "\x9b" => "&#162;", "\x9c" => "&#163;", "\x9d" => "&#165;", "\x9e" => "&#8359;", "\x9f" => "&#402;", "\xa0" => "&#225;", "\xa1" => "&#237;",
        "\xa2" => "&#243;", "\xa3" => "&#250;", "\xa4" => "&#241;", "\xa5" => "&#209;", "\xa6" => "&#170;", "\xa7" => "&#186;", "\xa8" => "&#191;", "\xa9" => "&#8976;", "\xaa" => "&#172;", "\xab" => "&#189;", "\xac" => "&#188;", "\xad" => "&#161;", "\xae" => "&#171;", "\xaf" => "&#187;", "\xb0" => "&#9617;", "\xb1" => "&#9618;", "\xb2" => "&#9619;",
        "\xb3" => "&#9474;", "\xb4" => "&#9508;", "\xb5" => "&#9569;", "\xb6" => "&#9570;", "\xb7" => "&#9558;", "\xb8" => "&#9557;", "\xb9" => "&#9571;", "\xba" => "&#9553;", "\xbb" => "&#9559;", "\xbc" => "&#9565;", "\xbd" => "&#9564;", "\xbe" => "&#9563;", "\xbf" => "&#9488;", "\xc0" => "&#9492;", "\xc1" => "&#9524;", "\xc2" => "&#9516;", "\xc3" => "&#9500;",
        "\xc4" => "&#9472;", "\xc5" => "&#9532;", "\xc6" => "&#9566;", "\xc7" => "&#9567;", "\xc8" => "&#9562;", "\xc9" => "&#9556;", "\xca" => "&#9577;", "\xcb" => "&#9574;", "\xcc" => "&#9568;", "\xcd" => "&#9552;", "\xce" => "&#9580;", "\xcf" => "&#9575;", "\xd0" => "&#9576;", "\xd1" => "&#9572;", "\xd2" => "&#9573;", "\xd3" => "&#9561;", "\xd4" => "&#9560;",
        "\xd5" => "&#9554;", "\xd6" => "&#9555;", "\xd7" => "&#9579;", "\xd8" => "&#9578;", "\xd9" => "&#9496;", "\xda" => "&#9484;", "\xdb" => "&#9608;", "\xdc" => "&#9604;", "\xdd" => "&#9612;", "\xde" => "&#9616;", "\xdf" => "&#9600;", "\xe0" => "&#945;", "\xe1" => "&#223;", "\xe2" => "&#915;", "\xe3" => "&#960;", "\xe4" => "&#931;", "\xe5" => "&#963;",
        "\xe6" => "&#181;", "\xe7" => "&#964;", "\xe8" => "&#934;", "\xe9" => "&#920;", "\xea" => "&#937;", "\xeb" => "&#948;", "\xec" => "&#8734;", "\xed" => "&#966;", "\xee" => "&#949;", "\xef" => "&#8745;", "\xf0" => "&#8801;", "\xf1" => "&#177;", "\xf2" => "&#8805;", "\xf3" => "&#8804;", "\xf4" => "&#8992;", "\xf5" => "&#8993;", "\xf6" => "&#247;",
        "\xf7" => "&#8776;", "\xf8" => "&#176;", "\xf9" => "&#8729;", "\xfa" => "&#183;", "\xfb" => "&#8730;", "\xfc" => "&#8319;", "\xfd" => "&#178;", "\xfe" => "&#9632;", "\xff" => "&#160;",
        );
        $trans2 = array("\xe4" => "&auml;",        "\xF6" => "&ouml;",        "\xFC" => "&uuml;",        "\xC4" => "&Auml;",        "\xD6" => "&Ouml;",        "\xDC" => "&Uuml;",        "\xDF" => "&szlig;");
        $all_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $last_was_ascii = False;
        $tmp = "";
        $nfo = $nfo . "\00";
        for ($i = 0; $i < (strlen($nfo) - 1); $i++)
        {
                $char = $nfo[$i];
                if (isset($trans2[$char]) and ($last_was_ascii or strpos($all_chars, ($nfo[$i + 1]))))
                {
                        $tmp = $tmp . $trans2[$char];
                        $last_was_ascii = True;
                }
                else
                {
                        if (isset($trans[$char]))
                        {
                                $tmp = $tmp . $trans[$char];
                        }
                        else
                        {
                            $tmp = $tmp . $char;
                        }
                        $last_was_ascii = strpos($all_chars, $char);
                }
        }

        return $tmp;

        }	
	
	/**
	 * Remove a temporary image
	 *
	 * @access	protected
	 * @param	string		Path to image
	 * @param	string		Image filename
	 * @return	boolean
	 */	
	protected function _removeTempImage( $path, $disNfo )
	{
		if( !$path OR !$disNfo )
		{
			return false;
		}
		
		if( is_file( $path . '/' . $disNfo ) )
		{
			@unlink( $path . '/' . $disNfo );
		}
		
		return true;
	}

	/**
	 * Print a 1x1 transparent gif and safely exist
	 *
	 * @access	protected
	 * @return	@e void
	 */
	protected function _safeExit()
	{
		if( is_file( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png' ) )
		{
			$content	= file_get_contents( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png' );
			header( "Content-type: image/png" );
		}
		else
		{
			$content	= base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
			header( "Content-type: image/gif" );
		}

		header( "Connection: Close" );
		header( "Cache-Control:  public, max-age=86400" );
		header( "Expires: " . gmdate( "D, d M Y, H:i:s", time() + 86400 ) . " GMT" );
		print $content;
		flush();
		exit;
	}
}