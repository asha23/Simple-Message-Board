# Wordpress Simple Message Board

This is a moderated WordPress message board. It works independently from the comments system and is designed for simple use.

A new table is created in your database called _messages 

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
