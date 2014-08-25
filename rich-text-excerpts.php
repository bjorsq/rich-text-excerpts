<?php
/*
Plugin Name: Rich Text Excerpts
Plugin URI: http://wordpress.org/extend/plugins/rich-text-excerpts/
Description: Adds rich text editing capability for excerpts using wp_editor()
Author: Peter Edwards <pete@bjorsq.net>
Author URI: https://github.com/bjorsq/rich-text-excerpts
Version: 1.3.2
Text Domain: rich-text-excerpts
License: GPLv3

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

if ( ! class_exists( 'Rich_Text_Excerpts' ) ) :
/**
 * implemented as a class to help avoid namespace collisions
 */
class Rich_Text_Excerpts {

	public static function register()
	{
		/**
		 * adds an action to remove the default meta box
		 * just after it is added to the page
		 */
		add_action( 'add_meta_boxes', array( __CLASS__, 'remove_excerpt_meta_box' ), 1, 1 );
		/**
		 * get the plugin options
		 */
		$plugin_options = self::get_plugin_options();
		/**
		 * adding a richtext editor to a sortable postbox has only been tested in 3.5
		 * so only add using add_meta_box() for 3.5 and above
		 */
		if ( $plugin_options['metabox']['use'] ) {
			/**
			 * adds an action to add the editor in a new meta box
			 */
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_richtext_excerpt_editor_metabox' ) );
		} else {
			/**
			 * adds an action to add the editor using edit_page_form and edit_form_advanced
			 */
			add_action( 'edit_page_form', array( __CLASS__, 'add_richtext_excerpt_editor' ) );
			add_action( 'edit_form_advanced', array( __CLASS__, 'add_richtext_excerpt_editor' ) );
		}
		/**
		 * filters to customise the teeny mce editor
		 */
		add_filter( 'teeny_mce_plugins', array( __CLASS__, 'teeny_mce_plugins' ), 10, 2 );
		add_filter( 'teeny_mce_buttons', array( __CLASS__, 'teeny_mce_buttons' ), 10, 2 );
		/**
		 * register plugin admin options
		 */
		add_action( 'admin_menu', array( __CLASS__, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_plugin_options' ) );

		 /**
		  * add a link to the settings page from the plugins page
		  */
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_page_link'), 10, 2 );

		/**
		 * register text domain
		 */
		add_action( 'plugins_loaded', array( __CLASS__, 'load_text_domain' ) );
		/**
		 * activate/deactivate
		 */
		register_activation_hook( __FILE__, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'on_deactivation' ) );
	}

	/**
	 * i18n
	 */
	public static function load_text_domain()
	{
		load_plugin_textdomain( 'rich-text-excerpts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * store default options for plugin on activation
	 */
	public static function on_activation()
	{
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		update_option( 'rich_text_excerpts_options', self::get_default_plugin_options() );
	}

	/**
	 * remove plugin options on deactivation
	 */
	public static function on_deactivation()
	{
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		delete_option( 'rich_text_excerpts_options' );
	}

	/**
	 * determines whether the post type has support for excerpts,
	 * and whether the plugin is configured to be used for that post type
	 */
	public static function post_type_supported( $post_type )
	{
		$plugin_options = self::get_plugin_options();
		return ( post_type_supports( $post_type, 'excerpt' ) && in_array( $post_type, $plugin_options['supported_post_types'] ) );
	}

	/**
	 * removes the excerpt meta box normally used to edit excerpts
	 */
	public static function remove_excerpt_meta_box( $post_type )
	{
		if ( self::post_type_supported( $post_type ) ) {
			remove_meta_box( 'postexcerpt', $post_type, 'normal' );
		}
	}

	/**
	 * adds a rich text editor to edit excerpts
	 * includes a sanity check to see if the post type supports them first
	 */
	public static function add_richtext_excerpt_editor()
	{
		global $post;
		if ( self::post_type_supported( $post->post_type ) ) {
			self::post_excerpt_editor();
		}
	}

	/**
	 * adds a rich text editor in a metabox
	 */
	public static function add_richtext_excerpt_editor_metabox()
	{
		$plugin_options = self::get_plugin_options();
		foreach ( $plugin_options["supported_post_types"] as $post_type ) {
			add_meta_box(
				'richtext_excerpt_editor_metabox'
				,__('Excerpt', 'rich-text-excerpts')
				,array( __CLASS__, 'post_excerpt_editor' )
				,$post_type
				,$plugin_options["metabox"]["context"]
				,$plugin_options["metabox"]["priority"]
			);
		}
	}

	/**
	 * Prints the post excerpt form field (using wp_editor()).
	 */
	public static function post_excerpt_editor()
	{
		global $post;
		if ( $post && $post->post_excerpt ) {
			$excerpt = $post->post_excerpt;
		} else {
			$excerpt = '';
		}
		$plugin_options = self::get_plugin_options();
		if ( ! $plugin_options['metabox']['use'] ) {
			/* wrap in a postbox to make it look pretty */
			printf( '<div class="postbox rich-text-excerpt-static"><h3><label for="excerpt">%s</label></h3><div class="rte-wrap">', __( 'Excerpt', 'rich-text-excerpts' ) );
		} else {
			/* wrap to identify presence of metabox to scripts so they can disable the editor when sorting takes place */
			print( '<div class="rte-wrap-metabox">' );
		}
		/* options for editor */
		$options = array(
			"wpautop"       => $plugin_options['editor_settings']['wpautop'],
			"media_buttons" => $plugin_options['editor_settings']['media_buttons'],
			"textarea_name" => 'excerpt',
			"textarea_rows" => $plugin_options['editor_settings']['textarea_rows'],
			"editor_height" => $plugin_options['editor_settings']['editor_height'],
			"teeny"         => ( "teeny" === $plugin_options['editor_type'] )? true : false
		);
		/* get decoded content for the editor */
		$excerpt = html_entity_decode( $excerpt );
		/**
		 * this will decode numeric entities
		 * @see http://wordpress.org/support/topic/special-characters-show-as-their-character-codes
		 */
		$excerpt = wp_kses_decode_entities( $excerpt );
		/* output editor */
		wp_editor( $excerpt, 'excerpt', $options );
		if ( ! $plugin_options['metabox']['use'] ) {
			/* finish wrapping */
			print('</div></div>');
		} else {
			print('</div>');
		}
	}

	/**
	 * filter to add plugins for the "teeny" editor
	 */
	public static function teeny_mce_plugins( $plugins, $editor_id )
	{
		$plugin_options = self::get_plugin_options();
		if ( count( $plugin_options['editor_settings']['plugins'] ) ) {
			foreach ( $plugin_options['editor_settings']['plugins'] as $plugin_name ) {
				if ( ! isset($plugins[$plugin_name] ) ) {
					array_push( $plugins, $plugin_name );
				}
			}
		}
		return $plugins;
	}

	/**
	 * filter to add buttons to the "teeny" editor
	 * this completely disregards the buttons array passed to it and returns a new array
	 */
	public static function teeny_mce_buttons($buttons, $editor_id)
	{
		$plugin_options = self::get_plugin_options();
		return $plugin_options['editor_settings']['buttons'];
	}


	/************************************************************
	 * PLUGIN OPTIONS ADMINISTRATION							*
	 ************************************************************/
	
	/**
	 * adds a link to the settings page from the plugins listing page
	 * called using the plugin_action_links filter
	 */
	public static function add_settings_page_link($links, $file)
	{
		if ( plugin_basename( __FILE__ ) == $file ) {
			$settings_page_link = sprintf('<a href="%s">%s</a>', admin_url( 'options-general.php?page=rich_text_excerpts_options' ), __( 'Settings', 'rich-text-excerpts' ) );
			$links[] = $settings_page_link;
		}
		return $links;
	}

	/**
	 * add an admin page under settings to configure the plugin
	 */
	public static function add_plugin_admin_menu()
	{
		/* Plugin Options page */
		$options_page = add_submenu_page( 'options-general.php', __( 'Rich Text Excerpts', 'rich-text-excerpts' ), __( 'Rich Text Excerpts', 'rich-text-excerpts' ), "manage_options", "rich_text_excerpts_options", array( __CLASS__, "plugin_options_page" ) );
		/**
		 * Use the admin_print_scripts action to add scripts.
		 * Admin script is only needed on plugin admin page, but editor script is needed on all pages
		 * which include the editor
		 */
		add_action( 'admin_print_scripts-' . $options_page, array( __CLASS__, 'plugin_admin_scripts' ) );
		add_action( 'admin_print_scripts', array( __CLASS__, 'plugin_editor_scripts' ) );
		/**
		 * Use the admin_print_styles action to add CSS.
		 * CSS is needed for the post/page editor only
		 */
		add_action( 'admin_print_styles', array( __CLASS__, 'plugin_admin_styles' ) );
	}

	/**
	 * add script to admin for plugin options
	 */
	public static function plugin_admin_scripts()
	{
		wp_enqueue_script('RichTextExcerptsAdminScript', plugins_url('rich-text-excerpts.js', __FILE__), array('jquery'));
	}
	
	/**
	 * add script to admin for plugin options
	 */
	public static function plugin_editor_scripts()
	{
		$screen = get_current_screen();
		if ( self::post_type_supported( $screen->post_type ) ) {
			wp_enqueue_script('RichTextExcerptsEditorScript', plugins_url('rich-text-excerpts-editor.js', __FILE__), array('jquery'));
		}
	}

	/**
	 * add css to admin for editor formatting
	 */
	public static function plugin_admin_styles()
	{
		$screen = get_current_screen();
		if ( self::post_type_supported( $screen->post_type ) ) {
			wp_enqueue_style('RichTextExcerptsAdminCSS', plugins_url('rich-text-excerpts.css', __FILE__));
		}
	}

	/**
	 * creates the options page
	 */
	public static function plugin_options_page()
	{
		printf('<div class="wrap"><div class="icon32" id="icon-options-general"><br /></div><h2>%s</h2>', __('Rich Text Excerpts Options', 'rich-text-excerpts'));
		settings_errors('rich_text_excerpts_options');
		print('<form method="post" action="options.php" id="rich_text_excerpts_options_form">');
		settings_fields('rich_text_excerpts_options');
		do_settings_sections('rte');
		printf('<p class="submit"><input type="submit" class="button-primary" name="Submit" value="%s" /></p>', __('Save Changes', 'rich-text-excerpts'));
		print('</form></div>');
	}

	/**
	 * registers settings and sections
	 */
	public static function register_plugin_options()
	{
		register_setting( 'rich_text_excerpts_options', 'rich_text_excerpts_options', array( __CLASS__, 'validate_rich_text_excerpts_options' ) );
		
		/* post type and metabox options */
		add_settings_section(
			'post-type-options',
			__('Post Types', 'rich-text-excerpts'),
			array( __CLASS__, 'options_section_text' ),
			'rte'
		);
				
		add_settings_field(
			'supported_post_types',
			__('Choose which post types will use a rich text editor for excerpts', 'rich-text-excerpts'),
			array( __CLASS__, 'options_setting_post_types' ),
			'rte',
			'post-type-options'
		);

		/* editor options */
		add_settings_section(
			'editor-options',
			__('Editor Options', 'rich-text-excerpts'),
			array( __CLASS__, 'options_section_text' ),
			'rte'
		);

		add_settings_field(
			'metabox',
			__('Use a meta box', 'rich-text-excerpts'),
			array( __CLASS__, 'options_setting_metabox' ),
			'rte',
			'editor-options'
		);

		add_settings_field(
			'editor_type',
			__('Choose which Editor is used for excerpts', 'rich-text-excerpts'),
			array( __CLASS__, 'options_setting_editor_type' ),
			'rte',
			'editor-options'
		);

		/* settings for editor */
		add_settings_field(
			'editor_settings',
			__('Editor Settings', 'rich-text-excerpts'),
			array( __CLASS__, 'options_editor_settings' ),
			'rte',
			'editor-options'
		);
	}

	/**
	 * gets plugin options - merges saved options with defaults
	 * @return array
	 */
	public static function get_plugin_options()
	{
		$saved = get_option('rich_text_excerpts_options');
		return self::validate_rich_text_excerpts_options($saved);
	}

	/**
	 * gets default plugin options
	 */
	public static function get_default_plugin_options()
	{
		return array(
			"supported_post_types" => array('post'),
			"editor_type" => "teeny",
			"metabox" => array(
				"use" => true,
				"context" => 'normal',
				"priority" => 'high'
			),
			"editor_settings" => array(
				"wpautop" => true,
				"media_buttons" => false,
				"textarea_rows" => 3,
				"editor_height" => 150,
				"buttons" => array('bold', 'italic', 'underline', 'separator','pastetext', 'pasteword', 'removeformat', 'separator', 'charmap', 'blockquote', 'separator', 'bullist', 'numlist', 'separator', 'justifyleft', 'justifycenter', 'justifyright', 'separator', 'undo', 'redo', 'separator', 'link', 'unlink'),
				"plugins" => array('charmap', 'paste')
			)
		);
	}

	/**
	 * settings section text
	 */
	public static function options_section_text()
	{
		echo "";
	}

	/**
	 * post type support settings
	 */
	public static function options_setting_post_types()
	{
		$options = self::get_plugin_options();
		$post_types = get_post_types( array( "public" => true ), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'excerpt' ) ) {
				$chckd = ( in_array( $post_type->name, $options["supported_post_types"] ) ) ? ' checked="checked"' : '';
				printf( '<p class="rte-post-types-inputs"><input class="rte-post-types" type="checkbox" name="rich_text_excerpts_options[supported_post_types][]" id="supported_post_types-%s" value="%s"%s /> <label for="supported_post_types-%s">%s</label></p>', $post_type->name, $post_type->name, $chckd, $post_type->name, $post_type->labels->name );
			}
		}
		printf('<div class="rte-post-types-error" style="background-color: rgb(255, 255, 224);border:1px solid rgb(230, 219, 85); border-radius:3px;	color: rgb(51, 51, 51);	padding: 4px 0.5em;	display:none;"></p>%s</p></div>', __('If you want to disable support for all post types, please disable the plugin', 'rich-text-excerpts'));
		printf('<p>%s<br /><a href="http://codex.wordpress.org/Function_Reference/add_post_type_support">add_post_type_support()</a></p>', __('Post types not selected here will use the regular plain text editor for excerpts. If the post type you want is not listed here, it does not currently support excerpts - to add support for excerpts to a post type, see the Wordpress Codex', 'rich-text-excerpts'));
	}

	/**
	 * Meta box support settings
	 */
	public static function options_setting_metabox()
	{
		$options = self::get_plugin_options();

		/* whether or not to use a metabox for excerpts */
		$chckd = $options["metabox"]["use"]? ' checked="checked"': '';
		printf( '<p class="rte-use-metabox-input"><input class="rte-metabox" type="checkbox" name="rich_text_excerpts_options[metabox][use]" id="rte-use-metabox" value="1"%s /> <label for="rte-use-metabox">%s</label></p>', $chckd, __('Check this box to put the excerpt in a draggable meta box', 'rich-text-excerpts') );
		print( '<div id="rte-metabox-settings">' );

		/* metabox context settings */
		$contexts = array(
			'normal',
			'advanced',
			'side'
		);
		print( '<p><label for="rte-metabox-context"><select name="rich_text_excerpts_options[metabox][context]">' );
		foreach ( $contexts as $context ) {
			$sel = ( $options['metabox']['context'] == $context ) ? ' selected' : '';
			printf( '<option value="%s"%s>%s</option>', $context, $sel, $context );
		}
		printf( '</select> %s</p>', __('Set the part of the page where the excerpt editor should be shown', 'rich-text-excerpts') );
		//print('<input type="hidden" name="rich_text_excerpts_options[metabox][context]" value="normal" />');

		/* metabox priority settings */
		$priorities = array(
			'high',
			'core',
			'default',
			'low'
		);
		print( '<p><label for="rte-metabox-priority"><select name="rich_text_excerpts_options[metabox][priority]">' );
		foreach ( $priorities as $priority ) {
			$sel = ( $options['metabox']['priority'] == $priority ) ? ' selected' : '';
			printf( '<option value="%s"%s>%s</option>', $priority, $sel, $priority);
		}
		printf( '</select> %s</p>', __('Set the priority of the excerpt editor', 'rich-text-excerpts') );
		//print('<input type="hidden" name="rich_text_excerpts_options[metabox][priority]" value="high" />');
		print( '</div>' );
	}

	/**
	 * editor type radios
	 */
	public static function options_setting_editor_type()
	{
		$options = self::get_plugin_options();
		$chckd = ( "teeny" === $options["editor_type"] ) ? ' checked="checked"': '';
		printf( '<p><label for="rich_text_excerpts_options-editor_type-teeny"><input type="radio" name="rich_text_excerpts_options[editor_type]" id="rich_text_excerpts_options-editor_type-teeny" class="rte-options-editor-type" value="teeny"%s /> %s</label></p>', $chckd, __('Use the minimal editor configuration used in PressThis', 'rich-text-excerpts') );
		$chckd = ( "teeny" === $options["editor_type"])? '': ' checked="checked"';
		printf( '<p><label for="rich_text_excerpts_options-editor_type-tiny"><input type="radio" name="rich_text_excerpts_options[editor_type]" id="rich_text_excerpts_options-editor_type-tiny" class="rte-options-editor-type" value="tiny"%s /> %s</label></p>', $chckd, __('Use the full version of the editor', 'rich-text-excerpts') );
		printf( '<p>%s.</p>', __('Choose whether to use the full TinyMCE editor, or the &ldquo;teeny&rdquo; version of the editor.', 'rich-text-excerpts') );
	}

	/**
	 * Settings for text editor
	 * Follows the Wordpress wp_editor function. Arguments not implemented are:
	 *  - tabindex - may be a way to find out what this should be for a metabox and pass to wp_editor automatically?
	 *  - editor_css - Additional CSS styling applied for both visual and HTML editors buttons, needs to include <style> tags, can use "scoped" (hard to validate)
	 *  - editor_class - Any extra CSS Classes to append to the Editor textarea (could be useful?)
	 *  - dfw - Whether to replace the default fullscreen editor with DFW (needs specific DOM elements and css)
	 *  - tinymce - Load TinyMCE, can be used to pass settings directly to TinyMCE using an array() - direct people to TinyMCE Advanced rther than implement this
	 *  - quicktags - Load Quicktags, can be used to pass settings directly to Quicktags using an array() (could be useful? does TA handle quicktags?)
	 * @see http://codex.wordpress.org/Function_Reference/wp_editor
	 */
	public static function options_editor_settings()
	{
		$options = self::get_plugin_options();
		$chckd = ( $options['editor_settings']['wpautop'] ) ? '' : ' checked="checked"';
		printf( '<p><input type="checkbox" name="rich_text_excerpts_options[editor_settings][wpautop]" id="rich_text_excerpts_options-editor_settings-wpautop" value="0"%s /> <label for="rich_text_excerpts_options-editor_settings-wpautop">%s.</label></p>', $chckd, __('Stop removing the &lt;p&gt; and &lt;br&gt; tags when saving and show them in the HTML editor This will make it possible to use more advanced coding in the HTML editor without the back-end filtering affecting it much. However it may behave unexpectedly in rare cases, so test it thoroughly before enabling it permanently', 'rich-text-excerpts') );
		$chckd = ( $options['editor_settings']['media_buttons'] ) ? 'checked="checked"' : '';
		printf( '<p><input type="checkbox" name="rich_text_excerpts_options[editor_settings][media_buttons]" id="rich_text_excerpts_options-editor_settings-media_buttons"%s /> <label for="rich_text_excerpts_options-editor_settings-media_buttons">%s</label></p>', $chckd, __('Enable upload media button', 'rich-text-excerpts') );
		printf( '<p><input type="text" length="2" name="rich_text_excerpts_options[editor_settings][textarea_rows]" id="rich_text_excerpts_options-editor_settings-textarea_rows" value="%d" /> <label for="rich_text_excerpts_options-editor_settings-textarea_rows">%s</label></p>', intVal($options['editor_settings']['textarea_rows']), __('Number of rows to use in the text editor (minimum is 3)', 'rich-text-excerpts') );
		printf( '<p><input type="text" length="4" name="rich_text_excerpts_options[editor_settings][editor_height]" id="rich_text_excerpts_options-editor_settings-editor_height" value="%d" /> <label for="rich_text_excerpts_options-editor_settings-editor_height">%s</label></p>', intVal($options['editor_settings']['editor_height']), __('Height of editor in pixels (between 50 and 5000)', 'rich-text-excerpts') );
		printf( '<p><strong>%s</strong></p>', __('Toolbar Buttons and Plugins', 'rich-text-excerpts') );
		/**
		 * settings for teeny text editor
		 */
		print( '<div id="editor_type_teeny_options">' );
		printf( '<p>%s.<br /><a href="http://www.tinymce.com/wiki.php/Buttons/controls">http://www.tinymce.com/wiki.php/Buttons/controls</a><br />%s<br /><a href="http://codex.wordpress.org/TinyMCE">http://codex.wordpress.org/TinyMCE</a><br />%s.</p>', __('For a list of buttons and plugins in TinyMCE, see the TinyMCE wiki', 'rich-text-excerpts'), __('There is also some documentation on the implementation of TinyMCE in Wordpress on the Wordpress Codex', 'rich-text-excerpts'), __('Button and plugin names should be separated using commas', 'rich-text-excerpts') );
		printf( '<p><label for="rich_text_excerpts_options-editor_settings-plugins">%s.</label><br /><input type="text" length="50" name="rich_text_excerpts_options[editor_settings][plugins]" id="rich_text_excerpts_options-editor_settings-plugins" value="%s" /></p>', __('Plugins to add - make sure you add any plugin specific buttons to the editor below', 'rich-text-excerpts'), implode(',', $options['editor_settings']['plugins']) );
		printf( '<p><label for="rich_text_excerpts_options-editor_settings-buttons">%s</label><br /><textarea name="rich_text_excerpts_options[editor_settings][buttons]" id="rich_text_excerpts_options-editor_settings-buttons" cols="100" rows="3">%s</textarea></p>', __('Toolbar buttons - use the word &lsquo;separator&rsquo; to separate groups of buttons', 'rich-text-excerpts'), implode(',', $options['editor_settings']['buttons']) );
		print( '</div>' );
		/**
		 * settings for tiny text editor (none to show here, but show links to TinyMCE advanced)
		 */
		print( '<div id="editor_type_tiny_options">' );
		if ( is_plugin_active( 'tinymce-advanced/tinymce-advanced.php' ) ) {
			printf( '<p><a href="%s">%s</a>.</p>', admin_url( 'options-general.php?page=tinymce-advanced' ), __('Configure the buttons for the advanced editor using the TinyMCE Advanced plugin', 'rich-text-excerpts') );
		} else {
			printf( '<p><a href="%s">%s</a>.</p>', admin_url( 'plugins.php' ), __('If you want to configure the buttons for the advanced editor, install and activate the TinyMCE Advanced plugin', 'rich-text-excerpts') );
		}
		print( '</div>');
	}

	/**
	 * takes a string of comma-separated arguments and splits it into an array
	 */
	public static function get_mce_array( $inputStr = '' )
	{
		if ( "" === trim($inputStr) ) {
			return array();
		} else {
			return self::cleanup_array( explode( ',', $inputStr ) );
		}
	}

	/**
	 * removes empty elements from an array
	 * Always returns an array, no matter what is passed to it
	 */
	public static function cleanup_array( $arr = array() )
	{
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
	 * input validation callback
	 * also used to sanitise options in get_plugin_options()
	 */
	public static function validate_rich_text_excerpts_options( $plugin_options )
	{
		/* get defaults as a fallabck for missing values */
		$defaults = self::get_default_plugin_options();

		/* make sure supported post types is an array */
		if ( ! isset( $plugin_options['supported_post_types'] ) || ! is_array($plugin_options['supported_post_types'] ) ) {
			$plugin_options['supported_post_types'] = $defaults['supported_post_types'];
		}

		/* see if the editor is being embedded in a metabox */
		$plugin_options['metabox']['use'] = (isset($plugin_options['metabox']['use']) && $plugin_options['metabox']['use'] == "1")? true: false;

		/* check context is an allowed value */
		if ( ! isset( $plugin_options['metabox']['context'] ) ) {
			$plugin_options['metabox']['context'] = $defaults['metabox']['context'];
		} else {
			if ( ! in_array( $plugin_options['metabox']['context'], array( 'normal', 'advanced', 'side' ) ) ) {
				$plugin_options['metabox']['context'] = $defaults['metabox']['context'];
			}
		}

		/* check priority is an allowed value */
		if ( ! isset( $plugin_options['metabox']['priority'] ) ) {
			$plugin_options['metabox']['priority'] = $defaults['metabox']['priority'];
		} else {
			if ( ! in_array( $plugin_options['metabox']['priority'], array( 'high', 'core', 'default', 'low' ) ) ) {
				$plugin_options['metabox']['priority'] = $defaults['metabox']['priority'];
			}
		}

		/* make sure editor type is one of the allowed types */
		if ( ! isset( $plugin_options['editor_type'] ) || ! in_array( $plugin_options['editor_type'], array( 'teeny','tiny' ) ) ) {
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
			$plugin_options['editor_settings']['textarea_rows'] = ( isset( $plugin_options['editor_settings']['textarea_rows'] ) ) ? intval( $plugin_options['editor_settings']['textarea_rows'] ): $defaults['editor_settings']['textarea_rows'];
			if ( $plugin_options['editor_settings']['textarea_rows'] < 3 ) {
				$plugin_options['editor_settings']['textarea_rows'] = 3;
			}

			/* make sure editor_height is set, and is an integer greater between 50 and 5000 */
			$plugin_options['editor_settings']['editor_height'] = ( isset( $plugin_options['editor_settings']['editor_height'] ) ) ? intval( $plugin_options['editor_settings']['editor_height'] ): $defaults['editor_settings']['editor_height'];
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
					if ( "" === trim( $plugin_options['editor_settings']['plugins'] ) ) {
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
					if ( "" === trim( $plugin_options['editor_settings']['buttons'] ) ) {
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

}
/* end class definition */

/* register the Plugin with the Wordpress API */
Rich_Text_Excerpts::register();

endif;