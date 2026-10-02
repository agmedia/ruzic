# Sidrene cijene i digitalni cjenik (OpenCart 3)

## Instalacija bez terminala

1. Deployajte sadržaj repozitorija kroz uobičajeni Git/cPanel postupak.
2. U OpenCart administraciji otvorite **Proširenja > Proširenja > Moduli**.
3. Instalirajte **OPG Ružić – Sidrene cijene**. Instalacija je idempotentna: kreira/nadogradi tablice, postavke i evente, generira tajni cron ključ te radi početne snapshot zapise koristeći OpenCart porezni kalkulator.
4. Otvorite **Katalog > Sidrene cijene**, pregledajte bazni datum i zapise koji čekaju potvrdu.

Ako instalacija kroz OpenCart nije moguća, u phpMyAdminu se može jednom pokrenuti [jedinstveni SQL paket](../sql/2026_09_30_anchor_price_opgruzic.sql). Paket očekuje produkcijski prefiks `oc_`. SQL fallback namjerno označava nove snapshote kao `pending`, jer phpMyAdmin nema OpenCart porezni kontekst; prije objave ih treba potvrditi ili zamijeniti provjerenim CSV uvozom. Nakon SQL fallbacka po potrebi dodijelite grupi administratora `access` i `modify` pravo za `extension/module/anchor_price`.

Deinstalacija modula ne briše sidrene cijene, revizijski trag ni arhivu cjenika, ali do ponovne instalacije isključuje javni prikaz i preuzimanje cjenika.

## Bazni datum i prikaz

Za prehrambene proizvode OPG-a zadani bazni datum je **2. 5. 2025.** Datum se može promijeniti u postavkama modula. Postojeći povijesni snapshoti se ne prepisuju automatski: današnji iznos nije dokaz cijene na raniji datum. Unesite stvarnu redovnu cijenu na referentni datum i razlog potvrde kroz admin ili CSV. Datum kreiranja proizvoda u OpenCartu nije konačan dokaz datuma prvog stavljanja u prodaju.

Svaki zapis ima jedinicu **kg** ili **l** i količinu prodajnog pakiranja. Neto/bruto sidrena cijena ostaje cijena cijelog pakiranja; jedinična cijena izračunava se dijeljenjem bruto cijene količinom pakiranja. Ne koristi se težina dostave iz OpenCarta. Npr. 11 EUR za 5 kg daje 2,20 EUR/kg, a 10 EUR za 3 l daje 3,33 EUR/l. Potvrđena sidrena cijena i njezina jedinična cijena prikazuju se na proizvodu i postojećim listama/karticama, uključujući mobilni prikaz. Nepotpuni podaci o jedinici/količini blokiraju novu objavu cjenika.

Za postojeću instalaciju pokrenite [nadogradnju za jedinične cijene](../sql/2026_10_02_anchor_price_units.sql) u phpMyAdminu prije povlačenja novog koda. Paket dodaje polja i poznate količine šest pakiranja te postavlja bazni datum 2. 5. 2025. Vlasnik je 2. 10. 2026. potvrdio da su postojeći iznosi svih šest proizvoda bili isti na 2. 5. 2025.; paket zato ispravlja datume samo tih početnih potvrđenih zapisa, uz provjeru očekivanih iznosa i revizijski trag. Ne prepisuje same iznose, drugačije referentne datume, nepotvrđene zapise niti arhivu. Kasnije se cijene i mjere mogu uređivati kroz Katalog > Sidrene cijene, uz razlog promjene; potvrđeni bazni datum ostaje sačuvan. Potom objavite novi CSV/XML par. Ponovno izvršavanje SQL-a ne prepisuje ručno uređene mjere niti ponavlja revizijski zapis.

## Masovni CSV uvoz

U administraciji odaberite CSV do 5 MB i najviše 10.000 podatkovnih redaka. Prvo ostavite uključeno **Samo provjera**. Tek nakon provjere bez grešaka ponovite unos bez te kvačice. Stvarni uvoz je all-or-nothing transakcija i svaka promjena ulazi u revizijski trag.

Podržani su `;` i `,` razdjelnik te UTF-8 BOM. Obvezni stupci:

- jedan ili više identifikatora: `product_id`, `model`, `sku`, `ean` (podržani su i `barcode` / `barkod`);
- `anchor_price` ili `gross_price` (bruto sidrena cijena);
- `reference_date` u obliku `YYYY-MM-DD`.

Stupci `unit` (`kg` ili `l`) i `package_quantity` (količina pakiranja, npr. `5` ili `0,75`) potrebni su za potvrđene zapise; kod ažuriranja mogu se izostaviti ako postojeći zapis već ima valjanu mjeru. Opcionalni stupci su `net_price`, `status` (`confirmed`, `pending`, `disabled`) i `reason`. Ako je zadano više identifikatora, svi moraju upućivati na isti jednoznačni proizvod. Aktivni proizvod mora imati status `confirmed`.

Primjer:

```csv
sku;anchor_price;reference_date;unit;package_quantity;status;reason
RZ-001;12,90;2025-05-02;kg;5;confirmed;Cijena provjerena prema povijesnom cjeniku
```

## CSV/XML objava i javni URL-ovi

Svaka objava proizvodi atomski par CSV + XML datoteka s istim skupom proizvoda i SHA-256 kontrolnim zbrojevima. Sadrže ID, naziv, model/SKU, proizvođača, jedinicu i količinu pakiranja, aktualnu i sidrenu cijenu po jedinici, redovnu i aktualnu cijenu pakiranja, sidrenu cijenu i datum, barkod, raspoloživost, količinu zalihe, status zalihe i valutu. XML koristi `unit`, `packageQuantity`, `unitPrice`, `anchorUnitPrice`, `brand="OPGRUZIC"` i `location="WEB"`. Aktualna jedinična cijena uključuje trenutačnu akciju/popust; sidrena cijena dolazi iz potvrđenog povijesnog zapisa.

U postavkama modula uređuju se oblik objekta (`webshop`), adresa i oznaka (`WEB`). Konzervativno se kao zadana adresa koristi poslovna adresa iz postavki trgovine; domena je rezervni podatak samo kad poslovna adresa nije unesena. Naziv novih datoteka je `webshop_ADRESA_web_000004_20261002_070000.csv` / `.xml`. ASCII normalizacija uklanja znakove nesigurne za nazive datoteka. Broj pohrane raste po prodajnom mjestu, datum/vrijeme su u Europe/Zagreb zoni. Stare arhivske datoteke ne preimenuju se i ne mijenjaju se njihovi kontrolni zbrojevi.

Službeni izvori: [NN 75/2025, točka VI (prehrana, 2. 5. 2025.)](https://narodne-novine.nn.hr/clanci/sluzbeni/2025_05_75_979.html), [NN 101/2026-1212, točka IV (zadržavanje starog datuma)](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_09_101_1212.html), [NN 101/2026-1213, točke III/VI (sadržaj i naziv datoteke)](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_09_101_1213.html). Izmjene [NN 110/2026-1309](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_09_110_1309.html) i [1310](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_09_110_1310.html) odgađaju početak novih odluka do 17. 11. 2026.; ne mijenjaju referentni datum za prehranu ni elemente cjenika. Propis ne zadaje jedini točan format vremenske oznake; `Ymd_His` je odabrani strojno čitljivi format.

Stabilni javni URL-ovi uvijek vraćaju najnoviju valjanu objavu:

- `index.php?route=information/price_list/download&format=csv`
- `index.php?route=information/price_list/download&format=xml`

Javna stranica `index.php?route=information/price_list` nudi oba formata za svaku objavu tijekom 30 dana. Starije datoteke automatski se brišu i zapis dobiva status `expired`.

## Ispravak već objavljenih cjenika

Nakon nadogradnje za mjere, u phpMyAdminu pokrenite [SQL za ispravke arhive](../sql/2026_10_02_anchor_price_archive_corrections.sql). Povucite novi kod i otvorite **Katalog > Sidrene cijene > Ispravi postojeće cjenike**. Gumb pravi zaseban CSV/XML par za svaku izvornu objavu dostupnu u 30-dnevnoj arhivi. Ponovni klik ne umnožava već napravljene ispravke. Nije potreban terminal niti izmjena cron zadatka.

Izvorni CSV/XML i kontrolni zbrojevi ostaju netaknuti. Ispravljene kopije zadržavaju izvorne aktualne/redovne/akcijske/sidrene iznose, barkodove, dostupnost i zalihe. Dodaju/ispravljaju jedinicu i količinu pakiranja te iz tih arhivskih iznosa računaju €/kg ili €/l. Povijesni datum mijenja se samo uz potvrđeni iznos koji odgovara arhivskom zapisu. Mjere koje već postoje u arhivi imaju prednost pred današnjim mjerama; za stare zapise bez mjere koriste se potvrđeni podaci modula. Ako se CSV i XML ne slažu, nedostaje potvrda ili je kontrolni zbroj pogrešan, ispravak se zaustavlja, bez prepisivanja izvornika.

Naziv kopije ima novi broj pohrane i stvarno vrijeme ispravka. CSV sadrži podatke o izvornoj objavi i ispravku; XML čuva izvorni `generatedAt` i dodaje `sourcePublicationId`, `sourcePublishedAt`, `correctedAt`. Arhiva jasno označava „Ispravak objave #…”. Kopije se čuvaju 30 dana od ispravka. Ispravak starog dana neće zamijeniti noviju dnevnu objavu na stabilnom URL-u najnovijeg cjenika. Ispravci nisu zamjena za redovnu dnevnu objavu.

## cPanel Cron Jobs

Admin modul prikazuje obični cron URL, tajni ključ i gotovi **cPanel cron URL s ključem**. U cPanel Cron Jobs GUI postavite dnevni poziv prije 08:00 po Europe/Zagreb vremenu, primjerice:

```text
curl -fsS "PUNI_URL_IZ_ADMINA" >/dev/null
```

Ako hosting ne nudi `curl`, može se koristiti `wget -qO- "PUNI_URL_IZ_ADMINA" >/dev/null`. URL s ključem je tajna. Integracije koje mogu slati zaglavlja trebaju koristiti osnovni URL i `X-Anchor-Price-Key` zaglavlje.

Objava se može ručno pokrenuti i gumbom **Objavi CSV i XML cjenik**. Objavu blokira bilo koji aktivni proizvod bez potvrđene sidrene cijene ili obveznih podataka.
