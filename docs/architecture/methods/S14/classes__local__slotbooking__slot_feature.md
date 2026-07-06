# slot_feature — Methoden-Doku
**Datei:** `classes/local/slotbooking/slot_feature.php` · **LOC:** 51 · **Subsystem:** S14 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S14_slotbooking.md)

## Klassenueberblick
`slot_feature` ist die Single Source of Truth dafuer, ob die Slot-Buchungs-Funktion auf der Seite verfuegbar ist. Slot-Buchung erfordert BEIDES: die aktive PRO-Lizenz UND den globalen Admin-Toggle `booking/slotbookingactive` (settings.php). Jeder Gate im Plugin (Option-Type-Form/-Validierung/-Resolver, Prepage-Condition, Agent-Skill, Slot-Entry-Scripts und Webservices) laeuft durch diesen Helfer, sodass das Ein-/Ausschalten des gesamten Features eine einzige, konsistente Entscheidung ist. Die Datei nutzt `declare(strict_types=1)`. Persistenz: keine. Kollaborateure: `mod_booking\utils\wb_payment::pro_version_is_activated`, `get_config('booking', 'slotbookingactive')`.

## Methoden

### `public static function is_enabled(): bool` — public static
- **Zweck:** Liefert `true` nur, wenn PRO aktiv ist UND der Admin-Toggle on ist. **Seiteneffekte:** `wb_payment::pro_version_is_activated()`, `get_config('booking', 'slotbookingactive')`. **Rueckgabe:** bool. **Bewertung:** A — korrekte Default-On-Semantik: `($active === false) || (bool)$active` behandelt den nie-geschriebenen Toggle (frisch nach dem einfuehrenden Upgrade) als aktiviert, sodass bestehende PRO-Seiten Slot-Buchung behalten, bis ein Admin sie explizit auf `'0'` setzt (was via `(bool)'0' === false` korrekt deaktiviert). PRO-Gate steht bewusst vor dem Toggle (Fail-fast).

## Bewertungs-Resümee
Triviale, aber bewusst zentralisierte Feature-Flag-Klasse mit durchdachter Default-On-Logik nach dem Upgrade. Keine Probleme. Klassen-Score **A / P3**.
