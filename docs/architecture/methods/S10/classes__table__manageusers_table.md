# manageusers_table — Methoden-Doku
**Datei:** `classes/table/manageusers_table.php` · **LOC:** 1086 · **Subsystem:** S10 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S10_tables.md)

## Klassenueberblick
`manageusers_table` erweitert `local_wunderbyte_table\wunderbyte_table` und rendert die Manager-Sicht der gebuchten/wartelistenden User (verwendet in `report.php` / Buchungstracker). Zwei Verantwortungsbereiche mischen sich: (1) zahlreiche `col_*`-Renderer fuer einzelne Spalten (Checkbox, Status, Praesenz, Aktionsbuttons) und (2) `action_*`-Handler, die ueber das transmitaction-Pattern (AJAX) mutierende Operationen auf `booking_answers` ausfuehren — Bestaetigen, Entbestaetigen, Loeschen, Ablehnen, Zertifikate ausloesen, Umsortieren. Kollaborateure: `singleton_service` (Settings/Option/User/Answers), `booking_option` (user_submit_response/user_delete_response/History/Cache), `confirmation` (Workflow-Capability), `price`, `enrollink`, `certificateclass`/`certificate_conditions`, diverse Events sowie `bookingextension`-Subplugins.

## Methoden

### `col_checkbox(stdClass $values): string` — public
- **Zweck:** Rendert eine Auswahl-Checkbox pro Zeile (mit userid als Value). Bei Download leer.
- **Rueckgabe:** HTML-String. **Seiteneffekte:** keine (liest is_downloading). **Aufrufkette:** vom wunderbyte_table-Renderer pro Zeile.
- **Bewertung:** B — inline-HTML, aber kurz und klar.

### `col_dragable(stdClass $values): string` — public
- **Zweck:** Rendert das Drag-Handle-Icon (Mustache `local_wunderbyte/col_sortableitem`).
- **Seiteneffekte:** liest `$OUTPUT`. **Bewertung:** A.

### `col_timemodified / col_coursestarttime / col_completeddate(stdClass): string` — public
- **Zweck:** Formatieren je ein Timestamp-Feld als `d.m.Y`, leer wenn unbesetzt.
- **Bewertung:** A — triviale Date-Formatter.

### `col_timebooked(stdClass $values): string` — public
- **Zweck:** Soll das Buchungsdatum formatieren.
- **Bewertung:** D — **Bug:** prueft `$values->timebooked` auf leer, formatiert dann aber `$values->timemodified` statt `timebooked` (manageusers_table.php:129). Liefert falsches Datum.

### `col_bookingstatus(stdClass $values): string` — public
- **Zweck:** Uebersetzt `$values->waitinglist` (STATUSPARAM-Konstante) in ein lokalisiertes Status-Label via switch.
- **Rueckgabe:** get_string-Label. **Bewertung:** B — langer switch, aber flach und lesbar; default nutzt inkonsistente Komponente `'booking'` statt `'mod_booking'` (manageusers_table.php:168).

### `col_titleprefix(stdClass $values): string` — public
- **Zweck:** Gibt `titleprefix` zurueck oder ''. **Bewertung:** A (trivial).

### `col_text(stdClass $values): string` — public
- **Zweck:** Rendert die Optionsspalte. Bei Download Rohtext; sonst via `bookingstracker_helper`, wobei im Scope `optionstoconfirm` der Reportlink durch den Optionsview-Link ersetzt wird.
- **Seiteneffekte:** instanziiert Helper. **Aufrufkette:** ruft `bookingstracker_helper::render_col_text`. **Bewertung:** B.

### `col_name(stdClass $values): string` — public
- **Zweck:** Rendert Vor-/Nachname mit Profil-Link via Mustache `mod_booking/booked_user`.
- **Bewertung:** B — `status` ist hier hartkodiert auf String 'waitinglist' (manageusers_table.php:219), unabhaengig vom tatsaechlichen Status.

### `col_status(stdClass $values): string` — public
- **Zweck:** Mappt den Praesenzstatus auf sein Label via `booking::get_array_of_possible_presence_statuses()`, Default UNKNOWN.
- **Seiteneffekte:** statischer Call. **Bewertung:** A.

### `col_presencecount(stdClass $values): string` — public
- **Zweck:** Zeigt Praesenzzaehler im Format `<b>x</b>/y`. Bei Scope `option` gegen Anzahl Sessions, sonst gegen (gebuchte User × Sessions).
- **Seiteneffekte:** liest Settings/Answers via singleton_service. **Bewertung:** B — gemischte Berechnung + inline-HTML, aber nachvollziehbar.

### `col_answerscount(stdClass $values): string` — public
- **Zweck:** Zeigt Buchungszaehler `<b>x</b>/max` abhaengig von waitinglist (0 → maxanswers, 1 → maxoverbooking).
- **Seiteneffekte:** Settings via singleton_service. **Bewertung:** B.

### `action_reorderrows(int $id, string $data): array` — public
- **Zweck:** Drag&Drop-Umsortierung: dekodiert `ids`, laedt Rawdata neu (setup + query_db_cached) und schreibt fortlaufend steigende `timemodified` je booking_answer.
- **Seiteneffekte:** **DB-Write** `booking_answers` (update_record je id); `booking_option::purge_cache_for_answers`. **Rueckgabe:** success/message. **Bewertung:** C — re-`setup()`+`query_db_cached` innerhalb einer Action ist ungewoehnlich; Variable `$id` der Signatur wird in der foreach-Schleife ueberschrieben (Shadowing, manageusers_table.php:317); reset() nach Loop fuer optionid.

### `action_confirmbooking(int $id, string $data): array` — public
- **Zweck:** Bestaetigt eine Wartelisten-Buchung. Prueft Confirm-Capability via `confirmation`, ermittelt erforderliche Confirmation-Anzahl, schreibt History, holt Userpreis und bucht entweder direkt (bei Preis & ohne Warteliste-Abschaltung & enrollink-Bedingungen) oder setzt je nach Confirmation-Stand/Autoenrol den Submit-Status; triggert `bookinganswer_confirmed`.
- **Parameter:** `$data` JSON mit answer-id. **Seiteneffekte:** **DB-Read** booking_answers; `booking_option::booking_history_insert` (DB-Write history); `price::get_price`; `option->user_submit_response` (Buchung/Enrolment/Cache); Event `bookinganswer_confirmed`. **Aufrufkette:** AJAX vom confirmbooking-Button (col_action_confirm_delete).
- **Bewertung:** D — 107 LOC (manageusers_table.php:347-454), tief verschachtelte Status-Logik mit Mehrfachbedingungen inkl. Zuweisung-in-Bedingung `$erwaitinglist = ...` (manageusers_table.php:401), gemischte Verantwortung (Capability + Preis + History + Status-FSM + Event); schwer testbar.

### `action_unconfirmbooking(int $id, string $data): array` — public
- **Zweck:** Hebt eine Bestaetigung auf. Iteriert ueber bookingextension-Subplugins zur Capability-Pruefung, setzt dann Submit-Status 3.
- **Seiteneffekte:** DB-Read booking_answers; `core_plugin_manager`-Iteration + dynamischer `class_exists`/Static-Call; `user_submit_response`. **Bewertung:** C — Magic-Number-Status `3` (manageusers_table.php:502); duplizierte Capability-Schleife (siehe Notes); bei keiner passenden Extension bleibt `$returnmessage` undefiniert.

### `action_deletebooking(int $id, string $data): array` — public
- **Zweck:** Loescht eine Buchungsantwort; behandelt RESERVED gesondert (syncronly=true).
- **Seiteneffekte:** Capability-Schleife (Extensions); DB-Read `record_exists` booking_answers; `option->user_delete_response`. **Bewertung:** C — duplizierte Capability-Schleife; undefinierte `$returnmessage` im Fehlfall.

### `action_denybooking(int $id, string $data): array` — public
- **Zweck:** Lehnt eine Buchung ab: identische Loeschlogik wie deletebooking plus `bookinganswer_denied`-Event.
- **Seiteneffekte:** Capability-Schleife; DB-Read; `user_delete_response`; Event `bookinganswer_denied`. **Bewertung:** C — fast vollstaendige Duplikation von action_deletebooking (manageusers_table.php:576-640) zuzueglich Event.

### `action_delete_checked_booking_answers(int $id, string $data): array` — public
- **Zweck:** Bulk-Loeschen ausgewaehlter Antworten (`checkedids`); pro Antwort Capability `mod/booking:bookforothers` pruefen, RESERVED gesondert behandeln; wirft bei unbekannter id.
- **Seiteneffekte:** DB-Read booking_answers; `context_module`; `has_capability`; `user_delete_response`; wirft moodle_exception. **Bewertung:** B — fokussiert, aber pro Iteration singleton-/Capability-Lookups.

### `action_trigger_certificate_booking_answers(int $id, string $data): array` — public
- **Zweck:** Stoesst Zertifikatsausstellung fuer ausgewaehlte Antworten an. Frueh-Return wenn tool_certificate fehlt/aus. Pro Answer: synthetisches `bookingoption_completed`-Event fuer condition-basierte Zertifikate (`certificate_conditions`) plus Legacy-Pfad ueber option-level `certificate`-Json und Praesenz/Completion-Bedingung (`certificateclass::issue_certificate`).
- **Seiteneffekte:** DB-Read booking_answers; `get_config`; Event-Erzeugung (synthetisch, ohne trigger()); `certificate_conditions::evaluate_...`; `certificateclass::issue_certificate`; wirft moodle_exception. **Bewertung:** C — 78 LOC, zwei parallele Zertifikatspfade (condition vs. legacy) mit komplexer Doppel-Negations-Bedingung (manageusers_table.php:750-754); inkonsistente Komponente `'booking'` in get_string (manageusers_table.php:770).

### `col_action_confirm_delete($values): bool|string` — public
- **Zweck:** Baut das Aktions-Button-Array fuer eine Zeile (Unconfirm / Confirm / Deny / Delete) abhaengig von JSON-Flags, Confirm-Capability, erforderlichen Bestaetigungen und Loesch-Capability; rendert via Mustache.
- **Seiteneffekte:** Settings/Answers via singleton_service; `confirmation::check_confirm_capability` + `get_required_confirmation_count`; `has_capability`; `table::transform_actionbuttons_array`; Render. **Bewertung:** D — 161 LOC (manageusers_table.php:786-946), groesste Methode der Datei; massive wiederholte Button-Array-Literale, mehrere Verzweigungspfade, gemischte Berechtigungs- und Praesentationslogik; nutzt vor erster Zuweisung `$data[]` ohne Init.

### `col_action_delete($values): bool|string` — public
- **Zweck:** Rendert einen einzelnen Delete-Button, nur bei Capability `mod/booking:deleteresponses`.
- **Seiteneffekte:** Settings; has_capability; transform + Render. **Bewertung:** B — Button-Literal dupliziert das Delete-Item aus col_action_confirm_delete.

### `col_actions($values): bool|string` — public
- **Zweck:** Rendert Aktionsbuttons fuer Praesenzstatus (modal_change_status) und Notizen (modal_change_notes) je Optionsdatum.
- **Seiteneffekte:** Settings; transform + Render. **Bewertung:** B — lange Array-Literale, aber gradlinig.

### `other_cols($colname, $values): string` — public
- **Zweck:** Fallback-Renderer fuer Spalten ohne dedizierte `col_*`-Methode; liefert bei konfigurierten Customfields den Template-Wert, sonst den Rohwert.
- **Seiteneffekte:** Settings via singleton_service. **Bewertung:** B.

## Triviale Akzessoren
`col_titleprefix`, `col_timemodified`, `col_coursestarttime`, `col_completeddate` sind triviale Wert-/Datums-Formatter (Score A, oben einzeln gelistet wegen Subsystem-Relevanz).
