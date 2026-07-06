# target_price_policy — Methoden-Doku
**Datei:** `classes/local/slotbooking/target_price_policy.php` · **LOC:** 127 · **Subsystem:** S14 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`target_price_policy` kapselt die Preis-Politik fuer das Self-Service-Rebooking von Slots (V1). Zustandslos, nur statische Methoden. V1-Regel: Self-Rebooking ist nur auf preisgleiche Zielslots erlaubt, womit jegliche Zahlungs-/Erstattungslogik entfaellt; die Webservice-Schicht haengt ausschliesslich an dieser Klasse, sodass V2 (Preisdifferenz via shopping_cart, siehe `SLOTBOOKING_REBOOKING_PRICE_CONCEPT.md`) lokal getauscht werden kann. Bereits enthalten ist `calculate_move_delta` als V2-Vorgriff. Einziger Kollaborateur: `slot_price::calculate_slot_price_data`.

## Methoden

### `public static function filter_self_targets(int $optionid, int $userid, array $currentslots, array $targetslots): array` — public static
- **Zweck:** Behaelt aus `$targetslots` nur die Zielslots, deren berechneter Preis dem Preis eines der aktuellen Slots des Users entspricht (preisgleiches Rebooking). Das Wiederwaehlen eines bestehenden Slots ist damit immer erlaubt, weil dessen eigener Preis in der erlaubten Menge liegt. **Seiteneffekte:** keine; pro aktuellem und pro Zielslot ein `slot_price::calculate_slot_price_data`-Aufruf (lesend; M+N Preisberechnungen). **Rueckgabe:** gefilterte Zielslot-Liste (Originaleintraege, unveraendert). **Bewertung:** B — korrekt und gut nachvollziehbar; Float-Vergleich ueber den normalisierten String-Key vermeidt Gleitkomma-Fallen. Skaliert mit Anzahl Slots (je ein Preisaufruf), fuer die kleinen Slot-Mengen pro Option unkritisch.

### `private static function price_key(float $price): string` — private static
- **Zweck:** Normalisiert einen Preis auf einen stabilen Vergleichsschluessel, um Float-Gleichheitsprobleme zu umgehen. **Seiteneffekte:** keine. **Rueckgabe:** `number_format($price, 2, '.', '')` (z.B. `"12.50"`). **Bewertung:** A — bewusst gegen Float-Identitaet abgesichert; rundet auf 2 Dezimalen, konsistent mit der Rundung in `slot_price`.

### `public static function calculate_move_delta(int $optionid, int $userid, array $currentslots, array $newslots): float` — public static
- **Zweck:** Berechnet die Preisdifferenz eines Move: `Summe(neue Slotpreise) - Summe(aktuelle Slotpreise)`. Positiv = Upgrade (Nachzahlung), negativ = Downgrade (Erstattung), 0 = preisneutral; beibehaltene Slots heben sich auf. Vorgesehen fuer `slot_update_service` zum Routing des Moves (direkt/Erstattung/Cart). **Seiteneffekte:** keine; je ein `slot_price`-Aufruf pro Slot beider Listen ueber eine lokale Summen-Closure. **Rueckgabe:** auf 2 Dezimalen gerundetes Preisdelta. **Bewertung:** A — saubere Closure-Kapselung der Summenbildung, korrekte Vorzeichensemantik dokumentiert.

## Bewertungs-Resümee
Schlanke, klar dokumentierte Policy-Klasse mit bewusster Absicherung gegen Float-Gleichheit und sauberer Trennung zwischen V1-Filter und V2-Delta-Vorgriff. Keine funktionalen Maengel; einzig die M+N-Preisberechnungen pro Filteraufruf sind eine theoretische, praktisch unkritische Kostenstelle. Klassen-Score **B / P3**.
