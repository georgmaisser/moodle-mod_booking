# identifier — Methoden-Doku
**Datei:** `classes/option/fields/identifier.php` · **LOC:** 157 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`identifier` ist ein Standard-Optionsfeld (`extends field_base`) fuer den eindeutigen, menschen- bzw. importtauglichen Identifier einer Buchungsoption (`booking_options.identifier`). Es kombiniert das Standard-Save-/Change-Tracking-Pattern mit einem Pflicht-Textfeld, einem zufaellig vorbelegten Default und einer **DB-gestuetzten Eindeutigkeitsvalidierung**. Persistenz: Spalte `identifier` in `booking_options`. Kollaborateure: `field_base` (prepare_save_field/check_for_changes), `fields_info`, `booking_option::create_truly_unique_option_identifier()`, `$DB` (Validierung).

Statische Konfig-Properties: `$id = MOD_BOOKING_OPTION_FIELD_IDENTIFIER`, `$save = MOD_BOOKING_EXECUTION_NORMAL`, `$header = MOD_BOOKING_HEADER_GENERAL`, `$fieldcategories = [STANDARD]`, leere `$alternativeimportidentifiers`/`$incompatiblefields`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Standard-Save: delegiert an `parent::prepare_save_field` (Default `''`) und ermittelt ueber eine frische Instanz die Change-Liste.
- **Seiteneffekte:** parent setzt `$newoption->identifier`; `check_for_changes` macht bei vorhandener `$formdata->id` einen settings-Lookup.
- **Rueckgabe:** `array` der Aenderungen.
- **Bewertung:** B — kanonisches Pattern; PHPDoc-`@return string` vs. tatsaechlich `array` (Doc-Inkonsistenz).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt ein Pflicht-Textfeld `identifier` (max. 255 Zeichen, `PARAM_TEXT`) hinzu und belegt es per Default mit einem garantiert eindeutigen Zufallswert.
- **Seiteneffekte:** `fields_info::add_header_to_mform`; `booking_option::create_truly_unique_option_identifier()` (kann DB lesen, um Eindeutigkeit zu garantieren); mform-Element/Rules/Default/HelpButton.
- **Rueckgabe:** void.
- **Bewertung:** A — sauber: Pflicht- und Maxlength-Rule client-seitig, sinnvoller Random-Default. `create_truly_unique_option_identifier` wird bei **jedem** Formular-Render aufgerufen (auch beim Bearbeiten, wo der Default vom geladenen Wert ohnehin ueberschrieben wird) — minimaler Overhead.

### `public static function validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Server-seitige Eindeutigkeitspruefung: setzt `$errors['identifier']`, falls ein anderer Datensatz (id != aktuelle optionid) denselben `identifier` traegt.
- **Seiteneffekte:** `$DB->get_records_sql("SELECT id FROM {booking_options} WHERE id <> :optionid AND identifier = :identifier")`.
- **Rueckgabe:** void (mutiert `$errors`).
- **Bewertung:** B — korrekt parametrisiert (kein SQL-Injection-Risiko), aber `get_records_sql` laedt alle Treffer statt `record_exists_sql` (effizienter, da nur Existenz geprueft wird). Bei fehlendem `$data['id']` (Neuanlage) ist `:optionid` `null` -> `id <> NULL` ist in SQL nie wahr, sodass die `id <>`-Bedingung effektiv ignoriert wird und alle gleichlautenden Identifier als Konflikt gelten — fuer Neuanlagen gewollt. Keine Pruefung, ob `identifier` leer ist (leerer String koennte als Duplikat triggern, wird aber durch die required-Rule abgefangen).

### Triviale Properties
Sechs statische Konfig-Properties (Z.42–80) als Field-Framework-Metadaten.

## Bewertungs-Resümee
Solides Identifier-Feld mit Zufalls-Default und DB-Eindeutigkeitsvalidierung. Schwaechen sind klein: `get_records_sql` statt `record_exists_sql` in der Validierung und die PHPDoc-Rueckgabetyp-Inkonsistenz. Keine P0/P1. Klassen-Score **B / P3**.
