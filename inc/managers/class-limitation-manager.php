<?php
/**
 * Limitation Manager
 *
 * Handles processes related to limitations.
 *
 * @package WP_Ultimo
 * @subpackage Managers/Limitation_Manager
 * @since 2.0.0
 */

namespace WP_Ultimo\Managers;

// Exit if accessed directly
defined('ABSPATH') || exit;

use Psr\Log\LogLevel;
use WP_Ultimo\Objects\Limitations;
use WP_Ultimo\Database\Sites\Site_Type;

/**
 * Handles processes related to limitations.
 *
 * @since 2.0.0
 */
class Limitation_Manager {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Instantiate the necessary hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {

		if (WP_Ultimo()->is_loaded() === false) {
			return;
		}

		add_filter('wu_product_options_sections', [$this, 'add_limitation_sections'], 10, 2);

		add_filter('wu_membership_options_sections', [$this, 'add_limitation_sections'], 10, 2);

		add_filter('wu_site_options_sections', [$this, 'add_limitation_sections'], 10, 2);

		add_action('plugins_loaded', [$this, 'register_forms']);

		add_action('wu_async_handle_plugins', [$this, 'async_handle_plugins'], 10, 5);

		add_action('wu_async_switch_theme', [$this, 'async_switch_theme'], 10, 2);
	}

	/**
	 * Handles async plugin activation and deactivation.
	 *
	 * @since 2.0.0
	 *
	 * @param string       $action The action to perform, can be either 'activate' or 'deactivate'.
	 * @param int          $site_id The site ID.
	 * @param string|array $plugins The plugin or list of plugins to (de)activate.
	 * @param boolean      $network_wide If we want to (de)activate it network-wide.
	 * @param boolean      $silent IF we should do the process silently - true by default.
	 * @return bool
	 */
	public function async_handle_plugins($action, $site_id, $plugins, $network_wide = false, $silent = true) {

		$results = false;

		// Avoid doing anything on the main site.
		if (wu_get_main_site_id() === $site_id) {
			return $results;
		}

		switch_to_blog($site_id);

		if ('activate' === $action) {
			$results = activate_plugins($plugins, '', $network_wide, $silent);
		} elseif ('deactivate' === $action) {
			$results = deactivate_plugins($plugins, $silent, $network_wide);
		}

		if (is_wp_error($results)) {
			wu_log_add('plugins', $results, LogLevel::ERROR);
		}

		restore_current_blog();

		return $results;
	}

	/**
	 * Switch themes via Job Queue.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $site_id The site ID.
	 * @param string $theme_stylesheet The theme stylesheet.
	 * @return true
	 */
	public function async_switch_theme($site_id, $theme_stylesheet): bool {

		switch_to_blog($site_id);

		switch_theme($theme_stylesheet);

		restore_current_blog();

		return true;
	}

	/**
	 * Register the modal windows to confirm resetting the limitations.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_forms(): void {

		wu_register_form(
			'confirm_limitations_reset',
			[
				'render'  => [$this, 'render_confirm_limitations_reset'],
				'handler' => [$this, 'handle_confirm_limitations_reset'],
			]
		);
	}

	/**
	 * Renders the conformation modal to reset limitations.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_confirm_limitations_reset(): void {

		$fields = [
			'confirm'       => [
				'type'      => 'toggle',
				'title'     => __('Confirm Reset', 'wp-multisite-waas'),
				'desc'      => __('This action can not be undone.', 'wp-multisite-waas'),
				'html_attr' => [
					'v-model' => 'confirmed',
				],
			],
			'submit_button' => [
				'type'            => 'submit',
				'title'           => __('Reset Limitations', 'wp-multisite-waas'),
				'value'           => 'save',
				'classes'         => 'button button-primary wu-w-full',
				'wrapper_classes' => 'wu-items-end',
				'html_attr'       => [
					'v-bind:disabled' => '!confirmed',
				],
			],
			'id'            => [
				'type'  => 'hidden',
				'value' => wu_request('id'),
			],
			'model'         => [
				'type'  => 'hidden',
				'value' => wu_request('model'),
			],
		];

		$form_attributes = [
			'title'                 => __('Reset', 'wp-multisite-waas'),
			'views'                 => 'admin-pages/fields',
			'classes'               => 'wu-modal-form wu-widget-list wu-striped wu-m-0 wu-mt-0',
			'field_wrapper_classes' => 'wu-w-full wu-box-border wu-items-center wu-flex wu-justify-between wu-p-4 wu-m-0 wu-border-t wu-border-l-0 wu-border-r-0 wu-border-b-0 wu-border-gray-300 wu-border-solid',
			'html_attr'             => [
				'data-wu-app' => 'reset_limitations',
				'data-state'  => wp_json_encode(
					[
						'confirmed' => false,
					]
				),
			],
		];

		$form = new \WP_Ultimo\UI\Form('reset_limitations', $fields, $form_attributes);

		$form->render();
	}

	/**
	 * Handles the reset of permissions.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_confirm_limitations_reset(): void {

		$id = wu_request('id');

		$model = wu_request('model');

		if ( ! $id || ! $model) {
			wp_send_json_error(
				new \WP_Error(
					'parameters-not-found',
					__('Required parameters are missing.', 'wp-multisite-waas')
				)
			);
		}

		/*
		 * Remove limitations object
		 */
		Limitations::remove_limitations($model, $id);

		wp_send_json_success(
			[
				'redirect_url' => wu_network_admin_url(
					"wp-ultimo-edit-{$model}",
					[
						'id'      => $id,
						'updated' => 1,
					]
				),
			]
		);
	}

	/**
	 * Returns the type of the object that has limitations.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Trait\Trait_Limitable $object Model to test.
	 * @return string
	 */
	public function get_object_type($object) {

		$model = false;

		if (is_a($object, \WP_Ultimo\Models\Site::class)) {
			$model = 'site';
		} elseif (is_a($object, WP_Ultimo\Models\Membership::class)) {
			$model = 'membership';
		} elseif (is_a($object, \WP_Ultimo\Models\Product::class)) {
			$model = 'product';
		}

		return apply_filters('wu_limitations_get_object_type', $model);
	}

	/**
	 * Injects the limitations panels when necessary.
	 *
	 * @since 2.0.0
	 *
	 * @param array                                   $sections List of tabbed widget sections.
	 * @param \WP_Ultimo\Models\Trait\Trait_Limitable $object The model being edited.
	 * @return array
	 */
	public function add_limitation_sections($sections, $object) {

		if ($this->get_object_type($object) === 'site' && $object->get_type() !== Site_Type::CUSTOMER_OWNED) {
			$html = sprintf('<span class="wu--mt-4 wu-p-2 wu-bg-blue-100 wu-text-blue-600 wu-rounded wu-block">%s</span>', __('Limitations are only available for customer-owned sites. You need to change the type to Customer-owned and save this site before the options are shown.', 'wp-multisite-waas'));

			$sections['sites'] = [
				'title'  => __('Limits', 'wp-multisite-waas'),
				'desc'   => __('Only customer-owned sites have limitations.', 'wp-multisite-waas'),
				'icon'   => 'dashicons-wu-browser',
				'fields' => [
					'note' => [
						'type'    => 'html',
						'content' => $html,
					],
				],
			];

			return $sections;
		}

		if ($this->get_object_type($object) !== 'site') {
			$sections['sites'] = [
				'title'  => __('Sites', 'wp-multisite-waas'),
				'desc'   => __('Control limitations imposed to the number of sites allowed for memberships attached to this product.', 'wp-multisite-waas'),
				'icon'   => 'dashicons-wu-browser',
				'fields' => $this->get_sites_fields($object),
				'v-show' => "get_state_value('product_type', 'none') !== 'service'",
				'state'  => [
					'limit_sites' => $object->get_limitations()->sites->is_enabled(),
				],
			];
		}

		/*
		 * Add Visits limitation control
		 */
		if ((bool) wu_get_setting('enable_visits_limiting', true)) {
			$sections['visits'] = [
				'title'  => __('Visits', 'wp-multisite-waas'),
				'desc'   => __('Control limitations imposed to the number of unique visitors allowed for memberships attached to this product.', 'wp-multisite-waas'),
				'icon'   => 'dashicons-wu-man',
				'v-show' => "get_state_value('product_type', 'none') !== 'service'",
				'state'  => [
					'limit_visits' => $object->get_limitations()->visits->is_enabled(),
				],
				'fields' => [
					'modules[visits][enabled]' => [
						'type'      => 'toggle',
						'title'     => __('Limit Unique Visits', 'wp-multisite-waas'),
						'desc'      => __('Toggle this option to enable unique visits limitation.', 'wp-multisite-waas'),
						'value'     => 10,
						'html_attr' => [
							'v-model' => 'limit_visits',
						],
					],
				],
			];

			if ('product' !== $object->model) {
				$sections['visits']['fields']['modules_visits_overwrite'] = $this->override_notice($object->get_limitations(false)->visits->has_own_enabled());
			}

			$sections['visits']['fields']['modules[visits][limit]'] = [
				'type'              => 'number',
				'title'             => __('Unique Visits Quota', 'wp-multisite-waas'),
				'desc'              => __('Set a top limit for the number of monthly unique visits. Leave empty or 0 to allow for unlimited visits.', 'wp-multisite-waas'),
				'placeholder'       => __('e.g. 10000', 'wp-multisite-waas'),
				'value'             => $object->get_limitations()->visits->get_limit(),
				'wrapper_html_attr' => [
					'v-show'  => 'limit_visits',
					'v-cloak' => '1',
				],
				'html_attr'         => [
					':min' => 'limit_visits ? 1 : -999',
				],
			];

			if ('product' !== $object->model) {
				$sections['visits']['fields']['allowed_visits_overwrite'] = $this->override_notice($object->get_limitations(false)->visits->has_own_limit(), ['limit_visits']);
			}

			/*
			 * If this is a site edit screen, show the current values
			 * for visits and the reset date
			 */
			if ($this->get_object_type($object) === 'site') {
				$sections['visits']['fields']['visits_count'] = [
					'type'              => 'text-display',
					'title'             => __('Current Unique Visits Count this Month', 'wp-multisite-waas'),
					'desc'              => __('Current visits count for this particular site.', 'wp-multisite-waas'),
					'display_value'     => sprintf('%s visit(s)', $object->get_visits_count()),
					'wrapper_html_attr' => [
						'v-show'  => 'limit_visits',
						'v-cloak' => '1',
					],
				];
			}
		}

		$sections['users'] = [
			'title'  => __('Users', 'wp-multisite-waas'),
			'desc'   => __('Control limitations imposed to the number of user allowed for memberships attached to this product.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-users',
			'v-show' => "get_state_value('product_type', 'none') !== 'service'",
			'state'  => [
				'limit_users' => $object->get_limitations()->users->is_enabled(),
			],
			'fields' => [
				'modules[users][enabled]' => [
					'type'      => 'toggle',
					'title'     => __('Limit User', 'wp-multisite-waas'),
					'desc'      => __('Enable user limitations for this product.', 'wp-multisite-waas'),
					'html_attr' => [
						'v-model' => 'limit_users',
					],
				],
			],
		];

		if ('product' !== $object->model) {
			$sections['users']['fields']['modules_user_overwrite'] = $this->override_notice($object->get_limitations(false)->users->has_own_enabled());
		}

		$this->register_user_fields($sections, $object);

		$sections['post_types'] = [
			'title'  => __('Post Types', 'wp-multisite-waas'),
			'desc'   => __('Control limitations imposed to the number of posts allowed for memberships attached to this product.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-book',
			'v-show' => "get_state_value('product_type', 'none') !== 'service'",
			'state'  => [
				'limit_post_types' => $object->get_limitations()->post_types->is_enabled(),
			],
			'fields' => [
				'modules[post_types][enabled]' => [
					'type'      => 'toggle',
					'title'     => __('Limit Post Types', 'wp-multisite-waas'),
					'desc'      => __('Toggle this option to set limits to each post type.', 'wp-multisite-waas'),
					'value'     => false,
					'html_attr' => [
						'v-model' => 'limit_post_types',
					],
				],
			],
		];

		if ('product' !== $object->model) {
			$sections['post_types']['fields']['post_quota_overwrite'] = $this->override_notice($object->get_limitations(false)->post_types->has_own_enabled());
		}

		$sections['post_types']['post_quota_note'] = [
			'type'              => 'note',
			'desc'              => __('<strong>Note:</strong> Using the fields below you can set a post limit for each of the post types activated. <br>Toggle the switch to <strong>deactivate</strong> the post type altogether. Leave 0 or blank for unlimited posts.', 'wp-multisite-waas'),
			'wrapper_html_attr' => [
				'v-show'  => 'limit_post_types',
				'v-cloak' => '1',
			],
		];

		$this->register_post_type_fields($sections, $object);

		$sections['limit_disk_space'] = [
			'title'  => __('Disk Space', 'wp-multisite-waas'),
			'desc'   => __('Control limitations imposed to the disk space allowed for memberships attached to this entity.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-drive',
			'v-show' => "get_state_value('product_type', 'none') !== 'service'",
			'state'  => [
				'limit_disk_space' => $object->get_limitations()->disk_space->is_enabled(),
			],
			'fields' => [
				'modules[disk_space][enabled]' => [
					'type'      => 'toggle',
					'title'     => __('Limit Disk Space per Site', 'wp-multisite-waas'),
					'desc'      => __('Enable disk space limitations for this entity.', 'wp-multisite-waas'),
					'value'     => true,
					'html_attr' => [
						'v-model' => 'limit_disk_space',
					],
				],
			],
		];

		if ('product' !== $object->model) {
			$sections['limit_disk_space']['fields']['disk_space_modules_overwrite'] = $this->override_notice($object->get_limitations(false)->disk_space->has_own_enabled());
		}

		$sections['limit_disk_space']['fields']['modules[disk_space][limit]'] = [
			'type'              => 'number',
			'title'             => __('Disk Space Allowance', 'wp-multisite-waas'),
			'desc'              => __('Set a limit in MBs for the disk space for <strong>each</strong> individual site.', 'wp-multisite-waas'),
			'min'               => 0,
			'placeholder'       => 100,
			'value'             => $object->get_limitations()->disk_space->get_limit(),
			'wrapper_html_attr' => [
				'v-show'  => "get_state_value('product_type', 'none') !== 'service' && limit_disk_space",
				'v-cloak' => '1',
			],
		];

		if ('product' !== $object->model) {
			$sections['limit_disk_space']['fields']['disk_space_override'] = $this->override_notice($object->get_limitations(false)->disk_space->has_own_limit(), ['limit_disk_space']);
		}

		$sections['custom_domain'] = [
			'title'  => __('Custom Domains', 'wp-multisite-waas'),
			'desc'   => __('Limit the number of users on each role, posts, pages, and more.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-link1',
			'v-show' => "get_state_value('product_type', 'none') !== 'service'",
			'state'  => [
				'allow_domain_mapping' => $object->get_limitations()->domain_mapping->is_enabled(),
			],
			'fields' => [
				'modules[domain_mapping][enabled]' => [
					'type'              => 'toggle',
					'title'             => __('Allow Custom Domains', 'wp-multisite-waas'),
					'desc'              => __('Toggle this option on to allow this plan to enable custom domains for sign-ups on this plan.', 'wp-multisite-waas'),
					'value'             => $object->get_limitations()->domain_mapping->is_enabled(),
					'wrapper_html_attr' => [
						'v-cloak' => '1',
					],
					'html_attr'         => [
						'v-model' => 'allow_domain_mapping',
					],
				],
			],
		];

		if ('product' !== $object->model) {
			$sections['custom_domain']['fields']['custom_domain_override'] = $this->override_notice($object->get_limitations(false)->domain_mapping->has_own_enabled(), ['allow_domain_mapping']);
		}

		$sections['allowed_themes'] = [
			'title'  => __('Themes', 'wp-multisite-waas'),
			'desc'   => __('Limit the number of users on each role, posts, pages, and more.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-palette',
			'v-show' => "get_state_value('product_type', 'none') !== 'service'",
			'state'  => [
				'force_active_theme' => '',
			],
			'fields' => [
				'themes' => [
					'type'    => 'html',
					'title'   => __('Themes', 'wp-multisite-waas'),
					'desc'    => __('Select how the themes installed on the network should behave.', 'wp-multisite-waas'),
					'content' => fn() => $this->get_theme_selection_list($object, $sections['allowed_themes']),
				],
			],
		];

		$sections['allowed_plugins'] = [
			'title'  => __('Plugins', 'wp-multisite-waas'),
			'desc'   => __('You can choose the behavior of each plugin installed on the platform.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-power-plug',
			'v-show' => "get_state_value('product_type', 'none') !== 'service'",
			'fields' => [
				'plugins' => [
					'type'    => 'html',
					'title'   => __('Plugins', 'wp-multisite-waas'),
					'desc'    => __('Select how the plugins installed on the network should behave.', 'wp-multisite-waas'),
					'content' => fn() => $this->get_plugin_selection_list($object),
				],
			],
		];

		$reset_url = wu_get_form_url(
			'confirm_limitations_reset',
			[
				'id'    => $object->get_id(),
				'model' => $object->model,
			]
		);

		$sections['reset_limitations'] = [
			'title'  => __('Reset Limitations', 'wp-multisite-waas'),
			'desc'   => __('Reset the limitations applied to this element.', 'wp-multisite-waas'),
			'icon'   => 'dashicons-wu-back-in-time',
			'fields' => [
				'reset_permissions' => [
					'type'  => 'note',
					'title' => sprintf("%s<span class='wu-normal-case wu-block wu-text-xs wu-font-normal wu-mt-1'>%s</span>", __('Reset Limitations', 'wp-multisite-waas'), __('Use this option to reset the custom limitations applied to this object.', 'wp-multisite-waas')),
					'desc'  => sprintf('<a href="%s" title="%s" class="wubox button-primary">%s</a>', $reset_url, __('Reset Limitations', 'wp-multisite-waas'), __('Reset Limitations', 'wp-multisite-waas')),
				],
			],
		];

		return $sections;
	}

	/**
	 * Generates the override notice.
	 *
	 * @since 2.0.0
	 *
	 * @param boolean $show Wether or not to show the field.
	 * @param array   $additional_checks Array containing javascript conditions that need to be met.
	 * @return array
	 */
	protected function override_notice($show = false, $additional_checks = []) {

		$text = sprintf('<p class="wu-m-0 wu-p-2 wu-bg-blue-100 wu-text-blue-600 wu-rounded">%s</p>', __('This value is being applied only to this entity. Changes made to the membership or product permissions will not affect this particular value.', 'wp-multisite-waas'));

		return [
			'desc'              => $text,
			'type'              => 'note',
			'wrapper_classes'   => 'wu-pt-0',
			'wrapper_html_attr' => [
				'v-show'  => ($additional_checks ? (implode(' && ', $additional_checks) . ' && ') : '') . var_export((bool) $show, true),
				'v-cloak' => '1',
				'style'   => 'border-top-width: 0 !important',
			],
		];
	}

	/**
	 * Register the user roles fields
	 *
	 * @since 2.0.0
	 *
	 * @param array                                   $sections Sections and fields.
	 * @param \WP_Ultimo\Models\Trait\Trait_Limitable $object The object being edit.
	 * @return void
	 */
	public function register_user_fields(&$sections, $object): void {

		$user_roles = get_editable_roles();

		$sections['users']['state']['roles'] = [];

		foreach ($user_roles as $user_role_slug => $user_role) {
			$sections['users']['state']['roles'][ $user_role_slug ] = $object->get_limitations()->users->{$user_role_slug};

			$sections['users']['fields'][ "control_{$user_role_slug}" ] = [
				'type'              => 'group',
				'title'             => sprintf(__('Limit %s Role', 'wp-multisite-waas'), $user_role['name']),
				'desc'              => sprintf(__('The customer will be able to create %s users(s) of this user role.', 'wp-multisite-waas'), "{{ roles['{$user_role_slug}'].enabled ? ( parseInt(roles['{$user_role_slug}'].number, 10) ? roles['{$user_role_slug}'].number : '" . __('unlimited', 'wp-multisite-waas') . "' ) : '" . __('no', 'wp-multisite-waas') . "' }}"),
				'tooltip'           => '',
				'wrapper_html_attr' => [
					'v-bind:class' => "!roles['{$user_role_slug}'].enabled ? 'wu-opacity-75' : ''",
					'v-show'       => 'limit_users',
					'v-cloak'      => '1',
				],
				'fields'            => [
					"modules[users][limit][{$user_role_slug}][number]" => [
						'type'            => 'number',
						'placeholder'     => sprintf(__('%s Role Quota. e.g. 10', 'wp-multisite-waas'), $user_role['name']),
						'min'             => 0,
						'wrapper_classes' => 'wu-w-full',
						'html_attr'       => [
							'v-model'         => "roles['{$user_role_slug}'].number",
							'v-bind:readonly' => "!roles['{$user_role_slug}'].enabled",
						],
					],
					"modules[users][limit][{$user_role_slug}][enabled]" => [
						'type'            => 'toggle',
						'wrapper_classes' => 'wu-mt-1',
						'html_attr'       => [
							'v-model' => "roles['{$user_role_slug}'].enabled",
						],
					],
				],
			];

			/*
			 * Add override notice.
			 */
			if ('product' !== $object->model) {
				$sections['users']['fields'][ "override_{$user_role_slug}" ] = $this->override_notice($object->get_limitations(false)->users->exists($user_role_slug), ['limit_users']);
			}
		}
	}

	/**
	 * Register the post type fields
	 *
	 * @since 2.0.0
	 *
	 * @param array                                   $sections Sections and fields.
	 * @param \WP_Ultimo\Models\Trait\Trait_Limitable $object The object being edit.
	 * @return void
	 */
	public function register_post_type_fields(&$sections, $object): void {

		$post_types = get_post_types([], 'objects');

		$sections['post_types']['state']['types'] = [];

		foreach ($post_types as $post_type_slug => $post_type) {
			$sections['post_types']['state']['types'][ $post_type_slug ] = $object->get_limitations()->post_types->{$post_type_slug};

			$sections['post_types']['fields'][ "control_{$post_type_slug}" ] = [
				'type'              => 'group',
				'title'             => sprintf(__('Limit %s', 'wp-multisite-waas'), $post_type->label),
				'desc'              => sprintf(__('The customer will be able to create %s post(s) of this post type.', 'wp-multisite-waas'), "{{ types['{$post_type_slug}'].enabled ? ( parseInt(types['{$post_type_slug}'].number, 10) ? types['{$post_type_slug}'].number : '" . __('unlimited', 'wp-multisite-waas') . "' ) : '" . __('no', 'wp-multisite-waas') . "' }}"),
				'tooltip'           => '',
				'wrapper_html_attr' => [
					'v-bind:class' => "!types['{$post_type_slug}'].enabled ? 'wu-opacity-75' : ''",
					'v-show'       => 'limit_post_types',
					'v-cloak'      => '1',
				],
				'fields'            => [
					"modules[post_types][limit][{$post_type_slug}][number]" => [
						'type'            => 'number',
						'placeholder'     => sprintf(__('%s Quota. e.g. 200', 'wp-multisite-waas'), $post_type->label),
						'min'             => 0,
						'wrapper_classes' => 'wu-w-full',
						'html_attr'       => [
							'v-model'         => "types['{$post_type_slug}'].number",
							'v-bind:readonly' => "!types['{$post_type_slug}'].enabled",
						],
					],
					"modules[post_types][limit][{$post_type_slug}][enabled]" => [
						'type'            => 'toggle',
						'wrapper_classes' => 'wu-mt-1',
						'html_attr'       => [
							'v-model' => "types['{$post_type_slug}'].enabled",
						],
					],
				],
			];

			/*
			 * Add override notice.
			 */
			if ('product' !== $object->model) {
				$sections['post_types']['fields'][ "override_{$post_type_slug}" ] = $this->override_notice(
					$object->get_limitations(false)->post_types->exists($post_type_slug),
					[
						'limit_post_types',
					]
				);
			}
		}
	}

	/**
	 * Returns the list of fields for the site tab.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Trait\Trait_Limitable $object The model being edited.
	 * @return array
	 */
	protected function get_sites_fields($object) {

		$fields = [
			'modules[sites][enabled]' => [
				'type'      => 'toggle',
				'title'     => __('Limit Sites', 'wp-multisite-waas'),
				'desc'      => __('Enable site limitations for this product.', 'wp-multisite-waas'),
				'value'     => $object->get_limitations()->sites->is_enabled(),
				'html_attr' => [
					'v-model' => 'limit_sites',
				],
			],
		];

		if ('product' !== $object->model) {
			$fields['sites_overwrite'] = $this->override_notice($object->get_limitations(false)->sites->has_own_enabled());
		}

		/*
		 * Sites not supported on this type
		 */
		$fields['site_not_allowed_note'] = [
			'type'              => 'note',
			'desc'              => __('The product type selection does not support allowing for the creating of extra sites.', 'wp-multisite-waas'),
			'tooltip'           => '',
			'wrapper_html_attr' => [
				'v-show'  => "get_state_value('product_type', 'none') === 'service' && limit_sites",
				'v-cloak' => '1',
			],
		];

		$fields['modules[sites][limit]'] = [
			'type'              => 'number',
			'min'               => 1,
			'title'             => __('Site Allowance', 'wp-multisite-waas'),
			'desc'              => __('This is the number of sites the customer will be able to create under this membership.', 'wp-multisite-waas'),
			'placeholder'       => 1,
			'value'             => $object->get_limitations()->sites->get_limit(),
			'wrapper_html_attr' => [
				'v-show'  => "get_state_value('product_type', 'none') !== 'service' && limit_sites",
				'v-cloak' => '1',
			],
		];

		if ('product' !== $object->model) {
			$fields['sites_overwrite_2'] = $this->override_notice($object->get_limitations(false)->sites->has_own_limit(), ["get_state_value('product_type', 'none') !== 'service' && limit_sites"]);
		}

		return apply_filters('wu_limitations_get_sites_fields', $fields, $object, $this);
	}

	/**
	 * Returns the HTML markup for the plugin selector list.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Trait\Trait_Limitable $object The model being edited.
	 * @return string
	 */
	public function get_plugin_selection_list($object) {

		$all_plugins = $this->get_all_plugins();

		return wu_get_template_contents(
			'limitations/plugin-selector',
			[
				'plugins' => $all_plugins,
				'object'  => $object,
			]
		);
	}

	/**
	 * Returns the HTML markup for the plugin selector list.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Ultimo\Models\Traits\Limitable $obj The model being edited.
	 * @param array                              $section The section array.
	 * @return string
	 */
	public function get_theme_selection_list($obj, &$section) {

		$all_themes = $this->get_all_themes();

		return wu_get_template_contents(
			'limitations/theme-selector',
			[
				'section' => $section,
				'themes'  => $all_themes,
				'object'  => $obj,
			]
		);
	}

	/**
	 * Returns a list of all plugins available as options, excluding WP Multisite WaaS.
	 *
	 * We also exclude a couple more.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_all_plugins() {

		$all_plugins = get_plugins();

		$listed_plugins = [];

		foreach ($all_plugins as $plugin_path => $plugin_info) {
			if (wu_get_isset($plugin_info, 'Network') === true) {
				continue;
			}

			if (in_array($plugin_path, $this->plugin_exclusion_list(), true)) {
				continue;
			}

			$listed_plugins[ $plugin_path ] = $plugin_info;
		}

		return $listed_plugins;
	}

	/**
	 * Returns a list of all themes available as options, after filtering.
	 *
	 * @since 2.0.0
	 */
	public function get_all_themes(): array {

		$all_plugins = wp_get_themes();

		return array_filter($all_plugins, fn($path) => ! in_array($path, $this->theme_exclusion_list(), true), ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Returns the exclusion list for plugins.
	 *
	 * We don't want people forcing WP Multisite WaaS to be deactivated, do we?
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function plugin_exclusion_list() {

		$exclusion_list = [
			'wp-ultimo/wp-ultimo.php',
			'user-switching/user-switching.php',
		];

		return apply_filters('wu_limitations_plugin_exclusion_list', $exclusion_list);
	}

	/**
	 * Returns the exclusion list for themes.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function theme_exclusion_list() {

		$exclusion_list = [];

		return apply_filters('wu_limitations_theme_exclusion_list', $exclusion_list);
	}
}
