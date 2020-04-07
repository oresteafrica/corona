<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('max_execution_time', 1200);

// opt = option for switch
if (check_get('opt') == 'ok') { $opt = $_GET['opt']; }  else { exit; };
// csv = csv file name
if (check_get('csv') == 'ok') { $csv = $_GET['csv']; }  else { exit; };

$daily_url = 'https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/';

switch ($opt) {
	case -1: // debug
		debug($opt,$csv);
		exit;
        break;
	case 0: // 
        break;
    case 1: // list files in directory
		$fnames = get_json($daily_url,0);
		echo '<pre>'; print_r($fnames); echo '</pre>';
        break;
	case 2: // get csv content
		$curlopt_url = $daily_url . $csv . '.csv';
		$csv_array = get_json($curlopt_url,$csv);
		echo '<pre>'; print_r($csv_array); echo '</pre>';
        break;
	case 3: // populate
		populate($daily_url);
        break;
    case 4: // table raw data
		$curlopt_url = $daily_url . $csv . '.csv';
		$csv_array = get_json($curlopt_url,$csv,0,1);
		echo '<pre>'; print_r($csv_array); echo '</pre>';
        break;
    case 5: // update_01 mysql start from 24/03/2020
		$dates_mysql = update_01($daily_url);
		echo '<h2>' . count($dates_mysql) . ' records</h2>';
//		echo '<pre>'; print_r($dates_mysql); echo '</pre>';
        break;
    case 6: // check double records in case of wrong update
        break;
    case 7: // 
        break;
    case 8: // 
		$curlopt_url = '';
        break;
    case 9: // 
		$curlopt_url = '';
        break;
    case 10: // 
		$curlopt_url = '';
        break;
    default:
       exit;
}



// ---------------------------------------------------------------------------------------------------------------------
function check_double_records() {
	$sql = 'SELECT State, COUNT(State), Country, COUNT(Country), Lastupdate, COUNT(Lastupdate), Confirmed, COUNT(Confirmed), Deaths, COUNT(Deaths), Recovered, COUNT(Recovered) FROM daily GROUP BY State, Country, Lastupdate, Confirmed, Deaths, Recovered HAVING COUNT(State) > 1 AND COUNT(Country) > 1 AND COUNT(Lastupdate) > 1 AND COUNT(Confirmed) > 1 AND COUNT(Deaths) > 1 AND COUNT(Recovered) > 1;';
}
// ---------------------------------------------------------------------------------------------------------------------
function update_01($daily_url) {

	// $fnames contains the list of dates that are also the name of files formatted as MM-DD-YYYY
	$fnames = get_json($daily_url,0);
	// build array of dates mysql formatted
	$dates = [];
	$pattern = '/(\d{2})-(\d{2})-(\d{4})/';
	$db = database();
	foreach($fnames as $date_from_file) {
		$replacement = function($r) {
			return $r[3] . '-' . $r[1] . '-' . $r[2];
		};
		$date_mysql = preg_replace_callback($pattern,$replacement,$date_from_file);
		// exclude dates already inserted in mysql table
		$sql = 'SELECT * FROM daily WHERE DATE(Lastupdate) = "' . $date_mysql . '"';
		$tabquery = $db->query($sql);
		$tabquery->setFetchMode(PDO::FETCH_ASSOC);
		$qrec = $tabquery->rowCount();
		if ($qrec < 1) {
			$dates[] = $date_from_file;
		}
		// tested up to here
		// $dates contains dates formatted MM-DD-YYYY as the csv file name
	}
	
	if (count($dates) == 0) {
		echo '$dates is empty';
		return [];
	}
	
	// read table in csv file and store in array
	$line_array = [];
	foreach($dates as $csv_file_name) {
		// compare with fields names in mysql table
		// then normalize
		$csv_raw_content = get_json($daily_url . $csv_file_name . '.csv',$csv_file_name,0,1);
		// ini take off commas within double quotes
		$pattern = '/(".+)(, )(.+")/';
		$replacement = '$1-$3';
		$count = 1;
		while ($count == 1) {
			$csv_raw_content = preg_replace($pattern,$replacement,$csv_raw_content, 1, $count);
		}
		// end take off commas within double quotes
		$a_csv = str_getcsv($csv_raw_content, "\n");
		$field_names = $a_csv[0];
		// ini map field names
		$a_field_names = str_getcsv($field_names,',','"');
		array_shift($a_csv); // takes off field names
		foreach($a_csv as $row){
			$line = str_getcsv($row,',','"');
			unset($line[0],$line[1],$line[10],$line[11]);
			$line = array_values($line);
			moveElement($line,3,7);
			moveElement($line,3,7);
			// escape special chars
			$line[0] = addslashes($line[0]);
			$line[1] = addslashes($line[1]);
			// add double quotes to strings and dates
			$line[0] = '"' . $line[0] . '"';
			$line[1] = '"' . $line[1] . '"';
			$line[2] = '"' . $line[2] . '"';
			$s_line = implode(',',$line);
			$line_array[] = $s_line;
			$sql = "INSERT INTO daily (State, Country, Lastupdate, Confirmed, Deaths, Recovered, Latitude, Longitude) VALUES ($s_line);";
			
			
echo $sql . '<br />';
			
			if (! $db->query($sql)) {
				echo "<hr />Error: " . $sql . "<br>" . $db->error;
			}
			
		}
	}
	
	return $line_array;
}
// ---------------------------------------------------------------------------------------------------------------------
function moveElement(&$array, $a, $b) {
    $out = array_splice($array, $a, 1);
    array_splice($array, $b, 0, $out);
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
		
		// do not include dates bigger than March 21, 2020
		if (date_create_from_format('m-d-Y',$filename) > date_create_from_format('m-d-Y','02-21-2020')) { continue; }
		
		if (preg_match("/(0[1-9]|1[012])[- -.](0[1-9]|[12][0-9]|3[01])[- -.](19|20)\d\d/i",$filename)) { $fnames[] = $filename; }
	}
	// now $fnames is populated with all csv file names up to March 21, 2020
	
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
function csv_array_mysql_formatted($csv_content) {

	$pattern = '/(".+)(, )(.+")/';
	$replacement = function($r) {
		$r[2] = '-';
		return $r[1] . $r[2] . $r[3];
	};
	$csv_content_modi = preg_replace_callback($pattern,$replacement,$csv_content);	// take off comma within double quotes

	$array_main = [];
	$rows = str_getcsv($csv_content_modi, "\n");
	array_shift($rows); // takes off field names
	foreach($rows as $row){
		$line = str_getcsv($row,',','"');
		$line_array = [];
		$count = 0;
		foreach($line as $item){
			if ( preg_match ('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',$item) ) {
				$line_array[] = str_replace('T',' ','"' . $item . '"');
				continue;
			}
			if ( preg_match ('/(\d{1}|\d{2})\/(\d{1}|\d{2})\/\d{4}|\d{2} \d{1}|\d{2}:\d{2}/',$item) ) { // January date is formatted differently
				$pattern = '/(\d{1}|\d{2})\/(\d{1}|\d{2})\/(\d{4}|\d{2}) (\d{1}|\d{2}):(\d{2})/';
				$replacement = function($r) {	// swap places and add leading zero
					if (strlen($r[1])==1) $r[1] = '0'.$r[1];
					if (strlen($r[2])==1) $r[2] = '0'.$r[2];
					if (strlen($r[3])==2) $r[3] = '20'.$r[3];
					if (strlen($r[4])==1) $r[4] = '0'.$r[4];
					return $r[3].'-'.$r[1].'-'.$r[2].' '.$r[4].':'.$r[5].':00';
				};
				$line_array[] = '"' . preg_replace_callback($pattern,$replacement,$item) . '"';
				continue;
			}
			if ($count < 2) { // at the beginning there are varchar
				if (is_numeric($item)) {
					$line_array[] = $item;
				} else { 
					if ( mb_substr($item,0,1) == '"') {	// double quoted string could contain special char
						$line_array[] = addslashes($item);
					} else {
						$line_array[] = '"' . $item . '"';
					}
				}
			} else {
				if (strlen($item)==0) {
					$line_array[] = 0;
				} else {
					$line_array[] = $item;
				}
			}
			$count++;
		}

		// it happens that sometime the first item is not appended
		if ( ! ( is_string($line_array[0]) and is_string($line_array[1]) and is_string($line_array[2]) ) ) {	 
			array_unshift($line_array,'""');
		}

		for ($i = count($line_array); $i < 8; $i++) { // January data do not include latitude and longitude, some other do not include deaths and recovered
			array_push($line_array,0);
		}
		$array_main[] = implode(',',$line_array);
	}
	return $array_main;
}
// ---------------------------------------------------------------------------------------------------------------------
function csv_table($csv_content) {
	$table = "<table>";
	$rows = str_getcsv($csv_content, "\n");
	foreach($rows as &$row){
		$table .= "<tr>";
		$cells = str_getcsv($row);
		foreach($cells as &$cell){
			$table .= "<td>$cell</td>";
		}
		$table .= "</tr>";
	}
	$table .= "</table>";
	return $table;
}
// ---------------------------------------------------------------------------------------------------------------------
function get_json($curlopt_url,$csv,$pty = false,$raw = false) {
	$ini_file = '../cron/corona.ini';
	$ini_array = parse_ini_file($ini_file);
	$ttok = $ini_array['ttok']; // github oauth token
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $curlopt_url,
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
			"Referer: $curlopt_url",
			"User-Agent: oresteafrica http://oreste.in",
			"Authorization: token $ttok"
		),
	));
	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) { echo "cURL Error #:" . $err; exit; }
	$json = json_decode($response);
	if ($pty) {
		echo '<pre>' . json_encode($json, JSON_PRETTY_PRINT) . '</pre>';
	} else {
		if (strlen($csv) == 10) { // csv file
			$csv_string = base64_decode($json->content);
			if ($raw) {
				return csv_table_raw($csv_string);
			} else {
				return csv_array_mysql_formatted($csv_string);
			}
		} else { // directory
			$fnames = [];
			foreach($json as $item) {
				$filename = pathinfo($item->name, PATHINFO_FILENAME);
				if (preg_match("/(0[1-9]|1[012])[- -.](0[1-9]|[12][0-9]|3[01])[- -.](19|20)\d\d/i",$filename)) {
					$fnames[] = $filename;
				}
			}
			return $fnames;
		}
	}
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
4	Lastupdate	datetime						No			None		
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
function csv_array_mysql_formatted_2($csv_content) {
	$array_main = [];
	$rows = str_getcsv($csv_content, "\n");
	array_shift($rows); // takes off field names
	foreach($rows as $row){
		$row = str_replace (', ','. ',$row); // comma within double quotes NOT ENOUGH -> Cote d'Ivoire
		$line = str_getcsv($row,',','"');
		$line_array = [];
		$count = 0;
		foreach($line as $item){
			if ( preg_match ('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',$item) ) {
				$line_array[] = str_replace('T',' ','"' . $item . '"');
				continue;
			}
			if ( preg_match ('/(\d{1}|\d{2})\/(\d{1}|\d{2})\/\d{4} \d{2}:\d{2}/',$item) ) { // January date is formatted differently
				$line_array[] = str_replace('/','-','"' . $item . '"');
				continue;
			}
			if ($count < 2) { // at the beginning there are varchar
				$line_array[] = is_numeric($item) ? $item : '"' . $item . '"';
			} else {
				$line_array[] = $item;
			}
			$count++;
		}
		for ($i = count($line_array); $i <= 8; $i++) { // January data do not include latitude and longitude
			array_push($line_array,null);
		}
		$array_main[] = implode(',',$line_array);
	}
	return $array_main;
}
// ---------------------------------------------------------------------------------------------------------------------
function debug($opt,$pty,$upt) {
	echo '$_SERVER[\'HTTP_HOST\'] = ' . $_SERVER['HTTP_HOST'];
	echo '<br />$_SERVER[\'SERVER_NAME\'] = ' . $_SERVER['SERVER_NAME'];
	echo '<br />$_SERVER[\'REQUEST_URI\'] = ' . $_SERVER['REQUEST_URI'];
	echo '<br />parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH) = ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	echo '<br />$_SERVER[\'PHP_SELF\'] = ' . $_SERVER['PHP_SELF'];
	echo '<br />$pty = ' . $pty;
	echo '<br />$opt = ' . $opt;
	echo '<br />$upt = ' . $upt;
	echo '<br />file_exists (\'../json/0.json\') = ' . (file_exists ('../json/0.json') ? 'true' : 'false');
	echo '<br />file_exists (\'../json/1.json\') = ' . (file_exists ('../json/1.json') ? 'true' : 'false');
	echo '<hr />' ;
	echo '<p>type $response = ' . gettype($response) . '</p>';
	echo '<p>length $response = ' . strlen($response) . '</p>';
	echo '<p>$curlopt_url = ' . $curlopt_url . '</p>';
}
// ---------------------------------------------------------------------------------------------------------------------
/*

------------------------------------------------------------------------------------------------------------------------
$curlopt_url = https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/

[
    {
        "name": ".gitignore",
        "path": "csse_covid_19_data\/csse_covid_19_daily_reports\/.gitignore",
        "sha": "496ee2ca6a2f08396a4076fe43dedf3dc0da8b6d",
        "size": 9,
        "url": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/contents\/csse_covid_19_data\/csse_covid_19_daily_reports\/.gitignore?ref=master",
        "html_url": "https:\/\/github.com\/CSSEGISandData\/COVID-19\/blob\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/.gitignore",
        "git_url": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/git\/blobs\/496ee2ca6a2f08396a4076fe43dedf3dc0da8b6d",
        "download_url": "https:\/\/raw.githubusercontent.com\/CSSEGISandData\/COVID-19\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/.gitignore",
        "type": "file",
        "_links": {
            "self": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/contents\/csse_covid_19_data\/csse_covid_19_daily_reports\/.gitignore?ref=master",
            "git": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/git\/blobs\/496ee2ca6a2f08396a4076fe43dedf3dc0da8b6d",
            "html": "https:\/\/github.com\/CSSEGISandData\/COVID-19\/blob\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/.gitignore"
        }
    },
    {
        "name": "01-22-2020.csv",
        "path": "csse_covid_19_data\/csse_covid_19_daily_reports\/01-22-2020.csv",
        "sha": "26a4512a85668bebac38522fe6579ccb05a434c3",
        "size": 1675,
        "url": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/contents\/csse_covid_19_data\/csse_covid_19_daily_reports\/01-22-2020.csv?ref=master",
        "html_url": "https:\/\/github.com\/CSSEGISandData\/COVID-19\/blob\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/01-22-2020.csv",
        "git_url": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/git\/blobs\/26a4512a85668bebac38522fe6579ccb05a434c3",
        "download_url": "https:\/\/raw.githubusercontent.com\/CSSEGISandData\/COVID-19\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/01-22-2020.csv",
        "type": "file",
        "_links": {
            "self": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/contents\/csse_covid_19_data\/csse_covid_19_daily_reports\/01-22-2020.csv?ref=master",
            "git": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/git\/blobs\/26a4512a85668bebac38522fe6579ccb05a434c3",
            "html": "https:\/\/github.com\/CSSEGISandData\/COVID-19\/blob\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/01-22-2020.csv"
        }
    },
 
 ...
 
     {
        "name": "README.md",
        "path": "csse_covid_19_data\/csse_covid_19_daily_reports\/README.md",
        "sha": "e69de29bb2d1d6434b8b29ae775ad8c2e48c5391",
        "size": 0,
        "url": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/contents\/csse_covid_19_data\/csse_covid_19_daily_reports\/README.md?ref=master",
        "html_url": "https:\/\/github.com\/CSSEGISandData\/COVID-19\/blob\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/README.md",
        "git_url": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/git\/blobs\/e69de29bb2d1d6434b8b29ae775ad8c2e48c5391",
        "download_url": "https:\/\/raw.githubusercontent.com\/CSSEGISandData\/COVID-19\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/README.md",
        "type": "file",
        "_links": {
            "self": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/contents\/csse_covid_19_data\/csse_covid_19_daily_reports\/README.md?ref=master",
            "git": "https:\/\/api.github.com\/repos\/CSSEGISandData\/COVID-19\/git\/blobs\/e69de29bb2d1d6434b8b29ae775ad8c2e48c5391",
            "html": "https:\/\/github.com\/CSSEGISandData\/COVID-19\/blob\/master\/csse_covid_19_data\/csse_covid_19_daily_reports\/README.md"
        }
    }
]

------------------------------------------------------------------------------------------------------------------------
response of https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv


{
    "name": "01-24-2020.csv",
    "path": "csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv",
    "sha": "0eba75c8b7b677a303b4ec5ff6902b55392fac8a",
    "size": 1695,
    "url": "https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv?ref=master",
    "html_url": "https://github.com/CSSEGISandData/COVID-19/blob/master/csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv",
    "git_url": "https://api.github.com/repos/CSSEGISandData/COVID-19/git/blobs/0eba75c8b7b677a303b4ec5ff6902b55392fac8a",
    "download_url": "https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv",
    "type": "file",
    "content": "77u/UHJvdmluY2UvU3RhdGUsQ291bnRyeS9SZWdpb24sTGFzdCBVcGRhdGUs\nQ29uZmlybWVkLERlYXRocyxSZWNvdmVyZWQNCkh1YmVpLE1haW5sYW5kIENo\naW5hLDEvMjQvMjAgMTc6MDAsNTQ5LDI0LDMxDQpHdWFuZ2RvbmcsTWFpbmxh\nbmQgQ2hpbmEsMS8yNC8yMCAxNzowMCw1MywsMg0KWmhlamlhbmcsTWFpbmxh\nbmQgQ2hpbmEsMS8yNC8yMCAxNzowMCw0MywsMQ0KQmVpamluZyxNYWlubGFu\nZCBDaGluYSwxLzI0LzIwIDE3OjAwLDM2LCwxDQpDaG9uZ3FpbmcsTWFpbmxh\nbmQgQ2hpbmEsMS8yNC8yMCAxNzowMCwyNywsDQpIdW5hbixNYWlubGFuZCBD\naGluYSwxLzI0LzIwIDE3OjAwLDI0LCwNCkd1YW5neGksTWFpbmxhbmQgQ2hp\nbmEsMS8yNC8yMCAxNzowMCwyMywsDQpTaGFuZ2hhaSxNYWlubGFuZCBDaGlu\nYSwxLzI0LzIwIDE3OjAwLDIwLCwxDQpKaWFuZ3hpLE1haW5sYW5kIENoaW5h\nLDEvMjQvMjAgMTc6MDAsMTgsLA0KU2ljaHVhbixNYWlubGFuZCBDaGluYSwx\nLzI0LzIwIDE3OjAwLDE1LCwNClNoYW5kb25nLE1haW5sYW5kIENoaW5hLDEv\nMjQvMjAgMTc6MDAsMTUsLA0KQW5odWksTWFpbmxhbmQgQ2hpbmEsMS8yNC8y\nMCAxNzowMCwxNSwsDQpGdWppYW4sTWFpbmxhbmQgQ2hpbmEsMS8yNC8yMCAx\nNzowMCwxMCwsDQpIZW5hbixNYWlubGFuZCBDaGluYSwxLzI0LzIwIDE3OjAw\nLDksLA0KSmlhbmdzdSxNYWlubGFuZCBDaGluYSwxLzI0LzIwIDE3OjAwLDks\nLA0KSGFpbmFuLE1haW5sYW5kIENoaW5hLDEvMjQvMjAgMTc6MDAsOCwsDQpU\naWFuamluLE1haW5sYW5kIENoaW5hLDEvMjQvMjAgMTc6MDAsOCwsDQpZdW5u\nYW4sTWFpbmxhbmQgQ2hpbmEsMS8yNC8yMCAxNzowMCw1LCwNClNoYWFueGks\nTWFpbmxhbmQgQ2hpbmEsMS8yNC8yMCAxNzowMCw1LCwNCkhlaWxvbmdqaWFu\nZyxNYWlubGFuZCBDaGluYSwxLzI0LzIwIDE3OjAwLDQsMSwNCkxpYW9uaW5n\nLE1haW5sYW5kIENoaW5hLDEvMjQvMjAgMTc6MDAsNCwsDQpHdWl6aG91LE1h\naW5sYW5kIENoaW5hLDEvMjQvMjAgMTc6MDAsMywsDQpKaWxpbixNYWlubGFu\nZCBDaGluYSwxLzI0LzIwIDE3OjAwLDMsLA0KVGFpd2FuLFRhaXdhbiwxLzI0\nLzIwIDE3OjAwLDMsLA0KTmluZ3hpYSxNYWlubGFuZCBDaGluYSwxLzI0LzIw\nIDE3OjAwLDIsLA0KSG9uZyBLb25nLEhvbmcgS29uZywxLzI0LzIwIDE3OjAw\nLDIsLA0KTWFjYXUsTWFjYXUsMS8yNC8yMCAxNzowMCwyLCwNCkhlYmVpLE1h\naW5sYW5kIENoaW5hLDEvMjQvMjAgMTc6MDAsMiwxLA0KR2Fuc3UsTWFpbmxh\nbmQgQ2hpbmEsMS8yNC8yMCAxNzowMCwyLCwNClhpbmppYW5nLE1haW5sYW5k\nIENoaW5hLDEvMjQvMjAgMTc6MDAsMiwsDQpTaGFueGksTWFpbmxhbmQgQ2hp\nbmEsMS8yNC8yMCAxNzowMCwxLCwNCklubmVyIE1vbmdvbGlhLE1haW5sYW5k\nIENoaW5hLDEvMjQvMjAgMTc6MDAsMSwsDQpRaW5naGFpLE1haW5sYW5kIENo\naW5hLDEvMjQvMjAgMTc6MDAsLCwNCldhc2hpbmd0b24sVVMsMS8yNC8yMCAx\nNzowMCwxLCwNCkNoaWNhZ28sVVMsMS8yNC8yMCAxNzowMCwxLCwNCixKYXBh\nbiwxLzI0LzIwIDE3OjAwLDIsLA0KLFRoYWlsYW5kLDEvMjQvMjAgMTc6MDAs\nNSwsDQosU291dGggS29yZWEsMS8yNC8yMCAxNzowMCwyLCwNCixTaW5nYXBv\ncmUsMS8yNC8yMCAxNzowMCwzLCwNCixWaWV0bmFtLDEvMjQvMjAgMTc6MDAs\nMiwsDQosRnJhbmNlLDEvMjQvMjAgMTc6MDAsMiws\n",
    "encoding": "base64",
    "_links": {
        "self": "https://api.github.com/repos/CSSEGISandData/COVID-19/contents/csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv?ref=master",
        "git": "https://api.github.com/repos/CSSEGISandData/COVID-19/git/blobs/0eba75c8b7b677a303b4ec5ff6902b55392fac8a",
        "html": "https://github.com/CSSEGISandData/COVID-19/blob/master/csse_covid_19_data/csse_covid_19_daily_reports/01-24-2020.csv"
    }
}
------------------------------------------------------------------------------------------------------------------------
use base64_decode
example:
$str = 'SGVsbG8gV29ybGQg8J+Yig==';
echo base64_decode($str);
# Output
Hello World
------------------------------------------------------------------------------------------------------------------------
echo csv_table(base64_decode($json->content));
------------------------------------------------------------------------------------------------------------------------
http://localhost/corona/index.php?opt=2&csv=03-20-2020

Array
(
    [0] => Province/State,Country/Region,Last Update,Confirmed,Deaths,Recovered,Latitude,Longitude
    [1] => Hubei,China,2020-03-20T07:43:02,67800,3133,58382,30.9756,112.2707
    [2] => ,Italy,2020-03-20T17:43:03,47021,4032,4440,41.8719,12.5674
    [3] => ,Spain,2020-03-20T17:43:03,20410,1043,1588,40.4637,-3.7492
    [4] => ,Germany,2020-03-20T20:13:15,19848,67,180,51.1657,10.4515
    [5] => ,Iran,2020-03-20T15:13:21,19644,1433,6745,32.4279,53.6880
    [6] => France,France,2020-03-20T22:43:03,12612,450,12,46.2276,2.2137
    [7] => ,"Korea, South",2020-03-20T02:13:46,8652,94,1540,35.9078,127.7669
    [8] => New York,US,2020-03-20T22:14:43,8310,42,0,42.1657,-74.9481
...

   [294] => ,Guernsey,2020-03-17T18:33:03,0,0,0,49.4500,-2.5800
    [295] => ,Jersey,2020-03-17T18:33:03,0,0,0,49.1900,-2.1100
    [296] => ,Puerto Rico,2020-03-17T16:13:14,0,0,0,18.2000,-66.5000
    [297] => ,Republic of the Congo,2020-03-17T21:33:03,0,0,0,-1.4400,15.5560
    [298] => ,The Bahamas,2020-03-19T12:13:38,0,0,0,24.2500,-76.0000
    [299] => ,The Gambia,2020-03-18T14:13:56,0,0,0,13.4667,-16.6000
)
------------------------------------------------------------------------------------------------------------------------

*/
?>
