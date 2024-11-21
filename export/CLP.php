<?php
include_once "../database.php";
include_once "../fungsi.php";
include_once "fpdf16/fpdf.php";

$id_process = $_REQUEST['id_process'];
// object database class
$db_object = new database();

// Query to fetch data
$sql_que = "SELECT
            conf.*, log.start_date, log.end_date
            FROM
             confidence conf, process_log `log`
            WHERE conf.id_process = '$id_process' "
    . " AND conf.id_process = log.id "
    // . " AND conf.lolos=1 "
    . " ORDER BY conf.nilai_uji_lift DESC";

$db_query = $db_object->db_query($sql_que) or die("Query failed");

// Function to remove spaces from array elements
function removeSpaces($inputArray)
{
    if (!is_array($inputArray)) {
        return []; // Return empty array if input is not an array
    }
    return array_map('trim', $inputArray); // Remove spaces from each element
}

// Function to reformat the sentence based on predefined categories
function ReformatSentence($arr)
{
    $categories = [
        "produk" => ["smallkebab", "syawarma", "burger", "kebab", "hotdog", "kebabsosis", "blackkebab"],
        "umur" => ["agelow", "ageold", "agemature"],
        "jenis_kelamin" => ["male", "female"],
        "harga" => ["priceabove20000", "pricebelow20000"]
    ];

    $prefixes = [
        "produk" => "membeli ",
        "umur" => "berumur ",
        "jenis_kelamin" => "berjenis kelamin ",
        "harga" => "memiliki total "
    ];

    foreach ($arr as &$item) {
        foreach ($categories as $category => $values) {
            if (in_array($item, $values)) {
                $item = $prefixes[$category] . $item;
                break;
            }
        }
    }

    // Join the array elements with appropriate connectors
    return implode(" dan ", $arr);
}

// Initialize variables
$i = 0;

// Fetch data from query
while ($data = $db_object->db_fetch_array($db_query)) {
    if ($data['nilai_uji_lift'] < 1.0) {
        continue; // Skip entries with lift value below 1.0
    }

    // Process kombinasi1
    $kombinasi1 = removeSpaces(explode(",", $data['kombinasi1']));
    if (!empty($kombinasi1)) {
        $kombinasi1 = ReformatSentence($kombinasi1);
    }
    $data['kombinasi1'] = $kombinasi1;

    // Process kombinasi2
    $kombinasi2 = removeSpaces(explode(",", $data['kombinasi2']));
    if (!empty($kombinasi2)) {
        $kombinasi2 = ReformatSentence($kombinasi2);
    }
    $data['kombinasi2'] = $kombinasi2;

    // Store results for PDF generation
    $cell[$i][0] = price_format($data['nilai_uji_lift']);
    $cell[$i][1] = "Jika konsumen " . $data['kombinasi1'] . " maka konsumen akan " . $data['kombinasi2'];

    $i++;
}

// Start PDF generation
class PDF extends FPDF
{
    // Header for the PDF
    function Header()
    {
        $this->SetFont('Arial', 'B', 14); // Font: Times New Roman, Bold, Size 14
        $this->SetFillColor(255, 255, 255); // Background color
        $this->SetTextColor(0, 0, 0); // Text color
    }
}

// PDF settings
$pdf = new PDF('L', 'cm', 'A4'); // Landscape, cm units, A4 paper size
$pdf->Open();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

// Title
$pdf->Cell(28, 1, 'Laporan Hasil Analisa', 'LRTB', 0, 'C');
$pdf->Ln();
$pdf->Ln();

// Table Header
$pdf->Cell(1, 1, 'No', 'LRTB', 0, 'C');
$pdf->Cell(24, 1, 'Rule', 'LRTB', 0, 'C');
$pdf->Cell(3, 1, 'Nilai Lift Ratio', 'LRTB', 0, 'C');
$pdf->Ln();
$pdf->SetFont('Arial', '', 10);


// Table Content
for ($j = 0; $j < $i; $j++) {
    $pdf->Cell(1, 1, $j + 1, 'LBTR', 0, 'C');
    $pdf->Cell(24, 1, $cell[$j][1], 'LBTR', 0, 'L');
    $pdf->Cell(3, 1, $cell[$j][0], 'LBTR', 0, 'C');
    $pdf->Ln();
}

// Output PDF
$pdf->Output();
