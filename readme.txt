=== Media Attachment Bulk Move ===
Contributors: sunphetkong
Tags: media, attachments, bulk, move, manage
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.2.3
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

View and bulk move media attachments between posts/pages directly from the post editor.

== Description ==

Media Attachment Bulk Move is a simple yet powerful plugin that helps you manage media files attached to your posts and pages. View all attached media in one place and easily move files between posts with a user-friendly interface.

= Features =

* **View All Attachments** - See all media files attached to any post/page in a clean grid layout
* **Bulk Selection** - Select multiple files with checkboxes
* **Bulk Move** - Move selected files to another post/page with autocomplete search
* **File Preview** - Thumbnail previews for images, mime-type icons for other files
* **File Details** - View file type, size, and upload date at a glance
* **Quick Actions** - View, edit, detach, or delete individual files
* **Autocomplete Search** - Search for destination posts/pages by name with live results
* **Works with All Post Types** - Automatically adds to all public post types

= Use Cases =

* Organize media files when restructuring content
* Move images from draft posts to published posts
* Clean up orphaned attachments
* Reorganize media after importing content
* Manage media in custom post types

= Developer Friendly =

* Clean, well-documented code
* Uses WordPress coding standards
* Follows WordPress plugin guidelines
* Fully translatable (i18n ready)
* Singleton pattern implementation
* Uses WordPress default libraries (jQuery, Dashicons)

== Installation ==

1. Upload the `media-attachment-bulk-move` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit any post or page to see the "Attached Media Files" meta box

== Frequently Asked Questions ==

= Does this work with custom post types? =

Yes! The plugin automatically adds the meta box to all public post types (except attachments themselves).

= Will moving files break embedded images in posts? =

No. Moving attachments only changes the parent post relationship. Embedded images and media in content will continue to work normally.

= Can I move files to posts of different post types? =

Yes! You can move attachments from a page to a post, or to any custom post type.

= What happens when I "detach" a file? =

Detaching removes the attachment's connection to the post, but keeps the file in your Media Library. The file remains accessible.

= What permissions do I need? =

You need `upload_files` capability to move and detach files, and `delete_posts` capability to delete files permanently.

= Is this translation ready? =

Yes! The plugin is fully internationalized and translation-ready. All strings use proper WordPress i18n functions.

== Screenshots ==

1. Meta box showing attached media files with thumbnails and file details
2. Bulk selection mode with autocomplete post search
3. File actions: View, Edit, Detach, Delete
4. Responsive layout on mobile devices

== Changelog ==

= 1.2.3 - 2026-01-16 =
* Fixed text domain: Changed from constant to string literal for proper i18n parsing
* Updated "Tested up to" WordPress version
* Removed unused TEXT_DOMAIN constant

= 1.2.2 - 2026-01-16 =
* Added translator comments for _n() function calls
* Fixed Plugin Check warnings
* Improved code quality and standards compliance

= 1.2.1 - 2026-01-16 =
* Enhanced i18n compliance with proper text escaping
* Improved security with input sanitization
* Updated prefix from `pc-` to `mabm-` for better namespacing
* Restructured translations for JavaScript strings
* Added text domain loading on init
* Code refactoring following WordPress coding standards

= 1.2.0 - 2026-01-16 =
* Improved UI: Filename now displays on top row for better visibility
* Enhanced layout with two-row design (header + body)
* Full filename display with word wrapping
* Better visual hierarchy for attachment cards

= 1.1.1 - 2026-01-16 =
* Fixed checkbox click issues in environments with TinyMCE conflicts
* Improved click event handling with stopPropagation
* Enhanced checkbox clickability with higher z-index
* Better event delegation for checkbox interactions

= 1.1.0 - 2026-01-16 =
* Added bulk move functionality
* Implemented autocomplete post search
* Added select all checkbox
* Visual feedback for selected items
* Success notification with link to target post

= 1.0.0 - 2026-01-16 =
* Initial release
* View all attachments in meta box
* Individual file actions (view, edit, detach, delete)
* Responsive grid layout
* File previews and metadata display

== Upgrade Notice ==

= 1.2.1 =
Security and i18n improvements. Recommended update for all users. Note: CSS/JS prefixes changed - clear browser cache after update.

= 1.2.0 =
Improved UI with better filename visibility. Clear your browser cache to see the new layout.

= 1.1.0 =
Major feature update! Now includes bulk move functionality with autocomplete search.

== Additional Information ==

For bug reports and feature requests, please visit:
https://github.com/Sun-Phetkong/media-attachment-bulk-move

= Credits =

Developed by Sun Phetkong

= Support =

For support and questions, please use the WordPress.org support forum for this plugin.
