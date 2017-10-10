<?php
//error_reporting(E_ALL);
	header('Access-Control-Allow-Origin: *');

	include("includes/util.php");
	require_once 'includes/phpdocx/Classes/Phpdocx/Create/CreateDocx.inc';
	date_default_timezone_set("America/Los_Angeles");
	
	$tourdateid = intval($_REQUEST["tourdateid"]);
	$profileid = intval($_REQUEST["pid"]);
	
	if(!($tourdateid > 0 && $profileid > 0))
		exit();
	
	$citydata = get_citydata($tourdateid);

	$seasonid = $citydata["seasonid"];
	$seasondir = db_one("year1","tbl_seasons","id='$seasonid'").db_one("year2","tbl_seasons","id='$seasonid'");
	$safecity = strtolower(str_replace(array(" ",",",".","-"),"",$citydata["city"]));
	$eventname = strtolower(str_replace(" ","",db_one("name","events","id='".$citydata["eventid"]."'")));
	
	$dancerdata = get_dancerdata($tourdateid,$profileid,$seasonid);
	$competition_awards = get_competition_awards($tourdateid);

	$dir = "/home/pdf/html/includes/phpdocx/templates/btfp/$eventname/$seasondir/";
	$docx = new Phpdocx\Create\CreateDocxFromTemplate($dir.$safecity.'.docx');
	$docx->enableCompatibilityMode();
	
	/* DANCER & CITY INFORMATION */
	$variables = array(
	    'p1_fname'  => $dancerdata["fname"],
	    'p1_date'	=> strtoupper($citydata["dispdate"]),
	    'p1_city'	=> strtoupper($citydata["city"]),
	    'p1_fname2'	=> strtoupper($dancerdata["fname"]),
	    'p1_fname3'	=> $dancerdata["fname"],
	    'p1_lname'	=> $dancerdata["lname"],
	    'p1_studioname'	=> $dancerdata["studioname"],
	    'p1_birthdate'	=> $dancerdata["birth_date"],
	    'p1_age'	=> $dancerdata["age"],
	    'p1_studiocode'	=> strlen($dancerdata["studiocode"]) > 0 ? $dancerdata["studiocode"] : "N/A",
	    'p1_workshoplevel'	=> $dancerdata["workshoplevel"],
	    'p1_auditionroom'	=> $dancerdata["audroom"],
	    'info_headdate'		=> $citydata["dispdate"],
	    'p2_fname'			=> $dancerdata["workshoplevel"] == "Teacher" ? "YOUR STUDIO" : strtoupper($dancerdata["fname"]),
	    'workshop_city'		=> $citydata["city"],
	    'workshop_venue'	=> $citydata["venue_name"] 
	);
	$docx->replaceVariableByText($variables);

	// HOST HOTEL & VENUE
	if($citydata["no_hotel"] == "1")
		$docx->replaceVariableByHTML('p1_hotelinfo','inline','<span style="font-family:Arial;font-size:11px;">There is no host hotel for this tour city.</span>');
	else
		$docx->replaceVariableByHTML('p1_hotelinfo','inline','<span style="font-family:Arial;font-size:11px;">'.$citydata["hotel_name"].'<br/>'.$citydata["hotel_address"].'<br/>'.$citydata["hotel_city"].', '.$citydata["venue_state"].' '.$citydata["hotel_zip"].'</span>');
	
	$docx->replaceVariableByHTML('p1_venueinfo','inline','<span style="font-family:Arial;font-size:11px;">'.$citydata["venue_name"].'<br/>'.$citydata["venue_address"].'<br/>'.$citydata["venue_city"].', '.$citydata["venue_state"].' '.$citydata["venue_zip"].'</span>');
	
	
	/* COMPETITION SCHEDULE */
	if(count($dancerdata["routines"]) > 0) {
		$data = array();
		foreach($dancerdata["routines"] as $routine) {
			$data[] = array(
				'routine_name' => $routine["dispname"],
				'routine_day'  => date('l',$routine["finals_time"]),
				'routine_time' => $routine["disptime"],
				'routine_room' => $routine["room"],
				'routine_number' => '#'.$routine["number_finals"]
			);
		}
	} 
	else {
		$data[] = array(
			'routine_name' => 'You are not registered for competition.',
			'routine_day'  => '-',
			'routine_time' => '-',
			'routine_room' => '-',
			'routine_number' => '-'
		);
	}
	$docx->replaceTableVariable($data);
	
	/* COMPETITION AWARDS */
	$docx->replaceVariableByHTML('competition_awards','inline','<span style="font-family:Open Sans;font-size:13px;">'.get_competition_awards($tourdateid).'</span>');
	
	/* REMOVE EXCESS / UNUSED PLACEHOLDERS */
	$docx->clearBlocks();
	
	/* REMOVE EXISTING PDF */
	$outputFileBase = strtolower(str_replace(array('_',' ','\'','"','-',',','.','&'),"",$dancerdata["fname"]."_".$dancerdata["lname"])."_".$safecity);
	if(file_exists('output/personal_pdf/'.$outputFileBase.'.pdf')) {
		unlink('output/personal_pdf/'.$outputFileBase.'.pdf');
	}
	
	/* SAVE DOCX */
	$docx->createDocx('output/personal_pdf/'.$outputFileBase);
	
	/* CONVERT DOCX TO PDF */
	$docx->transformDocument('output/personal_pdf/'.$outputFileBase.'.docx' , 'output/personal_pdf/'.$outputFileBase.'.pdf');

	/* REMOVE GENERATED DOCX */
	unlink('output/personal_pdf/'.$outputFileBase.'.docx');
	
	/* OPEN IF REQUESTED */
	if(isset($_REQUEST["open"])) {
		header("Cache-Control: public");
    	header("Content-Description: File Transfer");
	    header("Content-Disposition: attachment; filename=".basename($outputFileBase.'.pdf'));
    	header("Content-Type: application/octet-stream");
	    header("Content-Transfer-Encoding: binary");
		readfile('output/personal_pdf/'.$outputFileBase.'.pdf');
	} else {
		print($outputFileBase.'.pdf');
	}
	
	exit();
?>
