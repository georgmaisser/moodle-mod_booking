# location — Methoden-Doku
**Datei:** `classes/option/fields/location.php` · **LOC:** 153 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`location` ist der Option-Feld-Handler (`field_base`-Subklasse) fuer den Veranstaltungsort als Freitext (`location`-Spalte in `booking_options`). Die Klasse ist konditional: Ist das Plugin `local_entities` installiert (Klasse `local_entities\entitiesrelation_handler` existiert), uebernimmt das Entities-Subsystem die Orts-/Adressverwaltung und dieser Handler wird zum No-op (kein Form-Element, kein Save). Ohne Entities rendert er ein Autocomplete-Freitextfeld, dessen Vorschlagsliste aus den bestehenden, distinkten Orten gespeist wird. Im Vergleich zu anderen Feldern fehlt eine eigene `set_data`-Methode (erbt von `field_base`). Persistenz: Spalte `location`. Kollaborateure: `field_base`, `fields_info`, `$DB`, `MoodleQuickForm`, optional `local_entities`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Speichert den `location`-Freitext nur, wenn `local_entities` NICHT installiert ist (via Basis-Implementierung mit Default `''`); andernfalls gibt der Handler leeres Array zurueck und ueberlaesst Ort/Adresse dem Entities-Plugin.
- **Seiteneffekte:** Im Nicht-Entities-Fall: Mutiert `$newoption->location` (Basis) und ruft `check_for_changes` (laedt Settings, Change-Tracking).
- **Rueckgabe:** Changes-Array im Nicht-Entities-Fall, sonst `[]`.
- **Bewertung:** A — saubere Feature-Gate-Verzweigung via `class_exists`.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Rendert (nur ohne `local_entities`) ein Autocomplete-Element `location` mit Tags und einer Vorschlagsliste aller bislang verwendeten Orte; setzt Typ je nach `$CFG->formatstringstriptags` auf `PARAM_TEXT` oder `PARAM_CLEANHTML`.
- **Seiteneffekte:** Mutiert `$mform`; optional Header; `$DB->get_fieldset_sql('SELECT DISTINCT location FROM {booking_options} ORDER BY location')`.
- **Bewertung:** B — der `SELECT DISTINCT location`-Scan ueber die gesamte `booking_options`-Tabelle laeuft bei jedem Form-Aufbau ohne Limit/Cache. Bei vielen Optionen ein wiederkehrender Full-Scan (unindiziertes `location`-Feld). P3, da nur im Editor-Pfad.

### Triviale Properties
Sechs statische Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.41–77). `fields_info` ist importiert und in `instance_form_definition` genutzt.

## Bewertungs-Resümee
Konditionaler Freitext-Orts-Handler mit sauberem Entities-Feature-Gate. Einziger Wermutstropfen: der ungecachte `SELECT DISTINCT location`-Tabellenscan bei jedem Form-Aufbau. Funktional korrekt. Klassen-Score **B / P3**.
