<?php 
/**
* This file taks the contents in a row which has many columns and puts them in rows in a single column
* This helps when doing mapping as you do it row by row rather than column by column
* Author:nthezemu(jnr)
* Date: 14.09.2016 
**/

//----------------------------------------------------------------------------------------------------------------------
//  Columfy parameters
//----------------------------------------------------------------------------------------------------------------------
$dataelementscsv = isset($argv[1]) ? $argv[1] : 'dhamishtcdataq2.csv'; //this is the file containing the data values that has the dataelements headers that you want to put into a column for mapping
$dataelementstotal = 28; // this is the total number of the data elements in the dataelementscsv file
$dataelementsoffset = 8; // This is the column in the excell sheet where the data elements begins with first column counted as zero(0);
//header table
$headerdetailstable = '<table style="border: 1pt solid #000; border-collapse: collapse" cellspacing="0">' .
'<tr><td style="border: 1pt solid #000">Serial No/Position</td>'.
'<td style="border: 1pt solid #000">dataelement Name</td></tr>';
//open the file and put the contents into rows
$readdataelementsheaders  = fopen($dataelementscsv,"r") or die('Cant open file containing the data values'); 
$control = 1;//this controls the number of rows that need not to be considered when putting the contents into rows.

while(! feof($readdataelementsheaders)) {
	if($control != 1){
		break;
	}
	$control++;
	$dataelementsheaders  =fgetcsv($readdataelementsheaders);
	for($j=0;$j<$dataelementstotal;$j++){
		$headerdetailstable .= '<tr><td style="border: 1pt solid #000">'.($j+1).'</td>'.
		'<td style="border: 1pt solid #000">'.$dataelementsheaders[$j+$dataelementsoffset].'</td></tr>';
	}
     
}

fclose($readdataelementsheaders);
print($headerdetailstable);
        
