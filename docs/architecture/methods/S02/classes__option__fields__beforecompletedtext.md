# beforecompletedtext — Methoden-Doku
**Datei:** `classes/option/fields/beforecompletedtext.php` · **LOC:** 168 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`beforecompletedtext` ist eine Feld-Handler-Klasse (`extends field_base`) und ist strukturell ein Zwilling von `beforebookedtext`: identischer Aufbau, nur ein anderes Property (`beforecompletedtext` — Editor-Text, der Nutzern vor Abschluss/Completion der Buchung angezeigt wird). Kein Instanzzustand; rein statische Framework-Hooks. Persistenz: Spalte `beforecompletedtext` der Tabelle `booking_options` (vom generischen Save-Pfad). Kollaborateure: `field_base`, `fields_info`, `MoodleQuickForm`, `booking_option_settings`. Konfig-Properties: `$id = BEFORECOMPLETEDTEXT`, `$save = NORMAL`, `$header = BOOKINGOPTIONTEXT`, `$fieldcategories = [STANDARD]`, leere Import-/Inkompatibilitaets-Listen.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = []): array` — public static
- **Zweck:** Uebertraegt den Editor-Wert nach `$newoption->beforecompletedtext` und erfasst Aenderungen. **Seiteneffekte:** Mutiert `$newoption` per Referenz; instanziiert sich selbst nur fuer den `check_for_changes()`-Aufruf der Basis; behandelt Array-Form (`$value['text']`) und Skalar; leerer Wert => `''`. **Rueckgabe:** Changes-Array (leer ohne Aenderung). **Bewertung:** B — identisch zur `beforebookedtext`-Variante; reine Code-Duplikation (nur Feldname unterscheidet sich).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Editor-Element `beforecompletedtext` (mit Help-Button) und optional den `BOOKINGOPTIONTEXT`-Header hinzu. **Seiteneffekte:** Mutiert `$mform` (`addElement` editor, `setType` PARAM_CLEANHTML, `addHelpButton`); Header nur bei `$applyheader`. **Bewertung:** A.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Setzt die Editor-Array-Form `['text' => $value]` zur Formular-Vorbelegung. **Seiteneffekte:** Mutiert `$data->beforecompletedtext`; frueher Return, wenn Schluessel bereits gesetzt. **Bewertung:** B — wie der Zwilling, ohne `format`-Element.

### Triviale Properties
Sechs statische Konfig-Properties (Z.41–77).

## Bewertungs-Resümee
Funktional korrekter, aber zu `beforebookedtext` praktisch identischer Handler — die beiden Klassen koennten ueber eine gemeinsame Editor-Feld-Basis dedupliziert werden. Keine Bugs. Klassen-Score **B / P3**.
