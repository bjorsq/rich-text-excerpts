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
jQuery(function($){
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
	$('.rte-options-editor-type').click(function(){
		check_editor_options();
	});
	check_editor_options();
});