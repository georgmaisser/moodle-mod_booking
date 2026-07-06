# categories — Methoden-Doku
**Datei:** `categories.php` · **LOC:** 94 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Listet die Booking-Kategorien (`booking_category`) eines Kurses als verschachtelte `<ul>`-Struktur und rendert pro Kategorie Edit-/Delete-Links nach `categoryadd.php`. Tiefere Ebenen werden ueber die globale Hilfsfunktion `booking_show_subcategories()` (aus `lib.php`) ausgegeben. Kollaborateure: `$DB`, `$OUTPUT`, `$PAGE`, `$COURSE`, `booking_show_subcategories`, `mod_booking_categories_form` (per `require_once`).

## Request-/Permissions-Flow
1. `require_once config.php` + `lib.php` + `categoriesform.class.php`.
2. `courseid` (PARAM_INT, required); Page-URL/Context (`context_course::instance($courseid)`).
3. `require_login($courseid, false)` — **nur Login-Gate, KEINE `require_capability`**.
4. Wurzelkategorien laden: `$DB->get_records('booking_category', ['course' => $courseid, 'cid' => 0])`.
5. Header, Heading, „Neue Kategorie"-Box, dann pro Wurzelkategorie ein `<li>` mit Edit-/Delete-Link.
6. Pro Wurzelkategorie zusaetzliche Query der Subkategorien (Z.72) und pro Subkategorie ein `<li>` + Aufruf von `booking_show_subcategories($subcat->id, $courseid)` (rekursive Tiefe).
7. Footer.

## Bewertung der Einzelschritte
- **Fehlende Autorisierung (Z.34–36):** Es gibt nur `require_login`, keinen Capability-Check (z.B. `mod/booking:addinstance`/manage). Jeder im Kurs eingeschriebene Nutzer sieht die Kategorieverwaltung samt Edit-/Delete-Links. **Bewertung:** C / P3 (fehlende Autorisierung; die eigentliche Mutation liegt aber in `categoryadd.php`).
- **Unescaped Ausgabe (Z.71, 83):** `$category->name` / `$subcat->name` werden roh in HTML interpoliert, ohne `format_string()`/`s()`. Kategorienamen sind nutzergepflegt → **gespeicherter XSS** moeglich. **Bewertung:** C / P3.
- **N+1-Query (Z.66–84):** je Wurzelkategorie eine separate `get_records`-Query fuer Subkategorien (Z.72) plus `booking_show_subcategories()` pro Subkategorie (Z.84, vermutlich selbst rekursiv DB-lesend). Bei vielen Kategorien linear wachsende Querymenge; fuer typische Kategoriebaeume klein, aber strukturell N+1. **Bewertung:** C / P3.
- **Mutierende Links via GET (Z.69, 81):** Delete-Links zeigen mit `&delete=1` auf `categoryadd.php` — Loeschung wird (wenn dort ungeschuetzt) ueber GET ausgeloest; Bewertung haengt an `categoryadd.php`, hier nur als Hinweis.

## Bewertungs-Resümee
Einfaches Verwaltungs-Listing mit drei strukturellen Schwaechen: fehlender Capability-Check, unescaptes Echo der Kategorienamen (XSS) und ein N+1-Lesemuster. Funktional ausreichend fuer kleine Baeume, sicherheitstechnisch verbesserungswuerdig. Klassen-Score **C / P3**.
