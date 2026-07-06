# prepare_import — Methoden-Doku
**Datei:** `classes/option/fields/prepare_import.php` · **LOC:** 171 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`prepare_import` ist eine **Vorverarbeitungs-Feldklasse** (`extends field_base`) im Optionsformular-Feld-Framework (S02). Sie speichert selbst keine Daten, sondern laeuft als `NECESSARY`-Feld frueh in der Pipeline, um den Datensatz vor allen anderen Feldern fuer den Import vorzubereiten: Aufloesen einer bestehenden Buchungsoption ueber den `identifier`, Setzen des `importing`-Flags, Ableiten der `bookingid` aus der `cmid` und Default-Belegung von `id`/`addastemplate`. Persistenz: keine eigene; nur lesender Zugriff auf `booking_options`. Kollaborateure: `$DB` (Identifier-Lookup), `singleton_service` (Booking-Settings per cmid), `optional_param` (Request-Flag `addastemplate`), `moodle_exception`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** No-op-Override; die Klasse hat keinen eigenen Save-Wert. **Seiteneffekte:** keine. **Rueckgabe:** leeres Array. **Bewertung:** A — bewusst leer.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Liest das Request-Flag `addastemplate` und legt es in `$formdata` ab, damit nachgelagerte Felder/Logik wissen, dass die Option als Template angelegt wird. **Seiteneffekte:** `optional_param('addastemplate', ...)`; mutiert `$formdata['addastemplate']`. **Bewertung:** C — Z.120–122 enthalten eine **redundante Doppelzuweisung** (`$addastemplate = $addastemplate = optional_param(...)` plus eine vorangehende identische Zeile); zudem ist `optional_param(...) ?? 0` ueberfluessig (Funktion liefert nie null). Funktional harmlos, aber offensichtlicher Copy-Paste-Rest.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Kernlogik der Importvorbereitung. Ist keine `id` vorhanden, aber ein `identifier`, wird `importing=true` gesetzt und versucht, die bestehende Option per Identifier zu laden (`id` uebernommen); existiert keine und fehlen zugleich `text`/`name`, wird eine `moodle_exception` (`identifiernotfoundnotenoughdata`) geworfen. Fehlt die `bookingid`, aber eine `cmid` ist da, wird `bookingid` aus den Booking-Settings abgeleitet. Schliesslich `addastemplate` aus dem Request belegt und `id` als Fallback auf 0 gesetzt. **Seiteneffekte:** `$DB->get_record('booking_options', ['identifier' => ...])`; `singleton_service::get_instance_of_booking_settings_by_cmid`; `optional_param`; mehrere Mutationen an `$data` (`importing`, `id`, `bookingid`, `addastemplate`); ggf. Exception. **Bewertung:** C — funktional zentral und korrekt, aber dichte Mehrfachverantwortung in einer Methode; der Identifier-Lookup ohne Kontext-/Booking-Scope koennte bei nicht-eindeutigen Identifiern die erste passende Option treffen (Eindeutigkeit wird vorausgesetzt). Z.162 dupliziert die `addastemplate`-Zuweisung erneut (kosmetisch).

### Triviale Properties
Sechs statische Marker-Properties (`$id`, `$save = NORMAL`, `$header = GENERAL`, `$fieldcategories = [NECESSARY]`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.44–82) deklarieren das Feld als frueh laufendes Pflicht-Feld.

## Bewertungs-Resümee
Eine schlanke, aber wichtige Vorverarbeitungsklasse, die die Import-Pipeline aufsetzt (Identifier-Aufloesung, Flags, bookingid-Ableitung). Logik korrekt; Maengel sind die mehrfach duplizierten `addastemplate`-Zuweisungen (toter Code) und die Abhaengigkeit von Identifier-Eindeutigkeit ohne Scope. Kein Datenverlust. Klassen-Score **C / P3**.
