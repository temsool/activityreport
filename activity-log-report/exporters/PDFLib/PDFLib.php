<?php
//Based on HTML2PDF by Clément Lavoillotte

require('fpdf.php');

//function hex2dec
//returns an associative array (keys: R,G,B) from a hex html code (e.g. #3FE5AA)
function hex2dec($couleur = "#000000"){
    $R = substr($couleur, 1, 2);
    $rouge = hexdec($R);
    $V = substr($couleur, 3, 2);
    $vert = hexdec($V);
    $B = substr($couleur, 5, 2);
    $bleu = hexdec($B);
    $tbl_couleur = array();
    $tbl_couleur['R']=$rouge;
    $tbl_couleur['G']=$vert;
    $tbl_couleur['B']=$bleu;
    return $tbl_couleur;
}

//conversion pixel -> millimeter in 72 dpi
function px2mm($px){
    return $px*25.4/72;
}

function txtentities($html){
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}
////////////////////////////////////

class PDF extends FPDF
{
//variables of html parser
protected $B;
protected $I;
protected $U;
protected $HREF;
protected $fontList;
protected $issetfont;
protected $issetcolor;
var $widths;
var $aligns;

function __construct($orientation='P', $unit='mm', $format='A4')
{
    //Call parent constructor
    parent::__construct($orientation,$unit,$format);

    //Initialization
    $this->B=0;
    $this->I=0;
    $this->U=0;
    $this->HREF='';

    $this->tableborder=0;
    $this->tdbegin=false;
    $this->tdwidth=0;
    $this->tdheight=0;
    $this->tdalign="L";
    $this->tdbgcolor=false;

    $this->oldx=0;
    $this->oldy=0;

    $this->fontlist=array("arial","times","courier","helvetica","symbol");
    $this->issetfont=false;
    $this->issetcolor=false;
}

//////////////////////////////////////
//html parser

function WriteHTML($html)
{
    $html=strip_tags($html,"<b><u><i><a><img><p><br><strong><em><font><tr><blockquote><hr><td><tr><table><sup>"); //remove all unsupported tags
    $html=str_replace("\n",'',$html); //replace carriage returns with spaces
    $html=str_replace("\t",'',$html); //replace carriage returns with spaces
    $a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE); //explode the string
    foreach($a as $i=>$e)
    {
        if($i%2==0)
        {
            //Text
            if($this->HREF)
                $this->PutLink($this->HREF,$e);
            elseif($this->tdbegin) {
                if(trim($e)!='' && $e!="&nbsp;") {
                    $this->Cell($this->tdwidth,$this->tdheight,$e,$this->tableborder,'',$this->tdalign,$this->tdbgcolor);
                }
                elseif($e=="&nbsp;") {
                    $this->Cell($this->tdwidth,$this->tdheight,'',$this->tableborder,'',$this->tdalign,$this->tdbgcolor);
                }
            }
            else
                $this->Write(5,stripslashes(txtentities($e)));
        }
        else
        {
            //Tag
            if($e[0]=='/')
                $this->CloseTag(strtoupper(substr($e,1)));
            else
            {
                //Extract attributes
                $a2=explode(' ',$e);
                $tag=strtoupper(array_shift($a2));
                $attr=array();
                foreach($a2 as $v)
                {
                    if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
                        $attr[strtoupper($a3[1])]=$a3[2];
                }
                $this->OpenTag($tag,$attr);
            }
        }
    }
}

function OpenTag($tag, $attr)
{
    //Opening tag
    switch($tag){

        case 'SUP':
            if( !empty($attr['SUP']) ) {    
                //Set current font to 6pt     
                $this->SetFont('','',6);
                //Start 125cm plus width of cell to the right of left margin         
                //Superscript "1" 
                $this->Cell(2,2,$attr['SUP'],0,0,'L');
            }
            break;

        case 'TABLE': // TABLE-BEGIN
            if( !empty($attr['BORDER']) ) $this->tableborder=$attr['BORDER'];
            else $this->tableborder=0;
            break;
        case 'TR': //TR-BEGIN
            break;
        case 'TD': // TD-BEGIN
            if( !empty($attr['WIDTH']) ) $this->tdwidth=($attr['WIDTH']/4);
            else $this->tdwidth=40; // Set to your own width if you need bigger fixed cells
            if( !empty($attr['HEIGHT']) ) $this->tdheight=($attr['HEIGHT']/6);
            else $this->tdheight=6; // Set to your own height if you need bigger fixed cells
            if( !empty($attr['ALIGN']) ) {
                $align=$attr['ALIGN'];        
                if($align=='LEFT') $this->tdalign='L';
                if($align=='CENTER') $this->tdalign='C';
                if($align=='RIGHT') $this->tdalign='R';
            }
            else $this->tdalign='L'; // Set to your own
            if( !empty($attr['BGCOLOR']) ) {
                $coul=hex2dec($attr['BGCOLOR']);
                    $this->SetFillColor($coul['R'],$coul['G'],$coul['B']);
                    $this->tdbgcolor=true;
                }
            $this->tdbegin=true;
            break;

        case 'HR':
            if( !empty($attr['WIDTH']) )
                $Width = $attr['WIDTH'];
            else
                $Width = $this->w - $this->lMargin-$this->rMargin;
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetLineWidth(0.2);
            $this->Line($x,$y,$x+$Width,$y);
            $this->SetLineWidth(0.2);
            $this->Ln(1);
            break;
        case 'STRONG':
            $this->SetStyle('B',true);
            break;
        case 'EM':
            $this->SetStyle('I',true);
            break;
        case 'B':
        case 'I':
        case 'U':
            $this->SetStyle($tag,true);
            break;
        case 'A':
            $this->HREF=$attr['HREF'];
            break;
        case 'IMG':
            if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
                if(!isset($attr['WIDTH']))
                    $attr['WIDTH'] = 0;
                if(!isset($attr['HEIGHT']))
                    $attr['HEIGHT'] = 0;
                $this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
            }
            break;
        case 'BLOCKQUOTE':
        case 'BR':
            $this->Ln(5);
            break;
        case 'P':
            $this->Ln(10);
            break;
        case 'FONT':
            if (isset($attr['COLOR']) && $attr['COLOR']!='') {
                $coul=hex2dec($attr['COLOR']);
                $this->SetTextColor($coul['R'],$coul['G'],$coul['B']);
                $this->issetcolor=true;
            }
            if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
                $this->SetFont(strtolower($attr['FACE']));
                $this->issetfont=true;
            }
            if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist) && isset($attr['SIZE']) && $attr['SIZE']!='') {
                $this->SetFont(strtolower($attr['FACE']),'',$attr['SIZE']);
                $this->issetfont=true;
            }
            break;
    }
}

function CloseTag($tag)
{
    //Closing tag
    if($tag=='SUP') {
    }

    if($tag=='TD') { // TD-END
        $this->tdbegin=false;
        $this->tdwidth=0;
        $this->tdheight=0;
        $this->tdalign="L";
        $this->tdbgcolor=false;
    }
    if($tag=='TR') { // TR-END
        $this->Ln();
    }
    if($tag=='TABLE') { // TABLE-END
        $this->tableborder=0;
    }

    if($tag=='STRONG')
        $tag='B';
    if($tag=='EM')
        $tag='I';
    if($tag=='B' || $tag=='I' || $tag=='U')
        $this->SetStyle($tag,false);
    if($tag=='A')
        $this->HREF='';
    if($tag=='FONT'){
        if ($this->issetcolor==true) {
            $this->SetTextColor(0);
        }
        if ($this->issetfont) {
            $this->SetFont('arial');
            $this->issetfont=false;
        }
    }
}

function SetStyle($tag, $enable)
{
    //Modify style and select corresponding font
    $this->$tag+=($enable ? 1 : -1);
    $style='';
    foreach(array('B','I','U') as $s) {
        if($this->$s>0)
            $style.=$s;
    }
    $this->SetFont('',$style);
}

function PutLink($URL, $txt)
{
    //Put a hyperlink
    $this->SetTextColor(0,0,255);
    $this->SetStyle('U',true);
    $this->Write(5,$txt,$URL);
    $this->SetStyle('U',false);
    $this->SetTextColor(0);
}

function SetWidths($w)
{
    //Set the array of column widths
    $this->widths=$w;
}

function SetAligns($a)
{
    //Set the array of column alignments
    $this->aligns=$a;
}

function Row($data)
{
    //Calculate the height of the row
    $nb=0;
    for($i=0;$i<count($data);$i++)
        $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
    $h=5*$nb;
    //Issue a page break first if needed
    $this->CheckPageBreak($h);
    //Draw the cells of the row
    for($i=0;$i<count($data);$i++)
    {
        $w=$this->widths[$i];
        $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
        //Save the current position
        $x=$this->GetX();
        $y=$this->GetY();
        //Draw the border
        $this->Rect($x,$y,$w,$h);
        //Print the text
        $this->MultiCell($w,5,$data[$i],0,$a);
        //Put the position to the right of the cell
        $this->SetXY($x+$w,$y);
    }
    //Go to the next line
    $this->Ln($h);
}

function CheckPageBreak($h)
{
    //If the height h would cause an overflow, add a new page immediately
    if($this->GetY()+$h>$this->PageBreakTrigger)
        $this->AddPage($this->CurOrientation);
}

function NbLines($w,$txt)
{
    //Computes the number of lines a MultiCell of width w will take
    $cw=&$this->CurrentFont['cw'];
    if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    $s=str_replace("\r",'',$txt);
    $nb=strlen($s);
    if($nb>0 and $s[$nb-1]=="\n")
        $nb--;
    $sep=-1;
    $i=0;
    $j=0;
    $l=0;
    $nl=1;
    while($i<$nb)
    {
        $c=$s[$i];
        if($c=="\n")
        {
            $i++;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
            continue;
        }
        if($c==' ')
            $sep=$i;
        $l+=$cw[$c];
        if($l>$wmax)
        {
            if($sep==-1)
            {
                if($i==$j)
                    $i++;
            }
            else
                $i=$sep+1;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
        }
        else
            $i++;
    }
    return $nl;
}

function Header(){
	
	$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id_alr_report', 0 );
	$my_saved_attachment_post_id_url = '';
	$my_saved_attachment_post_id_path = '';
	/*if(!empty($my_saved_attachment_post_id)){
		/* $upload_dir = wp_upload_dir();
		$url = get_post_meta($my_saved_attachment_post_id,'_wp_attached_file',true);
		$my_saved_attachment_post_id_path = $upload_dir['basedir'].'/'.$url; */
		/*$my_saved_attachment_post_id_path = get_attached_file( $my_saved_attachment_post_id );
		$my_saved_attachment_post_id_url = wp_get_attachment_url( $my_saved_attachment_post_id );
	}*/

		$this->SetY(5);
		$this->SetTextColor(205,205,205);
		/*$this->SetFont('Arial','B',30);*/
		$this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
		$this->SetFont('MuseoSansRounded-Bold','B',30);
		$this->Cell(70,30, 'WEBSITE' ,0,1,'L');
		$this->SetY(17);
		$this->Cell(70,30, 'MAINTENANCE' ,0,1,'L');
		$this->SetY(29);
		$this->Cell(70,30, 'REPORT' ,0,1,'L');
		
		/*if(!empty($my_saved_attachment_post_id_path)){
			$this->Image($my_saved_attachment_post_id_path,152,10,50);
		}else{*/
			$my_saved_attachment_post_id_path = ABSPATH . 'wp-content/plugins/activity-log-report/paul-hewitt-design.png';
			$this->Image($my_saved_attachment_post_id_path,152,10,50);
		/*}*/
		$this->Ln();
		$this->SetY(40);
	/*	$this->SetFont('Arial','B',11);*/
	    $this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
    	$this->SetFont('MuseoSansRounded-Bold','B',11);
	
		$this->SetTextColor(158,158,158);
		$this->Cell(170,10, 'Date:' ,0,1,'R');
		$this->SetY(40);
	/*	$this->SetFont('Arial','',11);*/
		$this->AddFont('MuseoSansRounded','','museosansrounded.php');
    	$this->SetFont('MuseoSansRounded','',10);
	
		$this->SetTextColor(158,158,158);
		$this->Cell(190,10, date('d/m/Y') ,0,1,'R');
		$this->Ln(10);
		$this->SetDrawColor(205,205,205);
		$this->SetLineWidth(1.5);
		$this->Line(10, 55, 200, 55);
	
}

// Page footer
function Footer()
{
    $this->SetY(-25);
	/*$this->SetFont('Arial','B',8);*/
	$this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',8);
	$this->SetTextColor(3,169,244);
	$this->Cell(180,10, 'Design by PHD' ,0,1,'L');
	$this->SetY(-25);
	/*$this->SetFont('Arial','',8);*/
	
	$this->AddFont('MuseoSansRounded','','museosansrounded.php');
	$this->SetFont('MuseoSansRounded','',8);
		
	$this->SetTextColor(4,4,4);
	$this->SetX(32);
	$this->Cell(180,10, '14 Wilkinson Business Park, Clywedog Road South, Wrexham LL13 9AE' ,0,1,'L');
	$this->SetY(-21);
/*	$this->SetFont('Arial','B',8);*/
	
	$this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',8);
	
	$this->SetTextColor(3,169,244);
	$this->Cell(180,10, 'T' ,0,1,'L');
	$this->SetY(-21);
	/*$this->SetFont('Arial','',8);*/
	
	$this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',8);
	
	$this->SetTextColor(4,4,4);
	$this->SetX(13);
	$this->Cell(180,10, '+44 (0) 1978 856 688' ,0,1,'L');
	$this->Ln(10);
	$this->SetY(-21);
	$this->SetX(42);
/*	$this->SetFont('Arial','B',8);*/
	$this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',8);
	
	$this->SetTextColor(3,169,244);
	$this->Cell(180,10, 'E' ,0,1,'L');
	$this->SetY(-21);
/*	$this->SetFont('Arial','',8);*/

	$this->AddFont('MuseoSansRounded','','museosansrounded.php');
	$this->SetFont('MuseoSansRounded','',8);
	
	$this->SetTextColor(4,4,4);
	$this->SetX(45);
	$this->Cell(180,10, 'hello@paulhewittdesign.co.uk' ,0,1,'L');
	$this->SetY(-28);
	$this->SetX(10);
	$this->SetDrawColor(205,205,205);
	$this->SetFillColor(205,205,205);
	$this->Cell(190,1, ' ' ,1,1,'L',true);
	$this->Ln(100);
	$this->SetY(-12);
	$this->SetX(10);
	$this->SetDrawColor(205,205,205);
	$this->SetFillColor(205,205,205);
	$this->Cell(190,0.1, ' ' ,1,1,'L',true);
	$this->SetY(-13);
	$this->SetX(10);
	/* $this->SetFont('Arial','B',5);*/
	 $this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',5);
	
	$this->SetTextColor(4,4,4);
	$this->Cell(180,10, 'Company Reg No.' ,0,1,'L');
	$this->SetY(-13);
	$this->SetX(26);
/*	$this->SetFont('Arial','',5);*/
    $this->AddFont('MuseoSansRounded','','museosansrounded.php');
	$this->SetFont('MuseoSansRounded','',5);
	
	$this->SetTextColor(4,4,4);
	$this->Cell(180,10, '06595212' ,0,1,'L');
	
	$this->SetY(-13);
	$this->SetX(37);
/*	$this->SetFont('Arial','B',5);*/
    $this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',5);
	
	$this->SetTextColor(4,4,4);
	$this->Cell(180,10, 'Registered Address:' ,0,1,'L');
	$this->SetY(-13);
	$this->SetX(56);
/*	$this->SetFont('Arial','',5);*/
	$this->AddFont('MuseoSansRounded','','museosansrounded.php');
	$this->SetFont('MuseoSansRounded','',5);
	
	$this->SetTextColor(4,4,4);
	$this->Cell(180,10, 'Paul Hewitt Design Ltd, 14 Wilkinson Business Park, Clywedog Road South, Wrexham LL13 9AE' ,0,1,'L');
	
	$this->SetY(-13);
	$this->SetX(130);
/*	$this->SetFont('Arial','B',9);*/
	$this->AddFont('MuseoSansRounded-Bold','B','museosansroundedb.php');
	$this->SetFont('MuseoSansRounded-Bold','B',9);
	
	$this->SetTextColor(3,169,244);
	$this->Cell(70,10, 'designbyphd.co.uk' ,0,1,'R');
	
}

}//end of class

?>