# shoppingcart — Methoden-Doku
**Datei:** `classes/option/fields/shoppingcart.php` · **LOC:** 327 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`shoppingcart` ist der Bruecken-Feld-Handler (erweitert `field_base`) zwischen mod_booking-Buchungsoptionen und dem Plugin `local_shopping_cart` (Ratenzahlung/Installment u.a., Feld-Praefix `sch_`). POSTSAVE-Save (`MOD_BOOKING_EXECUTION_POSTSAVE`), da die `shopping_cart_handler`-Speicherung die Option-id braucht. Jeder Hook ist defensiv mit `class_exists('local_shopping_cart\shopping_cart_handler')` gegated, sodass die Klasse ohne installiertes Cart-Plugin inert bleibt. Saemtliche fachliche Form-/Save-/Validate-Logik wird an `shopping_cart_handler('mod_booking', 'option')` delegiert; eigenstaendig ist nur ein massgeschneidertes Change-Tracking ueber alle `sch_*`-Formularschluessel. Kollaborateure: `local_shopping_cart\shopping_cart_handler`, `singleton_service` (Settings), `get_string_manager`/`get_string`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Standard-Save des Feldwerts und Sammeln von Change-Tracking-Eintraegen — nur wenn das Cart-Plugin vorhanden ist.
- **Seiteneffekte:** Instanziiert `shopping_cart_handler` (laut Kommentar nur „um die Konstanten sicher zu haben"), ruft `parent::prepare_save_field(..., 0)` und das eigene `check_for_changes($formdata, $instance)` auf. Ohne Cart-Plugin: `return []`.
- **Rueckgabe:** `array` der Aenderungen bzw. `[]`. PHPDoc `@return string` ist falsch.
- **Bewertung:** B — korrekt gegated; Smell: die `$schhandler`-Instanz wird nur fuer Seiteneffekt-Autoloading erzeugt und ungenutzt verworfen (familientypisch, aber unklarer Kontrakt).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Delegiert den Aufbau der Cart-/Installment-Formularfelder an `shopping_cart_handler::definition`.
- **Seiteneffekte:** Mutiert `$mform` indirekt ueber den Handler. Ohne Cart-Plugin: No-op.
- **Rueckgabe:** void.
- **Bewertung:** A — reine, korrekt gegatete Delegation.

### `public static function validation(array $data, array $files, array &$errors): void` — public static
- **Zweck:** Reicht die Formularvalidierung an `shopping_cart_handler::validation` durch.
- **Seiteneffekte:** Mutiert `$errors` (per Referenz) ueber den Handler. Ohne Cart-Plugin: No-op.
- **Rueckgabe:** void (anders als `field_base::validation`, das `$errors` zurueckgibt — hier wird der Rueckgabewert weggelassen).
- **Bewertung:** A — korrekt; der ungleiche Rueckgabe-Stil zur Basisklasse ist harmlos, da der Aufrufer die per-Referenz-`$errors` nutzt.

### `public static function save_data(stdClass &$formdata, stdClass &$option, int $index = 0): void` — public static
- **Zweck:** POSTSAVE-Persistenz der Cart-Konfiguration an `shopping_cart_handler::save_data`.
- **Seiteneffekte:** Delegiert (DB-Writes im Handler). Ohne Cart-Plugin: No-op. Der Parameter `$index` wird entgegengenommen, aber nicht an den Handler weitergereicht.
- **Rueckgabe:** void.
- **Bewertung:** B — korrekt; `$index` ist toter Parameter.

### `public static function set_data(stdClass &$data, booking_option_settings $settings): void` — public static
- **Zweck:** Befuellt das Formular mit den gespeicherten Cart-Werten via `shopping_cart_handler::set_data`.
- **Seiteneffekte:** Instanziiert `shopping_cart_handler('mod_booking', 'option', $data->id)` und ruft `set_data($data)`. Ohne Cart-Plugin: No-op.
- **Rueckgabe:** void.
- **Bewertung:** B — funktional korrekt; `$data->id` wird ungeprueft als dritter Konstruktor-Parameter verwendet. Bei einer neuen, noch nicht gespeicherten Option ohne gesetztes `id` kann das eine undefinierte-Property-Notice/Null-Uebergabe ausloesen (siehe Findings, P3).

### `public function check_for_changes(stdClass $formdata, field_base $self, $mockdata = '', string|null $key = null, $value = ''): array` — public
- **Zweck:** Eigene, signaturkompatible Ueberschreibung des Basisklassen-Change-Trackings, spezialisiert auf alle `sch_*`-Schluessel: vergleicht neue Formularwerte gegen die zuvor gespeicherten und baut menschenlesbare Aenderungseintraege.
- **Seiteneffekte:** Frueh-Returns bei Exklusion (`MOD_BOOKING_CLASSES_EXCLUDED_FROM_CHANGES_TRACKING`), neuer Option (`empty($formdata->id)`) oder fehlenden `sch_*`-Keys. Sonst: laedt `singleton_service::get_instance_of_booking_option_settings($formdata->id)`, ruft `$self::set_data($mockdata, $settings)` (loest selbst einen `shopping_cart_handler`-Roundtrip aus), und vergleicht je `sch_*`-Key die normalisierten Alt-/Neuwerte. Fehlende Alt-Keys werden uebersprungen (Schutz vor Race-False-Positives); Fehler beim Laden werden gefangen und liefern `[]` (fail-safe gegen Falschmeldungen).
- **Rueckgabe:** `[]` oder `['changes' => [ key => ['changes' => ['fieldname','oldvalue','newvalue','formkey']]]]`.
- **Bewertung:** C — durchdacht (fail-safe try/catch, Skip fehlender Alt-Keys, Normalisierung), aber schwergewichtig: laedt fuer das blosse Aenderungs-Logging die kompletten Option-Settings plus einen weiteren Cart-Handler-`set_data`-Roundtrip. Der Parameter `$key`/`$value` der Signatur wird nicht genutzt (nur zur Interface-Kompatibilitaet).

### `private function normalize_value_for_change_tracking($value): string` — private
- **Zweck:** Normalisiert Skalare/Arrays/Bools/null auf einen vergleichbaren, sortierten String, damit Vergleich und Anzeige stabil sind.
- **Seiteneffekte:** Keine. Arrays werden elementweise getrimmt, `null`→`''`, `bool`→`'1'/'0'`, dann `sort()` + `implode(', ')`.
- **Rueckgabe:** `string`.
- **Bewertung:** A — saubere, deterministische Normalisierung (Sortierung macht den Array-Vergleich reihenfolge-unabhaengig).

### `private function get_field_label_from_key(string $key): string` — private
- **Zweck:** Wandelt einen `sch_<name>`-Key in ein lesbares Label.
- **Seiteneffekte:** `get_string_manager()->string_exists($fieldname, 'local_shopping_cart')`; bei Treffer `get_string($fieldname, 'local_shopping_cart')`, sonst `str_replace('_',' ', $fieldname)`.
- **Rueckgabe:** `string`.
- **Bewertung:** A — robuster Label-Fallback ueber den String-Manager.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id` = `MOD_BOOKING_OPTION_FIELD_SHOPPPINGCART` [sic, Konstantenname mit Tippfehler], `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers` = `['sch_allowinstallment']`, `$incompatiblefields`, Z.46–80).

## Bewertungs-Resümee
Eine durchgehend defensiv gegatete Integrationsbruecke zu `local_shopping_cart`: die fachliche Form-/Save-/Validate-Logik wird vollstaendig delegiert, eigenstaendig ist nur ein sorgfaeltig gebautes, fail-safe `sch_*`-Change-Tracking mit deterministischer Normalisierung und String-Manager-Labels. Stark: das `class_exists`-Gating macht das Feld ohne Cart-Plugin sauber inert. Schwaechen: das Change-Tracking ist pro Save ein doppelter Settings-/Handler-Roundtrip (Kosten beim Bulk-Editieren), `set_data` nutzt `$data->id` ungeprueft, und mehrere Parameter (`$index`, `$key`, `$value`) sind tot. Insgesamt die anspruchsvollste Feld-Klasse der Gruppe. Klassen-Score **C / P2**.
