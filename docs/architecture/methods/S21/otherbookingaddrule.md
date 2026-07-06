# otherbookingaddrule — Methoden-Doku
**Datei:** `otherbookingaddrule.php` · **LOC:** 106 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Controller fuer **Add / Edit / Delete** einer `booking_other`-Regel. Keine Klasse: kombiniert Berechtigungspruefung, direktes DB-CRUD und das `otherbookingaddrule_form`. Persistenz: schreibt/loescht `booking_other`. Kollaborateure: `otherbookingaddrule_form`, `$DB`, `redirect()`. Nach jeder Aktion Redirect zurueck auf `otherbooking.php`.

## Ablauf (prozeduraler Request-Flow)

### Setup & Berechtigung (Z.25–53)
- **Zweck:** Required `id` (cmid) + `optionid`, optional `bookingotherid`/`delete`. Baut Self-URL und Redirect-URL; `get_course_and_cm_from_cmid`, `require_course_login`, `context_module::instance`, `require_capability('mod/booking:updatebooking')`. **Seiteneffekte:** Login-Erzwingung, `moodle_exception('badcontext')`. **Bewertung:** A — Schutzkette korrekt.

### Delete-Pfad (Z.55–58)
- **Zweck:** Bei `delete==1` wird `booking_other` mit der `bookingotherid` geloescht und auf `otherbooking.php` redirected. **Seiteneffekte:** `$DB->delete_records` (Daten-Mutation). **Bewertung:** C — die Loeschung erfolgt direkt aus einem **GET-Request ohne sesskey/Bestaetigung** (die Delete-Links in `otherbooking.php` sind GET `single_button`). Capability wird zwar geprueft, aber es fehlt CSRF-Schutz (`require_sesskey`) und eine Confirm-Stufe. P3 (admin-only Capability mildert das Risiko).

### Save-/Display-Pfad (Z.65–104)
- **Zweck:** Instanziiert das Form mit `bookingotherid`+`optionid` als customdata. Drei Zweige: (a) cancel → Redirect; (b) `get_data()` liefert Daten → Upsert in `booking_other` (`update_record` wenn `id>0`, sonst `insert_record`); (c) sonst Form anzeigen, bei Edit Defaults aus `booking_other` laden (`id` unset, `bookingotherid` setzen). **Seiteneffekte:** `$DB->update_record`/`insert_record`, Redirect, Echo HTML. **Bewertung:** B — Standard-Moodle-Form-Controller-Muster; `get_data()` enthaelt implizit den sesskey-Check, daher ist der Save-Pfad (anders als Delete) CSRF-geschuetzt. `userslimit` wird ungecastet uebernommen (Form-`setType PARAM_INT` greift), `otheroptionid` explizit `(int)`-gecastet.

### Footer (Z.106)
- Echo Footer (nur erreicht im Display-Zweig, da Save/Delete vorher redirecten).

## Bewertungs-Resümee
Kompakter CRUD-Controller mit korrekter Capability-Pruefung und sauberem Upsert. Einziger echter Mangel: der Delete-Zweig fuehrt eine DB-Loeschung ohne sesskey/Confirm auf GET aus. Keine Daten-Verlust-/N+1-Probleme im Save-Pfad. Klassen-Score **C / P3**.
