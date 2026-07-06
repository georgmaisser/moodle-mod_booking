# competencies_handler — Methoden-Doku
**Datei:** `classes/local/competencies/competencies_handler.php` · **LOC:** 139 · **Subsystem:** S19 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`competencies_handler` ist ein rein statischer Lese-Cache fuer Nutzerkompetenzen aus Moodles Core-Competency-Subsystem (`competency_usercomp`, `competency`). Er versorgt vor allem Availability-Conditions mit der Frage „besitzt User X die Kompetenz Y?" sowie Shortname-Aufloesungen. Zweistufiges Caching: ein prozessstatischer Array-Cache (`$usercompetencies`, `$competencyshortnames`) vor einem MUC-Application-Cache (`usercompetenciescache`, `competenciesshortnamescache`). Persistenz: nur lesend gegen Core-Tabellen; schreibt ausschliesslich in Caches. Kollaborateure: `$DB`, `cache`.

## Methoden

### `public static function get_user_competency_ids(int $userid, ?int $timestamp = null): array` — public static
- **Zweck:** Liefert alle Competency-IDs eines Users, optional gefiltert auf `timecreated <= $timestamp`. Reihenfolge der Quellen: statischer Cache -> MUC-Cache -> DB-Query, danach Befuellen beider Caches. **Seiteneffekte:** `cache::make('mod_booking','usercompetenciescache')`, ggf. `$DB->get_records_sql(...)` bzw. `$DB->get_records('competency_usercomp', ...)`; schreibt in statischen und MUC-Cache. **Rueckgabe:** array von Competency-IDs (Keys aus den Records). **Bewertung:** C — **Cache-Key-Bug:** der Cache wird unter `[$userid]` gespeichert, ohne `$timestamp` in den Schluessel aufzunehmen. Folge: Wenn zuerst mit `$timestamp` gefiltert geladen wird, liefert ein spaeterer ungefilterter Aufruf faelschlich die gefilterte Teilmenge — und umgekehrt erhaelt ein gefilterter Aufruf nach einem ungefilterten die volle Liste. Innerhalb eines Requests/Cache-Lebens fuehrt das je nach Aufrufreihenfolge zu falschen Zeit-Filter-Ergebnissen.

### `public static function get_competency_shortname_by_id(int $competencyid): string` — public static
- **Zweck:** Loest den `shortname` einer Kompetenz auf, gecacht statisch + MUC. **Seiteneffekte:** `cache::make('mod_booking','competenciesshortnamescache')`; bei Cache-Miss `$DB->get_field('competency','shortname', ...)`; bei leerem Ergebnis `debugging(...)` und Rueckgabe `''` (dieser Negativfall wird **nicht** gecacht — wiederholte Misses fragen erneut die DB). **Rueckgabe:** string (Shortname oder `''`). **Bewertung:** B — sauberer Read-Through-Cache; kleiner Negativ-Caching-Gap bei nicht existierenden IDs (geringe Praxisrelevanz). Kommentar „store per user" ist hier falsch (kopiert), tatsaechlich per competencyid.

### `public static function user_has_competency(int $userid, int $competencyid, ?int $timestamp = null): bool` — public static
- **Zweck:** Praedikat, ob ein User eine bestimmte Kompetenz besitzt. **Seiteneffekte:** delegiert an `get_user_competency_ids()` (erbt deren Caching-Verhalten samt Timestamp-Cache-Bug), dann `in_array()`. **Rueckgabe:** bool. **Bewertung:** B — duenner Wrapper; `in_array()` ohne strict-Flag, da IDs aus `array_keys()` als ints/numerische Strings vorliegen, praktisch unkritisch.

### `public static function reset_caches(): void` — public static
- **Zweck:** Leert die beiden prozessstatischen Caches (gedacht fuer Test-Teardown). **Seiteneffekte:** setzt `$usercompetencies` und `$competencyshortnames` auf `[]` zurueck; beruehrt den MUC-Cache **nicht**. **Rueckgabe:** void. **Bewertung:** B — nuetzlich fuer Tests, raeumt aber nur die statische Ebene.

## Bewertungs-Resümee
Solider zweistufiger Lese-Cache. Hauptschwaeche ist der fehlende `$timestamp` im Cache-Schluessel von `get_user_competency_ids`, der bei gemischten gefilterten/ungefilterten Aufrufen falsche Resultate liefern kann. Sonst kleinere Punkte (kein Negativ-Caching, irrefuehrender Kommentar). Klassen-Score **B / P2**.
