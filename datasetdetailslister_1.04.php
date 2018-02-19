<?php
/**
 * This file lists the data elements associated with the selected data set. 
 * You need to supply the username, password and URL for the instance you are using.
 * Then you need to run this file either on the terminal or in the browser.
 * Select the dataset of interest from the list of datasets displayed
 * The details displayed are the ones required when migrating data so need to be saved as csv
 * if you want to try it on DHIS2 Demo server use URL https://play.dhis2.org/2.28 and admin as username and district as password
**/

// connection paramaters: change these parameters to corresponding values on the target DHIS instance
$username = "admin";//change the username here
$password = "district"; // change the password here
$apiURL   = "https://play.dhis2.org/2.28/api"; // change the server url here



// function to list dataset details
function listDatasetDetails ($apiURL,$context,$dataset,$defaultid){
    if ($dataset == "none") {
        return "";
    }

    $dataseturl = $apiURL . "/dataSets/$dataset.json";

    $datasetjson = file_get_contents($dataseturl, false, $context);

    $datasetarray = json_decode($datasetjson,true);

    $detailstable = '<p><b>Dataset ID:</b>' . $dataset . '</p><table style="border: 1pt solid #000; border-collapse: collapse" cellspacing="0">' .
    '<tr><td style="border: 1pt solid #000">Data Element Name</td>' .
    '<td style="border: 1pt solid #000">Code</td>' .
    '<td style="border: 1pt solid #000">Category Name</td>' .
    '<td style="border: 1pt solid #000">Data Element ID</td>' .
    '<td style="border: 1pt solid #000">Category ID</td>' .
    '<td style="border: 1pt solid #000">Attribute ID</td></tr>';

    for ($i = 0; $i < count( $datasetarray["dataSetElements"]); $i++ ) {
    	//for ($i = 0; $i < 1; $i++ ) {

        $dataelementid = $datasetarray["dataSetElements"][$i]["dataElement"]["id"];

	//get the name of the dataelement
        
        $dataelementurl = $apiURL . "/dataElements/$dataelementid.json";
	$dataelementjson = file_get_contents($dataelementurl, false, $context);
	$dataelementarray = json_decode($dataelementjson,true);
        $dataelementname = $dataelementarray["name"];
	$dataelementcode = $dataelementarray["code"];
	
        

    
	$dataelementcategoryid = $dataelementarray["categoryCombo"]["id"];

	//get the dataelement category name
        $dataelementcategoryurl  = $apiURL . "/categoryCombos/$dataelementcategoryid.json";
	$dataelementcategoryjson = file_get_contents($dataelementcategoryurl, false, $context);
	$dataelementcategoryarray = json_decode($dataelementcategoryjson,true);
        //var_dump($dataelementcategoryjson);
        if(!$dataelementcategoryarray){
		//default category
		$categorycomboname = "default";
		$categorycomboid = $defaultid;

		//write the html to the table
		//$attributeid = $categorycomboname == "default" ? $categorycomboid : $defaultid;
		$attributeid = $defaultid;
	

           	 $detailstable .= '<tr><td style="border: 1pt solid #000">'.$dataelementname.'</td>' .
		'<td style="border: 1pt solid #000">'.$dataelementcode.'</td>' .
                '<td style="border: 1pt solid #000">'.$categorycomboname.'</td>' .
                '<td style="border: 1pt solid #000">'.$dataelementid.'</td>' .
                '<td style="border: 1pt solid #000">'.$categorycomboid.'</td>' .
                '<td style="border: 1pt solid #000">'.$attributeid.'</td></tr>';
	}
	
	else{
		//dataelement is categorized hence find out on these
        	for ($j = 0; $j < count( $dataelementcategoryarray["categoryOptionCombos"]); $j++ ){

			//find out the categoryoption name
			$dataelementcategoryoptionid = $dataelementcategoryarray["categoryOptionCombos"][$j]["id"];
        		$dataelementcategoryoptionurl = $apiURL . "/categoryOptionCombos/$dataelementcategoryoptionid.json";
			$dataelementcategoryoptionjson = file_get_contents($dataelementcategoryoptionurl, false, $context);
			$dataelementcategoryoptionarray = json_decode($dataelementcategoryoptionjson,true);

			$categorycomboname = $dataelementcategoryoptionarray["name"];

			//write the html to the table
			$categorycomboid = $dataelementcategoryoptionid;
			//$attributeid = $categorycomboname == "default" ? $categorycomboid : $defaultid;
			$attributeid = $defaultid;

           		$detailstable .= '<tr><td style="border: 1pt solid #000">'.$dataelementname.'</td>' .
               		'<td style="border: 1pt solid #000">'.$dataelementcode.'</td>' .
			'<td style="border: 1pt solid #000">'.$categorycomboname.'</td>' .
               		'<td style="border: 1pt solid #000">'.$dataelementid.'</td>' .
        		'<td style="border: 1pt solid #000">'.$categorycomboid.'</td>' .
                	'<td style="border: 1pt solid #000">'.$attributeid.'</td></tr>';
	        }	
	}
        
    }

    $detailstable .= "</table>";
    return $detailstable;
}

//function to create an HTML form for selecting dataset whose details to list
function getDatasets($apiURL,$context, $selected="none") {
    $datasetsurl = $apiURL . "/dataSets.json?paging=false";
    $datasetsjson = file_get_contents($datasetsurl, false, $context);
    $datasetsArray = json_decode($datasetsjson,true);

    $form = '<form name="selectdataset" id="selectdataset" method="post" action="datasetdetailslister_1.04.php">' .
    'Dataset <select id="dataset" name="dataset" onchange="document.getElementById(\'selectdataset\').submit();"><option value="none">Select Dataset</option>';

    for($i = 0; $i < count ( $datasetsArray["dataSets"] ); $i++) {

        if ($datasetsArray["dataSets"][$i]["id"] == $selected) {
            $form .= '<option value="' . $datasetsArray["dataSets"][$i]["id"] . '" selected>' .
                $datasetsArray["dataSets"][$i]["displayName"] . '</option>';
        }
        else {
            $form .= '<option value="' . $datasetsArray["dataSets"][$i]["id"] . '">' .
            $datasetsArray["dataSets"][$i]["displayName"] . '</option>';
        }
    }

    $form .= "</select></form>";

    return $form;
}

// function to get the defaultid used as value for attributeid
function getDefaultID($apiURL,$context) {
    $categoryoptionurl = $apiURL . "/categoryOptionCombos.json?paging=false";
    $combojson = file_get_contents($categoryoptionurl, false, $context);
    $comboarray = json_decode($combojson,true);

    for ($h = 0; $h < count( $comboarray["categoryOptionCombos"]); $h++ ) {
        if ($comboarray["categoryOptionCombos"][$h]["displayName"] == "default") {
            $defaultid = $comboarray["categoryOptionCombos"][$h]["id"];
            return $defaultid;
        }
    }

    return "";
}

// Create a stream
$opts = array(
    'http'=>array(
        'method'=>"GET",
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    )
);

$context = stream_context_create($opts);

// get the default id
$defaultid = getDefaultID($apiURL,$context);

// get selected dataset (will be set to "none" if no dataset is selected)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dataset = $_POST["dataset"];
}
else {
    $dataset = "none";
}

// create the dataset selection form
$form = getDatasets($apiURL,$context,$dataset);

// list details of the selected dataset
$table = listDatasetDetails($apiURL,$context,$dataset,$defaultid);

print $form . $table;
