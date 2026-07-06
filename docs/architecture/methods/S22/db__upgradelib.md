# db/upgradelib.php — Methoden-Doku
**Datei:** `db/upgradelib.php` · **LOC:** 289 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
Prozedurale Sammlung von Einmal-Migrations-/Fixup-Funktionen, die aus `db/upgrade.php` zu bestimmten Versions-Bumps aufgerufen werden. Jede Funktion ist eine in sich geschlossene Datenkorrektur (UPDATE/Set-Field) auf einer mod_booking-Tabelle. Keine Klasse, keine geteilte State; einziger Kollaborateur ist global `$DB` (und in einem Fall die `core_customfield`-API). Funktionen sind absichtlich idempotent-ish (WHERE-Guards), aber teils laufzeitintensiv (Voll-Tabellen-Loops mit `update_record` statt Bulk-SQL).

## Methoden

### `migrate_booking_option_identifiers_2022090802(): void` — global (function)
- **Zweck:** Trennt den in `booking_options.text` mit Separator codierten Identifier heraus und schreibt ihn ins neue Feld `identifier`.
- **Parameter/Rueckgabe:** keine / void.
- **Seiteneffekte:** liest `config booking/uniqueoptionnameseparator`, liest **alle** `booking_options`, schreibt `booking_options` (`update_record` pro Treffer).
- **Aufrufkette:** gerufen aus `db/upgrade.php` (Savepoint 2022090802).
- **Bewertung:** C — Voll-Tabellen-Load + Einzel-`update_record` in Schleife (upgradelib.php:34); `strpos(...) == false` ist loser Vergleich (Position 0 würde fälschlich als „nicht gefunden" gelten) — latenter Bug (upgradelib.php:37); `explode` ohne Limit, nimmt nur erste 2 Teile.

### `migrate_optionids_for_prices_2022112901(): void` — global (function)
- **Zweck:** Setzt `area = 'option'` auf allen `booking_prices`-Zeilen nach Spalten-Rename optionid→itemid.
- **Seiteneffekte:** liest **alle** `booking_prices`, schreibt jede Zeile via `update_record`.
- **Aufrufkette:** `db/upgrade.php` (2022112901).
- **Bewertung:** C — könnte ein einziges Bulk-`UPDATE {booking_prices} SET area='option'` sein statt Load+Loop (upgradelib.php:58).

### `migrate_optionsfields_2023022800(): void` — global (function)
- **Zweck:** Setzt für alle `booking`-Instanzen den `optionsfields`-Default, damit nach View-Umstellung keine Felder verschwinden.
- **Seiteneffekte:** liest **alle** `booking`, schreibt jede Zeile mit hartcodiertem Feldstring.
- **Aufrufkette:** `db/upgrade.php` (2023022800).
- **Bewertung:** C — Load+Loop statt Bulk-UPDATE; hartcodierte Feldliste inline (upgradelib.php:75).

### `fix_bookingoption_descriptionformat_2024022700(): void` — global (function)
- **Zweck:** Setzt `descriptionformat = 1` wo `= 0`.
- **Seiteneffekte:** ein direktes `$DB->execute` Bulk-UPDATE auf `booking_options`.
- **Aufrufkette:** `db/upgrade.php` (2024022700).
- **Bewertung:** A — saubere Bulk-Korrektur, WHERE-Guard.

### `fix_showlistoncoursepage_2024030801(): void` — global (function)
- **Zweck:** Normalisiert `booking.showlistoncoursepage` von 2 auf 1.
- **Seiteneffekte:** Bulk-`execute` UPDATE auf `booking`.
- **Aufrufkette:** `db/upgrade.php` (2024030801).
- **Bewertung:** A — Bulk, WHERE-Guard.

### `migrate_contextids_2024040901(): void` — global (function)
- **Zweck:** Setzt `booking_rules.contextid = 1` (System-Kontext) für alle Regeln.
- **Seiteneffekte:** Bulk-`execute` UPDATE auf `booking_rules`, ohne WHERE.
- **Aufrufkette:** `db/upgrade.php` (2024040901).
- **Bewertung:** B — Bulk ok, aber pauschaler Kontext 1 (Annahme System) ohne Guard; akzeptabel für Einmal-Migration.

### `fix_booking_templateid(): void` — global (function)
- **Zweck:** Ersetzt NULL in `booking.templateid` durch 0.
- **Seiteneffekte:** SELECT der NULL-Zeilen, danach Einzel-`update_record` je Zeile.
- **Aufrufkette:** `db/upgrade.php` (versionslos benannt).
- **Bewertung:** C — Load+Loop, wäre als ein Bulk-`UPDATE ... SET templateid=0 WHERE templateid IS NULL` trivial (upgradelib.php:134); PHPDoc-`@return [type]` ist Platzhalter-Müll (upgradelib.php:122).

### `fix_places_for_booking_answers(): void` — global (function)
- **Zweck:** Setzt `places = 1` wo NULL in `booking_answers`.
- **Seiteneffekte:** Bulk-`execute` UPDATE.
- **Aufrufkette:** `db/upgrade.php`.
- **Bewertung:** A — Bulk, WHERE-Guard.

### `remove_completiongradeitemnumber_2025010803(): void` — global (function)
- **Zweck:** Leert `completiongradeitemnumber`/`completionpassgrade` für alle booking-`course_modules` (Bugfix #779).
- **Seiteneffekte:** liest `modules.id` (name=booking), Bulk-`execute` UPDATE auf `course_modules` mit Param.
- **Aufrufkette:** `db/upgrade.php` (2025010803).
- **Bewertung:** B — Bulk + parametrisiert; Param-Name-Typo `bookigmodules` (kosmetisch, konsistent verwendet, upgradelib.php:172).

### `booking_options_initialize_timecreated(): void` — global (function)
- **Zweck:** Backfill `timecreated = timemodified` wo `timecreated = 0`.
- **Seiteneffekte:** Bulk-`execute` UPDATE auf `booking_options`.
- **Aufrufkette:** `db/upgrade.php`.
- **Bewertung:** A — Bulk, WHERE-Guard.

### `booking_upgrade_change_id_425_to_391(): void` — global (function)
- **Zweck:** Patcht in `booking_form_config.json` jedes Element mit `id == 425` auf 391.
- **Seiteneffekte:** liest **alle** `booking_form_config`, json_decode/encode, `update_record` nur bei Änderung.
- **Aufrufkette:** `db/upgrade.php`.
- **Bewertung:** B — JSON muss zwangsläufig in PHP iteriert werden; Referenz-Unset korrekt (upgradelib.php:219), `update_record` nur bei `$updated`. Magic Numbers ohne Kontext, aber für Migration ok.

### `migrate_selflearningcourse_json_to_type_2025122201(): void` — global (function)
- **Zweck:** Übersetzt JSON-Flag `selflearningcourse=1` in die neue Spalte `booking_options.type` (1), sonst 0.
- **Seiteneffekte:** liest alle `booking_options` (nur id,json,type), `set_field` pro Zeile wo `type IS NULL`.
- **Aufrufkette:** `db/upgrade.php` (2025122201).
- **Bewertung:** C — Voll-Load + Einzel-`set_field` in Schleife (upgradelib.php:253); JSON-Pfad erzwingt PHP-Loop, daher teils unvermeidbar, aber Guard `type === null` strikt korrekt.

### `delete_customfields_in_tool_certificate_2026030500(): void` — global (function)
- **Zweck:** Löscht alle Customfield-Kategorien der Komponente `tool_certificate`.
- **Seiteneffekte:** liest `customfield_category`, erzeugt `category_controller`, ruft `handler::get_handler(...)->delete_category()` (löscht Kategorien + Felder über core_customfield-API).
- **Aufrufkette:** `db/upgrade.php` (2026030500).
- **Bewertung:** C — `catch` macht `return` (bricht die GANZE Schleife ab) statt `continue`/break — bei Fehlschlag der ersten Kategorie bleiben restliche unbearbeitet; vermutlich gemeint, da Fehler nur bei deinstalliertem Plugin auftritt, aber semantisch riskant (upgradelib.php:283); `moodle_exception` ohne `\`-Prefix im global Namespace (funktioniert nur, weil Moodle die Klasse global aliast).

## Triviale Akzessoren
Keine — Datei enthält ausschließlich eigenständige Migrationsfunktionen, keine Getter/Setter/Konstruktoren.
