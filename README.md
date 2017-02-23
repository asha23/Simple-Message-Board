# Wordpress Simple Message Board Plugin

This is a moderated WordPress message board plugin. It works independently from the comments system and is designed for simple use.

A new table is created in your database called _messages - This allows you to track the messages independently of your WordPress tables.

## Shortcodes

``` [messages_teaser] ```

Creates a frontend teaser for the message

``` [messages_form] ```

Creates a form for the message

``` [messages] ```

Creates full message

## Dependencies

This uses Bower for dependencies. You might want to edit these as you see fit. It uses bootstrap out of the box for styling and masonry to create the blocks. Matchheight is also included if you prefer this option to arrange you columns. Cycle 2 slider has been used for the message teaser.

Edit the scripts in frontend.php to adjust these if you like.

Frontend.php is also where all the HTML is created, edit this as you like.

# Roadmap/Todo

* Submit to WordPress.com
* Add a settings page for captcha api keys, styling options, view methods
* Consolidate the code
* Add the ability to edit entries before moderation
* Make it platform independent. IE, remove all custom styling
* Add more options for the teaser - Carousel, Short list, Widget, etc.
* Translation functionality
* Fix a few annoying bugs
* Add the option to download all the messages as a csv
* Add the option to destroy the table when you delete the Plugin
