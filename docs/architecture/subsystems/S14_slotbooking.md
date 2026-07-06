# S14 — slotbooking

## Zweck & Grenzen

Das Subsystem `mod_booking\local\slotbooking` implementiert die Zeitslot-Buchung als
eigenen Sub-Flow innerhalb von mod_booking. Eine Buchungsoption kann so konfiguriert
werden, dass sie nicht als ein fixer Termin, sondern als Menge virtueller Zeitslots
(fix, rollierend, sessionbasiert oder benutzerdefiniert) gebucht wird. Das Subsystem
besitzt die gesamte Domänenlogik dahinter:

- **Slot-Erzeugung & -Verfügbarkeit** (`slot_availability`): generiert virtuelle Slots
  aus der Slot-Konfiguration, prüft Kapazität, Lehrkraft-Constraints, Entitäts-Konflikte
  und Überlappungen.
- **Preisberechnung** (`slot_price`, `slot_rules`, `target_price_policy`, `slot_dto`).
- **Slot-Regeln** (Schliess- und Preisregeln; `slot_rules` lesend, `slot_rule_manager`
  schreibend).
- **Umbuchen / Stornieren / Ändern** ("Update booking"-Flow): `slot_mover`,
  `slot_update_service`, `slot_move_store`, `slot_change_policy` inkl. Preisdifferenz-
  Routing über `local_shopping_cart`.
- **Persistenz-Adapter** für die Slot-Daten im `booking_answers.json` (`slot_answer`).
- **Feature-Gate** (`slot_feature`) und **Event-Placeholder-Rendering**
  (`slot_event_placeholders`).

**Grenzen / nicht in diesem Scope:** Die UI-Schichten (Forms, Mustache, AMD/JS unter
`form/condition/slotbooking*`, `condition/slotbooking`, Prepages), die Webservices unter
`classes/external/*slot*` und die Events `bookinganswer_slot*` liegen ausserhalb dieses
Verzeichnisses und werden hier nur als Kollaborateure referenziert. Die eigentliche
Buchungsbestätigung (Schreiben des `booking_answers`-Datensatzes) erfolgt über die
Standard-Buchungspipeline; dieses Subsystem liefert dafür nur Daten/Validierung.

## Position im Gesamtsystem

```
UI (slotbooking_form, condition/slotbooking, Prepages, JS)
WS (external\*slot*) ───────────────────────────┐
moveslot.php / rebookslot.php Hostseiten ────────┤
                                                 ▼
            ┌──────────────── local\slotbooking ──────────────┐
            │  slot_feature (Gate)                            │
            │  slot_dto ── slot_availability ── slot_rules    │
            │      │            │                  │          │
            │  slot_price ──────┘            slot_rule_manager │
            │      │                                          │
            │  slot_update_service ── slot_mover ── slot_change_policy
            │      │         │            │                   │
            │  target_price_policy   slot_move_store          │
            │  slot_answer (JSON-Adapter)  slot_event_placeholders
            └──────────────────────────────────────────────────┘
                          │            │             │
                          ▼            ▼             ▼
          singleton_service   local_shopping_cart   local_entities
          booking_option(_settings)   booking_answers/events
```

Zentrale Fremd-Kollaborateure (ausserhalb des Scopes):
`mod_booking\singleton_service`, `mod_booking\booking_option`,
`mod_booking\booking_option_settings`, `mod_booking\price`,
`mod_booking\option\dates_handler`, `mod_booking\option\fields\multiplebookings`,
`mod_booking\utils\wb_payment`, `local_shopping_cart\shopping_cart`,
`local_entities\entities`, Events `bookinganswer_slotmoved` / `_slotcancelled`.

## Schlüsselkonzepte

- **Virtueller Slot:** Ein Paar `[start, end]` (Unix-Timestamps). Slots existieren nicht
  als DB-Zeilen, sondern werden bei Bedarf aus der Slot-Konfiguration
  (`booking_slot_config`) bzw. den Optionssessions generiert. Als Schlüssel dient
  durchgängig der String `"start:end"`.
- **Slot-Typen:** `fixed`, `rolling`, `session` (aus `booking_optiondates`),
  `userdefined` (keine generierten Slots).
- **Slot-Daten am Answer:** Die tatsächlich gebuchten Slots eines Teilnehmers liegen in
  `booking_answers.json` unter `slot` (Keys u. a. `slots`, `teachers`,
  `teachers_per_slot`, `moved_from`, `price`). `slot_answer` ist der Adapter dafür.
- **Belegung:** Kapazität wird per Overlap-Zählung über alle aktiven Answers berechnet
  (`count_bookings`), zzgl. „Holds" von schwebenden, kostenpflichtigen Umbuchungen
  (`slot_move_store`).
- **Update-Booking-Routing:** Eine geänderte Slot-Auswahl wird über ihre Preisdifferenz
  geroutet: `direct` (preisneutral), `refund` (günstiger → Gutschrift), `cart` (teurer →
  Hold + Checkout), `cancel` (leere Auswahl → Vollstorno).
- **Deadline/Locked-Slots:** Pro Slot gilt ein signierter Minuten-Offset relativ zum
  Slot-Start (`slot_change_policy`); abgelaufene Slots sind „locked" und können weder
  abgegeben noch storniert werden.
- **Feature-Gate:** Slot-Buchung erfordert PRO + Admin-Toggle (`slot_feature`).

## Datenfluss

**Anzeige (Picker / Report):**
`slot_dto::build_picker_slots` → `slot_availability::get_slots_with_status` →
`get_slots_for_range` (Generierung + `slot_rules::apply_to_slots`) →
`evaluate_slot_for_user` (Kapazität/Teacher/Entity/Overlap) → pro Slot
`slot_price::calculate_slot_price_data` (+`slot_rules::apply_price_rules_to_slot_price`).

**Umbuchung (self-service):**
`slot_update_service::plan/apply` → `slot_mover::resolve_self_target_slots` /
`move_self` / `release_self` → Validierung über `slot_availability` und
`slot_change_policy` → Preisdelta über `target_price_policy` → bei Upgrade
`slot_move_store::create_pending` + `shopping_cart::add_item_to_cart`; bei Checkout
`slot_mover::commit_pending_move` → `slot_move_store::commit`. Persistenz direkt in
`booking_answers.json`, Cache-Purge via `booking_option::purge_cache_for_answers` und
`singleton_service::destroy_answers_for_user`. Events `bookinganswer_slotmoved` /
`_slotcancelled` werden pro Änderung gefeuert; Benachrichtigung an Teilnehmer + Lehrkräfte.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| slot_availability.php | `slot_availability` | Service (Slot-Generierung & Verfügbarkeit) | 1422 | 28 | D | P1 |
| slot_mover.php | `slot_mover` | Service (Move/Cancel/Rebook-Kern) | 916 | 14 | D | P1 |
| slot_update_service.php | `slot_update_service` | Service (Preisdelta-Routing) | 499 | 7 | C | P2 |
| slot_dto.php | `slot_dto` | DTO-Builder / Renderer-Daten | 432 | 7 | C | P2 |
| slot_rules.php | `slot_rules` | Service (Slot-/Preisregeln lesend) | 451 | 11 | C | P2 |
| slot_move_store.php | `slot_move_store` | Repository (`booking_slot_moves`) | 260 | 9 | B | P3 |
| slot_rule_manager.php | `slot_rule_manager` | Repository (Regeln schreibend) | 198 | 6 | B | P3 |
| slot_price.php | `slot_price` | Service (Slot-Preis) | 158 | 3 | B | P3 |
| slot_change_policy.php | `slot_change_policy` | Policy (Deadline/Locked) | 132 | 5 | A | - |
| target_price_policy.php | `target_price_policy` | Policy (Preisdifferenz/Filter) | 127 | 3 | B | - |
| slot_answer.php | `slot_answer` | DTO/Adapter (JSON-Slotdaten) | 75 | 2 | A | - |
| slot_event_placeholders.php | `slot_event_placeholders` | Renderer (Event-Placeholder) | 75 | 1 | A | - |
| slot_feature.php | `slot_feature` | Gate (Feature-Verfügbarkeit) | 51 | 1 | A | - |

### `slot_availability` (slot_availability.php)

**Verantwortung:** Generiert virtuelle Slots aus der Slot-Konfiguration und beantwortet
sämtliche Verfügbarkeitsfragen (Kapazität, Lehrkräfte, Entitäts-Konflikt, Teilnehmer-
Überlappung). De-facto God-Service des Subsystems.
**Persistenz:** liest `booking_answers`, `booking_teacher_unavailability`,
`booking_slot_student_teacher`, `booking_options`; per-Request-Statik-Cache
`$bookedslotrangecache`. **Kollaborateure:** `singleton_service`, `slot_answer`,
`slot_rules`, `slot_move_store`, `local_entities\entities`, lib.php-Konstanten.

Methoden-Inventar:
- `public static get_teachers_required(int): int` — konfigurierte Pflichtlehrer pro Slot.
- `public static clear_request_cache(int=0): void` — Request-Cache (selektiv) leeren.
- `public static get_available_teachers_for_slot(int,int,int): array` — freie Lehrkräfte (DTO inkl. Initialen) für ein Slotfenster.
- `public static count_bookings(int,int,int,int=0,int=0): int` — Anzahl belegender Answers + aktive Holds im Fenster.
- `private static get_booked_slot_ranges_by_answer(int): array` — gebuchte Ranges je Answer (gecacht).
- `public static get_booked_slot_ranges_for_option(int): array` — alle gebuchten Ranges flach (für Entity-Occupancy-Provider).
- `public static extract_booked_ranges_from_answer(object): array` — kanonische gebuchte Ranges aus Answer-JSON.
- `private static get_booked_slot_key_set_for_user(int,int): array` — Slot-Keys eines Users.
- `public static is_slot_available(int,int,int,int=0,array=[],int=0,int=0): bool` — Dünner Wrapper über `evaluate_slot_for_user`.
- `public static evaluate_slot_for_user(...): array` — **Zentraler Evaluator** (Kapazität/Teacher/Entity/Overlap, Status + Warn-/Fehlermeldung). ~128 LOC.
- `public static has_entity_conflict_for_slot(int,int,int,bool=false): bool` — Entitäts-Belegung via `local_entities` (live optional).
- `public static is_within_slot_openings(int,int,int): bool` — prüft Custom-Slot gegen Öffnungs-/Gültigkeitsfenster.
- `public static get_slots_for_range(int,int,int): array` — generiert Slots (fixed/rolling/session) + `slot_rules`.
- `private static get_session_slots_for_range(int,int,int): array` — Slots aus Optionssessions.
- `public static get_slots_with_status(int,int=0): array` — Slots des Default-Ranges mit Status.
- `public static get_booked_ranges_for_day(int,int,int,int=0): array` — gebuchte Ranges im Tagesfenster.
- `public static get_slots_with_status_for_range(int,int,int,int=0): array` — Slots + Status/Bookings/Capacity für Range.
- `private static get_default_slot_range(int): array` — Default-Range (Session-spezifisch oder +365 Tage).
- `private static get_slot_config(int): ?stdClass` — Slot-Config aus Settings.
- `private static time_to_seconds(string): int` / `parse_days_of_week(string): int[]` — HH:MM- bzw. Tages-CSV-Parser.
- `private static has_teacher_capacity(stdClass,int,int,int=0): bool` — erfüllt `teachers_required`?
- `private static get_available_teacher_ids(stdClass,int,int,int=0): int[]` — freie Lehrkräfte (Pool minus Unavailability/Busy).
- `private static extract_teacher_pool_ids(stdClass): int[]` — Lehrer-Pool aus Config-JSON.
- `private static get_assigned_teacher_ids_for_user(int,int): int[]` — zugewiesene Lehrkräfte je User.
- `private static get_busy_teacher_ids(array,int,int,int=0): int[]` — über andere Answers belegte Lehrkräfte.
- `private static get_student_overlap_handling(int): int` — Overlap-Handling aus `availability`-JSON.
- `private static get_overlapping_option_ids_for_user_slot(...): int[]` — überlappende Optionen des Users.
- `private static get_option_names(int[]): string[]` — Optionsnamen für Meldungen.
- `private static get_inactive_booking_states(): int[]` / `is_inactive_booking_state(int): bool` — Status-Filter.
- `private static slots_overlap(int,int,int,int): bool` / `to_initials(string,string): string` — Hilfen.
- `public static reset_caches(): void` — Test-Teardown.

### `slot_mover` (slot_mover.php)

**Verantwortung:** Einziger Move-Kern für das Verschieben/Stornieren gebuchter Slots,
geteilt von Manager-Seite, Self-Service und Checkout-Commit. Owns Validierung,
Persistenz (Answer-JSON), Events und Notifications.
**Persistenz:** liest/schreibt `booking_answers`, `booking_slot_config`; Cache-Purge via
`booking_option` + `singleton_service`. **Kollaborateure:** `slot_availability`,
`slot_change_policy`, `slot_move_store`, `slot_price`, `slot_dto`, `target_price_policy`
(indirekt), `multiplebookings`, `dates_handler`, Events.

Methoden-Inventar:
- `public static get_move_context(int,int): array` — Answer + aktuelle/Ziel-Slots + Pflichtanzahl.
- `private static extract_current_slots(stdClass): array` — aktuelle Slots aus Answer-JSON.
- `public static available_target_slots(int,int,array,array): array` — wählbare Ziel-Slots (offen + aktuelle) für Kalender.
- `private static target_slot_entry(int,int,int,int,string): array` — beschriftetes Ziel-Slot-DTO inkl. Preis.
- `public static move(int,int,array,string=''): array` — Manager-Move (Capability-Gate) → `move_validated('manager')`.
- `public static move_self(int,int,array,string=''): array` — Self-Move (Ownership/opt-in/Deadline) → `move_validated('self')`.
- `public static resolve_self_target_slots(int,int,array): array` — validiert Ziel-Slots OHNE Commit (für Preisdelta/Hold).
- `public static commit_pending_move(int): array` — gehaltene Upgrade-Umbuchung bei Checkout committen.
- `public static release_self(int,int,array,string=''): array` — partielle/volle Self-Stornierung gebuchter Slots.
- `public static self_rebooking_allowed(int,stdClass): bool` — Single Source of Truth für UI/WS-Gate.
- `public static get_self_rebookable_answer(int,int): ?stdClass` — eigene buchbare Answer (oder null).
- `public static book_again_active(int,stdClass): bool` — Wrapper über `multiplebookings::book_again_due`.
- `public static last_booked_slot_end(stdClass): int` — Ende des letzten gebuchten Slots.
- `private static guard_given_up_slots_actionable(stdClass,array): void` — aufgegebene Slots müssen actionable sein.
- `private static get_slot_config(int): ?stdClass` — Config-Zeile (direkt aus DB).
- `private static move_validated(int,int,array,string,string,int=0): array` — **Shared Core**: validiert, persistiert, History/Teacher/Preis-Update, feuert Events, notify. ~220 LOC.
- `private static notify(stdClass,array,int,int,int,int,string,string): void` — Mail an Teilnehmer + Lehrkräfte.

### `slot_update_service` (slot_update_service.php)

**Verantwortung:** Form-unabhängige Commit-/Routing-Engine des „Update booking"-Flows.
Berechnet das Netto-Preisdelta und routet (direct/refund/cart/cancel). `plan()` ist die
seitenwirkungsfreie Leseseite (DynamicForm), `apply()` die Schreibseite.
**Persistenz:** indirekt über `slot_mover`/`slot_move_store`; nutzt
`local_shopping_cart\shopping_cart`. **Kollaborateure:** `slot_mover`,
`slot_change_policy`, `slot_availability`, `target_price_policy`, `slot_move_store`,
`singleton_service`, `shopping_cart`.

Methoden-Inventar:
- `public static plan(int,int,int,array,string='self'): array` — Dry-Run-Plan (kept/removed/added, netdelta, route, errors). Access-Gate self/manager.
- `private static parse_key(string): array` — `"start:end"` → `[int,int]`.
- `public static apply(int,int,int,array,string='',string='self'): array` — Commit + Routing (reduction/mixed/move). ~130 LOC.
- `private static refund_to_cart(int,int,float,string): void` — Teil-Refund als Gutschrift (no-op ohne Cart-API).
- `private static apply_reduction(...): array` — reine Reduktion via `release_self` + ggf. Refund.
- `private static apply_manager(int,int,array,string): array` — preis-agnostischer Manager-Update.
- `private static outcome(string,float,int,array): array` — Outcome-Array-Builder.

### `slot_dto` (slot_dto.php)

**Verantwortung:** Kanonischer Builder der Slot-Datenstrukturen für Picker, Report und
Move-Flow; bündelt Label-/Preis-Formatierung an einer Stelle.
**Kollaborateure:** `slot_availability`, `slot_price`, `slot_answer`,
`singleton_service`, `moodle_url`; `build_report_slots` liest `booking_answers` direkt.

Methoden-Inventar:
- `public static day_label(int): string` / `time_range_label(int,int): string` — lokalisierte Labels.
- `public static price_data(int,int,int,int=0): array` — formatierte Preisdaten je Slot.
- `public static build_picker_slots(int,int): array` — Picker-DTOs (offen/warn/gebucht) inkl. Teacher/Preis.
- `public static build_meta(int,int): array` — Picker-Konfig-Meta (maxselection, viewmode, prices, tz).
- `public static build_report_slots(int,int): array` — Report: Slots + Detail-Map (Studierende, Lehrkräfte, Belegung, Move-URL). ~180 LOC, eigene SQL.
- `private static resolve_teachers_per_slot(array): array` / `clean_ids(array): int[]` — Teacher-Auflösung/Normalisierung.

### `slot_rules` (slot_rules.php)

**Verantwortung:** Liest Slot-Regeln (Schliessregeln + Preisregeln) aus DB/MUC/Request-
Cache und wendet sie auf generierte Slots bzw. Slot-Basispreise an.
**Persistenz:** `booking_slot_rule`, `booking_slot_rule_price`; MUC-Caches
`slotrulesbyoption`, `slotrulepricesbyoption`; Request-Statiks. **Kollaborateure:** —
(von `slot_availability` und `slot_price` konsumiert).

Methoden-Inventar:
- `public static get_rules_for_option(int): array` — Regeln (Request→MUC→DB, „true"=leer-Marker).
- `public static apply_to_slots(int,array): array` — entfernt durch `closed`-Regeln blockierte Slots.
- `public static invalidate_option_cache(int): void` — Request- + MUC-Cache invalidieren.
- `public static apply_price_rules_to_slot_price(int,int,int,float,string=''): float` — Preisregeln (absolute/delta/factor) anwenden.
- `private static is_slot_allowed_by_rules(array,int,int): bool` — Schliessregel-Prüfung.
- `private static get_price_rules_for_option(int): array` — Preisregeln (Join Rule+Price, gecacht).
- `private static price_rule_matches_category(string,string): bool` — Kategorie-Match (CSV/`default`).
- `private static rule_matches_slot(stdClass,int,int): bool` — Zeit-/Tages-/Bereichs-Match.
- `private static parse_weekdays(string): int[]` / `time_to_seconds(string): ?int` — Parser.
- `private static rule_tables_available(): bool` — Schema-Guard (Tabellen vorhanden?).
- `public static reset_caches(): void` — Test-Teardown.

### `slot_move_store` (slot_move_store.php)

**Verantwortung:** Repository/State-Maschine für preis-differente Umbuchungen
(`booking_slot_moves`): PENDING-Hold während Checkout, COMMITTED nach Kauf, CANCELLED bei
Abbruch/Ablauf. **Persistenz:** `booking_slot_moves`. **Kollaborateure:** — (von
`slot_availability`, `slot_mover`, `slot_update_service` und den Cart-Callbacks genutzt).

Methoden-Inventar:
- Konstanten `STATUS_PENDING/COMMITTED/CANCELLED`.
- `public static create_pending(int,int,int,array,array,float,int): int` — Hold-Zeile anlegen.
- `public static get(int): ?stdClass` — Zeile per id.
- `public static get_pending_for_answer(int,?int=null): ?stdClass` — offener Hold je Answer.
- `public static get_pending_for_option_user(int,int,?int=null): ?stdClass` — offener Hold je Option/User (Cart-Callbacks).
- `public static get_active_holds_for_option(int,?int=null): array` — Zielslots aller aktiven Holds (Kapazitätszähler).
- `public static commit(int,?int=null): void` / `cancel(int): void` — Status setzen.
- `public static purge_expired(?int=null): void` — abgelaufene Holds als CANCELLED (Safety-Net).
- `public static decode_slots(?string): array` — Slot-JSON normalisieren.

### `slot_rule_manager` (slot_rule_manager.php)

**Verantwortung:** Schreibseite der Slot-Regeln (CRUD für `booking_slot_rule` /
`booking_slot_rule_price`) inkl. konsistenter Cache-Invalidierung.
**Persistenz:** `booking_slot_rule`, `booking_slot_rule_price`. **Kollaborateure:**
`slot_rules`, `singleton_service`, `cache_helper`.

Methoden-Inventar:
- Konstanten Ruletype/Pricemode.
- `public static save_rule(stdClass): int` — Regel anlegen/aktualisieren.
- `public static save_rule_price(stdClass): int` — Preis-Modifikator je Kategorie speichern.
- `public static delete_rule(int): void` / `delete_rule_price(int): void` — Löschen inkl. abhängiger Preise.
- `private static purge_option_caches(int): void` — Regel-/Options-/Settings-Caches purgen.

### `slot_price` (slot_price.php)

**Verantwortung:** Berechnet Slot-Preise aus den Standard-Optionspreisen plus Slot-
Preisregeln; enthält einen lasttragenden Fallback, wenn `mod_booking\price` keine
Preiszeile auflöst. **Kollaborateure:** `mod_booking\price`, `singleton_service`,
`slot_rules`.

Methoden-Inventar:
- `public static calculate_slot_price_data(int,int,int,int=0): array` — Slot-Preisdaten (Basis + Preisregeln).
- `public static calculate_price(int,int,int=0,array=[]): float` — Gesamtpreis über N Slots.
- `private static get_base_slot_price_data(int,int=0): array` — Basispreis je Slot inkl. Fallback (default/erste Preiszeile).

### `slot_change_policy` (slot_change_policy.php)

**Verantwortung:** Single Source of Truth für die relative Move/Cancel-Deadline (signierter
Minuten-Offset je Slot, Auflösung Option→Instanz→Plugin) und für die Partitionierung in
actionable/locked Slots. **Persistenz:** `booking_slot_config` (Feld
`change_deadline_minutes`), Instanz-JSON, Plugin-Config. **Kollaborateure:** `booking`,
`singleton_service`, `slot_answer`.

Methoden-Inventar:
- `public static resolve_deadline_minutes(int): int` — Offset auflösen (Option→Instanz→Plugin).
- `public static slot_actionable(int,int,?int=null): bool` — Slot noch änderbar?
- `public static partition_slots(stdClass): array` — Slots in actionable/locked teilen.
- `public static answer_has_actionable_slot(stdClass): bool` — mind. ein actionable Slot.
- `public static answer_all_slots_actionable(stdClass): bool` — alle Slots actionable (Vollstorno-Gate).

### `target_price_policy` (target_price_policy.php)

**Verantwortung:** Policy für Self-Rebooking-Zielslots: V1-Filter auf preisgleiche
Zielslots sowie Berechnung des Move-Preisdeltas (Summe neu − Summe alt).
**Kollaborateure:** `slot_price`.

Methoden-Inventar:
- `public static filter_self_targets(int,int,array,array): array` — nur preisgleiche Zielslots (V1).
- `private static price_key(float): string` — stabiler Vergleichsschlüssel.
- `public static calculate_move_delta(int,int,array,array): float` — Netto-Preisdifferenz eines Moves.

### `slot_answer` (slot_answer.php)

**Verantwortung:** Adapter zum Lesen/Schreiben der Slot-Daten unter dem Key `slot` im
`booking_answers.json`. **Kollaborateure:** —.

Methoden-Inventar:
- `public static get_slot_data(object): ?array` — Slot-Payload aus JSON.
- `public static set_slot_data(object,array): void` — Slot-Payload rekursiv mergen (Hinweis: `array_replace_recursive` lässt entfernte Slots stehen → Move/Release umgehen dies bewusst).

### `slot_event_placeholders` (slot_event_placeholders.php)

**Verantwortung:** Gemeinsamer Renderer für Booking-Rule-Placeholders der Slot-Events:
formatiert Slot-Listen aus dem Event-Payload. **Kollaborateure:** `dates_handler`.

Methoden-Inventar:
- `public static render(string,array): string` — erste nicht-leere Slot-Liste aus Event-„other" formatieren.

### `slot_feature` (slot_feature.php)

**Verantwortung:** Single Source of Truth, ob die Slot-Buchung verfügbar ist (PRO +
Admin-Toggle `booking/slotbookingactive`, default-on). **Kollaborateure:** `wb_payment`.

Methoden-Inventar:
- `public static is_enabled(): bool` — PRO aktiv UND Toggle an (Default-on bei nie geschriebenem Wert).

## Persistenz

**Tabellen (gelesen/geschrieben in diesem Subsystem):**
- `booking_slot_config` — Slot-Konfiguration je Option (`slot_type`, Öffnungszeiten,
  Tage, Dauer/Interval, max. Teilnehmer/Slots, Lehrer-Pool, `change_deadline_minutes`,
  `allow_self_rebooking` …). Gelesen über `singleton_service`-Settings (`slotconfig`) und
  direkt (`slot_mover`, `slot_change_policy`).
- `booking_slot_moves` — Hold/State-Maschine für preisdifferente Umbuchungen
  (`slot_move_store`).
- `booking_slot_rule` / `booking_slot_rule_price` — Schliess-/Preisregeln (`slot_rules`
  lesend, `slot_rule_manager` schreibend).
- `booking_slot_student_teacher` — zugewiesene Lehrkräfte je Teilnehmer (gelesen in
  `slot_availability`).
- `booking_teacher_unavailability` — Lehrer-Sperrzeiten (gelesen in `slot_availability`).
- `booking_answers` — die gebuchten Slots liegen im `json`-Feld unter `slot`; gelesen in
  fast allen Klassen, geschrieben in `slot_mover` (Move/Release).
- `booking_options`, `booking_optiondates` — Optionsnamen bzw. Session-Slots (gelesen).

**Caches:**
- MUC: `mod_booking/slotrulesbyoption`, `mod_booking/slotrulepricesbyoption`
  (`slot_rules`); invalidiert via `slot_rule_manager` / Events
  `setbackslotrules`/`setbackslotruleprices`.
- Per-Request-Statics: `slot_availability::$bookedslotrangecache`,
  `slot_rules::$requestrulescache`/`$requestpricerulescache`/`$hastables`.
- Indirekt: `booking_option::purge_cache_for_answers`,
  `singleton_service::destroy_answers_for_user` / `destroy_booking_option_singleton`.

## Extension-Points

- **Feature-Gate `slot_feature::is_enabled()`** — zentraler An/Aus-Schalter, an dem alle
  externen Gates hängen.
- **Policy-Klassen `target_price_policy` / `slot_change_policy`** — als austauschbare
  Strategie gedacht (V1→V2-Preiskonzept, siehe Klassen-Doku); Konsumenten hängen nur an
  diesen Klassen.
- **`slot_rules` Ruletypes (`closed`/`price`) und Price-Modes
  (`absolute`/`delta`/`factor`)** — erweiterbarer Regel-Katalog;
  `slot_rules::rule_tables_available()` macht das Subsystem schema-tolerant
  (graceful degradation, wenn Regeltabellen fehlen).
- **`slot_dto`** — kanonische DTO-Schicht; neue Frontends sollen darüber Daten beziehen,
  nicht direkt über `slot_availability`.
- **Events** `bookinganswer_slotmoved` / `_slotcancelled` (ausserhalb Scope) +
  `slot_event_placeholders` als Rendering-Hook für Booking-Rule-Placeholders.
- **Shopping-Cart-Integration** über `service_provider`-Area `moveslot` (Callbacks
  ausserhalb Scope) gegen `slot_move_store`.

## Bekannte Schulden (→ Blueprint)

- **`slot_availability` God-Service (P1):** 1422 LOC / 28 Methoden, vermischt Slot-
  Generierung, Kapazität, Lehrkraft-Verfügbarkeit, Entity-Konflikt und Teilnehmer-Overlap.
  `evaluate_slot_for_user` (`slot_availability.php:385-513`, ~128 LOC) ist hochverzweigt
  (Kapazität + Teacher-Matrix + Entity + Overlap-Handling in einer Methode). Kandidat zur
  Aufspaltung in Generator / Kapazitätszähler / Teacher-Resolver / Overlap-Policy.
  Statische API + Statik-Caches erschweren Testbarkeit/Isolation
  (`slot_availability.php:35`, `:1359`).
- **`slot_mover::move_validated` (P1):** `slot_mover.php:629-848` (~220 LOC) macht
  Validierung, JSON-Persistenz, History-/Teacher-/Preis-Neuberechnung, zwei Event-Trigger
  und Notify in einem Block. Hohe zyklomatische Komplexität; schwer punktuell testbar.
- **JSON-Persistenz wird mehrfach manuell umgangen:** `slot_mover.php:770-774`,
  `slot_mover.php:446-450` und `slot_update_service` bauen das `slot`-Payload direkt, weil
  `slot_answer::set_slot_data` (`slot_answer.php:72`, `array_replace_recursive`) entfernte
  Slots an höherem Index stehenlässt. Der Adapter ist damit für „Reduktion" unbrauchbar →
  Schuld liegt in `slot_answer`; Konsumenten duplizieren das Workaround.
- **Verstreute Direkt-SQL statt zentralem Repository:** `slot_availability` (z. B.
  `:1066`, `:1149`, `:1275`), `slot_dto::build_report_slots` (`slot_dto.php:221`) und
  `slot_mover` greifen je eigen auf `booking_answers` zu; Status-Filter
  (`get_inactive_booking_states`) und Overlap-Logik existieren teils doppelt.
- **`slot_update_service::apply` (P2):** `slot_update_service.php:223-353` mit mehreren
  ineinandergreifenden Routing-Zweigen (reduction / mixed shrink / same-count) und
  partieller Re-Validierung über `plan()` — Routing-Regeln sind über `apply`, `plan` und
  `apply_reduction` verteilt (potentielle Drift).
- **`slot_dto::build_report_slots` (P2):** `slot_dto.php:195-373` (~180 LOC) mischt
  DB-Query, Aggregation und Präsentations-Formatierung; Reporting-Concern gehört eher in
  eine eigene Report-Klasse.
- **Float-Preisvergleiche / Epsilon verstreut:** `slot_update_service::PRICE_EPSILON`
  (`:49`) vs. `target_price_policy::price_key` (`:88`) — zwei verschiedene Float-
  Vergleichsstrategien im selben Flow.
- **`target_price_policy::filter_self_targets` (V1-Altlast):** laut Klassen-Doku
  (`target_price_policy.php:18-28`) durch das Preisdifferenz-Routing bereits abgelöst;
  prüfen, ob noch ein Konsument existiert (sonst toter Code).
