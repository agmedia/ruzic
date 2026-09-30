# Jednostrani raskid ugovora i povrat

Modul proširuje postojeći OpenCart povrat tako da obrazac mogu poslati prijavljeni kupci i gosti. Sprema broj i datum računa, kontakt, opcionalni IBAN, više stavki (šifra, količina i cijena), opcionalni razlog, napomenu i privolu. Nakon uspješnog spremanja šalje potvrdu kupcu te obavijest administratorima trgovine.

U administraciji su dostupni popis, detalj zahtjeva, povijest statusa i izvoz označenih zahtjeva. Ako je PhpSpreadsheet dostupan, izvoz je XLSX; inače se preuzima CSV. Vrijednosti se zapisuju kao tekst, a CSV rezervni izvoz neutralizira početne znakove formula.

## Produkcijska instalacija

1. Napraviti sigurnosnu kopiju datoteka i baze te uključiti način održavanja.
2. U phpMyAdminu odabrati produkcijsku bazu i jednom izvršiti cijeli paket `sql/2026_09_30_unilateral_termination_return.sql`.
3. Provjeriti da završni upit prikazuje stupce `invoice_number`, `invoice_date`, `refund_iban` i `return_items` te HR/EN SEO retke.
4. Tek nakon uspješnog SQL-a isporučiti datoteke kroz uobičajeni Git deployment.
5. U OpenCart administraciji otvoriti Extensions > Modifications i pritisnuti Refresh, zatim očistiti predmemoriju teme.
6. Isključiti način održavanja nakon provjere.

SQL je idempotentan i može se ponovno izvršiti. Predviđen je za prefiks `oc_`, koji koristi ovaj projekt. Ako produkcija koristi drugi prefiks, prije izvršavanja dosljedno zamijeniti `oc_` stvarnim prefiksom.

## Postavke e-pošte

Pošiljatelj se uzima iz SMTP korisničkog računa kada je on valjana adresa, a inače iz adrese trgovine. Administratorska obavijest šalje se na adresu trgovine i na dodatne adrese za upozorenja. Kupac prima zasebnu potvrdu na adresu unesenu u obrazac. Prije objave provjeriti OpenCart Mail postavke i poslati probni zahtjev.

## Provjera nakon instalacije

- otvoriti footer poveznicu na hrvatskom i engleskom;
- poslati zahtjev kao gost i kao prijavljeni kupac;
- provjeriti obavezna polja, neispravan datum, neispravan opcionalni IBAN i privolu;
- dodati barem dvije stavke te provjeriti njihov prikaz u administraciji;
- potvrditi zapis u Sales > Returns i primitak poruke kupca i administratora;
- označiti zahtjev i provjeriti XLSX ili CSV izvoz;
- u testnoj stavci započeti vrijednost znakom `=`, `+`, `-` ili `@` te potvrditi da je izvoz tretira kao tekst;
- promijeniti status uz uključenu obavijest i potvrditi poruku kupcu.

## Povrat verzije

Kod se vraća standardnim Git povratom deploymenta. Nova četiri stupca namjerno se ne brišu pri povratu verzije kako se ne bi izgubili poslani zahtjevi. Ako je potpuno uklanjanje podataka nužno, prvo ih izvesti i napraviti zasebnu sigurnosnu kopiju baze.
