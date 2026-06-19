# Plugin Scaffold Workflow

Use this skill when creating or expanding an encapsulated Zen Cart plugin.

## Steps

- start with `manifest.php`, `filenames.php`, and `Installer/ScriptedInstaller.php`
- add `database_tables.php` when the plugin introduces table-name constants
- decide whether the feature belongs in `catalog/`, `admin/`, or both
- add `FILENAME_*` constants for new entrypoints before wiring links or admin pages
- keep catalog pages aligned across `header_php.php`, `main_template_vars.php`, language files, and templates
- add admin language and menu-definition files for every admin entrypoint
- add `psr4Autoload.php` only when extra namespace registration or plugin-local Composer autoloading is required
- allowlist the plugin in `zc_plugins/.gitignore` when creating a new plugin
- install the plugin through Plugin Manager before trusting runtime behavior
