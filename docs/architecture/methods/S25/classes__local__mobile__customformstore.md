# customformstore — Methoden-Doku
**Datei:** `classes/local/mobile/customformstore.php` · **LOC:** 347 · **Subsystem:** S25 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S25_mobile.md)

## Klassenueberblick
`customformstore` ist ein Cache-Store **plus** fachliche Geschaeftslogik fuer die Custom-Form-Zwischenstaende (z.B. der mobile Ionic-Submission-Flow). Es kapselt einen MUC-Cache `mod_booking/customformuserdata` unter dem Key `<userid>_<itemid>_customform` (Get/Set/Delete) und enthaelt daneben serverseitige Validierung (URL/Mail/Select-Kontingent/`enrolusersaction`-Kapazitaet/Pflichtfeld), Fehleruebersetzung zurueck ins Formular-Modell, einen Label-basierten Wert-Lookup sowie Preis-Modifikation gemaess Form-Auswahl. Persistenz: ausschliesslich Cache (kein DB-Write hier; DB nur lesend ueber Singletons). Kollaborateure: `cache`, `singleton_service` (option_settings, booking_answers, user, pricecategory), `bo_availability\conditions\customform` (`return_formelements`), `booking_answers\booking_answers::count_places`. Trotz Klassen-Doc-Block „cartstore" handelt es sich nicht um den Warenkorb-Store — die Copy-&-Paste-Doc-Bloecke (`@author`, „Validates each submission entry") sind durchgaengig irrefuehrend.

## Methoden

### `public function __construct(int $userid, int $itemid)` — public
- **Zweck:** Bindet Store an `userid`+`itemid`, initialisiert Cache-Handle und Cachekey. **Seiteneffekte:** `cache::make('mod_booking','customformuserdata')`. **Bewertung:** A.

### `public function get_customform_data()` — public
- **Zweck:** Liest den gecachten Form-Datensatz. **Seiteneffekte:** `cache->get($cachekey)`. **Rueckgabe:** object oder false (Cache-Miss). **Bewertung:** A.

### `public function set_customform_data($data)` — public
- **Zweck:** Schreibt den Form-Datensatz in den Cache. **Seiteneffekte:** `cache->set`. **Bewertung:** A.

### `public function delete_customform_data()` — public
- **Zweck:** Loescht den Cache-Eintrag (nach erfolgreicher Buchung/Reset). **Seiteneffekte:** `cache->delete`. **Bewertung:** A.

### `public function validation($customform, $data): array` — public
- **Zweck:** Serverseitige Validierung aller Form-Elemente; erzeugt eine `identifier => Fehlermeldung`-Map. Behandelt vier Typen plus Pflichtfeld: `url` (HTTP(S)-URL), `mail` (FILTER_VALIDATE_EMAIL), `select` (Kontingent pro Option-Zeile gegen bereits gebuchte User), `enrolusersaction` (numerisch, >0, ≤ freie Plaetze) sowie `notempty`-Pflicht. **Seiteneffekte:** beim `select`- und `enrolusersaction`-Pfad `singleton_service::get_instance_of_booking_option_settings($data['id'])` + `get_instance_of_booking_answers($settings)` (gecachte Singletons, lesend). **Rueckgabe:** `array $errors`. **Bewertung:** C — dichte Multi-Typ-Logik in einer Methode; der `select`-Kontingent-Check zaehlt die Treffer aus `get_usersonlist()` und vergleicht strikt (`$userbookings->$identifier === $expectedvalue`), wobei eine **bestehende eigene Buchung des Users mitgezaehlt** werden kann (Re-Submit kann faelschlich „ausgebucht" melden). Das `notempty`-Gate greift nur, wenn `$data[$identifier]` gesetzt ist — fehlt das Feld voellig, wird die Pflicht nicht durchgesetzt. `$data['id']` wird ungeprueft vorausgesetzt.

### `public function isvalidhttpurl($url)` — public
- **Zweck:** Prueft, ob `$url` eine gueltige http/https-URL ist. **Seiteneffekte:** keine. **Rueckgabe:** `bool|int` — `false` bei FILTER-Fehlschlag, sonst das `preg_match`-Ergebnis (int 0/1). **Bewertung:** B — funktional korrekt, gibt aber gemischte Typen (int statt strikt bool) zurueck; aufgerufen mit `!self::isvalidhttpurl(...)`, daher unkritisch.

### `public function translate_errors($customform, $errors)` — public
- **Zweck:** Schreibt pro Form-Element das passende `->error` (Meldung oder `false`) zurueck ins Form-Modell fuer das Re-Rendering. **Seiteneffekte:** mutiert `$customform` per Referenz; `unset($customitem)` bricht die Referenz korrekt. **Rueckgabe:** das angereicherte `$customform`. **Bewertung:** A — vorbildlich inkl. Referenz-Cleanup.

### `public function return_value_for_label(string $key)` — public
- **Zweck:** Liefert den vom User submitteten Wert fuer ein Form-Element anhand seines Keys/Labels. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($this->itemid)`, `customform::return_formelements`, `get_customform_data`. **Rueckgabe:** string-Wert, `''` falls Element ohne Wert, oder `false` falls kein Cache/kein Element. **Bewertung:** B — gemischter Rueckgabetyp (`string|false`); Doc-Hinweis „bei gleichem Label erster Wert" trifft nicht ganz, da ueber Key (nicht Label) aufgeloest wird.

### `public function modify_price(float $price, string $priceidentifier): float` — public
- **Zweck:** Modifiziert den Basispreis gemaess Form-Auswahl: `select` addiert den in Zeile-Spalte 4 (`$linearray[3]`) hinterlegten Aufpreis (user-/kategorie-spezifisch), `enrolusersaction` multipliziert den Preis mit der gewaehlten Stueckzahl. **Seiteneffekte:** `get_instance_of_booking_option_settings`, `customform::return_formelements`, `get_customform_data`; indirekt `get_price_for_user`. **Rueckgabe:** `round($price, 2)`. **Bewertung:** C — der uebergebene Parameter `$priceidentifier` wird im Rumpf nie verwendet (toter Parameter; Kategorie-Aufloesung passiert intern in `get_price_for_user`). Reihenfolge-Abhaengigkeit: ein `enrolusersaction`-Faktor multipliziert ggf. bereits addierte Select-Aufpreise mit; bei Faktor 0 (Daten leer/`'0'`) wird der Preis auf 0 gesetzt ohne Guard.

### `public function get_price_and_currency_for_user(string $pricedata): string` — public
- **Zweck:** Formatiert einen Preis (entweder direkt numerisch oder ueber Kategorie-Aufloesung) plus globale Waehrung. **Seiteneffekte:** ggf. `get_price_for_user`; `get_config('booking','globalcurrency')`. **Rueckgabe:** `"<float> <currency>"` oder `""` bei leerem Input. **Bewertung:** B.

### `private function get_price_for_user(string $pricedata, string $priceidentifier = ""): float` — private
- **Zweck:** Loest aus einem komma-/doppelpunkt-codierten `kategorie:preis`-String den fuer den User gueltigen Preis auf; Single-Value-Shortcut, sonst Kategorie-Map mit `default`-Fallback. **Seiteneffekte:** bei leerem `$priceidentifier` `singleton_service::get_instance_of_user($this->userid)` + `get_pricecategory_for_user`. **Rueckgabe:** float (0, falls keine Kategorie und kein `default`). **Bewertung:** B — solide Parser-Logik; Stille 0-Rueckgabe als „kein Preis" kann sich im Aufpreis-Pfad als „kein Aufschlag" tarnen.

### Triviale Properties
Vier geschuetzte Properties (`userid`, `itemid`, `cache`, `cachekey`, Z.46–56) als interne Werthalter.

## Bewertungs-Resümee
Funktional dichte Mischklasse: Cache-Store sauber, aber mit erheblicher fachlicher Validierungs- und Preis-Logik vermischt. Schwaechen: irrefuehrende Copy-Paste-Doc-Bloecke („cartstore"/„Validates each submission entry" auf jeder Methode), toter `$priceidentifier`-Parameter in `modify_price`, gemischte Rueckgabetypen, und die `select`-Kontingentpruefung, die eine bestehende eigene Buchung mitzaehlen kann. Kein Datenverlust-/Sicherheitsproblem, aber wartungs- und edge-case-anfaellig. Klassen-Score **C / P2**.
