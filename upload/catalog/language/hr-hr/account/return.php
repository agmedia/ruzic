<?php
// Croatian   v.2.x.x     Datum: 01.10.2014		Author: Gigo (Igor Ilić - igor@iligsoft.hr)
// Heading
$_['heading_title']      = 'Jednostrani raskid ugovora i povrat';

// Text
$_['text_account']       = 'Korisnički račun';
$_['text_return']        = 'Jednostrani raskid ugovora i povrat';
$_['text_return_detail'] = 'Detalji zahtjeva';
$_['text_description']   = '<p>Ovim obrascem možete obavijestiti OPG Ružić o jednostranom raskidu ugovora i zatražiti povrat kupljenih artikala. Nakon slanja primit ćete kopiju zahtjeva na e-mail.</p><p>Potrošač ima pravo na jednostrani raskid ugovora u zakonskom roku, pod uvjetima propisanim važećim propisima i uvjetima kupnje.</p>';
$_['text_order']         = 'Podaci kupca i računa';
$_['text_product']       = 'Artikli za povrat';
$_['text_reason']        = 'Razlog povrata';
$_['text_message']       = '<p>Vaš zahtjev za jednostrani raskid ugovora i povrat je zaprimljen.</p><p>Kopija zahtjeva poslana je na navedenu e-mail adresu. OPG Ružić kontaktirat će Vas nakon obrade.</p>';
$_['text_return_id']     = 'Zahtjev broj:';
$_['text_order_id']      = 'Broj računa:';
$_['text_date_ordered']  = 'Datum računa:';
$_['text_status']        = 'Status:';
$_['text_date_added']    = 'Datum dodavanja:';
$_['text_comment']       = 'Komentari uz zahtjev za povrat';
$_['text_history']       = 'Povijest povrata';
$_['text_empty']         = 'Do sad niste napravili niti jedan povrat!';
$_['text_agree']         = 'Pročitao sam i slažem se s <a href="%s" class="agree"><b>%s</b></a>';
$_['text_agree_fallback'] = 'Potvrđujem da su navedeni podaci točni i da ovim putem podnosim zahtjev za jednostrani raskid ugovora i povrat.';
$_['text_return_products_title'] = 'Artikli koje vraćate';
$_['mail_return_admin_subject']    = '%s - novi zahtjev za raskid i povrat #%s';
$_['mail_return_customer_subject'] = '%s - zaprimili smo Vaš zahtjev #%s';
$_['mail_return_admin_intro']      = 'Zaprimljen je novi zahtjev za jednostrani raskid ugovora i povrat putem digitalnog obrasca.';
$_['mail_return_customer_intro']   = 'Zaprimili smo Vaš zahtjev za jednostrani raskid ugovora i povrat. U nastavku je kopija podataka koje ste poslali.';
$_['mail_return_customer_footer']  = 'Kontaktirat ćemo Vas nakon obrade zahtjeva.';
$_['mail_return_label_return_id']  = 'Broj zahtjeva';

// Column
$_['column_return_id']   = 'Povrata artikala broj';
$_['column_order_id']    = 'Broj računa';
$_['column_status']      = 'Status';
$_['column_date_added']  = 'Datum dodavanja';
$_['column_customer']    = 'Kupac';
$_['column_product']     = 'Naziv artikla';
$_['column_model']       = 'Model';
$_['column_quantity']    = 'Količina';
$_['column_price']       = 'Cijena';
$_['column_opened']      = 'Otvoren';
$_['column_comment']     = 'Komentar';
$_['column_reason']      = 'Razlog';
$_['column_action']      = 'Akcija';


// Entry
$_['entry_order_id']     = 'Narudžba broj';
$_['entry_date_ordered'] = 'Datum narudžbe';
$_['entry_invoice_number'] = 'Broj računa';
$_['entry_invoice_date']   = 'Datum računa';
$_['entry_firstname']    = 'Ime';
$_['entry_lastname']     = 'Prezime';
$_['entry_email']        = 'E-mail';
$_['entry_telephone']    = 'Telefon';
$_['entry_product']      = 'Naziv artkla';
$_['entry_model']        = 'Model';
$_['entry_product_code'] = 'Šifra artikla';
$_['entry_quantity']     = 'Količina';
$_['entry_price']        = 'Cijena';
$_['entry_reason']       = 'Razlog povrata (nije obavezno)';
$_['entry_opened']       = 'Artikl je otvoren';
$_['entry_fault_detail'] = 'Napomena';
$_['entry_refund_iban']  = 'IBAN za povrat sredstava (nije obavezno)';
$_['button_add_product'] = 'Dodaj artikl';
// $_['entry_captcha']      = 'Upišite kod u polje (kućicu) ispod';

// Error
$_['text_error']         = 'Zahtjev za povrat koji ste zatražili nije pronađen!';
$_['error_order_id']     = 'Broj računa je obavezan podatak!';
$_['error_date_ordered'] = 'Unesite ispravan datum računa.';
$_['error_firstname']    = 'Ime mora sadržavati između 1 i 32 znaka!';
$_['error_lastname']     = 'Prezime mora sadržavati između 1 i 32 znaka!';
$_['error_email']        = 'Čini se da je navedena e-mail adresa neispravna!';
$_['error_telephone']    = 'Telefon mora sadržavati između 3 i 32 znaka!';
$_['error_product']      = 'Naziv artikla mora imati više od 3 i manje od 255 znakova!';
$_['error_model']        = 'Model artikla mora imati više od 3 i manje od 64 znaka!';
$_['error_reason']       = 'Morate odabrati razlog povrata artikla!';
$_['error_return_products'] = 'Unesite barem jedan artikl sa šifrom, količinom većom od 0 i brojčanom cijenom (zarez ili točka za decimale).';
$_['error_refund_iban']     = 'Unesite ispravan IBAN ili ostavite polje prazno.';
// $_['error_captcha']      = 'Kod za provjeru (verifikaciju) ne odgovara onom sa slike!'; // postojalo u verziji OC 2.0.3.1
$_['error_agree']        = 'Upozorenje: Morate prihvatiti (složiti se s) %s!';
$_['error_agree_fallback'] = 'Morate potvrditi suglasnost prije slanja zahtjeva.';
