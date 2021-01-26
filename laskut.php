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
while($row = pg_fetch_row($result)) 
    echo "<option value= \"" . $row[0] . "\"> id:". $row[0] . " / " . $row[6] . " / " . $row[3] . "</option>";

$projects = ob_get_clean();

$projectsList = explode($separator, $projects);

echo '<style type="text/css">
#bills{ display: none; }
</style>';
?>

<html>

<style>
@media print {
  .hide-on-print{
      display: none;
  }
}
</style>

<head>
  <title>Lasku</title>
</head>
<body>
    <a href="etusivu.php" class="hide-on-print">Etusivu</a><br>
    <span class="hide-on-print">Valitse projekti, jonka laskuja haluat tarkastella</span>
    <br>
    
    <form method="post" class="hide-on-print">
    <select name="projects" id="projects">
        <?php echo $projectsList[0]; ?>
    </select>
    <button type="submit" name="getProjectInfo" >Hae</button>
    </form>
    <?php



    if(isset($_POST['getProjectInfo'])){
        // Tallennetaan valittu projektiId
        $project_id = $_POST['projects'];

        
        $billRes = pg_query("SELECT id, eräpäivä, edellinen_lasku, lasku_tyyppi FROM lasku, laskutiedot WHERE lasku.id = lasku_id AND projekti_id = $project_id");

        // Tallennetaan kaikki projektiin liittyvät laskut taulukkoon
        $bills = array();
        $row = array();

        while($row = pg_fetch_row($billRes)){

            $rowData = array();
            for($i = 0; $i < count($row); $i++){
                array_push($rowData, $row[$i]);
            }
            array_push($bills, $rowData);
        }

        // Käydään taulukko läpi ja poistetaan laskut, joista on olemassa myöhempi versio
        // Poistetaan muistutuslaskujen sekä karhulaskujen "edellinen_lasku"
        for($i = 0; $i < count($bills); $i++){
            if($bills[$i][3] == "muistutus" || $bills[$i][3] == "karhu"){

                for($j = 0; $j < count($bills); $j ++){
                    if($bills[$j][0] == $bills[$i][2]){
                        array_splice($bills, $j, 1);
                        $i--;
                        break;
                    }
                }
            }
        }

        // Laskut pudotusvalikkoon
        ob_start();
        for($i = 0; $i < count($bills); $i++){
            $due_date = $bills[$i][1] ? $bills[$i][1] : "-";

            echo "<option value= \"" . $bills[$i][0] . "\"> id:". $bills[$i][0] . " / eräpäivä: " . $due_date . " / " .  $bills[$i][3] . "</option>";
        }

        $billList = ob_get_clean();

        $billList = explode($separator, $billList);
        $billListSpan = "<span>Laskut projektille $project_id </span>";

        echo '<style type="text/css">
            #bills{ display: inline; }
        </style>';

    }

    ?>

    <form method="post" id="bills">

        <?php echo $billListSpan; ?>
        <br/>
    <select name="bills" >
        <?php echo $billList[0]; ?>
    </select>
    <button type="submit" name="getBillInfo" >Hae</button>
    </form>


    <?php
    if(isset($_POST['getBillInfo'])){
        // Tallennetaan valittu laskuId
        $bill_id = $_POST['bills'];

        
        $billInfo = pg_fetch_row(pg_query("SELECT * FROM lasku WHERE lasku.id = $bill_id"));

        $laskutiedot = pg_fetch_row(pg_query("SELECT projekti_id FROM laskutiedot WHERE lasku_id = $bill_id"));
        $projekti = pg_fetch_row(pg_query("SELECT työkohde_id FROM projekti WHERE id = $laskutiedot[0]"));
        $työkohde = pg_fetch_row(pg_query("SELECT asiakas_id FROM työkohde WHERE id = $projekti[0]"));
        $asiakas = pg_fetch_row(pg_query("SELECT nimi, osoite FROM asiakas WHERE id = $työkohde[0]"));

        echo "<table border=2>";
        echo "<tr><th>ID</th><td>{$billInfo[0]}</td></tr>";
        echo "<tr><th>Eräpäivä</th><td>{$billInfo[1]}</td></tr>";
        echo "<tr><th>Päivämäärä</th><td>{$billInfo[2]}</td></tr>";
        echo "<tr><th>Maksupäivä</th><td>{$billInfo[3]}</td></tr>";
        echo "<tr><th>Asiakkaan nimi</th><td>{$asiakas[0]}</td></tr>";
        echo "<tr><th>Asiakkaan osoite</th><td>{$asiakas[1]}</td></tr>";
        echo "<tr><th>Työkohteen osoite</th><td>{$billInfo[4]}</td></tr>";
        echo "<tr><th>Projektin tyyppi</th><td>{$billInfo[5]}</td></tr>";
        echo "<tr><th>Työn alkuperäinen hinta</th><td>{$billInfo[6]}</td></tr>";
        echo "<tr><th>Tarvikkeiden alkuperäinen hinta</th><td>{$billInfo[7]}</td></tr>";
        echo "<tr><th>Tuntien määrä</th><td>{$billInfo[8]}</td></tr>";
        echo "<tr><th>Kotitalousvähennys</th><td>{$billInfo[9]}</td></tr>";
        echo "<tr><th>ALV-osuus</th><td>{$billInfo[10]}</td></tr>";
        echo "<tr><th>Alennus tarvikkeista</th><td>{$billInfo[11]}</td></tr>";
        echo "<tr><th>Alennus tuntityöstä</th><td>{$billInfo[12]}</td></tr>";
        echo "<tr><th>Työn alennettu hinta</th><td>{$billInfo[13]}</td></tr>";
        echo "<tr><th>Tarvikkeiden alennettu hinta</th><td>{$billInfo[14]}</td></tr>";
        echo "<tr><th>Osien lukumäärä</th><td>{$billInfo[15]}</td></tr>";
        echo "<tr><th>Osanumero</th><td>{$billInfo[16]}</td></tr>";
        echo "<tr><th>Loppusumma</th><td>{$billInfo[17]}</td></tr>";
        echo "<tr><th>Loppusumma alennuksineen</th><td>{$billInfo[18]}</td></tr>";
        echo "<tr><th>Alkuperäinen loppusumma</th><td>{$billInfo[19]}</td></tr>";
        echo "<tr class='hide-on-print'><th>Edellisen laskun ID</th><td>{$billInfo[20]}</td></tr>";
        echo "<tr><th>Laskun tyyppi</th><td>{$billInfo[21]}</td></tr>";
        echo "</table";
    }
    ?>
</body>
</html>

