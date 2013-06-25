/*
Plugin Name: Rich Text Excerpts
Plugin URI: http://wordpress.org/extend/plugins/rich-text-excerpts/
Description: Adds rich text editing capability for excerpts using wp_editor()
Author: Peter Edwards
Author URI: http://bjorsq.net
Version: 1.3beta
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
	/* this block hides and shows the options for the teeny version of the editor */
	if ($('.rte-options-editor-type').length) {
		/* add a handler to the radio buttons used for editor type selection */
		$('.rte-options-editor-type').click(function(){
			check_editor_options();
		});
		/* initial check on page load */
		check_editor_options();
	}
	/* this block prevents the de-selection of all post types on the settings page */
	if ($('.rte-post-types').length) {
		/* keep track of the last checkbox checked */
		var lastchecked = false;
		/* add a handler to the checkboxes for post type support */
		$('.rte-post-types').click(function(e){
			lastchecked = $(this);
			check_post_type_options(e);
		});
		/* initial check on page load */
		check_post_type_options();
	}
	/**
	 * function to check the editor type selected for the plugin and show/hide the options
	 * for the appropriate type
	 */
	function check_editor_options()
	{
		if ($('.rte-options-editor-type').length) {
			if ($('#rich_text_excerpts_options-editor_type-teeny').is(':checked')) {
				$('#editor_type_teeny_options').show();
				$('#editor_type_tiny_options').hide();
			} else {
				$('#editor_type_teeny_options').hide();
				$('#editor_type_tiny_options').show();
			}
		}
	}
	/**
	 * function to check the post-types checkboxes and ensure that one is checked
	 */
	function check_post_type_options(evt)
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
	}
	/**
	 * this removes the click.postboxes handler added by wordpress to the .postbox h3
	 * for the rich text excerpt editor. This is because the editor is placed in a 
	 * static metabox (the postbox class is used for formatting only) - it cannot be
	 * expanded, hidden or moved. This will only be invoked if the editor is added
	 * using edit_page_form and edit_form_advanced hooks to make the editor static.
	 */
	if ($('.rte-wrap').length) {
		/* turn off javascript on postbox heading - leave a little time for it to be added first */
		window.setTimeout(function(){jQuery('.rich-text-excerpt h3').unbind('click.postboxes');},500);
	}
	/**
	 * TinyMCE doesn't handle being moved in the DOM.  Destroy the
	 * editor instances at the start of a sort and recreate 
	 * them afterwards.
	 * From a comment by devesine on the TRAC ticket:
	 * http://core.trac.wordpress.org/ticket/19173
	 */
	var _triggerAllEditors = function(event, creatingEditor) {
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
	/**
	 * these functions will be invoked if the editor is placed inside a metabox
	 */
	$('#poststuff').on('sortstart', function(event) {
		_triggerAllEditors(event, false);
	}).on('sortstop', function(event) {
		_triggerAllEditors(event, true);
	});
});
