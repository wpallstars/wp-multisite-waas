<?php
/**
 * Grid item view.
 *
 * @since 2.0.0
 */
?>
<div class="wu-border-transparent" tabindex="0">

	<div class="wu-grid-item wu-border wu-border-solid wu-border-gray-300 wu-py-8 wu-bg-white wu-text-center">

	<div
		class="wu--mt-8 wu-py-8 wu-bg-gray-100 wu-bg-cover wu-bg-center"
		style="opacity: 0.15; background-image: url(<?php echo esc_url(get_avatar_url($item->get_user_id(), array('default' => 'identicon', 'size' => 320))); ?>)"
	>
		&nbsp;
	</div>

	<div class="customer-avatar wu-relative wu--mt-8">
		<?php
		echo get_avatar(
			$item->get_user_id(),
			92,
			'identicon',
			'',
			[
				'force_display' => true,
				'class'         => 'wu-rounded-full wu-border wu-border-solid wu-border-gray-300 wu-bg-white',
			]
		);
		?>
	</div>

	<div class="wu-text-base wu-mt-1">
		<div>
		<span class="wu-font-semibold"><?php echo esc_html($item->get_display_name()); ?></span>
		<small>#<?php echo esc_html($item->get_id()); ?></small>
		</div>
		<div class="wu-text-xs wu-my-1">
		<?php if ($item->get_email_address()) : ?>
		<a class="wu-no-underline" href="mailto:<?php echo esc_attr($item->get_email_address()); ?>">
			<?php echo esc_html($item->get_email_address()); ?>
		</a>
		<?php else : ?>
			<?php _e('No email address', 'wp-multisite-waas'); ?>
		<?php endif; ?>
		</div>
		<div class="wu-text-xs">
		<span class="<?php echo $item->is_vip() ? esc_attr('wu-font-semibold') : ''; ?>">
			<?php echo $item->is_vip() ? __('VIP Customer', 'wp-multisite-waas') : __('Regular Customer', 'wp-multisite-waas'); ?>
		</span>
		</div>
	</div>

	<div class="customer-secondary-info wu-mt-5">

		<div class="wu-flex wu-justify-between wu-border-0 wu-border-t wu-border-solid wu-border-gray-300 wu-py-2 wu-px-3">
			<span>
			<?php _e('Last Login:', 'wp-multisite-waas'); ?>
			</span>
			<span class="wu-font-semibold">
			<?php
			if ($item->is_online()) {
				echo '<span class="wu-inline-block wu-mr-1 wu-rounded-full wu-h-2 wu-w-2 wu-bg-green-500"></span>' . __('Online', 'wp-multisite-waas');
			} elseif ( '0000-00-00 00:00:00' !== $item->get_last_login() ) {
					echo human_time_diff(strtotime($item->get_last_login()), time()) . ' ' . __('ago', 'wp-multisite-waas');
			} else {
				_e('Never logged in', 'wp-multisite-waas');
			}
			?>
			</span>
		</div>
		<div class="wu-flex wu-justify-between wu-border-0 wu-border-t wu-border-solid wu-border-gray-300 wu-py-2 wu-px-3">
			<span>
			<?php _e('Customer Since:', 'wp-multisite-waas'); ?>
			</span>
			<span class="wu-font-semibold">
			<?php echo human_time_diff(strtotime($item->get_date_registered()), time()) . ' ' . __('ago', 'wp-multisite-waas'); ?>
			</span>
		</div>

		<div class="wu-flex wu-justify-between wu-border-0 wu-border-gray-300 wu-border-t wu-border-b-0 wu-border-solid wu-py-2 wu-px-3">
			<span>
			<?php _e('Memberships:', 'wp-multisite-waas'); ?>
			</span>
			<div>
			<span class="wu-font-semibold">
				<?php echo count($item->get_memberships()); ?>
			</span>

			<?php
			if ( ! empty($item->get_memberships())) {
				?>
				<a  href="<?php echo wu_network_admin_url('wp-ultimo-memberships', ['customer_id' => $item->get_id()]); ?>">
				<?php _e('View', 'wp-multisite-waas'); ?>
				</a>
				<?php
			}
			?>

			</div>
		</div>

		<div class="wu-flex wu-justify-between wu-border-0 wu-border-gray-300 wu-border-t wu-border-b-0 wu-border-solid wu-py-2 wu-px-3">
			<span>
			<?php _e('Actions:', 'wp-multisite-waas'); ?>
			</span>
			<div>

			<?php

				// Concatenate switch to url
					$is_modal_switch_to = \WP_Ultimo\User_Switching::get_instance()->check_user_switching_is_activated() ? '' : 'wubox';

					$url_switch_to = sprintf('<a title="%s" class="%s" href="%s">%s</a>', __('Switch To', 'wp-multisite-waas'), $is_modal_switch_to, \WP_Ultimo\User_Switching::get_instance()->render($item->get_user_id()), __('Switch To', 'wp-multisite-waas'));

					$actions = [
						'switch-to' => $item->get_user_id() !== get_current_user_id() ? $url_switch_to : __('None', 'wp-multisite-waas'),
					];

					echo implode('<br />', $actions);

					?>

			</div>
		</div>

	</div>

	<div class="wu-flex wu-justify-between wu-items-center wu--mb-8 wu-p-4 wu-bg-gray-100 wu-border wu-border-solid wu-border-gray-300 wu-border-l-0 wu-border-r-0 wu-border-b-0">

		<label>
			<input class="wu-rounded-none" type="checkbox" name="bulk-delete[]" value="<?php echo $item->get_id(); ?>" />
			<?php _e('Select Customer', 'wp-multisite-waas'); ?>
		</label>

		<a href="<?php echo wu_network_admin_url('wp-ultimo-edit-customer', ['id' => $item->get_id()]); ?>" class="button button-primary">
			<?php _e('Manage', 'wp-multisite-waas'); ?>
		</a>
	</div>
	</div>
</div>
