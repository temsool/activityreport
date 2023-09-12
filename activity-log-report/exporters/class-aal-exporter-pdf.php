<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AAL_Exporter_pdf {
    /**
	 * Exporter name
	 *
	 * @var string
	 */
	public $name = 'PDF';

	/**
	 * Exporter ID
	 *
	 * @var string
	 */
	public $id = 'pdf';

	/**
	 * Writes PDF data for download
	 *
	 * @param array $data Array of data to output.
	 * @param array $columns Column names included in data set.
	 * @return void
	 */
	public function write( $data, $columns ) {
		$is_test_mode_off = ! defined( 'AAL_TESTMODE' ) || ( defined( 'AAL_TESTMODE' ) && ! AAL_TESTMODE );

		
		include_once 'PDFLib/PDFLib.php';
		$pdf=new PDF();
		$pdf->AddPage();
		$pdf->SetAutoPageBreak(false,30);
		
		/* $pdf->SetX(10);
		$pdf->SetY(10); */
		/*$pdf->SetFont("Times",'',14);
		$pdf->SetFont('Arial','B',16);*/
		
		$pdf->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
		$pdf->SetFont('MuseoSansRounded-Bold','B',14);
		
		$pdf->SetTextColor(3,169,244);
		$pdf->Cell(180,10, site_url() ,0,1,'L');
		$pdf->Ln(3);
		$pdf->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
		$pdf->SetFont('MuseoSansRounded-Bold','B',10);
		/*$pdf->SetFont('Arial','B',10);*/
		$pdf->SetTextColor(4,4,4);
		$pdf->MultiCell(165,5,'Actions:',0,'L');
		$pdf->Ln(3);
		$pdf->SetTextColor(4,4,4);
		$pdf->SetDrawColor(205,205,205);
		
		$pdf->AddFont('MuseoSansRounded','','museosansrounded.php');
		$pdf->SetFont('MuseoSansRounded','',10);
		
		/*$pdf->SetFont("Times",'',11);*/
		$pdf->SetWidths(array(43,27,22,20,53,25));
		$i = 0;
		$headerData = array();
		foreach($columns as $col){
			$headerData[] = $col;
		}
		$pdf->SetTextColor(3,169,244);
		$pdf->Row(array($headerData[0],$headerData[1],$headerData[3],$headerData[4],$headerData[5],$headerData[6]));
		$pdf->SetTextColor(4,4,4);
		foreach($data as $row){
			
				$i = 0;
				$ddData = array();
				foreach($row as $col){
					$ddData[] = $col;
				}
				$tmp = trim($ddData[6]);
				if($tmp != 'Failed Login'){
				    
				    if($ddData[1] == 'DanLS@DLS' || $ddData[1] == 'DanS' || $ddData[1] == 'Daniel Salisbury'){
				        $ddData[1] = 'PHDmaint';
				    }
				    
					$pdf->Row(array($ddData[0],$ddData[1],$ddData[3],$ddData[4],$ddData[5],$ddData[6]));
				}
		}
		$pdf->Ln(7);
		$pdf->MultiCell(165,5,'Yours Sincerely,',0,'L');
		$pdf->Ln(4);
		$pdf->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
		$pdf->SetFont('MuseoSansRounded-Bold','B',11);
		$pdf->MultiCell(165,5,'Design by PHD',0,'L');
		$pdf->Ln(3);
		
		$pdf->Output('report.pdf','D');
		
		
	
		
		
		
		
		if ( $is_test_mode_off ) {
			exit;
		}
	}
	
}
