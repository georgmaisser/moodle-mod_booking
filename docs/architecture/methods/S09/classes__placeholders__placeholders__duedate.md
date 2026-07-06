# duedate — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/duedate.php` · **LOC:** 82 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`duedate` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den Platzhalter `{duedate}` durch das formatierte Faelligkeitsdatum einer Ratenzahlung (Installment) ersetzt. Stateless; reine statische API. Persistenz: keine; verwendet ausschliesslich den uebergebenen `$duedate`-Timestamp. Kollaborateure: Core `userdate()` / `get_string('strftimedate', 'langconfig')`. `placeholders_info`/`singleton_service` sind importiert, werden hier aber nicht genutzt.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Formatiert den uebergebenen `$duedate`-Timestamp gemaess dem Sprachstring `strftimedate` zu einem lesbaren Datum.
- **Seiteneffekte:** keine (kein DB-/Singleton-Zugriff, kein Memo-Schreiben).
- **Rueckgabe:** string — `userdate($duedate, $timeformat)`.
- **Bewertung:** B — Trivial-korrekt fuer gesetztes `$duedate`. Bei Default `$duedate = 0` wird die Unix-Epoche (1970) formatiert statt eines Leer-/Fehlerwerts; kein Guard wie bei den anderen Platzhaltern (`sthwentwrongwithplaceholder`). Da `duedate` nur im Installment-Kontext mit echtem Timestamp aufgerufen wird, praktisch unkritisch.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Einfachster moeglicher Platzhalter: reine `userdate`-Formatierung des uebergebenen Timestamps. Einziger Schoenheitsfehler ist die fehlende 0-Timestamp-Behandlung (zeigt 1970 statt Leerwert), funktional aber im realen Installment-Kontext irrelevant. Klassen-Score **B / P3**.
