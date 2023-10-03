<?php
function getUserIP() {
	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];
	if (!empty($forward))
		$forward = @explode(",", $forward)[0];
	if(filter_var($client, FILTER_VALIDATE_IP))
		$ip = $client;
	else if(filter_var($forward, FILTER_VALIDATE_IP))
		$ip = $forward;
	else
		$ip = $remote;
	return $ip;
}

// https://developer.matomo.org/api-reference/tracking-api

function matomoPageview($requests = array(), $matomoIDSite = NULL, $matomoAPI = NULL, $matomoTokenAuth = NULL, $matomoAPITimeout = 5, $debug = false) {
	$output = array( 'status' => false, 'error' => NULL, 'info' => array() );
	$payload = array( 'requests' => array() );

	if (empty($matomoAPI) || !filter_var($matomoAPI, FILTER_VALIDATE_URL)) {
		$output['error'] = -1;
		return $output;
	}
	if (empty($matomoIDSite)) {
		$output['error'] = -2;
		return $output;
	}
	if (!is_array($requests)) {
		$output['error'] = -3;
		return $output;
	}
	if (!empty($matomoTokenAuth)) {
		$payload['token_auth'] = $matomoTokenAuth;
	}
	foreach($requests as $request) {
		$pass = true;
		$task = array();
		// https://developer.matomo.org/api-reference/tracking-api
		foreach( [
			'url' => true, 
			'action_name' => true,
			'uid' => true,
			] as $fieldName => $mustHave ) {
			if (!isset($request[$fieldName])) {
				if (!$mustHave)
					continue;
				array_push($output['info'], array(
					'raw' => $request,
					'failed' => 'no '.$fieldName,
				));
				$pass = false;
				break;
			}
			$task[$fieldName] = $request[$fieldName];
		}
		// token_auth
		foreach( [
			'cip', 'cdt', 'country', 'region', 'city', 'lat', 'long',
			] as $fieldName) {
			if (isset($request[$fieldName]) && !isset($payload['token_auth'])) {
				array_push($output['info'], array(
					'raw' => $request,
					'failed' => $fieldName . ' with empty token_auth',
				));
				$pass = false;
				break;
			}
			$task[$fieldName] = $request[$fieldName];
		}
		if ($pass) {
			$task['idsite'] = $matomoIDSite;
			$task['apiv'] = 1;
			$task['rec'] = 1;

			foreach($request as $key => $value) {
				$task[$key] = $value;
			}

			array_push($payload['requests'], '?'.http_build_query($task));
		}
	}
	$taskCount = count($payload['requests']);
	$requestCount = count($requests);
	if ($taskCount == 0 || $taskCount != $requestCount) {
		array_push($output['info'], array(
			'raw' => $request,
			'failed' => "taskCount: $taskCount, requestCount: $requestCount",
		));
		return $output;
	}
	if ($debug) {
		array_push($output['info'], array(
			'payload' => $payload,
		));
	}
	$POST_DATA = json_encode($payload);
	$options = array(
		'http' => array(
			'header' => implode("\r\n", array(
				"Content-Type: application/json",
				"Content-Length: ".strlen($POST_DATA),
			)),
			'method' => 'POST',
			'content' => $POST_DATA,
			'timeout' => $matomoAPITimeout,
		)
	);
	if ($debug) {
		array_push($output['info'], array(
			'option' => $options,
		));
	}
	$context  = @stream_context_create($options);
	$result = @file_get_contents($matomoAPI, false, $context);
	array_push($output['info'], array(
		'apiResult' => $result,
	));
	$checker = @json_decode($result, true);
	$output['status'] = isset($checker['status']) && $checker['status'] && isset($checker['tracked']) && $checker['tracked'] > 0;
	return $output;
}

// https://developer.matomo.org/api-reference/reporting-api

function matomoQueryReport($query = array(), $matomoAPI = NULL, $matomoTokenAuth = NULL, $matomoAPITimeout = 5, $debug = false) {
	$output = array( 'status' => false, 'data' => NULL, 'error' => NULL, 'info' => array() );
	$requestInfo = array();

	if (empty($matomoAPI) || !filter_var($matomoAPI, FILTER_VALIDATE_URL)) {
		$output['error'] = -1;
		return $output;
	}
	if (!is_array($query)) {
		$output['error'] = -2;
		return $output;
	}

	if (empty($matomoTokenAuth)) {
		$output['error'] = -3;
		return $output;
	}

	if (!isset($query['filter_limit']))
		$query['filter_limit'] = 100;

	foreach( ['idSite', 'period', 'date', 'method'] as $field ) {
		if (!isset($query[$field])) {
			$output['error'] = -4;
			array_push($output['info'], 'no '.$field);
			return $output;
		}
	}
	switch($query['period']) {
		case 'day':
		case 'week':
		case 'month':
		case 'year':
		case 'range':
			break;
		default:
			$output['error'] = 4;
			array_push($output['info'], 'period value failed');
			return $output;
	}

	switch($query['date']) {
		case 'today':
		case 'yesterday':
		case 'lastWeek':
		case 'lastMonth':
		case 'lastYear':
			break;
		default:
			if (preg_match('/last[1-9][0-9]+/', $query['date'], $match)) {
				break;
			}
			if (preg_match('/([2][0-9]{3}\-[0-9]{2}\-[0-9]{2}),([2][0-9]{3}\-[0-9]{2}\-[0-9]{2})/', $query['date'], $match)) {
				break;
			}
			if (preg_match('/[2][0-9]{3}\-[0-9]{2}\-[0-9]{2}/', $query['date'], $match)) {
				break;
			}

			$output['error'] = 5;
			array_push($output['info'], 'date value failed');
			return $output;
	}
	
	$POST_DATA = http_build_query(array(
		'module' => 'API',
		'method' => 'API.getBulkRequest',
		'token_auth' => $matomoTokenAuth,
		'format' => 'json',
		'urls' => array(
			http_build_query($query),
		),
	));

	$options = array(
		'http' => array(
			'header' => implode("\r\n", array(
				"Content-Type: application/x-www-form-urlencoded",
				"Content-Length: ".strlen($POST_DATA),
			)),
			'method' => 'POST',
			'content' => $POST_DATA,
			'timeout' => $matomoAPITimeout,
		)
	);
	if ($debug) {
		array_push($output['info'], array(
			'option' => $options,
		));
	}
	$context  = @stream_context_create($options);
	$result = @file_get_contents($matomoAPI, false, $context);
	$checker = @json_decode($result, true);
	if (!isset($checker['result']) || $checker['result'] != 'error') {
		$output['status'] = true;
		$output['data'] = $checker;
	} else {
		array_push($output['info'], array(
			'apiResult' => $result,
		));
	}

	return $output;
}
