# potential_subscriber_selector — Methoden-Doku
**Datei:** `classes/potential_subscriber_selector.php` · **LOC:** 165 · **Subsystem:** S01 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`potential_subscriber_selector` (erbt `subscriber_selector_base`) ist das Gegenstueck zu `existing_subscriber_selector`: es liefert die als **Trainer hinzufuegbaren** User (alle, die noch nicht zugewiesen sind). Es kennt zwei Modi: regulaer (potenzielle = alle nicht bereits zugewiesenen) und `forcesubscribed` (Anzeige-Modus, bei dem alle als bereits subscribed gelten). Kollaborateur: `$DB`; Persistenz (lesend): `user`.

## Methoden

### `public function __construct($name, $options)` — public
- **Zweck:** Delegiert an Parent; setzt `forcesubscribed = true`, falls in `$options` gesetzt. **Bewertung:** B — setzt das Flag fix auf `true`, ignoriert den uebergebenen Wert (jeder gesetzte `forcesubscribed`-Key, egal welcher Wert, aktiviert den Modus).

### `protected function get_options()` — protected
- **Zweck:** Reicht `forcesubscribed` (als `1`) in das Roundtrip-`$options`-Array, wenn aktiv. **Bewertung:** A.

### `public function find_users($search)` — public
- **Zweck:** Sucht hinzufuegbare User. **Logik:** baut `$whereconditions` schrittweise (Suchstring, `u.suspended = 0`); im Nicht-force-Modus werden die ids der bereits zugewiesenen Trainer (`existingsubscribers`, gruppiert) gesammelt und per `get_in_or_equal(..., false)` (NOT IN) ausgeschlossen; `too_many_results`-Guard via Count. **Rueckgabe:** Gruppenname je nach Modus (`existingsubscribers` bei force, sonst `potentialsubscribers`). **Seiteneffekte:** `count_records_sql` + `get_records_sql`. **Bewertung:** B — schrittweiser WHERE-Aufbau ist sauber und alle ids gebunden; die doppelte Schleife zum Abflachen von `existingsubscribers` (Gruppen→ids) ist etwas umstaendlich; sucht ueber ALLE Moodle-User (kein Enrolment-/Context-Filter), nur durch Count-Limit gebremst.

### `public function set_existing_subscribers(array $users)` — public
- **Zweck:** Setzt die Liste der bereits zugewiesenen Trainer (zum Ausschluss). **Bewertung:** A.

### `public function set_force_subscribed($setting = true)` — public
- **Zweck:** Soll den force-Modus setzen. **Bewertung:** C — **Bug-Smell:** ignoriert den Parameter `$setting` komplett und setzt `forcesubscribed` immer hart auf `true` (Z.163), d. h. `set_force_subscribed(false)` aktiviert den Modus trotzdem. Echter Defekt, nicht nur kosmetisch.

### Triviale Properties
`forcesubscribed` (bool), `existingsubscribers` (array), beide protected.

## Bewertungs-Resümee
Funktional brauchbar mit sauber gebundenem SQL und schrittweisem WHERE-Aufbau. Zwei reale Defekte rund um `forcesubscribed`: Konstruktor und Setter ignorieren beide den uebergebenen Wert und erzwingen `true` (`set_force_subscribed(false)` ist wirkungslos). Zudem context-/enrolment-loser Voll-User-Scan, nur durch Count-Limit gebremst. Klassen-Score **B / P2** (Setter-Defekt → REFACTORING_BACKLOG-Kandidat).
