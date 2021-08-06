<?php

	$myFile = file_get_contents("document.txt");
	$lines  = explode(PHP_EOL, $myFile);


	$documentFields = array("Booking number"       => array("hasHeader" => false, "values" => []), 
							"Passenger details"    => array("hasHeader" => false, "values" => []), 
							"Transfer information" => array("hasHeader" => false, "values" => []), 
							"Additional services"  => array("hasHeader" => false, "values" => []), 
							"Name for a table"     => array("hasHeader" => false, "values" => []), 
							"Meeting point"        => array("hasHeader" => false, "values" => []), 
							"Comments"			   => array("hasHeader" => false, "values" => []),
							"Payment information"  => array("hasHeader" => false, "values" => []));

	$strFetchPattern = array("Booking number" => array("pattern" => "/^Booking number:\s+([0-9]+)$/", "dataAdjacent" => true), 
							 
							 "Passenger details" => array("pattern" => "/^Passenger details:$/", "dataAdjacent" => false, "titleLineBypassed" => false),
							 
							 "Transfer information" => array("pattern" => "/^Transfer information:$/", "dataAdjacent" => false, "titleLineBypassed" => false),
							 
							 "Additional services" => array("pattern" => "/^Additional services$/", "dataAdjacent" => false, "titleLineBypassed" => false),
							 
							 "Name for a table" => array("pattern" => "/^Name for a table: ([A-Za-z ]+)$/", "dataAdjacent" => true),
							 
							 "Meeting point" => array("pattern" => "/^Meeting point$/", "dataAdjacent" => false, "titleLineBypassed" => false),

							 "Comments" => array("pattern" => "/^Comments:$/", "dataAdjacent" => false, "titleLineBypassed" => false),
	
							 "Payment information" => array("pattern" => "/^Payment information:$/", "dataAdjacent" => false, "titleLineBypassed" => false));


	foreach($lines as $line) {
		if(strlen($line) == 0) { continue; }
		
		foreach ($strFetchPattern as $key => $value) {
			if(preg_match($value["pattern"], $line)) {
				$tmpMatch = $key;
				break;
			}			
		}

		if(isset($tmpMatch)) {
			if($strFetchPattern[$tmpMatch]["dataAdjacent"]) {
				preg_match($strFetchPattern[$tmpMatch]["pattern"], $line, $match);
				$documentFields[$tmpMatch]["hasHeader"] = false;
				if(count($match) > 0)
					array_push($documentFields[$tmpMatch]["values"], $match[1]);
			}
			else {
				if($strFetchPattern[$tmpMatch]["titleLineBypassed"]) {
					array_push($documentFields[$tmpMatch]["values"], $line);
				}
				else {
					$strFetchPattern[$tmpMatch]["titleLineBypassed"] = true;
					$documentFields[$tmpMatch]["hasHeader"] = true;
				}
			}
		}
	}


	$bookDetailsValuePair = array();



	foreach ($documentFields as $key => $docField) {

		if(!$docField["hasHeader"]) {
			$bookDetailsValuePair[$key] = (isset($docField["values"][0])) ? $docField["values"][0] : NULL;
		}
		else {
			if(count($docField["values"]) > 0) {
				$docFieldHeader    = preg_replace("/([ ]{2,})/", "---", $docField["values"][0]);
				$docFieldColsArray = explode("---", $docFieldHeader);

				if(count($docFieldColsArray) > 1) {

					for($j = 1; $j < count($docField["values"]); $j++) {

						$currentColPos = 0;

						for($i = 0; $i < count($docFieldColsArray); $i++) {

							if($i < count($docFieldColsArray) - 1) {

								$nextColPos     = strpos($docField["values"][0], $docFieldColsArray[$i + 1]);
								$currFieldValue = preg_replace("/([ ]{2,})/", '', substr($docField["values"][$j], $currentColPos, $nextColPos - $currentColPos));	

								$currFieldSplitAsArray = explode(":", $currFieldValue);

								if(count($currFieldSplitAsArray) == 2 && !is_numeric($currFieldSplitAsArray[0]) && !empty($currFieldSplitAsArray[1])) {

									if(preg_match("/Flight.+/", $currFieldSplitAsArray[0])) {
										$currFieldSplitAsArray[0] = preg_replace("/Flight.+/", "Flight Nr", $currFieldSplitAsArray[0]);
									}
									$bookDetailsValuePair[$key][$currFieldSplitAsArray[0]] = $currFieldSplitAsArray[1];
																		
								}
								else {

									if($j == 1) {
										$bookDetailsValuePair[$key][$docFieldColsArray[$i]] = $currFieldValue; 
									}
									else {
										$bookDetailsValuePair[$key][$docFieldColsArray[$i]] .= " " . $currFieldValue;	
									}
										
								}

								$currentColPos = $nextColPos;	
							}
							else {

								$currFieldValue = preg_replace("/([ ]{2,})/", '', substr($docField["values"][$j], $currentColPos));
								$currFieldSplitAsArray = explode(":", $currFieldValue);

								if(count($currFieldSplitAsArray) == 2 && !is_numeric($currFieldSplitAsArray[0]) && !empty($currFieldSplitAsArray[1])) {
									$bookDetailsValuePair[$key][$docFieldColsArray[$i]] = array($currFieldSplitAsArray[0] => $currFieldSplitAsArray[1]);	
								}
								else {

									if($j == 1) {
										$bookDetailsValuePair[$key][$docFieldColsArray[$i]] = $currFieldValue;	
									}
									else {
										$bookDetailsValuePair[$key][$docFieldColsArray[$i]] .= " " . $currFieldValue;	
									}	

								}
								
							}
							
						}
							
					}

				}
				else {

					foreach ($docField["values"] as $fieldValue) {

						$currFieldSplitAsArray = explode(":", $fieldValue);
						if(count($currFieldSplitAsArray) == 2 && !is_numeric($currFieldSplitAsArray[0]) && !empty($currFieldSplitAsArray[1])) {
							$bookDetailsValuePair[$key][] = array($currFieldSplitAsArray[0] => $currFieldSplitAsArray[1]);	
						}
						else {
							$bookDetailsValuePair[$key][] = $fieldValue;
						}	
					}
				}
			}
		}
	}

	//var_dump($bookDetailsValuePair);

	function removeWhiteSpace($str) {
		$str = preg_replace('/\s+/', ' ', $str);
		return trim($str);

	}

	echo "Booking number:" . removeWhiteSpace($bookDetailsValuePair["Booking number"]) . "\n";

	$tmp_array = $bookDetailsValuePair["Passenger details"];
	foreach ($tmp_array as $key => $value) {
		echo $key . ":" . removeWhiteSpace($value) . "\n";
	}
	//echo removeWhiteSpace($bookDetailsValuePair["Passenger details"]["Name"]) . "\n";
	//echo removeWhiteSpace($bookDetailsValuePair["Passenger details"]["Phone number"]) . "\n";
	//echo removeWhiteSpace($bookDetailsValuePair["Passenger details"]["Language"]) . "\n";
	//echo removeWhiteSpace($bookDetailsValuePair["Passenger details"]["People"]) . "\n";


	$tmp_array = $bookDetailsValuePair["Transfer information"];
	foreach ($tmp_array as $key => $value) {
		echo $key . ":" . removeWhiteSpace($value) . "\n";
	}

	/*echo removeWhiteSpace($bookDetailsValuePair["Transfer information"]["Pick-up"]) . "\n";
	echo removeWhiteSpace($bookDetailsValuePair["Transfer information"]["Pick-up time"]) . "\n";
	echo removeWhiteSpace($bookDetailsValuePair["Transfer information"]["Drop-off"]) . "\n";
	echo removeWhiteSpace($bookDetailsValuePair["Transfer information"]["Vehicle type"]) . "\n";
	if(isset($bookDetailsValuePair["Transfer information"]["Flight Nr"])) {
		echo removeWhiteSpace($bookDetailsValuePair["Transfer information"]["Flight Nr"]) . "\n";
	}*/

	if(isset($bookDetailsValuePair["Additional services"])) {
		$tmp_array = $bookDetailsValuePair["Additional services"][0];
		foreach ($tmp_array as $key => $value) {
			echo $key . ": " . removeWhiteSpace($value) . "\n";
		}
	}

	if($bookDetailsValuePair["Comments"] != NULL) {
		echo "Comments: " . "\n";
		$tmp_array = $bookDetailsValuePair["Comments"];
		foreach ($tmp_array as $key => $value) {
			echo removeWhiteSpace($value) . "\n";
		}
	}

	for($i = 0; $i < 3; $i++) {
		$tmp_array = $bookDetailsValuePair["Payment information"][$i];
		if(is_array($tmp_array)) {
			foreach ($tmp_array as $key => $value) {
				echo $key . ":" . removeWhiteSpace($value) . "\n";
			}	
		}
	}


?>
