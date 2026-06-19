# Admin Page Workflow

Use this skill when adding or reviewing a Zen Cart admin page in an encapsulated plugin.

## Steps

- create the admin entrypoint under `admin/`
- add `admin/includes/languages/english/lang.<page>.php`
- add `admin/includes/languages/english/extra_definitions/lang.<page>_menu.php`
- register the admin page from the installer if it should appear in the admin menu
- name page-specific admin JavaScript after the PHP page filename when custom JS is needed
- preserve admin sanitization behavior and use documented whitelisting when a field needs relaxed sanitization
- keep admin-side extra configures, classes, and observers inside the encapsulated plugin version directory
