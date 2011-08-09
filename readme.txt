=== WPML Multilingual CMS ===
Contributors: icanlocalize
Donate link: http://wpml.org/?page_id=2312
Tags: CMS, navigation, menus, menu, dropdown, css, sidebar, pages, i18n, translation, localization, language, multilingual, WPML
Requires at least: 2.8
Tested up to: 3.0.3
Stable tag: 2.0.4

Allows building complete multilingual sites with WordPress.

== Description ==

**WPML combines multilingual content authoring with powerful translation management. It powers corporate sites and is simple enough for bloggers.**

= Features =

[WPML](http://wpml.org) makes it possible to turn WordPress blogs multilingual in a few minutes with no knowledge of PHP or WordPress.
Its advanced features allow professional web developers to build full multilingual websites.

 * Turns a single WordPress site into a multilingual site.
 * Powerful translation management, allowing teams of translators to work on multilingual sites.
 * Built-in theme localization without .mo files.
 * Comments translation allows you to moderate and reply to comments in your own language.
 * Integrated professional translation (optional feature for folks who need help translating).
 * Includes CMS navigation elements for drop down menus, breadcrumbs trail and sidebar navigation.
 * Robust links to posts and pages that never break.

= Commercial Support =

[ICanLocalize](http://www.icanlocalize.com/site/) offers reliable commercial support for WPML. This support provides timely and dependable help directly from the developers.

= Learn More =

[WPML website](http://wpml.org)

== Installation ==

1. Place the folder containing this file into the plugins folder
2. Activate the plugin from the admin interface

WPML needs to create tables in your database. These tables are used to hold the new language information. In order to use WPML, your MySQL user needs to have sufficient privileges to create new tables in the database.

For help, visit the support forum - http://forum.wpml.org.

== Frequently Asked Questions ==

= Can I translate myself, or do I need to pay for it? =

You can certainly translate your site yourself. The professional translation is an optional feature intended for people who don't want to translate themselves.

If you're translating your site yourself, just ignore that option.

= Everything in the theme still appears without translation =

Have a look at our theme localization guide.

= Languages per directories are disabled =

To be able to use languages in directories, you should use a non-default permlink structure.

== Screenshots ==

1. Translation controls in posts and pages lists.
2. Translation controls in edit screen.
3. String translation panel for translating plugins and themes.
4. Language setup admin screen.

== Changelog ==

= 2.0.4 =
* Fixed the interface with ICanLocalize. Some jobs became stuck and this release allows to complete them.
* Fixed bugs in the translation management section related with custom types and canceling / redoing jobs.
* Added project summary on ICanLocalize to the translation dashboard.
* Translate page slugs too, for fine control over multilingual SEO.
* Fixed problem with URL redirection related to URLs with Russian or Asian characters.
* Fixed a bug that causes wp_list_pages to drop pages after sending jobs to translation.
* Added back the option to send jobs to the first available translator in ICanLocalize.
* Fixed PHP bug when adding more local translators.

= 2.0.3 =
* Fix bug with pages disappearing from wp_list_pages and ICanLocalize translations not being returned
* Added the option to send to the first available translator in ICanLocalize

= 2.0.2 =
* Added polling option for picking up translations from the server
* Added quote wizard for getting translations cost estimates
* Other small bug fixes 

= 2.0.1 =
* Added back option to set translated documents status.
* Fixed language parameter being added to the url twice.
* CMS Nav menu fix excluded non-pages children of pages.
* Fixes for admin texts translation.
* Fixed missing taxonomies fields on the translation editor.
* Fixed HTML encoding for displaying original elements in the translation editor.
* Fixed encoded & in post url and infinite loop for short urls in other languages than the default.
* Added progress of languages where translators were not selected yet.
* Rearranged the display filter in the translation dashboard.
* Fixed bug preventing WPML localization to work before the step 2 of the WPML setup wizard.
* Added ICanLocalize translators to the translators list on the dashboard.
* icl_ajax_url defined correctly for SSL.
* Added back debug function to reset the Professional Translation.

= 2.0.0 =
* Added Translator role.
* Added Translation Management toolset.
* Added Translation Editor.
* Added language configuration files.
* Compatibility packages are not obsolete and will be removed from the next version.

= 1.8.1.1 =
* Fixed source of warning messages in 1.8.1

= 1.8.1 =
* Bugfixes for the WP Nav Menus
* Improved translaiton interface
* Fixed bug with custom post type in WP_Query
* Fixed navigation url issues when using language added as a parameter
* Filter multiple post types
* Fixes for supporting https urls

= 1.8.0 =
* Added support for multilingual menus. Each language gets a different menus, linked as translations.
* Fixed bug with tag translations that have the same value.
* Fixed bug causing page order to change when adding translations.

= 1.7.9 =
* Fixed bug with pagination not showing on custom posts archive pages.
* Fixed bug for search results.
* Fixed bug for thinkbox popups.
* Fixed bug for auto adjusting IDs (e.g. post_parent).
* Fixed bug related to using WPML as a site wide activated plugin on WPMU.
* Added option to set strings language to be different than the default language.
* Option to scan WPMU plugins.
* Fixed bug related to http communication (Snoopy) with the translation server.
* Option to delete only specific translations.
* More bug fixes.

= 1.7.8.1 =
* Fixed bug with some js strings causing errors
* Fixed bug in icl_link_to_element (for pages)

= 1.7.8 =
* Added full support for WordPress custom posts.
* Added option to subscribe to paid support.
* Updated the CMS navigation to support custom posts and custom taxonomies.
* Fixed bug with 'menu_order' flag not being synced when using the PRO translation
* More bug fixes and better compatibility with WP 3.0


= 1.7.7 =
* Added full support for WordPress custom taxonomies.
* Fixed bugs with category and tag translation.
* icl_link_to_element fixed to work with auto-link adjustment.
* Works much faster when using the 'auto adjust IDs' function (caching results).

= 1.7.6 =
* Fixed bug that slipped through in 1.7.4 

= 1.7.4 =
* Works with WordPress 3.
* Fixed bug for wp_list_pages()/wp_list_categories() when Adjust IDs is on.
* Sync default categories upon change. Wasn't happening before.
* Fixed bug regarding get_pages() not working with Adjust IDs.
* Include private pages in the CMS navigation.
* Allow the flag 'private' to be synced between pages.
* icl_link_to_element() - added argument $return_original_if_missing (default true) Allows returning empty strings. 
* Author links by language.
* bugfix in the library used for communicating with ICanLocalize to work with HTTPS.

= 1.7.3 =
* Added languages editing, allowing to enter new languages to WPML.
* Added translation for texts in admin screens of themes and plugins.
* Fixed some issues with categories and tags when automaticallt adjust ids was on.
* Reverted language name for Persian (in Farsi).
* Language auto-update is disabled. Update is now only manual.

= 1.7.2 =
* Fixed bug related to using a revision for a translated page.
* Fixed language names Magyar, Croatian, Persian, Latvian.
* Set correct locales for Chinese (traditional and simplified).
* Fixed potential Cross Site Scripting (XSS) security hole.
* Sticky links works with single quote links too.
* Implemented Sticky links for strings.
* Fixed bug related to special (UTF-8) characters in translation body.
* Added warning on the reset function, to avoid accidental plugin data reset.

= 1.7.1 =
* Home-page URLs are now automatically adjusted per language.
* Added optional title to the language switcher (some theme don't display correctly without a title).
* Bug fix: subpages in different language than the default not rendering correctly when the auto-adjust IDs option was set.
* Bug fix: compatibility packages from the theme folder were not included.
* Added mysqldump feature on the troubleshooting page.
* Catalan, Slovenian, Serbian flags fixed.
* Fixed gallery links.
* Bug fix: WPMU - some strings translations for subblogs were wrong.

= 1.7.0 =
* WPML adapts itself to any WordPress theme, making any website fully multilingual without any changes.
* Added option to add a list of language links in the site's footer.
* Added controls for choosing what to synchronize between originals and translations.
* Added feature to allow hiding all contents per language.
* Fixed bug when quick editing tags.
* Switched locales for Chinese between simplified and traditional.
* Lots of other tiny bug fixes.

= 1.6.0 =
* Allows translating both themes and plugins in WPML's string translation.
* Existing translations in .mo files are imported to WPML's translation interface.
* Shows the source of strings for translation (both in the PHP and on the HTML).
* Cleaned and simplified string translation page.
* Fixed bugs for the option of showing the default language posts when translations are missing.
* Fixed search function when blog is set to display all posts with language fallback.
* Added placeholder for _cleanup_header_comment (for compatibility with WP 2.7 and below).
* Fixed some bugs with the tags and the tag cloud.
* Fixed bug with missing comments,
* Added correct Estonian flag.

= 1.5.1 =
* Fixed broken link to media library
* Fixed language selector appearance for people using ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS
* Tested WP 2.9 compatibility
* Fixed Ukranian flag
* Added support for PHP setups without json_decode
* Added compatibility package for Headspace2 SEO plugin
* Fixed bug in the 'icl_object_id' template function

= 1.5.0 =
* Added compatibility packages for Thematic, Hybrid and the default WordPress theme.
* Blog posts can be displayed in the default language if translations are missing.
* Language switcher can be customized to match the appearance of the site without editing CSS.
* Users can add notes for translation on posts and pages.
* Added an optional argument for specifying the breadcrumbs trail separator.
* Reporting 404 errors if translations don't exist for a URL.
* Fixed a bug which caused WPML to use HTTPS on certain Windows servers.
* Improved the way WPML imports the ajax.php files for more advanced WordPress installations.
* When importing .po files, only existing strings are updated and new strings are not created.
* Allow minimizing the ICanLocalize reminders box.
* Synchronize `allow_pings` and `allow_comments` between translations.
* Synchronize page templates for translations.
* Fix page parent synchronization for translation.
* Removed hard-coded flag sizes from HTML and moved to CSS and easy customization.
* Make sure that cache entries are not accumulated in the database.

= 1.4.0 =
* WPML now has two distinct modes - Basic and Advanced. Basic mode just for multilingual contents and Advanced for all the rest.
* Professional translation management is unified to one screen. No more Tools->Translation dashboard.
* Communication with translators is all done from within WordPress.
* Many bugs fixed.

= 1.3.5 =
* Improved the professional translation flow and streamlined it for easy usage.
* Avoids failing due to PHP errors in other plugins (caused various compatibility issues).
* Fixed a bug that prevented Sticky Links from working for links with anchors.
* Fixed bug for comments feed.
* Fix bug which caused Bulk Editing to change languages.
* After install, defaults to languages per directories if it's working on the server.
* Add language 'all', that allows listing contents in all languages (good for feeds).
* Turned the 'advanced' / 'basic' selection to a button.

= 1.3.4 =
* Changed the posts menu in the top navigation. Now its contents and position can be configured.
* Fixed a bug which prevented the preview changes from working.
* Fixed a bug in the language switcher, which sent the home page to the wrong URL.

= 1.3.3 =
* Huge speed improvements.
* Ability to prevent loading WPML's CSS and JS files.
* Fixed bugs for .po import/export.
* Corrected Czech flag.
* Fixed bug for feeds in other languages when using the language as a parameter in the url.
* Fixed bug for translation column not displaying after doing quick-edit.
* Fixed bug for comments count (was reporting SPAM comments too).
* Made multi-domain login optional. To enable, set `ICL_USE_MULTIPLE_DOMAIN_LOGIN` to true.

= 1.3.2 =
* Fixes to the comments translation. This will bring back comments that were hidden on translated pages after upgrading from a previous release.

= 1.3.1 =
* Added a translations summary to posts and pages lists.
* Fixed bug when replying to comments from the admin screen.
* Updated Chinese, Portuguese and Japanese translations.

= 1.3.0 =
* Added translation for comments.
* Allow associating existing contents as translation.
* Modified the layout of the translation boxes.
* Added a setup wizard.
* Fixed bugs that prevented WPML to work with MySQL in strict mode.
* Fixed bugs that prevented working with some other plugins.

= 1.2.1 =
* Allows specifying the locale for the default language.
* Added a theme integration file - docs/theme-integration/wpml-integration.php.
* Added an input for affiliate ID for themes.
* Simplified the setup for professional translation.

= 1.2.0 =
* Adds theme localization.
* ICanLocalize translation integration for theme and widget texts.
* Added string translation import and export using .po files.
* Fix for empty language tables bug.

= 1.1.0 =
* Adds translation for general texts, such as title, tagline and widgets.
* Can translate custom fields by ICanLocalize.
* Added an overview page, for quick access to all functions and a snapshot of WPML's status.

= 1.0.4 =
* Fixed the bug which caused errors when upgrading the plugin from previous versions.
* Fixed category and tag mess when using Quick-edit.
* Admin pages run much faster due to statistics caching and faster DB queries.
* Fixed name of blog page in cms-navigation section.
* Fixed compatibility with openID plugin.
* Fixed a bug that was caused when pages/posts had no title.
* Added `icl_object_id` which returns the ID of translated objects.
* Fixed permlinks for newly created posts (autosave by WordPress).
* Fixed bug which prevented sub-pages from being excluded from the navigation.
* Simplified the professional translation setup page.

= 1.0.3 =
* Added a hook for adding custom HTML in menu items.
* Added a function for creating multilingual links in themes.
* Cleaned up translation table, in case posts were deleted while WPML is inactive.
* Reverting to HTTP communication instead of HTTPs if a firewall is blocking us.

= 1.0.2 =
* Fixed language selector bug for some themes.
* Major improvements for translation database integrity.
* Fixed word count estimate for documents in Asian languages.
* Added a new Troubleshoot module, which allows getting translation table status and to reset the plugin.

= 1.0.1 =
* Fixed problems with all Asian languages and Norwegian.
* Fixed missing tables problem for people who upgraded from 0.9.9.
* Fixed CMS navigation drop down bug for IE6.
* Improved the display for the translation dashboard.

= 1.0.0 =
* Added the capability to translate contents, including posts, pages, tags and categories.
* Fixed HTML for the built in language selector.
* Fixed 'preview' functionality when using different domains per language.
* Fixed PHP error that popped when activating the plugin after upgrade.
* Fixed drafts count problem (the plugin didn't count correctly the number of drafts per language).

= 0.9.9 =
* Fixed problems with WordPress Gallery.
* Fixed error when using a static home page that's not translated (now, returns the 404 page).
* Fixed bug that prevented sticky links to work for pages.
* Fixed a CSS error in language-selector.css.
* Fixed a bug which created the RSS feed to have invalid XML (be an invalid feed).
* Fixed a bug which caused the default language to reset after plugin upgrade.
* Added WP-Http class for compatibility with WP 2.6.
* Added country flags as an option for the language switcher.
* Added a function that returns the languages information for building custom language switchers.
* Added the language name as the class for each entry in the languages selector, so that they can be styled individually.

= 0.9.8 =
* Fixed compatibility issues with Windows servers.
* Fixed bug with sticky post - mysql query error when no sticky post existed.
* Fixed search function.
* Prev/Next links for category archive pages are now working again.
* Add warning about disabled JavaScript (which is required for the plugin to work).
* Added debug information for hunting down stubborn bugs.
* Localized the admin section of the plugin to Spanish.

= 0.9.7 =
* Posts created via XML-RPC are assigned to the default language.
* Translated homepage displays correctly for blogs configured with 'language name passed as a parameter'.
* Defined a language contants that can be used in templates - `ICL_LANGUAGE_CODE`, `ICL_LANGUAGE_NAME`, `ICL_LANGUAGE_NAME_EN`.
* Split the stylesheet for the CMS Navigation into structure and design - users will be able to copy the design stylesheet and use it to override the plugin default style from their theme stylesheet.
* Fixed incorrect query when selecting categories in the admin panel, causing extra records to be added to the translation table when editing categories inline.

= 0.9.6 =
* Fixed search in different languages
* Fixed page edit links in different languages
* Custom language domains don't change back to default when switching to different language negotiation scheme.

= 0.9.4 =
* Custom domains per language work correctly (forced to WPML defaults before)
* Prevents from being activated on PHP4 (WPML only runs on PHP5)

= 0.9.3 =
* Fixed the Media Library (which the plugin disabled in the previous release).
* Checks against collision with other plugins (CMS-navigation and Absolute-links).
* Verified that per-directory language URLs can be implemented in WP.
* Split Portuguese to Brazilian and Portuguese (European) Portuguese.
* Fixed broken HTML in default category name for translations.
* Verify that the plugin can create the required database tables and warn if not.

= 0.9.2 =
* First public release

== Upgrade Notice ==

= 1.8.1 =
Multilingual menus bug fixes and improved translation interface

= 1.8.0 =
Added support for multilingual menus.

= 1.7.9 =
Added support for MultiSite, theme localization from any language and bug fixes.

= 1.7.1 =
Home page auto adjusts per language.

= 1.7.0 =
WPML automatically adjusts to any theme.

= 1.6.0 =
* WPML can now translate other plugins without editing .po/.mo files
