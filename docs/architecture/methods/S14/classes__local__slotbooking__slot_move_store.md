# slot_move_store — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_move_store.php` · **LOC:** 260 · **Subsystem:** S14 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_move_store` ist Repository und Zustandsmaschine fuer preis-differente Umbuchungen (Slot-Moves mit Aufpreis), gespeichert in `booking_slot_moves`. Es ist die Single Source of Truth fuer einen laufenden oder abgeschlossenen Move: eine PENDING-Zeile haelt die Ziel-Slots, solange die Upgrade-Zahlung im Checkout ist (der Kapazitaetszaehler liest nicht-abgelaufene Pending-Zeilen, sodass der Ziel-Slot gesperrt ist); bei erfolgreichem Checkout wird die Zeile COMMITTED und mit dem Cart-Identifier verknuepft; bei Abbruch/Ablauf/Stornierung wird sie CANCELLED. Drei Status-Konstanten (`STATUS_PENDING=0`, `STATUS_COMMITTED=1`, `STATUS_CANCELLED=2`), Tabellenname als private Konstante `TABLE='booking_slot_moves'`. Persistenz: Tabelle `booking_slot_moves`. Kollaborateure: `$DB`, die shopping_cart-`moveslot`-Callbacks (Cart-Item per optionid), der Slot-Kapazitaetszaehler, der Cart-Expiry-Task.

## Methoden

### `public static function create_pending(int $optionid, int $baid, int $userid, array $newslots, array $oldslots, float $pricedelta, int $expiry): int` — public static
- **Zweck:** Legt eine PENDING-Zeile an (gehaltene Ziel-Slot-Reservierung, an einen Checkout gebunden). **Seiteneffekte:** `$DB->insert_record`; setzt `timecreated`/`timemodified` auf `time()`, `identifier=null`. `newslots`/`oldslots` werden via `json_encode(array_values(...))` als kompakte Listen gespeichert. **Rueckgabe:** neue Row-id (int). **Bewertung:** A — `array_values` normalisiert die Slot-Listen vor dem Encode; klares Insert.

### `public static function get(int $id): ?stdClass` — public static
- **Zweck:** Liest eine Move-Zeile per id. **Seiteneffekte:** `$DB->get_record`. **Rueckgabe:** Record oder `null` (statt `false`). **Bewertung:** A.

### `public static function get_pending_for_answer(int $baid, ?int $now = null): ?stdClass` — public static
- **Zweck:** Liefert den offenen (pending, nicht-abgelaufenen) Move eines Answers. **Seiteneffekte:** `$DB->get_records_select` mit `timecreated DESC` und Limit 1. **Rueckgabe:** juengste Pending-Zeile oder `null`. **Bewertung:** A — Limit-1 + DESC-Sortierung halten "ein offener Move pro Answer" robust, auch falls mehrere existieren.

### `public static function get_pending_for_option_user(int $optionid, int $userid, ?int $now = null): ?stdClass` — public static
- **Zweck:** Offener Move fuer option+user (genutzt von den shopping_cart-Callbacks, die per optionid keyen). **Seiteneffekte:** `$DB->get_records_select`, `timecreated DESC`, Limit 1. **Rueckgabe:** juengster Pending-Move oder `null`. **Bewertung:** B — der Doc-Kommentar weist selbst auf die Mehrdeutigkeit hin: existieren mehrere Buchungen derselben Option mit je einem offenen Move, wird nur der juengste zurueckgegeben. Da der Cart-Schluessel optionid ist, kann ein zweiter offener Move desselben users/derselben Option (mehrere `baid`) nicht eindeutig adressiert werden — in der Praxis durch den "ein offener Move pro Answer"-Guard selten, aber konstruktiv nicht ausgeschlossen.

### `public static function get_active_holds_for_option(int $optionid, ?int $now = null): array` — public static
- **Zweck:** Gibt die gehaltenen Ziel-Slot-Ranges aller nicht-abgelaufenen Pending-Moves einer Option zurueck (vom Kapazitaetszaehler genutzt, um Ziel-Slots waehrend eines Checkouts zu sperren). **Seiteneffekte:** `$DB->get_records_select` (kein Limit). **Rueckgabe:** Liste `['moveid','baid','userid','slots'=>[['start','end'],...]]`. **Bewertung:** A — eine einzige Abfrage liefert alle Holds, das Decoding laeuft in-memory ueber `decode_slots`; kein per-Hold-Query.

### `public static function commit(int $id, ?int $identifier = null): void` — public static
- **Zweck:** Setzt einen Move auf COMMITTED und verknuepft ihn mit dem Cart-Checkout-Identifier. **Seiteneffekte:** `$DB->update_record` (status, identifier, `timemodified=time()`). **Rueckgabe:** void. **Bewertung:** A.

### `public static function cancel(int $id): void` — public static
- **Zweck:** Setzt einen Move auf CANCELLED (Abbruch/Ablauf/Answer-Stornierung). **Seiteneffekte:** `$DB->update_record` (status, `timemodified`). **Rueckgabe:** void. **Bewertung:** A.

### `public static function purge_expired(?int $now = null): void` — public static
- **Zweck:** Markiert alle abgelaufenen Pending-Moves als CANCELLED (Housekeeping-Sicherheitsnetz neben dem Cart-Expiry-Task). **Seiteneffekte:** `$DB->set_field_select` (Bulk-Update aller `status=PENDING AND expiry<=now`). **Rueckgabe:** void. **Bewertung:** A — ein Set-Based-Update statt Schleife; korrektes Praedikat.

### `public static function decode_slots(?string $json): array` — public static
- **Zweck:** Dekodiert ein gespeichertes Slots-JSON in eine normalisierte Liste `['start'=>int,'end'=>int]`. **Seiteneffekte:** keine. **Rueckgabe:** normalisierte Slot-Liste (leer bei null/ungueltig). **Bewertung:** A — defensiv (`empty`-Guard, `is_array`-Check, Eintraege nur bei vorhandenem `start`+`end`), castet beide Felder auf int.

### Triviale Konstanten
Drei Status-Konstanten und der private Tabellenname (`TABLE`) als zentrale Symbole der Zustandsmaschine.

## Bewertungs-Resümee
Sauber strukturiertes Repository mit klarer Drei-Zustands-Maschine, durchgaengig set-based bzw. limit-1-Abfragen und defensivem JSON-Decoding — kein N+1 und keine Datenverlust-Pfade. Die einzige konstruktive Schwaeche ist die optionid+userid-Aufloesung in `get_pending_for_option_user`, die bei mehreren Buchungen derselben Option pro User nicht eindeutig ist (bewusst per juengstem Move entschaerft, vom Doc-Kommentar offengelegt). Funktional unkritisch. Klassen-Score **B / P3**.
