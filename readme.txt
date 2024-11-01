=== Simple Gmail Login ===

Contributors: victor_jonsson
Tags: Gmail, wp-admin, login
Requires at least: 2.5
Donate link: http://victorjonsson.se/donations/
Tested up to: 3.5
Stable tag: 1.2.8
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With this plugin you can login to wp-admin using your GMail credentials

== Description ==

Once you have installed this plugin you can login to wp-admin using your ordinary user name (or your email)
and your password on GMail (your old wordpress password still works as well).

= Additional features =
- You'll get a log of all login activity in wp-admin
- Add an HTML-snippet that will be added below the login form


== Installation ==

1. Install the plugin via the WordPress.org plugin directory
2. Your set to go! Now you can login to wp-admin using your GMail credentials

==  Changelog ==

= 1.2.7 =
- No longer removes log when updating plugin
- Now possible to clear the log

= 1.2.6 =
- Moved wp_enqueue_style to correct action

= 1.2.5 =
- Fixed time bug in log
- Fixed bug in options page

= 1.2.3 =
- Fixed bug that made the log look empty in the dashboard widget

= 1.2 =
- Now validates login credentials against wp database before GMail
- Now possible to add an html snippet that will be displayed below the login form

= 1.1.4 =
- Exception no longer thrown if timezone isn't set
- Fixed bug related to systems having safe_mode or open_basedir turned on
- Dashboard widget is not registered if log isn't writeable