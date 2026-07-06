# existing_subscriber_selector — Methoden-Doku
**Datei:** `classes/existing_subscriber_selector.php` · **LOC:** 55 · **Subsystem:** S01 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`existing_subscriber_selector` (erbt `subscriber_selector_base`) ist das Selector-Widget zum **Entfernen bereits zugewiesener Trainer** einer Buchungsoption. Es listet alle als `booking_teachers` der Option eingetragenen User, gefiltert durch den Suchstring. Kollaborateur: `$DB`; Persistenz (lesend): `user` JOIN `booking_teachers`.

## Methoden

### `public function find_users($search)` — public
- **Zweck:** Liefert die der Option zugeordneten Trainer als `[get_string('existingsubscribers') => $users]`. **Logik:** `search_sql` fuer den Suchstring, JOIN `booking_teachers s ON s.userid = u.id WHERE s.optionid = :optionid`, sortiert via `users_order_by_sql`. **Seiteneffekte:** ein `$DB->get_records_sql`. **Bewertung:** A — kompakt, alle Werte gebunden (`:optionid`), klare Single-Query-Logik; kein `too_many_results`-Guard (zugewiesene Trainer sind ueblicherweise wenige, daher vertretbar).

## Bewertungs-Resümee
Minimaler, sauberer Selector ohne Smells: parametrisiertes SQL, eine Verantwortung. Klassen-Score **A / P3**.
