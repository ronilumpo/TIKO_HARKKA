CREATE TABLE Asiakas(
    id serial,
    tyyppi varchar(20) NOT NULL,
    nimi varchar(50) NOT NULL,
    osoite varchar(100) NOT NULL,
    PRIMARY KEY(id)
);


create table tarvike(
   id serial,
   nimi varchar(50) NOT NULL,
   yksikkö varchar(10) NOT NULL,
   sisäänostohinta decimal(9,2) NOT NULL,
   myyntihinta decimal(9,2),
   varastotilanne int NOT NULL,
   alv_prosentti decimal(4,2) NOT NULL,
   primary key (id)
);


create table tarvike_uusi(
   id serial,
   nimi varchar(50) NOT NULL,
   yksikkö varchar(10) NOT NULL,
   sisäänostohinta decimal(9,2) NOT NULL,
   myyntihinta decimal(9,2),
   varastotilanne int NOT NULL,
   alv_prosentti decimal(4,2) NOT NULL,
   primary key (id)
);


create table tarvike_poistunut(
   id serial,
   nimi varchar(50) NOT NULL,
   yksikkö varchar(10) NOT NULL,
   sisäänostohinta decimal(9,2) NOT NULL,
   myyntihinta decimal(9,2),
   varastotilanne int NOT NULL,
   alv_prosentti decimal(4,2) NOT NULL,
   poistumispäivä date NOT NULL,
   primary key (id)
);


CREATE TABLE Työkohde(
    id serial,
    asiakas_id integer,
    nimi varchar(50),
    osoite varchar(100),
    PRIMARY KEY(id),
    FOREIGN KEY(asiakas_id) REFERENCES asiakas(id)
);


CREATE TABLE Projekti(
    id serial,
    työkohde_id integer,
    tyyppi varchar(20) NOT NULL,
    nimi varchar(50) NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY (työkohde_id) REFERENCES työkohde(id)
);


CREATE TABLE Lasku(
    id serial,
    eräpäivä date,
    päivämäärä date NOT NULL,
    maksupäivä date,
    osoite varchar(100),
    projekti_tyyppi varchar(20) NOT NULL, --urakka, tuntityö
    työ_hinta_alkup decimal(10,2) NOT NULL,
    tarvikkeet_hinta_alkup decimal(10,2) NOT NULL,
    tunnit decimal(5,2),
    kotitalousvähennys decimal(10,2),
    alv_osuus decimal(10,2) NOT NULL,
    alennus_tarvikkeet decimal(5,2) DEFAULT 0.0,
    alennus_tuntityö decimal(5,2) DEFAULT 0.0,
    työ_hinta_alennettu decimal(10,2),
    tarvikkeet_hinta_alennettu decimal(10,2),
    osat_lkm integer NOT NULL,
    osat_numero integer,
    loppusumma decimal(10,2) NOT NULL,
    loppusumma_alennettu decimal(10,2),
    loppusumma_alkuperäinen decimal(10,2),
    edellinen_lasku integer,
    lasku_tyyppi varchar(20), --lasku, muistutus, karhu


    PRIMARY KEY(id),
    FOREIGN KEY (edellinen_lasku) REFERENCES Lasku
);


create table työhinnasto(
   id serial,
   työ_nimi varchar(50) NOT NULL,
   hinta decimal(9,2) NOT NULL,
   primary key (id)
);


CREATE TABLE Laskutiedot(
    projekti_id integer,
    lasku_id integer,
    PRIMARY KEY(projekti_id, lasku_id),
    FOREIGN KEY(projekti_id) REFERENCES Projekti(id),
    FOREIGN KEY(lasku_id) REFERENCES Lasku(id)
);


create table tarvikeluettelo(
   lasku_id int,
   tarvike_id int,
   lukumäärä int NOT NULL,
   alennus decimal(5,2) DEFAULT 0.0,
   foreign key (lasku_id) references lasku(id),
   foreign key (tarvike_id) references tarvike(id)
);


create table työluettelo(
   lasku_id int,
   työ_id int,
   lukumäärä int NOT NULL,
   alennus decimal(5,2) DEFAULT 0.0,
   primary key (lasku_id,työ_id),
   foreign key (lasku_id) references lasku(id),
   foreign key (työ_id) references työhinnasto(id)
);


-- Testidataa (laskua ei ole kasattu oikein)

INSERT INTO asiakas (nimi,tyyppi) values('hannun urakat', 'yritys');
INSERT INTO asiakas (nimi,tyyppi, osoite) values('pekka puumies', 'yksityinen', 'perämettänkuja 5');
INSERT INTO asiakas (nimi,tyyppi) values('jukka röimies', 'yksityinen');
INSERT INTO asiakas (nimi,tyyppi, osoite) values('rakennus oy', 'yritys', 'keskuskatu 17 A 4');

INSERT INTO työkohde(asiakas_id, nimi) values(1, 'hannulan tehdasalue');
INSERT INTO työkohde(asiakas_id, nimi) values(2, 'pekan kesämökki');
INSERT INTO työkohde(asiakas_id, nimi) values(3, 'jukan omakotitalo');
INSERT INTO työkohde(asiakas_id, nimi) values(4, 'kurikka');
INSERT INTO työkohde(asiakas_id, nimi) values(4, 'turku');
INSERT INTO työkohde(asiakas_id, nimi) values(1, 'helsinki-tampere');
INSERT INTO työkohde(asiakas_id, nimi) values(3, 'jukan auto');

INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(1, 'varastohalli', 'urakka');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(2, 'pekan kesämökin kiuas', 'tuntityö');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(3, 'vessaremontti', 'tuntityö');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(4, 'alakylän koulu', 'urakka');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(5, 'vesipuisto', 'urakka');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(3, 'jukan vuotava katto', 'tuntityö');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(6, 'moottoritie', 'urakka');
INSERT INTO projekti (työkohde_id, nimi, tyyppi) values(7, 'autoremontti', 'tuntityö');

INSERT INTO lasku
    (päivämäärä,
    eräpäivä,
    osoite,
    lasku_tyyppi,
    projekti_tyyppi,
    työ_hinta_alkup,
    tarvikkeet_hinta_alkup,
    loppusumma,
    tunnit,
    kotitalousvähennys,
    alv_osuus,
    alennus_tarvikkeet,
    alennus_tuntityö,
    työ_hinta_alennettu,
    tarvikkeet_hinta_alennettu,
    osat_lkm,
    osat_numero,
    loppusumma_alennettu,
    loppusumma_alkuperäinen
    )

    SELECT 
        '2020-01-02',
        '2020-02-02',
        asiakas.osoite,
        'lasku',
        'projekti',
        450.00,
        400.00,
        950.00,
        9,
        180,
        131.54,
        0.0,
        0.0,
        450.00,
        400.00,
        6,
        1,
        950.00,
        950.00
    FROM asiakas WHERE id = 2
;

INSERT INTO lasku
    (päivämäärä,
    eräpäivä,
    osoite,
    lasku_tyyppi,
    projekti_tyyppi,
    työ_hinta_alkup,
    tarvikkeet_hinta_alkup,
    loppusumma,
    tunnit,
    kotitalousvähennys,
    alv_osuus,
    alennus_tarvikkeet,
    alennus_tuntityö,
    työ_hinta_alennettu,
    tarvikkeet_hinta_alennettu,
    osat_lkm,
    osat_numero,
    loppusumma_alennettu,
    loppusumma_alkuperäinen
    )

    SELECT 
        '2020-01-02',
        '2020-02-02',
        asiakas.osoite,
        'lasku',
        'urakka',
        500.00,
        500.00,
        1005.00,
        9,
        180,
        131.54,
        0.0,
        0.0,
        450.00,
        400.00,
        1,
        1,
        1005.00,
        1000.00
    FROM asiakas WHERE id = 4
;

insert into tarvike(
   nimi,
   yksikkö,
   sisäänostohinta,
   myyntihinta,
   varastotilanne,
   alv_prosentti
) values ('porakone','kpl',1.00,1.49,2,0.24);

insert into tarvike(
   nimi,
   yksikkö,
   sisäänostohinta,
   myyntihinta,
   varastotilanne,
   alv_prosentti
) values ('naula','pkt',5.00,7.45,500,0.24);

insert into tarvike(
   nimi,
   yksikkö,
   sisäänostohinta,
   myyntihinta,
   varastotilanne,
   alv_prosentti
) values ('maallikon sähkö-opas','kpl',10.00,13.50,12,0.10);

INSERT INTO tarvikeluettelo
    values(
        1,
        2,
        20,
        0
    );
INSERT INTO tarvikeluettelo
    values(
        1,
        1,
        20,
        0.3
    );

INSERT INTO laskutiedot values (2,1);
INSERT INTO laskutiedot values (4,2);

INSERT INTO 


ja430758=> select * from asiakas;
 id |   tyyppi   |     nimi      |      osoite
----+------------+---------------+-------------------
  1 | yritys     | hannun urakat |
  2 | yksityinen | pekka puumies | perämettänkuja 5
  3 | yksityinen | jukka röimies |
  4 | yritys     | rakennus oy   | keskuskatu 17 A 4
(4 rows)



ja430758=> select * from tarvike;
 id |         nimi         | yksikkö | sisäänostohinta | myyntihinta | varastotilanne | alv_prosentti
----+----------------------+---------+-----------------+-------------+----------------+---------------
  1 | porakone             | kpl     |            1.00 |        1.49 |              2 |          0.24
  2 | naula                | pkt     |            5.00 |        7.45 |            500 |          0.24
  3 | maallikon sähkö-opas | kpl     |           10.00 |       13.50 |             12 |          0.10
(3 rows)



ja430758=> select * from työkohde;
 id | asiakas_id |        nimi         | osoite
----+------------+---------------------+--------
  1 |          1 | hannulan tehdasalue |
  2 |          2 | pekan kesämökki     |
  3 |          3 | jukan omakotitalo   |
  4 |          4 | kurikka             |
  5 |          4 | turku               |
  6 |          1 | helsinki-tampere    |
  7 |          3 | jukan auto          |
(7 rows)


ja430758=> select * from projekti;
 id | työkohde_id |  tyyppi  |         nimi
----+-------------+----------+-----------------------
  1 |           1 | urakka   | varastohalli
  2 |           2 | tuntityö | pekan kesämökin kiuas
  3 |           3 | tuntityö | vessaremontti
  4 |           4 | urakka   | alakylän koulu
  5 |           5 | urakka   | vesipuisto
  6 |           3 | tuntityö | jukan vuotava katto
  7 |           6 | urakka   | moottoritie
  8 |           7 | tuntityö | autoremontti
(8 rows)


ja430758=> \x
Expanded display is on.
ja430758=> select * from lasku;
-[ RECORD 1 ]--------------+-----------------
id                         | 1
eräpäivä                   | 2020-02-02
päivämäärä                 | 2020-01-02
maksupäivä                 |
osoite                     | perämettänkuja 5
projekti_tyyppi            | projekti
työ_hinta_alkup            | 450.00
tarvikkeet_hinta_alkup     | 400.00
tunnit                     | 9
kotitalousvähennys         | 180.00
alv_osuus                  | 131.54
alennus_tarvikkeet         |
alennus_tuntityö           |
työ_hinta_alennettu        | 450.00
tarvikkeet_hinta_alennettu | 400.00
osat_lkm                   | 6
osat_numero                |
loppusumma                 | 950.00
loppusumma_alennettu       | 950.00
loppusumma_alkuperäinen    | 950.00
edellinen_lasku            |
lasku_tyyppi               | lasku


ja430758=> select * from tarvike;
 id |         nimi         | yksikkö | sisäänostohinta | myyntihinta | varastotilanne | alv_prosentti
----+----------------------+---------+-----------------+-------------+----------------+---------------
  1 | porakone             | kpl     |            1.00 |        1.49 |              2 |          0.24
  2 | naula                | pkt     |            5.00 |        7.45 |            500 |          0.24
  3 | maallikon sähkö-opas | kpl     |           10.00 |       13.50 |             12 |          0.10
(3 rows)




ja430758=> select * from tarvikeluettelo;
 lasku_id | tarvike_id | lukumäärä | alennus
----------+------------+-----------+---------
        1 |          2 |        20 |    0.00
        1 |          1 |        20 |    0.30
(2 rows)