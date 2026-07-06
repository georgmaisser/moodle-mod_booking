# json — Methoden-Doku
**Datei:** `classes/option/fields/json.php` · **LOC:** 142 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`json` ist der Option-Feld-Handler (`field_base`-Subklasse) fuer die `json`-Spalte einer Buchungsoption — ein generischer Serialisierungs-Container, in dem viele andere Sub-Felder (z.B. Kampagnen-/Diverse-Settings) ihre Werte ablegen. Der Handler selbst transportiert den Roh-JSON-String unveraendert: er liest ihn aus dem Formular/Settings und schreibt ihn zurueck, ohne ihn zu parsen. Als `MOD_BOOKING_OPTION_FIELD_NECESSARY` markiert (anders als die meisten Felder, die `STANDARD` sind), weil das JSON-Feld immer mitgespeichert werden muss. Persistenz: Spalte `json` in `booking_options` (Form-Element ist `hidden`). Kollaborateure: `field_base`, `singleton_service`, `MoodleQuickForm`; importiert (aber ungenutzt) `bo_info`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Schreibt den `json`-Wert aus dem Formular ins `$newoption`-Objekt; faellt auf `'{}'` zurueck, wenn nicht gesetzt.
- **Seiteneffekte:** Mutiert `$newoption->json`. Ruft die Basis-Implementierung NICHT auf und macht KEIN Change-Tracking.
- **Rueckgabe:** Immer leeres Array (`[]`) — diese Klasse meldet keine Aenderungen ins Option-Log.
- **Bewertung:** A — bewusst minimal; das JSON-Feld aggregiert Sub-Feld-Werte, daher kein eigenes Change-Tracking.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt ein `hidden`-Element `json` mit dem aktuell gespeicherten JSON-Wert (oder `'{}'`) als Default hinzu, Typ `PARAM_RAW`.
- **Seiteneffekte:** Mutiert `$mform`; liest Option-Settings via `singleton_service::get_instance_of_booking_option_settings($optionid)`.
- **Bewertung:** B — `singleton_service` wird auch fuer `$optionid == 0` (neue Option) aufgerufen; in dem Fall liefert es ein leeres Settings-Objekt und `json ?? '{}'` greift. Kein Header (`$applyheader` ungenutzt), da hidden.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Setzt `$data->json` aus den Settings; im Import-Modus (`$data->importing`) bleibt ein bereits in `$data->json` vorhandener Wert erhalten (Import-Override), sonst gewinnt der Settings-Wert.
- **Seiteneffekte:** Mutiert `$data->json`. Deklariert `global $DB`, nutzt es aber nicht (toter Import).
- **Bewertung:** B — korrekt, aber `global $DB` ist ungenutzt; anders als die anderen Felder fehlt der `isset`-Idempotenz-Guard (set_data ueberschreibt im Nicht-Import-Fall immer).

### Triviale Properties
Sechs statische Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.43–79).

## Bewertungs-Resümee
Schlanker Pass-Through-Handler fuer den JSON-Container der Option, der bewusst kein Parsing und kein Change-Tracking betreibt. Kleinere Schoenheitsfehler: ungenutztes `global $DB` und `use bo_info`, kein Idempotenz-Guard in `set_data`. Funktional unkritisch. Klassen-Score **B / P3**.
