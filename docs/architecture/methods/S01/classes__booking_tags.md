# booking_tags — Methoden-Doku
**Datei:** `classes/booking_tags.php` · **LOC:** 169 · **Subsystem:** S01 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`booking_tags` implementiert ein einfaches kursweites Text-Platzhalter-System (eigenstaendig, nicht zu verwechseln mit S09-Placeholders). Bei der Konstruktion laedt es alle `booking_tags`-Records eines Kurses und baut daraus parallele `keys`/`values`-Arrays (`[tagname]` → Ersetzungstext). Mit `booking_replace()`/`option_replace()` ersetzt es diese Tags in einer Whitelist von Textfeldern einer Booking-Instanz (`bookingtextfields`) bzw. Buchungsoption (`optiontextfields`). Persistenz: liest `booking_tags` (DB). Kollaborateure: `$DB`, Konsumenten in Renderer-/Messaging-Pfaden, die Instanz-/Option-Settings vor der Anzeige durch die Tag-Ersetzung schicken.

## Methoden

### `public function __construct($courseid)` — public
- **Zweck:** Laedt alle Tags des Kurses und baut die Ersetzungstabelle. **Seiteneffekte:** `$DB->get_records('booking_tags', ['courseid' => $courseid])`; ruft `prepare_replaces()`. **Bewertung:** B — `$courseid` untypisiert; ein DB-Read pro Konstruktion ohne Caching (bei wiederholter Erzeugung pro Option/Instanz potenziell N Reads).

### `public function get_all_tags()` — public
- **Zweck:** Liefert die rohen Tag-Records. **Bewertung:** A.

### `private function prepare_replaces(): array` — private
- **Zweck:** Wandelt die Tag-Records in zwei parallele Arrays `['keys' => ['[tag]', …], 'values' => [text, …]]` fuer `str_replace`. **Bewertung:** B — parallele Arrays statt assoziativer Map; funktioniert, aber positionsgekoppelt (Reihenfolge muss konsistent bleiben).

### `public function get_replaces(): array` — public
- **Zweck:** Gibt die vorbereitete keys/values-Struktur zurueck. **Bewertung:** A.

### `public function tag_replaces($text)` — public
- **Zweck:** Fuehrt die eigentliche `str_replace($keys, $values, $text)`-Ersetzung auf einem String aus. **Bewertung:** A — schlank, kerngewuenschte Funktion.

### `public function booking_replace(?stdClass $settings = null): stdClass` — public
- **Zweck:** Klont die Instanz-Settings und ersetzt Tags in allen Feldern der `bookingtextfields`-Whitelist (sofern nicht null). **Seiteneffekte:** keine (arbeitet auf Klon, mutiert Original nicht). **Bewertung:** B — `?stdClass = null`-Default fuehrt bei `null` direkt zu `clone null` (Fatal); Aufrufer muss Settings garantieren, der Default-null ist irrefuehrend.

### `public function option_replace(?stdClass $optionsettings = null): stdClass` — public
- **Zweck:** Wie `booking_replace`, aber fuer Optionsfelder (`optiontextfields`-Whitelist). **Bewertung:** B — gleiche `clone null`-Falle wie `booking_replace`; die beiden Methoden sind nahezu identisch (nur Whitelist-Property unterscheidet sich) → Duplikation.

### Triviale Properties
`tags`, `replaces` (oeffentlich), `option` (privat, ungenutzt im sichtbaren Code) sowie die beiden Whitelist-Arrays `optiontextfields`/`bookingtextfields` (Z.49–62) als Konfiguration der ersetzbaren Felder.

## Bewertungs-Resümee
Funktional klares, kleines Substitutionssystem. Schwaechen: parallele keys/values-Arrays statt Map, die `clone null`-Falle bei nullbarem Default und die Duplikation zwischen `booking_replace`/`option_replace` (gleiche Schleife, andere Whitelist). Kein Caching der DB-Reads. Klassen-Score **B / P3**.
