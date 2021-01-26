<?php

$conn_info = "";
$f = fopen("tietokanta.txt", "r");
while(!feof($f)){
    $conn_info = fgets($f);
}


if (!$connection = pg_connect($conn_info))
   die("Connection error");


$color1 = "#fff";
$color2 = "#ccc";
$color = $color1;


if (isset($_POST['tallenna'])) {

    //luodaan tarvittavat taulut IF NOT EXISTS -tyylillä
    $f = fopen("luontilauseet.txt", "r");
    $q = "";
    while(!feof($f)){
        $q .= fgets($f);
    }
    pg_query($q);



    //tehdään uusi asiakastaulu, mikäli sellaista ei vielä ole
    pg_query("CREATE TABLE IF NOT EXISTS asiakas (
                id serial,
                tyyppi varchar(20) NOT NULL,
                nimi varchar(50) NOT NULL,
                osoite varchar(100) NOT NULL,
                PRIMARY KEY (id))");

    //suojataan merkkijonot ennen kyselyä
    $tyyppi  = pg_escape_string($_POST['tyyppi']);
    $nimi   = pg_escape_string($_POST['nimi']);
    $osoite   = pg_escape_string($_POST['osoite']);

    $tiedot_ok = ($tyyppi == 'yksityinen' || $tyyppi == 'yritys') && $nimi != '' && $osoite != '';

    $sql2 = "SELECT tyyppi, nimi, osoite FROM asiakas";
    $result2 = pg_query($sql2);

    //tarkistetaan onko järjestelmään syötetty jo kyseinen asiakas
    while($row1 = pg_fetch_row($result2)) {
        $tyyppiv = intval($row1[0]);
        $nimiv   = pg_escape_string($row1[1]);
        $osoitev   = pg_escape_string($row1[2]);
        if ($tyyppiv == $aid && $nimiv == $nimi && $osoitev == $osoite) {
            $tiedot_ok = false;
            $olemassa = true;
            break;
        }      
    }

    if ($tiedot_ok){
        $kysely = "INSERT INTO asiakas (tyyppi, nimi, osoite) VALUES ('$tyyppi','$nimi','$osoite')";
        $lisays = pg_query($kysely);

        if ($lisays) {
            $viesti = 'Asiakas lisätty!';
        }
        else {
            $viesti = 'Asiakasta ei lisätty: ' . pg_last_error($connection);
        } 
    }
    else {
        if ($olemassa) {
            $viesti = 'Annettu asiakas on jo olemassa!';
        }
        else {
            $viesti = 'Annetut tiedot puutteelliset - tarkista!';
        }
    }
       
}

?>

<html>
<head>
  <title>Asiakkaan lisäys</title>
</head>
<body>

    <h1>Asiakkaan lisäys</h1><br>
    <br>    
    <h3>Lisää asiakkaan tiedot:</h3>
    <form action="asiakas.php" method="post">

    <?php 
        if (isset($viesti)) {
            if ($viesti == 'Asiakas lisätty!') {
                echo '<p style="color:green">'.$viesti.'</p>'; 
            }
            else {
                echo '<p style="color:red">'.$viesti.'</p>'; 
            }
        }
         
       
    ?>
	<table border="0" cellspacing="0" cellpadding="3">
	    <tr>
    	    <td>Asiakkaan tyyppi (yritys, yksityinen):</td>
    	    <td><input type="text" name="tyyppi" value="" /></td>
	    </tr>
	    <tr>
    	    <td>Asiakkaan nimi:</td>
    	    <td><input type="text" name="nimi" value="" /></td>
	    </tr>
	    <tr>
    	    <td>Asiakkaan osoite:</td>
    	    <td><input type="text" name="osoite" value="" /></td>
	    </tr>
	</table>
    <br>
    <input type="hidden" name="tallenna" value="jep">
    <input type="submit"  value="Lisää asiakas">
    </form>
    <br>
    <a href="etusivu.php">Takaisin etusivulle</a>
    <br><br>
    <a href="tyokohde.php">Lisää uusi työkohde</a>
</body>
</html>
