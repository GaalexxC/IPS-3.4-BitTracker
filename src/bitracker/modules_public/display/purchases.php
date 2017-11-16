<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Manage purchases
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_display_purchases extends ipsCommand
{
	/**
	 * IPS command execution
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------
		// CSRF protection
		//-------------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', '1089.p0', null, null, 403 );
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id			= intval( $this->request['id'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_purchase'], '1089.p1' );
		}
		
		//-----------------------------------------
		// Get purchase info
		//-----------------------------------------
		
		$purchase	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'nexus_purchases', 'where' => 'ps_id=' . $id ) );
		
		if( !$purchase['ps_id'] OR $purchase['ps_app'] != 'bitracker' OR !$purchase['ps_item_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_purchase'], '1089.p2' );
		}

		$file		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $purchase['ps_item_id'] ) );

		if( !$file['file_renewal_term'] )
		{
			$this->registry->output->showError( $this->lang->words['file_no_renewals'], '1089.p2a' );
		}
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'reactivate':
				/* Can we reactivate? */
				if( !$purchase['ps_can_reactivate'] )
				{
					$this->registry->output->showError( $this->lang->words['cannot_reactivate_p'], '1089.p3' );
				}

				/* If it's not cancelled and there are renewal terms, just send to the renew page */
				if( !$purchase['ps_cancelled'] AND $purchase['ps_renewals'] )
				{
					$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=nexus&amp;module=clients&amp;section=purchases&amp;do=renew&amp;secure_key={$this->member->form_hash}&amp;itemApp=bitracker&amp;itemType=file&amp;itemId={$purchase['ps_item_id']}" );
				}

				/* If we're still here, reactivate */
				$this->DB->update( 'nexus_purchases', array( 'ps_cancelled' => 0, 'ps_renewals' => $file['file_renewal_term'], 'ps_renewal_unit' => $file['file_renewal_units'], 'ps_renewal_price' => $file['file_renewal_price'] ), 'ps_id=' . $purchase['ps_id'] );

				/* And then log */
				require_once IPSLib::getAppDir('nexus') . '/sources/customer.php';
				customer::load( $purchase['ps_member'] )->logAction( 'purchase', array( 'type' => 'uncancel', 'id' => $purchase['ps_id'], 'name' => $purchase['ps_name'] ) );

				/* If it is expired, the customer will now need to renew */
				if( time() > $purchase['ps_expire'] )
				{
					$this->registry->output->redirectScreen( $this->lang->words['purchase_reactivated_renew'], $this->settings['base_url'] . "app=nexus&amp;module=clients&amp;section=purchases&amp;do=renew&amp;secure_key={$this->member->form_hash}&amp;itemApp=bitracker&amp;itemType=file&amp;itemId={$purchase['ps_item_id']}" );
				}

				/* Otherwise set a message and redirect back to file */
				$message	= $this->lang->words['purchase_reactivated'];
			break;

			case 'cancel':
				/* Cancel the purchase */
				$this->DB->update( 'nexus_purchases', array( 'ps_renewals' => 0, 'ps_can_reactivate' => 1 ), 'ps_id=' . $purchase['ps_id'] );

				/* And then log */
				require_once IPSLib::getAppDir('nexus') . '/sources/customer.php';
				customer::load( $purchase['ps_member'] )->logAction( 'purchase', array( 'type' => 'cancel', 'id' => $purchase['ps_id'], 'name' => $purchase['ps_name'] ) );

				/* Otherwise set a message and redirect back to file */
				if( $purchase['ps_expire'] < time() )
				{
					$message	= $this->lang->words['purchase_cancelled_no'];
				}
				else
				{
					$message	= sprintf( $this->lang->words['purchase_cancelled'], $this->lang->getDate( $purchase['ps_expire'], 'SHORT' ) );
				}
			break;
		}

		//-----------------------------------------
		// If we're still here, redirect back to file
		//-----------------------------------------

		$this->registry->output->redirectScreen( $message, $this->settings['base_url'] . "app=bitracker&amp;showfile={$file['file_id']}", $file['file_name_furl'], 'bitshowfile' );
	}
}