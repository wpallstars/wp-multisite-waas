<?php
/**
 * Base Gateway.
 *
 * Base Gateway class. Should be extended to add new payment gateways.
 *
 * @package WP_Ultimo
 * @subpackage Managers/Site_Manager
 * @since 2.0.0
 */

namespace WP_Ultimo\Gateways;

use WP_Ultimo\Gateways\Base_Gateway;
use WP_Ultimo\Database\Memberships\Membership_Status;
use WP_Ultimo\Database\Payments\Payment_Status;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Base Gateway class. Should be extended to add new payment gateways.
 *
 * @since 2.0.0
 */
class Free_Gateway extends Base_Gateway {

	/**
	 * Holds the ID of a given gateway.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	protected $id = 'free';

	/**
	 * Process a checkout.
	 *
	 * It takes the data concerning
	 * a new checkout and process it.
	 *
	 * Here's where you will want to send
	 * API calls to the gateway server,
	 * set up recurring payment profiles, etc.
	 *
	 * This method is required and MUST
	 * be implemented by gateways extending the
	 * Base_Gateway class.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Payment    $payment The payment associated with the checkout.
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer The customer checking out.
	 * @param \WP_Ultimo\Checkout\Cart     $cart The cart object.
	 * @param string                       $type The checkout type. Can be 'new', 'retry', 'upgrade', 'downgrade', 'addon'.
	 * @return void
	 */
	public function process_checkout($payment, $membership, $customer, $cart, $type): void {

		$membership_status = $membership->get_status();

		// Set current gateway
		$membership->set_gateway_subscription_id('');
		$membership->set_gateway_customer_id('');
		$membership->set_gateway($this->get_id());
		$membership->set_auto_renew(false);

		if ('downgrade' === $type && (Membership_Status::ACTIVE === $membership_status || Membership_Status::TRIALING === $membership_status)) {
			/*
			 * When downgrading, we need to schedule a swap for the end of the
			 * current expiration date.
			 */
			$membership->schedule_swap($cart);

			/*
			 * Saves the membership with the changes.
			 */
			$status = $membership->save();

			return;
		} elseif ('upgrade' === $type || 'downgrade' === $type || 'addon' === $type) {
			/*
			 * A change to another free membership
			 * is a upgrade and if membership is not
			 * active or trialling, we swap now.
			 */
			$membership->swap($cart);

			$membership->set_status(Membership_Status::ACTIVE);
		}

		$membership->save();

		$payment->set_status(Payment_Status::COMPLETED);

		$payment->save();
	}

	/**
	 * Process a cancellation.
	 *
	 * It takes the data concerning
	 * a membership cancellation and process it.
	 *
	 * Here's where you will want to send
	 * API calls to the gateway server,
	 * to cancel a recurring profile, etc.
	 *
	 * This method is required and MUST
	 * be implemented by gateways extending the
	 * Base_Gateway class.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer The customer checking out.
	 * @return void
	 */
	public function process_cancellation($membership, $customer) {}

	/**
	 * Process a refund.
	 *
	 * It takes the data concerning
	 * a refund and process it.
	 *
	 * Here's where you will want to send
	 * API calls to the gateway server,
	 * to issue a refund.
	 *
	 * This method is required and MUST
	 * be implemented by gateways extending the
	 * Base_Gateway class.
	 *
	 * @since 2.0.0
	 *
	 * @param float                        $amount The amount to refund.
	 * @param \WP_Ultimo\Models\Payment    $payment The payment associated with the checkout.
	 * @param \WP_Ultimo\Models\Membership $membership The membership.
	 * @param \WP_Ultimo\Models\Customer   $customer The customer checking out.
	 * @return void
	 */
	public function process_refund($amount, $payment, $membership, $customer) {}
}
