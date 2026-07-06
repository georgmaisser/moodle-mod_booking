# maxanswers — Methoden-Doku
**Datei:** `classes/option/fields/maxanswers.php` · **LOC:** 155 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`maxanswers` ist der Option-Feld-Handler (`field_base`-Subklasse) fuer die maximale Teilnehmerzahl einer Buchungsoption (`maxanswers`-Spalte). Neben dem Speichern des Werts setzt der Handler als Seiteneffekt das `limitanswers`-Flag, sobald ein Limit > 0 gesetzt ist. Eng verzahnt mit `maxoverbooking` (Warteliste), das dasselbe `limitanswers`-Flag manipuliert. Eine Besonderheit: `set_data` liest den Wert frisch aus der DB statt aus dem Settings-Objekt, weil Kampagnen den Settings-Wert ueberschreiben koennen. Persistenz: Spalten `maxanswers` und `limitanswers` in `booking_options`. Kollaborateure: `field_base`, `fields_info`, `singleton_service`, `$DB`, `MoodleQuickForm`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Speichert `maxanswers` (via Basis mit Default 0) und setzt `$newoption->limitanswers = 1`, falls `maxanswers` nicht leer ist.
- **Seiteneffekte:** Mutiert `$newoption` (`maxanswers`, ggf. `limitanswers`); ruft `check_for_changes` (Settings-Load + Change-Tracking).
- **Rueckgabe:** Changes-Array.
- **Bewertung:** B — `limitanswers` wird hier nur EINgeschaltet, nie wieder auf 0 gesetzt, wenn `maxanswers` geleert wird. Da `maxoverbooking` denselben Flag setzt, ist das Zuruecksetzen nirgends sauber zentralisiert (siehe Findings).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt ein `text`-Element `maxanswers` (Typ `PARAM_INT`) mit Help-Button hinzu.
- **Seiteneffekte:** Mutiert `$mform`; optional Header via `fields_info::add_header_to_mform`.
- **Bewertung:** A — Standard-Form-Definition.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Setzt `$data->maxanswers` — bewusst aus der DB (`booking_options.maxanswers`) statt aus dem Settings-Objekt, da Kampagnen-Logik den In-Memory-Settings-Wert verfaelschen kann; idempotent via `isset`-Guard.
- **Seiteneffekte:** `$DB->get_field('booking_options', 'maxanswers', ['id' => $settings->id])`; mutiert `$data`.
- **Bewertung:** B — der zusaetzliche Direkt-DB-Read pro Option ist begruendet (Kampagnen-Override), bedeutet aber einen extra Query pro Feld-Set-Data; bei Bulk-Edits ein N+1-Beitrag. Vertretbar, da nur im Editor-Pfad.

### Triviale Properties
Sechs statische Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.42–78).

## Bewertungs-Resümee
Teilnehmerzahl-Handler mit dem Seiteneffekt, `limitanswers` zu aktivieren, und einem bewusst DB-frischen `set_data` gegen Kampagnen-Verfaelschung. Schwaeche: das `limitanswers`-Flag wird nur gesetzt, nie geloescht, und die Verantwortung dafuer ist mit `maxoverbooking` geteilt. Funktional fuer den Normalfall korrekt. Klassen-Score **B / P3**.
