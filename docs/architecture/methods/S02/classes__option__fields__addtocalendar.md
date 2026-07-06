# addtocalendar — Methoden-Doku
**Datei:** `classes/option/fields/addtocalendar.php` · **LOC:** 195 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`addtocalendar` ist ein Option-Feld-Handler (erweitert `field_base`) der Buchungsoption mit POSTSAVE-Speicherung (`$save = MOD_BOOKING_EXECUTION_POSTSAVE`), da Kalendereintraege die Option-id und die Optiondates voraussetzen. Verantwortung: pro Buchungsoption Moodle-Kurs-Kalenderereignisse fuer alle Optiondates erzeugen (Wert 1) oder loeschen (Wert 0). Kollaborateure: `calendar` (`booking_optiondate_add_to_cal`), `singleton_service` (Settings/cmid/calendarid), `fields_info` (Header), `field_base` (`check_for_changes`), `$DB` (Tabellen `booking_optiondates`, `event`). Reine statische Klasse; `public static`-Properties sind Registry-Metadaten.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Speichert die Kalender-Auswahl in die Option und raeumt — bei Auswahl 0 — bereits existierende Kurs-Kalenderereignisse aller Optiondates ab.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($formdata->id)` (Read, Ergebnis `$settings` ungenutzt). Bei `addtocalendar == 0`: `$DB->get_records('booking_optiondates', ['optionid' => $optionid])` und je Optiondate `$DB->delete_records_select('event', ...)` mit `uuid = "{optionid}-{optiondateid}"`; bei erfolgreichem Loeschen `update_record('booking_optiondates', ...)` mit `eventid = null`. Delegiert an `parent::prepare_save_field` (Rueckgabe verworfen). Erzeugt `new addtocalendar()` nur fuer `check_for_changes`.
- **Rueckgabe:** `array` der erkannten Aenderungen (`check_for_changes`). PHPDoc behauptet `string`.
- **Bewertung:** B — funktional korrekt; Smells: ungenutzte `$settings`-Variable (Z.99), Instanziierung nur fuer `check_for_changes` (Z.124), `$optionid = $formdata->id` ohne Null-Guard. Die Loesch-Schleife ist ein gebundenes N+1 (ein DELETE je Optiondate) — bei realistischer Optiondate-Zahl unkritisch.

### `public static function save_data(stdClass &$data, stdClass &$option)` — public static
- **Zweck:** POSTSAVE-Schritt: legt bei `addtocalendar == 1` fuer jedes Optiondate ohne vorhandenes Event ein Kurs-Kalenderereignis an.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($option->id)` (Read). Guard `!empty($settings->cmid)` verhindert Lauf auf Template-Optionen ohne cmid. `$DB->get_records('booking_optiondates', ...)` und je Optiondate `$DB->record_exists('event', ['id' => $optiondate->eventid])` — bei vorhandenem Event uebersprungen, sonst `calendar::booking_optiondate_add_to_cal(cmid, optionid, optiondate, calendarid)` (DB-Write Event).
- **Rueckgabe:** void.
- **Bewertung:** B — korrekt mit cmid-Guard gegen Template-Faelle; gebundenes N+1 (`record_exists` je Optiondate). Hinweis: `record_exists('event', ['id' => null])` bei NULL-`eventid` liefert false → Event wird angelegt (gewuenscht).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Select „addtocalendar" (caldonotadd / caladdascourseevent) unter dem DATES-Header hinzu.
- **Seiteneffekte:** Mutiert `$mform` (optionaler Header, Select, Default 0). `hideIf` gegen `selflearningcourse == 1` falls vorhanden. `get_config('booking', 'addtocalendar_locked')` → ggf. `freeze`.
- **Rueckgabe:** void.
- **Bewertung:** A — knapp, korrekt, mit Lock- und hideIf-Behandlung.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.42–78).

## Bewertungs-Resümee
Solider POSTSAVE-Kalender-Handler mit korrekter Create/Delete-Symmetrie und cmid-Guard gegen Templates. Schwaechen sind durchgehend kosmetisch (ungenutzte `$settings`, Selbst-Instanziierung fuer `check_for_changes`, fehlender Null-Guard auf `$formdata->id`) plus zwei gebundene N+1-Schleifen ueber Optiondates, die wegen der typisch kleinen Optiondate-Zahl praktisch unkritisch sind. Klassen-Score **B / P3**.
