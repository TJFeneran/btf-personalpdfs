<?php

	/* PERIODICALLY CLEAR EXISTING PERSONAL PDFs AND WORKSHP XLSs */

	exec("rm -rf /home/pdf/html/output/personal_pdf/*.pdf");
	exec("rm -rf /home/pdf/html/output/workshop_xls/*.xls");
	
	print("DONE");

?>
