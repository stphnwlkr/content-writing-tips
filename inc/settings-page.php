<?php
/**
 * Settings page and options.
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize settings before saving.
 *
 * Also handles optional cache flush when the flush button is used.
 *
 * @param array $input Raw input from the settings form.
 * @return array
 */
function sanitize_settings( $input ) {
	$settings = get_settings();

	if ( isset( $input['tip_count'] ) ) {
		$count = (int) $input['tip_count'];
		if ( in_array( $count, array( 5, 10, 15, 20 ), true ) ) {
			$settings['tip_count'] = $count;
		}
	}

	if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
		$cats = array_filter(
			array_map(
				'absint',
				$input['categories']
			)
		);
		$settings['categories'] = $cats;
	} elseif ( isset( $input['categories'] ) && empty( $input['categories'] ) ) {
		// Explicitly clear categories if nothing selected.
		$settings['categories'] = array();
	}

	// If the flush button was clicked, clear cache.
	if (
		isset( $_POST['vact_cwt_flush_cache'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		&& current_user_can( 'manage_options' )
	) {
		flush_tip_cache();
	}

	// No settings_errors() usage here – we print our own notice in render_settings_page().
	return $settings;
}

/**
 * Register the plugin settings.
 *
 * @return void
 */
function register_settings() {
	register_setting(
		'vact_cwt_settings',
		'vact_cwt_settings',
		array(
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_settings',
		)
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );

/**
 * Register the settings page under Settings → Content Writing Tips.
 *
 * @return void
 */
function register_settings_page() {
	add_options_page(
		esc_html__( 'Content Writing Tips', 'vact-content-writing-tips' ),
		esc_html__( 'Content Writing Tips', 'vact-content-writing-tips' ),
		'manage_options',
		'vact-content-writing-tips',
		__NAMESPACE__ . '\\render_settings_page'
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\\register_settings_page' );

/**
 * Render the settings page.
 *
 * @return void
 */
function render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings    = get_settings();
	$tip_count   = $settings['tip_count'];
	$selected    = $settings['categories'];
	$categories  = get_tip_categories();
	$mailto_href = 'mailto:vawordpressadmin@va.gov?subject=' . rawurlencode( 'Content Writing Tip suggestion' );

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Content Writing Tips Settings', 'vact-content-writing-tips' ); ?></h1>


		<h2><?php esc_html_e( 'How to use Content Writing Tips', 'vact-content-writing-tips' ); ?></h2>

		<p>
			<?php esc_html_e( 'This plugin pulls content writing tips from a central location and makes them available in your WordPress dashboard and on your site.', 'vact-content-writing-tips' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'vact_cwt_settings' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Number of tips per day', 'vact-content-writing-tips' ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<?php esc_html_e( 'Number of tips per day', 'vact-content-writing-tips' ); ?>
								</legend>

								<?php
								$choices = array( 5, 10, 15, 20 );
								foreach ( $choices as $choice ) :
									?>
									<label>
										<input type="radio" name="vact_cwt_settings[tip_count]" value="<?php echo esc_attr( $choice ); ?>" <?php checked( $tip_count, $choice ); ?> />
										<?php echo esc_html( $choice ); ?>
									</label><br />
									<?php
								endforeach;
								?>
								<p class="description">
									<?php esc_html_e( 'Controls how many random tips are selected each day and displayed in the dashboard slider (and as the default slider size).', 'vact-content-writing-tips' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Tip categories to include', 'vact-content-writing-tips' ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<?php esc_html_e( 'Tip categories to include', 'vact-content-writing-tips' ); ?>
								</legend>

								<?php
								if ( is_wp_error( $categories ) ) {
									echo '<p class="description">' . esc_html( $categories->get_error_message() ) . '</p>';
								} elseif ( empty( $categories ) ) {
									echo '<p class="description">' . esc_html__( 'No categories were found from the remote endpoint.', 'vact-content-writing-tips' ) . '</p>';
								} else {
									// If no categories are saved yet, treat as "all selected".
									$no_selection = empty( $selected );

									foreach ( $categories as $cat ) {
										if ( ! isset( $cat->id, $cat->name ) ) {
											continue;
										}
										$id      = (int) $cat->id;
										$name    = (string) $cat->name;
										$checked = $no_selection || in_array( $id, $selected, true );
										?>
										<label>
											<input type="checkbox" name="vact_cwt_settings[categories][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( $checked ); ?> />
											<?php echo esc_html( $name ); ?>
										</label><br />
										<?php
									}
									?>
									<p class="description">
										<?php esc_html_e( 'If no categories are selected, tips from all categories are eligible.', 'vact-content-writing-tips' ); ?>
									</p>
									<?php
								}
								?>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Tip cache', 'vact-content-writing-tips' ); ?>
						</th>
						<td>
							<p class="description">
								<?php esc_html_e( 'Tips are cached once per day to reduce API calls. Use this button to clear today\'s cache and force a fresh set of tips to be loaded.', 'vact-content-writing-tips' ); ?>
							</p>
							<button type="submit" name="vact_cwt_flush_cache" class="button button-secondary">
								<?php esc_html_e( 'Flush tip cache', 'vact-content-writing-tips' ); ?>
							</button>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Save changes', 'vact-content-writing-tips' ) ); ?>
		</form>

		<hr>

		<h3><?php esc_html_e( 'Dashboard widget', 'vact-content-writing-tips' ); ?></h3>
		<p>
			<?php esc_html_e( 'On the main Dashboard screen, look for the “Content Writing Tips” widget. It shows a slider of one or more tips each day. Use the Previous and Next buttons to move between tips.', 'vact-content-writing-tips' ); ?>
		</p>

		<h3><?php esc_html_e( 'Shortcode', 'vact-content-writing-tips' ); ?></h3>
		<p>
			<?php esc_html_e( 'You can display tips in posts, pages, or widgets using this shortcode:', 'vact-content-writing-tips' ); ?>
		</p>
		<pre><code>[vact_tip_of_the_day]</code></pre>
		<p>
			<?php esc_html_e( 'By default this shows a single tip. To show a slider of multiple tips, use:', 'vact-content-writing-tips' ); ?>
		</p>
		<pre><code>[vact_tip_of_the_day mode="slider" count="5"]</code></pre>
		<p>
			<?php esc_html_e( 'You can change the count value from 1 to 20. Tips are drawn from the same daily pool controlled by the settings above.', 'vact-content-writing-tips' ); ?>
		</p>

		<h3><?php esc_html_e( 'Gutenberg block', 'vact-content-writing-tips' ); ?></h3>
		<p>
			<?php esc_html_e( 'In the block editor, add the block named “Content Writing Tip of the Day”. In the block settings sidebar, choose whether to display a single tip or a slider, and how many tips to show in slider mode.', 'vact-content-writing-tips' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'The block uses the same daily pool of tips and category filters configured in the settings above.', 'vact-content-writing-tips' ); ?>
		</p>

		<p>
			<a href="https://digital.va.gov/wpnavigator/content-tips/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Visit WP Navigator for more tips.', 'vact-content-writing-tips' ); ?>
			</a>
			<?php esc_html_e( ' or ', 'vact-content-writing-tips' ); ?>
			<a href="<?php echo esc_url( $mailto_href ); ?>">
				<?php esc_html_e( 'email vawordpressadmin@va.gov to suggest a new tip.', 'vact-content-writing-tips' ); ?>
			</a>
		</p>
	</div>
	<?php
}