<?php
/**
 *
 *
 * allows data migration from an excel sheet exported from DHAMIS to DHIS2 instance
 * writes a log file for the data migration for future reference (if necessary)
 *   

 * Author:nthezemu(jnr)
 * Date: 14.09.2016
 * 
 */

//----------------------------------------------------------------------------------------------------------------------
//  parameters for importing the data
//----------------------------------------------------------------------------------------------------------------------

$dataelementscsv = isset($argv[1]) ? $argv[1] : 'htcmappeddataelements.csv';
$datavaluescsv = isset($argv[2]) ? $argv[2] : 'dhamishtcdataq1.csv'; // The csc file containing the values;
$orgunitscsv = isset($argv[3]) ? $argv[3] : 'orgunitsconsolidated.csv'; // This file contains all the orgunits and their ids.
$dataentriescount = 2176; //this is one less the total number of rows in the datavaluescsv file 
$dataelementstotal = 28; //this is the number of data elements which are being imported 
$dataelementsoffset = 2; // This is the column in the excel sheet where the data elements begins with first column counted as zero(0);

//log file description
$logdescription = "htcmigration"; //this is optional and it  contains the description (if you want one) of the log file 

// target server parameters
$target_dataset = "datasetid";
$target_username = "username";
$target_password = "password";
$target_api_url = "serverURL";
$date = date("Y-m-d");

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
//create a mapped array to be used in migrating the data later on
//----------------------------------------------------------------------------------------------------------------------
$dataelementspayload =array(
             "dataSet"=>"",
             "completeDate"  => "",
             "period"  => "",
             "orgUnit"  => "",
             "dataValues"  => array(
                            "dataElement"  => "",
                            "period"  => "",
                            
                            "categoryOptionCombo"  => "",
                            "attributeOptionCombo"  => "",
                            "value"  => "",
                            "storedBy"  => ""
                            
              )
  );
  
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
$dataelementscount  = 0;  

while(! feof($readdataelements)) {
	if($dataelementstotal<0){
		break;
	}
	$dataelementstotal--;

	$dataelementsarray  =fgetcsv($readdataelements);

  	// skip headings
  	if ($dataelementsarray[0]  == "Data Element Name") {
  		continue;
  	}
     //create an associative array for the data elements.
      $dataelementspayload['dataValues'][$dataelementscount]['dataElement']  = $dataelementsarray[2]; 
      $dataelementspayload['dataValues'][$dataelementscount]['period']  = "";
      $dataelementspayload['dataValues'][$dataelementscount]['categoryOptionCombo']  = $dataelementsarray[3]; 
      $dataelementspayload['dataValues'][$dataelementscount]['attributeOptionCombo']  = $dataelementsarray[4];
      $dataelementspayload['dataValues'][$dataelementscount]['value']  = "";
      $dataelementspayload['dataValues'][$dataelementscount]['storedBy']  = $target_username;
      $dataelementscount++;
 
     
}

fclose($readdataelements);

//----------------------------------------------------------------------------------------------------------------------
// read the excel sheet containing the values and put them into the migrationpayload array
//----------------------------------------------------------------------------------------------------------------------
$readdatavalues  = fopen($datavaluescsv,"r") or die('<b style=\"background-color:#FF0000;color:#FFFFFF;\">Can\'t open the file containing the values</b>'); 
$datavaluesarray  =fgetcsv($readdatavalues);

$control = 0;
while(! feof($readdatavalues)) {
	
	if($dataentriescount<1){
		break;
	}
  

	$dataentriescount--;
	$datavaluesarray  =fgetcsv($readdatavalues);

  	// skip headings
  	if ($datavaluesarray[0]  == "Site") {
  		continue;
  	}



    //----------------------------------------------------------------------------------------------------------------------
  	//get the orgunit id using the name of the orgunit in the csv file
    //----------------------------------------------------------------------------------------------------------------------
  	$orgunitidentifier = trim($datavaluesarray[0]);
  	$orgunitid = $orgunitsarray[$orgunitidentifier]['orgunitid'];
  	$period = $datavaluesarray[1];
  	
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

//----------------------------------------------------------------------------------------------------------------------
//get the data values and put them into the json string
//----------------------------------------------------------------------------------------------------------------------
  	
  	for($i=0; $i<$dataelementscount;$i++){
        //keep editing the migration payload as long as there are data values
        $dataelementid = $dataelementspayload['dataValues'][$i]['dataElement'];
        $dataelementcategoryoptioncombo = $dataelementspayload['dataValues'][$i]['categoryOptionCombo'];
        $dataelementattributeoptioncombo = $dataelementspayload['dataValues'][$i]['attributeOptionCombo'];
        $dataelementvalue = $datavaluesarray[$i+$dataelementsoffset];
        
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
       $migrationpayload .= "\""."$dataelementvalue"."\",";
       $migrationpayload .= "\"storedBy\":";
       $migrationpayload .= "\" "."$target_username"."\"";
       
       //----------------------------------------------------------------------------------------------------------------------
       //check if this is the last iteration so that we should not include the last comma
       //----------------------------------------------------------------------------------------------------------------------
       if($i< ($dataelementscount -1)){
        $migrationpayload .=" },";
       }
       else{
        $migrationpayload .=" }";
       }
                

  	} 
  	
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
//close the file for data values
fclose($readdatavalues);


 
    
    
