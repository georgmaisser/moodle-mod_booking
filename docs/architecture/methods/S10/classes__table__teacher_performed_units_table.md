# teacher_performed_units_table — Methoden-Doku
**Datei:** `classes/table/teacher_performed_units_table.php` · **LOC:** 160 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`teacher_performed_units_table` erbt von `table_sql` und rendert den individuellen Performance-Report eines Teachers (geleistete Unterrichtseinheiten/Termine). Spalten/Headers werden bewusst NICHT im Konstruktor definiert (Kommentar: generisch halten) — sie werden extern gesetzt; die Klasse liefert nur die `col_*`-Formatter sowie locale-abhaengige Zahlenseparatoren. Persistenz: keine eigene; formatiert uebergebene Datensaetze. Kollaborateur: `mod_booking\option\dates_handler` (Datumsaufbereitung), `current_language()`.

## Methoden

### `public function __construct(string $uniqueid)` — public
- **Zweck:** Initialisiert die Basis und setzt sprachabhaengige Dezimal-/Tausender-Trennzeichen. **Seiteneffekte:** haengt `current_language()` an die `$uniqueid` (sprachgetrennte Tabellen-Caches/Prefs); setzt `$this->baseurl = $PAGE->url`; bei `de` Komma+Leerzeichen, sonst Punkt+Komma. **Bewertung:** B — sinnvolle Locale-Behandlung; die `if/else`-Separatorlogik dupliziert lediglich die bereits als Property-Default gesetzten Werte (der `else`-Zweig ist redundant). `current_language()` wird zweimal aufgerufen.

### `public function col_optiondate(object $values): string` — public
- **Zweck:** Formatiert Start-/Endzeit eines Optiondate als huebschen Datums-Zeit-String. **Seiteneffekte:** `dates_handler::prettify_optiondates_start_end($values->coursestarttime, $values->courseendtime, current_language())`. **Rueckgabe:** gerenderter Datums-/Zeitbereich (String). **Bewertung:** A.

### `public function col_coursestarttime($values)` — public
- **Zweck:** Gibt `coursestarttime` als `Y-m-d H:i` zurueck, leer bei 0. **Seiteneffekte:** `date(...)` (Server-Zeitzone). **Rueckgabe:** String. **Bewertung:** B — nutzt `date()` statt `userdate()`, also Server- statt User-Zeitzone (im Report-Kontext meist gewollte maschinenlesbare Form, aber TZ-unabhaengig nicht ganz korrekt).

### `public function col_courseendtime($values)` — public
- **Zweck:** Wie `col_coursestarttime`, fuer `courseendtime`. **Seiteneffekte:** `date(...)`. **Rueckgabe:** String. **Bewertung:** B — identisches Muster (Duplikat von oben).

### `public function col_titleprefix($values)` — public
- **Zweck:** Gibt `titleprefix` zurueck, leer wenn nicht gesetzt. **Seiteneffekte:** keine. **Rueckgabe:** String. **Bewertung:** B — gibt den Wert ungefiltert aus (kein `format_string`); im Report meist unkritisch, aber kein Output-Escaping.

### `public function col_duration_units($values)` — public
- **Zweck:** Formatiert die Anzahl Unterrichtseinheiten mit einer Nachkommastelle und den locale-Separatoren. **Seiteneffekte:** `number_format($values->duration_units, 1, ...)`. **Rueckgabe:** formatierte Zahl (String). **Bewertung:** A.

### Triviale Properties
`public $decimalseparator = "."`, `public $thousandsseparator = ","` (Z.47/50) — Locale-Separatoren; der zweite Doc-Block (`@var string $decimalseparator`) ist ein Copy-Paste-Fehler.

## Bewertungs-Resümee
Geradliniger Report-Renderer mit sauberer Locale-Behandlung und Delegation der Datumslogik an `dates_handler`. Kleinere Schwaechen: redundanter `else`-Separatorzweig, doppeltes `current_language()`, `date()` statt `userdate()` (Server-TZ), ungeescapter `titleprefix` und ein dupliziertes PHPDoc. Keine funktionalen Korrektheits- oder Sicherheits-Risiken. Klassen-Score **B / P3**.
