# bookinginstancetemplatessettings — Methoden-Doku
**Datei:** `bookinginstancetemplatessettings.php` · **LOC:** 59 · **Subsystem:** S21 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Die Seite listet alle Instanz-Templates (`booking_instancetemplate`) ueber das `bookinginstancetemplatessettings_table` und bietet eine Loesch-Aktion. Aufgerufen wird sie mit der Course-Module-id `id`. Kollaborateure: `bookinginstancetemplatessettings_table` (local_wunderbyte_table-Abkoemmling), `$DB`, `$PAGE`, `$OUTPUT`, Core `get_course_and_cm_from_cmid`.

## Request-/Permissions-Flow
1. `require_once config.php` + `tablelib.php` + `adminlib.php`.
2. Params: `id` (PARAM_INT, cmid, required), `templateid` (PARAM_INT, optional), `action` (PARAM_ALPHANUM, optional).
3. `[$course, $cm] = get_course_and_cm_from_cmid($id)` — loest Kurs/CM aus der cmid.
4. `require_course_login($course, false)` — kein Guest-Autologin, aber **nur Login-Gate, keine Capability-Pruefung**.
5. Wenn `action === 'delete'` und `templateid > 0`: `$DB->delete_records('booking_instancetemplate', ['id' => $templateid])` und Redirect mit `templatedeleted`-Notification.
6. Sonst: Tabelle mit `set_sql('id, name', '{booking_instancetemplate}', '1=1')` befuellen, Page-Setup, Header/Heading, `$table->out(25, true)`, Footer.

## Bewertung der Einzelschritte
- **Loesch-Aktion (Z.38–41):** Loescht ein Template anhand `templateid` ueber einen **GET-Request ohne `sesskey`/`confirm_sesskey`** — klassische CSRF-Luecke; ein praeparierter Link `?id=..&action=delete&templateid=X` loescht still. Zusaetzlich fehlt jede `require_capability(...)`: nach `require_course_login` darf **jeder im Kurs eingeschriebene Nutzer** loeschen. Da `booking_instancetemplate` global (nicht kurs-scoped, `1=1`) ist, kann so jedes beliebige Instanz-Template entfernt werden. **Bewertung:** C / P2 (CSRF + fehlende Autorisierung, Datenverlust).
- **Tabellen-Listing (Z.43–57):** `set_sql(..., '1=1')` zeigt alle Templates systemweit unabhaengig vom Kurs; konsistent mit dem globalen Charakter der Templates, aber keine Mandanten-/Kurstrennung. **Bewertung:** B.
- **Page-Setup (Z.48–55):** Standard, korrekt (`set_url`, `set_title`, `navbar->add`). **Bewertung:** A.

## Bewertungs-Resümee
Funktional schlankes Listing, aber die Loesch-Aktion ist die Schwachstelle: GET ohne sesskey-Schutz und ohne Capability-Check auf einer global wirkenden Tabelle ermoeglicht CSRF-getriebenen Datenverlust durch jeden eingeloggten Nutzer. Klassen-Score **B / P2**.
