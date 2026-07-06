# scope_base_answers — Methoden-Doku
**Datei:** `classes/booking_answers/scope_base_answers.php` · **LOC:** 126 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_booking_answers.md)

## Klassenueberblick
Zwischenbasis fuer die nicht-aggregierte Antworten-Sicht (eine Zeile pro `booking_answers`-Eintrag = pro gebuchtem User). Liefert SELECT-/ORDER-BY-Bausteine und Spaltendefinition fuer diese Detailtabellen. Erbt von `scope_base`; Kollaborateure: Moodle `get_config`/`get_string`.

## Methoden

### `get_selectpart(string $scope): string` — public
- **Zweck:** Baut den SELECT/FROM-Block der Detail-Query (booking_answers join user/booking_options/course_modules/booking/course/modules); fuegt optional die presencecount-Subquery (LEFT JOIN) hinzu, wenn der Presence-Counter aktiviert ist.
- **Parameter:** `$scope` (Literal in SELECT).
- **Rueckgabe:** `string` (SQL-Fragment).
- **Seiteneffekte:** `get_config('booking','bookingstrackerpresencecounter')` Read; kein Query-Run. Param `:statustocount`.
- **Aufrufkette:** Von `return_sql_for_booked_users` der konkreten Answer-Scopes.
- **Bewertung:** C — grosser handgebauter SQL-String; presencecount-Subquery ist Duplikat der Variante in `scope_base_options` und `alloptions`; `$scope` per Konkatenation. Smell scope_base_answers.php:42-88.

### `get_endpart(): string` — public
- **Zweck:** Liefert `ORDER BY titleprefix, text, lastname, firstname, timemodified ASC`.
- **Rueckgabe:** `string`.
- **Seiteneffekte:** Keine.
- **Bewertung:** A.

### `return_cols_for_tables(int $statusparam): array` — public
- **Zweck:** Spalten der Detailtabelle (titleprefix/text/firstname/lastname/email; bei statusparam 0 zusaetzlich presencecount/status/notes; immer timemodified).
- **Rueckgabe:** `array` Spalten-Map.
- **Seiteneffekte:** `get_config` Read, `get_string`.
- **Aufrufkette:** Vom Tabellen-Bau; ueberschreibt Basis.
- **Bewertung:** A.

## Notizen
Saubere Trennung; einziger relevanter Geruch ist der duplizierte presencecount-Subquery-Block (dreifach: hier, scope_base_options, alloptions) — Kandidat fuer Extraktion in eine gemeinsame Helfer-Methode.
