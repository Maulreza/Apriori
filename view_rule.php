<?php
//session_start();
if (!isset($_SESSION['apriori_toko_id'])) {
    header("location:index.php?menu=forbidden");
}

include_once "database.php";
include_once "fungsi.php";
include_once "mining.php";
include_once "display_mining.php";
?>
<div class="main-content">
    <div class="main-content-inner">
        <div class="page-content">
            <div class="page-header">
                <h1>
                    Hasil
                </h1>
            </div><!-- /.page-header -->
            
<div class="row">
    <div class="col-sm-12">
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
<?php
//object database class
$db_object = new database();

$pesan_error = $pesan_success = "";
if(isset($_GET['pesan_error'])){
    $pesan_error = $_GET['pesan_error'];
}
if(isset($_GET['pesan_success'])){
    $pesan_success = $_GET['pesan_success'];
}

if (isset($_POST['submit'])) {
    $can_process = true;
    if (empty($_POST['min_support']) || empty($_POST['min_confidence'])) {
        $can_process = false;
        ?>
        <script> location.replace("?menu=view_rule&pesan_error=Min Support dan Min Confidence harus diisi");</script>
        <?php
    }
    if(!is_numeric($_POST['min_support']) || !is_numeric($_POST['min_confidence'])){
        $can_process = false;
        ?>
        <script> location.replace("?menu=view_rule&pesan_error=Min Support dan Min Confidence harus diisi angka");</script>
        <?php
    }

    if($can_process){
        $id_process = $_POST['id_process'];

        $tgl = explode(" - ", $_POST['range_tanggal']);
        $start = format_date($tgl[0]);
        $end = format_date($tgl[1]);

        echo "Min Support Absolut: " . $_POST['min_support'];
        echo "<br>";
        $sql = "SELECT COUNT(*) FROM transaksi 
        WHERE transaction_date BETWEEN '$start' AND '$end' ";
        $res = $db_object->db_query($sql);
        $num = $db_object->db_fetch_array($res);
        $minSupportRelatif = ($_POST['min_support']/$num[0]) * 100;
        echo "Min Support Relatif: " . $minSupportRelatif;
        echo "<br>";
        echo "Min Confidence: " . $_POST['min_confidence'];
        echo "<br>";
        echo "Start Date: " . $_POST['range_tanggal'];
        echo "<br>";

        //delete hitungan untuk id_process
        reset_hitungan($db_object, $id_process);

        //update log process
        $field = array(
                        "start_date"=>$start,
                        "end_date"=>$end,
                        "min_support"=>$_POST['min_support'],
                        "min_confidence"=>$_POST['min_confidence']
                    );
        $where = array(
                        "id"=>$id_process
                    );
        $query = $db_object->update_record("process_log", $field, $where);

        $result = mining_process($db_object, $_POST['min_support'], $_POST['min_confidence'],
                $start, $end, $id_process);
        if ($result) {
            display_success("Proses mining selesai");
        } else {
            display_error("Gagal mendapatkan aturan asosiasi");
        }

        display_process_hasil_mining($db_object, $id_process);
    }        
} 
else{
    $id_process = 0;
    if(isset($_GET['id_process'])){
        $id_process = $_GET['id_process'];
    }
    // Confidence dari itemset 4
    $sql5 = "SELECT * FROM confidence "
                . " WHERE id_process = ".$id_process
                . " AND from_itemset=4 "
                ;//. " ORDER BY lolos DESC";
    $query5 = $db_object->db_query($sql5);
    $jumlah5 = $db_object->db_num_rows($query5);

    // Confidence dari itemset 3
    $sql = "SELECT
            conf.*, log.start_date, log.end_date
            FROM
             confidence conf, process_log log
            WHERE conf.id_process = '$id_process' "
            . " AND conf.id_process = log.id "
            . " AND conf.from_itemset=3 "
            ;//. " ORDER BY conf.lolos DESC";
    //        echo $sql;
    $query=$db_object->db_query($sql);
    $jumlah=$db_object->db_num_rows($query);


    // Confidence dari itemset 2
    $sql1 = "SELECT
            conf.*, log.start_date, log.end_date
            FROM
             confidence conf, process_log log
            WHERE conf.id_process = '$id_process' "
            . " AND conf.id_process = log.id "
            . " AND conf.from_itemset=2 "
            ;//. " ORDER BY conf.lolos DESC";
    //        echo $sql;
    $query1=$db_object->db_query($sql1);
    $jumlah1=$db_object->db_num_rows($query1);

    $sql_log = "SELECT * FROM process_log
    WHERE id = ".$id_process;
    $res_log = $db_object->db_query($sql_log);
    $row_log = $db_object->db_fetch_array($res_log);
    
//            if($jumlah==0){
//                    echo "Data kosong...";
//            }
//            else{
            
            ?>
            Confidence dari itemset 4
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>X => Y</th>
                <th>Support X U Y</th>
                <th>Support X </th>
                <th>Confidence</th>
                <th>Keterangan</th>
                </tr>
                <?php
                    $no=1;
                    $data_confidence = array();
                    while($row5=$db_object->db_fetch_array($query5)){
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$row5['kombinasi1']." => ".$row5['kombinasi2']."</td>";
                            echo "<td>".price_format($row5['support_xUy'])."</td>";
                            echo "<td>".price_format($row5['support_x'])."</td>";
                            echo "<td>".price_format($row5['confidence'])."</td>";
                            $keterangan = ($row5['confidence'] <= $row5['min_confidence'])?"Tidak Lolos":"Lolos";
                            echo "<td>".$keterangan."</td>";
                        echo "</tr>";
                        $no++;
                        if ($row5['lolos'] == 1) {
                            $data_confidence[] = $row5;
                        }
                        $all_data_confidence[] = $row5;
                    }
                    ?>
            </table>

            Confidence dari itemset 3
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>X => Y</th>
                <th>Support X U Y</th>
                <th>Support X </th>
                <th>Confidence</th>
                <th>Keterangan</th>
                </tr>
                <?php
                    $no=1;
                    // $data_confidence = array();
                    while($row=$db_object->db_fetch_array($query)){
                        
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$row['kombinasi1']." => ".$row['kombinasi2']."</td>";
                            echo "<td>".price_format($row['support_xUy'])."</td>";
                            echo "<td>".price_format($row['support_x'])."</td>";
                            echo "<td>".price_format($row['confidence'])."</td>";
                            $keterangan = ($row['confidence'] <= $row['min_confidence'])?"Tidak Lolos":"Lolos";
                            echo "<td>".$keterangan."</td>";
                        echo "</tr>";
                        $no++;
                        //if($row['confidence']>=$row['min_cofidence']){
                        if($row['lolos']==1){
                        $data_confidence[] = $row;
                        }
                        $all_data_confidence[] = $row;
                    }
                    ?>
            </table>
            
            Confidence dari itemset 2
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>X => Y</th>
                <th>Support X U Y</th>
                <th>Support X </th>
                <th>Confidence</th>
                <th>Keterangan</th>
                </tr>
                <?php
                    $no=1;
                    while($row=$db_object->db_fetch_array($query1)){
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$row['kombinasi1']." => ".$row['kombinasi2']."</td>";
                            echo "<td>".price_format($row['support_xUy'])."</td>";
                            echo "<td>".price_format($row['support_x'])."</td>";
                            echo "<td>".price_format($row['confidence'])."</td>";
                            $keterangan = ($row['confidence'] <= $row['min_confidence'])?"Tidak Lolos":"Lolos";
                            echo "<td>".$keterangan."</td>";
                        echo "</tr>";
                        $no++;
                        //if($row['confidence']>=$row['min_cofidence']){
                        if($row['lolos']==1){
                        $data_confidence[] = $row;
                        }
                        $all_data_confidence[] = $row;
                    }
                    ?>
            </table>
            
            <h2 class="p-0">Rule Asosiasi:</h2>
            <span>Min support: <?php echo $data_confidence[0]['min_support'] . "<br>"; ?></span>
            <span>Min confidence: <?php echo $data_confidence[0]['min_confidence'] . "<br>"; ?></span>
            <span>Start Date: <?php echo $data_confidence[0]['start_date'] . "<br>"; ?></span>
            <span>End Date: <?php echo $data_confidence[0]['end_date'] . "<br>"; ?></span>
            <br>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>X => Y</th>
                <th>Confidence</th>
                <th>Nilai Uji lift</th>
                <th>Korelasi rule</th>
                <!--<th></th>-->
                </tr>
                <?php
                    // confidence is string make the confidence float
                    foreach($data_confidence as $key => $val){
                        $data_confidence[$key]['confidence'] = floatval($val['confidence']);
                    }

                    // sort the confidence desc
                    usort($data_confidence, function($a, $b) {
                        return $b['confidence'] <=> $a['confidence'];
                    });

                    $no=1;
                    //while($row=$db_object->db_fetch_array($query)){
                    foreach($data_confidence as $key => $val){
                        echo "<tr>";
                        echo "<td>" . $no . "</td>";
                        echo "<td>" . $val['kombinasi1']." => ".$val['kombinasi2'] . "</td>";
                        echo "<td>" . price_format($val['confidence']) . "</td>";
                        echo "<td>" . price_format($val['nilai_uji_lift']) . "</td>";
                        echo "<td>" . ($val['korelasi_rule']) . "</td>";
                        //echo "<td>" . ($val['lolos'] == 1 ? "Lolos" : "Tidak Lolos") . "</td>";
                        echo "</tr>";
                        $no++;
                    }
                    ?>
            </table>

            <h2>Nilai Uji Lift:</h2>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>X => Y</th>
                <th>Nilai Uji Lift</th>
                <th>Keterangan</th>
                <!--<th></th>-->
                </tr>
                <?php
                    // confidence is string make the confidence float
                    foreach($all_data_confidence as $key => $val){
                        $all_data_confidence[$key]['nilai_uji_lift'] = floatval($val['nilai_uji_lift']);
                    }

                    // sort the confidence desc
                    usort($all_data_confidence, function($a, $b) {
                        return $b['nilai_uji_lift'] <=> $a['nilai_uji_lift'];
                    });
                    $no=1;
                    foreach($all_data_confidence as $key => $val){
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$val['kombinasi1']." => ".$val['kombinasi2']."</td>";
                            echo "<td>" . price_format($val['nilai_uji_lift']) . "</td>";
                            $keterangan = ($val['nilai_uji_lift'] <= 1)?"Tidak Lolos":"Lolos";
                            echo "<td>".$keterangan."</td>";
                        echo "</tr>";
                        $no++;
                    }
                    ?>
            </table>
            
            
            <h2>Hasil Analisa</h2>
            <a href="export/CLP.php?id_process=<?php echo $id_process; ?>" class="btn btn-app btn-light btn-xs" target="blank">
                <i class="ace-icon fa fa-print bigger-160"></i>
                Print
            </a>
            <br>
            <table class='table table-bordered table-striped  table-hover'>
                <?php
                // confidence is string make the confidence float
                foreach($all_data_confidence as $key => $val){
                    $all_data_confidence[$key]['nilai_uji_lift'] = floatval($val['nilai_uji_lift']);
                }

                // sort the confidence desc
                usort($all_data_confidence, function($a, $b) {
                    return $b['nilai_uji_lift'] <=> $a['nilai_uji_lift'];
                });

                function removeSpaces($inputString) {
                    return str_replace(' ', '', $inputString);
                }

                function ReformatSentence($arr, $key, $tes = null){
                    $arrProduk = array("smallkebab", "syawarma", "burger", "kebab", "hotdog", "kebabsosis", "blackkebab");
                    $arrUmur = array("agelow", "ageold", "agemature");
                    $arrJenisKelamin = array("male", "female");
                    $arrHarga = array("priceabove20000", "pricebelow20000");
                    
                    if(in_array($arr[0], $arrProduk)){
                        // add word "membeli" before produk
                        $arr[0] = "membeli ".$arr[0];
                    }else if(in_array($arr[1], $arrProduk)){
                        // add word "membeli" before produk
                        $arr[1] = "membeli ".$arr[1];
                    }else if(in_array($arr[2], $arrProduk)){
                        // add word "membeli" before produk
                        $arr[2] = "membeli ".$arr[2];
                    }

                    if(in_array($arr[0], $arrUmur)){
                        // add word "berumur" before umur
                        $arr[0] = "berumur ".$arr[0];
                    }else if(in_array($arr[1], $arrUmur)){
                        // add word "berumur" before umur
                        $arr[1] = "berumur ".$arr[1];
                    }else if(in_array($arr[2], $arrUmur)){
                        // add word "berumur" before umur
                        $arr[2] = "berumur ".$arr[2];
                    }

                    if(in_array($arr[0], $arrJenisKelamin)){
                        // add word "berjenis kelamin" before jenis kelamin
                        $arr[0] = "berjenis kelamin ".$arr[0];
                    }else if(in_array($arr[1], $arrJenisKelamin)){
                        // add word "berjenis kelamin" before jenis kelamin
                        $arr[1] = "berjenis kelamin ".$arr[1];
                    }else if(in_array($arr[2], $arrJenisKelamin)){
                        // add word "berjenis kelamin" before jenis kelamin
                        $arr[2] = "berjenis kelamin ".$arr[2];
                    }

                    if(in_array($arr[0], $arrHarga)){
                        // add word "dengan harga" before harga
                        $arr[0] = "memiliki total ".$arr[0];
                    }else if(in_array($arr[1], $arrHarga)){
                        // add word "memiliki total" before harga
                        $arr[1] = "memiliki total ".$arr[1];
                    }else if(in_array($arr[2], $arrHarga)){
                        // add word "memiliki total" before harga
                        $arr[2] = "memiliki total ".$arr[2];
                    }

                    if(count($arr) == 1){
                        $arr = $arr[0];
                    }else if(count($arr) == 2){
                        $arr = $arr[0] . " dan " . $arr[1];
                    }else if(count($arr) == 3){
                        $arr = $arr[0] . ", " . $arr[1] . " dan " . $arr[2];
                    }

                    return $arr;
                }

                // split kombinasi1
                foreach($all_data_confidence as $key => $val){
                    $kombinasi1 = removeSpaces(explode(",", $val['kombinasi1']));
                    $kombinasi1 = ReformatSentence($kombinasi1, $key, 1);
                    $all_data_confidence[$key]['kombinasi1'] = $kombinasi1;

                    $kombinasi2 = removeSpaces(explode(",", $val['kombinasi2']));
                    $kombinasi2 = ReformatSentence($kombinasi2, $key);
                    $all_data_confidence[$key]['kombinasi2'] = $kombinasi2;

                    $all_data_confidence[$key]['lolos'] = ($val['nilai_uji_lift'] <= 1)?0:1;
                }
                    

                $no=1;
                //while($row=$db_object->db_fetch_array($query)){
                foreach($all_data_confidence as $key => $val){
                    if($val['lolos']==1){
                        echo "<tr>";
                        echo "<td>".$no.". Jika konsumen ".$val['kombinasi1'] . ", maka konsumen akan ".$val['kombinasi2'] . "</td>";
                        echo "</tr>";
                    }
                    $no++;
                }
                ?>
            </table>

          
            <?php
            //query itemset 1
            $sql1 = "SELECT
                    *
                    FROM
                     itemset1 
                    WHERE id_process = '$id_process' "
                    . " ORDER BY lolos DESC";
            $query1=$db_object->db_query($sql1);
            $jumlah1=$db_object->db_num_rows($query1);
            $itemset1 = $jumlahItemset1 = $supportItemset1 = array();
            ?>
            <hr>
            <h3>Perhitungan</h3>
            <strong>Itemset 1:</strong></br>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>Item 1</th>
                <th>Jumlah</th>
                <th>Support</th>
                <th>Keterangan</th>
                </tr>
                <?php
                $no=1;
                    while($row1=$db_object->db_fetch_array($query1)){
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$row1['atribut']."</td>";
                            echo "<td>".$row1['jumlah']."</td>";
                            echo "<td>".price_format($row1['support'])."</td>";
                            echo "<td>".($row1['lolos']==1?"Lolos":"Tidak Lolos")."</td>";
                        echo "</tr>";
                        $no++;
                        if($row1['lolos']==1){
                            $itemset1[] = $row1['atribut'];//item yg lolos itemset1
                            $jumlahItemset1[] = $row1['jumlah'];
                            $supportItemset1[] = price_format($row1['support']);
                        }
                    }
                    ?>
            </table>
            <?php      
            //display itemset yg lolos
            echo "<br><strong>Itemset 1 yang lolos:</strong><br>";
            echo "<table class='table table-bordered table-striped  table-hover'>
                    <tr>
                        <th>No</th>
                        <th>Item</th>
                        <th>Jumlah</th>
                        <th>Suppport</th>
                    </tr>";
            $no=1;
            foreach ($itemset1 as $key => $value) {
                echo "<tr>";
                echo "<td>" . $no . "</td>";
                echo "<td>" . $value . "</td>";
                echo "<td>" . $jumlahItemset1[$key] . "</td>";
                echo "<td>" . $supportItemset1[$key] . "</td>";
                echo "</tr>";
                $no++;
            }
            echo "</table>";
            ?>
            
            
            <?php
            //query itemset 2
            $sql2 = "SELECT
                    *
                    FROM
                     itemset2 
                    WHERE id_process = '$id_process' "
                    . " ORDER BY lolos DESC";
            $query2=$db_object->db_query($sql2);
            $jumlah2=$db_object->db_num_rows($query2);
            $itemset2_var1 = $itemset2_var2 = $jumlahItemset2 = $supportItemset2 = array();
            ?>
            <hr>
            <strong>Itemset 2:</strong></br>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>Item 1</th>
                <th>Item 2</th>
                <th>Jumlah</th>
                <th>Support</th>
                <th>Keterangan</th>
                </tr>
                <?php
                $no=1;
                while($row2=$db_object->db_fetch_array($query2)){
                        echo "<tr>";
                        echo "<td>".$no."</td>";
                        echo "<td>".$row2['atribut1']."</td>";
                        echo "<td>".$row2['atribut2']."</td>";
                        echo "<td>".$row2['jumlah']."</td>";
                        echo "<td>".price_format($row2['support'])."</td>";
                        echo "<td>".($row2['lolos']==1?"Lolos":"Tidak Lolos")."</td>";
                    echo "</tr>";
                    $no++;
                    if($row2['lolos']==1){
                        $itemset2_var1[] = $row2['atribut1'];
                        $itemset2_var2[] = $row2['atribut2'];
                        $jumlahItemset2[] = $row2['jumlah'];
                        $supportItemset2[] = price_format($row2['support']);
                    }
                }
                ?>
            </table>
            
            <?php
            //display itemset yg lolos
            echo "<br><strong>Itemset 2 yang lolos:</strong><br>";
            echo "<table class='table table-bordered table-striped  table-hover'>
                    <tr>
                        <th>No</th>
                        <th>Item 1</th>
                        <th>Item 2</th>
                        <th>Jumlah</th>
                        <th>Suppport</th>
                    </tr>";
            $no=1;
            foreach ($itemset2_var1 as $key => $value) {
                echo "<tr>";
                echo "<td>" . $no . "</td>";
                echo "<td>" . $value . "</td>";
                echo "<td>" . $itemset2_var2[$key] . "</td>";
                echo "<td>" . $jumlahItemset2[$key] . "</td>";
                echo "<td>" . $supportItemset2[$key] . "</td>";
                echo "</tr>";
                $no++;
            }
            echo "</table>";
            ?>
            
           <?php
            //query itemset 3
            $sql3 = "SELECT
                    *
                    FROM
                     itemset3 
                    WHERE id_process = '$id_process' "
                    . " ORDER BY lolos DESC";
            $query3=$db_object->db_query($sql3);
            $jumlah3=$db_object->db_num_rows($query3);
            $itemset3_var1 = $itemset3_var2 = $itemset3_var3 = $jumlahItemset3 = $supportItemset3 = array();
            ?>
            <hr>
            <strong>Itemset 3:</strong></br>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>Item 1</th>
                <th>Item 2</th>
                <th>Item 3</th>
                <th>Jumlah</th>
                <th>Support</th>
                <th>Keterangan</th>
                </tr>
                <?php
                $no=1;
                    while($row3=$db_object->db_fetch_array($query3)){
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$row3['atribut1']."</td>";
                            echo "<td>".$row3['atribut2']."</td>";
                            echo "<td>".$row3['atribut3']."</td>";
                            echo "<td>".$row3['jumlah']."</td>";
                            echo "<td>".price_format($row3['support'])."</td>";
                            echo "<td>".($row3['lolos']==1?"Lolos":"Tidak Lolos")."</td>";
                        echo "</tr>";
                        $no++;
                        if($row3['lolos']==1){
                            $itemset3_var1[] = $row3['atribut1'];
                            $itemset3_var2[] = $row3['atribut2'];
                            $itemset3_var3[] = $row3['atribut3'];
                            $jumlahItemset3[] = $row3['jumlah'];
                            $supportItemset3[] = $row3['support'];
                        }
                    }
                    ?>
            </table>
            
            <?php
            //display itemset yg lolos
            echo "<br><strong>Itemset 3 yang lolos:</strong><br>";
            echo "<table class='table table-bordered table-striped  table-hover'>
                    <tr>
                        <th>No</th>
                        <th>Item 1</th>
                        <th>Item 2</th>
                        <th>Item 3</th>
                        <th>Jumlah</th>
                        <th>Suppport</th>
                    </tr>";
            $no=1;
            foreach ($itemset3_var1 as $key => $value) {
                echo "<tr>";
                echo "<td>" . $no . "</td>";
                echo "<td>" . $value . "</td>";
                echo "<td>" . $itemset3_var2[$key] . "</td>";
                echo "<td>" . $itemset3_var3[$key] . "</td>";
                echo "<td>" . $jumlahItemset3[$key] . "</td>";
                echo "<td>" . $supportItemset3[$key] . "</td>";
                echo "</tr>";
                $no++;
            }
            echo "</table>";
            ?>

            <?php
            //query itemset 4
            $sql4 = "SELECT
                    *
                    FROM
                     itemset4 
                    WHERE id_process = '$id_process' "
                    . " ORDER BY lolos DESC";
            $query4=$db_object->db_query($sql4);
            $jumlah4=$db_object->db_num_rows($query4);
            $itemset4_var1 = $itemset4_var2 = $itemset4_var3 = $itemset4_var4 = $jumlahItemset4 = $supportItemset4 = array();
            ?>

            <hr>
            <strong>Itemset 4:</strong></br>
            <table class='table table-bordered table-striped  table-hover'>
                <tr>
                <th>No</th>
                <th>Item 1</th>
                <th>Item 2</th>
                <th>Item 3</th>
                <th>Item 4</th>
                <th>Jumlah</th>
                <th>Support</th>
                <th>Keterangan</th>
                </tr>Rule Asosiasi yang terbentukt:
                <?php
                $no=1;
                    while($row4=$db_object->db_fetch_array($query4)){
                            echo "<tr>";
                            echo "<td>".$no."</td>";
                            echo "<td>".$row4['atribut1']."</td>";
                            echo "<td>".$row4['atribut2']."</td>";
                            echo "<td>".$row4['atribut3']."</td>";
                            echo "<td>".$row4['atribut4']."</td>";
                            echo "<td>".$row4['jumlah']."</td>";
                            echo "<td>".price_format($row4['support'])."</td>";
                            echo "<td>".($row4['lolos']==1?"Lolos":"Tidak Lolos")."</td>";
                        echo "</tr>";
                        $no++;
                        if($row4['lolos']==1){
                            $itemset4_var1[] = $row4['atribut1'];
                            $itemset4_var2[] = $row4['atribut2'];
                            $itemset4_var3[] = $row4['atribut3'];
                            $itemset4_var4[] = $row4['atribut4'];
                            $jumlahItemset4[] = $row4['jumlah'];
                            $supportItemset4[] = $row4['support'];
                        }
                    }
                    ?>
            </table>


             <?php
            //display itemset yg lolos
            echo "<br><strong>Itemset 4 yang lolos:</strong><br>";
            echo "<table class='table table-bordered table-striped  table-hover'>
                    <tr>
                        <th>No</th>
                        <th>Item 1</th>
                        <th>Item 2</th>
                        <th>Item 3</th>
                        <th>Item 4</th>
                        <th>Jumlah</th>
                        <th>Suppport</th>
                    </tr>";
            $no=1;
            foreach ($itemset4_var1 as $key => $value) {
                echo "<tr>";
                echo "<td>" . $no . "</td>";
                echo "<td>" . $value . "</td>";
                echo "<td>" . $itemset4_var2[$key] . "</td>";
                echo "<td>" . $itemset4_var3[$key] . "</td>";
                echo "<td>" . $itemset4_var4[$key] . "</td>";
                echo "<td>" . $jumlahItemset4[$key] . "</td>";
                echo "<td>" . $supportItemset4[$key] . "</td>";
                echo "</tr>";
                $no++;
            }
            echo "</table>";
            ?>
            
            
            <?php
            //}
            ?>
        
<?php
}
?>


                </div>
            </div>
        </div>
    </div>
</div>