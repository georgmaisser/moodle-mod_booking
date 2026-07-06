# teachers.php — Methoden-Doku
**Datei:** `teachers.php` · **LOC:** 116 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Einstiegspunkt (kein Klassenkontext) fuer die oeffentliche „Alle Lehrer"-Uebersichtsseite. Das Skript loest bedingt `require_login`, baut auf System-Kontext, ermittelt per SQL die Menge relevanter Teacher-Userids (entweder ALLE oder nur die aus konfigurierten Booking-Instanzen) und delegiert das Rendering an das DTO `mod_booking\output\page_allteachers` ueber `renderer::render_allteacherspage`. Kollaborateure: `$DB`, `$PAGE`/`$OUTPUT`, `get_config('booking', ...)` (`teachersnologinrequired`, `allteacherspagebookinginstances`), `page_allteachers`, `renderer`. Keine Mutation — reiner Lese-/Render-Pfad.

## Request-/Permission-Flow
1. **Z.28–33 — config.php + bedingtes Login:** Login wird nur erzwungen, wenn `get_config('booking','teachersnologinrequired')` leer ist. Ist die Einstellung gesetzt, ist die Seite ohne Authentifizierung erreichbar (gewollter „oeffentlicher" Modus).
2. **Z.37–39 — Kontext:** `context_system::instance()`; bei Fehlschlag `moodle_exception('badcontext')`. Keine capability-Pruefung auf den Inhalt (bewusst, oeffentliche Seite).
3. **Z.42–53 — Page-Setup:** URL, Titel (`allteachers`), pagelayout `base`, Body-Klasse, `$OUTPUT->header()`.
4. **Z.59–100 — Teacher-Selektion:** `allteacherspagebookinginstances` wird per `explode(',', ...)` zerlegt.
   - Ist der Wert „leer"/„0" → SQL ueber ALLE `booking_teachers` (DISTINCT userid, LEFT JOIN `user`).
   - Sonst: nur Teacher der gewaehlten Instanzen (JOIN `booking` → `booking_options` → `booking_teachers` → `user`, `WHERE b.id $insql` via `get_in_or_equal(..., SQL_PARAMS_NAMED)`); fuer Nutzer mit `mod/booking:updatebooking` wird zusaetzlich ein Warn-Alert mit Settings-Link ausgegeben.
5. **Z.102–106 — Reduktion:** aus den Records werden nur die `userid` in `$teacherids` gesammelt.
6. **Z.109–116 — Render:** `new page_allteachers($teacherids)` → `render_allteacherspage` → `$OUTPUT->footer()`.

## Bewertung einzelner Stellen
- **Z.61–68 — toter Guard:** Die Bedingung `empty($bookinginstances) || !is_array($bookinginstances) || ...` ist in den ersten beiden Disjunkten effektiv tot: `explode()` liefert immer ein nicht-leeres Array (mind. `['']`), also ist `empty()` nie wahr und `!is_array()` nie wahr. Wirksam ist nur der dritte Zweig (`count==1 && [0]==0`). Anti-Pattern, funktional harmlos. **Bewertung:** C / P3.
- **Z.71/93 — selektiertes, ungenutztes `u.email`:** Beide SQLs selektieren `firstname, lastname, email`, obwohl danach (Z.104) ausschliesslich `userid` weiterverwendet wird. Ueberfluessige Spaltenselektion; in Kombination mit dem login-losen Modus (Z.31) ist das mindestens ein Geruch in Richtung Datensparsamkeit — die Mailadressen verlassen das Skript hier zwar nicht, aber `page_allteachers` rendert eine oeffentlich abrufbare Teacher-Liste. **Bewertung:** C / P3 (Privacy/Cleanup).
- **Z.102 — Full-Scan-Variante:** Im „ALLE"-Zweig ist `SELECT DISTINCT ... FROM booking_teachers LEFT JOIN user` ein potenziell grosser, ungefilterter Scan; bei vielen Teachern teuer, aber durch `DISTINCT` + Sortierung begrenzt und nur einmal pro Request. **Bewertung:** C / P3.

## Bewertungs-Resümee
Schlankes, gut lesbares Render-Skript ohne Mutationen. Schwaechen: der teils tote Instanz-Guard, die ungenutzte `email`-Selektion (im login-losen Modus heikel hinsichtlich Datensparsamkeit) und der ungefilterte All-Teachers-Scan. Keine funktionale Datenverlust-/Korrektheitsgefahr. Klassen-Score **C / P3**.
