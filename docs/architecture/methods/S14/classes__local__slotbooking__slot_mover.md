# slot_mover — Methoden-Doku

**Datei:** `classes/local/slotbooking/slot_mover.php` · **LOC:** 916 · **Subsystem:** S14 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
Statische Service-Klasse (kein State, alle Methoden `static`), die das Verschieben/Freigeben gebuchter Slots einer `booking_answers`-Zeile kapselt — geteilte Implementierung fuer Move-Slot-Page, -Webservice und -Modal sowie fuer Self-Service-Rebooking. Owns Validierung, Persistenz (JSON in `booking_answers`), Cache-Purge, Events (`bookinganswer_slotmoved`/`_slotcancelled`) und Benachrichtigungen. Kollaborateure: `slot_answer`, `slot_availability`, `slot_dto`, `slot_price`, `slot_change_policy`, `slot_move_store`, `multiplebookings`, `singleton_service`, `booking_option`, `dates_handler`.

## Methoden

### `get_move_context(int $optionid, int $baid): array` — public static
- **Zweck:** Laedt Move-Kontext (Answer, aktuelle Slots+Keys, geforderte Slotanzahl, waehlbare Zielslots).
- **Parameter/Rueckgabe:** optionid, baid → Array mit answer/currentslots/currentslotkeys/requiredslotcount/targetslots.
- **Seiteneffekte:** DB-Read `booking_answers` (MUST_EXIST). Wirft `invaliddata` bei leeren Slots.
- **Aufrufkette:** von `move_validated`, `resolve_self_target_slots`, sowie Pages/WS (extern). Ruft `extract_current_slots`, `available_target_slots`.
- **Bewertung:** A — klar, fokussiert.

### `extract_current_slots(stdClass $answer): array` — private static
- **Zweck:** Liest aktuelle Slot-Ranges aus dem Answer-JSON (validiert start<end).
- **Seiteneffekte:** keine (delegiert an `slot_answer::get_slot_data`).
- **Aufrufkette:** von get_move_context, release_self, last_booked_slot_end, guard_given_up_slots_actionable.
- **Bewertung:** A.

### `available_target_slots(int $optionid, int $userid, array $currentslots, array $currentslotkeys): array` — public static
- **Zweck:** Baut Liste waehlbarer Zielslots (offene Slots im Gueltigkeitsbereich + aktuelle Slots) mit Labels/Preisen, sortiert.
- **Seiteneffekte:** indirekt DB/Cache via `slot_availability::get_slots_with_status` und `slot_dto::price_data`.
- **Aufrufkette:** von get_move_context und Pages/Modal (extern). Ruft `target_slot_entry`.
- **Bewertung:** B — zwei Schleifen + Dedupe-Set, aber lesbar (~37 LOC).

### `target_slot_entry(int $optionid, int $userid, int $start, int $end, string $key): array` — private static
- **Zweck:** Erstellt einen beschrifteten Zielslot-Eintrag (Tag/Zeit-Label, Preisdaten).
- **Seiteneffekte:** `slot_dto::price_data` (Preis-Resolution; ggf. DB).
- **Aufrufkette:** von available_target_slots.
- **Bewertung:** A — reiner Mapper.

### `move(int $optionid, int $baid, array $selectedslotkeys, string $reason = ''): array` — public static
- **Zweck:** Manager-Move: Capability-Gate (`moveslots`/`updatebooking`), delegiert an Core.
- **Seiteneffekte:** `has_capability`/`require_capability`. Indirekt alle Effekte von move_validated.
- **Aufrufkette:** von Move-Slot-Page/WS. Ruft `move_validated(...,'manager')`.
- **Bewertung:** A — duenner Authz-Wrapper.

### `move_self(int $optionid, int $baid, array $selectedslotkeys, string $reason = ''): array` — public static
- **Zweck:** Self-Service-Move: Cap `moveslotsself`, Ownership, Booked-State, Opt-in/Deadline, Given-up-Guard, dann Core.
- **Seiteneffekte:** DB-Read `booking_answers`, Globals `$USER`. Wirft `slot_rebook_not_allowed`. Indirekt move_validated-Effekte.
- **Aufrufkette:** von Self-Rebooking-WS. Ruft self_rebooking_allowed, guard_given_up_slots_actionable, move_validated(...,'self').
- **Bewertung:** B — Gate-Kaskade, aber klar; Validierungsblock teilweise dupliziert mit resolve_self_target_slots/release_self.

### `resolve_self_target_slots(int $optionid, int $baid, array $selectedslotkeys): array` — public static
- **Zweck:** Validiert Self-Move + resolved Zielslots OHNE Commit (fuer Preis-Delta/Pending-Upgrade vor Zahlung).
- **Seiteneffekte:** DB-Read `booking_answers`, Globals `$USER`, `slot_availability::is_slot_available`. Wirft `slot_move_select`/`slot_rebook_not_allowed`.
- **Aufrufkette:** von `slot_update_service`. Ruft self_rebooking_allowed, guard_given_up_slots_actionable, get_move_context.
- **Bewertung:** C — ~69 LOC; Gate-Block (266-282) und Slot-Resolve-Schleife (294-323) sind nahezu identische Duplikate von move_self/move_validated. Smell: Duplikat `slot_mover.php:294`.

### `commit_pending_move(int $moveid): array` — public static
- **Zweck:** Committet gehaltenen Move beim Checkout (Upgrade-Pfad), ohne Gate-Recheck; replayt gespeicherte Zielslots.
- **Seiteneffekte:** `slot_move_store::get/decode_slots/commit` (DB). Wirft `slot_move_notpending`.
- **Aufrufkette:** von Checkout/Payment-Flow. Ruft move_validated(...,'self',$moveid).
- **Bewertung:** A — kompakt, delegiert.

### `release_self(int $optionid, int $baid, array $releaseslotkeys, string $reason = ''): array` — public static
- **Zweck:** Self-Service-Teil-Stornierung: gibt einzelne aktionierbare Slots frei; bei Vollfreigabe Standard-Loeschpfad, sonst Persistenz Restslots + slotcancelled-Event.
- **Parameter/Rueckgabe:** → {released, remaining, cancelled}.
- **Seiteneffekte:** DB-Read+Write `booking_answers` (`update_record`), `booking_option::purge_cache_for_answers`, `singleton_service::destroy_answers_for_user`, Event `bookinganswer_slotcancelled`, `booking_option::user_delete_response` (Voll-Cancel), Globals `$USER`. Mehrere Throws.
- **Aufrufkette:** von Release-WS/Modal. Ruft self_rebooking_allowed, extract_current_slots, slot_change_policy::*, singleton_service.
- **Bewertung:** D — ~94 LOC; gemischte Verantwortung (Gate + Diff-Berechnung + JSON-Surgery + Cache + Event), JSON-decode/encode-Hantierung dupliziert mit move_validated (446-450 vs 770-773). Smell: gemischte Verantwortung/Duplikat `slot_mover.php:380`.

### `self_rebooking_allowed(int $optionid, stdClass $answer): bool` — public static
- **Zweck:** Single Source of Truth ob Self-Rebooking moeglich (Opt-in + Booked + aktionierbarer Slot).
- **Seiteneffekte:** DB via get_slot_config, `slot_change_policy::answer_has_actionable_slot`.
- **Aufrufkette:** von move_self, resolve_self_target_slots, release_self, get_self_rebookable_answer, UI-Gating.
- **Bewertung:** A.

### `get_self_rebookable_answer(int $optionid, int $userid): ?\stdClass` — public static
- **Zweck:** Liefert eigene BOOKED-Answer nur wenn Self-Rebooking erlaubt (UI-Gating).
- **Seiteneffekte:** DB-Read `booking_answers` (IGNORE_MULTIPLE).
- **Aufrufkette:** UI (alreadybooked step-back, slotmove condition). Ruft self_rebooking_allowed.
- **Bewertung:** A.

### `book_again_active(int $optionid, \stdClass $answer): bool` — public static
- **Zweck:** Duenner Wrapper auf `multiplebookings::book_again_due`.
- **Seiteneffekte:** delegiert.
- **Bewertung:** A.

### `last_booked_slot_end(stdClass $answer): int` — public static
- **Zweck:** Max-Endzeitstempel der gebuchten Slots (0 wenn keiner).
- **Seiteneffekte:** keine. Ruft extract_current_slots.
- **Bewertung:** A.

### `guard_given_up_slots_actionable(stdClass $answer, array $selectedslotkeys): void` — private static
- **Zweck:** Stellt sicher, dass jeder aufgegebene (nicht reselectierte) Slot noch aktionierbar ist.
- **Seiteneffekte:** keine DB-Writes; `slot_change_policy::*`. Wirft `slot_rebook_slot_started`.
- **Aufrufkette:** von move_self, resolve_self_target_slots.
- **Bewertung:** A.

### `get_slot_config(int $optionid): ?stdClass` — private static
- **Zweck:** Laedt `booking_slot_config`-Zeile.
- **Seiteneffekte:** DB-Read `booking_slot_config` (IGNORE_MISSING).
- **Bewertung:** A.

### `move_validated(int $optionid, int $baid, array $selectedslotkeys, string $reason, string $initiatedby, int $excludemoveid = 0): array` — private static
- **Zweck:** Geteilter Move-Kern: validiert Selektion, baut neue Slot-Payload (inkl. Move-History, Teacher-Per-Slot-Remap, Preis-Recalc), persistiert JSON, purged Cache, feuert Move-/Cancel-Events, triggert Notify.
- **Parameter/Rueckgabe:** initiatedby 'manager'/'self', excludemoveid fuer Pending-Hold-Ausschluss → {newstart, newend, slotcount}.
- **Seiteneffekte:** DB-Read+Write `booking_answers` (`update_record`), `slot_availability::is_slot_available`, `slot_price::calculate_slot_price_data`, `booking_option::purge_cache_for_answers`, `singleton_service::destroy_answers_for_user`, Events `bookinganswer_slotmoved` + `bookinganswer_slotcancelled`, Notify (E-Mails).
- **Aufrufkette:** von move, move_self, commit_pending_move. Ruft get_move_context, slot_availability, slot_price, Events, notify.
- **Bewertung:** E — ~220 LOC God-Method mit vielen vermischten Verantwortlichkeiten (Validierung, Slot-Resolve, History, Teacher-Remap, Preis-Aggregation, JSON-Surgery, Cache, 2 Events, Notify-Dispatch); manuelle JSON-decode/encode (770-773) dupliziert release_self; Slot-Resolve-Schleife (656-683) dupliziert resolve_self_target_slots. Smell: God-Method/gemischte Verantwortung `slot_mover.php:629`.

### `notify(stdClass $answer, array $slotdata, int $oldstart, int $oldend, int $newstart, int $newend, string $reason, string $initiatedby): void` — private static
- **Zweck:** Benachrichtigt Student + zugewiesene Teacher ueber den Move (self vs manager Strings).
- **Seiteneffekte:** DB-Read `user` (MUST_EXIST) + `get_records_list('user',...)`, `email_to_user` (externe Mail), `dates_handler::prettify_*`, `core_user::get_noreply_user`.
- **Aufrufkette:** von move_validated. 
- **Bewertung:** C — ~52 LOC; gemischte Verantwortung (String-Auswahl + Mailversand an mehrere Empfaenger), 8 Parameter (langes Param-Listing). Smell: lange Parameterliste `slot_mover.php:863`.

## Triviale Akzessoren
Keine (klassenweit nur statische Service-Methoden, kein Konstruktor/Getter/Setter).
