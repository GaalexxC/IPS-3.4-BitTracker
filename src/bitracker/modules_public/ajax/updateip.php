<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.bitracker save rating
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_ajax_updateip extends ipsAjaxCommand 
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
       	$id 	= intval($this->request['id']);
        $ip     = $this->convertAndMakeSafe($this->request['strip']);
        $_ip    = preg_replace('/(\\-+)/', '.' ,$ip);

		if( !$id OR !$ip )
		{
				$this->returnJsonError( $this->lang->words['bit_bad_request'] );
				exit;
        }

		if ( !filter_var( trim($_ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) 
        {
		  		$this->returnJsonError( $this->lang->words['bit_bad_ip'] );
				exit;
		}


        if($id == 1)
         {
           $ip_address = 'ip_address';
           $mem_ip = $this->memberData['ip_address'];
         }elseif($id == 2){
           //$ip_address = 'ip_address2';
           //$mem_ip = $this->memberData['ip_address2'];
		  		$this->returnJsonError( $this->lang->words['bit_bad_ip'] );
				exit;
         }elseif($id == 3){
           //$ip_address = 'ip_address3';
           //$mem_ip = $this->memberData['ip_address3'];
		  		$this->returnJsonError( $this->lang->words['bit_bad_ip'] );
				exit;
         }else{
		  		$this->returnJsonError( $this->lang->words['bit_invalid_id'] );
				exit;
         }


        if($_ip == $mem_ip)
         {

           $this->returnJsonArray( array( 'ip' => $this->cleanOutput( $_ip ), 'msgi' => $this->cleanOutput('<div>The IP already matches you don\'t need to update it</div>' ) ) );
           return;

         }else{

	 	   $this->DB->update( 'members', array( $ip_address => $_ip ), "member_id='{$this->memberData['member_id']}'" );

           $this->returnJsonArray( array( 'ip' => $this->cleanOutput( $_ip ), 'msg' => $this->cleanOutput('<div>Your IP Address has been updated</div>') ) );
         }
		
    }
}