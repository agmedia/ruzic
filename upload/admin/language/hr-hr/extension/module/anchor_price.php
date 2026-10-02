<?php
// Naslov
$_['heading_title'] = 'OPG Ružić – Sidrene cijene';

// Tekst
$_['text_extension'] = 'Proširenja';
$_['text_list'] = 'Registar sidrenih cijena';
$_['text_edit'] = 'Uredi sidrenu cijenu';
$_['text_filter'] = 'Filtri';
$_['text_publications'] = 'Dnevni cjenici';
$_['text_settings'] = 'Postavke i automatizacija';
$_['text_import'] = 'Masovni CSV uvoz';
$_['text_import_errors'] = 'Uvoz nije primijenjen zbog sljedećih grešaka:';
$_['text_no_results'] = 'Nema pronađenih zapisa.';
$_['text_all_statuses'] = 'Svi statusi';
$_['text_status_confirmed'] = 'Potvrđeno';
$_['text_status_pending'] = 'Čeka provjeru';
$_['text_status_disabled'] = 'Isključeno';
$_['text_system'] = 'Sustav';
$_['text_missing_count'] = 'Aktivnih artikala bez sidrene cijene: %s';
$_['text_success_edit'] = 'Uspješno: Sidrena cijena i revizijski trag su ažurirani.';
$_['text_success_sync'] = 'Uspješno: Kreirano je %s nedostajućih sidrenih cijena.';
$_['text_success_publish'] = 'Uspješno: Objavljeni su CSV i XML cjenik: %s';
$_['text_success_repair_archive'] = 'Uspješno: Kreirano je %s ispravljenih objava; %s objava već ima ispravak. Izvorne objave ostaju sačuvane.';
$_['text_archive_correction'] = 'Ispravak objave #%s; izvorno objavljeno %s';
$_['text_success_settings'] = 'Uspješno: Postavke sidrenih cijena su spremljene.';
$_['text_success_import_dry_run'] = 'Provjera je uspješna: %s CSV redaka je valjano. Podaci nisu promijenjeni.';
$_['text_success_import'] = 'CSV uvoz je dovršen: kreirano %s, ažurirano %s zapisa.';
$_['text_reference_rule'] = 'Bazni datum za prehrambene proizvode je 2. 5. 2025. Unesite stvarnu cijenu koja je vrijedila na taj datum, ne današnju cijenu. Za artikle kasnije stavljene u prodaju koristi se prva prodajna cijena. Automatski zapisi čekaju provjeru; sve ručne izmjene imaju revizijski trag.';
$_['text_select_unit'] = 'Odaberite kg ili l';
$_['text_measure_missing'] = 'Nedostaje kg/l ili količina';
$_['text_cron_help'] = 'Za tehničke integracije pozovite URL svaki dan prije 08:00 Europe/Zagreb i pošaljite ključ u zaglavlju X-Anchor-Price-Key. Generiraju se CSV i XML.';
$_['text_cron_cpanel_help'] = 'U cPanel Cron Jobs GUI unesite HTTP poziv ovog potpunog URL-a (npr. curl -fsS "URL" >/dev/null). Ključ je tajan i URL se ne smije javno dijeliti.';
$_['text_audit'] = 'Revizijski trag';

// Stupci
$_['column_product'] = 'Artikl';
$_['column_model'] = 'Model / SKU';
$_['column_net_price'] = 'Neto sidrena cijena pakiranja';
$_['column_gross_price'] = 'Bruto sidrena cijena pakiranja';
$_['column_package_quantity'] = 'Količina pakiranja';
$_['column_unit_price'] = 'Aktualna jedinična cijena';
$_['column_anchor_unit_price'] = 'Sidrena jedinična cijena';
$_['column_reference_date'] = 'Referentni datum';
$_['column_status'] = 'Status';
$_['column_action'] = 'Radnja';
$_['column_location'] = 'Prodajno mjesto';
$_['column_sequence'] = 'Redni broj';
$_['column_filename'] = 'Datoteka';
$_['column_products'] = 'Artikala';
$_['column_published'] = 'Objavljeno';
$_['column_user'] = 'Korisnik';
$_['column_reason'] = 'Razlog';
$_['column_before'] = 'Prije';
$_['column_after'] = 'Poslije';
$_['column_date_added'] = 'Datum';

// Polja
$_['entry_filter_name'] = 'Naziv artikla';
$_['entry_filter_model'] = 'Model / SKU';
$_['entry_filter_status'] = 'Status provjere';
$_['entry_date_from'] = 'Referentni datum od';
$_['entry_date_to'] = 'Referentni datum do';
$_['entry_price'] = 'Neto iznos pakiranja';
$_['entry_gross_price'] = 'Bruto iznos pakiranja';
$_['entry_unit'] = 'Jedinica mjere';
$_['entry_package_quantity'] = 'Količina u pakiranju';
$_['entry_publication_type'] = 'Oblik objekta / prodaje';
$_['entry_publication_address'] = 'Adresa objekta';
$_['entry_publication_code'] = 'Oznaka objekta';
$_['entry_reference_date'] = 'Referentni datum';
$_['entry_verification_status'] = 'Status provjere';
$_['entry_reason'] = 'Razlog promjene';
$_['entry_default_unit'] = 'Zadana prodajna jedinica';
$_['entry_reference_date_setting'] = 'Bazni referentni datum';
$_['entry_cron_url'] = 'URL dnevnog cron zadatka';
$_['entry_cron_key'] = 'Cron ključ';
$_['entry_cron_cpanel'] = 'cPanel cron URL s ključem';
$_['entry_public_urls'] = 'Javni URL-ovi najnovijeg cjenika';
$_['entry_import_file'] = 'CSV datoteka';
$_['entry_dry_run'] = 'Samo provjera';

// Gumbi
$_['button_filter'] = 'Filtriraj';
$_['button_clear'] = 'Očisti';
$_['button_sync'] = 'Kreiraj nedostajuće sidrene cijene';
$_['button_publish'] = 'Objavi CSV i XML cjenik';
$_['button_repair_archive'] = 'Ispravi postojeće cjenike';
$_['button_settings'] = 'Spremi postavke';
$_['button_download'] = 'Preuzmi';
$_['button_import'] = 'Provjeri / uvezi CSV';

// Pomoć
$_['help_reason'] = 'Obvezno. Razlog se trajno čuva u revizijskom tragu.';
$_['help_gross_price'] = 'Sidrena cijena cijelog pakiranja s porezom na referentni datum (za bazne prehrambene artikle 2. 5. 2025.). Možete je naknadno ispraviti uz obvezan razlog, ali ne prepisivati današnjom cijenom.';
$_['help_package_quantity'] = 'Neto količina u odabranoj jedinici, npr. 5 za paket od 5 kg jabuka ili 3 za 3 l soka. Jedinična cijena računa se dijeljenjem cijene pakiranja tom količinom; nije dostavna težina.';
$_['help_publication_filename'] = 'Naziv datoteke: oblik_adresa_oznaka_brojpohrane_datum_vrijeme.csv/xml. Zadano: webshop, adresa objekta i WEB. Oblik i oznaka koriste slova, brojke, crticu ili podvlaku; adresa se pretvara u siguran naziv.';
$_['help_repair_archive'] = 'Kreira nove CSV/XML ispravke postojećih cjenika s datumom 2. 5. 2025., jediničnim cijenama po kg/l i usklađenim nazivima datoteka. Cijene i zalihe uzimaju se iz izvorne objave, ne iz današnjeg stanja. Izvornici se ne mijenjaju; ponovni klik ne stvara duple ispravke.';
$_['help_default_unit'] = 'Jedinica i količina unose se zasebno za svaki proizvod; globalna jedinica ne koristi se u cjeniku.';
$_['help_reference_date_setting'] = 'Primjenjuje se na nove bazne snapshot zapise. Već potvrđeni povijesni zapisi se ne prepisuju.';
$_['help_import_file'] = 'Do 5 MB / 10.000 redaka. Obvezno: jedan od product_id, model, sku ili ean; zatim anchor_price (ili gross_price) za cijelo pakiranje i reference_date. Opcionalno: unit (kg/l), package_quantity, net_price, status i reason. Bez unit/package_quantity zadržavaju se postojeće vrijednosti; potvrđeni zapisi moraju imati oboje. Cijena mora odgovarati navedenom povijesnom datumu.';
$_['help_dry_run'] = 'Preporučeno: provjeri sve retke bez upisa. Isključite kvačicu tek kada je provjera bez grešaka.';

// Upozorenja i greške
$_['warning_publication_due'] = 'Dnevni cjenik nije objavljen do 08:00.';
$_['warning_product_added'] = 'Datum unosa proizvoda u sustav je %s, nakon baznog datuma. To nije nužno datum prve prodaje: bazni datum koristite samo ako znate da je proizvod tada već bio u prodaji i možete potvrditi tadašnju cijenu; obrazložite promjenu.';
$_['error_permission'] = 'Upozorenje: Nemate ovlasti za izmjenu modula Sidrene cijene.';
$_['error_not_installed'] = 'Tablice modula ne postoje. Najprije instalirajte modul kroz Proširenja.';
$_['error_not_found'] = 'Tražena sidrena cijena nije pronađena.';
$_['error_price'] = 'Unesite ispravan nenegativan neto iznos.';
$_['error_gross_price'] = 'Unesite ispravan nenegativan bruto iznos.';
$_['error_unit'] = 'Odaberite kg ili l.';
$_['error_package_quantity'] = 'Unesite pozitivnu količinu u pakiranju (najviše 6 decimalnih mjesta).';
$_['error_publication_settings'] = 'Unesite oblik (1–24 slova/brojke/crtica/podvlaka), adresu (1–120 znakova bez HTML-a) i oznaku (1–16 slova/brojke/crtica/podvlaka).';
$_['error_reference_date'] = 'Unesite ispravan datum u obliku GGGG-MM-DD.';
$_['error_status'] = 'Odaberite ispravan status provjere.';
$_['error_reason'] = 'Razlog mora sadržavati između 3 i 255 znakova.';
$_['error_default_unit'] = 'Zadana jedinica mora sadržavati između 1 i 16 znakova.';
$_['error_reference_date_setting'] = 'Bazni referentni datum mora biti valjan datum koji nije u budućnosti.';
$_['error_import_upload'] = 'Odaberite CSV datoteku za uvoz.';
$_['error_import_file'] = 'Datoteka mora biti .csv, veličine između 1 B i 5 MB.';
$_['error_import_rows'] = 'CSV nije primijenjen. Pronađeno je %s grešaka.';
$_['error_file_missing'] = 'Datoteka objave nije dostupna ili joj je istekao rok čuvanja od 30 dana.';
