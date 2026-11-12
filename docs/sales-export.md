# Potpuni CSV izvoz prodaje

Na nadzornoj ploči odaberite **Potpuni izvoz prodaje → Pokreni potpuni izvoz**.
Gumb je dostupan administratorima koji već imaju `access` i `modify` za
`tool/backup`. Nema instalacije modula ni migracije baze.

Ostavite stranicu otvorenu. Nakon zatvaranja, prekida veze ili ponovne prijave
odaberite **Nastavi**. Po završetku preuzmite osam zasebnih CSV datoteka:
`order`, `order_product`, `order_option`, `order_total`, `order_history`,
`customer`, `address`, `product`. Uključeni su svi postojeći zapisi, sve trgovine,
svi statusi (i status 0), sva standardna i dodatna polja te zaglavlja praznih tablica.

## Format za analizu

- UTF-8 s BOM-om, razdjelnik `;`, dvostruki navodnici, CRLF između redaka.
- Prvi red sadrži izvorne nazive stupaca. Navodnici, razdjelnici i novi redovi
  unutar vrijednosti pravilno su citirani.
- SQL `NULL` prikazan je kao `\N`; prazan tekst ostaje prazan tekst.
- Tekstu koji počinje `\` dodaje se još jedan `\` radi razlikovanja od NULL-a.
- Tekstu koji počinje apostrofom ili može pokrenuti tabličnu formulu dodaje se
  apostrof. Negativni numerički iznosi ostaju nepromijenjeni.
- Za povrat izvorne vrijednosti nakon CSV parsiranja: točan `\N` pretvorite u
  NULL; inače, ako vrijednost počinje s dva `\` ili jednim apostrofom, uklonite
  jedan početni znak. Ostalo ostavite nepromijenjeno.
- Pri uvozu identifikatore, telefone i poštanske brojeve učitajte kao tekst kako
  alat ne bi uklonio početne nule. Decimalni separator je točka.
- MySQL `TIMESTAMP` vrijednosti čitaju se u UTC-u. `DATETIME` ostaje kakav je u bazi.

## Obrada i pristup

Svaki zahtjev čita najviše 100 zapisa preko primarnog ključa, bez OFFSET-a,
COUNT-a svih redaka ili učitavanja cijelog izvoza u memoriju. Napredak i pozicija
u datoteci spremaju se atomarno. Ponovljeni zahtjevi i prekinuti zapisi nastavljaju
se bez dupliciranja redaka. Preuzimanje šalje blokove od 1 MiB i podržava HTTP
Range. Ne koristi se ZIP finalizacija koja bi opet mogla blokirati veliki izvoz.

Na početku se zabilježi najveći ID svake tablice. Naknadno dodani viši ID-evi
nisu uključeni; promjene i brisanja postojećih zapisa mogu se odraziti u izvozu.
Ovo nije transakcijska snimka baze u jednom trenutku. Za takav rezultat zaustavite
izmjene tijekom izvoza ili izvozite iz zasebne snimke baze.

Jedan posao po administratoru, s provjerom ovlasti, prijave, tokena, vlasnika i
ID-a posla na svim rutama `tool/backup/sales/*`. Datoteke su u privatnoj mapi
`sys_get_temp_dir()/opgruzic-sales-<hash>/`, izvan web korijena, s pravima 0700.
PHP korisnik mora imati prostor i pravo pisanja u privremenu mapu. Podaci kupaca
uključuju i autentifikacijska polja jer je tražen izvoz svih polja.

Pristup izvozu istječe nakon 24 sata. Zastarjele datoteke brišu se pri sljedećem
pristupu tog administratora; gumb **Obriši izvoz** uklanja ih odmah. Za fizičko
brisanje bez ponovnog pristupa uključite čišćenje privremenih mapa na poslužitelju.
Brisanje izvoza ne mijenja webshop bazu. Nema crona ni zasebnog queue workera;
zatvaranje stranice pauzira pripremu.

## Provjera

`php -d memory_limit=64M tests/sales_export.php`

`node tests/sales_export_ui.js`

Provjerava više od 30.000 redaka, nastavak nakon prekida, nepotpun zapis na disku,
ponavljanje zahtjeva, zaključavanje, ovlasti, izolaciju korisnika, rok trajanja,
sva polja, praznu tablicu, CSV parsiranje i očuvanje izvornih vrijednosti.
Opcionalno postavite `SALES_EXPORT_TEST_SOCKET` na socket izolirane testne MariaDB
baze; test stvara i uklanja vlastitu nasumično imenovanu bazu, bez učitavanja
konfiguracije webshopa.
