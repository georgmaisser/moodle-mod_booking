# service_provider — Methoden-Doku
**Datei:** `classes/shopping_cart/service_provider.php` · **LOC:** 1022 · **Subsystem:** S05 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S05_shopping_cart.md)

## Klassenueberblick
Implementiert das `service_provider`-Callback-Interface von `local_shopping_cart` fuer mod_booking. Vermittelt zwischen Warenkorb-Engine und Booking-Domaene: laedt/entlaedt Cart-Items (Buchungsoptionen, Subbookings, Slot-Moves), commitet Buchungen nach erfolgreicher Zahlung, storniert, prueft Cancel-Erlaubnis und passt Stueckzahlen an. Reine statische Callback-Klasse (kein Zustand); kollaboriert mit `booking_bookit`, `booking_option`, `singleton_service`, `subbookings_info`, `bo_info`, sowie der Slotbooking-Familie (`slot_move_store`, `slot_mover`, `slot_price`, `slot_answer`) und der Shopping-Cart-Entity `cartitem`. Hauptlast liegt auf der ueberlangen `load_cartitem`-Methode; die Beschreibungsaufbereitung wurde bereits in mehrere private Helfer ausgelagert (gute Tendenz), das Area-Dispatch-Muster (option/subbooking/moveslot) ist aber in vielen Methoden dupliziert.

## Methoden

### `load_cartitem(string $area, int $itemid, int $userid = 0): array` — public static
- **Zweck:** Zentraler Cart-Load-Callback: ermittelt Preis, Kostenstelle, Service-Periode und Beschreibung fuer ein zu reservierendes Item und liefert ein `cartitem`. Behandelt drei Bereiche: `option`, `subbooking*`, `moveslot`.
- **Parameter/Rueckgabe:** Area-String, Item-/User-ID → `['cartitem' => cartitem]` oder `['error' => '<code>']`.
- **Seiteneffekte:** `require_once lib.php`; Verfuegbarkeitspruefung via `bo_info::is_available`; **reserviert** die Option/Subbooking via `booking_bookit::answer_*` (Status RESERVED → DB-Write der booking_answers); liest `get_config('booking', ...)`, `get_config('local_shopping_cart', ...)`; Capability-Check `local/shopping_cart:cashier` (context_system); Slot-Move-Lookup `slot_move_store::get_pending_for_option_user`, `decode_slots`, `slot_price::calculate_slot_price_data`; nutzt `$USER`.
- **Aufrufkette:** Gerufen von local_shopping_cart-Engine (Callback). Ruft `get_cart_book_intent_ignored_condition_ids`, `apply_reserved_slotbooking_price`, `build_cartitem_description`, `build_moveslot_description`.
- **Bewertung:** **E** — ~220 LOC (service_provider.php:63-283), drei grosse if/else-if-Aeste mit dupliziertem cartitem-Bau und dupliziertem Costcenter-Normalisierungs-Block (134-136, 202-204, 257-260), gemischte Verantwortung (Permission, Service-Periode, Preis, Beschreibung, Slotlogik). Klarer Zerlegungskandidat (je Area eine Methode).

### `build_moveslot_description(\stdClass $move): string` — private static
- **Zweck:** Erzeugt menschenlesbare "abgegeben → genommen"-Beschreibung eines Slot-Moves; zeigt nur geaenderte Slots (kept-Slots ausgefiltert).
- **Parameter/Rueckgabe:** booking_slot_moves-Row → lokalisierter String.
- **Seiteneffekte:** keine DB; `slot_move_store::decode_slots`, `dates_handler::prettify_optiondates_start_end`, `current_language`, `get_string`. Enthaelt zwei lokale Closures (`$format`, `$keyof`) sowie Filter-Lambdas.
- **Aufrufkette:** Von `load_cartitem` (moveslot-Ast).
- **Bewertung:** A — kompakt, funktional sauber, gute Diff-Logik via Schluesselmengen.

### `apply_reserved_slotbooking_price(object $settings, array $item, $answer): array` — private static
- **Zweck:** Ueberschreibt den Item-Preis mit dem reservierten Slotbooking-Preis aus den Answer-Daten, falls Typ SLOTBOOKING und Slotdaten vorhanden.
- **Parameter/Rueckgabe:** settings, item-Array, answer → modifiziertes item-Array.
- **Seiteneffekte:** keine DB; `slot_answer::get_slot_data`.
- **Aufrufkette:** Von `load_cartitem` (option-Ast).
- **Bewertung:** A — Guard-Clauses, kleine reine Transformation.

### `build_cartitem_description(object $settings, array $item, $answer, array $bookinginformation, int $numberofitems): string` — private static
- **Zweck:** Baut die Cart-Beschreibung: ersetzt `{placeholder}`-Tokens aus Config `sccartdescription`, entfernt ggf. Preisfragmente und haengt Slotbooking-Kontext an.
- **Parameter/Rueckgabe:** mehrere Kontextdaten → HTML-Beschreibung.
- **Seiteneffekte:** `get_config('booking', 'sccartdescription')`; `preg_match_all`, `userdate`, `get_string`; ruft mehrere Helfer.
- **Aufrufkette:** Von `load_cartitem`. Ruft `flatten_booking_information`, `build_slotbooking_placeholder_values`, `remove_price_information_from_description`, `append_slotbooking_context_to_description`.
- **Bewertung:** B — ~52 LOC, gut delegiert; Platzhalter-Ersetzungsschleife mit `$settings->$match`-Dynamik und der etwas eigenwilligen "is_numeric → userdate"-Regel (391-393) ist leicht fragil, aber lesbar.

### `flatten_booking_information(array $bookinginformation): array` — private static
- **Zweck:** Entwickelt verschachtelte Booking-Info (iambooked/iamreserved/onwaitinglist/notbooked) auf eine flache Ebene.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Von `build_cartitem_description`.
- **Bewertung:** A — klein, klar.

### `build_slotbooking_placeholder_values(array $item, array $slotdata, array $bookinginformation, int $numberofitems, bool $useprice): array` — private static
- **Zweck:** Baut die Platzhalter-Map (slot_*, booking_*, numberofitems) fuer die Beschreibungs-Ersetzung.
- **Seiteneffekte:** keine DB; `dates_handler::prettify_optiondates_start_end`, `userdate`, `get_string`.
- **Aufrufkette:** Von `build_cartitem_description`.
- **Bewertung:** B — ~40 LOC, primaer Daten-Mapping; akzeptabel.

### `remove_price_information_from_description(string $description): string` — private static
- **Zweck:** Entfernt `<div class="bo_price">…</div>`-Fragmente per Regex aus der Beschreibung.
- **Seiteneffekte:** keine.
- **Bewertung:** B — Einzeiler; Regex-auf-HTML ist prinzipiell brittle, aber eng begrenzt.

### `append_slotbooking_context_to_description(object $settings, string $description, array $slotdata, array $bookinginformation, int $numberofitems, int $userid = 0): string` — private static
- **Zweck:** Haengt fuer SLOTBOOKING-Optionen eine Slot-Detail-Liste (Datum + Preis pro Slot) und einen versteckten JSON-Kontext-Node an die Beschreibung an.
- **Seiteneffekte:** keine DB; pro Slot `slot_price::calculate_slot_price_data`, `format_float`, `json_encode`, `s()`-Escaping, `get_string`.
- **Aufrufkette:** Von `build_cartitem_description`.
- **Bewertung:** C — ~84 LOC (service_provider.php:509-592), gemischte Verantwortung (Preisberechnung pro Slot + HTML-Bau + JSON-Payload); hartkodierter deutscher String "Anzahl der Slots: " (564) statt get_string. Zerlegung in Slotlinien-Bau und HTML-Render empfehlenswert.

### `unload_cartitem(string $area, int $itemid, int $userid = 0): array` — public static
- **Zweck:** Entlaedt Item aus Cart und gibt es wieder frei (Reservierung aufheben). Drei Areas.
- **Parameter/Rueckgabe:** → `['success' => 0|1, 'itemstounload' => array]`.
- **Seiteneffekte:** `require_once lib.php`; `booking_option::create_option_from_optionid` (DB-Read); `subbookings_info::return_array_of_subbookings`; `booking_bookit::answer_booking_option` (Status NOTBOOKED → DB-Write); moveslot: `slot_move_store::get_pending_for_option_user`/`cancel` (DB-Write); `$USER`.
- **Aufrufkette:** local_shopping_cart-Engine; delegiert Subbooking an `unload_subbooking`.
- **Bewertung:** B — ~46 LOC, klares Area-Dispatch; das Dispatch-Muster wiederholt sich (Duplikat zu cancel_purchase/successful_checkout).

### `successful_checkout(string $area, int $itemid, int $paymentid, int $userid): bool` — public static
- **Zweck:** Commit nach erfolgreicher Zahlung: bestaetigt/bucht Option, bucht Subbooking, oder commited Slot-Move.
- **Parameter/Rueckgabe:** → bool Erfolg.
- **Seiteneffekte:** `require_once lib.php`; `booking_option::user_confirm_response` / `user_submit_response` (DB-Writes booking_answers); **Event** `booking_failed` (trigger → Observer loescht Kalendereintraege); `subbookings_info::save_response` (DB-Write); `slot_mover::commit_pending_move` (DB-Write, try/catch moodle_exception); `$USER`, `singleton_service::get_instance_of_user`.
- **Aufrufkette:** local_shopping_cart-Engine nach Payment.
- **Bewertung:** C — ~62 LOC (service_provider.php:658-720); option-Ast mit verschachtelter Fallback-Buchungslogik und Event-Bau gemischt; redundantes Re-Holen von `$user` (668 vs. 679). Funktional, aber dichte Mischung aus Domaenenlogik + Logging.

### `cancel_purchase(string $area, int $itemid, int $userid = 0): bool` — public static
- **Zweck:** Storniert einen bereits gebuchten Kurs/Subbooking bzw. voidet einen Slot-Move.
- **Seiteneffekte:** `require_once lib.php`; `booking_bookit::answer_booking_option` (Status DELETED, DB-Write); `subbookings_info::save_response` (DELETED); `slot_move_store::get_pending_for_option_user`/`cancel`; `$USER`.
- **Aufrufkette:** local_shopping_cart-Engine.
- **Bewertung:** B — strukturell identisch zu unload/successful_checkout (Area-Dispatch-Duplikat), aber kurz und klar.

### `quota_consumed(string $area, int $itemid, int $userid = 0): float` — public static
- **Zweck:** Liefert verbrauchten Anteil (0..1) des Items zur Storno-Rueckerstattungsberechnung.
- **Seiteneffekte:** `booking_option::get_consumed_quota` (DB-Read).
- **Bewertung:** A — trivialer Dispatch.

### `allowed_to_cancel(string $area, int $itemid): bool` — public static
- **Zweck:** Prueft, ob ein Item stornierbar ist (disablecancel auf Option- oder Instanzebene).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`; `booking_option::get_value_of_json_by_key`, `booking::get_value_of_json_by_key` (DB-Reads).
- **Aufrufkette:** local_shopping_cart-Engine.
- **Bewertung:** B — klar; enthaelt grossen auskommentierten canceluntil-Block (822-830) als Altlast/Doku.

### `unload_subbooking(string $area, int $itemid, int $userid = 0): array` — private static
- **Zweck:** Setzt Subbooking-Reservierung zurueck (Status NOTBOOKED).
- **Seiteneffekte:** `subbookings_info::save_response` (DB-Write).
- **Aufrufkette:** Von `unload_cartitem`.
- **Bewertung:** A — trivial.

### `get_cart_book_intent_ignored_condition_ids(object $settings, int $userid): array` — private static
- **Zweck:** Liefert bei multiplebookings + bereits gebuchtem User die zu ignorierenden Availability-Condition-IDs (Book-again-Intent).
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers`, `return_all_booking_information`; `bookitbutton::get_book_intent_override_condition_ids`; `$USER`.
- **Aufrufkette:** Von `load_cartitem` und `allow_add_item_to_cart`.
- **Bewertung:** A — fokussiert, Guard-Clauses.

### `allow_add_item_to_cart(string $area, int $itemid, int $userid = 0): array` — public static
- **Zweck:** Pruefgate vor dem Hinzufuegen zum Cart: ermittelt blockierende Availability-Condition und erlaubt/verweigert mit passendem Info-Code; baut bei Bedarf direkt einen cartitem.
- **Parameter/Rueckgabe:** → `['allow' => bool, 'info' => ..., 'itemname' => ...]` bzw. `cartitem->as_array()`.
- **Seiteneffekte:** `booking_option::purge_cache_for_answers` (**Cache-Purge**); `bo_info::is_available`; `singleton_service::get_instance_of_user`; `return_booking_option_information` (DB-Read).
- **Aufrufkette:** local_shopping_cart-Engine; ruft `get_cart_book_intent_ignored_condition_ids`.
- **Bewertung:** C — ~98 LOC (service_provider.php:891-989); switch fuer Fehlercodes + separater cartitem-Bau (Duplikat zu load_cartitem) + dynamische allow/deny-Konstruktion; offener TODO-Kommentar (946) deutet ungewollten Aufrufpfad an. Zerlegung in Gate-Eval + cartitem-Bau sinnvoll.

### `adjust_number_of_items(string $area, int $itemid, int $nritems, int $userid = 0): bool` — public static
- **Zweck:** Passt Stueckzahl (Lizenzen) einer Buchungsantwort an, begrenzt durch freie Plaetze + bereits gehaltene.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_answers`, `get_usersreserved`/`get_usersonlist`; `booking_answers::count_places`; `enrollink::return_number_of_booked_licenses_from_booking_answer`, `update_number_of_booked_licenses_for_booking_answer` (DB-Write).
- **Aufrufkette:** local_shopping_cart-Engine.
- **Bewertung:** B — ~21 LOC, klare Kapazitaetslogik.

## Anmerkungen
- Hartkodierter deutscher String "Anzahl der Slots: " in `append_slotbooking_context_to_description:564` umgeht die Sprachdatei (i18n-Inkonsistenz, kein Funktionsbug).
- Wiederkehrendes Costcenter-Normalisierungs-Idiom (`is_array → reset`) dreimal in `load_cartitem` dupliziert; das option/subbooking/moveslot-Dispatch-Muster ist ueber vier Methoden hinweg dupliziert.
