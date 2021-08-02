<?php 

/**
 *
 * View Hoses Page
 * load via INCLUDE at page load.
 *
 */
 
 if(count(get_included_files()) ==1){
 	 http_response_code(403);
 	 exit("ERROR 403: Direct access not permitted.");
}

require_once("config.php");

?>

<h2> Your Hoses </h2>

<?php echo loadHoses(); ?>









<?php 
 // functions for loading and displaying hoses that exist for this company. 
 

function loadHoses(){
	$hose = new Hose();
	
	$user = new user();
	$user->LoadUserByName($_SESSION['USER']);
	
	// get LIMIT and OFFSET
	$output = '';
	if(isset($_GET['perpage']) && is_numeric($_GET['perpage'])){
		$limit = $_GET['perpage'];
	} else {
		$limit = 5;
	}
	if(isset($_GET['page']) && is_numeric($_GET['page'])){
		// there is a page number, -1 and mult by 10 for offset. 
		
		$offset = ($_GET['page'] -1) * $limit;
		
	} else {
		$offset = 0;
	}
	
	$ids = $hose->GetHosesForOwner($user->CompanyID, $limit, $offset); // get limited count for ids
	$ordercount = count($hose->GetHosesForOwner($user->CompanyID)); // get all ids count. 
	
	// create page buttons
	$output .= pageFilter($ordercount, $limit, $offset);
	
	foreach($ids as $id){
		$hoseData = $hose->GetHoseData($id);
		
		$output .= '<div class="HistoryLine">
						<div class="HistoryHose">' . $hoseData['Name'] . ($hoseData['PartNumber'] != '' ? ' ( ' . $hoseData['PartNumber'] . ')</div>' : '</div>') . '
						<div class="HistoryOwner"><p>' . $hoseData['CouplingA']['Name'] . ' (' . $hoseData['CouplingA']['AttachMethod'] . ') X ' . $hoseData['CouplingB']['Name'] . ' (' . $hoseData['CouplingB']['AttachMethod'] . ')</p><span>Asset #: ' . $hoseData['AssetNumber'] . '</div>';

		// place the hose image.
		$himg = $hose->GetHoseImage($hoseData['TemplatePK']);

		if($himg != ''){
			$output .= '<img src="' . $himg . '" style="width: 300px;" />';
		}
		
		// output all tests				
		$testid = $hose->GetTestsForHose($id);
		
		foreach($testid as $t){
			// output info for each test done. 
			
			$testData = $hose->GetTestData($t);
			
			$output .= '<div class="HistoryResult"><p>Test #: ' . $testData['TestNumber'] . '</p><p>Testing Date: ' . $hose->DaypochToDate($testData['Date']) . '</p><span class="res' . $testData['TestResult'] . '">' . $testData['TestResult'] . '</span><a href="pdf-gen.php?id='. $t .'" target="_blank">View Cert</a></div>';
		}
		
						
		$output .= '</div>';
		
	}
	
	$output .= pageButtons($ordercount, $limit, $offset);
	
	return $output;
	
	
}


/**  OLD CONTENT HERE ONLY
 *
 *


<?php

function loadTestHistory(){
	// load the last order entered. Since this page is a one-off, 
	// it will always show the last order entered by the user. 
	
	
	
	foreach
		$test = $ht->getTestByID($id);
		$hose = $ht->getHoseByID($test['HosePK']);
		$owner = $ht->getOwnerName($test['OwnerPK']);
		
		$ddate = explode(' ', $test['Date']);
		
		$date  = explode('-', $ddate[0]);
		
		
	}
	
	
				
	echo $output;			
	
}
*/
function pageButtons($ordercount, $limit, $offset){
	// take the total orders, divide by limit for total pages. round up. 
	$pages = ceil($ordercount / $limit); 
	
	$output = '<div class="historycontrol">
				<div class="historycontrolbuttons">';
	// for each page, output a button that links back to page with a set limit and offset. 
	// back and forward buttons are set with +limit and -limit to current values, unless on the first or last page. 
	// input area allows user to set number of results per page. 
	$own = '';
	
	if($offset >= $limit){
		$output .= '<a href="index.php?p=hoses&perpage=' . $limit . '&page=' . (ceil($offset/$limit)) . $own . '">Prev</a> ';
	}
	
	if(isset($_GET['page'])){
		$currentpage = $_GET['page'] - 1;
	} else {
		$currentpage = -1;
	}
	
	if($currentpage - 2 < 0){
		$minpage = 0;
	} else {
		$minpage = $currentpage - 2;
	}
	
	if($currentpage + 2 >= $pages){
		$maxpage = $pages;
	} else {
		$maxpage = $currentpage + 3;
	}
	
	for($i = $minpage; $i < $maxpage; $i++){
		
		$output .= '<a ' . ($currentpage == $i ? 'class="activepage" ' : '') . 'href="index.php?p=hoses&perpage=' . $limit . '&page=' . ($i+1) . $own . '">' . $i . '</a> ';
		
	}
	if($offset < $ordercount - $limit){
		$output .= '<a href="index.php?p=hoses&perpage=' . $limit . '&page=' . (ceil($offset/$limit) + 2) . $own . '">Next</a> ';
	}
	
	
	
	$output .= '</div>
				</div>';
	return $output;
}

function pageFilter($ordercount, $limit, $offset){
	$output = '<form method="get" action="index.php" class="ordersperpage">
					<label for="perpage">Per Page</label>
					<input type="text" name="perpage" value="' . $limit . '" />
					<input type="hidden" name="page" value="' . ($offset / $limit + 1) . '" />
					<input type="hidden" name="p" value="hoses" />
					<input type="submit" value="Update" />
				</form>';
	
	return $output;
}

