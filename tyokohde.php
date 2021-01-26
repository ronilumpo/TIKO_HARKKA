<?php

$conn_info = "";
$f = fopen("tietokanta.txt", "r");
while(!feof($f)){
    $conn_info = fgets($f);
}


if (!$connection = pg_connect($conn_info))
   die("Connection error");

//haetaan asiakkaan tiedot dropdown valikkoon   
$sql = "SELECT id, nimi, osoite FROM asiakas";
$result= pg_query($sql);

$color1 = "#fff";
$color2 = "#ccc";
$color = $color1;

if (isset($_POST['tallenna'])) {

    $tulos = pg_query("SELECT nimi FROM työkohde");
    if (pg_num_rows($tulos) == 0) {
        pg_query("ALTER SEQUENCE työkohde_id_seq RESTART WITH 1");
    }

    //suojataan merkkijonot ennen kyselyä
    $aid  = intval($_POST['aid']);
    $nimi   = pg_escape_string($_POST['nimi']);
    $osoite   = pg_escape_string($_POST['osoite']);

    $tiedot_ok = $aid != 0 && $nimi != '';

    $sql2 = "SELECT asiakas_id, nimi, osoite FROM työkohde";
    $result2 = pg_query($sql2);

    //tarkistetaan onko järjestelmään syötetty jo kyseinen työmaa
    while($row1 = pg_fetch_row($result2)) {
        $aidv = intval($row1[0]);
        $nimiv   = pg_escape_string($row1[1]);
        $osoitev   = pg_escape_string($row1[2]);
        if ($aidv == $aid && $nimiv == $nimi && $osoitev == $osoite) {
            $tiedot_ok = false;
            $olemassa = true;
            break;
        }      
    }

    if ($tiedot_ok){
        $kysely = "INSERT INTO työkohde (asiakas_id, nimi, osoite) VALUES ('$aid','$nimi','$osoite')";
        $lisays = pg_query($kysely);

        if ($lisays) {
            $viesti = 'Työkohde lisätty!';
        }
        else {
            $viesti = 'Työkohdetta ei lisätty: ' . pg_last_error($connection);
        } 
    }
    else {
        if ($olemassa) {
            $viesti = 'Annettu kohde on jo olemassa!';
        }
        else {
            $viesti = 'Annetut tiedot puutteelliset - tarkista!';
        }
    }
       
}

?>

<html>
<head>
  <title>Työkohteen lisäys</title>
</head>
<body>

    <h1>Työkohteen lisäys</h1><br>
    <h3>Etsi asiakas:</h3>
    <select name="asiakas">
        <?php
            //dropdown valikko
            while($row = pg_fetch_row($result)) {
                $color == $color1 ? $color = $color2 : $color = $color1;
                echo "<option value=\"" . $row[0] . "\" style=background:$color;> id: " . $row[0] . "  || nimi: ". $row[1] . "  || osoite: " . $row[2] . "</option>";            
            }
        ?>
    </select>
    <br>
    
    <h3>Lisää työkohteen tiedot:</h3>
    <form action="tyokohde.php" method="post">

    <?php 
        if (isset($viesti)) {
            if ($viesti == 'Työkohde lisätty!') {
                echo '<p style="color:green">'.$viesti.'</p>'; 
            }
            else {
                echo '<p style="color:red">'.$viesti.'</p>'; 
            }
        }
         
       
    ?>
	<table border="0" cellspacing="0" cellpadding="3">
	    <tr>
    	    <td>Asiakas ID:</td>
    	    <td><input type="text" name="aid" value="" /></td>
	    </tr>
	    <tr>
    	    <td>Työkohteen nimi:</td>
    	    <td><input type="text" name="nimi" value="" /></td>
	    </tr>
	    <tr>
    	    <td>Työkohteen osoite:</td>
    	    <td><input type="text" name="osoite" value="" /></td>
	    </tr>
	</table>
    <br>
    <input type="hidden" name="tallenna" value="jep">
    <input type="submit"  value="Lisää työkohde">
    </form>
    <br>
    <a href="etusivu.php">Takaisin etusivulle</a>
    <br><br>
    <a href="asiakas.php">Lisää uusi asiakas</a>

</body>
</html>
