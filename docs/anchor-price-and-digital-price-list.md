# Bazne cijene i digitalni cjenik (OpenCart 3)

## Instalacija bez terminala

1. Deployajte sadržaj repozitorija kroz uobičajeni Git/cPanel postupak.
2. U OpenCart administraciji otvorite **Proširenja > Proširenja > Moduli**.
3. Instalirajte **Voćarstvo Ružić d.o.o. – Bazne cijene**. Instalacija je idempotentna: kreira/nadogradi tablice, postavke i evente, generira tajni cron ključ te radi početne snapshot zapise koristeći OpenCart porezni kalkulator.
4. Otvorite **Katalog > Bazne cijene**, pregledajte bazni datum i zapise koji čekaju potvrdu.

Ako instalacija kroz OpenCart nije moguća, u phpMyAdminu se može jednom pokrenuti [jedinstveni SQL paket](../sql/2026_09_30_anchor_price_opgruzic.sql). Paket očekuje produkcijski prefiks `oc_`. SQL fallback namjerno označava nove snapshote kao `pending`, jer phpMyAdmin nema OpenCart porezni kontekst; prije objave ih treba potvrditi ili zamijeniti provjerenim CSV uvozom. Nakon SQL fallbacka po potrebi dodijelite grupi administratora `access` i `modify` pravo za `extension/module/anchor_price`.

Deinstalacija modula ne briše bazne cijene, revizijski trag ni arhivu cjenika, ali do ponovne instalacije isključuje javni prikaz i preuzimanje cjenika.

## Bazni datum i prikaz

Za prehrambene proizvode trgovca zadani bazni datum je **2. 5. 2025.** Bazni datum za nove zapise može se promijeniti u postavkama modula. Datum pojedinog postojećeg zapisa, uključujući već potvrđenu baznu cijenu, može se promijeniti kroz **Katalog > Bazne cijene > Uredi > Referentni datum**, uz obvezan razlog promjene. Dopušten je valjan datum između postavljenog baznog datuma i današnjeg datuma u zoni Europe/Zagreb. U povijesti se čuvaju stari i novi datum, korisnik i razlog. Postojeći povijesni snapshoti se ne prepisuju automatski: današnji iznos nije dokaz cijene na raniji datum. Potvrdite povijesnu cijenu kroz admin ili CSV. Ako je prehrambeni proizvod na referentni dan bio na sniženju, primijenite najnižu cijenu u 30 dana prije početka te prodaje prema članku 3. Pravilnika o isticanju bazne cijene. Datum kreiranja proizvoda u OpenCartu nije konačan dokaz datuma prvog stavljanja u prodaju. Tehnička mogućnost izmjene ne mijenja pravila o propisanom referentnom datumu.

Svaki zapis ima jedinicu **kg** ili **l** i količinu prodajnog pakiranja. Neto/bruto bazna cijena ostaje cijena cijelog pakiranja; jedinična cijena izračunava se dijeljenjem bruto cijene količinom pakiranja. Ne koristi se težina dostave iz OpenCarta. Npr. 11 EUR za 5 kg daje 2,20 EUR/kg, a 10 EUR za 3 l daje 3,33 EUR/l. Potvrđena bazna cijena i njezina jedinična cijena prikazuju se na proizvodu i postojećim listama/karticama, uključujući mobilni prikaz. Nepotpuni podaci o jedinici/količini blokiraju novu objavu cjenika.

Za postojeću instalaciju pokrenite [nadogradnju za jedinične cijene](../sql/2026_10_02_anchor_price_units.sql) u phpMyAdminu prije povlačenja novog koda. Paket dodaje polja i poznate količine šest pakiranja te postavlja bazni datum 2. 5. 2025. Vlasnik je 2. 10. 2026. potvrdio da su postojeći iznosi svih šest proizvoda bili isti na 2. 5. 2025.; paket zato ispravlja datume samo tih početnih potvrđenih zapisa, uz provjeru očekivanih iznosa i revizijski trag. Ne prepisuje same iznose, drugačije referentne datume, nepotvrđene zapise niti arhivu. Kasnije se cijene, mjere i referentni datum mogu uređivati kroz Katalog > Bazne cijene, uz razlog promjene; prethodne vrijednosti ostaju u povijesti promjena. Potom objavite novi CSV/XML par. Ponovno izvršavanje SQL-a ne prepisuje ručno uređene mjere niti ponavlja revizijski zapis. Omogućavanje uređivanja datuma ne zahtijeva dodatni SQL niti samo po sebi mijenja postojeće podatke ili objavljene cjenike.

## Masovni CSV uvoz

U administraciji odaberite CSV do 5 MB i najviše 10.000 podatkovnih redaka. Prvo ostavite uključeno **Samo provjera**. Tek nakon provjere bez grešaka ponovite unos bez te kvačice. Stvarni uvoz je all-or-nothing transakcija i svaka promjena ulazi u revizijski trag.

Podržani su `;` i `,` razdjelnik te UTF-8 BOM. Obvezni stupci:

- jedan ili više identifikatora: `product_id`, `model`, `sku`, `ean` (podržani su i `barcode` / `barkod`);
- `anchor_price` ili `gross_price` (bruto bazna cijena);
- `reference_date` u obliku `YYYY-MM-DD`.

Stupci `unit` (`kg` ili `l`) i `package_quantity` (količina pakiranja, npr. `5` ili `0,75`) potrebni su za potvrđene zapise; kod ažuriranja mogu se izostaviti ako postojeći zapis već ima valjanu mjeru. Opcionalni stupci su `net_price`, `status` (`confirmed`, `pending`, `disabled`) i `reason`. Ako je zadano više identifikatora, svi moraju upućivati na isti jednoznačni proizvod. Aktivni proizvod mora imati status `confirmed`.

Primjer:

```csv
sku;anchor_price;reference_date;unit;package_quantity;status;reason
RZ-001;12,90;2025-05-02;kg;5;confirmed;Cijena provjerena prema povijesnom cjeniku
```

## Objava i preuzimanje

Trgovac je **Voćarstvo Ružić d.o.o.**, OIB **96162140350**. Jedan cjenik vrijedi za sva prodajna mjesta. U postavkama modula može se urediti OIB. Za izdanje br. 50 objavljeno 18. 11. 2026. u 07:00 po zagrebačkom vremenu naziv je `96162140350_ZA_SVA_PRODAJNA_MJESTA_ROBA_000050_20261118_0700.csv`. XML nosi isti naziv s nastavkom `.xml`. Redni broj nastavlja postojeći niz.

CSV koristi UTF-8 i točku-zarez. CSV i XML imaju deset polja istim redom: naziv, šifra, marka, neto količina, jedinica mjere, maloprodajna cijena s oznakom posebnog oblika prodaje, cijena po jedinici, barkod, EAN barkod i bazna cijena. Iznosi su u eurima. U CSV-u npr. `9,50 (da)` označuje akcijsku cijenu, a `12,00 (ne)` redovnu prodaju. XML nosi brojčanu cijenu i atribut `sale`. Količina pakiranja služi izračunu €/kg ili €/l. Aktualna jedinična cijena prikazuje se uz prodajnu cijenu, a uz baznu cijenu stoje datum i cijena pakiranja.

Javna stranica je `index.php?route=information/price_list`. Stalne adrese za važeći cjenik su:

- `index.php?route=information/price_list/download&format=csv`
- `index.php?route=information/price_list/download&format=xml`

Važeći par dostupan je do zamjene, a prethodni još 15 dana nakon zamjene. Javno preuzimanje starih objava čita povijesne iznose i prikazuje deset javnih polja pod nazivom s OIB-om, izvornim brojem pohrane i vremenom objave. Izvorne datoteke s kontrolnim zbrojevima dostupne su kroz administraciju. Gumb za ispravak starih mjera radi s prethodnim formatom i čuva vezu s izvornom objavom.

## Akcijske cijene

Za redovnu akciju unesite Special u kartici Akcija na artiklu. Sustav bilježi primijenjene maloprodajne cijene s PDV-om za pojedini artikl, trgovinu i grupu kupaca. Uz akcijsku cijenu prikazuje najnižu cijenu iz 30 dana prije početka te akcije. Usporedni iznos vrijedi do kraja iste akcije. Kada nema potpune evidencije, u istoj kartici upišite najnižu cijenu koju ste potvrdili iz poslovnih evidencija. Kupci do potvrde ili do ispravka previsoke akcijske cijene vide redovnu ponudu. Modul Bazne cijene prikazuje šifru artikla koji čeka provjeru. Snižena maloprodajna cijena i njezina jedinična cijena ulaze u javni cjenik s oznakom `da`.

## Nadogradnja postojeće instalacije

1. Postavite izmjene koda na hosting. U **Proširenja > Modifikacije** osvježite OCMOD, zatim očistite predmemoriju Twig predložaka u administraciji.
2. Otvorite **Katalog > Bazne cijene**, provjerite OIB, potvrdite povijesnu cijenu i količinu pakiranja aktivnih artikala te kliknite **Objavi CSV i XML cjenik** za prvo izdanje u novom formatu. Postojećoj instalaciji koja već objavljuje jedinične cijene nije potrebna nova SQL migracija.
3. U cPanel Cron Jobs postavite postojeći zaštićeni URL na raspored `* * * * *`. Time se svake minute provjeravaju zakazani početak i kraj akcije, datum dostupnosti i promjene napravljene izvan OpenCarta. Ključ i gotov URL nalaze se u postavkama. Primjer naredbe:

   ```sh
   curl -fsS "PUNI_CRON_URL_IZ_ADMINISTRACIJE" >/dev/null
   ```

Spremanje artikla, brza izmjena, brisanje, potvrda bazne cijene i dovršeni uvoz pokreću provjeru odmah. Nova verzija nastaje ako su promijenjeni podaci javnog cjenika. Zadnji poziv crona i razlog neuspjele objave vide se u modulu. Ako artiklu nedostaje potvrđena bazna cijena ili mjera, poruka sadrži njegov ID i naziv. Nakon ispravka modul ponavlja objavu. Prethodni cjenik dostupan je dok oba nova formata ne prođu provjeru.

Članak 3. stavak 5. propisuje objavu najkasnije do 11:00 na dan izmjene. Promjene koje trgovac može planirati treba provesti prije tog roka. Naknadno unesena promjena objavljuje se sa stvarnim vremenom objave.

[Pravilnik o načinu objave cjenika proizvoda, NN 132/2026](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_11_132_1584.html) · [Pravilnik o isticanju bazne cijene proizvoda, NN 132/2026](https://narodne-novine.nn.hr/clanci/sluzbeni/2026_11_132_1583.html)
