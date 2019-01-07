<?php
/**
 * Plugin Name:  Contact Form 7 Dropbox
 * Description:  Contact Form 7 Add-on - Upload file on Dropbox.
 * Version:      1.2
 * Author:       Jay Swadas
 * Contributors: jayswadas
 * Requires at least: 4.0
 *
 *
 * @package Contact Form 7 Dropbox
 * @category Contact Form 7 Addon
 * @author Jay Swadas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WPCF7_Dropbox Class
 */
class WPCF7_Dropbox {
	/**
	 * Construct class
	 */
	public function __construct() {
		$this->plugin_url       = plugin_dir_url( __FILE__ );
		$this->plugin_path      = plugin_dir_path( __FILE__ );
		$this->version          = '1.0';
		$this->add_actions();
	}

	/**
	 * Add actions
	 */
	private function add_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'wpcf7_editor_panels', array( $this, 'add_panel' ) );
		add_action( 'wpcf7_after_save', array( $this, 'store_meta' ) );
		add_action( 'wpcf7_after_create', array( $this, 'duplicate_form_support' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Enqueue theme styles and scripts - front-end
	 */
	public function enqueue_frontend() {
		wp_enqueue_script( 'wpcf7-dropbox-min-script', $this->plugin_url . 'js/dropbox-sdk.min.js', array(), null, true );
		wp_enqueue_script( 'wpcf7-dropbox-script', $this->plugin_url . 'js/wpcf7-dropbox-script.js', array(), null, true );
		wp_localize_script( 'wpcf7-dropbox-script', 'wpcf7_dropbox_forms', $this->get_forms() );
	}

	/**
	 * Adds a tab on contact form edit page
	 *
	 * @param array $panels an array of panels.
	 */
	public function add_panel( $panels ) {
		$panels['dropbox-panel'] = array(
			'title'     => __( 'Dropbox Settings', 'wpcf7-dropbox' ),
			'callback'  => array( $this, 'create_panel_inputs' ),
		);
		return $panels;
	}

	/**
	 * Create plugin fields
	 *
	 * @return array of plugin fields: name and type
	 */
	public function get_plugin_fields() {
		$fields = array(
			array(
				'name' => 'access_token',
				'type' => 'text',
			),
			array(
				'name' => 'file_input',
				'type' => 'text',
			),
			array(
				'name' => 'folder',
				'type' => 'text',
			)
		);

		return $fields;
	}

	/**
	 * Get all fields values
	 *
	 * @param integer $post_id Form ID.
	 * @return array of fields values keyed by fields name
	 */
	public function get_fields_values( $post_id ) {
		$fields = $this->get_plugin_fields();

		foreach ( $fields as $field ) {
			$values[ $field['name'] ] = get_post_meta( $post_id, '_wpcf7_dropbox_' . $field['name'] , true );
		}

		return $values;
	}

	/**
	 * Validate and store meta data
	 *
	 * @param object $contact_form WPCF7_ContactForm Object - All data that is related to the form.
	 */
	public function store_meta( $contact_form ) {
		if ( ! isset( $_POST ) || empty( $_POST ) ) {
			return;
		} else {
			if ( ! wp_verify_nonce( $_POST['wpcf7_dropbox_page_metaboxes_nonce'], 'wpcf7_dropbox_page_metaboxes' ) ) {
				return;
			}

			$form_id = $contact_form->id();
			$fields = $this->get_plugin_fields( $form_id );
			$data = $_POST['wpcf7-dropbox'];

			foreach ( $fields as $field ) {
				$value = isset( $data[ $field['name'] ] ) ? $data[ $field['name'] ] : '';

				switch ( $field['type'] ) {
					case 'text':
						$value = sanitize_text_field( $value );
						break;
				}

				update_post_meta( $form_id, '_wpcf7_dropbox_' . $field['name'], $value );
			}
		}
	}

	/**
	 * Push all forms dropbox settings data into an array.
	 * @return array  Form dropbox settings data
	 */
	public function get_forms() {
		$args = array(
			'post_type' => 'wpcf7_contact_form',
			'posts_per_page' => -1,
		);
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :

			$fields = $this->get_plugin_fields();

			while ( $query->have_posts() ) : $query->the_post();

				$post_id = get_the_ID();

				foreach ( $fields as $field ) {
					$forms[ $post_id ][ $field['name'] ] = get_post_meta( $post_id, '_wpcf7_dropbox_' . $field['name'], true );
				}

			endwhile;
			wp_reset_postdata();

		endif;

		return $forms;
	}

	/**
	 * Copy dropbox page key and assign it to duplicate form
	 *
	 * @param object $contact_form WPCF7_ContactForm Object - All data that is related to the form.
	 */
	public function duplicate_form_support( $contact_form ) {
		$contact_form_id = $contact_form->id();

		if ( ! empty( $_REQUEST['post'] ) && ! empty( $_REQUEST['_wpnonce'] ) ) {
			$post_id = intval( $_REQUEST['post'] );

			$fields = $this->get_plugin_fields();

			foreach ( $fields as $field ) {
				update_post_meta( $contact_form_id, '_wpcf7_dropbox_' . $field['name'], get_post_meta( $post_id, '_wpcf7_dropbox_' . $field['name'], true ) );
			}
		}
	}

	/**
	 * Verify Contact Form 7 dependencies.
	 */
	public function admin_notice() {
		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			$wpcf7_path = plugin_dir_path( dirname( __FILE__ ) ) . 'contact-form-7/wp-contact-form-7.php';
			$wpcf7_data = get_plugin_data( $wpcf7_path, false, false );

			// If Contact Form 7 version is < 4.2.0.
			if ( $wpcf7_data['Version'] < 4.2 ) {
				?>

				<div class="error notice">
					<p>
						<?php esc_html_e( 'Error: Please update Contact Form 7.', 'wpcf7-dropbox' );?>
					</p>
				</div>

				<?php
			}
		} else {
			// If Contact Form 7 isn't installed and activated, throw an error.
			$wpcf7_path = plugin_dir_path( dirname( __FILE__ ) ) . 'contact-form-7/wp-contact-form-7.php';
			$wpcf7_data = get_plugin_data( $wpcf7_path, false, false );
			?>

			<div class="error notice">
				<p>
					<?php esc_html_e( 'Error: Please install and activate Contact Form 7.', 'wpcf7-dropbox' );?>
				</p>
			</div>

			<?php
		}
	}

	/**
	 * Create the panel inputs
	 *
	 * @param  object $post Post object.
	 */
	public function create_panel_inputs( $post ) {
		wp_nonce_field( 'wpcf7_dropbox_page_metaboxes', 'wpcf7_dropbox_page_metaboxes_nonce' );
		$fields = $this->get_fields_values( $post->id() );
		?>
		<fieldset>
			<legend><?php esc_html_e( 'Dropbox File Upload Settings', 'wpcf7-dropbox' );?></legend>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wpcf7-dropbox-access-token"><?php esc_html_e( 'Access Token', 'wpcf7-dropbox' );?></label>
						</th>
						<td>
							<input type="password" id="wpcf7-dropbox-access-token" class="large-text" placeholder="<?php esc_html_e( 'Access Token', 'wpcf7-dropbox' );?>" name="wpcf7-dropbox[access_token]" value="<?php echo $fields['access_token'];?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpcf7-dropbox-file-input"><?php esc_html_e( 'File Input IDs', 'wpcf7-dropbox' );?></label>
						</th>
						<td>
							<input type="text" id="wpcf7-dropbox-file-input" class="large-text" placeholder="<?php esc_html_e( 'File Input IDs', 'wpcf7-dropbox' );?>" name="wpcf7-dropbox[file_input]" value="<?php echo $fields['file_input'];?>">
							<small><?php esc_html_e( 'To upload multiple files using multiple id enter comma-separated value. For eg. FILE_ID1,FILE_ID2', 'wpcf7-dropbox' );?></small>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wpcf7-dropbox-file-input"><?php esc_html_e( 'Dropbox Folder', 'wpcf7-dropbox' );?></label>
						</th>
						<td>
							<input type="text" id="wpcf7-dropbox-folder" class="large-text" placeholder="<?php esc_html_e( 'Dropbox Folder', 'wpcf7-dropbox' );?>" name="wpcf7-dropbox[folder]" value="<?php echo $fields['folder'];?>">
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<?php
	}
}

$cf7_dropbox = new WPCF7_Dropbox();