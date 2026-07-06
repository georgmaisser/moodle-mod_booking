# booking_existing_user_selector — Methoden-Doku
**Datei:** `classes/booking_existing_user_selector.php` · **LOC:** 123 · **Subsystem:** S01 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`booking_existing_user_selector` (erbt `booking_user_selector_base`) ist das Gegenstueck zu `booking_potential_user_selector`: das User-Selector-Widget zum **Entfernen bereits gebuchter** Teilnehmer. Die Suche ist auf die zuvor uebergebene `potentialusers`-Menge (= aktuell gebuchte User-ids) beschraenkt und wendet darauf den Suchstring sowie ggf. den Institutions-Filter an. Kollaborateure: `$DB`, `booking_check_if_teacher`, Core `users_order_by_sql`/`get_in_or_equal`. Persistenz (lesend): `user`, `booking_options`.

## Methoden

### `public function __construct($name, $options)` — public
- **Zweck:** Uebernimmt `potentialusers` (die als „existing"/gebuchte Menge fungieren) und das rohe `$options`-Array als Properties, delegiert an Parent. **Bewertung:** B — wie bei der Schwesterklasse doppelter State (Property + Parent-Feld).

### `public function find_users($search)` — public
- **Zweck:** Sucht innerhalb der gebuchten User-Menge nach Treffern des Suchstrings. **Logik:** baut aus `array_keys($this->potentialusers)` ein `get_in_or_equal`-IN-Constraint (named params `in_`); gibt sofort `[]` zurueck, wenn keine potentialusers vorliegen; wendet bei Trainer ohne `readallinstitutionusers` den Institutions-Filter an; respektiert den `too_many_results`-Guard (Count vor Fetch, ausser beim Validieren). **Rueckgabe:** `[get_string('booked','booking') => $users]`. **Seiteneffekte:** `$DB->count_records_sql` + `get_records_sql`. **Bewertung:** B — sauberer als die potential-Variante (alle ids ueber `get_in_or_equal` gebunden, kein interpoliertes SQL), aber teilt mit ihr den dupliziert kopierten Institutions-Filter-Block (Z.85–98 nahezu identisch). `find_users` filtert hier NICHT auf `suspended`/`deleted` (anders als die potential-Variante) — bewusst, da bereits gebuchte User entfernbar bleiben sollen.

### Triviale Properties
`potentialusers`, `options` (beide public, dupliziert Parent-State).

## Bewertungs-Resümee
Kleiner und sauberer als `booking_potential_user_selector` (gebundene IN-Liste statt interpoliertem SQL). Hauptschuld ist die Code-Duplikation des Institutions-Filter-Blocks mit der Schwesterklasse — Kandidat fuer eine gemeinsame protected Helper-Methode in der Basisklasse. Klassen-Score **B / P2**.
