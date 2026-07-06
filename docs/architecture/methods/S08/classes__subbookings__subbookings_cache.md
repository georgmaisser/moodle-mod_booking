# subbookings_cache — Methoden-Doku
**Datei:** `classes/subbookings/subbookings_cache.php` · **LOC:** 36 · **Subsystem:** S08 · **Klassen-Score:** D / P3
> [Subsystem-Doc](../../subsystems/S08_*.md)

## Klassenueberblick
`subbookings_cache` ist eine leere Marker-/Platzhalter-Klasse im Subbookings-Subsystem. Sie deklariert keinerlei Methoden, Felder oder Konstanten — der Klassenrumpf ist leer. Der eigentliche Cache `subbookings` wird nicht ueber diese Klasse, sondern direkt via `cache::make('mod_booking', 'subbookings')` in `subbookings.php` angesprochen; die Cache-Definition selbst liegt in `db/caches.php` (ausserhalb dieses Scopes). Die Klasse erfuellt damit keinen aktiven Laufzeitzweck und ist vermutlich ein historischer Ueberrest oder ein reservierter Namespace-Anker. Persistenz: keine. Kollaborateure: keine.

## Methoden
Keine. Die Klasse hat einen leeren Rumpf (Z.35–36).

## Bewertungs-Resümee
Toter Platzhalter ohne Verhalten. Kein funktionales Risiko, aber Dead Code: entweder mit der tatsaechlich genutzten Cache-Logik fuellen oder ersatzlos entfernen. Klassen-Score **D / P3**.
