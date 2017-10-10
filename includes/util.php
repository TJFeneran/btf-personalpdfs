<?php

	
	include("db.php");
	
	$mysql=mysql_connect(constant("db_host"),constant("db_username"),constant("db_password"));
	if(!$mysql) { die("Error: ".mysql_error()); }
		$db = mysql_select_db(constant("db_database"),$mysql);
	if(!$db) {	die("Error: ".mysql_error()); }	
	
	
	//useful get one
	function db_one($field,$table,$where = "",$limit = 1) {
		if(strlen($where)>0)
			$where = "WHERE ".$where;
		$base_sql = "SELECT $field FROM $table $where LIMIT $limit";
		$res = mysql_query($base_sql) or die(mysql_error());
		while(list($afield) = mysql_fetch_row($res))
			return $afield;
	}
	
	//get all city data
	function get_citydata($tourdateid) {
		$citydata = Array();
		$sql = "SELECT * FROM `tbl_tour_dates` WHERE id=$tourdateid";
		$res = mysql_query($sql) or die(mysql_error());
		if(mysql_num_rows($res) > 0) {
			while($row = mysql_fetch_assoc($res)) {
				$row["venue_state"] = db_one("abbreviation","tbl_states","id=".$row["stateid"]);
				$row["dispdate"] = get_tourdate_dispdate($tourdateid);
				$citydata = $row;
			}
		}
		return $citydata;
	}
	
	//get all dancer data
	function get_dancerdata($tourdateid,$profileid,$seasonid) {
		//get dancer info
		$sql = "SELECT tbl_date_dancers.id AS datedancerid, tbl_date_dancers.one_day, tbl_date_dancers.studioid, tbl_profiles.birth_date, tbl_profiles.id AS profileid, tbl_profiles.fname, tbl_profiles.lname, tbl_date_dancers.age, tbl_date_dancers.workshoplevelid, tbl_workshop_levels_$seasonid.name AS workshoplevel, tbl_date_dancers.studioid, tbl_studios.name AS studioname FROM `tbl_date_dancers` LEFT JOIN tbl_studios ON tbl_studios.id=tbl_date_dancers.studioid LEFT JOIN tbl_profiles ON tbl_profiles.id=tbl_date_dancers.profileid LEFT JOIN tbl_workshop_levels_$seasonid ON tbl_workshop_levels_$seasonid.id=tbl_date_dancers.workshoplevelid WHERE tbl_date_dancers.tourdateid=$tourdateid AND tbl_date_dancers.profileid=$profileid";			
		$res = mysql_query($sql) or die(mysql_error());
		if(mysql_num_rows($res) > 0) {
			while($row = mysql_fetch_assoc($res)) {
				
				//audition room = mandatory based on age
				$dage = intval($row["age"]);
				$row["age"] = $dage > 0 ? $dage : "N/A";
				$row["studioname"] = stripslashes(str_replace("&amp;","&",str_replace("&#44;",",",$row["studioname"])));
				$row["audroom"] = "N/A";
				if($row["workshoplevel"] == "JUMPstart" || $row["workshoplevel"] == "Sidekick" || $row["workshoplevel"] == "Nubie" || $row["workshoplevel"] == "Rookie" || $row["workshoplevel"] == "PeeWee") {
					$row["audroom"] = "-";
				}
				else {
					if($dage > 0 && $row["workshoplevel"] != "Teacher" && $row["one_day"] < 1) {
						if($dage > 6 && $dage < 11)
							$row["audroom"] = "Mini";
						if($dage == 11 || $dage == 12)
							$row["audroom"] = "Junior";
						if($dage > 12 && $dage < 16)
							$row["audroom"] = "Teen";
						if($dage > 15 && $dage < 22)
							$row["audroom"] = "Senior";
					}
				}
				$row["birth_date"] = strlen($row["birth_date"]) > 0 ? $row["birth_date"] : "N/A";
				$row["studiocode"] = db_one("studiocode","tbl_date_studios","studioid=".$row["studioid"]." AND tourdateid=$tourdateid");
				
				$routines = Array();
				
				if($row["workshoplevel"] == "Teacher") {
					
					$sql3 = "SELECT tbl_age_divisions.name AS agedivisionname, tbl_date_routines.number_finals, tbl_routines.name AS routinename, tbl_date_routines.finals_has_a, tbl_date_routines.finals, tbl_routine_categories_$seasonid.name AS routinecategoryname, tbl_performance_divisions.name AS perfdivisionname, tbl_date_routines.finals_time, tbl_date_routines.room_finals AS room FROM `tbl_date_routines` LEFT JOIN tbl_age_divisions ON tbl_age_divisions.id=tbl_date_routines.agedivisionid LEFT JOIN tbl_routines ON tbl_routines.id=tbl_date_routines.routineid LEFT JOIN tbl_routine_categories_$seasonid ON tbl_routine_categories_$seasonid.id=tbl_date_routines.routinecategoryid LEFT JOIN tbl_performance_divisions ON tbl_performance_divisions.id=tbl_date_routines.perfcategoryid WHERE tbl_date_routines.tourdateid=$tourdateid AND tbl_date_routines.studioid=".$row["studioid"]." AND tbl_date_routines.number_finals != 0 ORDER BY tbl_date_routines.finals_time ASC";
	
					$res3 = mysql_query($sql3) or die(mysql_error());
	
					if(mysql_num_rows($res3) > 0) {
						while($row3 = mysql_fetch_assoc($res3)) {
							
							$row3["date"] = date('l, F jS',$row3["finals_time"]);
							list($hh,$mm,$ss,$mo,$dd,$yy) = explode(":",date('G:i:s:m:d:Y',$row3["finals_time"]));
							$row3["disptime"] = strtoupper(date('g:i a',mktime(($hh),$mm,$ss,$mo,$dd,$yy)));
							
							if($row3["finals_has_a"] == "1")
								$dispnum = $row3["number_finals"].".a";
							else
								$dispnum = $row3["number_finals"];
							$row3["number_finals"] = $dispnum;
							
							$disprname = stripslashes(str_replace("&amp;","&",str_replace("&#44;",",",$row3["routinename"])));
							if($row3["room"] == 2)
								$disprname = stripslashes(str_replace("&amp;","&",str_replace("&#44;",",",$row3["routinename"])))." - ROOM 2";
							$row3["dispname"] = $disprname;
							
							$routines[$row3["finals_time"]] = $row3;
						}
					}
				}
				else {			
					$sql2 = "SELECT tbl_date_routine_dancers.routineid FROM `tbl_date_routine_dancers` WHERE tbl_date_routine_dancers.profileid=$profileid AND tbl_date_routine_dancers.tourdateid=$tourdateid";
					$res2 = mysql_query($sql2) or die(mysql_error());
					if(mysql_num_rows($res2) > 0) {
						while($row2 = mysql_fetch_assoc($res2)) {
							$sql4 = "SELECT tbl_age_divisions.name AS agedivisionname, tbl_date_routines.number_finals, tbl_routines.name AS routinename, tbl_date_routines.finals_has_a, tbl_date_routines.finals, tbl_date_routines.room_finals AS room, tbl_routine_categories_$seasonid.name AS routinecategoryname, tbl_performance_divisions.name AS perfdivisionname, tbl_date_routines.finals_time FROM `tbl_date_routines` LEFT JOIN tbl_age_divisions ON tbl_age_divisions.id=tbl_date_routines.agedivisionid LEFT JOIN tbl_routines ON tbl_routines.id=tbl_date_routines.routineid LEFT JOIN tbl_routine_categories_$seasonid ON tbl_routine_categories_$seasonid.id=tbl_date_routines.routinecategoryid LEFT JOIN tbl_performance_divisions ON tbl_performance_divisions.id=tbl_date_routines.perfcategoryid WHERE tbl_date_routines.tourdateid=$tourdateid AND tbl_date_routines.routineid=".$row2["routineid"]." AND tbl_date_routines.number_finals != 0 ORDER BY tbl_date_routines.finals_time ASC";
							$res4 = mysql_query($sql4) or die(mysql_error());
							if(mysql_num_rows($res4) > 0) {
								while($row4 = mysql_fetch_assoc($res4)) {
									
									$row4["date"] = date('l, F jS',$row4["finals_time"]);
									list($hh,$mm,$ss,$mo,$dd,$yy) = explode(":",date('G:i:s:m:d:Y',$row4["finals_time"]));
									$row4["disptime"] = strtoupper(date('g:i a',mktime(($hh),$mm,$ss,$mo,$dd,$yy)));									
									
									if($row4["finals_has_a"] == "1")
										$dispnum = $row4["number_finals"].".a";
									else
										$dispnum = $row4["number_finals"];
									$row4["number_finals"] = $dispnum;
									
									
									$disprname = stripslashes(str_replace("&amp;","&",str_replace("&#44;",",",$row4["routinename"])));
									if($row4["room"] == 2)
										$disprname = stripslashes(str_replace("&amp;","&",str_replace("&#44;",",",$row4["routinename"])))." - ROOM 2";
									$row4["dispname"] = $disprname;
									
									$routines[$row4["finals_time"]] = $row4;
								}
							}	
						}
					}
				}
				
				$aroutines = array();
				ksort($routines,SORT_NUMERIC);
				$aroutines = $routines;
				$routines = array();
				foreach($aroutines as $aroutine)
					$routines[] = $aroutine;
				$row["routines"] = $routines;

				return $row;
			}
		}
	}

	function get_competition_awards($tourdateid) {
		//get awards times
		$awards = Array();
		$awardstext = "";
		$sql = "SELECT awards FROM `tbl_date_schedule_competition` WHERE tourdateid=$tourdateid";
		$res = mysql_query($sql) or die(mysql_error());
		if(mysql_num_rows($res) > 0) {
			while($row = mysql_fetch_assoc($res)) {
				$awardsa = json_decode($row["awards"],true);
				$awards = $awardsa["finals"];
			}
			if(count($awards) > 0) {
				foreach($awards as $award) {
					$atime = ""; 
					//do awards times.  DATEROUTINES'S TIME PLUS DATEROUTINE'S DURATION
					list($rhh,$rii,$rss,$rmm,$rdd,$ryy) = explode("-",date("G-i-s-m-d-Y",db_one("finals_time","tbl_date_routines","id='".$award["dateroutineid"]."'")));
					$atime = strtoupper(date("g:ia",mktime($rhh,$rii+intval(db_one("duration","tbl_date_routines","id='".$award["dateroutineid"]."'")),$rss,$rdd,$rmm,$ryy)));				
					$awardstext .= $award["date"]." -- $atime -- ".$award["desc"]."<br/>";
				}
			}
		}
		return $awardstext;	
	}
	
	//returns nicely-formatted tourdate date string (ex: January 4-6, 2013  or  February 28 - March 2, 2013  or  May 28, 2013)
	function get_tourdate_dispdate($tourdateid = 0) {
		if($tourdateid > 1) {
			$start_date_a = db_one("start_date","tbl_tour_dates","id=$tourdateid");
			if(strlen($start_date_a) > 2) {
				list($yy,$mm,$dd) = explode("-",$start_date_a);
				$start_date = date('F j',mktime(0,0,0,$mm,$dd,$yy));
				$start_month = date('F',mktime(0,0,0,$mm,$dd,$yy));
				$end_date_a = db_one("end_date","tbl_tour_dates","id=$tourdateid");
				list($eyy,$emm,$edd) = explode("-",$end_date_a);
				$end_date = date('F j',mktime(0,0,0,$emm,$edd,$eyy));
				$end_month = date('F',mktime(0,0,0,$emm,$edd,$eyy));
				$end_day = date('j',mktime(0,0,0,$emm,$edd,$eyy));
				
				//if one day
				if($start_date == $end_date) {
					$dispdate = $start_date.", $yy";
				}
				//if not one day
				else {
					if($start_month != $end_month)
						$dispdate = $start_date."-".$end_date.", $eyy";
					else $dispdate = $start_date."-".$end_day.", $eyy";
				}
			}
			return $dispdate;
		}
	}
?>