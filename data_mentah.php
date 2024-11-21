<?php
//session_start();
if (!isset($_SESSION['apriori_parfum_id'])) {
    header("location:index.php?menu=forbidden");
}

include_once "database.php";
include_once "fungsi.php";
include_once "import/excel_reader2.php";
?>
<div class="main-content">
    <div class="main-content-inner">
        <div class="page-content">
            <div class="page-header">
                <h1>
                    Data Mentah
                </h1>
            </div><!-- /.page-header -->
<?php
//object database class
$db_object = new database();

$sql = "SELECT
        *
        FROM
         raw";
$query=$db_object->db_query($sql);
$jumlah=$db_object->db_num_rows($query);

function count_alphabets_without_spaces($string) {
    return strlen(preg_replace('/\s/', '', $string));
  }

// function check if string contain all numbers
function is_all_numbers($string){
    return preg_match('/^[0-9]+$/', $string);
}

// 

?>            
<div class="row">
    <div class="col-sm-4">
        <div class="widget-box">
        </div>
    </div>
</div>

            <div class="row">
                <div class="col-sm-12">
                <div class="widget-box">
                    <div class="widget-body">
                    <div class="widget-main">
            <?php
            if (!empty($pesan_error)) {
                display_error($pesan_error);
            }
            if (!empty($pesan_success)) {
                display_success($pesan_success);
            }

            echo "Jumlah data: ".$jumlah."<br>";
            if($jumlah==0){
                echo "Data kosong...";
            }
            else{
            ?>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Umur (tahun)</th>
                <th>Jenis Kelamin</th>
                <th>Harga</th>
                </tr>
                <?php
                // raw data: smallburger,largeburger,kebab, hotdog, largesosis, blackkebab, kebabsosis, agelow, ageold, agemature, male, female, priceabove20000, pricebelow20000
                // produk is smallburger,largeburger,kebab, hotdog, largesosis, blackkebab, kebabsosis
                // umur is agelow, ageold, agemature
                // jenis kelamin is male, female
                // harga is priceabove20000, pricebelow20000
                    $no=1;
                    while($row=$db_object->db_fetch_array($query)){
                        echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".format_date2($row['transaction_date'])."</td>";
                            // echo "<td>".$row['produk']."</td>";
                            // sesuaikan th dengan data yang ada di database
                            $rawProduk = explode(",", $row['produk']);
                            $arrProduk = array("smallkebab", "syawarma", "burger", "kebab", "hotdog", "kebabsosis", "blackkebab");
                            // $arrUmur = array("agelow", "ageold", "agemature");
                            $arrJenisKelamin = array("male", "female");
                            // $arrHarga = array("priceabove20000", "pricebelow20000");

                            $produk = "";
                            $umur = "";
                            $jenisKelamin = "";
                            $harga = "";
                            foreach ($rawProduk as $key => $value) {
                                if(in_array($value, $arrProduk)){
                                    $produk .= $value.", ";
                                }
                                if(count_alphabets_without_spaces($value) >=1 && count_alphabets_without_spaces($value) <= 2){
                                    $umur .= $value.", ";
                                }
                                if(in_array($value, $arrJenisKelamin)){
                                    $jenisKelamin .= $value.", ";
                                }
                                if(count_alphabets_without_spaces($value) > 2 && is_all_numbers($value)){
                                    $harga .="Rp ".number_format($value,0,',','.').", ";
                                }
                            }

                            echo "<td>".substr($produk, 0, -2)."</td>";
                            echo "<td>".substr($umur, 0, -2)."</td>";
                            echo "<td>".substr($jenisKelamin, 0, -2)."</td>";
                            echo "<td>".substr($harga, 0, -2)."</td>";

                        echo "</tr>";
                        $no++;
                    }
                    ?>
            </table>
            <?php
            }
            ?>
            </div>
            </div>
                    </div>
            </div>
                </div>
        </div>
    </div>
</div>

<?php
function get_produk_to_in($produk){
    $ex = explode(",", $produk);
    //$temp = "";
    for ($i=0; $i < count($ex); $i++) { 

        $jml_key = array_keys($ex, $ex[$i]);
        if(count($jml_key)>1){
            unset($ex[$i]);
        }

        //$temp = $ex[$i];
    }
    return implode(",", $ex);
}

?>
  