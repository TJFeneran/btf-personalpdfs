<?php
	/* SPITS OUT AN XLS ONLY, NO PDF */
	
	include "includes/util.php";
	include "includes/phpexcel/Classes/PHPExcel.php";
	include "includes/phpexcel/Classes/PHPExcel/Writer/Excel2007.php";
	include "includes/phpexcel/Classes/PHPExcel/IOFactory.php";	
	date_default_timezone_set("America/Los_Angeles");

	$tourdateid = intval($_GET["tourdateid"]);
	
	if($tourdateid > 0) {
		$cityname = db_one("city","tbl_tour_dates","id=$tourdateid");
		
		//safe city name (lowercase, no spaces, any extra characters or crap like that)
		$safecity = strtolower(str_replace(array(" ",",","."),"",$cityname));

		$eventid = db_one("eventid","tbl_tour_dates","id=$tourdateid");
		$eventname = strtolower(str_replace(" ","",db_one("name","events","id=$eventid")));
		
		//is updated?
		$isupdated = db_one("workshop_updated","tbl_tour_dates","id=$tourdateid");
		
		//get workshop room names & count
		$wsnames = array();
		$wrcount = 0;
		$eventid = 0;
		$sql = "SELECT eventid,workshop_room_count,workshop_room_1,workshop_room_2,workshop_room_3,workshop_room_4,workshop_room_5,workshop_room_6 FROM `tbl_tour_dates` WHERE id=$tourdateid LIMIT 1";
		$res = mysql_query($sql) or die(mysql_error());
		while($row = mysql_fetch_row($res)) {
			$wsnames = $row;
			$wrcount = $row[1];
			$eventid = $row[0];
		}
		
		//get workshop sched
		$sql = "SELECT * FROM `tbl_date_schedule_workshops` WHERE tourdateid=$tourdateid ORDER BY start_time ASC";
		$res = mysql_query($sql) or die(mysql_error());
		while($row = mysql_fetch_assoc($res)) {
			$wdata[] = $row;
		}
		
		if(count($wdata) > 0) {
			$room1name = strtoupper($wsnames[2]);
			$room2name = strtoupper($wsnames[3]);
			$room3name = strtoupper($wsnames[4]);
			if($wrcount == 4 || $wrcount == 5 || $wrcount == 6)
				$room4name = strtoupper($wsnames[5]);
			if($wrcount == 5 || $wrcount == 6)
				$room5name = strtoupper($wsnames[6]);
			if($wrcount == 6)
				$room6name = strtoupper($wsnames[7]);
							
			//create excel shit
			$workbook = new PHPExcel();
			$workbook->setActiveSheetIndex(0);
			
			//set global font & font size
			$workbook->getDefaultStyle()->getFont()->setName('Tahoma');
			
			if($wrcount == 2) 
				$workbook->getDefaultStyle()->getFont()->setSize(8);
			if($wrcount == 3)
				$workbook->getDefaultStyle()->getFont()->setSize(8);
			if($wrcount == 4)
				$workbook->getDefaultStyle()->getFont()->setSize(8);
			if($wrcount == 5)
				$workbook->getDefaultStyle()->getFont()->setSize(7);
			if($wrcount == 6)
				$workbook->getDefaultStyle()->getFont()->setSize(7);
							
			//set cell wrapping to TRUE for all cells
			$workbook->getDefaultStyle()->getAlignment()->setWrapText(true);
		
			//SET COLUMN WIDTHS
			$sheet = $workbook->getActiveSheet();
			
			if($wrcount == 2) {
				if($eventid == 7 || $eventid == 14)
					$sheet->getColumnDimension('A')->setWidth(30);
				if($eventid == 8)
					$sheet->getColumnDimension('A')->setWidth(30);
				$sheet->getColumnDimension('B')->setWidth(68);
				$sheet->getColumnDimension('C')->setWidth(68);
			}
			if($wrcount == 3) {
				if($eventid == 7 || $eventid == 14)
					$sheet->getColumnDimension('A')->setWidth(26);
				if($eventid == 8)
					$sheet->getColumnDimension('A')->setWidth(24);
				$sheet->getColumnDimension('B')->setWidth(46);
				$sheet->getColumnDimension('C')->setWidth(46);
				$sheet->getColumnDimension('D')->setWidth(46);
			}		
			if($wrcount == 4) {
				if($eventid == 7 || $eventid == 14)
					$sheet->getColumnDimension('A')->setWidth(21);
				if($eventid == 8)
					$sheet->getColumnDimension('A')->setWidth(21);
				$sheet->getColumnDimension('B')->setWidth(36);
				$sheet->getColumnDimension('C')->setWidth(36);	
				$sheet->getColumnDimension('D')->setWidth(36);	
				$sheet->getColumnDimension('E')->setWidth(37);
			}
			if($wrcount == 5) { //89
				if($eventid == 7 || $eventid == 14)
					$sheet->getColumnDimension('A')->setWidth(25);
				if($eventid == 8)
					$sheet->getColumnDimension('A')->setWidth(23);
				$sheet->getColumnDimension('B')->setWidth(35);
				$sheet->getColumnDimension('C')->setWidth(35);	
				$sheet->getColumnDimension('D')->setWidth(35);	
				$sheet->getColumnDimension('E')->setWidth(35);
				$sheet->getColumnDimension('F')->setWidth(35);
			}
			if($wrcount == 6) {
				if($eventid == 7 || $eventid == 14)
					$sheet->getColumnDimension('A')->setWidth(23);
				if($eventid == 8)
					$sheet->getColumnDimension('A')->setWidth(22);
				$sheet->getColumnDimension('B')->setWidth(30);
				$sheet->getColumnDimension('C')->setWidth(29);	
				$sheet->getColumnDimension('D')->setWidth(29);	
				$sheet->getColumnDimension('E')->setWidth(29);
				$sheet->getColumnDimension('F')->setWidth(29);
				$sheet->getColumnDimension('G')->setWidth(30);
			}
			
			//BOLD time column (A) for JUMP & TDA only
			if($eventid == 7 || $eventid == 14)
				$workbook->getActiveSheet()->getStyle('A1:A100')->getFont()->setBold(true);
			
			//VERTICAL CENTER for ALL ROWS
			$workbook->getActiveSheet()->getStyle('A1:G100')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
			
			//ALL NOT ITALIC
			$workbook->getActiveSheet()->getStyle('A1:G100')->getFont()->setItalic(false);
			
			//ROW HEIGHT (USE LOOP FOR ROW #)
			for($i=1;$i<300;$i++)
				$workbook->getActiveSheet()->getRowDimension($i)->setRowHeight(13);
		
			$dataArray = array();
			$currentday = "";
			$count = 0;
			$endbend1_end = 0; //when schedule ends
			$daycount = 0; //what day are we on? set first
			
			foreach($wdata as $wline) {
				++$count;
				$thisdow = date('l',$wline["date"]."");
				if($thisdow != $currentday) {
					++$daycount;
					if($count > 1) { 
						//add empty row...
						$addArray = array("","","","","","","");
						$dataArray[] = $addArray;
						
						//shrink gap between cells
						$workbook->getActiveSheet()->getRowDimension($count)->setRowHeight(8);
						++$count;
					}
			

					$currentday = $thisdow;
					if($wrcount == 2)
						$addArray = array(date('l M. j',$wline["date"]),"$room1name","$room2name");				
					if($wrcount == 3)
						$addArray = array(date('l M. j',$wline["date"]),"$room1name","$room2name","$room3name");
					if($wrcount == 4)
						$addArray = array(date('l M. j',$wline["date"]),"$room1name","$room2name","$room3name","$room4name");
					if($wrcount == 5)
						$addArray = array(date('l M. j',$wline["date"]),"$room1name","$room2name","$room3name","$room4name","$room5name");
					if($wrcount == 6)
						$addArray = array(date('l M. j',$wline["date"]),"$room1name","$room2name","$room3name","$room4name","$room5name","$room6name");
					$dataArray[] = $addArray;
		
					$workbook->getActiveSheet()->getStyle('A'.$count)->getFont()->setSize(11);
		
					//BOLD/ITALIC/FONT_SIZE WS NAMES
					$workbook->getActiveSheet()->getStyle('B'.$count.':G'.$count)->getFont()->setBold(true);
					$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->getFont()->setItalic(true);
					if($eventid == 7 || $eventid == 14)
						$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->getFont()->setSize(8);
					if($eventid == 8)
						$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->getFont()->setSize(7);
					
					//WS NAME ROW HEIGHT
					if($eventid == 7 || $eventid == 14)
						$workbook->getActiveSheet()->getRowDimension($count)->setRowHeight(16);
					if($eventid == 8)
						$workbook->getActiveSheet()->getRowDimension($count)->setRowHeight(14);
					
					if($eventid == 7 || $eventid == 14) {
						$styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_HAIR)));
						if($wrcount == 2) {
							$workbook->getActiveSheet()->getStyle('A'.$count.':C'.$count)->applyFromArray($styleArray);
							$workbook->getActiveSheet()->getStyle('A'.$count.':C'.$count)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
							$workbook->getActiveSheet()->getStyle('A'.$count.':C'.$count)->getFill()->getStartColor()->setARGB('FFEEEEEE');
						}
						if($wrcount == 3) {
							$workbook->getActiveSheet()->getStyle('A'.$count.':D'.$count)->applyFromArray($styleArray);
							$workbook->getActiveSheet()->getStyle('A'.$count.':D'.$count)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
							$workbook->getActiveSheet()->getStyle('A'.$count.':D'.$count)->getFill()->getStartColor()->setARGB('FFEEEEEE');
						}
						if($wrcount == 4) {
							$workbook->getActiveSheet()->getStyle('A'.$count.':E'.$count)->applyFromArray($styleArray);
							$workbook->getActiveSheet()->getStyle('A'.$count.':E'.$count)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
							$workbook->getActiveSheet()->getStyle('A'.$count.':E'.$count)->getFill()->getStartColor()->setARGB('FFEEEEEE');
						}
						if($wrcount == 5) {
							$workbook->getActiveSheet()->getStyle('A'.$count.':F'.$count)->applyFromArray($styleArray);
							$workbook->getActiveSheet()->getStyle('A'.$count.':F'.$count)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
							$workbook->getActiveSheet()->getStyle('A'.$count.':F'.$count)->getFill()->getStartColor()->setARGB('FFEEEEEE');
						}
						if($wrcount == 6) {
							$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->applyFromArray($styleArray);
							$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
							$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->getFill()->getStartColor()->setARGB('FFEEEEEE');
						}
					}	
						
					++$count;
				}
		
				list($hh,$mm,$ss) = explode(":",date('H:i:s',(intval($wline["start_time"]))));
				$dur_raw = $wline["duration"];
				list($dhh,$dmm) = explode(":",$dur_raw);
				$starttime = date('g:ia',(intval($wline["start_time"])));
				$endtime = date('g:ia',mktime($hh+(intval($dhh)),$mm+(intval($dmm)),$ss,0,0,0));
				$addArray = array(substr($starttime,0,strlen($starttime)-1)."-".substr($endtime,0,strlen($endtime)-1));
				$workbook->getActiveSheet()->getStyle('A'.$count)->getFont()->setName('Verdana');
				$workbook->getActiveSheet()->getStyle('A'.$count)->getFont()->setItalic(true);
				
				//only bold time for jump & tda
				if($eventid == 7 || $eventid == 14)
					$workbook->getActiveSheet()->getStyle('A'.$count)->getFont()->setBold(true);
				
				//just in case
				if($wline["span"] > $wrcount)
					$wline["span"] = $wrcount;
					
				//merge if necessary
				if($wline["span"] == 2)
						$workbook->getActiveSheet()->mergeCells('B'.$count.':C'.$count);
				if($wline["span"] == 3)
						$workbook->getActiveSheet()->mergeCells('B'.$count.':D'.$count);
				if($wline["span"] == 4)
						$workbook->getActiveSheet()->mergeCells('B'.$count.':E'.$count);
				if($wline["span"] == 5)
						$workbook->getActiveSheet()->mergeCells('B'.$count.':F'.$count);
				if($wline["span"] == 6)
						$workbook->getActiveSheet()->mergeCells('B'.$count.':G'.$count);
										
				for($i=0;$i<intval($wrcount);$i++) {
					$celltext = urldecode($wline["room".($i+1)]);
					$addArray[] = " ".$celltext;
					
					//only bold for jump & tda...italicize for nuvo
					if($eventid == 7 || $eventid == 14) {
						if($wline["room1_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('B'.$count)->getFont()->setBold(true);
						if($wline["room2_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('C'.$count)->getFont()->setBold(true);
						if($wline["room3_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('D'.$count)->getFont()->setBold(true);
						if($wline["room4_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('E'.$count)->getFont()->setBold(true);
						if($wline["room5_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('F'.$count)->getFont()->setBold(true);
						if($wline["room6_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('G'.$count)->getFont()->setBold(true);
					}
					if($eventid == 8) {
						if($wline["room1_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('B'.$count)->getFont()->setItalic(true);
						if($wline["room2_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('C'.$count)->getFont()->setItalic(true);
						if($wline["room3_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('D'.$count)->getFont()->setItalic(true);
						if($wline["room4_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('E'.$count)->getFont()->setItalic(true);
						if($wline["room5_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('F'.$count)->getFont()->setItalic(true);
						if($wline["room6_bold"] == 1)
							$workbook->getActiveSheet()->getStyle('G'.$count)->getFont()->setItalic(true);
					}
					if($wline["room1_highlight"] == 1)
						$workbook->getActiveSheet()->getStyle('B'.$count)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
					if($wline["room2_highlight"] == 1)
						$workbook->getActiveSheet()->getStyle('C'.$count)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
					if($wline["room3_highlight"] == 1)
						$workbook->getActiveSheet()->getStyle('D'.$count)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
					if($wline["room4_highlight"] == 1)
						$workbook->getActiveSheet()->getStyle('E'.$count)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
					if($wline["room5_highlight"] == 1)
						$workbook->getActiveSheet()->getStyle('F'.$count)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
					if($wline["room6_highlight"] == 1)
						$workbook->getActiveSheet()->getStyle('G'.$count)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
				}			
				
				$dataArray[] = $addArray;
				
				$styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_HAIR)));
				if($wrcount == 2)
					$workbook->getActiveSheet()->getStyle('A'.$count.':C'.$count)->applyFromArray($styleArray);
				if($wrcount == 3)
					$workbook->getActiveSheet()->getStyle('A'.$count.':D'.$count)->applyFromArray($styleArray);
				if($wrcount == 4)
					$workbook->getActiveSheet()->getStyle('A'.$count.':E'.$count)->applyFromArray($styleArray);
				if($wrcount == 5)				
					$workbook->getActiveSheet()->getStyle('A'.$count.':F'.$count)->applyFromArray($styleArray);
				if($wrcount == 6)
					$workbook->getActiveSheet()->getStyle('A'.$count.':G'.$count)->applyFromArray($styleArray);
				
			}
		
			$workbook->getActiveSheet()->fromArray($dataArray,NULL,'A1');
			
			foreach($workbook->getActiveSheet()->getRowDimensions() as $rd) { $rd->setRowHeight(-1); }
			
			$outputFileType = 'Excel5';
			$outputFileName = "output/workshop_xls/$eventname"."_"."$safecity.xls";
			$inexcel2 = $outputFileName;
			$objWriter = PHPExcel_IOFactory::createWriter($workbook, $outputFileType);
			$objWriter->save($outputFileName);
			unset($workbook);
			unset($objWriter);
			
			if(isset($_GET["open"])) {
				header("Cache-Control: public");
		    	header("Content-Description: File Transfer");
			    header("Content-Disposition: attachment; filename=".basename($outputFileName));
		    	header("Content-Type: application/octet-stream");
			    header("Content-Transfer-Encoding: binary");
				readfile($outputFileName);
			}
		}
	}	

	exit();
?>