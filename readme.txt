=== Linkerator ===
Contributors: cr4ckp0t
Donate link: http://wowhead-tooltips.com/contact-me/donate/
Tags: links
Requires at least: 2.8.4
Tested up to: 2.8.4
Stable tag: trunk

Scans content for triggers you provide and replaces them with a link with the URL provided.

== Description ==

This plugin will scan the content for triggers that you provide it.  When it finds a trigger it will replace it with a link to the URL associated with the trigger.  This is useful for links that you use a lot, but don't want to have to write the anchor tags every time, just let the plugin do it for you.  It uses preg_replace() to make the replacements so it is as quick and efficient as possible.  You can specify an &lt;a&gt; class to be used with that link every time it is parsed.

Triggers can be added via the admin panel, or by creating triggers.txt in the same directory as the plugin.  Each entry should be on its own line, and fields separated with a pipe ('|').  See the included triggers.txt for the correct format.

If you have multiple WordPress blogs you can automatically export your triggers into `triggers.txt` and upload them to your other blog.

== Installation ==

1. Upload `linkerator` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add triggers via the admin menu.
1. **OPTIONAL** Set the permissions (CHMOD) of `linkerator` to 0777 or 0755, depending on your host.

== Frequently Asked Questions ==

= Will this plugin slow down execution time? =
* In theory, yes, depending on the number of entries, but the increased load times will be basically unnoticeable.

= Where are the triggers stored? =
* They are stored in WordPress' database in the {prefix)linkerator SQL table to make execution as quick as possible.

= Can I see it in action? =
* Yes I have a working demo on the scripts page.  http://www.wowhead-tooltips.com/tools/wordpress/linkerator/

= How can I contact you? =
* You can contact me by going to http://crackpot.ws/contact-me/.

== Screenshots ==
N/A

== Changelog ==

= 0.1 =
* Initial release

