<?php

class ZenAiAssistDocSourceRegistry
{
    public static function all(): array
    {
        return [
            [
                'url' => 'https://docs.zen-cart.com/dev/',
                'tags' => ['developer-docs'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/',
                'tags' => ['plugins', 'encapsulated-plugins'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/directory_structure/',
                'tags' => ['plugins', 'encapsulated-plugins', 'directory-structure'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/manifests/',
                'tags' => ['plugins', 'encapsulated-plugins', 'manifest'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/plugin_language_files/',
                'tags' => ['plugins', 'encapsulated-plugins', 'language-files'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/installer_classes/',
                'tags' => ['plugins', 'encapsulated-plugins', 'installer-classes'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/class_autoloading/',
                'tags' => ['plugins', 'encapsulated-plugins', 'class-autoloading'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/sql_installation/',
                'tags' => ['plugins', 'encapsulated-plugins', 'sql-installation'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated/converting/',
                'tags' => ['plugins', 'encapsulated-plugins', 'converting'],
                'required' => false,
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/database/',
                'tags' => ['database'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/testing/',
                'tags' => ['testing'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/architecture/',
                'tags' => ['architecture'],
            ],
        ];
    }
}
