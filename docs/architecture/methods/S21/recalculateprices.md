# recalculateprices — Methoden-Doku
**Datei:** `recalculateprices.php` · **LOC:** 103 · **Subsystem:** S21 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Einstiegspunkt (keine Klasse, aber `namespace mod_booking`). Bestaetigungs-/Trigger-Seite, um die Preisneuberechnung aller Buchungsoptionen als Adhoc-Task in die Queue zu legen. Im GET-Pfad rendert sie eine Bestaetigungsvorlage (`mod_booking/recalculateprices`); mit `submit=1` validiert sie Vorbedingungen (Preiskategorien vorhanden, Preisformel gesetzt) und queued den Task `\mod_booking\task\recalculate_prices`. Kollaborateure: `price` (Pruefung `pricecategories`), `recalculate_prices`-Task, `\core\task\manager`, `get_config('booking', ...)`, `$DB`, `$OUTPUT`. Persistenz: Tabelle `task_adhoc` (ueber den Task-Manager).

## Request-/Permission-Flow
1. `require_once config.php` + `adminlib.php`. Parameter `id` (cmid, `PARAM_INT`), `submit` (`PARAM_BOOL`).
2. `get_course_and_cm_from_cmid($cmid)` + `require_course_login($course, false, $cm)` — Kurs-Login als einziges erzwungenes Gate.
3. `$PAGE->activityheader->disable()`, URL/Title.
4. Baut `$data` (cmid, Back-/Continue-URLs, Alert-Text) und `$data->hascap = has_capability('mod/booking:calculateprices', $context)` — **dieser Wert wird nur an die Vorlage gereicht, nicht als Gate im Submit-Pfad verwendet** (siehe Findings).
5. `if ($submit)`:
   - `price`-Objekt; leere `pricecategories` → Alert „nopricecategoriesyet".
   - leere `defaultpriceformula` → Alert „nopriceformulaset" (mit Link zu den Admin-Defaults).
   - sonst: wenn KEIN Adhoc-Task mit `classname = '\mod_booking\task\recalculate_prices'` existiert, neuen Task mit `custom_data = ['cmid' => $cmid]` und `userid` anlegen und via `reschedule_or_queue_adhoc_task()` einreihen; Erfolgsmeldung + `redirect`. Existiert bereits einer, Meldung „bocondoptionhasstarted".
6. GET/Nicht-Submit: Header, Heading, `render_from_template('mod_booking/recalculateprices', $data)`, Footer.

## Bewertung
- **Seiteneffekte:** Einreihen eines Adhoc-Tasks (`task_adhoc`-Insert/Reschedule), Konfig-Reads, Redirect, Seitenausgabe.
- **Bewertung:** C —
  1. **Fehlendes Capability-Gate im Submit-Pfad (P2):** `$data->hascap` (Z.63) wird berechnet, aber der `if ($submit)`-Block (Z.65) prueft `mod/booking:calculateprices` nie. Einziges Gate ist `require_course_login`; ein eingeschriebener Nutzer kann per `?submit=true` die Preisneuberechnung anstossen, obwohl die UI den Button nur bei `hascap` anzeigt.
  2. **Instanzuebergreifender Existenz-Check (P2):** Der Guard `record_exists('task_adhoc', ['classname' => '\mod_booking\task\recalculate_prices'])` (Z.79) ist GLOBAL und nicht nach `cmid` gefiltert. Ein bereits eingereihter Recalc-Task fuer eine BELIEBIGE Booking-Instanz blockiert das Queuen fuer diese Instanz und liefert die irrefuehrende Meldung „bocondoptionhasstarted".
  3. Kein `confirm_sesskey()` im Submit-Pfad (CSRF-Trigger eines reschedule-idempotenten Tasks; geringes, aber reales Risiko).

## Bewertungs-Resümee
Funktional korrekter Task-Trigger mit guter Vorbedingungs-Pruefung (Kategorien/Formel), aber mit echten Schwaechen: die Capability `calculateprices` wird ermittelt und doch nicht durchgesetzt, und der Adhoc-Existenz-Check arbeitet instanzuebergreifend. Klassen-Score **C / P2**.
