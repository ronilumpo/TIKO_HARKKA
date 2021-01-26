<?php

$conn_info = "";
$f = fopen("tietokanta.txt", "r");
while(!feof($f)){
    $conn_info = fgets($f);
}


if (!$connection = pg_connect($conn_info))
   die("Connection error");

// Tietokannassa olevat asiakkaat työkohteineen
$sql = "SELECT * 
        FROM asiakas, työkohde
        WHERE asiakas_id = asiakas.id
       ";
$result = pg_query($sql);


// Työkohteet pudotusvalikkoon
$separator = "\n";
ob_start();
while($row = pg_fetch_row($result)) 
    echo "<option value= \"" . $row[4] . "\"> id:". $row[4] . " / " . $row[6] . " / " . $row[2] . "</option>";

$projects = ob_get_clean();

$projectsList = explode($separator, $projects);




?>

<html>

<style>
label{width: 250px; display:inline-block;}
input {margin-bottom: 10px;}
</style>

<head>
  <title>Tuntityöprojekti</title>
</head>
<body>
    <a href="etusivu.php">Etusivu</a><br>
    <span>Valitse työkohde, jolle haluat lisätä projektin</span>
    <br>

    <form method="post">
    <select name="dropdown" id="dropdown">
        <?php echo $projectsList[0]; ?>
    </select>
    <button type="submit" name="getProjectInfo" >Hae</button>
    </form>

    





    <?php
    if(isset($_POST['getProjectInfo'])){
        $dd_val = $_POST['dropdown'];

        echo "Lisätään projektia työkohteelle " . $dd_val . "<br>";

        echo '
            <form method="post" id="add_form">
                <input type="hidden" value=' . $dd_val . ' name="dropdown_value">
                <label for="name" id="name">Nimi</label>
                <input name="name" type="text"/>
                <br/>
                <label for="discount_hours" id="discount_hours">Lisäalennusprosentti tuntityölle</label>
                <input name="discount_hours" type="text" value="0"/>
                <br/>
                <label for="discount_stuff" id="discount_stuff">Lisäalennusprosentti tarvikkeille</label>
                <input name="discount_stuff" type="text" value="0"/>
                <br/>
                <button type="submit" name="add_project">Lisää projekti</button>
            </form>
        
        ';
    }

    if(isset($_POST['add_project'])){
        $tyokohde_id = $_POST['dropdown_value'];
        $project_name = $_POST['name'];
        $discount_hours = $_POST['discount_hours'];
        $discount_stuff = $_POST['discount_stuff'];

        $address = pg_fetch_row(pg_query("SELECT * FROM työkohde WHERE id = $tyokohde_id"))[3];
        $date = date("Y-m-d");


        $project_id = pg_fetch_row(pg_query("INSERT INTO projekti (työkohde_id, tyyppi, nimi) VALUES ($tyokohde_id, 'tuntityö', '$project_name') RETURNING id;"))[0];
        $pg_q = "INSERT INTO lasku (
                            päivämäärä,
                            osoite,
                            projekti_tyyppi,
                            työ_hinta_alkup,
                            tarvikkeet_hinta_alkup,
                            alv_osuus,
                            alennus_tarvikkeet,
                            alennus_tuntityö,
                            osat_lkm,
                            osat_numero,
                            loppusumma,
                            lasku_tyyppi
                            )
                        VALUES(
                            '$date',
                            '$address',
                            'tuntityö',
                            0,
                            0,
                            0,
                            $discount_stuff,
                            $discount_hours,
                            1,
                            1,
                            0,
                            'lasku'
                        ) RETURNING id;
                            ";
        $billId = pg_fetch_row(pg_query($pg_q))[0];

        pg_query("INSERT INTO laskutiedot VALUES($project_id, $billId)");
    }
    ?>


    



</body>
</html>
