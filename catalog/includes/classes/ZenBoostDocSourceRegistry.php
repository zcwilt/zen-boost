<?php

class ZenBoostDocSourceRegistry
{
    public static function all(): array
    {
        return [
            [
                'url' => 'https://docs.zen-cart.com/dev/',
                'tags' => ['developer-docs'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/',
                'tags' => ['plugins'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated_plugins/',
                'tags' => ['plugins', 'encapsulated-plugins'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/plugins/encapsulated_plugins/manifests/',
                'tags' => ['plugins', 'manifest'],
            ],
            [
                'url' => 'https://docs.zen-cart.com/dev/schema/',
                'tags' => ['schema'],
            ],
        ];
    }
}
