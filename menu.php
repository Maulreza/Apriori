<?php
$menu_active = '';
if (isset($_GET['menu'])) {
    $menu_active = $_GET['menu'];
}
?>

<div id="sidebar" class="sidebar                  responsive                    ace-save-state">
    <script type="text/javascript">
        try {
            ace.settings.loadState('sidebar')
        } catch (e) {
        }
    </script>
    <ul class="nav nav-list">
        <li <?php echo ($menu_active == '') ? "class='active'" : ""; ?> >
            <a href="index.php">
                <i class="menu-icon fa fa-home"></i>
                <span class="menu-text"> Halaman Utama </span>
            </a>
            <b class="arrow"></b>
        </li>

        <li <?php echo ($menu_active == 'data_mentah') ? "class='active'" : ""; ?> >
            <a href="index.php?menu=data_mentah">
                <i class="menu-icon fa fa-table"></i>
                <span class="menu-text"> Data Mentah </span>
            </a>
            <b class="arrow"></b>
        </li>

        <li <?php echo ($menu_active == 'data_transaksi') ? "class='active'" : ""; ?> >
            <a href="index.php?menu=data_transaksi">
                <i class="menu-icon fa fa-table"></i>
                <span class="menu-text"> Data Transaksi </span>
            </a>
            <b class="arrow"></b>
        </li>

        <li <?php echo ($menu_active == 'proses_apriori') ? "class='active'" : ""; ?>  >
            <a href="index.php?menu=proses_apriori">
                <i class="menu-icon fa fa-bolt"></i>
                <span class="menu-text"> Proses Apriori </span>
            </a>
            <b class="arrow"></b>
        </li>

        <li <?php echo ($menu_active == 'hasil') ? "class='active'" : ""; ?>  >
            <a href="index.php?menu=hasil">
                <i class="menu-icon fa fa-book"></i>
                <span class="menu-text"> Hasil </span>
            </a>
            <b class="arrow"></b>
        </li>
        <!-- Log out -->
        <li>
            <a href="logout.php">
                <i class="menu-icon fa fa-power-off"></i>
                <span class="menu-text"> Log Out </span>
            </a>
            <b class="arrow"></b>
        </li>
    </ul><!-- /.nav-list -->

    <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
        <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" 
           data-icon1="ace-icon fa fa-angle-double-left" 
           data-icon2="ace-icon fa fa-angle-double-right"></i>
    </div>
</div>