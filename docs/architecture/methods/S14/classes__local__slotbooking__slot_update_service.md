# slot_update_service — Methoden-Doku

**Datei:** `classes/local/slotbooking/slot_update_service.php` · **LOC:** 499 · **Subsystem:** S14 (Slotbooking) · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
Form-unabhaengiger Service, der ein Self-Service "Update booking" (move/cancel/change) committet und nach Netto-Preisdelta routet (price-neutral → direct, downgrade → refund als Cart-Credit, upgrade → pending move + Cart-Item `moveslot`, leere Auswahl → cancel). Reines statisches Util ohne Instanz-Zustand. Kollaborateure: `slot_mover` (Move-Kontext + Commit-Helfer), `slot_change_policy` (Deadline/Locked-Partitionierung), `slot_availability` (Slot-Verfuegbarkeit), `target_price_policy` (Delta-Berechnung), `slot_move_store` (pending move), `shopping_cart` (Cart/Refund), `singleton_service`/`booking_option_settings` (Settings/Kontext). Ruft DynamicForm-seitig (read: `plan`) und Webservice/Hostpages moveslot.php/rebookslot.php (write: `apply`).

## Methoden

### `public static plan(int $optionid, int $baid, int $userid, array $newkeys, string $actor = 'self'): array`
- **Zweck:** Dry-Run/Read-Side ohne Seiteneffekt: beschreibt, was eine neue Slot-Auswahl mit der aktuellen Buchung machen wuerde (Live-Preis, Confirmation-Summary, Validierung der Form). Routing identisch zu `apply`.
- **Parameter:** optionid, baid, userid; `$newkeys` Ziel-Slot-Keys "start:end"; `$actor` 'self' (priced + Deadline/Locked-Regeln) vs. 'manager' (capability-gated, price-agnostic).
- **Rueckgabe:** Plan-Array (currentkeys, lockedkeys, newkeys, kept, removed, added, netdelta, route, ismove, newstart, newend, slotcount, errors). Selection-Verstoesse als `errors` (lang-string-ids), kein Throw.
- **Seiteneffekte:** Keine Writes. Reads ueber `slot_mover::get_move_context` (DB-Antwort/Slots), `slot_change_policy`, `slot_availability::is_slot_available`. `has_capability`/`require_capability` (manager). `throw moodle_exception('slot_rebook_not_allowed')` bei Self-Access-Verstoss. `time()`.
- **Aufrufkette:** Gerufen von Update-DynamicForm + intern von `apply` (mixed-shrink-Validierung, Zeile 253). Ruft slot_mover/slot_change_policy/slot_availability/target_price_policy.
- **Bewertung:** **D** — 124 LOC (Z.70-194, >80), gemischte Verantwortung (Access-Guard + Locked/Deadline-Validierung + Slot-Parsing + Preis-Routing + Result-Bau), mehrere geschachtelte foreach/if-Bloecke; Routing-Logik dupliziert mit `apply` (zwei Quellen der Wahrheit fuer dieselben Regeln). Smell: slot_update_service.php:70-194.

### `private static parse_key(string $key): array{0:int,1:int}`
- **Zweck:** Parst Slot-Key "start:end" in int-Paar; [0,0] bei Malformed.
- **Seiteneffekte:** keine. **Aufrufkette:** intern (plan, apply, apply_reduction).
- **Bewertung:** **A** — klein, rein, klar.

### `public static apply(int $optionid, int $baid, int $userid, array $keys, string $reason = '', string $actor = 'self'): array`
- **Zweck:** Commit-Seite: ersetzt aktuelle Slots durch neue Auswahl und routet nach Netto-Preisdelta (reduction/full-cancel, mixed-shrink, same-count-move). Delegiert Manager an `apply_manager`.
- **Parameter:** wie plan + `$reason` Change-Reason. **Rueckgabe:** outcome-Array {mode, pricedelta, moveid, newstart, newend, slotcount}.
- **Seiteneffekte:** DB-Writes via `slot_mover::move_self`/`resolve_self_target_slots`, `slot_move_store::create_pending` (pending-move-Tabelle). Cart: `shopping_cart::add_item_to_cart('mod_booking','moveslot',...)`, `refund_to_cart`. Events indirekt ueber slot_mover (slotmoved/slotcancelled/user_delete_response). `throw moodle_exception('slot_update_no_add')` bei Wachstum. `get_string`.
- **Aufrufkette:** Gerufen von move_slot-Webservice/Hostpages. Ruft plan, apply_reduction, apply_manager, slot_mover, slot_move_store, shopping_cart, target_price_policy, outcome.
- **Bewertung:** **D** — 131 LOC (Z.223-353, >80), tiefe Verzweigung (4 Routing-Pfade), dupliziertes Slot-Build/usort (mit apply_reduction/plan), statische God-Calls auf shopping_cart/slot_mover, gemischte Verantwortung (Branching + Pricing + Cart-Orchestrierung). Smell: slot_update_service.php:223-353.

### `private static refund_to_cart(int $optionid, int $userid, float $amount, string $itemtitle): void`
- **Zweck:** Partial-Refund als Cart-Credit, defensiv (no-op falls shopping_cart fehlt/API alt oder Slot nie ueber Cart gekauft).
- **Seiteneffekte:** `shopping_cart::add_partial_refund` (Cart-Ledger-Write). `method_exists`-Guard.
- **Aufrufkette:** intern (apply, apply_reduction). **Bewertung:** **A** — fokussiert, defensiv.

### `private static apply_reduction(int $optionid, int $baid, int $userid, array $newkeys, array $ctx, string $reason, booking_option_settings $settings): array`
- **Zweck:** Reine Reduktion (Slots aufgeben, keine adds) committen via `release_self` + Netto-Refund; leere Auswahl = Full-Cancel ueber Standard-Deletion-Pfad.
- **Seiteneffekte:** `slot_mover::release_self` (DB + slotcancelled-Event), `refund_to_cart`, `get_string`. **Aufrufkette:** von apply (Z.247). Ruft target_price_policy, slot_mover, refund_to_cart, outcome.
- **Bewertung:** **C** — 47 LOC, fokussiert, aber dupliziertes newslots-Build/usort-Pattern (auch in apply/plan) und 7 Parameter (langer Signatur-Tail). Smell: slot_update_service.php:407-414 (wiederholtes Slot-Mapping).

### `private static apply_manager(int $optionid, int $baid, array $keys, string $reason): array`
- **Zweck:** Manager-Update (price-agnostic, capability-gated): move/swap/reduce ohne Payment; leere Auswahl = Full-Cancel via user_delete_response; Wachstum abgelehnt.
- **Seiteneffekte:** `has_capability`/`require_capability` (mod/booking:moveslots|updatebooking), `booking_option::user_delete_response` (DB + Event), `slot_mover::move` (DB + slotmoved/slotcancelled). `throw moodle_exception('slot_update_no_add')`. **Aufrufkette:** von apply (Z.232). Ruft singleton_service, slot_mover, outcome.
- **Bewertung:** **C** — Capability-Check-Block dupliziert identisch mit `plan` (Z.80-85 vs. 452-457). Smell: slot_update_service.php:452-457 (Cap-Guard-Duplikat).

### `private static outcome(string $mode, float $pricedelta, int $moveid, array $result): array`
- **Zweck:** Baut das Routing-Outcome-Array (mode, gerundetes pricedelta, moveid, newstart/newend/slotcount).
- **Seiteneffekte:** keine. **Aufrufkette:** intern (alle apply*-Pfade). **Bewertung:** **A** — reiner Mapper.

### Triviale Akzessoren
Keine klassischen Getter/Setter; Konstante `PRICE_EPSILON = 0.005` (price-neutral-Schwelle).
