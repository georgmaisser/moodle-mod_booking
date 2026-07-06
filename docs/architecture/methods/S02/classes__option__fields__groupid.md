# groupid — Methoden-Doku
**Datei:** `classes/option/fields/groupid.php` · **LOC:** 217 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`groupid` ist ein Optionsfeld (`extends field_base`) im Field-Plugin-Schema von mod_booking. Es verwaltet die Zuordnung einer Buchungsoption zu einer Moodle-Kursgruppe (`booking_options.groupid`). Im Unterschied zu reinen Werte-Feldern enthaelt es Logik: Beim Speichern legt es ueber `booking_option::create_group()` automatisch eine Kursgruppe an (wenn die Instanz `addtogroup` aktiviert hat) oder setzt eine bestehende Gruppe zurueck. Das Formular zeigt das Feld nur lesend (`static`-Element) plus eine Reset-Checkbox an. Persistenz: Spalte `groupid` in `booking_options` (vom Save-Pipeline geschrieben, nicht von dieser Klasse direkt). Kollaborateure: `singleton_service` (booking_option_settings, booking_settings_by_cmid, booking_option), Moodle-Core `groups_get_group`/`groups_get_group_by_name`, `fields_info`, `booking_option::create_group()`.

Statische Konfig-Properties: `$id = MOD_BOOKING_OPTION_FIELD_GROUPID`, `$save = MOD_BOOKING_EXECUTION_NORMAL`, `$header = MOD_BOOKING_HEADER_COURSES`, `$fieldcategories = [STANDARD]`, leere `$alternativeimportidentifiers`/`$incompatiblefields`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Setzt `$newoption->groupid` abhaengig von Reset-Checkbox und der Instanz-Einstellung `addtogroup`. Drei Pfade: (a) Reset-Checkbox gesetzt -> versucht ueber den kanonischen Gruppennamen `"$bookingsettings->name - $settings->text ($optionid)"` die korrekte Gruppe wiederzufinden (`groups_get_group_by_name`); gefunden -> setzen; sonst bei aktivem `addtogroup` neu anlegen; sonst `groupid = null`. (b) Kein Reset, aber `addtogroup` aktiv und bisher keine Gruppe -> neue Gruppe anlegen. (c) sonst nichts.
- **Seiteneffekte:** Mehrere Singleton-Lookups (settings, bookingsettings, booking_option); `groups_get_group_by_name()` (DB); potenziell **Kursgruppen-Anlage** ueber `$bo->create_group(...)` (schreibender Core-Aufruf, legt Moodle-Gruppe an). Mutiert `$newoption->groupid`.
- **Rueckgabe:** immer `[]` (keine Warnungen/Changes; trackt keine Aenderungen via `check_for_changes`).
- **Bewertung:** C — verschachtelte Mehrfach-Verzweigung mit frueh-Returns; baut den Gruppennamen per String-Interpolation nach (fragil gegenueber Namensaenderungen der Instanz/Option). Liefert nie ein Change-/Warning-Element, obwohl eine Gruppe angelegt werden kann (kein Audit-Hinweis). Guard `empty($settings->cmid)` -> stiller No-op ist sinnvoll.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Rendert das Feld read-only: ohne optionid oder ohne gesetzte `groupid` wird nichts angezeigt (early return). Sonst Anzeige des Gruppennamens als Link auf `/group/index.php?id=courseid` (oder `?` + ID, falls Gruppe nicht mehr existiert) plus Reset-Checkbox `resetgroupid`.
- **Seiteneffekte:** `groups_get_group($groupid)` (DB-Lookup); `fields_info::add_header_to_mform`; baut HTML-Markup direkt als String. `global $DB` deklariert, aber ungenutzt.
- **Rueckgabe:** void.
- **Bewertung:** B — klares Read-only-Pattern mit Fallback bei verwaister Gruppe. Manuelles HTML-Concat (`<b>`, `<a class="btn">`) statt Renderer/OUTPUT; Gruppenname wird via `$group->name` ausgegeben — `groups_get_group` liefert den unformatierten DB-Wert; XSS-Risiko gering, aber kein `format_string`/`s()`. Ungenutztes `global $DB`.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Initialisiert die Reset-Checkbox im Formular mit 0 (unchecked).
- **Seiteneffekte:** mutiert `$data->resetgroupid`.
- **Rueckgabe:** void (explizites `return;`).
- **Bewertung:** A — trivial, korrekt.

### Triviale Properties
Sechs statische Konfig-Properties (Z.45–81) steuern Sortierung/Header/Kategorie im Field-Framework; reine Metadaten ohne Logik.

## Bewertungs-Resümee
Funktional korrektes, aber logikreicheres Optionsfeld: die `prepare_save_field`-Reset-Mechanik mit Namens-Rekonstruktion und automatischer Gruppen-Anlage ist die Hauptkomplexitaet und am ehesten fragil (Name-basiertes Wiederfinden, kein Change/Warning-Feedback trotz Gruppen-Anlage). Anzeige nutzt rohes HTML-Concat ohne `format_string`. Keine Datenverlust-/Sicherheits-P0/P1, aber ueberdurchschnittliche Verzweigungstiefe fuer ein Feld. Klassen-Score **C / P3**.
