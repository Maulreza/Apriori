<?php
// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['apriori_parfum_id'])) {
    header("Location: index.php?menu=forbidden");
    exit;
}

include_once "database.php";
include_once "fungsi.php";
include_once "mining.php";
include_once "display_mining.php";

// Initialize database object
$db_object = new database();

// Get error or success messages if set
$pesan_error = $_GET['pesan_error'] ?? "";
$pesan_success = $_GET['pesan_success'] ?? "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $can_process = true;
    $min_support = $_POST['min_support'] ?? null;
    $min_confidence = $_POST['min_confidence'] ?? null;

    if (empty($min_support) || empty($min_confidence)) {
        header("Location: ?menu=proses_apriori&pesan_error=Min Support dan Min Confidence harus diisi");
        exit;
    }

    if (!is_numeric($min_support) || !is_numeric($min_confidence)) {
        header("Location: ?menu=proses_apriori&pesan_error=Min Support dan Min Confidence harus diisi angka");
        exit;
    }

    // Ensure min support and min confidence are between 0 and 100
    if ($min_support < 0 || $min_support > 100 || $min_confidence < 0 || $min_confidence > 100) {
        header("Location: ?menu=proses_apriori&pesan_error=Min Support dan Min Confidence harus diisi diantara 0 - 100");
        exit;
    }

    $tgl = explode(" - ", $_POST['range_tanggal']);
    $start = format_date($tgl[0]);
    $end = format_date($tgl[1]);

    if ($can_process) {
        if (isset($_POST['id_process'])) {
            $id_process = $_POST['id_process'];
            reset_hitungan($db_object, $id_process);

            $field = [
                "start_date" => $start,
                "end_date" => $end,
                "min_support" => $min_support,
                "min_confidence" => $min_confidence,
            ];
            $where = ["id" => $id_process];
            $db_object->update_record("process_log", $field, $where);
        } else {
            $field_value = [
                "start_date" => $start,
                "end_date" => $end,
                "min_support" => $min_support,
                "min_confidence" => $min_confidence,
            ];
            $db_object->insert_record("process_log", $field_value);
            $id_process = $db_object->db_insert_id();
        }

        // Execute mining process and display results
        $result = mining_process($db_object, $min_support, $min_confidence, $start, $end, $id_process);
        if ($result) {
            display_success("Proses mining selesai");
        } else {
            display_error("Gagal mendapatkan aturan asosiasi");
        }

        display_process_hasil_mining($db_object, $id_process);
    }
} else {
    $where = "WHERE 1=1";
    if (!empty($_POST['range_tanggal'])) {
        $tgl = explode(" - ", $_POST['range_tanggal']);
        $start = format_date($tgl[0]);
        $end = format_date($tgl[1]);
        $where = " WHERE transaction_date BETWEEN '$start' AND '$end'";
    }

    $sql = "SELECT * FROM transaksi $where";
    $query = $db_object->db_query($sql);
    $jumlah = $db_object->db_num_rows($query);
?>
<div class="main-content">
    <div class="main-content-inner">
        <div class="page-content">
            <div class="page-header">
                <h1>Proses Apriori</h1>
            </div>
            <form method="post" action="">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label>Tanggal:</label>
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <input type="text" class="form-control pull-right" name="range_tanggal"
                                       id="id-date-range-picker-1" required="" placeholder="Date range"
                                       value="<?php echo htmlspecialchars($_POST['range_tanggal'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <input name="search_display" type="submit" value="Search" class="btn btn-default">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label>Min Support:</label>
                            <input name="min_support" type="text" class="form-control" 
                                   placeholder="Min Support" value="<?php echo htmlspecialchars($min_support); ?>">
                        </div>
                        <div class="form-group">
                            <label>Min Confidence:</label>
                            <input name="min_confidence" type="text" class="form-control" 
                                   placeholder="Min Confidence" value="<?php echo htmlspecialchars($min_confidence); ?>">
                        </div>
                        <div class="form-group">
                            <input name="submit" type="submit" value="Proses" class="btn btn-success">
                        </div>
                    </div>
                </div>
            </form>

            <?php
            if ($jumlah === 0) {
                echo "Data kosong...";
            } else {
                echo "<table class='table table-bordered table-striped table-hover'>";
                echo "<tr><th>No</th><th>Tanggal</th><th>Produk</th></tr>";
                $no = 1;
                while ($row = $db_object->db_fetch_array($query)) {
                    echo "<tr><td>{$no}</td><td>{$row['transaction_date']}</td><td>{$row['produk']}</td></tr>";
                    $no++;
                }
                echo "</table>";
            }
            ?>
        </div>
    </div>
</div>
<?php
}
?>