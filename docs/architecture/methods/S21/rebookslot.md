# rebookslot — Methoden-Doku
**Datei:** `rebookslot.php` · **LOC:** 86 · **Subsystem:** S21 · **Klassen-Score:** B / -
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Self-Service-Einstiegspunkt (keine Klasse) fuer das Umbuchen/Aendern eines eigenen gebuchten Slots. Spiegelt `moveslot.php`, ist aber durch Eigentuemerschaft (`answer.userid === USER->id`) plus die Opt-in-Pruefung `slot_mover::self_rebooking_allowed()` und die Capability `mod/booking:moveslotsself` abgesichert. Die Seite ist nur der Host: sie rendert das geteilte „Update booking"-DynamicForm (`slotUpdate`-AMD, `slotupdate_form` + `slot_update_service` mit Actor `self`), das Validierung, Routing, Persistenz und Events besitzt; die Persistenz selbst laeuft ueber `slot_mover::move_self()`. Kollaborateure: `slot_mover` (Domaenenlogik), `$DB` (Lesen des `booking_answers`-Records), `$PAGE`/`$OUTPUT`, AMD `mod_booking/condition/slotUpdate`.

## Request-/Permission-Flow
1. `require_once config.php`. Pflicht-Parameter `id` (cmid), `optionid`, `baid` (booking_answers-ID) — alle `PARAM_INT`.
2. `get_course_and_cm_from_cmid($id, 'booking')` + `require_course_login`.
3. `context_module::instance($cm->id)` + `require_capability('mod/booking:moveslotsself', $context)`.
4. `$DB->get_record('booking_answers', ['id' => $baid, 'optionid' => $optionid], '*', MUST_EXIST)` — laedt den Antwort-Record (Existenz erzwungen).
5. **Ownership-Gate:** Wirft `moodle_exception('slot_rebook_not_allowed')`, wenn `(int)$answer->userid !== (int)$USER->id` ODER `!slot_mover::self_rebooking_allowed($optionid, $answer)`. Das bindet das Umbuchen strikt an den eigenen Datensatz und die Option-Opt-in-Regel.
6. Setzt `$PAGE`-URL/Title/Heading/Context.
7. `$PAGE->requires->js_call_amd('mod_booking/condition/slotUpdate', 'init', [$containerid])` — startet das clientseitige Update-Form.
8. Ausgabe: Header, Heading, ein Container-`<div>` mit `data-optionid`/`data-userid`/`data-baid`/`data-selfservice=1`/`data-returnurl`, ein Formular-Region-`<div>` und ein Submit-Button; Footer.

## Bewertung
- **Seiteneffekte:** Ein DB-Lesezugriff; HTML-Ausgabe; JS-AMD-Registrierung. Keine direkte Mutation im PHP-Pfad — die eigentliche Umbuchung erfolgt asynchron ueber den DynamicForm-/Service-Pfad.
- **Bewertung:** B — sauberes, defensiv gegatetes Self-Service-Skript (Capability + Ownership + Opt-in dreifach). Klein: die `data-returnurl` wird per `$returnurl->out(false)` ausgegeben (unescaped roher URL-Text im Attribut, aber aus `moodle_url` konstruiert und nicht aus User-Input → unkritisch). Logik ist bewusst duenn, weil Validierung/Persistenz im Service liegen.

## Bewertungs-Resümee
Gut abgesicherter Host fuer das geteilte Slot-Update-Form mit vorbildlichem dreifachem Gate (Capability, Eigentuemerschaft, `self_rebooking_allowed`). Keine funktionalen Maengel. Klassen-Score **B / -**.
