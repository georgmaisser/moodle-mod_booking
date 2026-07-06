# slot_rules — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_rules.php` · **LOC:** 451 · **Subsystem:** S14 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
Statische Helfer-Klasse zum Laden und Anwenden von Slot-Regeln (Schliessregeln und Preisregeln) auf generierte Buchungs-Slots. Liest aus `booking_slot_rule` / `booking_slot_rule_price`, puffert ueber einen zweistufigen Cache (Request-Static + MUC `slotrulesbyoption` / `slotrulepricesbyoption`) und prueft Slot-Zeitfenster (activefrom/until, Wochentage, Tageszeit-Range) gegen Regeln. Kollaborateure: Slot-Generator (Aufrufer von `apply_to_slots`), Preis-Aufloesung (Aufrufer von `apply_price_rules_to_slot_price`), Moodle `cache` / `$DB` / `xmldb`-Schema-Manager. Reine Lese-/Berechnungsklasse ohne DB-Writes; sehr fokussiert, gut testbar (eigenes `reset_caches`).

## Methoden

### `get_rules_for_option(int $optionid): array` — public static
- **Zweck:** Liefert alle Regeln einer Option, geordnet `priority DESC, id ASC`, aus Request-Static-Cache, MUC oder DB.
- **Parameter/Rueckgabe:** `$optionid`; gibt `array` von Regel-Records (oder `[]`) zurueck.
- **Seiteneffekte:** DB-Read `booking_slot_rule`; MUC-Read/Write `mod_booking/slotrulesbyoption` (Key `option_<id>`, sentinel `true` = leer); Static-Cache-Write `$requestrulescache`. Liest `global $DB`.
- **Aufrufkette:** Gerufen von `apply_to_slots`; ruft `rule_tables_available`.
- **Bewertung:** A — sauberes dreistufiges Cache-Muster mit Negativ-Sentinel; klare Guards.

### `apply_to_slots(int $optionid, array $slots): array` — public static
- **Zweck:** Filtert generierte Basis-Slots, entfernt durch Schliessregeln gesperrte Slots.
- **Parameter/Rueckgabe:** `$optionid`, `$slots` (Paare `[start,end]`); gibt gefilterte Slot-Liste zurueck.
- **Seiteneffekte:** Keine direkten (delegiert Reads an `get_rules_for_option`).
- **Aufrufkette:** Oeffentlicher Einstieg fuer Slot-Generierung; ruft `get_rules_for_option`, `is_slot_allowed_by_rules`.
- **Bewertung:** A — kurz, klar, frueher Exit bei leeren Slots/Regeln.

### `invalidate_option_cache(int $optionid): void` — public static
- **Zweck:** Invalidiert Regel- und Preisregel-Cache (Static + MUC) fuer eine Option.
- **Seiteneffekte:** Static-Unset `$requestrulescache`/`$requestpricerulescache`; MUC-Delete `slotrulesbyoption` und `slotrulepricesbyoption` (Key `option_<id>`).
- **Aufrufkette:** Von Persistenz-/Editierpfaden der Slot-Regeln (extern) zu rufen.
- **Bewertung:** A — symmetrisch zu den Lade-Methoden, vollstaendige Invalidierung.

### `apply_price_rules_to_slot_price(int $optionid, int $slotstart, int $slotend, float $baseprice, string $pricecategoryidentifier=''): float` — public static
- **Zweck:** Wendet passende Preisregeln (delta/factor/absolute) sequentiell auf einen Slot-Basispreis an, gegated nach Zeit-Match und Preiskategorie.
- **Parameter/Rueckgabe:** Slot-Zeitfenster, Basispreis, aktive Kategorie; gibt finalen Preis (`>= 0`) zurueck.
- **Seiteneffekte:** Keine direkten (Reads via `get_price_rules_for_option`).
- **Aufrufkette:** Von Preis-Aufloesung der Slots; ruft `get_price_rules_for_option`, `rule_matches_slot`, `price_rule_matches_category`.
- **Bewertung:** A — verstaendliche Mode-Behandlung mit Default-Fallback und `max(0.0, ...)`-Clamp; ~42 LOC, klar strukturiert.

### `is_slot_allowed_by_rules(array $rules, int $slotstart, int $slotend): bool` — private static
- **Zweck:** Prueft, ob ein Slot von keiner `closed`-Regel getroffen wird.
- **Rueckgabe:** `false` sobald eine Schliessregel matcht, sonst `true`.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Von `apply_to_slots`; ruft `rule_matches_slot`.
- **Bewertung:** A — kompakt, fail-fast.

### `get_price_rules_for_option(int $optionid): array` — private static
- **Zweck:** Laedt Preisregeln (JOIN Regel+Preiszeile) fuer eine Option mit gleichem dreistufigem Cache-Muster.
- **Seiteneffekte:** DB-Read via `get_records_sql` (JOIN `booking_slot_rule` × `booking_slot_rule_price`); MUC-Read/Write `slotrulepricesbyoption`; Static-Write `$requestpricerulescache`. `global $DB`.
- **Aufrufkette:** Von `apply_price_rules_to_slot_price`; ruft `rule_tables_available`.
- **Bewertung:** B — funktional sauber, aber inline-SQL-String (handgebauter JOIN, ~20 Zeilen) und nahezu strukturelle Duplikation des Cache-Geruests von `get_rules_for_option` (slot_rules.php:234-300 vs. 54-100). Smell: SQL-Bau + Cache-Boilerplate-Duplikat.

### `price_rule_matches_category(string $rulecategoryidentifier, string $activecategoryidentifier): bool` — private static
- **Zweck:** Prueft, ob die (komma-separierte) Regel-Kategorie auf die aktive Nutzer-Kategorie passt; `default` matcht immer.
- **Seiteneffekte:** Keine.
- **Aufrufkette:** Von `apply_price_rules_to_slot_price`.
- **Bewertung:** B — `strpos`-Substring-Match (Zeile 320) statt exaktem Vergleich kann false positives erzeugen (z.B. Kategorie `vip` matcht `vip-extra`); funktional gewollt? Smell: lose Substring-Kategoriepruefung slot_rules.php:320.

### `rule_matches_slot(\stdClass $rule, int $slotstart, int $slotend): bool` — private static
- **Zweck:** Kern-Praedikat: prueft Slot gegen activefrom/activeuntil (+DAYSECS-Toleranz), Wochentage und Tageszeit-Range mit Ueberlappungslogik.
- **Seiteneffekte:** Keine (nutzt `date('N')`, `strtotime('midnight')` — zeitzonenabhaengig).
- **Aufrufkette:** Von `is_slot_allowed_by_rules` und `apply_price_rules_to_slot_price`; ruft `parse_weekdays`, `time_to_seconds`.
- **Bewertung:** B — ~40 LOC, mehrere sequentielle Guard-Bloecke; einzelne Verantwortung (Match-Praedikat) aber dichte Zeitarithmetik mit impliziten Annahmen (Server-Zeitzone, DAYSECS-Toleranz bei activeuntil Zeile 347). Gut lesbar, aber Testabdeckung der Range-Ueberlappung wichtig.

### `parse_weekdays(string $csv): array` — private static
- **Zweck:** Parst Wochentage-CSV zu eindeutigen Ints 1..7.
- **Bewertung:** A — kleine reine Funktion, robuste Filterung.

### `rule_tables_available(): bool` — private static
- **Zweck:** Schema-Guard: prueft (gecacht) ob `booking_slot_rule` und `booking_slot_rule_price` existieren — Schutz fuer Pre-Upgrade-Zustaende.
- **Seiteneffekte:** `$DB->get_manager()->table_exists` (Schema-Lookup); Static-Write `$hastables`. `global $DB`.
- **Aufrufkette:** Von `get_rules_for_option`, `get_price_rules_for_option`.
- **Bewertung:** A — pragmatischer Forward-Compat-Guard, korrekt 1x pro Request gecacht.

### `time_to_seconds(string $time): ?int` — private static
- **Zweck:** Parst `HH:MM` zu Sekunden ab Mitternacht; validiert Wertebereiche, sonst `null`.
- **Bewertung:** A — saubere reine Funktion mit Regex- und Range-Validierung.

### `reset_caches(): void` — public static
- **Zweck:** Setzt alle Static-Caches (`$requestrulescache`, `$requestpricerulescache`, `$hastables`) zurueck — fuer Test-Teardown.
- **Bewertung:** A — explizite Testbarkeits-Hilfe; gut.

## Anmerkungen
- DB-Write-frei (reine Lese-/Auswerteklasse). Konstanten `RULETYPE_CLOSED`/`RULETYPE_PRICE` gut typisiert.
- Zwei nahezu identische Cache-Lade-Methoden (`get_rules_for_option` / `get_price_rules_for_option`) — extrahierbares Muster, aber geringe Prio.
