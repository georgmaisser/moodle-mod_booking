# send_mail_interval — Methoden-Doku
**Datei:** `classes/booking_rules/actions/send_mail_interval.php` · **LOC:** 279 · **Subsystem:** S06 · **Klassen-Score:** C / P1
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`send_mail_interval` implementiert `booking_rule_action` und realisiert **gestaffelten Mailversand** fuer Wartelisten-Szenarien: statt alle Empfaenger sofort zu benachrichtigen, wird immer nur der naechste noch nicht behandelte User benachrichtigt und die Kette per Intervall (Minuten) in die Zukunft verlaengert. Der Zustand der Kette (`intervaldata`: `nextruntime`, `usersalreadytreated[]`, `interval`) wird in den `rulejson`-Blob hineingeschrieben und mit jedem Adhoc-Task weitergereicht. Die Instanz wird von `rule_react_on_event::execute` ueber die `foreach ($records ...)`-Schleife **wiederverwendet**, sodass die Instanz-Property `$counter` ueber die Records einer Ausfuehrung hinweg persistiert (0 = erster User sofort, 1 = zweiter User verzoegert + `repeat`, >1 = Abbruch). Pro Schritt werden zwei Adhoc-Tasks erzeugt: `confirm_bookinganswer` (Bestaetigungs-Settings) und `send_mail_by_rule_adhoc` (Versand). Kollaborateure: `confirm_bookinganswer` (gleicher Namespace), `send_mail_by_rule_adhoc`, `placeholders_info`, `\core\task\manager`.

## Methoden

### `public function set_actiondata(stdClass $record)` — public
- **Zweck:** Adapter — delegiert an `set_actiondata_from_json($record->rulejson)`. **Bewertung:** A.

### `public function set_actiondata_from_json(string $json)` — public
- **Zweck:** Dekodiert den Blob, fuellt `subject`/`template`/`interval`. **Seiteneffekte:** Property-Mutation. **Bewertung:** C — `subject`, `template` UND `interval` werden ohne Null-Coalesce gelesen, obwohl `set_defaults`/`save_action` `interval ?? 60` verwenden; bei Alt-JSON ohne `interval` wirft das (Undefined property) bzw. setzt `interval=null` → spaeter `null*60 = 0` Sekunden Verzoegerung (Inkonsistenz/Fehlerquelle).

### `public function add_action_to_mform(MoodleQuickForm &$mform, array &$repeateloptions)` — public
- **Zweck:** Baut das Formular: Warnhinweis (`mailintervalwarning`), Intervall-Textfeld (PARAM_INT, Default 60, Help-Button), Platzhalter-Hilfetext, Betreff, Mail-Editor. **Seiteneffekte:** mutiert `$mform`. **Bewertung:** B — Editor mit `context => null`/`maxfiles => 0` wie bei `send_mail`.

### `public function get_name_of_action($localized = true)` — public
- **Zweck:** Liefert `get_string('sendmailinterval', 'mod_booking')`. **Bewertung:** A — `$localized` ignoriert.

### `public function is_compatible_with_ajaxformdata(array $ajaxformdata = [])` — public
- **Zweck:** Kompatibilitaets-Gate; hart `true`. **Bewertung:** A.

### `public function save_action(stdClass &$data): void` — public
- **Zweck:** Serialisiert interval/subject/template(+format) in `$data->rulejson`. **Seiteneffekte:** mutiert `$data->rulejson`. **Bewertung:** B — `global $DB;` ungenutzt (toter Code); kein json-Fehler-Check.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt Form-Defaults aus `$record->rulejson`, `interval ?? 60`. **Seiteneffekte:** mutiert `$data`. **Bewertung:** B — `subject`/`template`/`templateformat` ohne `??`.

### `public function execute(stdClass $record)` — public
- **Zweck:** Treibt die Staffel-Kette fuer einen Empfaenger-Record. Initialisiert `intervaldata` beim ersten Lauf; bricht ab, wenn `$record->userid` bereits in `usersalreadytreated`. Bei `counter===0`: User merken, sofort versenden. Bei `counter===1`: `repeat=1`, `nextruntime += interval*60` (Verzoegerung). Bei `counter>1`: `return`. Erzeugt danach `confirm_bookinganswer` (mit `set_next_runtime_for_adhoc`/`set_ruleid`/`execute`) und einen `send_mail_by_rule_adhoc` mit dem aktualisierten rulejson; `optiondateid` nur wenn gesetzt. **Seiteneffekte:** zwei Adhoc-Tasks pro behandeltem Record via `reschedule_or_queue_adhoc_task`; mutiert `$this->rulejson` und `$this->counter`. **Bewertung:** C — die Staffel-Logik haengt kritisch davon ab, dass die Aufrufer-Schleife dieselbe Instanz wiederverwendet (sonst bliebe `counter` immer 0 und es waere kein Staffeln); das ist im aktuellen Aufrufer (`rule_react_on_event::execute`) gegeben, aber implizit und fragil. Zudem wird der zweite User (`counter===1`) NICHT in `usersalreadytreated` aufgenommen — bewusst (er wird im verzoegerten Folgelauf erneut als „erster" behandelt), aber ohne Kommentar schwer nachvollziehbar; `intervaldata->nextruntime` wird gespeichert, aber spaeter nie wieder gelesen (toter Zustand).

### Triviale Properties
Sieben oeffentliche Properties (Z.39–57): `actionname`, `rulejson`, `ruleid`, `subject`, `template`, `interval`, `counter` — wobei `counter` Laufzeit-State der Staffelung haelt.

## Bewertungs-Resümee
Konzeptionell wertvolle, aber heikle Action: korrektes Staffeln nur unter der impliziten Voraussetzung instanz-wiederverwendeter Schleife; mehrere Defensiv-/Hygiene-Maengel (ungeschuetztes `interval`/`subject`/`template`, ungenutztes `global $DB;`, toter `intervaldata->nextruntime`-State, kein json-Fehler-Check). Kein direkter Datenverlust, aber Fehl-Timing-Potenzial bei Alt-JSON oder geaendertem Aufrufer. Klassen-Score **C / P1**.
