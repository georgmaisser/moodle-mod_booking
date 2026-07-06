# slot_rule_manager — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_rule_manager.php` · **LOC:** 198 · **Subsystem:** S14 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_rule_manager` ist die Schreibseite (CRUD) der Slot-Preis-/Schliess-Regeln. Sie persistiert in zwei Tabellen: `booking_slot_rule` (eine Regel: Typ `closed`/`price`, Prioritaet, Zeitfenster, Wochentage, Zeitspanne, `valueint`, `payloadjson`) und `booking_slot_rule_price` (pro Regel je Preiskategorie ein Modifikator: `mode` absolute/delta/factor, `value`, `currency`). Alle Methoden sind statisch. Nach jeder Mutation wird konsistent invalidiert: `slot_rules::invalidate_option_cache`, `singleton_service::destroy_booking_option_singleton` und vier `cache_helper::purge_by_event`-Aufrufe. Konstanten definieren Regeltypen und Preismodi. Kollaborateure: `$DB`, `slot_rules`, `singleton_service`, `cache_helper`.

## Methoden

### `public static function save_rule(stdClass $ruledata): int` — public static
- **Zweck:** Insert oder Update einer Slot-Regel. Baut den Record defensiv aus `$ruledata` (alle Felder mit Default und Typ-Cast), validiert `optionid > 0` (sonst `coding_exception`) und normalisiert unbekannte `ruletype`-Werte auf `closed`. Bei vorhandener gueltiger `id` Update unter Beibehaltung von `timecreated` und `optionid` aus dem Bestandsrecord, sonst Insert mit `timecreated = now`. **Seiteneffekte:** `$DB->record_exists`/`get_record`/`update_record`/`insert_record` auf `booking_slot_rule`; abschliessend `purge_option_caches`. **Rueckgabe:** Rule-id. **Bewertung:** B — robuste Normalisierung; das Ueberschreiben von `optionid` mit dem Bestandswert beim Update verhindert versehentliches Verschieben einer Regel, ignoriert dabei aber stillschweigend ein abweichendes `optionid` im Input (gewollt, aber undokumentiert).

### `public static function save_rule_price(stdClass $pricedata): int` — public static
- **Zweck:** Insert oder Update eines Preis-Modifikators fuer eine Preiskategorie einer Regel. Validiert `ruleid` (muss existieren, sonst `coding_exception`), normalisiert unbekannten `mode` auf `absolute` und leeren `pricecategoryidentifier` auf `default`. **Seiteneffekte:** Schreibzugriff auf `booking_slot_rule_price`; ermittelt die zugehoerige `optionid` per `get_field` aus `booking_slot_rule` und ruft `purge_option_caches`. **Rueckgabe:** Rule-price-id. **Bewertung:** B — beim Update wird `timecreated` nicht gesetzt (korrekt, bleibt erhalten), beim Insert gesetzt; konsistent mit `save_rule`.

### `public static function delete_rule(int $ruleid): void` — public static
- **Zweck:** Loescht eine Regel samt aller zugehoerigen Preiszeilen. **Seiteneffekte:** No-op bei ungueltiger/nicht existenter id; sonst ermittelt `optionid`, loescht `booking_slot_rule_price` (per `ruleid`) und `booking_slot_rule`, dann `purge_option_caches`. **Rueckgabe:** void. **Bewertung:** A — saubere Reihenfolge (Kinder vor Elternzeile), korrekte Invalidierung. Beide Deletes laufen nicht in einer expliziten Transaktion; bei DB-Fehler nach dem ersten Delete bliebe eine verwaiste Elternzeile zurueck (sehr geringes Risiko, P3).

### `public static function delete_rule_price(int $rulepriceid): void` — public static
- **Zweck:** Loescht genau einen Preis-Modifikator. **Seiteneffekte:** No-op bei ungueltiger id; ermittelt ueber zwei `get_field`-Hops (`booking_slot_rule_price`→`ruleid`, `booking_slot_rule`→`optionid`) die betroffene Option, loescht die Zeile, ruft `purge_option_caches`. **Rueckgabe:** void. **Bewertung:** A.

### `private static function purge_option_caches(int $optionid): void` — private static
- **Zweck:** Zentrale Cache-/Singleton-Invalidierung nach jeder Mutation. **Seiteneffekte:** No-op bei `optionid <= 0`; sonst `slot_rules::invalidate_option_cache`, `singleton_service::destroy_booking_option_singleton` und vier `cache_helper::purge_by_event` (`setbackslotrules`, `setbackslotruleprices`, `setbackoptionstable`, `setbackoptionsettings`). **Rueckgabe:** void. **Bewertung:** B — gruendlich, aber grobkoernig: die globalen `purge_by_event`-Aufrufe invalidieren systemweit alle Options-Tabellen-/Settings-Caches, nicht nur die betroffene Option (bekanntes Muster im Plugin; Performance-Implikation bei haeufigen Regel-Edits).

### Triviale Properties
Fuenf `public const`-Konstanten (`RULETYPE_CLOSED`, `RULETYPE_PRICE`, `PRICEMODE_ABSOLUTE`, `PRICEMODE_DELTA`, `PRICEMODE_FACTOR`, Z.36–48) als Wert-Enumerationen.

## Bewertungs-Resümee
Klar strukturierte, defensiv geschriebene Schreibseite mit konsequenter, wenn auch grobkoerniger Cache-Invalidierung. Keine funktionalen Defekte; kleinere Punkte sind die fehlende Transaktionsklammer in `delete_rule` und die systemweiten Purges. Klassen-Score **B / P3**.
