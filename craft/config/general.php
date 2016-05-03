<?php

if (! defined('ENV')) {
	switch ($_SERVER['SERVER_NAME']) {
		case 'www.teslastockvalue.com':
			define('ENV', 'prod');
			break;
		case 'www.teslastockvalue.com':
			define('ENV', 'stage');
			break;
		default:
			define('ENV', 'local');
	}
}

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$protocol = $secure ? 'https://' : 'http://';

$domain = $_SERVER['SERVER_NAME'];
$root = $protocol . $domain;

$cdnUrl = ENV === 'prod' ? $root : $root;
$assetUrl = $cdnUrl . '/assets';

$isPjax = isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === 'true';

$config['*'] = [
	'addTrailingSlashesToUrls' => false,
	'allowAutoUpdates' => false,
	'cacheDuration' => false,
	'cacheMethod' => 'memcache',
	'cpTrigger' => 'cms',
	'defaultImageQuality' => 80,
	'errorTemplatePrefix' => '_errors/',
	'generateTransformsBeforePageLoad' => true,
	'maxUploadFileSize' => 512000000,
	'omitScriptNameInUrls' => true,
	'postCpLoginRedirect' => 'entries',
	'rememberedUserSessionDuration' => 'P100Y',
	'sendPoweredByHeader' => false,
	'siteUrl' => $root,
	'useCompressedJs' => true,
	'usePathInfo' => true,
	'defaultSearchTermOptions' => [
		'subRight' => true
	],

	// Global variables
	'env' => ENV,
	'assetUrl' => $assetUrl,
	'cdnUrl' => $cdnUrl,
	'dateFormat' => 'F j, Y',
	'isPjax' => $isPjax,
	'defaultLayout' => '_layouts/' . ($isPjax ? 'pjax' : 'master'),

	// Cache busting
	'cssVersion' => 1,
	'jsVersion' => 1,
	'jsLegacyVersion' => 1,

	// Environment variables
	'environmentVariables' => [
		'basePath' => $_SERVER['DOCUMENT_ROOT'] . '/',
		'cdnUrl' => $cdnUrl
	],

	// Default entry values
	'entryDefaults' => [
		'title' => '',
		'seoTitle' => '',
		'seoDescription' => '',
		'seoNoIndex' => false
	]
];

switch (ENV) {
	case 'prod':
		// $config[$domain] = [
		// ];
		break;
	case 'stage':
		// $config[$domain] = [
		// ];
		break;
	case 'local':
		$config[$domain] = [
			'allowAutoUpdates' => true,
			'cacheMethod' => 'file',
			'devMode' => true,
			'translationDebugOutput' => false,
			'useCompressedJs' => false
		];

		$localConfig = @include(CRAFT_CONFIG_PATH . '/local/general.php');

		array_merge($config[$domain], $localConfig);
}

return $config;