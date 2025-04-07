<?php
/**
 * Graph countries view.
 *
 * @since 2.0.0
 */
?>

<div class="wu-styling">

	<div class="wu-widget-inset">

		<?php
		$data    = [];
		$slug    = 'signup_forms';
		$headers = [
			__('Checkout Form', 'wp-multisite-waas'),
			__('Signups', 'wp-multisite-waas'),
		];

		foreach ($forms as $form) {
			$line = [
				esc_html($form->signup_form),
				intval($form->count), // Ensure count is an integer and properly escaped
			];

			$data[] = $line;
		}

		$page->render_csv_button(
			[
				'headers' => $headers,
				'data'    => $data,
				'slug'    => $slug,
			]
		);
		?>

	</div>

</div>

<?php if ( ! empty($forms)) : ?>

	<div class="wu-advanced-filters wu--mx-3 wu--mb-3 wu-mt-3">

		<table class="wp-list-table widefat fixed striped wu-border-t-0 wu-border-l-0 wu-border-r-0">

			<thead>
			<tr>
				<th><?php esc_html_e('Checkout Form', 'wp-multisite-waas'); ?></th>
				<th class="wu-text-right"><?php esc_html_e('Signups', 'wp-multisite-waas'); ?></th>
			</tr>
			</thead>

			<tbody>

			<?php foreach ($forms as $form) : ?>

				<tr>
					<td>
						<?php echo esc_html($form->signup_form); ?>
						<?php if ('by-admin' === $form->signup_form) : ?>
							<?php echo wp_kses_post(wu_tooltip(__('Customers created via the admin panel, by super admins.', 'wp-multisite-waas'))); ?>
						<?php endif; ?>
					</td>
					<td class="wu-text-right"><?php echo intval($form->count); ?></td> <!-- Ensure count is an integer and properly escaped -->
				</tr>

			<?php endforeach; ?>

			</tbody>

		</table>

	</div>

<?php else : ?>

	<div class="wu-bg-gray-100 wu-p-4 wu-rounded wu-mt-6">

		<?php esc_html_e('No data yet.', 'wp-multisite-waas'); ?>

	</div>

<?php endif; ?>
