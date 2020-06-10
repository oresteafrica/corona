<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ( isset($_GET['opt']) ) { $opt = $_GET['opt']; }

switch ($opt) {
    case 1: // 
		stat01();
        break;
	case 2: // 
		stat02();
        break;
	case 3: // 
		stat03();
        break;
    case 4: // 

        break;
    default:
       exit;
}

// ---------------------------------------------------------------------------------------------------------------------
function stat01() {
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://covid-19-coronavirus-statistics.p.rapidapi.com/v1/stats?country=Italy",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"x-rapidapi-host: covid-19-coronavirus-statistics.p.rapidapi.com",
			"x-rapidapi-key: PUBnW6445DQXvbhhgSk7MU@I_zR3W2oL"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		print_r($response);
	}

}
// ---------------------------------------------------------------------------------------------------------------------
function stat02() {
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://covid-193.p.rapidapi.com/statistics",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"x-rapidapi-host: covid-193.p.rapidapi.com",
			"x-rapidapi-key: PUBnW6445DQXvbhhgSk7MU@I_zR3W2oL"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		var_dump($response);
//		echo json_encode($response, JSON_PRETTY_PRINT);
	}
}
// ---------------------------------------------------------------------------------------------------------------------
function stat03() {
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://coronavirus-monitor.p.rapidapi.com/coronavirus/cases_by_particular_country.php?country=Italy",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"x-rapidapi-host: coronavirus-monitor.p.rapidapi.com",
			"x-rapidapi-key: PUBnW6445DQXvbhhgSk7MU@I_zR3W2oL"
		),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		echo $response;
	}
}
// ---------------------------------------------------------------------------------------------------------------------
?>
