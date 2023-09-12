<?php
/**
 Plugin Name: Activity Log Report
 Plugin URI: http://www.ozonewebtechs.com/
 Description: Activity Log Report pdf file
 Version: 1.0
 Author: Ozone Webtech
 Author URI: http://www.ozonewebtechs.com/
 Text Domain: Activity-Log-Report
 
 */

function aal_exporters_pdf($exporter_instances) {
	
	include_once 'exporters/class-aal-exporter-pdf.php';
	
	$classname = 'AAL_Exporter_pdf';
	
	$instance = new $classname;
	if ( property_exists( $instance, 'id' ) ) {
		$exporter_instances[ $instance->id ] = $instance;
	}
	
    return $exporter_instances;
}
add_filter( "aal_exporters", "aal_exporters_pdf",10,1 );

function admin_footer_alr(){
	$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id_alr_report', 0 );
	?>
	<script>
	jQuery( document ).ready(function() {
		if(jQuery( "body button[name='aal-record-actions-submit']" ).length){
			jQuery( "body button[name='aal-record-actions-submit']" ).text('Export');
			jQuery( "body button[name='aal-record-actions-submit']" ).css({"margin-top": "0px"});
		}
		
		jQuery(document).on('change',"body select[name='aal-record-action']",function(e){
			var val = jQuery(this).val();
			if(val != ''){
				jQuery( "body button[name='aal-record-actions-submit']" ).text('Export as '+val);
				jQuery( "body button[name='aal-record-actions-submit']" ).trigger('click');
			}
		});
		
		/* var file_frame;
		var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
		var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this

			jQuery('#upload_image_button').on('click', function( event ){

				event.preventDefault();

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					// Set the post ID to what we want
					file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					// Open frame
					file_frame.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = set_to_post_id;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();

					// Do something with attachment.id and/or attachment.url here
					jQuery( '#image_attachment_id_alr_logo_url' ).val( attachment.url );
					jQuery( '#image_attachment_id_alr_logo' ).val( attachment.id );

					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});

					// Finally, open the modal
					file_frame.open();
			}); */

			// Restore the main ID when the add media button is pressed
			jQuery( 'a.add_media' ).on( 'click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
	});
	</script>
	<?php
}
add_action( "admin_footer", "admin_footer_alr",100 );

function action_admin_menu_alr(){
	add_submenu_page(
			'activity_log_page',
			__( 'Report Settings', 'aryo-activity-log' ), 	
			__( 'Report Settings', 'aryo-activity-log' ), 			
			'manage_options', 								
			'report-settings', 			
			'display_settings_page_alr' 			
		);
}
add_action( 'admin_menu', 'action_admin_menu_alr', 30 );



function display_settings_page_alr(){
    if(isset($_REQUEST['email_address_send']) && !empty($_REQUEST['email_address_send'])){
        
    	$is_test_mode_off = ! defined( 'AAL_TESTMODE' ) || ( defined( 'AAL_TESTMODE' ) && ! AAL_TESTMODE );

		
		include_once 'exporters/PDFLib/PDFLib.php';
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
		$pdf->MultiCell(165,5,'Design by PH',0,'L');
		$pdf->Ln(3);

	// email stuff (change data below)
	    $server = $_SERVER['SERVER_NAME'];
        $to = $_REQUEST['email_address_send']; 
        $from = "website@".$server; 
        $subject = "Website Maintenance Report"; 
        $message = "<p>Please see the attachment.</p>";
        
        // a random hash will be necessary to send mixed content
        $separator = md5(time());
        
        // carriage return type (we use a PHP end of line constant)
        $eol = PHP_EOL;
        
        // attachment name
        $filename = "Report.pdf";
        
        // encode data (puts attachment in proper format)
        $pdfdoc = $pdf->Output("", "S");
        $attachment = chunk_split(base64_encode($pdfdoc));
        
        // main header
        $headers  = "From: ".$from.$eol;
        $headers .= "MIME-Version: 1.0".$eol; 
        $headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"";
        
        // no more headers after this, we start the body! //
        
        $body = "--".$separator.$eol;
     
        // message
        $body .= "--".$separator.$eol;
        $body .= "Content-Type: text/html; charset=\"iso-8859-1\"".$eol;
        $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
        $body .= $message.$eol;
        
        // attachment
        $body .= "--".$separator.$eol;
        $body .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol; 
        $body .= "Content-Transfer-Encoding: base64".$eol;
        $body .= "Content-Disposition: attachment".$eol.$eol;
        $body .= $attachment.$eol;
        $body .= "--".$separator."--";
        
        // send message
        $send= mail($to, $subject, $body, $headers);
        
        if($send == 1){
            echo "<h3>Main Send Successfully</h3>";
        }
    }
	?>
	<div class="wrap">

		<h1 class="aal-page-title"><?php _e( 'Report Settings', 'aryo-activity-log' ); ?></h1>
		
		<form method="post">
            <table class="form-table">
                <tr valign="top">
                <th scope="row">Enter E-mail Address</th>
                <td><input type="text" name="email_address_send"  /></td>
                </tr>
                
            </table>
            
            <?php submit_button(); ?>
        
        </form>
		
	</div>
	<?php
} 