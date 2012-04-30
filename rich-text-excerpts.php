<?php
/*
Plugin Name: Rich Text Excerpts
Plugin URI: https://bitbucket.org/bjorsq/rich-text-excerpts
Description: Adds rich text editing capability for excerpts using wp_editor()
Author: Peter Edwards
Author URI: http://bjorsq.net
Version: 1.0
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
        print('<div class="postbox-container"><h3 style="float:left;"><label for="excerpt">Excerpt</label></h3>');
        /* options for editor */
        $options = array(
            "wpautop" => true,
            "media_buttons" => false,
            "textarea_name" => 'excerpt',
            "textarea_rows" => 3,
            "teeny" => true //use minimal editor configuration
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
        if (!isset($plugins["paste"])) {
            array_push($plugins, "paste");
        }
        if (!isset($plugins["charmap"])) {
            array_push($plugins, "charmap");
        }
        return $plugins;
    }

    /**
     * filter to add buttons to the "teeny" editor
     * this completely disregards the buttons array passed to it and returns a new array
     */
    public static function teeny_mce_buttons($buttons, $editor_id)
    {
        return array('bold', 'italic', 'underline', 'separator','pastetext', 'pasteword', 'removeformat', 'separator', 'charmap', 'blockquote', 'separator', 'bullist', 'numlist', 'separator', 'justifyleft', 'justifycenter', 'justifyright', 'separator', 'undo', 'redo', 'separator', 'link', 'unlink');
    }

}
/* end class definition */

/* register the Plugin with the Wordpress API */
RichTextExcerpts::register();

endif;