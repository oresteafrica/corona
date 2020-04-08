<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('max_execution_time', 1200);

// opt = option for switch
if (check_get('opt') == 'ok') { $opt = $_GET['opt']; }  else { exit; };
// csv = csv file name
if ( isset($_GET['csv']) ) { $csv = $_GET['csv']; }

$daily_url = 'https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/';

switch ($opt) {
    case 1: // list files in directory
		$fnames = github_api($daily_url);
		echo '<pre>'; print_r($fnames); echo '</pre>';
        break;
	case 2: // get csv content
		$csv_array = github_api($daily_url . $csv . '.csv');
		echo '<pre>'; print_r($csv_array); echo '</pre>';
        break;
	case 3: // populate data
		populate($daily_url);
        break;
    case 4: // update data
		update($daily_url);
        break;
    default:
       exit;
}
// ---------------------------------------------------------------------------------------------------------------------
function moveElement(&$array, $a, $b) {
    $out = array_splice($array, $a, 1);
    array_splice($array, $b, 0, $out);
}
// ---------------------------------------------------------------------------------------------------------------------
function update($daily_url) {
// check the Lastupdate	date at table `daily` start update after that date

	$ini_file = '../cron/corona.ini';
	$ini_array = parse_ini_file($ini_file);
	$ttok = $ini_array['ttok']; // github oauth token
	$db = database();
	$sql = 'SELECT * FROM daily ORDER BY Lastupdate DESC LIMIT 1';
	$tabquery = $db->query($sql);
	$tabquery->setFetchMode(PDO::FETCH_ASSOC);
	// highest date available in database table `daily`
	$last_daily_date_string = $tabquery->fetch()['Lastupdate'];

	$json = github_api($daily_url);
	$fnames = [];
	foreach($json as $item) {
		$filename = pathinfo($item->name, PATHINFO_FILENAME);		
		// do not include dates already present in database table `daily`
		if (date_create_from_format('m-d-Y',$filename) <= date_create_from_format('YYYY-m-d',$last_daily_date_string)) { continue; }
		if (preg_match("/(0[1-9]|1[012])[- -.](0[1-9]|[12][0-9]|3[01])[- -.](19|20)\d\d/i",$filename)) { $fnames[] = $filename; }
	}
	// now $fnames is populated with all csv file names

	// get content of each csv file
	foreach($fnames as $date) {
		$filedate = $daily_url . $date . '.csv';

		$jcsv = github_api($filedate);
		// csv content as php string
		$csv_string = base64_decode($jcsv->content);

		// ini take off comma within double quotes
		$pattern = '/(".+)(, )(.+")/';
		$replacement = function($r) {
			$r[2] = '-';
			return $r[1] . $r[2] . $r[3];
		};
		$temp_csv_string = preg_replace_callback($pattern,$replacement,$csv_string);
		$csv_string = $temp_csv_string;	
		// end take off comma within double quotes

		// Parse a CSV string into an array
		$rows = str_getcsv($csv_string, "\n");
		$rows_head = $rows[0];
		// takes off field names, $rows contains just data from a single csv
		array_shift($rows);


		foreach($rows as $row) {
			$csv_line = str_getcsv($row);
			// replace commas
			$csv_line_temp = str_replace( ',' , ' -' , $csv_line);
			$csv_line = $csv_line_temp;
			// escape special chars
			array_walk_recursive($csv_line, function(&$item, $key) { $item = addslashes($item); });
			$record = '';
			if ( count($csv_line) > 8 ) {
				array_shift($csv_line);
				array_shift($csv_line);
				array_pop($csv_line);
				array_pop($csv_line);
				moveElement($csv_line, 3, 7);
				moveElement($csv_line, 3, 8);
				$s_csv_line = implode(',',$csv_line);
				$record = process_jhu_csv($s_csv_line,$date);
			} else {
				$record = process_jhu_csv($row,$date);
			}
			$sql = "INSERT INTO daily (State, Country, Lastupdate, Confirmed, Deaths, Recovered, Latitude, Longitude) VALUES ($record)";

			// debug
			echo $record . '<hr />';
		
			if (! $db->query($sql)) { echo "<hr />Error: " . $sql . "<br>" . $db->error; }
		}

	}

}
// ---------------------------------------------------------------------------------------------------------------------
function populate($daily_url) {
	$ini_file = '../cron/corona.ini';
	$ini_array = parse_ini_file($ini_file);
	$ttok = $ini_array['ttok']; // github oauth token
	$db = database();
	$sql = 'SELECT * FROM daily';
	$tabquery = $db->query($sql);
	$tabquery->setFetchMode(PDO::FETCH_ASSOC);
	$daily_rows = $tabquery->rowCount();
	
	// debug
	echo '$daily_rows = ' . $daily_rows . '<hr />';
	
	// database already populated
	if ($daily_rows > 0) { $db = null; return 0; }

	$json = github_api($daily_url);

	$fnames = [];
	foreach($json as $item) {
		$filename = pathinfo($item->name, PATHINFO_FILENAME);
		
		// do not include dates bigger than February 21, 2020
		if (date_create_from_format('m-d-Y',$filename) > date_create_from_format('m-d-Y','02-21-2020')) { continue; }
		
		if (preg_match("/(0[1-9]|1[012])[- -.](0[1-9]|[12][0-9]|3[01])[- -.](19|20)\d\d/i",$filename)) { $fnames[] = $filename; }
	}
	// now $fnames is populated with all csv file names up to February 21, 2020
	
	// debug
	echo '$fnames<br />';
	echo '<pre>'; print_r($fnames); echo '</pre><hr />';

	// get content of each csv file
	foreach($fnames as $date) {
		$filedate = $daily_url . $date . '.csv';

		// debug
		echo '<b>' . $date . '</b>';
		echo '<br />==============<br />';

		$jcsv = github_api($filedate);

		// csv content as php string
		$csv_string = base64_decode($jcsv->content);

		// ini take off comma within double quotes
		$pattern = '/(".+)(, )(.+")/';
		$replacement = function($r) {
			$r[2] = '-';
			return $r[1] . $r[2] . $r[3];
		};
		$csv_content_modi = preg_replace_callback($pattern,$replacement,$csv_string);	
		// end take off comma within double quotes

		// Parse a CSV string into an array
		$rows = str_getcsv($csv_content_modi, "\n");
		// takes off field names, $rows contains just data from a single csv
		array_shift($rows);

		// ini process csv data
		foreach($rows as $row) {
			$record = process_jhu_csv($row,$date);
			$sql = "INSERT INTO daily (State, Country, Lastupdate, Confirmed, Deaths, Recovered, Latitude, Longitude) VALUES ($record)";

			// debug
			echo $sql . '<hr />';
		
			if (! $db->query($sql)) {
				echo "<hr />Error: " . $sql . "<br>" . $db->error;
			}

		} // foreach($rows as $row)

	} // foreach($fnames as $date)
			
	$db = null;
}
// ---------------------------------------------------------------------------------------------------------------------
function process_jhu_csv($row,$date) {
// return string formatted for mysql insert
	
	// convert $date to mysql date, each mysql statement will contain this date instead of the date indicated in csv
	$mysqldate = substr($date,6,4) . '-' . substr($date,0,2) . '-' . substr($date,3,2);
			
	// Parse a CSV string into an array
	$line = str_getcsv($row);
	$lenline = count($line);
		
	for ($i = 0; $i < $lenline; $i++) {				
		switch ($i) {
			case 0:	// State
			case 1:	// Country
				if(substr($line[$i], 0,1) == '"' && substr( $line[$i],-1) == '"') {
					$line[$i] = addslashes($line[$i]);
				} else {
					$line[$i] = '"' . $line[$i] . '"';
				}
				if (strlen($line[$i])==0) {
					$line[$i] = '""';
				}
				break;
			case 2:	// Lastupdate
				$line[$i] = '"' . $mysqldate . '"';
				break;
			case 3:	// Confirmed
			case 4:	// Deaths
			case 5:	// Recovered
			case 6:	// Latitude
			case 7:	// Longitude
				if (strlen($line[$i])==0) {
					$line[$i] = 0;
				}
				break;
			default:
		} // switch		
	} // for
	// some csvs do not have values for latitude and longitude
	for ($k = 0; $k < (8 - $lenline); $k++) {				
		$line[] = 0;
	}
	$ret = implode(',',$line);
	return $ret;
}
// ---------------------------------------------------------------------------------------------------------------------
function github_api($uri) {
	$ini_file = '../cron/corona.ini';
	$ini_array = parse_ini_file($ini_file);
	// github oauth token
	$ttok = $ini_array['ttok'];
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $uri,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_TIMEOUT => 500,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"Accept: application/vnd.github.v3+json",
			"Accept-Encoding: gzip, deflate, br",
			"Cache-Control: no-cache",
			"Connection: keep-alive",
			"Referer: $uri",
			"User-Agent: oresteafrica http://oreste.in",
			"Authorization: token $ttok"
		),
	));
	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) { echo "cURL Error #:" . $err; exit; }
	$json = json_decode($response);
	return $json;
}
// ---------------------------------------------------------------------------------------------------------------------
function database() {
	$ini_file = '../cron/corona.ini';
	$ini_array = parse_ini_file($ini_file);
	$user = $ini_array['user'];
	$pass = $ini_array['pass'];
	$sdsn = $ini_array['sdsn'];
	$opts = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
	);
	try {
		$db = new PDO($sdsn, $user, $pass, $opts);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
		die('Base dati non accessibile:<br/>' . $e);
	}
	return $db;

/*
Table daily
#	Name		Type		Collation			Attributes	Null
1	id			int(11)		No					None		AUTO_INCREMENT
2	State		varchar(60)	utf8_general_ci		No			None		
3	Country		varchar(60)	utf8_general_ci		No			None		
4	Lastupdate	date							No			None		
5	Confirmed	int(11)							No			None		
6	Deaths		int(11)							No			None		
7	Recovered	int(11)							No			None		
8	Latitude	decimal(10,0)					No			None		
9	Longitude	decimal(10,0)					No			None		

*/
}
// ---------------------------------------------------------------------------------------------------------------------
function check_get($var) {
	$ret = 'ok'; $a = '0'; $b = '0'; $c = '0'; $d = '0'; $chk = true;
	if (!isset($_GET[$var])) { $a = '1'; $chk = false; }
	if ($_GET[$var] === '') { $b = '1'; $chk = false; }
	if ($_GET[$var] === null) { $c = '1'; $chk = false; }
//	if (empty($_GET[$var])) { $d = '1'; $chk = false; }
	if ($chk) { return 'ok'; } else { return $a . $b . $c . $d; }
}
// ---------------------------------------------------------------------------------------------------------------------
?>
