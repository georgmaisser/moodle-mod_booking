# sendmessage — Methoden-Doku
**Datei:** `sendmessage.php` · **LOC:** 168 · **Subsystem:** S21 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point mit einer Top-Level-Funktion `send_custom_message()`. Die Seite zeigt das Custom-Message-Formular (`mod_booking_sendmessage_form`) und versendet beim Submit eine individuelle Nachricht an die per `uids` (JSON-Array) selektierten User. Pro Empfaenger wird ein `message_controller` (SEND_NOW / CUSTOM_MESSAGE) erzeugt und ein `custom_message_sent`-Event gefeuert; bei breiter Streuung zusaetzlich ein `custom_bulk_message_sent`-Event. Kollaborateure: `singleton_service` (Option-Settings + booking_answers), `message_controller`, Events `custom_message_sent`/`custom_bulk_message_sent`, `cache_helper`.

## Methoden

### Top-Level-Request-Flow (Z.34–90) — top-level
- **Zweck:** Liest `id` (cmid), `optionid`, `uids` (PARAM_RAW, JSON), ermittelt cm/Kurs, `require_course_login`, Modul-Kontext und `require_capability('mod/booking:communicate', $context)`. Baut das Formular; bei Cancel Redirect, bei gueltigem Submit `clean_param_array(json_decode($uids), PARAM_INT)` und Aufruf von `send_custom_message(...)`, dann Redirect mit Erfolgsmeldung. Sonst Form-Anzeige. **Seiteneffekte:** Login-/Capability-Erzwingung, HTTP-Redirects, Form-Ausgabe. **Bewertung:** B — Capability-Gate korrekt; `uids` wird vor Verwendung per `clean_param_array(..., PARAM_INT)` saniert. `groups_get_activity_groupmode($cm)` (Z.47) wird ermittelt aber nie verwendet (toter Code).

### `function send_custom_message(int $optionid, string $subject, string $message, array $selecteduserids)` — global (Z.101)
- **Zweck:** Versendet pro selektiertem User eine Custom-Nachricht und feuert je ein `custom_message_sent`-Event; erkennt anschliessend heuristisch ob es eine „Bulk"-Nachricht war und feuert dann zusaetzlich `custom_bulk_message_sent`. **Seiteneffekte:** pro User `new message_controller(...)->send_or_queue()` (versendet/queued Mail), Event-Trigger, **`cache_helper::purge_by_event('setbackeventlogtable')` innerhalb der Empfaenger-Schleife** (Z.139). **Rueckgabe:** void. **Bewertung:** C —
  - **N+1 / Performance (Prio P2):** der Cache-Purge `purge_by_event('setbackeventlogtable')` steht **in der Schleife** (Z.139) und wird damit fuer JEDEN Empfaenger erneut ausgefuehrt; bei einer Custom-Mail an viele User vervielfacht das die Cache-Invalidierung. Er gehoert einmalig nach die Schleife. Ebenso wird `context_system::instance()` pro Iteration neu geholt (Core cached das zwar, aber das Event-Array wird je User komplett neu gebaut).
  - **Bulk-Heuristik (Z.143–167):** „Bulk" = ≥3 Empfaenger UND ≥75% der gebuchten User. Division `($countselected / $countbooked)` ist gegen `$countbooked==0` faktisch geschuetzt (der Block laeuft nur bei nicht-leerem `$bookedusers`), also kein Division-by-zero. Funktional ok, aber die Schwellen sind hartkodiert.
  - `message_controller` mit `MSGCONTRPARAM_SEND_NOW` wird pro User synchron im Web-Request versandt — bei grossen Empfaengerlisten potentiell Timeout-anfaellig (kein Adhoc-Task-Pfad).

## Bewertungs-Resümee
Korrekt abgesicherter Versand-Endpoint (`communicate`-Cap, sanitiertes `uids`). Hauptschwaeche ist die Top-Level-Funktion: der **Cache-Purge liegt in der Empfaenger-Schleife** (P2, unnoetige wiederholte Invalidierung) und der synchrone Direktversand pro User skaliert schlecht. Toter `groupmode`-Aufruf. Klassen-Score **C / P2**.
