<?php

require 'report.php';

$config = @json_decode(@file_get_contents('.env'), true);
foreach( ['endpoint', 'siteId', 'accessToken'] as $configKey ) {
	if (!isset($config[$configKey]) || empty($config[$configKey])) {
		echo "[ERROR] config not found: `$configKey` @ '.env' \n";
		exit;
	}
}

// https://developer.matomo.org/4.x/api-reference/tracking-api
// https://developer.matomo.org/4.x/api-reference/reporting-api

// https://developer.matomo.org/api-reference/tracking-api
// https://developer.matomo.org/api-reference/reporting-api

print_r(matomoQueryReport(
	[
		'idSite' => $config['siteId'],
		'period' => 'day',
		'date' => '2023-10-21,2023-10-31',
		//'method' => 'VisitsSummary.get',
		//'method' => 'UsersManager.getUser',
		'method' => 'Actions.getPageUrls',
		//'columns' => implode(',', ['nb_uniq_visitors', 'nb_users']),
		'showColumns' => implode(',', ['nb_uniq_visitors', 'nb_users']),
		'expanded' => 1,
	]
	, $config['endpoint']
	, $config['accessToken']
));


