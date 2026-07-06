# tagtemplates — Methoden-Doku
**Datei:** `tagtemplates.php` · **LOC:** 97 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (kein Klassen-Deklarant; der Datei-Header-Comment "Import options from CSV" ist ein Copy-Paste-Artefakt und beschreibt die Seite falsch). Die Seite listet alle Tag-Templates eines Kurses (`[tag]` + Text) in einer Tabelle mit Edit-/Delete-Buttons und bietet einen Delete-Controller. Kollaborateure: `mod_booking\booking_tags::get_all_tags()`, `$DB` (Tabelle `booking_tags`), `html_table`/`html_writer`, `$OUTPUT->single_button`.

## Request-/Permission-Flow
1. **Parameter:** `id` (cmid, `required_param`), `tagid` (`optional_param`, Default 0), `action` (`optional_param`, `PARAM_ALPHANUM`).
2. **Auth (Z.38-51):** `get_course_and_cm_from_cmid($id)` → `require_course_login($course, false, $cm)`; `activityheader->disable()`; `groups_get_activity_groupmode($cm)` (Rueckgabe ungenutzt); `context_module` → `require_capability('mod/booking:updatebooking', $context)`.
3. **Delete-Controller (Z.53-56):** wenn `action === 'delete'` und `tagid > 0`: `$DB->delete_records('booking_tags', ['id' => $tagid])` → Redirect mit `tagdeleted`-Meldung.
4. **Listing (Z.66-85):** `new booking_tags($cm->course)` → `get_all_tags()`; pro Tag ein Edit-Button (→ `tagtemplatesadd.php`) und ein Delete-Button (→ `tagtemplates.php?...&action=delete`), gerendert in eine `html_table` mit Spalten Tag/Text/Buttons. `nl2br($tag->text)`.
5. **Footer (Z.87-97):** Cancel- und "Add new"-Buttons, dann Footer.

## Bewertung der Einzelschritte
- **Delete via GET (Z.53-56):** Der destruktive `delete_records`-Pfad wird durch einen `single_button(..., 'get')` (Z.77) ausgeloest, also per GET-Request **ohne `require_sesskey()`/`confirm_sesskey()`**. Das ist eine klassische CSRF-Loecke: ein praeparierter Link/`<img src>` kann bei einem eingeloggten Manager mit `updatebooking` ein Tag-Template loeschen. Bewertung C (siehe Findings P2).
- **`tagid` ohne Kurs-Scope (Z.54):** Das Delete filtert nur auf `id => $tagid`, nicht auf `$cm->course`; ein Manager eines Kurses koennte per manipuliertem `tagid` ein Tag-Template eines fremden Kurses loeschen (IDOR). Bewertung C (Findings P3).
- **Markup:** Inline-`style`-Attribute fuer Layout (Z.80/90-94) und ungefiltertes `$tag->tag` im `[...]`-Wrapper (admin-kuratiert) — kosmetisch.
- **Toter Code:** `groups_get_activity_groupmode($cm)` (Z.45) Rueckgabe wird nie verwendet.

## Bewertungs-Resümee
Funktional korrekte Listing-/Delete-Seite mit Capability-Gate. Die wesentliche Schwaeche ist das Loeschen ueber einen GET-Button ohne Sesskey-Token (CSRF) plus fehlender Kurs-Scope auf `tagid` (IDOR); dazu ein irrefuehrender Datei-Header und ungenutzter Groupmode-Aufruf. Klassen-Score **B / P3** (mit P2-Sicherheitsbefund im Delete-Pfad).
