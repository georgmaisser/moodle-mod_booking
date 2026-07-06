# bookingoptionsimporter — Methoden-Doku
**Datei:** `classes/importer/bookingoptionsimporter.php` · **LOC:** 326 · **Subsystem:** S18 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S18_import_export.md)

## Klassenueberblick
`bookingoptionsimporter` ist die mod_booking-spezifische Fassade fuer den CSV-Import von Buchungsoptionen. Sie haelt keinen Zustand (nur statische Methoden), sondern definiert das Spaltenschema fuer Buchungsoptionen, konfiguriert ein `csvsettings`-Objekt (Callback `booking_option::update`, fix injizierter `cmid`-Wert) und startet den generischen `fileparser` im Import- bzw. Preview-Modus. Zusaetzlich liefert sie die AJAX-Formdaten fuer die `importoptions.php`-Seite sowie das Spaltenschema als Template-Export. Kollaborateure: `mod_booking\import\csvsettings`, `mod_booking\import\fileparser`, `booking_option::update` (als String-Callback), `importoptions.php` (Konsument). Persistenz: keine eigene; die eigentliche Schreibarbeit erledigt der Callback pro Zeile.

## Methoden

### `public static function execute_bookingoptions_csv_import(stdClass $data, string $content)` — public static
- **Zweck:** Haupt-Importpfad. Baut Spaltenschema + Callback, konfiguriert `csvsettings` (mit `acceptunknowncolumns=true`, Delimiter/Encoding/Dateformat aus den AJAX-Daten), injiziert `cmid` als Konstantwert fuer jede Zeile und startet `fileparser::process_csv_data()`. **Seiteneffekte:** instanziiert `fileparser`; das eigentliche Persistieren passiert pro Zeile im Callback `booking_option::update`. **Rueckgabe:** Ergebnis-Array von `process_csv_data` (Erfolg/Fehler/Warnungen). **Bewertung:** B — klar strukturiert; Code ist nahezu zeilengleich zu `..._preview` (Duplikation).

### `public static function return_ajaxformdata(): array` — public static
- **Zweck:** Liefert die statische AJAX-Form-Konfiguration (Form-id `mbo_csv_import_form`, `settingscallback` = Import-Methode, `previewcallback` = Preview-Methode) fuer den DynamicForm-Filepicker. **Seiteneffekte:** keine. **Rueckgabe:** assoziatives Array. **Bewertung:** A — reine Konfigurationskonstante.

### `public static function execute_bookingoptions_csv_import_preview(stdClass $data, string $content): array` — public static
- **Zweck:** Dry-Run-Variante: identische Settings-Konstruktion wie der Importpfad, ruft aber `fileparser::preview_csv_data()` statt `process_csv_data()` — validiert ohne zu speichern. **Seiteneffekte:** keine Persistenz (Preview). **Rueckgabe:** Preview-Array (`validrows`/`skippedrows`). **Bewertung:** B — fast vollstaendige Duplikation von `execute_bookingoptions_csv_import` (Settings-Aufbau koennte in einen privaten Helfer ausgelagert werden).

### `private static function get_callbackfunction()` — private static
- **Zweck:** Liefert den Callback-Namen als String `"mod_booking\booking_option::update"`. **Seiteneffekte:** keine. **Rueckgabe:** String. **Bewertung:** B — der Backslash in `"mod_booking\booking_option"` ist innerhalb eines Double-Quote-Strings kein gueltiges Escape-Sequenz und bleibt zufaellig erhalten; ein Single-Quote-String (oder `::class`) waere robuster.

### `private static function define_settings(array $definedcolumns, ?string $callbackfunction = null, bool $acceptunknowncolumns = false, ?string $delimiter = null, ?string $encoding = null, ?string $dateformat = null)` — private static
- **Zweck:** Baut ein `csvsettings`-Objekt aus den Spalten und setzt optional Callback/AcceptUnknown/Delimiter/Encoding/Dateformat (jeweils nur bei `!empty`). **Seiteneffekte:** instanziiert `csvsettings`, ruft diverse Setter. **Rueckgabe:** `csvsettings`. **Bewertung:** B — sauberer Builder; doppelter `@return`-Tag im DocBlock (`mixed` und `csvsettings`).

### `private static function define_bookingoption_columns()` — private static
- **Zweck:** Liefert das sequentielle Spaltenschema (~23 Spalten: identifier, title, location, maxanswers, Preise, Teacher-/User-E-Mails, Kurszeiten, completed …) jeweils mit `name`, `mandatory`, Typ-Angabe und `importinstruction`. **Seiteneffekte:** zahlreiche `get_string`-Aufrufe. **Rueckgabe:** Array von Spalten-Definitionen. **Bewertung:** C — siehe Resuemee: inkonsistente Typ-Schluessel (`format` vs. `type`) deaktivieren stillschweigend die Format-Validierung der numerischen Spalten; ausserdem `importinstructions`-Tippfehler bei `completed` und mehrfach identischer `importlocation`-String fuer institution/address.

### `public static function export_columns_for_template()` — public static
- **Zweck:** Gibt das Spaltenschema (`define_bookingoption_columns`) nach aussen, damit ein CSV-Template/Spaltenkopf erzeugt werden kann. **Seiteneffekte:** keine. **Rueckgabe:** Array. **Bewertung:** A.

## Bewertungs-Resümee
Schlanke, gut lesbare Importer-Fassade ueber den generischen `fileparser`. Hauptschwaeche ist das Spaltenschema in `define_bookingoption_columns`: numerische Spalten (`maxanswers`, `maxoverbooking`, `coursenumber`, `addtocalendar`, `courseshortname`, `default`, …) deklarieren ihren PARAM-Typ unter dem Schluessel `'type'`, waehrend `csvsettings::create_columns` und die Validierung in `fileparser` (`switch get_param_value($column,'format')`) den Wert unter `'format'` erwarten — Textspalten setzen korrekt `'format'`. Dadurch wird die Import-Format-Validierung (Integer-/Float-/Alphanum-Pruefung) fuer alle numerischen Spalten unbemerkt uebersprungen. Dazu zwei kosmetische Maengel (`importinstructions`-Tippfehler bei `completed`; mehrere Spalten teilen sich denselben `importlocation`-Hinweistext) und die fast vollstaendige Duplikation zwischen Import- und Preview-Methode. Funktional relevant (P2 wegen fehlender Validierung), aber kein Datenverlust. Klassen-Score **B / P2**.
