<?php

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 *
 * @link      https://github.com/job963/oxProbs
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @copyright (C] Joachim Barthel 2012-2017
 * @version   0.9.0
 *
 */

$ModulePath = OxidEsales\Eshop\Core\Registry::get(OxidEsales\Eshop\Core\ViewConfig::class)->getModulePath('oxprobs');

$aModule = [
    'id' => 'oxprobs',
    'title' => 'OxProbs - Data Problem Analysis',
    'description' => [
        'de' => 'Analyse-Modul zum Auffinden problematischer Shop Daten.<br><ul>'
        . '<li>in Artikeln, Versand, Gruppierungen, Benutzern, Bestellungen und Bildern'
        . '<li>erweiterbar durch eigene Analysen'
        . '<li>alle Analysen können gedruckt und als CSV exportiert werden</ul>',
        'en' => 'Analysis module for finding problematical shop data.<br><ul>'
        . '<li>in Articles, Shipping, Groups, Users, Orders and Images'
        . '<li>extensible by your own analysis'
        . '<li>all Analysis can be printed and exported as CSV</ul>',
    ],
    'thumbnail' => 'oxprobs.png',
    'version' => '0.10.0',
    'author' => 'Joachim Barthel',
    'url' => 'https://github.com/job963/oxProbs',
    'email' => 'jobarthel@gmail.com',
    
    'controllers' => [
        'oxprobs_articles' => \Job963\Oxid\HealthCheck\Controller\Admin\Articles::class,
        'oxprobs_delivery' => \Job963\Oxid\HealthCheck\Controller\Admin\Delivery::class,
        'oxprobs_groups' => \Job963\Oxid\HealthCheck\Controller\Admin\Groups::class,
        'oxprobs_users' => \Job963\Oxid\HealthCheck\Controller\Admin\Users::class,
        'oxprobs_orders' => \Job963\Oxid\HealthCheck\Controller\Admin\Orders::class,
        'oxprobs_pictures' => \Job963\Oxid\HealthCheck\Controller\Admin\Pictures::class,
    ],
    'templates' => [
        'oxprobs_articles.tpl' => 'jx/HealthCheck/views/admin/tpl/oxprobs_articles.tpl',
        'oxprobs_delivery.tpl' => 'jx/HealthCheck/views/admin/tpl/oxprobs_delivery.tpl',
        'oxprobs_groups.tpl' => 'jx/HealthCheck/views/admin/tpl/oxprobs_groups.tpl',
        'oxprobs_users.tpl' => 'jx/HealthCheck/views/admin/tpl/oxprobs_users.tpl',
        'oxprobs_orders.tpl' => 'jx/HealthCheck/views/admin/tpl/oxprobs_orders.tpl',
        'oxprobs_pictures.tpl' => 'jx/HealthCheck/views/admin/tpl/oxprobs_pictures.tpl',
    ],
    'settings' => [
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'sOxProbsEANField',
            'type' => 'select',
            'value' => 'oxean',
            'constrains' => 'oxean|oxdistean',
            'position' => 0,
        ],
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'sOxProbsMinDescLen',
            'type' => 'str',
            'value' => '15',
        ],
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'sOxProbsBPriceMin',
            'type' => 'str',
            'value' => '0.5',
        ],
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'sOxProbsMaxActionTime',
            'type' => 'str',
            'value' => '14',
        ],
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'bOxProbsProductPreview',
            'type' => 'bool',
            'value' => true,
        ],
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'bOxProbsProductActiveOnly',
            'type' => 'bool',
            'value' => true,
        ],
        [
            'group' => 'OXPROBS_ARTICLESETTINGS',
            'name' => 'bOxProbsProductTimeActive',
            'type' => 'bool',
            'value' => true,
        ],
        [
            'group' => 'OXPROBS_ORDERSETTINGS',
            'name' => 'sOxProbsOrderPaidLater',
            'type' => 'str',
            'value' => 'oxidinvoice,oxidcashondel',
        ],
        [
            'group' => 'OXPROBS_ORDERSETTINGS',
            'name' => 'sOxProbsOrderPaidbyCIA',
            'type' => 'str',
            'value' => 'oxidpayadvance',
        ],
        [
            'group' => 'OXPROBS_ORDERSETTINGS',
            'name' => 'sOxProbsOrderPaidbyInvoice',
            'type' => 'str',
            'value' => 'oxidinvoice',
        ],
        [
            'group' => 'OXPROBS_ORDERSETTINGS',
            'name' => 'sOxProbsOrderIgnoredRemark',
            'type' => 'str',
            'value' => 'Hier k%nnen Sie uns noch etwas mitteilen.',
        ],
        [
            'group' => 'OXPROBS_PICTURESETTINGS',
            'name' => 'sOxProbsPictureDirs',
            'type' => 'select',
            'value' => 'master',
            'constrains' => 'master|generated',
            'position' => 0,
        ],
        [
            'group' => 'OXPROBS_INCLUDESETTINGS',
            'name' => 'aOxProbsArticleIncludeFiles',
            'type' => 'arr',
            'value' => [],
            'position' => 1,
        ],
        [
            'group' => 'OXPROBS_INCLUDESETTINGS',
            'name' => 'aOxProbsGroupIncludeFiles',
            'type' => 'arr',
            'value' => [],
            'position' => 1,
        ],
        [
            'group' => 'OXPROBS_INCLUDESETTINGS',
            'name' => 'aOxProbsDeliveryIncludeFiles',
            'type' => 'arr',
            'value' => [],
            'position' => 1,
        ],
        [
            'group' => 'OXPROBS_INCLUDESETTINGS',
            'name' => 'aOxProbsUsersIncludeFiles',
            'type' => 'arr',
            'value' => [],
            'position' => 1,
        ],
        [
            'group' => 'OXPROBS_INCLUDESETTINGS',
            'name' => 'aOxProbsOrdersIncludeFiles',
            'type' => 'arr',
            'value' => [],
            'position' => 1,
        ],
        [
            'group' => 'OXPROBS_INCLUDESETTINGS',
            'name' => 'aOxProbsPicturesIncludeFiles',
            'type' => 'arr',
            'value' => [],
            'position' => 1,
        ],
        [
            'group' => 'OXPROBS_DOWNLOAD',
            'name' => 'bOxProbsHeader',
            'type' => 'bool',
            'value' => 'true',
        ],
        [
            'group' => 'OXPROBS_DOWNLOAD',
            'name' => 'sOxProbsSeparator',
            'type' => 'select',
            'value' => 'comma',
            'constrains' => 'comma|semicolon|tab|pipe|tilde',
            'position' => 0,
        ],
        [
            'group' => 'OXPROBS_DOWNLOAD',
            'name' => 'bOxProbsQuote',
            'type' => 'bool',
            'value' => 'true',
        ],
        [
            'group' => 'OXPROBS_DOWNLOAD',
            'name' => 'bOxProbsStripTags',
            'type' => 'bool',
            'value' => 'true',
        ],
    ],
];
