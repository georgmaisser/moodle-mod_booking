# tag — Methoden-Doku
**Datei:** `tag.php` · **LOC:** 70 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (kein Klassen-Deklarant). Die Seite listet alle Booking-Instanzen *desselben Kurses*, die einem gegebenen Moodle-Core-Tag zugeordnet sind, als Link-Liste. Kollaborateure: `core_tag_tag` (`/tag/lib.php`), `$DB` (Tabellen `tag_instance`, `booking`), `get_coursemodule_from_instance`, `$PAGE`/`$OUTPUT`.

## Request-/Permission-Flow
1. **Parameter:** `id` (cmid, `required_param`), `tag` (`optional_param`, `PARAM_TAG`, Default `''`).
2. **Auth (Z.35-37):** `get_course_and_cm_from_cmid($id)` → `require_course_login($course, false, $cm)`.
3. **Tag-Aufloesung (Z.38-40):** `core_tag_tag::get_by_name(0, $tagname)` → `make_display_name($tag)`.
4. **Page-Setup (Z.39-46):** Standard-Pagelayout, Navbar-Eintrag, Heading = Kurs-Fullname, Title = "Tag - <name>".
5. **Listing (Z.52-66):** `$DB->get_records('tag_instance', ['tagid' => $tag->id, 'itemtype' => 'booking'])`; pro Record wird die `booking`-Instanz geladen (gefiltert auf `course => $cm->course`) und — falls vorhanden — der zugehoerige Kursmodul-Link gerendert.

## Bewertung der Einzelschritte
- **Tag-Aufloesung (Z.38/52):** `get_by_name` liefert bei leerem oder nicht existentem `$tagname` `false`. Direkt danach (`make_display_name($tag)`, Z.40) und im DB-Query (`$tag->id`, Z.52) wird ohne Null-Guard auf `$tag` zugegriffen → bei fehlendem/unbekanntem Tag PHP-Warning bzw. Fehler. Da `tag` ein `optional_param` mit Default `''` ist, ist dieser Pfad direkt erreichbar. Bewertung C (siehe Findings P3).
- **Listing (Z.58-65):** Klassisches N+1 — pro `tag_instance`-Record je ein `get_record('booking', ...)` und ein `get_coursemodule_from_instance(...)`. Tag-zu-Instanz-Mengen sind in der Praxis klein, daher Prio niedrig. Bewertung B.
- **Markup:** `$booking->name` wird ohne `format_string`/`s()` in das `<li>`-HTML interpoliert (Z.63). Instanz-Namen sind admin-kuratiert; dennoch unsauber ggue. dem restlichen Code. Bewertung B.

## Bewertungs-Resümee
Einfache, leserliche Tag-Listing-Seite. Schwaechen: fehlender Null-Guard nach `get_by_name` (direkt erreichbar via leerem/unbekanntem Tag), ein gutartiges N+1 ueber die Tag-Instanzen und ungefiltertes Echo des Instanz-Namens. Funktional weitgehend unkritisch. Klassen-Score **B / P3**.
