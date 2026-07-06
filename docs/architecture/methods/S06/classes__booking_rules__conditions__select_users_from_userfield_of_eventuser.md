# select_users_from_userfield_of_eventuser — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_users_from_userfield_of_eventuser.php` · **LOC:** 218 · **Subsystem:** S06 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_users_from_userfield_of_eventuser` implementiert `booking_rule_condition` und bestimmt Empfaenger, indem aus einem **Custom-Profilfeld des Event-Users** (Ausloeser `userid` oder Betroffener `relateduserid`) eine kommaseparierte Liste von User-IDs gelesen und als IN-Filter ins Regel-Query gehaengt wird. Anwendungsfall: ein Textfeld am Event-User enthaelt die IDs der eigentlich zu benachrichtigenden Personen. Persistenz: Konfiguration (`fieldofuserfromevent`, `userfromeventtype`) im `rulejson`. Kollaborateure: `$DB` (`user_info_data`/`user_info_field`, `user`), `profile_get_custom_fields()`, die Schwester-Condition `select_user_from_event` (deren statischer Form-Helfer `add_userselect_to_mform` wiederverwendet wird). Nur fuer event-basierte Regeln vorgesehen (nicht `rule_daysbefore`/`rule_specifictime`).

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Schliesst die zeitgesteuerten Regeltypen aus (kein Event → kein Event-User). **Seiteneffekte:** keine. **Rueckgabe:** false fuer `rule_daysbefore`/`rule_specifictime`, sonst true. **Bewertung:** A — hier deckt sich Kommentar und Logik (anders als in der shopping_cart-Condition).

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt JSON aus DB-Record. **Seiteneffekte:** delegiert an `set_conditiondata_from_json`. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Dekodiert das JSON und uebernimmt `fieldofuserfromevent` und `userfromeventtype` in die Instanz. **Seiteneffekte:** keine externen; setzt Properties. **Bewertung:** B — `json_decode` und Zugriff auf `->conditiondata->...` ohne Null-/isset-Guard; bei fehlendem Feld PHP-Notice (Familienmuster).

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Baut einen Dropdown der **Text**-Custom-Profilfelder (`datatype === 'text'`) und haengt ueber `select_user_from_event::add_userselect_to_mform` die Auswahl Ausloeser/Betroffener an. **Seiteneffekte:** `profile_get_custom_fields()`, `$mform->addElement('select', ...)`. **Rueckgabe:** void. **Bewertung:** C — der gesamte Block (inkl. User-Typ-Auswahl) steht in `if (!empty($userfields))`: existiert *kein* Custom-Profilfeld, wird *gar kein* Eingabefeld gerendert, aber die Condition bleibt waehlbar → spaeter leere Konfiguration ohne Hinweis. Das globale `$DB` wird importiert, aber nicht genutzt.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Lokalisierter (`selectusersfromuserfieldofeventuser`) oder technischer Name. **Seiteneffekte:** `get_string`. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert `fieldofuserfromevent` (aus `$data->fieldofuserfromevent`) und `userfromeventtype` (aus `$data->condition_select_user_from_event_type`) ins `rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`; globales `$DB` importiert, ungenutzt. **Bewertung:** B — `json_decode` ohne Guard; `?? ''`-Defaults vorhanden.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt Formular-Defaults (`bookingruleconditiontype`, gewaehltes Feld, User-Typ) aus dem gespeicherten JSON. **Seiteneffekte:** `json_decode($record->rulejson)`. **Bewertung:** B — Zugriff auf `$jsonobject->conditiondata->...` ohne Guard.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Liest aus `$params['json']` Event-Daten, ermittelt den Ziel-`userid` (per `userfromeventtype`), holt mit einer **Vorabquery** den Rohwert des Profilfelds (`user_info_data` JOIN `user_info_field` auf `shortname`), splittet ihn an Kommata, und haengt `JOIN {user} u ON u.id IN (...)` plus `CONCAT(bo.id,'_',u.id) AS uniqueid, u.id userid` ans Regel-Query. Begruendung im Code: getrennte Queries, weil das ID-Splitting in MariaDB/MySQL inline nicht zuverlaessig funktioniert. **Seiteneffekte:** `$DB->get_field_sql(...)`, `$DB->get_in_or_equal(...)`; mutiert `$sql->select/from` und `$params`. **Rueckgabe:** void (Early-Return bei leerem Rohwert). **Bewertung:** C — sauberes Vorabquery-Muster, aber: bei einem Rohwert, der nur aus Kommata/Whitespace besteht (z.B. `","`), liefert `array_filter(array_map('trim', explode(...)))` ein leeres Array → `get_in_or_equal([])` wirft `coding_exception` (siehe Finding). Zudem ist der Wert ein per-User frei editierbares Textfeld → die IDs sind nicht validiert (Benachrichtigung an beliebige Nutzer moeglich), aber nur per Konfigurations-Capability erreichbar.

## Bewertungs-Resümee
Funktional klar umrissene Condition mit pragmatischem Zwei-Query-Ansatz gegen das MySQL-Split-Problem. Schwaechen: leeres-Array-Pfad in `execute` kann eine `coding_exception` werfen, der Formular-Aufbau verschwindet komplett ohne Custom-Profilfelder, mehrere ungenutzte `$DB`-Importe und durchgaengig fehlende JSON-Null-Guards. Klassen-Score **C / P2**.
