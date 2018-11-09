<?php
/**
 * A WordPress plugin which adds rich text editing capability for excerpts
 * Plugin Name: Rich Text Excerpts
 * Plugin URI: http://wordpress.org/extend/plugins/rich-text-excerpts/
 * Description: Adds rich text editing capability for excerpts using wp_editor()
 * Author: Peter Edwards <pete@bjorsq.net>
 * Author URI: https://github.com/bjorsq/rich-text-excerpts
 * Version: 1.3.4
 * Text Domain: rich-text-excerpts
 * License: GPLv3
 *
 * @package Rich_Text_Excerpts
 */

/*

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! class_exists( 'Rich_Text_Excerpts' ) ) {
	/**
	 * Class to encapsulate all functions in the plugin
	 */
	class Rich_Text_Excerpts {
		/**
		 * Plugin version
		 *
		 * @var string semver version number
		 */
		private $version = '1.3.4';

		/**
		 * Constructor - adds actions and filters using the WordPress API
		 */
		public function __construct() {
			/**
			 * Bail if Gutenberg is being used
			 */
			if ( $this->is_gutenberg_active() ) {
				return;
			}

			/**
			 * Adds an action to remove the default meta box
			 * just after it is added to the page
			 */
			add_action( 'add_meta_boxes', array( $this, 'remove_excerpt_meta_box' ), 1, 1 );

			/**
			 * Get the plugin options
			 */
			$plugin_options = $this->get_plugin_options();

			/**
			 * Adding a richtext editor to a sortable postbox has only been tested in 3.5
			 */
			add_action( 'add_meta_boxes', array( $this, 'add_richtext_excerpt_editor_metabox' ) );

			/**
			 * Filters to customise the teeny mce editor
			 */
			add_filter( 'teeny_mce_plugins', array( $this, 'teeny_mce_plugins' ), 10, 2 );
			add_filter( 'teeny_mce_buttons', array( $this, 'teeny_mce_buttons' ), 10, 2 );

			/**
			 * Register plugin admin options
			 */
			add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_plugin_options' ) );

			/**
			 * Add a link to the settings page from the plugins page
			*/
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_page_link' ), 10, 2 );

			/**
			 * Register text domain
			 */
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

			/**
			 * Activate/deactivate
			 */
			register_activation_hook( __FILE__, array( __CLASS__, 'on_activation' ) );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'on_deactivation' ) );
		}

		/**
		 * Auto-deactivates plugin and displays admin notice
		 */
		public function auto_deactivate_plugin() {
			deactivate_plugins( dirname( __FILE__ ) . '/rich-text-excerpts.php' );
			add_action( 'admin_notices', function() {
				print( '<div class="notice notice-error is-dismissible"><p>' );
				esc_html_e( 'Sorry, Rich Text Excerpts does not work with the Gutenberg editor enabled', 'rich-text-excerpts' );
				print( '</p></div>' );
			}, 10);
		}

		public function is_gutenberg_active() {
			$gutenberg = false;
			$block_editor = false;

			if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) {
				// Gutenberg is installed and activated.
				$gutenberg = true;
			}

			if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) {
				// Block editor.
				$block_editor = true;
			}

			if ( ! $gutenberg && ! $block_editor ) {
				return false;
			}

			$replace = ( get_option( 'classic-editor-replace' ) !== 'no-replace' );

			if ( $block_editor && ( $replace || isset( $_GET['classic-editor'] ) ) ) {
				return false;
			}

			if ( $gutenberg && ( $replace || isset( $_GET['classic-editor'] ) ) ) {
				return true;
			}
			return $gutenberg;
		}

		/**
		 * Internationalisation
		 */
		public function load_text_domain() {
			load_plugin_textdomain( 'rich-text-excerpts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Store default options for plugin on activation
		 */
		public static function on_activation() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			update_option( 'rich_text_excerpts_options', self::get_default_plugin_options() );
		}

		/**
		 * Remove plugin options on deactivation
		 */
		public static function on_deactivation() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			delete_option( 'rich_text_excerpts_options' );
		}

		/**
		 * Determines whether the post type has support for excerpts,
		 * and whether the plugin is configured to be used for that post type
		 *
		 * @param string $post_type slug for post type.
		 */
		public function post_type_supported( $post_type ) {
			$plugin_options = $this->get_plugin_options();
			return ( post_type_supports( $post_type, 'excerpt' ) && in_array( $post_type, $plugin_options['supported_post_types'], true ) );
		}

		/**
		 * Removes the excerpt meta box normally used to edit excerpts
		 *
		 * @param string $post_type slug for post type.
		 */
		public function remove_excerpt_meta_box( $post_type ) {
			if ( $this->post_type_supported( $post_type ) ) {
				remove_meta_box( 'postexcerpt', $post_type, 'normal' );
			}
		}

		/**
		 * Adds a rich text editor to edit excerpts
		 * includes a sanity check to see if the post type supports them first
		 */
		public function add_richtext_excerpt_editor() {
			global $post;
			if ( $this->post_type_supported( $post->post_type ) ) {
				$this->post_excerpt_editor();
			}
		}

		/**
		 * Adds a rich text editor in a metabox
		 */
		public static function add_richtext_excerpt_editor_metabox() {
			$plugin_options = $this->get_plugin_options();
			foreach ( $plugin_options['supported_post_types'] as $post_type ) {
				add_meta_box(
					'richtext_excerpt_editor_metabox',
					__( 'Excerpt', 'rich-text-excerpts' ),
					array( $this, 'post_excerpt_editor' ),
					$post_type,
					$plugin_options['metabox']['context'],
					$plugin_options['metabox']['priority']
				);
			}
		}

		/**
		 * Prints the post excerpt form field (using wp_editor()).
		 */
		public function post_excerpt_editor() {
			global $post;
			if ( $post && $post->post_excerpt ) {
				$excerpt = $post->post_excerpt;
			} else {
				$excerpt = '';
			}
			$plugin_options = $this->get_plugin_options();
			/* Wrap to identify presence of metabox to scripts so they can disable the editor when sorting takes place */
			print( '<div class="rte-wrap-metabox">' );
			/* Options for editor */
			$options = array(
				'wpautop'       => $plugin_options['editor_settings']['wpautop'],
				'media_buttons' => $plugin_options['editor_settings']['media_buttons'],
				'textarea_name' => 'excerpt',
				'textarea_rows' => $plugin_options['editor_settings']['textarea_rows'],
				'editor_height' => $plugin_options['editor_settings']['editor_height'],
				'teeny'         => ( 'teeny' === $plugin_options['editor_type'] ) ? true : false,
			);
			/* get decoded content for the editor */
			$excerpt = html_entity_decode( $excerpt );
			/**
			 * This will decode numeric entities
			 *
			 * @see http://wordpress.org/support/topic/special-characters-show-as-their-character-codes
			 */
			$excerpt = wp_kses_decode_entities( $excerpt );
			/* output editor */
			wp_editor( $excerpt, 'excerpt', $options );
			print( '</div>' );
		}

		/**
		 * Filter to add plugins for the "teeny" editor
		 *
		 * @param array  $plugins - array of plugin names loading into editor.
		 * @param string $editor_id ID of current editor.
		 * @return array $plugins - possibly altered array of plugin names loading into editor.
		 */
		public function teeny_mce_plugins( $plugins, $editor_id ) {
			$plugin_options = $this->get_plugin_options();
			if ( count( $plugin_options['editor_settings']['plugins'] ) ) {
				foreach ( $plugin_options['editor_settings']['plugins'] as $plugin_name ) {
					if ( ! isset( $plugins[ $plugin_name ] ) ) {
						array_push( $plugins, $plugin_name );
					}
				}
			}
			return $plugins;
		}

		/**
		 * Filter to add buttons to the "teeny" editor
		 * this completely disregards the buttons array passed to it and returns a new array
		 *
		 * @param array  $buttons - array of button names loading into editor.
		 * @param string $editor_id ID of current editor.
		 * @return array $buttons - button names set by plugin.
		 */
		public function teeny_mce_buttons( $buttons, $editor_id ) {
			$plugin_options = $this->get_plugin_options();
			return $plugin_options['editor_settings']['buttons'];
		}

		/**
		 * Adds a link to the settings page from the plugins listing page
		 * called using the plugin_action_links filter
		 *
		 * @param array  $links - array of HTML anchor tags to display under plugin title on hover.
		 * @param string $file - name of the plugin file for the current plugin.
		 * @return array $links - possibly altered  array of HTML anchor tags to display under plugin title on hover.
		 */
		public function add_settings_page_link( $links, $file ) {
			if ( plugin_basename( __FILE__ ) === $file ) {
				$settings_page_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=rich_text_excerpts_options' ), __( 'Settings', 'rich-text-excerpts' ) );
				$links[]            = $settings_page_link;
			}
			return $links;
		}

		/**
		 * Add an admin page under settings to configure the plugin
		 */
		public function add_plugin_admin_menu() {
			/* Plugin Options page */
			$options_page = add_submenu_page(
				'options-general.php',
				__( 'Rich Text Excerpts', 'rich-text-excerpts' ),
				__( 'Rich Text Excerpts', 'rich-text-excerpts' ),
				'manage_options',
				'rich_text_excerpts_options',
				array( $this, 'plugin_options_page' )
			);

			/**
			 * Use the admin_print_scripts action to add scripts.
			 * Admin script is only needed on plugin admin page, but editor script is needed on all pages
			 * which include the editor
			 */
			add_action( 'admin_print_scripts-' . $options_page, array( $this, 'plugin_admin_scripts' ) );
			add_action( 'admin_print_scripts', array( $this, 'plugin_editor_scripts' ) );

			/**
			 * Use the admin_print_styles action to add CSS.
			 * CSS is needed for the post/page editor only
			 */
			add_action( 'admin_print_styles', array( $this, 'plugin_admin_styles' ) );
		}

		/**
		 * Add script to admin for plugin options
		 */
		public function plugin_admin_scripts() {
			wp_enqueue_script(
				'RichTextExcerptsAdminScript',
				plugins_url( 'rich-text-excerpts.js', __FILE__ ),
				array( 'jquery' ),
				$this->version(),
				true
			);
		}

		/**
		 * Add script to editor page for metabox
		 */
		public function plugin_editor_scripts() {
			$screen = get_current_screen();
			if ( $this->post_type_supported( $screen->post_type ) ) {
				wp_enqueue_script(
					'RichTextExcerptsEditorScript',
					plugins_url( 'rich-text-excerpts-editor.js', __FILE__ ),
					array( 'jquery' ),
					$this->version(),
					true
				);
			}
		}

		/**
		 * Add css to admin for editor formatting
		 */
		public function plugin_admin_styles() {
			$screen = get_current_screen();
			if ( $this->post_type_supported( $screen->post_type ) ) {
				wp_enqueue_style(
					'RichTextExcerptsAdminCSS',
					plugins_url( 'rich-text-excerpts.css', __FILE__ ),
					array(),
					$this->version()
				);
			}
		}

		/**
		 * Creates the options page
		 */
		public function plugin_options_page() {
			printf( '<div class="wrap"><h2>%s</h2>', esc_html__( 'Rich Text Excerpts Options', 'rich-text-excerpts' ) );
			settings_errors( 'rich_text_excerpts_options' );
			printf( '<form method="post" action="%s" id="rich_text_excerpts_options_form">', esc_url( admin_url( 'options.php' ) ) );
			settings_fields( 'rich_text_excerpts_options' );
			do_settings_sections( 'rte' );
			printf( '<p class="submit"><input type="submit" class="button-primary" name="Submit" value="%s" /></p>', esc_html__( 'Save Changes', 'rich-text-excerpts' ) );
			print( '</form></div>' );
		}

		/**
		 * Registers settings and sections
		 */
		public function register_plugin_options() {
			register_setting(
				'rich_text_excerpts_options',
				'rich_text_excerpts_options',
				array( $this, 'validate_rich_text_excerpts_options' )
			);

			/* post type and metabox options */
			add_settings_section(
				'post-type-options',
				__( 'Post Types', 'rich-text-excerpts' ),
				array( $this, 'options_section_text' ),
				'rte'
			);

			add_settings_field(
				'supported_post_types',
				__( 'Choose which post types will use a rich text editor for excerpts', 'rich-text-excerpts' ),
				array( $this, 'options_setting_post_types' ),
				'rte',
				'post-type-options'
			);

			/* editor options */
			add_settings_section(
				'editor-options',
				__( 'Editor Options', 'rich-text-excerpts' ),
				'__return_empty_string',
				'rte'
			);

			add_settings_field(
				'editor_type',
				__( 'Choose which Editor is used for excerpts', 'rich-text-excerpts' ),
				array( $this, 'options_setting_editor_type' ),
				'rte',
				'editor-options'
			);

			add_settings_field(
				'metabox',
				__( 'Meta box', 'rich-text-excerpts' ),
				array( $this, 'options_setting_metabox' ),
				'rte',
				'editor-options'
			);

			/* settings for editor */
			add_settings_field(
				'editor_settings',
				__( 'Editor Settings', 'rich-text-excerpts' ),
				array( $this, 'options_editor_settings' ),
				'rte',
				'editor-options'
			);
		}

		/**
		 * Gets plugin options - merges saved options with defaults
		 *
		 * @return array associative array of options
		 */
		public function get_plugin_options() {
			$saved = get_option( 'rich_text_excerpts_options' );
			return $this->validate_rich_text_excerpts_options( $saved );
		}

		/**
		 * Gets default plugin options
		 */
		public static function get_default_plugin_options() {
			return array(
				'supported_post_types' => array( 'post' ),
				'editor_type'          => 'teeny',
				'metabox'              => array(
					'context'  => 'normal',
					'priority' => 'high',
				),
				'editor_settings'      => array(
					'wpautop'       => true,
					'media_buttons' => false,
					'textarea_rows' => 3,
					'editor_height' => 150,
					'buttons'       => array( 'bold', 'italic', 'underline', 'separator', 'pastetext', 'pasteword', 'removeformat', 'separator', 'charmap', 'blockquote', 'separator', 'bullist', 'numlist', 'separator', 'justifyleft', 'justifycenter', 'justifyright', 'separator', 'undo', 'redo', 'separator', 'link', 'unlink' ),
					'plugins'       => array( 'charmap', 'paste' ),
				),
			);
		}

		/**
		 * Post type support settings
		 */
		public function options_setting_post_types() {
			$options    = $this->get_plugin_options();
			$post_types = get_post_types( array( 'ublic' => true ), 'objects' );
			foreach ( $post_types as $post_type ) {
				if ( post_type_supports( $post_type->name, 'excerpt' ) ) {
					$chckd = ( in_array( $post_type->name, $options['supported_post_types'], true ) ) ? ' checked' : '';
					printf( '<p class="rte-post-types-inputs"><input class="rte-post-types" type="checkbox" name="rich_text_excerpts_options[supported_post_types][]" id="supported_post_types-%s" value="%s"%s /> <label for="supported_post_types-%s">%s</label></p>', esc_attr( $post_type->name ), esc_attr( $post_type->name ), esc_attr( $chckd ), esc_attr( $post_type->name ), esc_html( $post_type->labels->name ) );
				}
			}
			printf( '<div class="rte-post-types-error" style="background-color: rgb(255, 255, 224);border:1px solid rgb(230, 219, 85); border-radius:3px;	color: rgb(51, 51, 51);	padding: 4px 0.5em;	display:none;"></p>%s</p></div>', esc_html__( 'If you want to disable support for all post types, please disable the plugin', 'rich-text-excerpts' ) );
			printf( '<p>%s<br /><a href="http://codex.wordpress.org/Function_Reference/add_post_type_support">add_post_type_support()</a></p>', esc_html__( 'Post types not selected here will use the regular plain text editor for excerpts. If the post type you want is not listed here, it does not currently support excerpts - to add support for excerpts to a post type, see the WordPress Codex', 'rich-text-excerpts' ) );
		}

		/**
		 * Meta box support settings
		 */
		public function options_setting_metabox() {
			$options = $this->get_plugin_options();
			print( '<div id="rte-metabox-settings">' );

			/* metabox context settings */
			$contexts = array(
				'normal',
				'advanced',
				'side',
			);
			print( '<p><label for="rte-metabox-context"><select name="rich_text_excerpts_options[metabox][context]">' );
			foreach ( $contexts as $context ) {
				$sel = ( $options['metabox']['context'] === $context ) ? ' selected' : '';
				printf( '<option value="%s"%s>%s</option>', esc_attr( $context ), esc_attr( $sel ), esc_html( $context ) );
			}
			printf( '</select> %s</p>', esc_html__( 'Set the part of the page where the excerpt editor should be shown', 'rich-text-excerpts' ) );

			/* metabox priority settings */
			$priorities = array(
				'high',
				'core',
				'default',
				'low',
			);
			print( '<p><label for="rte-metabox-priority"><select name="rich_text_excerpts_options[metabox][priority]">' );
			foreach ( $priorities as $priority ) {
				$sel = ( $options['metabox']['priority'] === $priority ) ? ' selected' : '';
				printf( '<option value="%s"%s>%s</option>', esc_attr( $priority ), esc_attr( $sel ), esc_html( $priority ) );
			}
			printf( '</select> %s</p>', esc_html__( 'Set the priority of the excerpt editor', 'rich-text-excerpts' ) );
			print( '</div>' );
		}

		/**
		 * Editor type radios
		 */
		public function options_setting_editor_type() {
			$options = $this->get_plugin_options();
			$chckd   = ( 'teeny' === $options['editor_type'] ) ? ' checked' : '';
			printf( '<p><label for="rich_text_excerpts_options-editor_type-teeny"><input type="radio" name="rich_text_excerpts_options[editor_type]" id="rich_text_excerpts_options-editor_type-teeny" class="rte-options-editor-type" value="teeny"%s /> %s</label></p>', esc_attr( $chckd ), esc_html__( 'Use the minimal editor configuration used in PressThis', 'rich-text-excerpts' ) );
			$chckd = ( 'teeny' === $options['editor_type'] ) ? '' : ' checked';
			printf( '<p><label for="rich_text_excerpts_options-editor_type-tiny"><input type="radio" name="rich_text_excerpts_options[editor_type]" id="rich_text_excerpts_options-editor_type-tiny" class="rte-options-editor-type" value="tiny"%s /> %s</label></p>', esc_attr( $chckd ), esc_html__( 'Use the full version of the editor', 'rich-text-excerpts' ) );
			printf( '<p>%s.</p>', esc_html__( 'Choose whether to use the full TinyMCE editor, or the &ldquo;teeny&rdquo; version of the editor.', 'rich-text-excerpts' ) );
		}

		/**
		 * Settings for text editor
		 * Follows the WordPress wp_editor function. Arguments not implemented are:
		 *  - tabindex - may be a way to find out what this should be for a metabox and pass to wp_editor automatically?
		 *  - editor_css - Additional CSS styling applied for both visual and HTML editors buttons, needs to include <style> tags, can use "scoped" (hard to validate)
		 *  - editor_class - Any extra CSS Classes to append to the Editor textarea (could be useful?)
		 *  - dfw - Whether to replace the default fullscreen editor with DFW (needs specific DOM elements and css)
		 *  - tinymce - Load TinyMCE, can be used to pass settings directly to TinyMCE using an array() - direct people to TinyMCE Advanced rther than implement this
		 *  - quicktags - Load Quicktags, can be used to pass settings directly to Quicktags using an array() (could be useful? does TA handle quicktags?)
		 *
		 * @see http://codex.wordpress.org/Function_Reference/wp_editor
		 */
		public function options_editor_settings() {
			$options = $this->get_plugin_options();
			$chckd   = ( $options['editor_settings']['wpautop'] ) ? '' : ' checked';
			printf( '<p><input type="checkbox" name="rich_text_excerpts_options[editor_settings][wpautop]" id="rich_text_excerpts_options-editor_settings-wpautop" value="0"%s /> <label for="rich_text_excerpts_options-editor_settings-wpautop">%s.</label></p>', esc_attr( $chckd ), esc_html__( 'Stop removing the &lt;p&gt; and &lt;br&gt; tags when saving and show them in the HTML editor This will make it possible to use more advanced coding in the HTML editor without the back-end filtering affecting it much. However it may behave unexpectedly in rare cases, so test it thoroughly before enabling it permanently', 'rich-text-excerpts' ) );
			$chckd = ( $options['editor_settings']['media_buttons'] ) ? ' checked' : '';
			printf( '<p><input type="checkbox" name="rich_text_excerpts_options[editor_settings][media_buttons]" id="rich_text_excerpts_options-editor_settings-media_buttons"%s /> <label for="rich_text_excerpts_options-editor_settings-media_buttons">%s</label></p>', esc_attr( $chckd ), esc_html__( 'Enable upload media button', 'rich-text-excerpts' ) );
			printf( '<p><input type="text" length="2" name="rich_text_excerpts_options[editor_settings][textarea_rows]" id="rich_text_excerpts_options-editor_settings-textarea_rows" value="%d" /> <label for="rich_text_excerpts_options-editor_settings-textarea_rows">%s</label></p>', esc_attr( intVal( $options['editor_settings']['textarea_rows'] ) ), esc_html__( 'Number of rows to use in the text editor (minimum is 3)', 'rich-text-excerpts' ) );
			printf( '<p><input type="text" length="4" name="rich_text_excerpts_options[editor_settings][editor_height]" id="rich_text_excerpts_options-editor_settings-editor_height" value="%d" /> <label for="rich_text_excerpts_options-editor_settings-editor_height">%s</label></p>', esc_attr( intVal( $options['editor_settings']['editor_height'] ) ), esc_html__( 'Height of editor in pixels (between 50 and 5000)', 'rich-text-excerpts' ) );
			printf( '<p><strong>%s</strong></p>', esc_html__( 'Toolbar Buttons and Plugins', 'rich-text-excerpts' ) );

			/**
			 * Settings for teeny text editor
			 */
			print( '<div id="editor_type_teeny_options">' );
			printf( '<p>%s.<br /><a href="http://www.tinymce.com/wiki.php/Buttons/controls">http://www.tinymce.com/wiki.php/Buttons/controls</a><br />%s<br /><a href="http://codex.wordpress.org/TinyMCE">http://codex.wordpress.org/TinyMCE</a><br />%s.</p>', esc_html__( 'For a list of buttons and plugins in TinyMCE, see the TinyMCE wiki', 'rich-text-excerpts' ), esc_html__( 'There is also some documentation on the implementation of TinyMCE in WordPress on the WordPress Codex', 'rich-text-excerpts' ), esc_html__( 'Button and plugin names should be separated using commas', 'rich-text-excerpts' ) );
			printf( '<p><label for="rich_text_excerpts_options-editor_settings-plugins">%s.</label><br /><input type="text" length="50" name="rich_text_excerpts_options[editor_settings][plugins]" id="rich_text_excerpts_options-editor_settings-plugins" value="%s" /></p>', esc_html__( 'Plugins to add - make sure you add any plugin specific buttons to the editor below', 'rich-text-excerpts' ), esc_attr( implode( ',', $options['editor_settings']['plugins'] ) ) );
			printf( '<p><label for="rich_text_excerpts_options-editor_settings-buttons">%s</label><br /><textarea name="rich_text_excerpts_options[editor_settings][buttons]" id="rich_text_excerpts_options-editor_settings-buttons" cols="100" rows="3">%s</textarea></p>', esc_html__( 'Toolbar buttons - use the word &lsquo;separator&rsquo; to separate groups of buttons', 'rich-text-excerpts' ), esc_attr( implode( ',', $options['editor_settings']['buttons'] ) ) );
			print( '</div>' );

			/**
			 * Settings for tiny text editor (none to show here, but show links to TinyMCE advanced)
			 */
			print( '<div id="editor_type_tiny_options">' );
			if ( is_plugin_active( 'tinymce-advanced/tinymce-advanced.php' ) ) {
				printf( '<p><a href="%s">%s</a>.</p>', esc_url( admin_url( 'options-general.php?page=tinymce-advanced' ) ), esc_html__( 'Configure the buttons for the advanced editor using the TinyMCE Advanced plugin', 'rich-text-excerpts' ) );
			} else {
				printf( '<p><a href="%s">%s</a>.</p>', esc_url( admin_url( 'plugins.php' ) ), esc_html__( 'If you want to configure the buttons for the advanced editor, install and activate the TinyMCE Advanced plugin', 'rich-text-excerpts' ) );
			}
			print( '</div>' );
		}

		/**
		 * Takes a string of comma-separated arguments and splits it into an array
		 *
		 * @param string $str - comma-separated text.
		 * @return array Array of strings separated at commas.
		 */
		public function get_mce_array( $str = '' ) {
			if ( '' === trim( $str ) ) {
				return array();
			} else {
				return $this->cleanup_array( explode( ',', $str ) );
			}
		}

		/**
		 * Removes empty elements from an array
		 * Always returns an array, no matter what is passed to it
		 *
		 * @param array $arr array of string values.
		 * @return array $output array of non-empty, trimmed string values
		 */
		private function cleanup_array( $arr = array() ) {
			$output = array();
			if ( is_array( $arr ) && count( $arr ) ) {
				$arr = array_map( 'trim', $arr );
				foreach ( $arr as $str ) {
					if ( ! empty( $str ) ) {
						$output[] = $str;
					}
				}
			}
			return $output;
		}

		/**
		 * Input validation callback
		 * also used to sanitise options in get_plugin_options()
		 *
		 * @param array $plugin_options options.
		 * @return array $plugin_options options (sanitised).
		 */
		public function validate_rich_text_excerpts_options( $plugin_options ) {
			/* get defaults as a fallabck for missing values */
			$defaults = self::get_default_plugin_options();

			/* make sure supported post types is an array */
			if ( ! isset( $plugin_options['supported_post_types'] ) || ! is_array( $plugin_options['supported_post_types'] ) ) {
				$plugin_options['supported_post_types'] = $defaults['supported_post_types'];
			}

			/* check context is an allowed value */
			if ( ! isset( $plugin_options['metabox']['context'] ) ) {
				$plugin_options['metabox']['context'] = $defaults['metabox']['context'];
			} else {
				if ( ! in_array( $plugin_options['metabox']['context'], array( 'normal', 'advanced', 'side' ), true ) ) {
					$plugin_options['metabox']['context'] = $defaults['metabox']['context'];
				}
			}

			/* check priority is an allowed value */
			if ( ! isset( $plugin_options['metabox']['priority'] ) ) {
				$plugin_options['metabox']['priority'] = $defaults['metabox']['priority'];
			} else {
				if ( ! in_array( $plugin_options['metabox']['priority'], array( 'high', 'core', 'default', 'low' ), true ) ) {
					$plugin_options['metabox']['priority'] = $defaults['metabox']['priority'];
				}
			}

			/* make sure editor type is one of the allowed types */
			if ( ! isset( $plugin_options['editor_type'] ) || ! in_array( $plugin_options['editor_type'], array( 'teeny', 'tiny' ), true ) ) {
				$plugin_options['editor_type'] = $defaults['editor_type'];
			}

			/* make sure there are some editor settings */
			if ( ! isset( $plugin_options['editor_settings'] ) ) {
				$plugin_options['editor_settings'] = $defaults['editor_settings'];
			} else {

				/* make sure wpautop is set, and a boolean value */
				if ( ! isset( $plugin_options['editor_settings']['wpautop'] ) ) {
					$plugin_options['editor_settings']['wpautop'] = $defaults['editor_settings']['wpautop'];
				} else {
					$plugin_options['editor_settings']['wpautop'] = (bool) $plugin_options['editor_settings']['wpautop'];
				}

				/* make sure media_buttons is set, and a boolean value */
				if ( ! isset( $plugin_options['editor_settings']['media_buttons'] ) ) {
					$plugin_options['editor_settings']['media_buttons'] = $defaults['editor_settings']['media_buttons'];
				} else {
					$plugin_options['editor_settings']['media_buttons'] = (bool) $plugin_options['editor_settings']['media_buttons'];
				}

				/* make sure textarea_rows is set, and is an integer greater than 3 */
				$plugin_options['editor_settings']['textarea_rows'] = ( isset( $plugin_options['editor_settings']['textarea_rows'] ) ) ? intval( $plugin_options['editor_settings']['textarea_rows'] ) : $defaults['editor_settings']['textarea_rows'];
				if ( $plugin_options['editor_settings']['textarea_rows'] < 3 ) {
					$plugin_options['editor_settings']['textarea_rows'] = 3;
				}

				/* make sure editor_height is set, and is an integer greater between 50 and 5000 */
				$plugin_options['editor_settings']['editor_height'] = ( isset( $plugin_options['editor_settings']['editor_height'] ) ) ? intval( $plugin_options['editor_settings']['editor_height'] ) : $defaults['editor_settings']['editor_height'];
				if ( $plugin_options['editor_settings']['editor_height'] < 50 || $plugin_options['editor_settings']['editor_height'] > 5000 ) {
					$plugin_options['editor_settings']['editor_height'] = $defaults['editor_settings']['editor_height'];
				}

				/* make sure plugins and buttons are set, and are arrays */
				if ( ! isset( $plugin_options['editor_settings']['plugins'] ) ) {
					$plugin_options['editor_settings']['plugins'] = $defaults['editor_settings']['plugins'];
				} else {

					/* if this is a string, we are coming from the settings form */
					if ( ! is_array( $plugin_options['editor_settings']['plugins'] ) ) {

						/* tidy up the string and make sure we end up with an array */
						if ( '' === trim( $plugin_options['editor_settings']['plugins'] ) ) {
							$plugin_options['editor_settings']['plugins'] = array();
						} else {
							$plugin_options['editor_settings']['plugins'] = self::get_mce_array( $plugin_options['editor_settings']['plugins'] );
						}
					} else {
						$plugin_options['editor_settings']['plugins'] = self::cleanup_array( $plugin_options['editor_settings']['plugins'] );
					}
				}
				if ( ! isset( $plugin_options['editor_settings']['buttons'] ) ) {
					$plugin_options['editor_settings']['buttons'] = $defaults['editor_settings']['buttons'];
				} else {

					/* if this is a string, we are coming from the settings form */
					if ( ! is_array( $plugin_options['editor_settings']['buttons'] ) ) {

						/* tidy up the string and make sure we end up with an array */
						if ( '' === trim( $plugin_options['editor_settings']['buttons'] ) ) {
							$plugin_options['editor_settings']['buttons'] = array();
						} else {
							$plugin_options['editor_settings']['buttons'] = self::get_mce_array( $plugin_options['editor_settings']['buttons'] );
						}
					} else {
						$plugin_options['editor_settings']['buttons'] = self::cleanup_array( $plugin_options['editor_settings']['buttons'] );
					}
				}

				/* if the buttons array is empty, reset both buttons and plugins to the default value */
				if ( ! count( $plugin_options['editor_settings']['buttons'] ) ) {
					$plugin_options['editor_settings']['buttons'] = $defaults['editor_settings']['buttons'];
					$plugin_options['editor_settings']['plugins'] = $defaults['editor_settings']['plugins'];
				}
			}
			return $plugin_options;
		}

		/**
		 * Gets the version of the plugin
		 */
		public function version() {
			return $this->version;
		}

	}
	/* end class definition */

	/* instantiate object */
	new Rich_Text_Excerpts();
}
