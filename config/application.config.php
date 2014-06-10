<?php
/**
 * Configuration file generated by ZFTool
 * The previous configuration file is stored in application.config.old
 *
 * @see https://github.com/zendframework/ZFTool
 */
return array(
    'modules' => array(
        'Application',
        'ZendDeveloperTools',
        'ZFTool',
        'DoctrineModule',
        'DoctrineORMModule',
        'ZfcBase',
        'ZfcAdmin',
        'ZfcUser',
        'ZfcUserDoctrineORM',
        'BjyAuthorize',
        'Catalog',
        'User',
        'Cart',
        'Product',
        'GoSession',
        'Register',
        'Data',
        'Admin'
        ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor'
            ),
        'config_glob_paths' => array('config/autoload/{,*.}{global,local}.php')
        )
    );
