<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$subject = 'FIPS,Admin2,Province_State,Country_Region,Last_Update,Lat,Long_,Confirmed,Deaths,Recovered,Active,Combined_Key
45001,Abbeville,South Carolina,US,2020-03-23 23:19:34,34.22333378,-82.46170658,1,0,0,0,"Abbeville, South Carolina, US"
22001,Acadia,Louisiana,US,2020-03-23 23:19:34,30.295064899999996,-92.41419698,1,0,0,0,"Acadia, Louisiana, US"';

echo '<hr />';
echo 'Original<br />';
echo $subject;
echo '<hr />';


$pattern = '/(".+)(, )(.+")/';
$replacement = function($r) {
	$r[2] = '-';
	return $r[1] . $r[2] . $r[3];
};
$modi = preg_replace_callback($pattern,$replacement,$subject);

echo '<br />Modified<br />';
echo $modi;
echo '<hr />';

$pattern = '/(".+)(, )(.+")/';
$replacement = '$1-$3';
$count = 1;
while ($count == 1) {
	$subject = preg_replace($pattern,$replacement,$subject, 1, $count);
}

echo '<br />Modified<br />';
echo '$count = ' . $count . '<br />';
echo $subject;
echo '<hr />';






/*


$dates on 25/03/2020
Array
(
    [0] => 03-23-2020
    [1] => 03-24-2020
)

$original_field_names on 24/03/2020
Array
(
    [0] => FIPS
    [1] => Admin2
    [2] => Province_State
    [3] => Country_Region
    [4] => Last_Update
    [5] => Lat
    [6] => Long_
    [7] => Confirmed
    [8] => Deaths
    [9] => Recovered
    [10] => Active
    [11] => Combined_Key
)

$original_field_names on 25/03/2020
Array
(
    [0] => FIPS
    [1] => Admin2
    [2] => Province_State
    [3] => Country_Region
    [4] => Last_Update
    [5] => Lat
    [6] => Long_
    [7] => Confirmed
    [8] => Deaths
    [9] => Recovered
    [10] => Active
    [11] => Combined_Key
)

unset($a_field_names[0],$a_field_names[1],$a_field_names[10],$a_field_names[11]);
Array
(
    [2] => Province_State
    [3] => Country_Region
    [4] => Last_Update
    [5] => Lat
    [6] => Long_
    [7] => Confirmed
    [8] => Deaths
    [9] => Recovered
)

$a_field_names = array_values($a_field_names);
Array
(
    [0] => Province_State
    [1] => Country_Region
    [2] => Last_Update
    [3] => Lat
    [4] => Long_
    [5] => Confirmed
    [6] => Deaths
    [7] => Recovered
)

moveElement($a_field_names,3,7);
moveElement($a_field_names,3,7);		

Array
(
    [0] => Province_State
    [1] => Country_Region
    [2] => Last_Update
    [3] => Confirmed
    [4] => Deaths
    [5] => Recovered
    [6] => Lat
    [7] => Long_
)



		
		echo $sql;
		echo '<br />';
		
		echo '$qrec = ' . $qrec;
		echo '<hr />';		
		


		// it happens that sometime the first item is not appended
		if ( ! ( is_string($line_array[0]) and is_string($line_array[1]) and is_string($line_array[2]) ) ) {	 
			array_unshift($line_array,'""');
		}


		
". Azerbaijan","2020-02-28 15:03:26",1,0,0,0,0,0
invece di		
"","Azerbaijan","2020-02-28 15:03:26",1,0,0,0,0		
apparentemente inspiegabile perché la tabella in github appare perfetta
occorre dunque controllare che i primi due campi siano delle stringhe
nel caso non lo siano aggiungere una stringa vuota all'inizio

da programma
"Santa Clara. CA","US","2020-02-21 05:23:04",2,0,1,0,0
". Azerbaijan","2020-02-28 15:03:26",1,0,0,0,0,0

Originale in github
Santa Clara, CA	US	2020-02-21T05:23:04	2	0	1
Azerbaijan	2020-02-28T15:03:26	1	0	0

raw table data
http://localhost/corona/index.php?opt=4&csv=02-28-2020
, Azerbaijan,2020-02-28T15:03:26,1,0,0
note space after comma







		
		// è qui il problema del punto prima di Azerbaijan
		$row = str_replace (', ','. ',$row); // comma within double quotes




// $pattern = '/".+,.+"/ims';
//$subject = preg_replace($pattern,'$1.$3',$subject,-1,$count);

$subject = '1-22-2020 17:00';
$pattern = '/(\d{1}|\d{2})-(\d{1}|\d{2})-(\d{4}) (\d{2}:\d{2})/';
$replacement = '$3-$1-$2 $4:00';
$modi = preg_replace($pattern,$replacement,$subject);

echo $subject;
echo '<br />';
echo $modi;
echo '<hr />';

$subject = '1/22/2020 17:00';
$pattern = '/(\d{1}|\d{2})\/(\d{1}|\d{2})\/(\d{4}) (\d{2}:\d{2})/';
//$replacement = '$3-$1-$2 $4:00';
$replacement = function($r) {
	if (strlen($r[1])==1) $r[1] = '0'.$r[1];
	if (strlen($r[2])==1) $r[2] = '0'.$r[2];
	return $r[3].'-'.$r[1].'-'.$r[2].' '.$r[4].':00';
};
$modi = preg_replace_callback($pattern,$replacement,$subject);

echo $subject;
echo '<br />';
echo $modi;
echo '<hr />';

$subject = '1/2/2020 17:00';
$pattern = '/(\d{1}|\d{2})\/(\d{1}|\d{2})\/(\d{4}) (\d{2}:\d{2})/';
//$replacement = '$3-$1-$2 $4:00';
$replacement = function($r) {
	if (strlen($r[1])==1) $r[1] = '0'.$r[1];
	if (strlen($r[2])==1) $r[2] = '0'.$r[2];
	return $r[3].'-'.$r[1].'-'.$r[2].' '.$r[4].':00';
};
$modi = preg_replace_callback($pattern,$replacement,$subject);

echo $subject;
echo '<br />';
echo $modi;
echo '<hr />';







*/

?>
