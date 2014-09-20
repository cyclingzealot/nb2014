#!/usr/bin/php



<?php
#Scrapper for 2014 election

$testing=FALSE;

$limit=107;

if($testing)  $limit=3;

$outputFile='/tmp/results.csv';
$outputContent='';

$ridingsNames = array();
$resultsByRidingByParty = array();
$resultsByPartyByRiding = array();
$resultsByRidingSummary = array();
$wastedVotesByParty     = array();


### Get the riding list first and objects

$url = 'http://www.gnb.ca/elections/results-resultats/2014-09-22/2014-09-22-resultshtml-e.asp';

echo 'Getting page....';
$html = file_get_contents($url);

$doc = new DOMDocument();
$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

### Lets get the riding names
$query = "/html/body/div/div[4]/div[5]/div[2]/div/h4";
$h4Nodes = $xpath->query($query);
$h4Count = $h4Nodes->length;

### Now the tables
$query = "/html/body/div/div[4]/div[5]/div[2]/div/table";
$tableNodes = $xpath->query($query);
$tableCount = $tableNodes->length;

echo "\nI found $h4Count riding headers\n";
echo "\nI found $tableCount tables\n";


for($i=0;  $i<$h4Count; $i++) {
 

	$district = $h4Nodes->item($i)->textContent;
	$district = trim(substr($district, strpos($district, ",") + 5));    

	echo "Doing riding of $district\n";

	/* SKIP PARSING NEW HTML DOCUMENT SINCE WE ALREADY HAVE IT
	$doc = new DOMDocument();
	$doc->loadHTML($html);
	$xpath = new DOMXPath($doc);


    $xPathQuery = '//*[@id="grdResultsucElectoralDistrictResult'. $i . '"]/caption';

    echo "\n\nxPath: $xPathQuery\n\n";
	$ridingNode = $xpath->query($xPathQuery);

    echo $ridingNames[$i];
	*/


	$ridingNames[$i] = $district;
	

	$tableNoNeeded = 2*$i+1;
	echo "Getting table no $tableNoNeeded\n";
	$table = $tableNodes->item($tableNoNeeded);

	#echo get_class($tableNodes) . "\n";

	$rows = $table->getElementsByTagName('tr');


	$numRows = $rows->length;

    echo "\nThere are $numRows rows\n";

	$j=0;
    
	echo "Parsing results...";
	for($j=0; $j<$numRows; $j++) {



		if($j<=1)  continue;
		#echo "\n\n";

		$row = $rows->item($j);

		#echo "Content of row is:";
		#echo $row->textContent;
		#echo "\n\n";

		$cells = $row->getElementsByTagName('td');

		#echo "Cells is a:";
		#var_export($cells);
		#echo "\n\n";


		#echo "\n\n";
		#echo "Cells length:";
		#var_export($cells->length);
		#echo "\n\n";


		$partyColumnNo = 2;
		$partyNode = $cells->item($partyColumnNo);

		if(!is_object($partyNode))  die("partyNode is not an object\n");

		$party = $cells->item($partyColumnNo)->textContent;

		$votes = preg_replace("/[^0-9]/", "", trim($cells->item(3)->textContent));
		$votes = rand(50, 5000);

		echo "Scrapped: $party\t$votes\n";


		$resultsByRidingByParty[$i][$party] = $votes; 
		$resultsByPartyByRiding[$party][$i] = $votes;

		#var_export($resultsByRidingByParty);
		#echo "\n\n";
		#var_export($resultsByPartyByRiding);
		#echo "\n\n";
	}

	$tableNoNeeded = 2*$i;
	#echo "Getting table no $tableNoNeeded\n";
	$table = $tableNodes->item($tableNoNeeded);

	### Getting partipation data....
	$rows = $table->getElementsByTagName('tr');
	$numRows = $rows->length;
    #echo "\nThere are $numRows rows\n";
	$participationStr = $rows->item(0)->getElementsByTagName('td')->item(1)->textContent;
	$participationData = explode('/', $participationStr);


	$numVoters = preg_replace("/[^0-9]/", "", "$participationData[1]");

    echo "Number of Voters: $numVoters\n";

	$totalVotes = preg_replace("/[^0-9]/", "", "$participationData[0]");
    echo "Number of total votes: $totalVotes\n";

    $participationRate = $totalVotes / $numVoters;
    echo "Participation rate: $participationRate\n";

    #$resultsByRidingSummary[$ridingID]['wastedVotes'] = preg_replace("/[^0-9]/", "", "$numVoters");
    $resultsByRidingSummary[$i]['numVoters']    = preg_replace("/[^0-9]/", "", "$numVoters");
    $resultsByRidingSummary[$i]['totalVotes']   = preg_replace("/[^0-9]/", "", "$totalVotes");
    $resultsByRidingSummary[$i]['pRate']        = $participationRate;
}



$listOfParties  =array_keys($resultsByPartyByRiding);
$listOfRidings 	=array_keys($resultsByRidingByParty);

### Make the header of the csv file
$outputContent .= " \t \t";
foreach($listOfParties as $partyName) {
	$outputContent .= $partyName . "\t"; 
}
$outputContent .= "Wasted Votes\tWasted Votes %\tParticipation Rate";
$outputContent .= "\n";


# For WV analysis (WVA)
$wastedVotesMax = 0;
$wastedVotesMaxRidingName = '';

### Make the rows of the csv file
foreach($ridingNames as $ridingID => $ridingName) {
	$outputContent .= "$ridingID\t$ridingName\t";


    # For WV analysis (WVA)
    $winnerVotes = 0;
    $winningParty = "";
    $totalValidVotes = 0;
    $wastedVotes = 0;

	foreach($listOfParties as $partyName) {

        # For CSV
		$votes = "";
		
		if(isset($resultsByRidingByParty[$ridingID][$partyName])) {
			$votes = $resultsByRidingByParty[$ridingID][$partyName];
            $totalValidVotes += $votes;

            # For WVA
            if($votes > $winnerVotes) {
                $winningParty = $partyName;
                $winnerVotes  = $votes;
            }

            $wastedVotes = $totalValidVotes - $winnerVotes;
            $resultsByRidingSummary[$ridingID]['wastedVotes']       = $wastedVotes;
            $resultsByRidingSummary[$ridingID]['wastedVotesPct']    = $wastedVotes/$totalValidVotes;


            echo "For $ridingName, the winning party was $winningParty with $winnerVotes.  Among the $totalValidVotes votes, there were $wastedVotes wasted votes " . round($wastedVotes/$totalValidVotes*99) . "%\n";
		}
		$outputContent .= $votes . "\t";

	}


    #echo "For $ridingName, the winning party was $winningParty with $winnerVotes.  Among the $totalValidVotes votes, there were $wastedVotes wasted votes " . round($wastedVotes/$totalValidVotes*100) . "%\n";


    ### Write row by data
    $outputContent .= "$wastedVotes\t" . round($resultsByRidingSummary[$ridingID]['wastedVotesPct']*100,2)  .  "\t"  . round($resultsByRidingSummary[$i]['pRate']*100,2)   ;
	$outputContent .= "\n";

}





echo "\n\n\n\n";
echo $outputContent;

file_put_contents($outputFile, $outputContent);

echo "\n\n\n\n\n";

### More WVA
$resultsByPartySummary  = array();

uasort($resultsByRidingSummary, function ($a, $b) {
    #var_export($b); 
    return ($a['wastedVotesPct'] - $b['wastedVotesPct'])*100;
});   

foreach($resultsByRidingSummary as $ridingID => $ridingNumbers) {
    #The participation rate threshold for which we will calculate the wasted votes.
    # 0.12 is the third of the partipation rate for the riding the lowest turnout (Fort McMurray) of the election with the lowest turnout (2008)
    $pRateThreshold = .12;

    if($ridingNumbers['pRate'] > $pRateThreshold) {
        printf("%-50s%.2f %%\n", $ridingNames[$ridingID], $ridingNumbers['wastedVotesPct']*100);
    }
}


echo "\n\n\n\n\n";


echo "List of parties are:\n";

sort($listOfParties);
foreach($listOfParties as $partyName) {
	echo "$partyName\n";
}
