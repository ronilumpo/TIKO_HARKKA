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
label{width: 200px; display:inline-block;}
input {margin-bottom: 10px;}
</style>

<head>
  <title>Urakkaprojekti</title>
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
                <label for="amount_hours" id="dola_hours">Työt tunteina</label>
                <input name="amount_hours" type="text" value="0"/>
                <br/>
                <label for="dola_hours" id="dola_hours">Tuntitöiden hinta</label>
                <input name="dola_hours" type="text" value="0"/>
                <br/>
                <label for="discount_hours" id="discount_hours">Alennusprosentti tuntityölle</label>
                <input name="discount_hours" type="text" value="0"/>
                <br/>
                <label for="dola_stuff" id="dola_stuff">Tarvikkeiden hinta</label>
                <input name="dola_stuff" type="text" value="0"/>
                <br/>
                <label for="discount_stuff" id="discount_stuff">Alennusprosentti tarvikkeille</label>
                <input name="discount_stuff" type="text" value="0"/>
                <br/>
                
                <label for="amount_bills" id="amount_bills">Laskujen kappalemäärä</label>
                <input name="amount_bills" type="text" value="1"/>
                <br/>


                <button type="submit" name="add_project">Siirry syöttämään tarvikemäärät</button>
            </form>

        ';

        
    }

    if(isset($_POST['add_project'])){
        $tyokohde_id = $_POST['dropdown_value'];
        $project_name = $_POST['name'];
        $amount_bills = $_POST['amount_bills'];
        $dola_hours = $_POST['dola_hours'] / $amount_bills;
        $amount_hours = $_POST['amount_hours'] / $amount_bills;
        $discount_hours = $_POST['discount_hours'];
        $dola_stuff = $_POST['dola_stuff']  / $amount_bills;
        $discount_stuff = $_POST['discount_stuff'];

        $address = pg_fetch_row(pg_query("SELECT * FROM työkohde WHERE id = $tyokohde_id"))[3];
        $date = date("Y-m-d");



        $discounted_hours = $dola_hours * (1 - $discount_hours / 100);
        $discounted_stuff = $dola_stuff * (1 - $discount_stuff / 100);

        $alv = ($discounted_hours + $discounted_stuff) * 0.24;

        $final_amount = $dola_hours + $dola_stuff;
        $final_amount_discounted = $discounted_hours + $discounted_stuff;

        $ktv = $discounted_hours * 0.40;

        $project_id = pg_fetch_row(pg_query("INSERT INTO projekti (työkohde_id, tyyppi, nimi) VALUES ($tyokohde_id, 'urakka', '$project_name') RETURNING id;"))[0];

        for($i = 1; $i <= $amount_bills; $i++){
            $pg_q = "INSERT INTO lasku (
                                päivämäärä,
                                osoite,
                                projekti_tyyppi,
                                työ_hinta_alkup,
                                tarvikkeet_hinta_alkup,
                                tunnit,
                                kotitalousvähennys,
                                alv_osuus,
                                alennus_tarvikkeet,
                                alennus_tuntityö,
                                työ_hinta_alennettu,
                                tarvikkeet_hinta_alennettu,
                                osat_lkm,
                                osat_numero,
                                loppusumma,
                                loppusumma_alennettu,
                                loppusumma_alkuperäinen,
                                lasku_tyyppi
                                )
                            VALUES(
                                '$date',
                                '$address',
                                'urakka',
                                $dola_hours,
                                $dola_stuff,
                                $amount_hours,
                                $ktv,
                                $alv,
                                $discount_stuff,
                                $discount_hours,
                                $discounted_hours,
                                $discounted_stuff,
                                $amount_bills,
                                $i,
                                $final_amount,
                                $final_amount_discounted,
                                $final_amount,
                                'lasku'
                            ) RETURNING id;
                                ";
            $billId = pg_fetch_row(pg_query($pg_q))[0];
            pg_query("INSERT INTO laskutiedot VALUES($project_id, $billId)");
        }

        $host  = $_SERVER['HTTP_HOST'];
        $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        header("Location: http://$host$uri/kulusivu.php");
        exit;
    }

    
    
    
    ?>



</body>
</html>
