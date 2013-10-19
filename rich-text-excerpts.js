/*
Plugin Name: Rich Text Excerpts
Plugin URI: http://wordpress.org/extend/plugins/rich-text-excerpts/
Description: Adds rich text editing capability for excerpts using wp_editor()
Author: Peter Edwards
Author URI: https://github.com/bjorsq/rich-text-excerpts
Version: 1.3.1
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
jQuery(document).ready(function($){
	/* variable and function assignment */

	/* keep track of the last checkbox checked */
	var lastchecked = false,
	/**
	 * function to check the editor type selected for the plugin and show/hide the options
	 * for the appropriate type
	 */
	check_editor_options = function()
	{
		if ($('#rich_text_excerpts_options-editor_type-teeny').prop('checked')) {
			$('#editor_type_teeny_options').show();
			$('#editor_type_tiny_options').hide();
		} else {
			$('#editor_type_teeny_options').hide();
			$('#editor_type_tiny_options').show();
		}
	},

	/**
	 * function to check the post-types checkboxes and ensure that one is checked
	 */
	check_post_type_options = function()
	{
		if ($('.rte-post-types').length) {
			if (!$('.rte-post-types:checked').length) {
				if (lastchecked === false) {
					$('.rte-post-types:first').attr('checked', 'checked');
				} else {
					lastchecked.attr('checked', 'checked');
				}
				$('.rte-post-types-error').show();
			} else {
				$('.rte-post-types-error').hide();
			}
		}
	},

	/** 
	 * function to show the meta box settings field if this is the method chosen to 
	 * add the excerpt editor.
	 */
	check_metabox_settings = function()
	{
		if ($('#rte-use-metabox').prop("checked")) {
			$('#rte-metabox-settings').show();
		} else {
			$('#rte-metabox-settings').hide();
		}
	},

	/**
	 * TinyMCE doesn't handle being moved in the DOM.  Destroy the
	 * editor instances at the start of a sort and recreate 
	 * them afterwards.
	 * From a comment by devesine on the TRAC ticket:
	 * http://core.trac.wordpress.org/ticket/19173
	 */
	_triggerAllEditors = function(event, creatingEditor) {
		var postbox, textarea;

		postbox = $(event.target);
		textarea = postbox.find('textarea.wp-editor-area');

		textarea.each(function(index, element) {
			var editor, is_active;

			editor = tinyMCE.EditorManager.get(element.id);
			is_active = $(this).parents('.tmce-active').length;

			if (creatingEditor) {
				if (!editor && is_active) {
					tinyMCE.execCommand('mceAddControl', true, element.id);
				}
			}
			else {
				if (editor && is_active) {
					editor.save();
					tinyMCE.execCommand('mceRemoveControl', true, element.id);
				}	   
			} 
		});
	};
	/* add event handlers and setup the form */
	if ($('#rich_text_excerpts_options_form').length) {

		/* add a handler to the radio buttons used for editor type selection */
		$('.rte-options-editor-type').on('click', function(){
			check_editor_options();
		});

		/* add a handler to the checkbox used for the editor metabox display option */
		$('#rte-use-metabox').on('click', function(){
			check_metabox_settings();
		});
		
		/* add a handler to the checkboxes for post type support */
		$('.rte-post-types').on('click', function(){
			lastchecked = $(this);
			check_post_type_options();
		});

		/* initial checks on page load */
		check_editor_options();
		check_metabox_settings();
		check_post_type_options();
	}

	/**
	 * this removes the click.postboxes handler added by wordpress to the .postbox h3
	 * for the rich text excerpt editor when it is placed in a static metabox (the 
	 * postbox class is used for formatting only). Only invoked if the editor is added
	 * using edit_page_form and edit_form_advanced hooks to make the editor static.
	 */
	if ($('.rich-text-excerpt-static').length) {
		/* turn off javascript on postbox heading - leave a little time for it to be added first */
		window.setTimeout(function(){jQuery('.rich-text-excerpt-static h3').unbind('click.postboxes');},500);
	}

	/**
	 * these functions will be invoked if the editor is placed inside a draggable metabox
	 * From a comment by devesine on the TRAC ticket:
	 * http://core.trac.wordpress.org/ticket/19173
	 */
	if ($('.rte-wrap-metabox').length) {
		$('#poststuff').on('sortstart', function(event) {
			_triggerAllEditors(event, false);
		}).on('sortstop', function(event) {
			_triggerAllEditors(event, true);
		});
	}
});