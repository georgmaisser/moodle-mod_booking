# price — Methoden-Doku
**Datei:** `classes/option/fields/price.php` · **LOC:** 277 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`price` ist die **Postsave-Feldklasse** (`extends field_base`) im Optionsformular-Feld-Framework (S02), die die Preiskategorien einer Buchungsoption verwaltet. Sie ist als `POSTSAVE` markiert, weil die Preise (Tabelle `booking_prices`, ueber die zentrale `mod_booking\price`-Klasse) erst nach Anlegen der Option mit deren `id` geschrieben werden koennen. Sie bildet die **Bruecke zwischen Feld-Framework und `mod_booking\price`** (hier `Mod_bookingPrice` aliasiert) und kapselt zusaetzlich das `useprice`-Flag im Options-JSON sowie Import-spezifische Preisuebernahme. Kollaborateure: `mod_booking\price` (Formular, Validierung, Persistenz, Cache), `booking_option` (JSON-Helfer `add_data_to_json`/`get_value_of_json_by_key`, `has_price_set`), `get_config('booking', 'priceisalwayson')`, `singleton_service`, `html_writer`/`moodle_url` (Slotbooking-Regeleditor-Link).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Schreibt das `useprice`-Flag (0/1) ins Options-JSON, **bevor** das JSON persistiert wird, und delegiert dann an `parent::prepare_save_field`. **Seiteneffekte:** `booking_option::add_data_to_json($newoption, 'useprice', 0|1)` (mutiert `$newoption->json`); Parent-Aufruf. **Rueckgabe:** leeres Array — Preisaenderungen werden bewusst nicht hier, sondern in `save_data()` gesammelt und in den konsolidierten Option-Change-Set gemerged. **Bewertung:** B — korrekte Reihenfolge (JSON vor Speichern); Kommentar Z.107 „11 means we have a price" ist ein Tippfehler (gesetzt wird 1).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut den Preisblock ueber `Mod_bookingPrice::add_price_to_mform` und fuegt eine Slotbooking-Hinweiszeile hinzu, die (bei vorhandener cmid+optionid) auf den Slot-Regeleditor verlinkt bzw. sonst zum vorherigen Speichern auffordert; das Element wird per `hideIf` nur fuer den Optionstyp Slotbooking sichtbar gemacht. **Seiteneffekte:** instanziiert `Mod_bookingPrice('option', $formdata['id'])`; mehrere `addElement`/`hideIf`-Aufrufe. **Bewertung:** B — klar; die eigentliche Preisfeld-Erzeugung ist an `mod_booking\price` ausgelagert.

### `public static function validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Delegiert die Preisvalidierung an `Mod_bookingPrice::validation`. **Seiteneffekte:** instanziiert `price`-Handler; mutiert `$errors`. **Bewertung:** A — reiner Delegations-Wrapper.

### `public static function save_data(stdClass &$formdata, stdClass &$option): array` — public static
- **Zweck:** Postsave-Persistenz der Preise: speichert die Formularpreise ueber `Mod_bookingPrice::save_from_form` mit `$option->id` und gibt die erkannten Aenderungen zurueck, damit `booking_option::update` **ein** konsolidiertes `bookingoption_updated`-Event mit allen Changes ausloest. **Seiteneffekte:** `Mod_bookingPrice('option', $option->id)->save_from_form($formdata, false)` → DB-Writes auf `booking_prices`. **Rueckgabe:** Change-Array. **Bewertung:** B — saubere Postsave-Logik; das `false`-Argument unterdbrueckt den Einzel-Event, korrekt fuer die konsolidierte Sammlung.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Belegt die Preisfelder des Formulars — zwei Pfade: (a) Import (`$data->importing`): laeuft ueber alle `pricecategories`, nimmt importierte numerische Werte je Kategorie-Identifier oder faellt auf bestehenden Preis bzw. `defaultvalue` zurueck, encodiert die Feldschluessel (`bin2hex(identifier)`) und setzt `useprice` anhand vorhandener Preise / `priceisalwayson` / Settings; (b) Normalfall: liest `useprice` aus dem JSON (Fallback ueber `has_price_set`), respektiert `priceisalwayson` und delegiert dann an `$pricehandler->set_data($data)`. **Seiteneffekte:** `Mod_bookingPrice('option', $data->id)`, `Mod_bookingPrice::get_prices_from_cache_or_db('option', $data->id)`; `booking_option::get_value_of_json_by_key` / `has_price_set` (DB); `get_config('booking','priceisalwayson')`; viele Mutationen an `$data` (encodierte Preisgruppen, `useprice`); liest `$USER`. **Bewertung:** C — die laengste, komplexeste Methode der Klasse mit verschachtelter Import-/Normal-Verzweigung und doppeltem `pricecategories`-Loop im Importzweig (einmal Werte, einmal `useprice`-Erkennung); der Inline-Kommentar Z.203 „This has to be fixed." dokumentiert eine bekannte Architektur-Schwaeche (Preise werden teils noch via Definition-Default statt set_data gesetzt). Funktional, aber wartungsintensiv.

### Triviale Properties
Sechs statische Marker-Properties (`$id`, `$save = POSTSAVE`, `$header = PRICE`, `$fieldcategories = [STANDARD]`, `$alternativeimportidentifiers = ['useprice']`, `$incompatiblefields`, Z.45–83) konfigurieren das Postsave-Verhalten und den Import-Alias.

## Bewertungs-Resümee
Die zentrale Bruecke zwischen Optionsformular und Preis-Subsystem (S05). Architektur korrekt (Postsave wegen Option-id, JSON-Flag vor Persistenz, konsolidiertes Event). Schwerpunkt der Komplexitaet liegt in `set_data` mit doppeltem Kategorie-Loop und einem selbst dokumentierten Refactoring-TODO; kleiner Kommentar-Tippfehler in `prepare_save_field`. Keine Datenverlust-/Sicherheitsdefekte. Klassen-Score **C / P2**.
