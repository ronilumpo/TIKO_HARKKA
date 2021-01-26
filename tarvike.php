<?php

$conn_info = "";
$f = fopen("tietokanta.txt", "r");
while(!feof($f)){
    $conn_info = fgets($f);
}


if (!$connection = pg_connect($conn_info))
   die("Connection error");


$sql = "SELECT nimi, yksikkö FROM tarvike_uusi";
$stuff_result = pg_query($sql);


if (isset($_POST['tarvike'])) {
    $sql = "SELECT nimi, yksikkö FROM tarvike_uusi";
    $stuff_result = pg_query($sql);

    $nimi = pg_escape_string($_POST['nimi']);
    $yksikko = pg_escape_string($_POST['yks']);
    $hinta = floatval($_POST['hinta']);
    $varasto = intval($_POST['varasto']);
    $alv = floatval($_POST['alv']);
    $tiedot_ok = $nimi != '' && $yksikko != '' && $hinta > 0.0 && $alv >= 0.00;

    $sql2 = "SELECT nimi, yksikkö FROM tarvike_uusi";
    $result = pg_query($sql2);

    //tarkistetaan onko järjestelmään syötetty jo kyseinen tarvike
    while($row = pg_fetch_row($result)) {
        $nimiv   = pg_escape_string($row[1]);
        $yksikkov   = pg_escape_string($row[2]);
        if ($nimiv == $nimi && $yksikkov == $yksikko) {
            $tiedot_ok = false;
            $olemassa = true;
            break;
        }      
    }

    if ($tiedot_ok) {
        $myynti = $hinta * 1.25;
        $lisays = pg_query("INSERT INTO tarvike_uusi (nimi,yksikkö,sisäänostohinta,myyntihinta,varastotilanne,alv_prosentti)
        VALUES ('$nimi','$yksikko','$hinta','$myynti','$varasto','$alv')");

        if ($lisays) {
            $viesti = 'Tarvike lisätty!';
            $stuff_result = pg_query($sql);
        }
        else {
            $viesti = 'Tarviketta ei lisätty: ' . pg_last_error($connection);
        }     
    }
    else {
        if ($olemassa) {
            $viesti = 'Annettu kohde on jo olemassa';
        }
        else {
            $viesti = 'Annetut tiedot puutteelliset - tarkista!';
        }
    }
}



if (isset($_POST['tallenna'])) {
    $date = date("Y-m-d");
    $date = pg_escape_string($date);

    //uusien ja vanhojen tarvikkeiden vertailu
    $query1 = "SELECT * FROM tarvike";
    $result1 = pg_query($query1);

    $query2 = "SELECT * FROM tarvike_uusi";
    $result2 = pg_query($query2);

    while($row1 = pg_fetch_row($result1)) {
        $onko = false;
        while($row2 = pg_fetch_row($result2)) {
            if ($row1 == $row2) {
                $onko = true;
                break;
            }
        }
        if (!$onko) {
            pg_query("INSERT INTO tarvike_poistunut (nimi,yksikkö,sisäänostohinta,
            myyntihinta,varastotilanne, alv_prosentti,poistumispäivä) VALUES ('$row1[1]','$row1[2]',
            '$row1[3]', '$row1[4]', '$row1[5]', '$row1[6]', '$date')");
        }
    }

    //poista kaikki tuotteet tarvikelistasta, 
    //aloita sekvenssi 1 ja lisää uudet tuotteet
    pg_query("DELETE FROM tarvike WHERE id > 0");
    pg_query("ALTER SEQUENCE tarvike_id_seq RESTART WITH 1");
    $result3 = pg_query($query2);
    while($row3 = pg_fetch_row($result3)) {
        $myynti = floatval($row3[3] * 1.25);
        pg_query("INSERT INTO tarvike (nimi, yksikkö, sisäänostohinta, myyntihinta, varastotilanne, alv_prosentti)
        VALUES ('$row3[1]','$row3[2]','$row3[3]', '$myynti', '$row3[5]','$row3[6]')");
    }

    //poistetaan kaikki tarvike_uusi listasta
    pg_query("DELETE FROM tarvike_uusi WHERE id > 0");
    pg_query("ALTER SEQUENCE tarvike_id_seq RESTART WITH 1");
    $viesti = "Uudet tarvikkeet lisätty!";
    
}   


?>

<html>
<head>
  <title>Tarvikelistan päivitys</title>
</head>
<body>
<?php
if (isset($viesti)) {
    if ($viesti == "Uudet tarvikkeet lisätty!" || 
    $viesti == "Tarvike lisätty!") {
        echo '<p style="color:green">'.$viesti.'</p>';
    }
    else {
        echo '<p style="color:red">'.$viesti.'</p>'; 
    }
}
?>
<h3>Lisätyt tarvikkeet:</h3>
<select name="uudet_tarvikkeet">
        <?php
            //dropdown valikko
            while($row = pg_fetch_row($stuff_result)) {
                echo "<option> Nimi: " . $row[0] . "  || Yksikkö: ". $row[1] . "</option>";            
            }
        ?>
</select><br>

<form method="post">

    <h3>Lisää tarvike:</h3>
    <table border="0" cellspacing="0" cellpadding="3">
	    <tr>
    	    <td>Nimi:</td>
    	    <td><input type="text" name="nimi" value="" /></td>
	    </tr>
	    <tr>
    	    <td>Yksikkö (Esim. kpl, pkt):</td>
    	    <td><input type="text" name="yks" value="" /></td>
	    </tr>
	    <tr>
    	    <td>Sisäänostohinta:</td>
    	    <td><input type="text" name="hinta" value="" /></td>
        </tr>
        <tr>
    	    <td>Varastotilanne:</td>
    	    <td><input type="text" name="varasto" value="" /></td>
        </tr>
        <tr>
    	    <td>ALV-prosentti (Esim. 0.10, 0.24):</td>
    	    <td><input type="text" name="alv" value="" /></td>
	    </tr>
	</table>
    <br>
    <input type="hidden" name="tarvike" value=""/>
    <input type="submit" value="Tallenna tarvike" />
    <br><br>
</form>
<form action="tarvike.php" method="post">
    <h3>Päivitä uudet tarvikkeet yrityksen tarvikelistaan:</h3><br>
    <input type="hidden" name="tallenna" value=""/>
    <input type="submit" value="Tallenna järjestemään" />
</form>    
<br>
<a href="etusivu.php">Linkki etusivulle</a>


</body>
</html>
