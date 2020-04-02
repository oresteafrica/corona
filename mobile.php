


<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// opt = option for switch
if (check_get('opt') == 'ok') { $opt = $_GET['opt']; }  else { exit; };
// opt = option for switch
if (check_get('lan') == 'ok') { $lan = $_GET['lan']; }  else { exit; };

switch ($opt) {
	case -1: // debug
		debug($opt,$csv);
		exit;
        break;
	case 0: // 
        break;
    case 1: // first form
		echo form_choose_country($lan);
        break;
	case 2: // second form last day data
		echo '<h1>'.lan_interface($lan,2).'</h1>';
//		echo '<pre>'; print_r(country_data($lan,$_GET['countries'])); echo '</pre>';
		echo country_data($lan,$_GET['countries']);
        break;
	case 3: // debug country list
		echo '<pre>'; print_r(country_list()); echo '</pre>';
        break;
    case 4: // json country list
		echo country_list(true);
        break;
    case 5: // json last day country data
		echo country_data($lan,$_GET['countries'],true);
        break;
    case 6: // json all country data
		json_all_country_data($lan, $_GET['countries']);
        break;
    case 7: // 
        break;
    case 8: // 

        break;
    case 9: // 

        break;
    case 10: // 

        break;
    default:
       exit;
}

// ---------------------------------------------------------------------------------------------------------------------
function json_all_country_data($lan,$country) {
	$db = database();
	$sql = 'SELECT * FROM daily WHERE Country = "' . $country . '" ORDER BY Lastupdate ASC';
	$tabquery = $db->query($sql);
	$tabquery->setFetchMode(PDO::FETCH_ASSOC);
	if ($tabquery->rowCount() == 0) {
		return 0;
	} else {
		$rows = [];
		$dates = [];
		$confirmed = [];
		$deaths = [];		
		$recovered = [];
		foreach($tabquery as $row) {
			$rows[] = $row;
			$dates[] = mb_substr($row['Lastupdate'],0,10);
			$confirmed[] = $row['Confirmed'];
			$deaths[] = $row['Deaths'];
			$recovered[] = $row['Recovered'];
		}
	}
	$data = array($row['Country'],$dates,$confirmed,$deaths,$recovered);

//	print_r($data);

	header("Content-Type: application/json");
	echo json_encode($data);
	
	return 1;
}
// ---------------------------------------------------------------------------------------------------------------------
function country_data($lan,$country,$json = false) {
	$db = database();
	$sql = 'SELECT * FROM daily WHERE Country = "' . $country . '" ORDER BY Lastupdate DESC LIMIT 1';
	$tabquery = $db->query($sql);
	$tabquery->setFetchMode(PDO::FETCH_ASSOC);
	if ($tabquery->rowCount() == 0) {
		return 0;
	} else {
		$rows = [];
		foreach($tabquery as $row) {
			$rows[] = $row;
		}
		$html_country = '<div style="width:100%;background-color:white;"><div>'.lan_interface($lan,3).':</div><div style="font-weight:bold;">'.$rows[0]['Country'].'</div></div>'.
			'<div style="width:100%;background-color:white;">'.
			'<div style="width:100%;"><div>'.lan_interface($lan,4).':</div><div style="font-weight:bold;">'.$rows[0]['Lastupdate'].'</div></div>'.
			'<div style="width:100%;"><div>'.lan_interface($lan,5).':</div><div style="font-weight:bold;">'.$rows[0]['Confirmed'].'</div></div>'.
			'<div style="width:100%;"><div>'.lan_interface($lan,6).':</div><div style="font-weight:bold;">'.$rows[0]['Deaths'].'</div></div>'.
			'<div style="width:100%;"><div>'.lan_interface($lan,7).':</div><div style="font-weight:bold;">'.$rows[0]['Recovered'].'</div></div>'.
			'</div>';
	}
	if ($json) {
		return json_encode($rows);
	} else {
		return $html_country;
	}
}
// ---------------------------------------------------------------------------------------------------------------------
function lan_interface($lan,$ind) {
	$lan_interface = [];
	$lan_interface['xx'] = [ ['en','English'],['fr','Français'],['pt','Português'],['it','Italiano'],['es','Español'],
		['de','Deutsch'],['el','Ελληνικά'],['nl','Nederlands'],['ar','العربية'],['ru','Pусский'],['bg','Български'],
		['sl','Slovenščina'],['tr','Türkçe'],['fi','Suomi'],['hu','Magyar']	];
	$lan_interface['fr'] = ['États disponibles dans la base de données','Allez','Dernières données reçues','Pays','Date et heure','Confirmés','Décédés','Rétablis','','','','','','',''];
	$lan_interface['pt'] = ['Estados disponíveis na base de dados','Vai','Dados mais recentes','País','Data e hora','Confirmados','Falecidos','Recuperados','','','','','','',''];
	$lan_interface['en'] = ['Available countries in database','Go','Latest data received','Country','Date and time','Confirmed','Deaths','Recovered','','','','','','',''];
	$lan_interface['it'] = ['Stati disponibili nella base dati','Vai','Ultimi dati pervenuti','Paese','Data e ora','Confermati','Deceduti','Recuperati','','','','','','',''];
	return $lan_interface[$lan][$ind];
}
// ---------------------------------------------------------------------------------------------------------------------
function form_choose_country($lan) {
	$form = '<form action="mobile.php?opt=2&lan='.$lan.'" method="get" data-ajax="false">' . "\n";
	$form .= country_list_to_html_select($lan);
	$form .= hidden_var($lan,2);
	$form .= '<input type="submit" data-inline="true" value="'.lan_interface($lan,1).'">' . "\n";
	$form .= '</form>' . "\n";
	return $form;
}
// ---------------------------------------------------------------------------------------------------------------------
function hidden_var($lan,$opt) {
	return '<input type="hidden" id="opt" name="lan" value="'.$lan.'" />' . 
			"\n" .
			'<input type="hidden" id="opt" name="opt" value="'.$opt.'" />' .
			"\n";
}
// ---------------------------------------------------------------------------------------------------------------------
function country_list_to_html_select($lan) {
	$country_list = country_list();
	$html_select = '<label for="countries" style="font-size:x-small;background-color:white;">'.
		lan_interface($lan,0).
		'</label><select id="countries" name ="countries">' . 
		"\n";
	foreach($country_list as $country) {
		$html_select .= '<option value="'.$country.'">'.$country.'</option>' . "\n";
	}
	$html_select .= '</select>' . "\n";
	return $html_select;
}
// ---------------------------------------------------------------------------------------------------------------------
function country_list($json = false) {
	$db = database();
	$sql = 'SELECT DISTINCT(Country) AS Country FROM daily ORDER BY Country ASC';
	$tabquery = $db->query($sql);
	$tabquery->setFetchMode(PDO::FETCH_ASSOC);
	if ($tabquery->rowCount() == 0) {
		return 0;
	} else {
		$country_list = [];
		foreach($tabquery as $row) {
			$country_list[] = trim(implode(null,$row));
		}
		if ($json) {
			return json_encode($country_list);
		} else {
			return $country_list;
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
	$chk = 'ok';
	if (!isset($_GET[$var])) { $chk = 'ko'; }
	if ($_GET[$var] === '') { $chk = 'ko'; }
	if ($_GET[$var] === null) { $chk = 'ko'; }
//	if (empty($_GET[$var])) { $d = '1'; $chk = false; }
	return $chk;
}
// ---------------------------------------------------------------------------------------------------------------------
function debug() {
	echo '$_SERVER[\'HTTP_HOST\'] = ' . $_SERVER['HTTP_HOST'];
	echo '<br />';
	echo '<br />$_SERVER[\'SERVER_NAME\'] = ' . $_SERVER['SERVER_NAME'];
	echo '<br />';
	echo '<br />$_SERVER[\'REQUEST_URI\'] = ' . $_SERVER['REQUEST_URI'];
	echo '<br />';
	echo '<br />parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH) = ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	echo '<br />';
	echo '<br />$_SERVER[\'PHP_SELF\'] = ' . $_SERVER['PHP_SELF'];
	echo '<br /><br />$_GET<br />';
	print_r($_GET);
}
// ---------------------------------------------------------------------------------------------------------------------
/*

------------------------------------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------------------------------------

*/
?>

