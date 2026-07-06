# bo_subinfo — Methoden-Doku
**Datei:** `classes/bo_availability/bo_subinfo.php` · **LOC:** 497 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
`bo_subinfo` ist das Sub-Pendant zu `bo_info` und kapselt die Verfuegbarkeits-/Prebooking-Logik fuer **Subbookings** (Zusatzbuchungen) einer Booking-Option. Die Klasse laedt dynamisch alle `subconditions`-Klassen per `glob()`, fragt deren Verfuegbarkeit ab, sortiert die blockierenden Bedingungen in Prebooking-Seiten (pre/book/post) und rendert Bedingungs-Meldungen (Preis, Notify-Me-Button, Modals). Kollaborateure: `singleton_service`, die `subconditions\*`-Klassen, `output\col_price`/`button_notifyme`/`prepagemodal`, `booking_option_settings`. Wirkt wie eine teilweise per Copy-Paste aus `bo_info` abgeleitete Klasse mit mehreren Resten toten/inkonsistenten Codes.

## Methoden

### `__construct(booking_option_settings $settings, int $subbookingid)` — public
- **Zweck:** Initialisiert optionid (aus settings), subbookingid und userid (aktueller `$USER`).
- **Seiteneffekte:** liest Global `$USER`. **Bewertung:** A.

### `is_available(?int $optionid = null, int $userid = 0): array` — public
- **Zweck:** Ermittelt Verfuegbarkeit des Subbookings; liefert die Beschreibung der hoechst-priorisierten blockierenden Bedingung.
- **Rueckgabe:** `[$id, $isavailable, $description]` (3er-Array trotz Docblock-`[isavailable, description]`).
- **Seiteneffekte:** keine direkt; delegiert an `get_subcondition_results`.
- **Aufrufkette:** ruft `get_subcondition_results`; gerufen von `get_description`.
- **Bewertung:** C — Rueckgabe-Vertrag inkonsistent zum Docblock; `$description` ist bei `count>0` zwar gesetzt, aber Selektionslogik („id===0 || id>id") ist kryptisch. Smell: bo_subinfo.php:114-120.

### `get_subcondition_results(int $optionid, int $subbookingid, int $userid = 0): array` — public static
- **Zweck:** Instanziiert alle Subconditions, ruft je `get_description`, filtert auf nicht-verfuegbare und sortiert nach Bedingungs-id.
- **Seiteneffekte:** `require_once lib.php`; liest `$USER`/`$CFG`; `singleton_service::get_instance_of_booking_option_settings` (gecachte DB-Lesung).
- **Aufrufkette:** ruft `get_subconditions`; gerufen von `is_available`, `load_pre_booking_page`.
- **Bewertung:** C — `$full` wird berechnet und an `get_description` weitergereicht, ist aber Teil eines invertierten Rechte-Heuristik-Musters (`USER->id == userid ? false : true`); gemischte Verantwortung (Param-Resolve + Instanziierung + Filter + Sort). Smell: bo_subinfo.php:141.

### `get_full_information(?\course_modinfo $modinfo = null)` — public
- **Zweck:** Soll Beschreibung aller Restriktionen liefern.
- **Bewertung:** D — **No-op/toter Code:** gibt in jedem Pfad `''` zurueck (auch wenn `$availability` gesetzt ist). `$this->availability` wird im Konstruktor nie initialisiert. Smell: bo_subinfo.php:197-203.

### `get_description(booking_option_settings $settings, int $subbookingid, $userid = null, $full = false): array` — public
- **Zweck:** Adapter, der schlicht `is_available($settings->id, $userid)` zurueckgibt.
- **Bewertung:** C — Parameter `$subbookingid` und `$full` werden ignoriert; `$userid` default `null` an `int`-Param von `is_available` (Typ-Coercion null→0). Smell: bo_subinfo.php:215-218.

### `add_conditions_to_mform(MoodleQuickForm &$mform, int $optionid)` — public static
- **Zweck:** Fuegt Header + je Subcondition-Formfelder dem mform hinzu.
- **Seiteneffekte:** mutiert `$mform`; `global $DB` deklariert aber **ungenutzt**.
- **Aufrufkette:** ruft `get_subconditions` + je `add_condition_to_mform`.
- **Bewertung:** B — sauber, nur ungenutztes `$DB`. Smell (minor): bo_subinfo.php:228.

### `save_json_conditions_from_form(stdClass &$fromform)` — public static
- **Zweck:** Sammelt je Subcondition ein JSON-Objekt und schreibt es als `$fromform->availability` (JSON-String).
- **Seiteneffekte:** mutiert `$fromform`; persistiert spaeter im Feld `booking_options.availability` (Write erfolgt ausserhalb).
- **Bewertung:** B.

### `get_subconditions(): array` — public static
- **Zweck:** Laedt per `glob()` alle Dateien in `subconditions/`, instanziiert die gleichnamigen Klassen.
- **Seiteneffekte:** Dateisystem-Scan (`glob`), `class_exists`/`new` dynamisch; liest `$CFG`.
- **Aufrufkette:** zentral genutzt von `get_subcondition_results`, `add_conditions_to_mform`, `save_json_conditions_from_form`.
- **Bewertung:** C — Filesystem-glob + dynamische Instanziierung bei jedem Aufruf, kein Cache; God-artiges Auto-Discovery. Smell: bo_subinfo.php:283-299.

### `get_condition($conditionname)` — private static
- **Zweck:** Soll eine Condition-Instanz aus `conditions/` erzeugen.
- **Bewertung:** D — **Bug/toter Code:** baut Klassennamen mit `.php`-Suffix (`...\\conditions\\$name.php`) und uebergibt diesen an `class_exists` → trifft nie zu, liefert immer `null`. Zudem im File ungenutzt (keine interne Referenz). Smell: bo_subinfo.php:310-318.

### `load_pre_booking_page(int $optionid, int $pagenumber, int $userid)` — public static
- **Zweck:** Rendert die Prebooking-Seite der zur `$pagenumber` gehoerenden Bedingung.
- **Seiteneffekte:** wirft `moodle_exception('wrongpagenumberforprebookingpage')`; ruft `$condition->render_page`.
- **Aufrufkette:** ruft `get_subcondition_results` + `return_class_of_current_page`.
- **Bewertung:** D — **Bug:** ruft `get_subcondition_results($optionid, $userid)` mit nur 2 Args; `$userid` landet auf dem `$subbookingid`-Parameter, der echte `$userid` faellt auf Default 0. Signatur-Mismatch. Smell: bo_subinfo.php:330.

### `render_conditionmessage(...)` — public static
- **Zweck:** Rendert Bedingungs-Meldung (Alert oder Modal) + optional Preis (`col_price`) + optional Notify-Me-Button.
- **Parameter:** 8 Parameter (description, style, optionid, showprice, optionvalues, shownotificationlist, usertobuyfor, modalfordescription).
- **Seiteneffekte:** `$PAGE->get_renderer`; `singleton_service` (settings + booking_answers, DB-Lesungen); `context_module::instance`; `booking::convert_prices_to_number_format`.
- **Bewertung:** C — lange Parameterliste (8), gemischte Verantwortung (Description-Render + Preis + Notify), mehrere statische God-Calls; `$usertobuyfor->id` ohne Null-Guard bei aktivem notificationlist (potentieller Notice, da `?stdClass`). Smell: bo_subinfo.php:366-419.

### `return_class_of_current_page(array $results, int $pagenumber)` — private static
- **Zweck:** Liefert Klassennamen-String der zur Seitenzahl gehoerenden Bedingung.
- **Bewertung:** B — schlank; greift ungeprueft auf `$conditionsarray[$pagenumber]` zu (Index-Annahme), Aufruferseite faengt empty ab.

### `return_sorted_conditions(array $results)` — public static
- **Zweck:** Sortiert blockierende Bedingungen in pre/book/post-Reihenfolge via `insertpage`-Konstanten; bestimmt zugleich die Gesamt-Seitenzahl.
- **Seiteneffekte:** keine (rein funktional).
- **Bewertung:** B — klar strukturiert, leichte Schachtelung (switch in foreach), aber gut lesbar.

### Triviale Akzessoren
Keine eigenstaendigen Getter/Setter; Property-Zuweisungen ausschliesslich im Konstruktor.
