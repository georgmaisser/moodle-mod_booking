# select_user_shopping_cart — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_user_shopping_cart.php` · **LOC:** 313 · **Subsystem:** S06 · **Klassen-Score:** D / P1
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_user_shopping_cart` implementiert das Interface `booking_rule_condition` und identifiziert Empfaenger ueber **offene Ratenzahlungen** im Shopping-Cart-Verlauf. Anders als die meisten Conditions filtert sie nicht eine bereits per Event bestimmte Nutzermenge, sondern erweitert das von der Regel gelieferte `$sql`-Konstrukt (`select`/`from`/`where`) um einen `RIGHT JOIN` auf `local_shopping_cart_history` und eine JSON-Auswertung des `installments`-Arrays, um faellig werdende Teilzahlungen (paid=0) zu finden. Persistenz: keine eigene Tabelle — der Zustand lebt im `rulejson` des `booking_rules`-Records (diese Condition speichert sogar keine eigenen Daten, nur `conditionname`). Kollaborateure: `$DB` (DB-Family-Abhaengig: Postgres-LATERAL vs. MySQL/MariaDB-`JSON_TABLE`), `local_shopping_cart_history`, die zeitgesteuerten Regeltypen `rule_daysbefore`/`rule_specifictime`, sowie `db_is_at_least_mariadb_106_or_mysql_8()` aus `lib.php`.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Erlaubt die Condition nur mit den zeitgesteuerten Regeltypen `rule_daysbefore`/`rule_specifictime` und nur auf JSON-faehigen DBs (Postgres immer, MySQL/MariaDB nur ab MariaDB 10.6 / MySQL 8.0 wegen `JSON_TABLE`). **Seiteneffekte:** `$DB->get_dbfamily()`, `db_is_at_least_mariadb_106_or_mysql_8()`. **Rueckgabe:** bool. **Bewertung:** C — der Inline-Kommentar Z.67 (`cannot be combined with "days before" or "specific time" ... has no event`) widerspricht dem Code, der genau fuer diese beiden true liefert; irrefuehrend (siehe Finding).

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt JSON aus dem DB-Record. **Seiteneffekte:** delegiert an `set_conditiondata_from_json($record->rulejson)`. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Uebernimmt den Roh-JSON-String in `$this->rulejson`. **Seiteneffekte:** keine (im Gegensatz zu den meisten Conditions wird hier *nichts* aus dem JSON dekodiert — die Condition traegt keine konfigurierbaren Daten). **Bewertung:** A.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt lediglich ein statisches Beschreibungs-Element (`conditionselectusershoppingcart_desc`) ins Formular ein — kein Eingabefeld, da nicht konfigurierbar. **Seiteneffekte:** `$mform->addElement('static', ...)`. **Rueckgabe:** void. **Bewertung:** A.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Liefert den lokalisierten (`selectusershoppingcart`) oder technischen Conditionnamen. **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt `conditionname` und ein leeres `conditiondata`-Objekt in `$data->rulejson`. **Seiteneffekte:** mutiert `$data->rulejson` (re-encodes). **Bewertung:** B — `json_decode($data->rulejson)` ohne Null-Guard; bei korruptem JSON wuerde `$jsonobject` null und der folgende Property-Zugriff fehlschlagen (Muster der Condition-Familie).

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt `bookingruleconditiontype` fuer das Form. **Seiteneffekte:** `json_decode($record->rulejson)` — das Ergebnis wird in eine ungenutzte lokale Variable `$jsonobject` geschrieben (toter Decode). **Bewertung:** B — funktional ausreichend, aber der Decode ist ein No-op.

### `public function execute(stdClass &$sql, array &$params, $testmode = false, $nextruntime = 0): void` — public
- **Zweck:** Kern der Condition: baut die DB-Family-spezifische Such-SQL fuer offene Installment-Zahlungen. Fuegt `paymentstatus=2` (PAYMENT_SUCCESS), `componentname=mod_booking`, `area=option` zu `$params`; normalisiert `numberofdays`→`numberofseconds` (Sub-Tages-Praezision fuer `rule_specifictime`). Fuer Postgres: `RIGHT JOIN local_shopping_cart_history` + `LATERAL jsonb_array_elements(...installments.payments)`; selektiert `uniquid` (concat aus bo.id, ggf. bod.id, payment id + timestamp), userid, datefield/paid/price/payment_id. Fuer MySQL/MariaDB analog mit `JSON_TABLE`. `where` filtert `paid=0 AND installments>0 AND paymentstatus AND json nicht leer`; im Testmodus zusaetzlich exakter `userid`+`timestamp`-Match, sonst `timestamp >= now + numberofseconds`. **Seiteneffekte:** `$DB->get_dbfamily()`, `$DB->sql_concat(...)`; mutiert `$sql->select/from/where` und `$params` per Referenz. **Rueckgabe:** void. **Bewertung:** D — sehr schwergewichtige, dialektgedoppelte JSON-SQL (Postgres LATERAL vs. JSON_TABLE), `RIGHT JOIN` + unkorrelierter LATERAL ueber die gesamte History; kein `default`-Zweig (bei nicht unterstuetzter DB bleibt `$sql` unveraendert — siehe Finding); Wartungslast und Performance-Risiko hoch (P1, deckt sich mit CLASS_INDEX).

## Bewertungs-Resümee
Eine atypische, aber funktional notwendige Condition: sie webt komplexe shopping-cart-spezifische JSON-SQL in das Regel-Query, doppelt in zwei DB-Dialekten gepflegt. Hauptschwaechen: hohe Wartungs-/Performance-Last der JSON-Joins, irrefuehrender Kommentar in `can_be_combined_with_bookingruletype`, fehlender `default`-Zweig in `execute` und der tote `json_decode` in `set_defaults`. Klassen-Score **D / P1**.
