<?php return array(
    'root' => array(
        'name' => '__root__',
        'pretty_version' => '12.5.x-dev',
        'version' => '12.5.9999999.9999999-dev',
        'reference' => '6975a339dd6363c0eb3b68f1f5d2ccff8c60951e',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '12.5.x-dev',
            'version' => '12.5.9999999.9999999-dev',
            'reference' => '6975a339dd6363c0eb3b68f1f5d2ccff8c60951e',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'friendsofphp/php-cs-fixer' => array(
            'dev_requirement' => true,
            'replaced' => array(
                0 => 'v3.95.31',
            ),
        ),
        'php-cs-fixer/shim' => array(
            'pretty_version' => 'v3.95.31',
            'version' => '3.95.31.0',
            'reference' => 'ecc5bd6c3a8151488dc6b9a81502655726bd0f1b',
            'type' => 'application',
            'install_path' => __DIR__ . '/../php-cs-fixer/shim',
            'aliases' => array(),
            'dev_requirement' => true,
        ),
    ),
);
