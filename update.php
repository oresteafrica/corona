<?php

$daily_url = 'https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/';


echo '<html>';
echo '<head>';
echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8" />';
echo '</head>';
echo '<body>';

update($daily_url);

echo '</body>';
echo '</html>';



// ---------------------------------------------------------------------------------------------------------------------
function moveElement(&$array, $a, $b) {
    $out = array_splice($array, $a, 1);
    array_splice($array, $b, 0, $out);
}
// ---------------------------------------------------------------------------------------------------------------------
function update($daily_url) {
// check the Lastupdate	date at table `daily` start update after that date

	$ini_file = 'corona.ini';
	$ini_array = parse_ini_file($ini_file);
	$ttok = $ini_array['ttok']; // github oauth token
	$db = database();
	$sql = 'SELECT * FROM daily ORDER BY Lastupdate DESC LIMIT 1';
	$tabquery = $db->query($sql);
	$tabquery->setFetchMode(PDO::FETCH_ASSOC);
	// highest date available in database table `daily`
	$tabfetch = $tabquery->fetch();
	$last_daily_date_string = $tabfetch['Lastupdate'];
	$lastdate = date_create_from_format('!Y-m-d',$last_daily_date_string);

	$json = github_api($daily_url);
	$fnames = [];

	foreach($json as $item) {
		$filename = pathinfo($item->name, PATHINFO_FILENAME);		
		// do not include dates already present in database table `daily`
		$filedate = date_create_from_format('!m-d-Y',$filename);
		if (  $filedate > $lastdate ) {
			if (preg_match("/(0[1-9]|1[012])[- -.](0[1-9]|[12][0-9]|3[01])[- -.](19|20)\d\d/i",$filename)) { $fnames[] = $filename; }
		}
	}
	// now $fnames is populated with all csv file names
	
	echo '$fnames<br />';
	echo '<pre>'; print_r($fnames); echo '</pre>';

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
			$length_csv_line = count($csv_line);
			// replace commas
			$csv_line_temp = str_replace( ',' , ' -' , $csv_line);
			$csv_line = $csv_line_temp;
			// escape special chars
			array_walk_recursive($csv_line, function(&$item, $key) { $item = addslashes($item); });
			$record = '';
						
			// correction due to change of structure on May 29, 2020
			$critical_date = date_create_from_format('!m-d-Y','05-28-2020');
			$file_date = date_create_from_format('!m-d-Y',$date);
			if ( $file_date > $critical_date ) {
				array_pop($csv_line);
				array_pop($csv_line);
			}			
						
			if ( $length_csv_line > 8 ) {
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
		
			if (! $db->query($sql)) { echo '<hr />Error:<br />' . $sql . '<br /><br />' . $db->error; }
		}

	}

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
	$ini_file = 'corona.ini';
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
	$ini_file = 'corona.ini';
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
?>
