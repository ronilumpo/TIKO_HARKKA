<?php

$conn_info = "";
$f = fopen("tietokanta.txt", "r");
while(!feof($f)){
    $conn_info = fgets($f);
}


if (!$connection = pg_connect($conn_info))
   die("Connection error");

//haetaan laskut järjestelmästä
$sql = "SELECT * FROM lasku";
$result = pg_query($sql);
$date = date("Y-m-d");

//käydään laskut rivi riviltä läpi
while($row = pg_fetch_row($result)) {
    $sql_date = $row[1];
    //jos laskun eräpäivä on mennyt ja maksua ei ole suoritettu
    if (!empty($row[1]) && $sql_date < $date && empty($row[3])) {
        $new_date = date("Y-m-d", strtotime("+30 days"));
        $new_date = pg_escape_string($new_date);
        $date = pg_escape_string($date);
        
        //mikäli maksamattoman laskun tyyppi on lasku, tehään siitä muistutuslasku
        if ($row[21] == 'lasku') {
            $hinta = floatval($row[17] + 5);
            $hinta_ale = floatval($row[18] + 5);
            $type = pg_escape_string("muistutus");
            $adding = pg_query("INSERT INTO lasku (eräpäivä,
            päivämäärä, osoite, projekti_tyyppi, työ_hinta_alkup, tarvikkeet_hinta_alkup,
            tunnit, kotitalousvähennys, alv_osuus, alennus_tarvikkeet, alennus_tuntityö, 
            työ_hinta_alennettu, tarvikkeet_hinta_alennettu,
            osat_lkm, osat_numero, loppusumma, loppusumma_alennettu,
            loppusumma_alkuperäinen, edellinen_lasku, lasku_tyyppi) VALUES 
            ('$new_date', '$date', '$row[4]', '$row[5]',
            '$row[6]', '$row[7]', '$row[8]', '$row[9]',
            '$row[10]', '$row[11]', '$row[12]', '$row[13]',
            '$row[14]', '$row[15]', '$row[16]', '$hinta',
            '$hinta_ale', '$row[19]', '$row[0]',
            '$type')");

            pg_query("UPDATE lasku SET maksupäivä = '$date' WHERE id = '$row[0]'");
            //haetaan edellisen laskun ID:n perusteella projekti_ID
            $nro = intval($row[0]);
            $haku = "SELECT projekti_id FROM laskutiedot WHERE lasku_id = '$nro'";
            $projekti = pg_query($haku);
            $projID = pg_fetch_row($projekti);
            //haetaan viimeisin (äsken lisätty) lasku ja annetaan sille sama projekti_ID
            //kuin edellisellekin
            $haku2 = "SELECT MAX(id) AS id FROM lasku";
            $lasku = pg_query($haku2);
            $laskuID = pg_fetch_row($lasku);
            $projektiID = intval($projID[0]);
            $laskunID = intval($laskuID[0]);
            $laskulisays = "INSERT INTO laskutiedot VALUES ('$projektiID','$laskunID')";
            $lisa = pg_query($laskulisays);
        }

        //mikäli maksamattoman laskun tyyppi on muistutus, tehdään siitä karhulasku
        else if ($row[21] == 'muistutus') {

            $type = pg_escape_string("karhu");
            $hinta = floatval($row[17] + 5 + 0.16/12*$row[17]);
            $hinta_ale = floatval($row[18] + 5 + 0.16/12*$row[18]);
            $adding2 = pg_query("INSERT INTO lasku (eräpäivä,
            päivämäärä, osoite, projekti_tyyppi, työ_hinta_alkup, tarvikkeet_hinta_alkup,
            tunnit, kotitalousvähennys, alv_osuus, alennus_tarvikkeet, alennus_tuntityö, 
            työ_hinta_alennettu, tarvikkeet_hinta_alennettu,
            osat_lkm, osat_numero, loppusumma, loppusumma_alennettu,
            loppusumma_alkuperäinen, edellinen_lasku, lasku_tyyppi) VALUES 
            ('$new_date', '$date', '$row[4]', '$row[5]',
            '$row[6]', '$row[7]', '$row[8]', '$row[9]',
            '$row[10]', '$row[11]', '$row[12]', '$row[13]',
            '$row[14]', '$row[15]', '$row[16]', '$hinta',
            '$hinta_ale', '$row[19]', '$row[0]',
            '$type')");

            pg_query("UPDATE lasku SET maksupäivä = '$date' WHERE id = '$row[0]'");
            //haetaan edellisen laskun ID:n perusteella projekti_ID
            $nro = intval($row[0]);
            $haku = "SELECT projekti_id FROM laskutiedot WHERE lasku_id = '$nro'";
            $projekti = pg_query($haku);
            $projID = pg_fetch_row($projekti);
            //haetaan viimeisin (äsken lisätty) lasku ja annetaan sille sama projekti_ID
            //kuin edellisellekin
            $haku2 = "SELECT MAX(id) AS id FROM lasku";
            $lasku = pg_query($haku2);
            $laskuID = pg_fetch_row($lasku);
            $projektiID = intval($projID[0]);
            $laskunID = intval($laskuID[0]);
            $laskulisays = "INSERT INTO laskutiedot VALUES ('$projektiID','$laskunID')";
            $lisa = pg_query($laskulisays);

        }
        //mikäli maksamattoman laskun tyyppi on karhu, tehdään uusi karhulasku
        else if ($row[21] == 'karhu') {
            $type = pg_escape_string("karhu");
            $hinta = floatval($row[17] + 5 + 0.16/12*$row[17]);
            $hinta_ale = floatval($row[18] + 5 + 0.16/12*$row[18]);
            pg_query("INSERT INTO lasku (eräpäivä,
            päivämäärä, osoite, projekti_tyyppi, työ_hinta_alkup, tarvikkeet_hinta_alkup,
            tunnit, kotitalousvähennys, alv_osuus, alennus_tarvikkeet,
            alennus_tuntityö, työ_hinta_alennettu, tarvikkeet_hinta_alennettu,
            osat_lkm, osat_numero, loppusumma, loppusumma_alennettu,
            loppusumma_alkuperäinen, edellinen_lasku, lasku_tyyppi) VALUES 
            ($new_date, $date, $row[4], $row[5], $row[6], $row[7], $row[8],
            $row[9], $row[10], $row[11], $row[12], $row[13], $row[14], $row[15],
            $row[16], $uusi_summa, $uusi_alennettu_summa, $row[19], $row[0], 'karhu') ");

            pg_query("UPDATE lasku SET maksupäivä = '$date' WHERE id = '$row[0]'");
            //haetaan edellisen laskun ID:n perusteella projekti_ID
            $nro = intval($row[0]);
            $haku = "SELECT projekti_id FROM laskutiedot WHERE lasku_id = '$nro'";
            $projekti = pg_query($haku);
            $projID = pg_fetch_row($projekti);
            //haetaan viimeisin (äsken lisätty) lasku ja annetaan sille sama projekti_ID
            //kuin edellisellekin
            $haku2 = "SELECT MAX(id) AS id FROM lasku";
            $lasku = pg_query($haku2);
            $laskuID = pg_fetch_row($lasku);
            $projektiID = intval($projID[0]);
            $laskunID = intval($laskuID[0]);
            $laskulisays = "INSERT INTO laskutiedot VALUES ('$projektiID','$laskunID')";
            $lisa = pg_query($laskulisays);
        }

    }
}

?>

<html>

<style>
a{
    font-size: 2em;
}
</style>



<head>
  <title>Etusivu</title>
</head>
<body>

    <a href="asiakas.php">Asiakkaan lisäys</a><br>

    <a href="tyokohde.php">Lisää työkohde asiakkaalle</a><br>

    <a href="kulusivu.php">Päivitä kulut</a><br>

    <a href="tuntityoprojekti.php">Lisää tuntityö-tyyppinen projekti</a><br>

    <a href="tarvike.php">Tarvikelistan päivitys</a><br>
    
    <a href="urakkaprojekti.php">Lisää urakka-tyyppinen projekti</a><br>

    <a href="laskut.php">Selaa laskuja</a><br>

    <a href="projektivalmis.php">Viimeistele projekti</a><br>

</body>
</html>
