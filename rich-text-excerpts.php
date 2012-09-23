<?php
/*
Plugin Name: Rich Text Excerpts
Plugin URI: https://bitbucket.org/bjorsq/rich-text-excerpts
Description: Adds rich text editing capability for excerpts using wp_editor()
Author: Peter Edwards
Author URI: http://bjorsq.net
Version: 1.2
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

if ( ! class_exists('RichTextExcerpts') ) :
/**
 * implemented as a class to help avoid naming collisions
 */
class RichTextExcerpts {

	public static function register()
    {
        /**
         * adds an action to remove the default meta box 
         * just after it is added to the page
         */
        add_action('add_meta_boxes', array('RichTextExcerpts', 'remove_excerpt_meta_box'), 1, 1);
        /**
         * adds an action to add the new meta box using wp_editor()
         */
        add_action('edit_page_form', array('RichTextExcerpts', 'add_richtext_excerpt_editor'));
        add_action('edit_form_advanced', array('RichTextExcerpts', 'add_richtext_excerpt_editor'));
        /**
         * filters to customise the teeny mce editor
         */
        add_filter('teeny_mce_plugins', array('RichTextExcerpts', 'teeny_mce_plugins'), 10, 2);
        add_filter('teeny_mce_buttons', array('RichTextExcerpts', 'teeny_mce_buttons'), 10, 2);
        /**
         * register plugin admin options
         */
        add_action( 'admin_menu', array('RichTextExcerpts', 'add_plugin_admin_menu') );
        add_action( 'admin_init', array('RichTextExcerpts', 'register_plugin_options') );
        /**
         * register text domain
         */
        add_action('plugins_loaded', array('RichTextExcerpts', 'load_text_domain'));
    }

    /**
     * i18n
     */
    public static function load_text_domain()
    {
        load_plugin_textdomain( 'rich-text-excerpts', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * removes the excerpt meta box normally used to edit excerpts
     */
    public static function remove_excerpt_meta_box($post_type)
    {
        if ( post_type_supports($post_type, 'excerpt') ) {
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
        if ( post_type_supports($post->post_type, 'excerpt') ) {
            self::post_excerpt_editor();
        }
    }

    /**
     * Prints the post excerpt form field (using wp_editor()).
     */
    public static function post_excerpt_editor()
    {
        global $post;
        $plugin_options = self::get_plugin_options();
        printf('<div style="margin-bottom:1em;clear:both;width:100%%;"><h3><label for="excerpt">%s</label></h3>', __('Excerpt', 'rich-text-excerpts'));
        /* options for editor */
        $options = array(
            "wpautop" => $plugin_options['editor_settings']['wpautop'],
            "media_buttons" => $plugin_options['editor_settings']['media_buttons'],
            "textarea_name" => 'excerpt',
            "textarea_rows" => $plugin_options['editor_settings']['textarea_rows'],
            "teeny" => ($plugin_options['editor_type'] === "teeny")? true: false
        );
        /* "echo" the editor */
        wp_editor(html_entity_decode($post->post_excerpt), 'excerpt', $options );
        print('</div>');
    }

    /**
     * filter to add plugins for the "teeny" editor
     */
    public static function teeny_mce_plugins($plugins, $editor_id)
    {
        $plugin_options = self::get_plugin_options();
        if (count($plugin_options['editor_settings']['plugins'])) {
            foreach ($plugin_options['editor_settings']['plugins'] as $plugin_name) {
                if (!isset($plugins[$plugin_name])) {
                    array_push($plugins, $plugin_name);
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
     * PLUGIN OPTIONS ADMINISTRATION                            *
     ************************************************************/
    
    /**
     * add an admin page under settings to configure the plugin
     */
    public static function add_plugin_admin_menu()
    {
        /* Plugin Options page */
        $options_page = add_submenu_page("options-general.php", __('Rich Text Excerpts', 'rich-text-excerpts'), __('Rich Text Excerpts', 'rich-text-excerpts'), "manage_options", "rich_text_excerpts_options", array('RichTextExcerpts', "plugin_options_page") );
        /* Use the admin_print_scripts action to add scripts for theme options */
        add_action( 'admin_print_scripts-' . $options_page, array('RichTextExcerpts', 'plugin_admin_scripts') );
        /* Use the admin_print_styles action to add CSS for theme options */
        //add_action( 'admin_print_styles-' . $options_page, array('RichTextExcerpts', 'plugin_admin_styles') );
    }

    /**
     * add script to admin for plugin options
     */
    public static function plugin_admin_scripts()
    {
        wp_enqueue_script('RichTextExcerptsAdminScript', plugins_url('rich-text-excerpts.js', __FILE__), array('jquery'));
    }
    
    /**
     * creates the options page
     */
    public static function plugin_options_page()
    {
        printf('<div class="wrap"><div class="icon32" id="icon-options-general"><br /></div><h2>%s</h2>', __('Rich Text Excerpts Options', 'rich-text-excerpts'));
        settings_errors('rich_text_excerpts_options');
        print('<form method="post" action="options.php">');
        settings_fields('rich_text_excerpts_options');
        do_settings_sections('rte');
        printf('<p class="submit"><input type="submit" class="button-primary" name="Submit" value="%s" /></p>', __('Save Changes', 'rich-text-excerpts'));
        print('</form></div>');
    }

    /**
     * registers settings and sections
     */
    function register_plugin_options()
    {
        register_setting('rich_text_excerpts_options', 'rich_text_excerpts_options', array('RichTextExcerpts', 'validate_rich_text_excerpts_options'));
        
        /* post type options */
        add_settings_section(
            'post-type-options',
            'Post Types',
            array('RichTextExcerpts', 'options_section_text'), 
            'rte'
        );
        add_settings_field(
            'supported_post_types', 
            __('Choose which post types will have rich text editor for excerpts', 'rich-text-excerpts'), 
            array('RichTextExcerpts', 'options_setting_post_types'), 
            'rte', 
            'post-type-options'
        );
                
        /* editor options */
        add_settings_section(
            'editor-options',
            __('Editor Options', 'rich-text-excerpts'),
            array('RichTextExcerpts', 'options_section_text'), 
            'rte'
        );
        add_settings_field(
            'editor_type', 
            __('Choose which Editor is used for excerpts', 'rich-text-excerpts'), 
            array('RichTextExcerpts', 'options_setting_editor_type'), 
            'rte', 
            'editor-options'
        );
        /* settings for editor */
        add_settings_field(
            'editor_settings', 
            __('Editor Settings', 'rich-text-excerpts'), 
            array('RichTextExcerpts', 'options_editor_settings'), 
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
        $defaults = array(
            "supported_post_types" => array('post'),
            "editor_type" => "teeny",
            "editor_settings" => array(
                "wpautop" => true,
                "media_buttons" => false,
                "textarea_rows" => 3,
                "buttons" => array('bold', 'italic', 'underline', 'separator','pastetext', 'pasteword', 'removeformat', 'separator', 'charmap', 'blockquote', 'separator', 'bullist', 'numlist', 'separator', 'justifyleft', 'justifycenter', 'justifyright', 'separator', 'undo', 'redo', 'separator', 'link', 'unlink'),
                "plugins" => array('charmap', 'paste')
            )
        );
        $saved = get_option('rich_text_excerpts_options');
        foreach ($defaults as $key => $val) {
            if (!isset($saved[$key])) {
                $saved[$key] = $val;
            }
        }
        return $saved;
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
        $post_types = get_post_types(array("public" => true),'names');
        foreach ($post_types as $post_type ) {
            if ( post_type_supports($post_type, 'excerpt') ) {
                $chckd = (in_array($post_type, $options["supported_post_types"]))? ' checked="checked"': '';
                printf('<p><input type="checkbox" name="rich_text_excerpts_options[supported_post_types][]" id="supported_post_types-%s" value="%s"%s /> <label for="supported_post_types-%s">%s</label></p>', $post_type, $post_type, $chckd, $post_type, $post_type);
            }
        }
        printf('<p>%s<br /><a href="http://codex.wordpress.org/Function_Reference/add_post_type_support">add_post_type_support()</a></p>', __('Post types not selected here will use the regular plain text editor for excerpts. If the post type you want is not listed here, it does not currently support excerpts - to add support for excerpts to a post type, see the Wordpress Codex', 'rich-text-excerpts'));
    }

    /**
     * settings section text
     */
    public static function options_setting_editor_type()
    { 
        $options = self::get_plugin_options();
        $chckd = ($options["editor_type"] === "teeny")? ' checked="checked"': '';
        printf('<p><input type="radio" name="rich_text_excerpts_options[editor_type]" id="rich_text_excerpts_options-editor_type-teeny" class="rte-options-editor-type" value="teeny"%s /> <label for="rich_text_excerpts_options-editor_type-teeny">%s</label></p>', $chckd, __('Use the minimal editor configuration used in PressThis', 'rich-text-excerpts'));
        $chckd = ($options["editor_type"] === "teeny")? '': ' checked="checked"';
        printf('<p><input type="radio" name="rich_text_excerpts_options[editor_type]" id="rich_text_excerpts_options-editor_type-tiny" class="rte-options-editor-type" value="tiny"%s /> <label for="rich_text_excerpts_options-editor_type-tiny">%s</label></p>', $chckd, __('Use the full version of the editor', 'rich-text-excerpts'));
        printf('<p>%s.</p>', __('Choose whether to use the full TinyMCE editor, or the &ldquo;teeny&rdquo; version of the editor (recommended). Customising the full TinyMCE editor is best carried out using a plugin like TinyMCE Advanced. If you choose to use the &ldquo;teeny&rdquo; version of the editor, you can customise the controls it will have here', 'rich-text-excerpts'));
    }

    /**
     * general settings for text editor
     */
    public static function options_editor_settings()
    { 
        $options = self::get_plugin_options();
        $chckd = $options['editor_settings']['wpautop']? '': ' checked="checked"';
        printf('<p><input type="checkbox" name="rich_text_excerpts_options[editor_settings][wpautop]" id="rich_text_excerpts_options-editor_settings-wpautop"%s /> <label for="rich_text_excerpts_options-editor_settings-wpautop">%s.</label></p>', $chckd, __('Stop removing the &lt;p&gt; and &lt;br&gt; tags when saving and show them in the HTML editor This will make it possible to use more advanced coding in the HTML editor without the back-end filtering affecting it much. However it may behave unexpectedly in rare cases, so test it thoroughly before enabling it permanently', 'rich-text-excerpts'));
        $chckd = $options['editor_settings']['media_buttons']? 'checked="checked"': '';
        printf('<p><input type="checkbox" name="rich_text_excerpts_options[editor_settings][media_buttons]" id="rich_text_excerpts_options-editor_settings-media_buttons"%s /> <label for="rich_text_excerpts_options-editor_settings-media_buttons">%s</label></p>', $chckd, __('Enable upload media button', 'rich-text-excerpts'));
        printf('<p><input type="text" length="2" name="rich_text_excerpts_options[editor_settings][textarea_rows]" id="rich_text_excerpts_options-editor_settings-textarea_rows" value="%d" /> <label for="rich_text_excerpts_options-editor_settings-textarea_rows">%s</label></p>', intVal($options['editor_settings']['textarea_rows']), __('Number of rows to use in the text editor (minimum is 3)', 'rich-text-excerpts'));
        printf('<p><strong>%s</strong></p>', __('Toolbar Buttons and Plugins', 'rich-text-excerpts'));
        /**
         * settings for teeny text editor
         */
        print('<div id="editor_type_teeny_options">');
        printf('<p>%s.<br /><a href="http://www.tinymce.com/wiki.php/Buttons/controls">http://www.tinymce.com/wiki.php/Buttons/controls</a><br />%s<br /><a href="http://codex.wordpress.org/TinyMCE">http://codex.wordpress.org/TinyMCE</a><br />%s.</p>', __('For a list of buttons and plugins in TinyMCE, see the TinyMCE wiki', 'rich-text-excerpts'), __('There is also some documentation on the implementation of TinyMCE in Wordpress on the Wordpress Codex', 'rich-text-excerpts'), __('Button and plugin names should be separated using commas', 'rich-text-excerpts'));
        printf('<p><label for="rich_text_excerpts_options-editor_settings-plugins">%s.</label><br /><input type="text" length="50" name="rich_text_excerpts_options[editor_settings][plugins]" id="rich_text_excerpts_options-editor_settings-plugins" value="%s" /></p>', __('Plugins to add - make sure you add any plugin specific buttons to the editor below', 'rich-text-excerpts'), implode(',', $options['editor_settings']['plugins']));
        printf('<p><label for="rich_text_excerpts_options-editor_settings-buttons">%s</label><br /><textarea name="rich_text_excerpts_options[editor_settings][buttons]" id="rich_text_excerpts_options-editor_settings-buttons" cols="100" rows="3">%s</textarea></p>', __('Toolbar buttons - use the word &lsquo;separator&rsquo; to separate groups of buttons', 'rich-text-excerpts'), implode(',', $options['editor_settings']['buttons']));
        print('</div>');
        /**
         * settings for tiny text editor (none to show here, but show links to TinyMCE advanced)
         */
        print('<div id="editor_type_tiny_options">');
        if (is_plugin_active('tinymce-advanced/tinymce-advanced.php')) {
            printf('<p><a href="%s">%s</a>.</p>', admin_url('options-general.php?page=tinymce-advanced'), __('Configure the buttons for the advanced editor using the TinyMCE Advanced plugin', 'rich-text-excerpts'));
        } else {
            printf('<p><a href="%s">%s</a>.</p>', admin_url('plugins.php'), __('If you want to configure the buttons for the advanced editor, install and activate the TinyMCE Advanced plugin', 'rich-text-excerpts'));
        }
        print('</div>');
    }

    /**
     * takes a string of comma-separated arguments and splits/trims it
     */
    public static function cleanup_mce_array($inputStr = '')
    {
        if (trim($inputStr) === "") {
            return array();
        } else {
            $input = explode(',', $inputStr);
            $output = array();
            foreach ($input as $str) {
                if (trim($str) !== '') {
                    $output[] = $str;
                }
            }
            return $output;
        }
    }
    
    /**
     * input validation callback
     */
    public static function validate_rich_text_excerpts_options($plugin_options)
    {
        if (!isset($plugin_options['supported_post_types'])) {
            $plugin_options['supported_post_types'] = array();
        }
        $plugin_options['editor_settings']['wpautop'] = (!isset($plugin_options['editor_settings']['wpautop']));
        $plugin_options['editor_settings']['media_buttons'] = (isset($plugin_options['editor_settings']['media_buttons']));
        $plugin_options['editor_settings']['textarea_rows'] = intval($plugin_options['editor_settings']['textarea_rows']);
        if ($plugin_options['editor_settings']['textarea_rows'] < 3) {
            $plugin_options['editor_settings']['textarea_rows'] = 3;
        }
        if (trim($plugin_options['editor_settings']['plugins']) == "") {
            $plugin_options['editor_settings']['plugins'] = array();
        } else {
            $plugin_options['editor_settings']['plugins'] = self::cleanup_mce_array($plugin_options['editor_settings']['plugins']);
        }
        if (trim($plugin_options['editor_settings']['buttons']) == "") {
            $plugin_options['editor_settings']['buttons'] = array();
        } else {
            $plugin_options['editor_settings']['buttons'] = self::cleanup_mce_array($plugin_options['editor_settings']['buttons']);
        }

        return $plugin_options;
    }

}
/* end class definition */

/* register the Plugin with the Wordpress API */
RichTextExcerpts::register();

endif;