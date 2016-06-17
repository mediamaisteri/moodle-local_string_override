# local_string_override

Moodle plugin that allows plugins to override existing translations.

Every now and then plugins need to change the way how Moodle behaves.
Changes in the behavior may however cause the existing translations to become
inconsistent with the new behavior. This plugin makes it possible for plugins
to override the existing translations regardless if they're bundled with Moodle
or they're originated from a community plugin.

## Installation

 1. Place the plugin source code to `/local/string_override/` directory
 2. Enable the string manager in `config.php` with:

     `$CFG->customstringmanager = 'local_string_override_manager';`

## How to use it

 1. Find the identifier of the string that you want to override
   - E.g. `auth_changepasswordhelp_expl`
 2. Find out the file where the translation is located
   - E.g. `/lang/en/auth.php`
 3. Create the file `<type>/<your_plugin>/lang/<lang_code>/<translation_file>.php`
   - E.g. `local/lazydog/lang/en/auth.php`
 4. Add the string identifier to the file using the standard translation file syntax

    `$string['auth_changepasswordhelp_expl'] = 'Your custom translation here';`
