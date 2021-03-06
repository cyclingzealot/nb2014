<a name="top"></a>

<?php
require_once("php_fast_cache.php");
phpFastCache::$storage = "auto";

$number = phpFastCache::get("entireJurisdictionResult");

if(!empty($number) && is_numeric($number) && $number > 5000) {
	echo "<h1>$number unrepresented votes (so far) in New Brunswick 2014 election</h1>";
} else {
	echo '<h1>How *MANY* unrepresented voters?</h1>';
}
?>
<table>

<tr><td bgcolor="#00AAB4" border="1"><a href="http://www.fairvote.ca"><img src="http://campaign2015.fairvote.ca/wp-content/themes/fairvotecanada/images/structure/unfair.png"></a></td>


<td>Hello,


<p>My name is <a href="https://www.linkedin.com/in/julienlamarche">Julien Lamarche</a>.  I am a volunteer for <a href="http://www.fairvote.ca">Fair Vote Canada</a>, an organization that does 
public education and lobbying to change the voting system so that more votes count.</p>

<p>Below is a compilation of the numbers taken from the 
<a href="http://www.gnb.ca/elections/results-resultats/2014-09-22/2014-09-22-results-e.asp">New Brunswick live election results</a>.  

<p>It sorts ridings by percentage of unrepresented votes.  An unrepresented vote is a vote that will not be represented in the legislature because it did not 
go to the winning candidate.  At the bottom you will also see <a href="#byParty">unrepresented votes by party</a>.</p>

<p>Winner-take-all systems (both first past the post and preferential ballot - ie Alternative Vote) have a high 
rate of wasted or unreprested votes.  But most democracies have moved on to some form of proportional voting system which greatly diminish the lack of 
representation (to about 5% of unrepresented votes). A proportional voting system also eliminates false government majorities.</p>

<p>If you wish to help:<ul>
<li>go <a href="http://secure.fairvote.ca/en/declaration">Sign the Declaration of Voters Rights</a></li>
<li>Retweet relevant <a href="https://twitter.com/cyclingzealot">@cyclingzealot</a>, <a href="https://twitter.com/search?q=%23wastedvotes">#wastedvotes</a> tweets during the nb2014 election</li>
<li>After the election, follow and retweet <a href="https://twitter.com/FairVoteCanada">@FairVoteCanada</a>, <a href="https://twitter.com/search?q=%23pr2015">#pr2015</a> and <a href="https://twitter.com/search?q=%23fairvote">#fairvote</a></li>
</ul>

More information is available on the website of <a href="http://www.fairvote.ca">Fair Vote Canada</a></p>

<p>For more information: 
<table border="1">
<tr><th>Position</th><th>Name & email</th></tr>
<tr><td>Fair Vote New Brunswick contact</td><td><a href="mailto:hdpnx@stu.ca">John Hoben</a></td></tr>
<tr><td>Contact francophone</td><td><a href="mailto:julien.lamarche@gmail.com">Julien Lamarche</a></td></tr>
<tr><td>National Executive Director</td><td><a href="mailto:office@fairvote.ca">Kelly Carmichael</a></td></tr>
<tr><td>Script author</td><td><a href="mailto:julien.lamarche@gmail.com">Julien Lamarche</td></tr>
</table>
</p>

</td></tr></table>


<!-- Scrapper for 2014 NB elections -->

<?php


$dateFormat = 'Y-m-d H:i:s';
$content = '';

$testing=FALSE;
if(isset($_GET["testing"]) && $_GET["testing"]=="yes")  $testing=TRUE;

$cacheReset=FALSE;
if(isset($_GET["cache"]) && $_GET["cache"]=="reset")  $cacheReset = TRUE;

$limit=107;

#if($testing)  $limit=3;

$outputFile='/tmp/results.csv';
$outputContent='';

$ridingsNames = array();
$resultsByRidingByParty = array();
$resultsByPartyByRiding = array();
$resultsByRidingSummary = array();
$wastedVotesByParty     = array();



### Check first caching

$content = phpFastCache::get("main");

if(($content !== null && strlen($content) > 500) && $cacheReset !== TRUE) {
	$content .= '<p><font size="-1">Cached results printed on ' . date($dateFormat) . '</font></p>';
	printf("%s", $content);
}
else {

### Get the riding list first and objects

#$url = 'http://www.gnb.ca/elections/results-resultats/2014-09-22/2014-09-22-resultshtml-e.asp';
$url = 'http://www.gnb.ca/elections/results-resultats/2014-09-22/2014-09-22-results-e.asp';

webCommentPrint('Getting page....');
$html = file_get_contents($url);

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($html);
libxml_use_internal_errors(false);
$xpath = new DOMXPath($doc);

### Lets get the riding names
$query = "/html/body/div/div[4]/div[5]/div[2]/div/h4";
$h4Nodes = $xpath->query($query);
$h4Count = $h4Nodes->length;

### Now the tables
$query = "/html/body/div/div[4]/div[5]/div[2]/div/table";
$tableNodes = $xpath->query($query);
$tableCount = $tableNodes->length;

webCommentPrint("\nI found $h4Count riding headers\n");
webCommentPrint("\nI found $tableCount tables\n");


for($i=0;  $i<$h4Count; $i++) {
 

	$district = $h4Nodes->item($i)->textContent;
	$district = trim(substr($district, strpos($district, ",") + 5));    

	webCommentPrint("Doing riding of $district\n");

	$ridingNames[$i] = $district;
	

	$tableNoNeeded = 2*$i+1;
	webCommentPrint("Getting table no $tableNoNeeded\n");
	$table = $tableNodes->item($tableNoNeeded);

	#echo get_class($tableNodes) . "\n";

	$rows = $table->getElementsByTagName('tr');


	$numRows = $rows->length;

    webCommentPrint("\nThere are $numRows rows\n");

	$j=0;
    
	webCommentPrint("Parsing results...");
	for($j=0; $j<$numRows; $j++) {



		if($j<=1)  continue;
		#webCommentPrint(\n\n";

		$row = $rows->item($j);

		#webCommentPrint(Content of row is:";
		#echo $row->textContent;
		#webCommentPrint(\n\n";

		$cells = $row->getElementsByTagName('td');

		#webCommentPrint(Cells is a:";
		#var_export($cells);
		#webCommentPrint(\n\n";


		#webCommentPrint(\n\n";
		#webCommentPrint(Cells length:";
		#var_export($cells->length);
		#webCommentPrint(\n\n";


		$partyColumnNo = 2;
		$partyNode = $cells->item($partyColumnNo);

		if(!is_object($partyNode))  die("partyNode is not an object\n");

		$party = $cells->item($partyColumnNo)->textContent;

		$votes = preg_replace("/[^0-9]/", "", trim($cells->item(3)->textContent));
		if($testing)  $votes = rand(50, 5000);

		webCommentPrint("Scrapped: $party\t$votes\n");


		$resultsByRidingByParty[$i][$party] = $votes; 
		$resultsByPartyByRiding[$party][$i] = $votes;

		#var_export($resultsByRidingByParty);
		#webCommentPrint(\n\n";
		#var_export($resultsByPartyByRiding);
		#webCommentPrint(\n\n";
	}

	$tableNoNeeded = 2*$i;
	#webCommentPrint(Getting table no $tableNoNeeded\n)";
	$table = $tableNodes->item($tableNoNeeded);

	### Getting partipation data....
	$rows = $table->getElementsByTagName('tr');
	$numRows = $rows->length;
    #webCommentPrint(\nThere are $numRows rows\n";
	$participationStr = $rows->item(0)->getElementsByTagName('td')->item(1)->textContent;
	$participationData = explode('/', $participationStr);


	$numVoters = preg_replace("/[^0-9]/", "", "$participationData[1]");

    webCommentPrint("Number of Voters: $numVoters\n");

	$totalVotes = preg_replace("/[^0-9]/", "", "$participationData[0]");

	$pRateTesting = 0;
	if($testing)  {
		$pRateTesting	= rand(0, 80)/100;
		$totalVotes		= round($pRateTesting * $numVoters);
	}

    webCommentPrint("Number of total votes: $totalVotes\n");

    $participationRate = $totalVotes / $numVoters;
    if($testing)  $participationRate = $pRateTesting;
    webCommentPrint("Participation rate: $participationRate\n");


    #$resultsByRidingSummary[$ridingID]['wastedVotes'] = preg_replace("/[^0-9]/", "", "$numVoters");
    $resultsByRidingSummary[$i]['numVoters']    = preg_replace("/[^0-9]/", "", "$numVoters");
    $resultsByRidingSummary[$i]['totalVotes']   = preg_replace("/[^0-9]/", "", "$totalVotes");
    $resultsByRidingSummary[$i]['pRate']        = $participationRate;

	/*
	echo '<pre>';
	var_export($resultsByRidingSummary[$i]);
	echo '</pre>';
	*/
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

$entireJurisdiction = array('wastedVotes' => 0, 'totalValidVotes'=>0, 'numVoters'=>0, 'totalVotes'=>0);

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

			if(!isset($wastedVotesByParty[$partyName]))  
				{$wastedVotesByParty[$partyName] = 0;}
			$wastedVotesByParty[$partyName] += $votes;

            $wastedVotes = $totalValidVotes - $winnerVotes;
            $resultsByRidingSummary[$ridingID]['wastedVotes']       = $wastedVotes;
			if($totalValidVotes > 0) {
            	$resultsByRidingSummary[$ridingID]['wastedVotesPct']    = $wastedVotes/$totalValidVotes;
			} else  {
				$resultsByRidingSummary[$ridingID]['wastedVotesPct']	= 0;
			}



		}
		$outputContent .= $votes . "\t";

	}

	// Now subtract from wasted votes by party the votes of the winner
	$wastedVotesByParty[$winningParty] -= $winnerVotes;


    #webCommentPrint(For $ridingName, the winning party was $winningParty with $winnerVotes.  Among the $totalValidVotes votes, there were $wastedVotes wasted votes " . round($wastedVotes/$totalValidVotes*100) . "%\n";
	if($totalValidVotes>0) {
	    webCommentPrint("For $ridingName, the winning party was $winningParty with $winnerVotes.  Among the $totalValidVotes votes, there were $wastedVotes wasted votes " . round($wastedVotes/$totalValidVotes*99) . "%");
	} else {
		 webCommentPrint("For $ridingName, no votes have been counted yet");
	}


    ### Write row by data
    #$outputContent .= "$wastedVotes\t" . round($resultsByRidingSummary[$ridingID]['wastedVotesPct']*100,2)  .  "\t"  . round($resultsByRidingSummary[$ridingID]['pRate']*100,2)   ;
	#$outputContent .= "\n";

	$entireJurisdiction['wastedVotes']		+= $wastedVotes;
	$entireJurisdiction['totalValidVotes']	+= $totalValidVotes;
	$entireJurisdiction['totalVotes']		+= $resultsByRidingSummary[$ridingID]['totalVotes'];
	$entireJurisdiction['numVoters']		+= $resultsByRidingSummary[$ridingID]['numVoters'];

	$entireJurisdiction['wastedVotesPct']    = $entireJurisdiction['totalValidVotes'] > 0 ? $entireJurisdiction['wastedVotes'] / $entireJurisdiction['totalValidVotes'] : 0;
	$entireJurisdiction['pRate']		     = $entireJurisdiction['totalVotes'] / $entireJurisdiction['numVoters'];


	/*
	echo '<pre>';
	echo "Results by riding\n";
	var_export($resultsByRidingSummary[$ridingID]);
	echo "\nEntire Jrdt\n";
	var_export($entireJurisdiction);
	echo '</pre>';
	*/
	
}

#var_export(array_keys($resultsByRidingSummary));




#echo $outputContent;

#file_put_contents($outputFile, $outputContent);


### More WVA
$resultsByPartySummary  = array();

uasort($resultsByRidingSummary, function ($a, $b) {
    #var_export($b); 
    return ($b['wastedVotesPct'] - $a['wastedVotesPct'])*100;
});   

$nonListed = '';

$content .= '<p><table width="100%" border="1"><tr><th>Riding name</th><th>Unrepresented votes %</th><th>Unrepresented votes</th><th>Participation rate % (so far) </th></tr>';
foreach($resultsByRidingSummary as $ridingID => $ridingNumbers) {
    #The participation rate threshold for which we will calculate the unrepresented votes.
    # 0.12 is the third of the partipation rate for the riding the lowest turnout (Fort McMurray) of the election with the lowest turnout (2008)
    $pRateThreshold = .12;

    if($ridingNumbers['pRate'] > $pRateThreshold) {
        $content .= sprintf("<tr><td>%s</td><td>%.2f %%</td><td>%d</td><td>%.0f %%</td></tr>", $ridingNames[$ridingID], $ridingNumbers['wastedVotesPct']*100, $ridingNumbers['wastedVotes'], $ridingNumbers['pRate']*100);
    } else {
		$nonListed .= sprintf("%s (%d %%), ", $ridingNames[$ridingID], $ridingNumbers['pRate']*100);
	}
}
$content .= sprintf("<tr style=\"font-weight: bold;\"><td>%s</td><td>%.2f %%</td><td>%d*</td><td>%.0f %%</td></tr>", "New Brunswick", $entireJurisdiction['wastedVotesPct']*100, $entireJurisdiction['wastedVotes'], $entireJurisdiction['pRate']*100);
$content .= "</table></p>";

// Print unrepresented votes by party
arsort($wastedVotesByParty);
$content .= "<p><a name=\"byParty\">Unrepresented votes by party:</a><ol>";
foreach($wastedVotesByParty as $partyName => $partyVotes) {
	$content .= "<li>$partyName: $partyVotes</li>\n";;;;
}
$content .= "</ol></p>";
$content .= '<a href="#top">Top</a>';

if(!empty($nonListed)) {
	$content .= "<p>The following ridings are not listed because they don't have enough votes counted yet: $nonListed</p>";
}

$content .= '<hr /><p><font size="-1">Results as of ' . date($dateFormat) . '</font></p>';

phpFastCache::set("main", $content, 30);
$content .= '<p><font size="-1">Non-cached results printed on ' . date($dateFormat) . '</font></p>';

#$content .= '<pre>';
#$content .= 'Values for entire jurisdiction: ';
#$content .= var_export($entireJurisdiction, TRUE);
#$content .= '</pre>';


echo $content;

if(!empty($entireJurisdiction['totalValidVotes']) && $entireJurisdiction['totalValidVotes'] > 0) {
	$number = $entireJurisdiction['wastedVotes'];
	phpFastCache::set("entireJurisdictionResult", $number, 60*60*24*365);
} else {
	phpFastCache::set("entireJurisdictionResult", -1, 1);
}



webCommentPrint("List of parties are:\n");

sort($listOfParties);
foreach($listOfParties as $partyName) {
	webCommentPrint("$partyName\n");
}

### End of else for non-cached, fresh results
}



function webCommentPrint($comment) {
	$comment = rtrim($comment);
	echo "<!-- $comment -->\n";
}



?>


<p><font size="-1">Code available on <a href="https://github.com/cyclingzealot/nb2014">Github</a></font></p>
<p><font size="-1">*For practicallity, the headline displays the number calculated at the previous analysis</p>
