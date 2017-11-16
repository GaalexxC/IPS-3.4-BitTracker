class public_bitracker_ajax_ajaxpeers extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
	
	    if ( $this->request['fid'] == '')
		  {
		    	$this->returnJsonError( $this->lang->words['bit_bad_request'] );
				exit;
		  }

		//-------------------------------------------
		// Get the Peers
		//-------------------------------------------

		$this->DB->build( array( 'select' 	=> '*',
                                 'from'		=> 'bitracker_torrent_peers',
                                 'where'	=> "torrent='{$file['file_id']}'",
                                 'order'    => 'uploaded DESC'
                       )		);

		$result = $this->DB->execute();

		while( $row = $this->DB->fetch( $result ) )
		{

   			$peers[] = $row;

		}

		if(empty($peers))
		{

		    $this->returnJsonArray( array() );
			exit;

		}else{

 			foreach(array_keys($peers) as $peer){

 				$displayName = $this->DB->buildAndFetch( array( 'select' 	=> 'members_display_name',
                                                                'from'		=> 'members',
                                                                'where'	=> "member_id ='{$peers[$peer]['mem_id']}'"
                                        			  )		);

    	$peers[$peer]['members_display_name'] = $displayName['members_display_name'];

	   //---------------------------------------
	   // Set some numbers
	   //---------------------------------------

		$peers[$peer]['f_ratio'] = (($peers[$peer]['downloaded'] > 0) ? ($peers[$peer]['uploaded'] / $peers[$peer]['downloaded']) : 0);	
        $peers[$peer]['f_ratio'] = number_format($peers[$peer]['f_ratio'], 2, '.', '');	

		if( $peers[$peer]['seeder'] == yes)
 		{
			$peers[$peer]['p_done'] = 100;
            $peers[$peer]['downloaded'] = $file['torrent_filesize'];
            $peers[$peer]['f_ratio'] = $peers[$peer]['uploaded'] / $file['torrent_filesize'];
            $peers[$peer]['f_ratio'] = number_format($peers[$peer]['f_ratio'], 2, '.', '');

		if($file['file_submitter'] == $peers[$peer]['mem_id'])
 		{
   			$peers[$peer]['uploader'] = TRUE;
 		}

 		}else{

 			$peers[$peer]['p_done'] = $peers[$peer]['downloaded'] / $file['torrent_filesize'];
 			$peers[$peer]['p_done'] = $peers[$peer]['p_done'] * 100;
 			$peers[$peer]['p_done'] = number_format($peers[$peer]['p_done'], 1, '.', '');

  		    }

 	     }

      }

         $this->returnJsonArray( $peers );
     
		
    }
}