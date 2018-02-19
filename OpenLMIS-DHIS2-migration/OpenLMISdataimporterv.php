<?php
/**
 * This file imports data from the excel files exported from OpenLMIS to a DHIS2 Instance
 * One need to supply reference to the excel files saved as CSV, the organisation units ids and data elements for which data would be migrated
 * The Id for the dataset in DHIS2 for which this data is being migrated, username, password and URL for the instance
 * The scrip writes a log file for the data migration for future reference (if necessary)
 *   

 * developer: nthezemu(jnr)
 * Date: 14.09.2016
 * UPDATED 01.02.2018
 *
 */

//----------------------------------------------------------------------------------------------------------------------
//  parameters for importing the data
//----------------------------------------------------------------------------------------------------------------------

$dataelementscsv = isset($argv[1]) ? $argv[1] : 'openlmisdataelements.csv'; //dataelemes details got after running datasetdettailslister.php file
$datavaluescsv = isset($argv[2]) ? $argv[2] : 'openlmisdatavaluesjul2017.csv'; // The csv file containing the values;
$orgunitscsv = isset($argv[3]) ? $argv[3] : 'orgunitsconsolidated.csv'; // This file contains all the orgunits and their ids.
$period = "201707"; //this is period for which the data needs to be migrated.
//log file description
$logdescription = "openLMIS_migration"; //this is optional and it  contains the description (if you want one) of the log file 

// target server parameters
$target_dataset = "Gf2Vrx3fpgIurfh3"; // replace with the id of the dataset you are migrating data for
$target_username = "admin"; //replace with your username
$target_password = "district"; //replace with your password
$target_api_url = "https://play.dhis2.org/2.28/api/"; //replace with your server URL
$date = date("Y-m-d");

$orgunitidentifierarray["orgunitidentifiername"]= "";
$orgunitidentifierarray["orgunitidentifiercode"]= "";


$dataelementidarray = array("dataelementid" => "");
$categorycomboidarray = array("categorycomboid" => "");
$attributeidarray = array("attributeid" => "");

//----------------------------------------------------------------------------------------------------------------------
//put the orgunits into an array
//----------------------------------------------------------------------------------------------------------------------
$readorgunits = fopen($orgunitscsv,"r") or die('<b style=\"background-color:#FF0000;color:#FFFFFF;\">Can\'t open org units csv file</b>');  
while(! feof($readorgunits)) {
	$readorgunitsarray = fgetcsv($readorgunits);

	// skip headings
	if ($readorgunitsarray[0] == "name") {
  		continue;
  	}
	//----------------------------------------------------------------------------------------------------------------------
	// add to orgunits array
	//----------------------------------------------------------------------------------------------------------------------
	$readorgunitname = trim($readorgunitsarray[0]);
        $orgunitsarray["$readorgunitname"] = array(
    		'orgunitid' => $readorgunitsarray[1]    		
	);
}  
fclose($readorgunits);
 
//----------------------------------------------------------------------------------------------------------------------
// open log file
//----------------------------------------------------------------------------------------------------------------------
$outputfile = "./logs/dataimporter ".$logdescription . date("Y-m-d-H-i-s") . ".txt";
$logfile = fopen($outputfile, "w") or die("<br><br><b style=\"background-color:#FF0000;color:#FFFFFF;\">Unable to open the log file! please make sure you have in the location of this file a folder named logs and that you have priviledges to write to logs folder"."<br>".
	"1.sudo mkdir /destination to logs folder" ."<br>"."2. you can issue the command \"sudo chmod -R 777 /destination to logs folder </b>");
fwrite($logfile, "Importing data for dataset: ".$target_dataset."\n\n" );
echo "Importing data for dataset: ".$target_dataset."\n\n<br><br>"; 

//----------------------------------------------------------------------------------------------------------------------
//put the data elements into an array
//----------------------------------------------------------------------------------------------------------------------

$readdataelements  = fopen($dataelementscsv,"r") or die('<b style=\"background-color:#FF0000;color:#FFFFFF;\">Cant open dataelements details file</b>');  

while(! feof($readdataelements)) {
	$dataelementsarray  =fgetcsv($readdataelements);

	// skip headings
	if ($dataelementsarray[0]  == "Data Element Name") {
  		continue;
  	}
	//put the data elements into 3 arrays containing data element id, category option combo and attribute id
	$readdataelementcode = $dataelementsarray[1];
	$dataelementidarray["$readdataelementcode"] = $dataelementsarray[3];
	$categorycomboidarray["$readdataelementcode"] = $dataelementsarray[4];
	$attributeidarray["$readdataelementcode"] = $dataelementsarray[5];    
}

fclose($readdataelements);
//----------------------------------------------------------------------------------------------------------------------
// read the excel sheet containing the values and put them into the migrationpayload array
//----------------------------------------------------------------------------------------------------------------------
$readdatavalues  = fopen($datavaluescsv,"r") or die('<b style=\"background-color:#FF0000;color:#FFFFFF;\">Can\'t open the file containing the values</b>'); 
//control is put here so as to skip the empty rows/ headers in the excel file containing the data
$control = 0;
while(! feof($readdatavalues)) {
	$datavaluesarray  =fgetcsv($readdatavalues);
	// skip headings
	if ($control < 2 or $datavaluesarray[2]  == "Name") {
		$control++;
		continue;
	}



	//----------------------------------------------------------------------------------------------------------------------
	//get the orgunit id using the name of the orgunit or the code in the csv file
	//----------------------------------------------------------------------------------------------------------------------
	$orgunitidentifiername = trim($datavaluesarray[2]);
	$orgunitcode = trim($datavaluesarray[5]);
	if(isset($orgunitidentifiername) and $orgunitidentifiername !=""){
		$orgunitidentifierarray["orgunitidentifiername"] = $orgunitidentifiername;
		$orgunitidentifierarray["orgunitidentifiercode"] = $orgunitcode;	
	}
	
	$orgunitname = $orgunitidentifierarray["orgunitidentifiername"];
	$orgunitidefiercode = $orgunitidentifierarray["orgunitidentifiercode"];
	$orgunitid = $orgunitsarray[$orgunitname]['orgunitid'];
	//check if the orgunit id was not found using the name
	if($orgunitid == ""){
		$orgunitid = $orgunitsarray[$orgunitidefiercode]['orgunitid'];
	}
	//----------------------------------------------------------------------------------------------------------------------
	//get the data values and put them into the json string
	//----------------------------------------------------------------------------------------------------------------------  	
	//read the product code, and all the three data values;

	$productcode = trim($datavaluesarray[7]);
	$facilityname = trim($datavaluesarray[2]);
	//read the other variables
	$stockonhand = trim($datavaluesarray[8]);
	$quatintyused = trim($datavaluesarray[9]);
	$stockoutdays = trim($datavaluesarray[10]);
	$quantityreceived = trim($datavaluesarray[12]);

	//check if orgunit id is empty and skip migration if it's empty
	if($orgunitid == ""){
		continue;
	}
	
	//write the data into json format
	//create a json string for migrating the data
	$migrationpayload = "{\"dataSet\":";
	$migrationpayload .= "\""."$target_dataset"."\",";
	$migrationpayload .= "\"completeDate\": ";
	$migrationpayload .= "\""."$date"."\",";
	$migrationpayload .= "\"period\":";
	$migrationpayload .= "\""."$period"."\",";
	$migrationpayload .= "\"orgUnit\":";
	$migrationpayload .= "\""."$orgunitid"."\",";
	$migrationpayload .= "\"dataValues\": [";
        //enter code for the stock on hand data
	//get the data elements attribute for the stock on hand data
	$dataelementstockonhandid = $productcode ."-a";
	$migrationpayload .= updatemigrationpayload($migrationpayload,$dataelementstockonhandid,$dataelementidarray,$categorycomboidarray,$attributeidarray);

	//get the data elements attribute for quantity used data
	$dataelementstockusedid = $productcode ."-b";
	$migrationpayload .= updatemigrationpayload($migrationpayload,$dataelementstockusedid,$dataelementidarray,$categorycomboidarray,$attributeidarray);
	
	//get the data elements attribute for stockout days data
	$dataelementstockoutdaysid = $productcode ."-c";
	$migrationpayload .= updatemigrationpayload($migrationpayload,$dataelementstockoutdaysid,$dataelementidarray,$categorycomboidarray,$attributeidarray);

	//get the data elements attribute for quantity received data
	$dataelementquantityreceivedid = $productcode ."-d";
	$migrationpayload .= updatemigrationpayload($migrationpayload,$dataelementquantityreceivedid,$dataelementidarray,$categorycomboidarray,$attributeidarray);
	
	//close the migration payload
	$migrationpayload .= "]}";
  	
	echo ($migrationpayload."<br><br>");
	fwrite($logfile, "Target JSON string\n\n". $migrationpayload . "\n\n" );

	//--------------------------------------------------------------------------------------------------------------
	// push the read values to target DHIS instance
	//--------------------------------------------------------------------------------------------------------------
	$remote_url = $target_api_url . 'dataValueSets';

	// Create a stream
	$opts = array(
		'http'=>array(
			'method'=>"POST",
			'header' => "Authorization: Basic " . base64_encode("$target_username:$target_password") . "\r\n" .
			"Content-Type: application/json",
			'content' => $migrationpayload
		)
	);
	$context  = stream_context_create($opts);

	// push dataset values and get response from server
	$response = file_get_contents($remote_url, false, $context);
	if(!$response){
		$errormessage = "data could not be imported because your computer could not connect to the target server. Please check you iternet connection settings and rerun the file";
		echo"<p style=\"background-color:#FF0000;font-size:50px;text-style:bold;\">Sorry the ".$errormessage."</p";
		fwrite($logfile, "The Import process failed because\n\n". $errormessage. "\n\n" );
		die;
	}
	else{
		print($response)."<br>";
		fwrite($logfile, "\n\n". $response. "\n\n" );
	} 

}
//function for updating the migration payload
function updatemigrationpayload($migrationpayload,$dataelementidentifer,$dataelementidarray,$categorycomboidarray,$attributeidarray){
	$dataelementid = $dataelementidarray[$dataelementidentifer];
	$dataelementcategoryoptioncombo = $categorycomboidarray[$dataelementidentifer];
	$dataelementattributeoptioncombo = $attributeidarray[$dataelementidentifer];
	//----------------------------------------------------------------------------------------------------------------------
	//enter into the json the data for selected dataelement
	//----------------------------------------------------------------------------------------------------------------------
	//enter code for the stock on hand data
	$migrationpayload .= "{ \"dataElement\":";
	$migrationpayload .= "\""."$dataelementid"."\",";
	$migrationpayload .= "\"period\":";
	$migrationpayload .= "\""."$period"."\",";
	$migrationpayload .= "\"orgUnit\":";
	$migrationpayload .= "\""."$orgunitid"."\",";
	$migrationpayload .= "\"categoryOptionCombo\":";
	$migrationpayload .= "\""."$dataelementcategoryoptioncombo"."\",";
	$migrationpayload .= "\"attributeOptionCombo\":";
	$migrationpayload .= "\""."$dataelementattributeoptioncombo"."\",";
	$migrationpayload .= "\"value\":";
	$migrationpayload .= "\""."$stockoutdays"."\",";
	$migrationpayload .= "\"storedBy\":";
	$migrationpayload .= "\" "."$target_username"."\"";
	$migrationpayload .=" },";
	return $migrationpayload;
}
//close the file for data values
fclose($readdatavalues);
//close the table



 
    
    
