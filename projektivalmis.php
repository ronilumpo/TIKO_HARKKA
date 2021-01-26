<?php

$conn_info = "";
$f = fopen("tietokanta.txt", "r");
while(!feof($f)){
    $conn_info = fgets($f);
}


if (!$connection = pg_connect($conn_info))
   die("Connection error");

// Tietokannassa olevat projektit
$sql = "SELECT * 
        FROM projekti, työkohde
        WHERE työkohde_id = työkohde.id
       ";
$result = pg_query($sql);

// Projektit pudotusvalikkoon
$separator = "\n";
ob_start();
while($row = pg_fetch_row($result)) {
    echo "<option value= \"" . $row[0] . "\"> id:". $row[0] . " / " . $row[6] . " / " . $row[3] . "</option>";
}

$projects = ob_get_clean();

$projectsList = explode($separator, $projects);

echo '<style type="text/css">
</style>';
?>

<html>

<head>
  <title>Projektin viimeistely</title>
</head>
<body>

    <?php
        
        if(isset($_POST['getProjectInfo'])){
            // Tallennetaan valittu projektiId
            $project_id = $_POST['projects'];

            $billRes = pg_query("SELECT id, projekti_tyyppi, alennus_tuntityö, alennus_tarvikkeet, maksupäivä  FROM lasku, laskutiedot 
            WHERE lasku.id = lasku_id AND projekti_id = $project_id");

            $date = date("Y-m-d", strtotime("+30 days"));
            $new_date = pg_escape_string($date);

            while($bill = pg_fetch_row($billRes)){
                if($bill[1] == 'tuntityö' && !$bill[4]){
                    $query = '';
                    $query .= ("UPDATE lasku SET eräpäivä = '$new_date' WHERE id = $bill[0];");

                    $alv = 0;

                    $hours = 0;
                    $paid_work_dolas = 0;
                    $paid_work_original = 0;

                    $hourInfo = pg_query("SELECT * FROM työluettelo, työhinnasto where lasku_id = $bill[0] and id = työ_id");
                    while($work = pg_fetch_row($hourInfo)){
                        $hours += $work[2];
                        $paid_work_dolas += $work[2] * $work[6] - $work[2] * $work[6] / 1.24 * ($work[3] / 100);
                        $paid_work_original += $work[2] * $work[6];
                        $alv += $work[2] * $work[6] * 0.24;
                    }
                    $ktv = $paid_work_dolas * 0.4;
                    $paid_work_dolas_discounted = $paid_work_dolas * ((100 - $bill[2]) / 100);


                    $paid_item_dolas = 0;
                    $paid_item_dolas_original = 0;

                    $itemInfo = pg_query("SELECT * FROM tarvikeluettelo, tarvike where lasku_id = $bill[0] and id = tarvike_id");
                    while($item = pg_fetch_row($itemInfo)){
                        $paid_item_dolas += $item[2] * $item[8] - $item[2] * $item[8] / (1 + $item[10])  * ($item[3] / 100);
                        $paid_item_dolas_original += $item[2] * $item[8];
                        $alv += $item[2] * $item[8] * $item[10];
                    }
                    $paid_item_dolas_discounted = $paid_item_dolas * ((100 - $bill[3]) / 100);
                    
                    $total_discounted = $paid_work_dolas_discounted + $paid_item_dolas_discounted;
                    $total_original = $paid_work_dolas_discounted + $paid_item_dolas_discounted;
                    $total_original_not_discounted = $paid_work_original + $paid_item_dolas_original;

                    $query .= ("UPDATE lasku SET työ_hinta_alkup = $paid_work_original WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET tarvikkeet_hinta_alkup = $paid_item_dolas_original WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET tunnit = $hours WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET kotitalousvähennys = $ktv WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET alv_osuus = $alv WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET työ_hinta_alennettu = $paid_work_dolas_discounted WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET tarvikkeet_hinta_alennettu = $paid_item_dolas_discounted WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET loppusumma = $total_original_not_discounted WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET loppusumma_alennettu = $total_discounted WHERE id = $bill[0];");
                    $query .= ("UPDATE lasku SET loppusumma_alkuperäinen = $total_original WHERE id = $bill[0];");

                    $result = pg_query($query);

                    if($result) $viesti = "Laskun viimeistely onnistui!";
                    else $viesti = "Laskun viimeistely ei onnistunut!";
                }

                if($bill[1] == 'urakka' && !$bill[4]){

                    $result = pg_query("UPDATE lasku SET eräpäivä = '$new_date' WHERE id = $bill[0]");

                    $date = date('Y-m-d', strtotime($date. ' + 1 months'));
                    $new_date = pg_escape_string($date);

                    if($result) $viesti = "Laskun viimeistely onnistui!";
                    else $viesti = "Laskun viimeistely ei onnistunut!";
                }
            }
            

            
            /* $date = date("Y-m-d", strtotime("+30 days"));
            $new_date = pg_escape_string($date);
            pg_query("UPDATE lasku SET eräpäivä = '$new_date' WHERE id = '$billInfo[0]'");
           
            
            $workSql = "SELECT * FROM työluettelo WHERE lasku_id = $billInfo[0]";
            $work = pg_query($workSql);
            $pricesSql = "SELECT * FROM työhinnasto";
            $prices = pg_query($pricesSql);
            
            $suunnittelu = 0;
            $tyo = 0;
            $aputyo = 0;
            //päivitä hintamuuttujat
            $round = 0;
            while($row = pg_fetch_row($prices)) {
                if ($round == 0) {
                    $suunnittelu = $row[2];
                }
                else if ($round == 1) {
                    $tyo = $row[2];
                }
                else if ($round == 2) {
                    $aputyo = $row[2];
                } 
                else {
                    $suunnittelu = 100;
                    $tyo = 100;
                    $aputyo = 100;
                }
                $round = $round + 1;
            };

            
            $original_price = 0.0;
            $p_discount = 0.0;
            $discount_price = 0.0;
            $tunnit = 0;
            //päivitä hintamuuttujat
            while($row = pg_fetch_row($work)) {
                $tunnit = $tunnit + $row[2];
                if ($row[1] == 1) {
                    $original_price = $original_price + $row[2]*$suunnittelu;
                    $p_discount = $p_discount + $row[2]*$suunnittelu*$row[3];
                    $discount_price = $discount_price + $row[2]*$suunnittelu*(1-$row[3]);
                }
                else if ($row[1] == 2) {
                    $original_price = $original_price + $row[2]*$tyo;
                    $p_discount = $p_discount + $row[2]*$tyo*$row[3];
                    $discount_price = $discount_price + $row[2]*$tyo*(1-$row[3]);
                }
                else if ($row[1] == 3) {
                    $original_price = $original_price + $row[2]*$aputyo;
                    $p_discount = $p_sicount + $row[2]*$aputyo*$row[3];
                    $discount_price = $discount_price + $row[2]*$aputyo*(1-$row[3]);
                }
            }
            
            $original_price = doubleval($original_price);
            $p_discount = doubleval($p_discount);
            $discount_price = doubleval($discount_price);
            $tunnit = intval($tunnit);

            $ktv = $discount_price * 0.40;

            pg_query("UPDATE lasku SET työ_hinta_alkup = '$original_price',
            alennus_tuntityö = '$p_discount', työ_hinta_alennettu = '$discount_price',
            tunnit = '$tunnit', kotitalousvähennys = '$ktv' WHERE id = '$billInfo[0]'");
            
            ///
            //TÄHÄN ASTI TOIMII!!!
            ///
            
            //päivitetään tarvikkeiden hinnat
            $stuffSql = "SELECT * FROM tarvikeluettelo WHERE lasku_id = '$billInfo[0]'";
            $stuff = pg_query($stuffSql);
            $stuff_pricesSql = "SELECT * FROM tarvike";
            $stuff_prices = pg_query($stuff_pricesSql);

            $s_original_price = 0.0;
            $s_discount = 0.0;
            $s_discount_price = 0.0;
            //päivitä hintamuuttujat
            while($row = pg_fetch_row($stuff)) {
                while($item = pg_fetch_row($stuff_prices)) {
                    if ($row[1] == $item[0]) {
                        $s_original_price = doubleval($s_original_price + $row[2]*$item[4]);
                        $s_discount = doubleval($s_discount + $row[2]*$item[4]*$row[3]);
                        $s_discount_price = doubleval($s_discount_price + $row[2]*$item[4]*(1-$row[3]));
                    }
                    
                }
            }
            $s_original_price = doubleval($s_original_price);
            $s_discount = doubleval($s_discount);
            $s_discount_price = doubleval($s_discount_price);

            $end_price = doubleval($original_price + $s_original_price);
            $end_discount_price = doubleval($original_price + $s_original_price - $p_discount - $s_discount);

            $update = pg_query("UPDATE lasku SET tarvikkeet_hinta_alkup = '$s_original_price',
            alennus_tarvikkeet = '$s_discount', tarvikkeet_hinta_alennettu = '$s_discount_price', loppusumma = '$end_price',
            loppusumma_alennettu = '$end_discount_price', loppusumma_alkuperäinen = '$end_price' WHERE id = '$billInfo[0]'");
 */
            /*
            pg_query("UPDATE lasku SET loppusumma = '$end_price',
            loppusumma_alennettu = '$end_discount_price', loppusumma_alkuperäinen = '$end_price' WHERE id = '$billInfo[0]'");*/
            
        }
            
    ?>

    <a href="etusivu.php">Etusivu</a><br>
    <span>Valitse projekti, jonka laskun haluat muodostaa</span>
    <br>
    
    <form method="post">
    <select name="projects" id="projects">
        <?php echo $projectsList[0]; ?>
    </select>
    <br><br>
    <button type="submit" name="getProjectInfo" >Viimeistele projekti</button>
    </form>

    <?php 
    if (isset($viesti)) {
        if ($viesti == 'Laskun viimeistely onnistui!') {
            echo '<p style="color:green">'.$viesti.'</p>';
        }
        else {
            echo '<p style="color:red">'.$viesti.'</p>';
        }
    }
    
    ?>

    
</body>
</html>
