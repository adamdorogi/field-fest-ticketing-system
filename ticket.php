<?php
//https://fieldfest.myshopify.com/admin/orders/4404117548.json
//https://fieldfest.myshopify.com/admin/products/17114038316.json?fields=product_type
//https://fieldfest.myshopify.com/admin/products/17114038316/metadata.json

// Get id parameter from URL.
$ticket_id = @preg_replace('/[^-0-9]/', '', $_GET['id']);

// Extract ticket information.
$ticket_info = explode("-", $ticket_id);
$order_id = $ticket_info[0];
$line_item_id = @$ticket_info[1];
$sequence_id = @$ticket_info[2];

// Specify order API JSON file URL.
$order_url = "https://100d4825e2638deb085cb8160f242622:b80ecfc47e6a14f3bb7b3921c81a8f4e@fieldfest.myshopify.com/admin/orders/$order_id.json?fields=id,currency,line_items";
// Get JSON file.
$order_content = @file_get_contents($order_url);
// Convert contents to JSON object.
$order_json = json_decode($order_content, true);

// Create array of line items.
$line_items = array();

// Populate line_items array with line item title, quantity, and price,
// by line item ID.
foreach ((array)$order_json['order']['line_items'] as $line_item) {
    $line_items[$line_item['id']] = array(
        'product_id' => $line_item['product_id'],
        'title' => $line_item['title'],
        'quantity' => $line_item['quantity'],
        'price' => $line_item['price']
    );
}

// Handle line item ID and sequence ID.
if (!array_key_exists($line_item_id, $line_items) or
$sequence_id > $line_items[$line_item_id]['quantity'] - 1) {
    print "ERROR: Invalid ticket ID";
    exit();
}

// Get product ID of current ticket.
$product_id = $line_items[$line_item_id]['product_id'];

// Specify product API JSON file URL.
$product_url = "https://100d4825e2638deb085cb8160f242622:b80ecfc47e6a14f3bb7b3921c81a8f4e@fieldfest.myshopify.com/admin/products/$product_id.json?fields=product_type";
// Get JSON file.
$product_content = @file_get_contents($product_url);
// Convert contents to JSON object.
$product_json = json_decode($product_content, true);

// Generate PDF. //

// Include required files.
use \setasign\Fpdi\Fpdi;
require('fpdf181/fpdf.php');
require('fpdi201/src/autoload.php');

// Create new PDF.
$pdf = new FPDI();
$pdf->AddPage();

// Set PDF template.
$pdf->setSourceFile("field_fest_jegy.pdf");
$template = $pdf->importPage(1);
$pdf->useTemplate($template);

// Generate QR code.
$pdf->Image('https://'.$_SERVER['SERVER_NAME'].'/qrcode.php?id='.$ticket_id, 150, 55, null, null, 'PNG');

// Add font.
$pdf->AddFont('Helvetica Neue Condensed Bold', '', 'helveticaneuecb.php');
$pdf->AddFont('Helvetica Neue', '', 'helveticaneue.php');

// Write QR code.
$pdf->SetXY(10, 110);
$pdf->SetFont('Helvetica Neue Condensed Bold','',12);
$pdf->Cell(0, 0, $ticket_id, 0, 0, 0);

// Write product title.
$pdf->SetXY(10, 60);
$pdf->SetFont('Helvetica Neue Condensed Bold', '', 28);
$pdf->Cell(0, 0, iconv("UTF-8", "ISO-8859-2", $line_items[$line_item_id]['title']));

// Write product type.
$pdf->SetXY(10, 68);
$pdf->SetFont('Helvetica Neue Condensed Bold', '', 12);
$pdf->Cell(0, 0, iconv("UTF-8", "ISO-8859-2", $product_json['product']['product_type']));

// Write product price.
$pdf->SetXY(10, 74);
$pdf->SetFont('Helvetica Neue Condensed Bold', '', 12);
$pdf->Cell(0, 0, iconv("UTF-8", "ISO-8859-2", "Ár: ".$line_items[$line_item_id]['price']." ".$order_json['order']['currency']));

// Get product validity.
// Specify metafield API JSON file URL.
$metafield_url = "https://100d4825e2638deb085cb8160f242622:b80ecfc47e6a14f3bb7b3921c81a8f4e@fieldfest.myshopify.com/admin/products/$product_id/metafields.json";
// Get JSON file.
$metafield_content = @file_get_contents($metafield_url);
// Convert contents to JSON object.
$metafield_json = json_decode($metafield_content, true);

// Format validity.
setlocale(LC_TIME, 'hu_HU');
$format = "%Y. %B %e. %R";
$valid_from = strftime($format, $metafield_json['metafields'][0]['value']);
$valid_to = strftime($format, $metafield_json['metafields'][1]['value']);
$validity = $valid_from." - ".$valid_to;

// Write product validity.
$pdf->SetXY(10, 80);
$pdf->SetFont('Helvetica Neue','',10);
$pdf->Cell(0, 0, iconv("UTF-8", "ISO-8859-2", "Érvényesség: ").$validity);

// Output PDF file.
$pdf->Output('I', 'field_fest_jegy');
?>