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
?>

<html>

<style>
@media print {
  button {
    display: none;
    
  }
  .hide-on-print{
      display: none;
  }
}
</style>

<head>
  <title>Kulut</title>
</head>
<body>
    <a href="etusivu.php" class="hide-on-print">Etusivu</a><br>
    <span class="hide-on-print">Valitse projekti, johon haluat lisätä kuluja</span>
    <br>
    
    <form method="post" class="hide-on-print">
    <select name="projects" id="projects">
        <?php echo $projectsList[0]; ?>
    </select>
    <button type="submit" name="getProjectInfo" >Hae</button>
    </form>
    <?php


    function createLists($type, $tableName, $tableListName, $billId, $prType){
        // Tarvikkeet / Työt
        $thingSql = "SELECT * FROM " . $tableName;
        $thingRes = pg_query($thingSql);


        $usedThingsSql = "SELECT * FROM " . $tableListName . "
                        WHERE lasku_id = " . $billId;
        $usedThingsRes = pg_query($usedThingsSql);

        $usedThings = array();

        while($row = pg_fetch_row($usedThingsRes)) {
            array_push($usedThings, array($row[1], $row[2], $row[3]));
        }

        $itemTr = "<th>Id</th>
        <th>Nimi</th>
        <th>Yksikkö</th>";

        if($prType == 'tuntityö')
            $itemTr .= "<th>Myyntihinta</th><th>Alennusprosentti</th>";
        $itemTr .= "<th>Kappalemäärä</th>";

        $workTr = "<th>Id</th>
        <th>Nimi</th>
        <th>Tuntihinta</th>
        <th>Alennusprosentti</th>
        <th>Tuntimäärä</th>";

        $thingTable = "<table>
        <input type=\"hidden\" value=" . $billId . " name=\"billId\">
        <tr>";
            
        if($type == "item") $thingTable .= $itemTr;
        if($type == "work") $thingTable .= $workTr;
    
        $thingTable .= "</tr>";

        ob_start();
        while($row = pg_fetch_row($thingRes)) {
            if($prType == 'tuntityö'){
                if($type == 'item')
                    echo "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td>" . $row[4] . "</td><td><input type=\"text\" name=\"item-discount-". $row[0] ."\"  value=".checkDiscount($usedThings, $row[0])."> </td><td> <input type=\"text\" name=\"item-amount-". $row[0] ."\"  value=".checkAmount($usedThings, $row[0])."> </td></tr>";
                if($type == 'work')
                    echo "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td><input type=\"text\" name=\"work-discount-". $row[0] ."\"  value=".checkDiscount($usedThings, $row[0])."> </td><td> <input type=\"text\" name=\"work-amount-". $row[0] ."\"  value=".checkAmount($usedThings, $row[0])."> </td></tr>";

            }
            if($prType == 'urakka'){
                if($type == "item")
                    echo "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td> <input type=\"text\" name=\"item-amount-". $row[0] ."\"  value=".checkAmount($usedThings, $row[0])."> </td></tr>";
                if($type == "work")
                    echo "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td><td>" . $row[2] . "</td><td> <input type=\"text\" name=\"work-amount-". $row[0] ."\"  value=".checkAmount($usedThings, $row[0])."> </td></tr>";
            }
        }

        $things = ob_get_clean();

        // Lisätään valmistetut tr-elementit taulukkoon
        $thingTable .= $things;
        $thingTable .= "</table>";

        // Päivitysnappula
        if($type == "item")
            $thingTable .= "<button type=\"submit\" name=\"updateItems\" >Päivitä tarvikkeet</button>";
        if($type == "work")
            $thingTable .= "<button type=\"submit\" name=\"updateWorks\" >Päivitä työtunnit</button>";

        return $thingTable;
    }


    if(isset($_POST['getProjectInfo'])){
        // Tallennetaan valittu projekti
        $value = $_POST['projects'];


        $prType = pg_fetch_row(pg_query("SELECT * FROM projekti WHERE id = $value"))[2];

        // Haetaan projektin laskun tiedot
        // Oletetaan ensimmäisen laskun olevan haluttu
        $billSql = "SELECT * FROM lasku
                    WHERE id in (   SELECT lasku_id 
                                    FROM laskutiedot 
                                    WHERE projekti_id = " . $value .")
                    ;";

        $billRes = pg_fetch_row(pg_query($billSql));



        if($billRes[0] != null){
            $itemTable = createLists("item", "tarvike", "tarvikeluettelo", $billRes[0], $prType);
            $workTable = createLists("work", "työhinnasto", "työluettelo", $billRes[0], $prType);

            if($prType == "urakka") $workTable = "";
        }
        else{
            $itemTable = "Ei tuloksia";
        }

    }

    // Löytää kyseisen tarvikkeen / työn määrän ja palauttaa sen listan inputin arvoksi
    function checkAmount($arr, $id){

        for($i = 0; $i < count($arr); $i++){
            if($arr[$i][0] == $id)
                return "\"" . $arr[$i][1] . "\"";
        }
        return "0";
    }

    function checkDiscount($arr, $id){

        for($i = 0; $i < count($arr); $i++){
            if($arr[$i][0] == $id)
                return $arr[$i][2];
        }
        return "0";
    }
    ?>

    <div>
            <form method="post">
                <?php echo $itemTable; ?>
            </form>

            <form method="post">
                <?php echo $workTable; ?>
            </form>
    </div>

    <?php

    // Tarvikkeiden päivitys tietokantaan "Päivitä tarvikkeet" -nappulan painamisen yhteydessä
    if(isset($_POST['updateItems'])){
        $billId = $_POST['billId'];

        $itemSql = "SELECT * FROM  tarvike";
        $itemRes = pg_query($itemSql);

        $updateSql = "";

        while($row = pg_fetch_row($itemRes)) {
            $itemAmount = $_POST["item-amount-" . $row[0]];
            $_POST["item-discount-" . $row[0]] ? $itemDiscount = $_POST["item-discount-" . $row[0]] : $itemDiscount = 0;

            
            $updateSql .= "  UPDATE tarvikeluettelo SET lukumäärä = " . $itemAmount . ", alennus = " . $itemDiscount . " WHERE tarvike_id = " . $row[0] . " AND lasku_id = " . $billId . ";
                            INSERT INTO tarvikeluettelo (tarvike_id, lasku_id, lukumäärä, alennus) 
                            SELECT " . $row[0] . ", " . $billId . ", " . $itemAmount . ", " . $itemDiscount .
                        "   WHERE NOT EXISTS (SELECT 1 FROM tarvikeluettelo WHERE lasku_id = " . $billId . " AND tarvike_id = " . $row[0] . "); ";
        }
        pg_query($updateSql);
    }

    // Tarvikkeiden päivitys tietokantaan "Päivitä työtunnit" -nappulan painamisen yhteydessä
    if(isset($_POST['updateWorks'])){
        $billId = $_POST['billId'];

        $workSql = "SELECT * FROM  työhinnasto";
        $workRes = pg_query($workSql);

        $updateSql = "";

        while($row = pg_fetch_row($workRes)) {
            $workAmount = $_POST["work-amount-" . $row[0]];
            $workDiscount = $_POST["work-discount-" . $row[0]];

            $updateSql .= "  UPDATE työluettelo SET lukumäärä = " . $workAmount . ", alennus = " . $workDiscount . " WHERE työ_id = " . $row[0] . " AND lasku_id = " . $billId . ";
                            INSERT INTO työluettelo (työ_id, lasku_id, lukumäärä, alennus) 
                            SELECT " . $row[0] . ", " . $billId . ", " . $workAmount . ", " . $workDiscount .
                        "   WHERE NOT EXISTS (SELECT 1 FROM työluettelo WHERE lasku_id = " . $billId . " AND työ_id = " . $row[0] . "); ";
        }

        pg_query($updateSql);
    }
    ?>
</body>
</html>
