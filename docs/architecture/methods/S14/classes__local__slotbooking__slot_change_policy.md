# slot_change_policy — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_change_policy.php` · **LOC:** 132 · **Subsystem:** S14 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_change_policy` ist die Single Source of Truth fuer die relative Move/Cancel-Deadline eines gebuchten Slots. Die Deadline ist ein signierter Minuten-Offset zum jeweiligen Slot-Start (positiv = N Minuten vor Start, 0 = bis Slot-Start, negativ = N Minuten nach Start). Ein Slot ist "actionable" (verschieb-/stornierbar), solange `now < slotstart - offset`, sonst "locked". Die Deadline wird je Option in der Kaskade Option (`booking_slot_config.change_deadline_minutes`) -> Instanz (Booking-JSON-Key `slot_change_deadline_minutes`) -> Plugin-Default (`get_config('booking', ...)`) aufgeloest und **pro einzelnem Slot** ausgewertet (nicht fuer den fruehesten Slot einer Mehr-Slot-Buchung). Persistenz: keine eigene; liest aus `booking_slot_config`, der Booking-Instanz und der Plugin-Config. Kollaborateure: `slot_answer` (Slot-Daten aus dem Answer-JSON), `singleton_service`, `booking::get_value_of_json_by_key`, `$DB`, `get_config`.

## Methoden

### `public static function resolve_deadline_minutes(int $optionid): int` — public static
- **Zweck:** Loest den signierten Offset fuer eine Option in der Kaskade Option -> Instanz -> Plugin-Default auf. **Seiteneffekte:** `$DB->get_field('booking_slot_config', ...)`, `singleton_service::get_instance_of_booking_option_settings($optionid)`, `booking::get_value_of_json_by_key(...)`, `get_config('booking', 'slot_change_deadline_minutes')`. **Rueckgabe:** Offset in Minuten (int). **Bewertung:** A — saubere Nullable-Inherit-Semantik: Option-Wert greift nur bei `!== false && !== null`, Instanz-Wert nur bei `!== null && !== ''`, sonst Plugin-Default. Klar lesbare Fallback-Kette.

### `public static function slot_actionable(int $slotstart, int $offsetminutes, ?int $now = null): bool` — public static
- **Zweck:** Reine Stichtags-Pruefung fuer einen einzelnen Slot. **Seiteneffekte:** keine (Default `now = time()`). **Rueckgabe:** `true`, solange `now < slotstart - offsetminutes*60`. **Bewertung:** A — testbar dank injizierbarem `$now`, korrekte Vorzeichen-Arithmetik.

### `public static function partition_slots(stdClass $answer): array` — public static
- **Zweck:** Teilt die gebuchten Slots eines Answers anhand der aufgeloesten Deadline in `actionable` und `locked`. **Seiteneffekte:** indirekt ueber `resolve_deadline_minutes` (DB/Config/Singleton); liest Slot-Daten via `slot_answer::get_slot_data`. **Rueckgabe:** `['actionable' => [...], 'locked' => [...]]`. **Bewertung:** A — defensives Auslesen von `$slotdata['slots']` (Fallback `[]`) und der `start`-Werte; ein gemeinsames `$now` fuer alle Slots haelt die Partition konsistent.

### `public static function answer_has_actionable_slot(stdClass $answer): bool` — public static
- **Zweck:** Gate, ob Move/Cancel ueberhaupt angeboten wird (mind. ein Slot actionable). **Seiteneffekte:** ruft `partition_slots` (inkl. dessen DB/Config-Zugriffe). **Rueckgabe:** bool. **Bewertung:** B — funktional korrekt; ruft jedoch erneut die volle `partition_slots`-Kette auf. Wird sie zusammen mit `answer_all_slots_actionable` fuer denselben Answer aufgerufen, entstehen zwei identische Deadline-Aufloesungen (siehe Resuemee).

### `public static function answer_all_slots_actionable(stdClass $answer): bool` — public static
- **Zweck:** Gate fuer die Voll-Answer-Stornierung (jeder Slot actionable; leerer Slot-Satz ergibt `false`). **Seiteneffekte:** ruft `partition_slots`. **Rueckgabe:** `true`, wenn `locked` leer und `actionable` nicht leer. **Bewertung:** A — das `!empty(actionable)`-Glied verhindert korrekt, dass eine slotlose Buchung faelschlich als "alle actionable" gilt.

## Bewertungs-Resümee
Klar entworfene Policy-Klasse mit injizierbarer Zeit, sauberer Inherit-Kaskade und korrekter Slot-weiser Auswertung. Einziger Schoenheitsfehler: die beiden `answer_*`-Gates rufen `partition_slots` jeweils komplett neu auf (wiederholte Deadline-Aufloesung pro Answer) — bei Aufruf in einer Answer-Schleife ein mildes N+1-Risiko, da `resolve_deadline_minutes` pro Aufruf eine DB-Abfrage + Singleton-Lookup macht. Funktional unkritisch. Klassen-Score **A / P3**.
