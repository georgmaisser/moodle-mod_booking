# category — Methoden-Doku
**Datei:** `category.php` · **LOC:** 76 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Die Seite listet alle Booking-Instanzen, die einer bestimmten Booking-Kategorie (`booking_category`) zugeordnet sind, als Linkliste. Aufgerufen wird sie pro Kurs-Modul (`id` = cmid) mit einer `category`-id. Persistenz: nur lesend auf `course`, `booking_category` und `booking`. Kollaborateure: `$DB`, `$PAGE`, `$OUTPUT`, `get_coursemodule_from_id`/`get_coursemodule_from_instance`, `require_course_login`.

## Request-/Permission-Flow
1. `required_param('id', PARAM_INT)` (cmid) + `optional_param('category', '', PARAM_INT)` (Z.28-29).
2. `$PAGE->set_url(...)` (Z.31-33).
3. Aufloesung des Coursemodule via `get_coursemodule_from_id('booking', $id)` → `moodle_exception('invalidcoursemodule')` bei Fehlschlag (Z.35-37).
4. Kurs-Record laden → `moodle_exception('coursemisconf')` bei Fehlschlag (Z.39-41).
5. `require_course_login($course, false, $cm)` (Z.43) — einzige Zugriffskontrolle; KEINE eigene Capability-Pruefung. Jeder im Kurs eingeschriebene/zugangsberechtigte User kann die Kategorie-Liste sehen.
6. Kategorie-Record laden (Z.45), Navbar/Heading/Title setzen, Header ausgeben.
7. Booking-Instanzen der Kategorie ermitteln (Z.57) und als `<ul>`-Linkliste auf `view.php` rendern (Z.61-71).
8. Box-/Footer-Ausgabe.

## Auffaelligkeiten
- **Z.45-51 (P3): Kein `MUST_EXIST`-Guard auf die Kategorie.** `$category = $DB->get_record('booking_category', ['id' => $categoryid])` liefert bei leerem/ungueltigem `category` (default `''` → PARAM_INT → `0`) `false`. Direkt danach wird in Z.48/51 `$category->name` gelesen → Zugriff auf Property von `false` (Warning/Fehler) bzw. leere Seite. Kein definierter Fehlerpfad fuer „Kategorie nicht gefunden".
- **Z.57 (P2): Fehlerhafte und nicht-portable Kategorie-Abfrage.** `get_records_select('booking', 'categoryid LIKE "%' . $category->id . '%"')`. Zwei Probleme: (a) `categoryid` ist numerisch, ein `LIKE '%1%'`-Substring-Match trifft auch `11`, `21`, `100`, `512` usw. → falsche Booking-Instanzen werden gelistet; (b) doppelte Anfuehrungszeichen als String-Literal sind nicht ANSI-portabel — auf PostgreSQL werden `"..."` als Identifier interpretiert, die Query schlaegt fehl. Korrekt waere ein exakter Vergleich `categoryid = ?` mit Parameter. (Die id stammt aus der DB, daher keine SQL-Injection, aber die Semantik ist falsch.)
- **Z.63-70 (P3): N+1 / redundanter Re-Fetch.** Pro Treffer aus Z.57 wird in Z.64 dieselbe Zeile via `$DB->get_record('booking', ['id' => ..., 'course' => ...])` erneut geladen (nur um auf den Kurs zu filtern) und in Z.66 zusaetzlich `get_coursemodule_from_instance` aufgerufen. Der Course-Filter liesse sich in die WHERE-Klausel ziehen; der Re-Fetch ist vermeidbar.
- Roh-HTML-Konstruktion (`<ul>`, `<li><a>`) statt Renderer/Template; `$booking->name` wird ungefiltert ausgegeben (Name ist serverseitig gepflegt, aber `format_string` fehlt).

## Bewertungs-Resümee
Kurzes Listing-Skript mit duenner Zugriffskontrolle (nur `require_course_login`) und einem echten Query-Defekt: das `LIKE "%id%"` matcht falsch und bricht auf PostgreSQL. Dazu fehlender Existenz-Guard auf die Kategorie und ein vermeidbarer Per-Row-Re-Fetch. Funktional eingeschraenkt korrekt, aber fragil. Klassen-Score **C / P3**.
