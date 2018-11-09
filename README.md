Rich Text Excerpts
==================

A Wordpress plugin which swaps out the textarea used for excerpts with a TinyMCE editor instance.

Tags: excerpt, editor, TinyMCE, formatting
Requires at least Wordpress version 3.5
Tested up to Wordpress version 5
License: [GPLv3](http://www.gnu.org/licenses/gpl.html)

Description
-----------

The Plugin uses the [wp_editor](http://codex.wordpress.org/Function_Reference/wp_editor) function to generate a rich text editor for page/post excerpts placed in a meta box on the edit screen, so **will only work in Wordpress 3.5 or greater**. This plugin **does not currently work with the Gutenberg editor** - users of WordPress 5.0 and above should activate the [Classic Editor Plugin](https://wordpress.org/plugins/classic-editor/) if they wish to continue to use this plugin.

The plugin removes the excerpt meta box and replaces it with a new one containing the TinyMCE editor using the [wp_editor](http://codex.wordpress.org/Function_Reference/wp_editor)function. You can set the options for the rich text editor in the plugin options page (in the Wordpress Settings menu). You can also use a "teeny" version of the editor with limited buttons, and configure those buttons on the plugin options page.

Installation
------------

1. Upload the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin using the Plugin options page under the Settings menu

If you want to use excerpts in pages, add this to your theme's `functions.php` file:

`add_post_type_support( 'page', 'excerpt' );`

See [add_post_type_support](http://codex.wordpress.org/Function_Reference/add_post_type_support) in the Codex for details.
 
If you want to use excerpts in Custom Post Types, do it when you create them using the `supports` array in the arguments for [register_post_type](http://codex.wordpress.org/Function_Reference/register_post_type).

Frequently Asked Questions
--------------------------

### I activated the plugin, but the rich text editor isn't working for [pages|custom post types] - why?

This plugin has a settings page where you can configure which post types use a rich text editor for their excerpts - by default only posts will use the rich text editor. The post types displayed on the settings page are only those which support excerpts, so if you don't see your post type here, it doesn't support manual excerpts.

### My excerpts don't appear on my site with the formatting I apply using the editor - why?

This plugin only adds the ability to edit excerpts in the Wordpress admin area using a Rich Text editor. How these excerpts are displayed on your site, or whether they are displayed at all, is the responsibility of the theme you are using.

If they use excerpts, most themes will use `get_the_excerpt()` outside the wordpress 'loop', or `the_excerpt()` inside the loop. However, there are other wordpress functons which can be used to get excerpts, such as [`wp_trim_excerpt()`](http://codex.wordpress.org/Function_Reference/wp_trim_excerpt), which will generate an excerpt from the post content rather than using the manual excerpt field. Themes could also remove all formatting and shortcodes from excerpts.

### My excerpts contain shortcodes, but they aren't showing up on my site - why?

The way excerpts are displayed on your site, or whether they are displayed at all, is the responsibility of the theme you are using (not this plugin).

If you have access to the theme files, there are a number of ways you can process shortcodes in excerpts, depending on the method used to display them in the theme. For instance, if the excerpt is added to pages/posts using `the_excerpt()`, you could change this to `echo do_shortcode(get_the_excerpt())`. Another approach would be to try the techniques outlined here:

http://stephanieleary.com/2010/02/using-shortcodes-everywhere/

### I'm using another plugin which alters excertps in the Wordpress Admin area, and this plugin disables these features - can this be fixed?

Other plugins which enhance the excerpt editor in some way will probably have issues with this plugin, as it removes and re-adds the excerpt editor meta box. The best solution would be to encourage the developers of the other plugin to utilise a rich text editor in their plugin (it isn't hard!).

Changelog
---------

### 1.3.5

* It is currently not possible to change the excerpt input when using the Gutenberg Editor. The plugin disables itself if it detects that Gutenberg is active.
* Removed Finnish translation in response to user feedback.
* Tidied up code to more closely adhere to WordPress coding standards.
* Removed the option to show the Excerpt editor in a static box and increased minimum version to 3.5.
* Bumped “Tested up to” value to 5


### 1.3.4

* started using admin_enqueue_scripts to enqueue all scripts and styles.
* Added icon
* Bumped “Tested up to” value to 4.9.4

### 1.3.3

* Incorporated _some_ of the suggestions in [this forum post](https://wordpress.org/support/topic/css-to-remove-space) about the padding around the editor
* Bumped "Tested up to" value to Wordpress 4.4.

### 1.3.2
* Updated Polish translation thanks to [alcoholdenat](https://github.com/alcoholdenat).
* Updated main plugin files to make them follow the [Wordpress Coding standards](http://make.wordpress.org/core/handbook/coding-standards/php/).
* Added Grunt build for language file compilation.
* Bumped "Tested up to" value to Wordpress 4.0 and updated screnshot.
* Split javascript into two files and changed javascript and css inclusion strategy in response to [this forum post](http://wordpress.org/support/topic/js-injection-in-back-end).
* Added an option to set the height of the editor.

### 1.3.1
* added extra CSS styles to the editor, incorporated from [this forum post](http://wordpress.org/support/topic/better-look-with-a-few-extra-lines-of-code).
* re-built translations to reflect new admin options, and incorporated the changes suggested by Johan Pirlouit in [this forum post](http://wordpress.org/support/topic/french-translation-updated-and-a-few-other-minor-things-fixed).
* started to use `wp_kses_decode_entities` in addition to `html_entity_decode` on content before it is displayed in the editor to stop the behaviour described in [this forum post](http://wordpress.org/support/topic/special-characters-show-as-their-character-codes).

### 1.3
* made register_plugin_options() static (bugfix).
* added option to use a meta box instead of placing the excerpt statically using edit_form_advanced or edit_page_form.

### 1.2.1
* removed a debugging setting from the plugin which set the `textarea_rows` to 20. Setting `textarea_rows` in `wp_editor()` doesn't appear to have any effect anyway.
* tidied up javascript and added error checking for selection of post types.
* additional error checking in the validation routine.

### 1.2
* Added translations (no idea whether these work though!).
* Fixed some bugs in plugin options saving/retrieval.
* Fixed post type support.
* Put the editor in a container (suggestion from Chris Van Patten).
* changed screenshot.

### 1.1
* Addition of plugin options page to Wordpress Admin area, giving users the ability to configure the operation of the plugin.
* Fixed minor bug relating to editor display which was triggered in some circumstances.

### 1.0
* Wordpress submission after initial development on bitbucket.

Contribute
----------

Please contribute to the development of this plugin by using it and reporting issues via the Wordpress plugins forum. If you are using it successfully, please post a review and rate the plugin.

I'm maintaining this plugin on Github:

https://github.com/bjorsq/rich-text-excerpts

If you would like to make any improvements to the codebase or to translations, please do so by forking it and issuing a pull request when you have made the changes. 

All releases are tagged on github, and eventually find their way into the Wordpress Plugin Directory.

Translations
------------

If you can translate this plugin, or improve on the existing translations (most of which were made using [Google translate](http://translate.google.com/)), please get in touch either through the [Wordpress support forum](http://wordpress.org/support/plugin/rich-text-excerpts) or via [GitHub](https://github.com/bjorsq/rich-text-excerpts).

Many thanks to [alcoholdenat](https://github.com/alcoholdenat) for an updated Polish translation (in the 1.3.2 release).

### Available Languages

Afrikaans (af), Albanian (al), Arabic (ar), Basque (eu), Belarusian (be_BY), Bulgarian (bg_BG), Bosnian (bs_BA), Catalan (ca), Chinese (zh_CN), Croatian (hr), Czech (cs_CZ), Danish (da_DK), Dutch (nl_NL), Esperanto (eo), Estonian (et), Finnish (fi), French (fr_FR), Gaeilge (ga), Galician (gl_ES), Georgian (ge_GE), German (de_DE), Greek (el_GR), Hebrew (he_IL), Hindi (hi_IN), Hungarian (hu_HU), Icelandic (is_IS), Indonesian (id_ID), Italian (it_IT), Japanese (ja), Javanese (jv_JV), Kannada (kn_IN), Khmer (km_KH), Korean (ko_KR), Lao (lo), Latvian (lv), Lithuanian (lt_LT), Macedonian (mk_MK), Malay (ms_MY), Norwegian (nb_NO), Persian (fa_IR), Polish (pl_PL), Portuguese (pt_PT), Romanian (ro_RO), Russian (ru_RU), Serbian (sr_RS), Slovak (sk_SK), Slovenian (sl_SI), Spanish (es_ES), Swedish (sv_SE), Tamil (ta_LK), Thai (th), Turkish (tr), Ukranian (uk), Urdu (ur), Vietnamese (vi), Welsh (cy).
