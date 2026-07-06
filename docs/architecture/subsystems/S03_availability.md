# S03 — availability

## Zweck & Grenzen

Das Subsystem `bo_availability` ist die **Gating-Engine** von mod_booking: Es beantwortet die
Frage „Darf dieser User diese Buchungsoption (jetzt) buchen — und wenn nicht, warum und mit
welchem Button/Modal?". Es besteht aus

- einem zentralen Orchestrator (`bo_info`, analog `bo_subinfo` für Subbookings),
- zwei Vertrags-Interfaces (`bo_condition`, `bo_subcondition`) plus einem optionalen
  Form-Interface (`freezable_condition`),
- ~50 **Conditions** (`conditions/*.php`) und 4 **Subconditions** (`subconditions/*.php`),
- zwei Hilfsklassen für Skip/Freeze-Konfiguration (`condition_state_helper`,
  `condition_visibility_manager`).

Jede Condition ist ein einzelner Gating-Aspekt (z. B. „ist ausgebucht", „Buchungszeitfenster",
„Userprofilfeld erfüllt", „Preis gesetzt", „bereits gebucht"). Der Orchestrator führt ALLE
Conditions aus, sammelt die fehlschlagenden ein und ermittelt über eine **Prioritäts-ID**
(höchste ID gewinnt) die eine Condition, deren Beschreibung/Button dem User angezeigt wird.

**Grenzen:** Das Subsystem entscheidet nur über *Verfügbarkeit/Anzeige*. Die eigentliche
Buchungs-Transaktion (`booking_bookit::bookit`), das Rendering der Buttons-Templates, die
Preisberechnung (`price`), der Warenkorb (`local_shopping_cart`) und die Prepage-Modal-JS liegen
außerhalb, werden aber von hier aus angestoßen. Die SQL-Filter-Erzeugung (`return_sql`) gehört zum
Subsystem, der konsumierende Listen-Query (wunderbyte_table) nicht.

## Position im Gesamtsystem

- **Aufgerufen von:** `booking_bookit` (Buchungsfluss & Button-Rendering), `view.php` /
  Shortcodes / Webservices (`load_pre_booking_page`), den Output-Renderern
  (`col_price`, `bookingoption_description`), Availability-Listenfiltern (`return_sql_from_conditions`).
- **Ruft auf / nutzt:** `singleton_service` (Settings-/Answers-/User-Caching + Result-Memo),
  `booking_option_settings` (Datenträger der Option, inkl. `availability`-JSON), `price`,
  `booking_answers`, `booking_bookit`, `local_shopping_cart\shopping_cart` (optional),
  diverse `output\*`-Renderer, Moodle-Core (`has_capability`, Cohort-/Course-/Competency-APIs).
- **Konfiguration:** Admin-Settings unter `booking` (`skipableconditions`,
  `availabilityconditionsettings`, `usesqlfilteravailability`, `conditionwarningatbottom`,
  `conditionsoverwritingbillboard` …) sowie die Konstanten in `lib.php`
  (`MOD_BOOKING_BO_COND_*`, `MOD_BOOKING_BO_BUTTON_*`, `MOD_BOOKING_BO_PREPAGE_*`,
  `MOD_BOOKING_CONDPARAM_*`).

## Schlüsselkonzepte

- **Prioritäts-ID statt Boolean-Baum:** Jede Condition hat eine feste ganzzahlige `$id`
  (`lib.php:141–215`). Bei `is_available` werden alle fehlschlagenden Conditions gesammelt; die
  mit der **höchsten ID** liefert die angezeigte Beschreibung. Negative IDs sind „Buchungs-Aktionen"
  (Button/Preis/Confirmation: `BOOKITBUTTON -90`, `PRICEISSET -70`, `CONFIRMATION -100`), positive
  IDs sind echte Sperren (`ALREADYBOOKED 150`, `ISCANCELLED 130`, `FULLYBOOKED 90` …).
- **Hardcoded vs. JSON-Condition:** `is_json_compatible()` unterscheidet fest verdrahtete
  Conditions (immer aktiv, als Instanzen) von pro-Option konfigurierbaren Conditions, die als
  JSON im Feld `booking_options.availability` liegen und zur Laufzeit instanziiert werden
  (`bo_info::get_condition_results` Zeile 265–337).
- **Drei Antwort-Achsen je Condition:** `get_description()` liefert ein 4-Tupel
  `[$isavailable, $description, $insertpage, $button]`. `$insertpage`
  (`MOD_BOOKING_BO_PREPAGE_*`) steuert die Position in der Prepage-Modal-Sequenz, `$button`
  (`MOD_BOOKING_BO_BUTTON_*`) steuert die Button-Darstellung.
- **Soft- vs. Hard-Block:** `is_available()` baut die Prebooking-Modals auf (z. B. Policy,
  Subbooking-Seite); `hard_block()` ist die finale Sperre unmittelbar vor dem Buchen und wird nur
  ausgewertet, wenn `is_available` bereits false war und `$onlyhardblock` gesetzt ist.
- **Override-Mechanik:** JSON-Conditions können andere Conditions via `overrides` /
  `overrideoperator` (`AND`/`OR`) umkippen (`bo_info::get_condition_results` Zeile 339–402) — ein
  kleiner verschachtelter Boolean-Evaluator innerhalb des sonst flachen Prioritätsmodells.
- **Skip & Freeze:** Conditions können administrativ deaktiviert (skip), nur im Formular
  eingefroren (freeze) oder skip+freeze gesetzt werden (`condition_state_helper` Tri-State 0/1/2).
  `freezable_condition` erlaubt es Conditions, ihre Formularelemente selbst zu deklarieren, statt
  per Switch im Manager.
- **Per-Request-Memo:** `get_condition_results` cached pro Option/User/Flags auf
  `singleton_service->conditionresults`; invalidiert beim Zerstören der Booking-Answers.
- **SQL-Spiegelung:** Conditions können via `return_sql()` ihre Logik als SQL beisteuern, damit
  Optionen in Listen nicht nur geblockt, sondern komplett ausgeblendet werden
  (`bo_info::return_sql_from_conditions`, gated durch `usesqlfilteravailability`).

## Datenfluss

1. Buchungsfluss/Anzeige ruft `bo_info::is_available()` bzw. statisch
   `bo_info::get_condition_results()`.
2. Orchestrator lädt Hardcoded-Conditions (`get_available_conditions(HARDCODED_ONLY)`) und merged
   die pro-Option-JSON-Conditions aus `settings->availability` hinzu; skip-Conditions werden via
   `exclude_conditions()` / `condition_state_helper` entfernt.
3. Für jede Condition wird `get_description()` aufgerufen → 4-Tupel. Bei `$onlyhardblock` wird
   `hard_block()` zum Überschreiben benutzt. Override-Conditions werden nachträglich verrechnet.
4. Nur die fehlschlagenden Ergebnisse (`isavailable == false`) bleiben übrig, nach ID sortiert.
5. `is_available()` wählt das Ergebnis mit höchster ID (mit Cashier-/bookforothers-Sonderfällen)
   und gibt `[$id, $isavailable, $description]` zurück.
6. Für Prepage-Modals: `load_pre_booking_page()` sortiert die blockierenden Conditions in
   pre/book/post-Seiten (`return_sorted_conditions`), triggert ggf. die eigentliche Buchung
   (`booking_bookit::bookit` oder `shopping_cart::add_item_to_cart`) und rendert über die jeweilige
   Condition `render_page()` plus Header/Footer-Templates.
7. Formular-Seite: `add_conditions_to_mform()` / `save_json_conditions_from_form()` schreiben die
   JSON-Conditions ins Feld `availability`; `condition_visibility_manager` friert/versteckt
   Felder gemäß Skip/Freeze-State.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| bo_info.php | bo_info | Orchestrator/God-Class | 1624 | 30 | D | P1 |
| bo_subinfo.php | bo_subinfo | Orchestrator (Subbookings) | 497 | 12 | C | P2 |
| bo_condition.php | bo_condition (Interface) | Vertrag Condition | 184 | 11 | A | - |
| bo_subcondition.php | bo_subcondition (Interface) | Vertrag Subcondition | 137 | 5 | A | - |
| freezable_condition.php | freezable_condition (Interface) | Form-Element-Deklaration | 50 | 1 | A | - |
| condition_state_helper.php | condition_state_helper | Skip/Freeze-Tri-State-Resolver | 158 | 5 | B | P3 |
| condition_visibility_manager.php | condition_visibility_manager | mform Freeze/Hide-Anwendung | 210 | 7 | B | P3 |
| conditions/bookitbutton.php | bookitbutton | Aktion: Bookit-Button (id −90) | 365 | 13 | B | P3 |
| conditions/confirmation.php | confirmation | Aktion: Abschluss-Seite (id −100) | 270 | 11 | B | - |
| conditions/confirmbookit.php | confirmbookit | Aktion: Bestätigung Bookit (id −80) | 286 | 11 | B | - |
| conditions/priceisset.php | priceisset | Aktion: Preis/Warenkorb (id −70) | 340 | 12 | C | P3 |
| conditions/noshoppingcart.php | noshoppingcart | Sperre: Preis ohne Cart (id −60) | 253 | 11 | B | - |
| conditions/bookwithcredits.php | bookwithcredits | Aktion: Buchen mit Credits (id −50) | 361 | 12 | C | P3 |
| conditions/confirmbookwithcredits.php | confirmbookwithcredits | Aktion: Bestätigung Credits (id −40) | 285 | 11 | B | - |
| conditions/bookwithsubscription.php | bookwithsubscription | Aktion: Buchen mit Abo (id −30) | 360 | 12 | C | P3 |
| conditions/confirmbookwithsubscription.php | confirmbookwithsubscription | Aktion: Bestätigung Abo (id −20) | 283 | 11 | B | - |
| conditions/electivebookitbutton.php | electivebookitbutton | Aktion: Wahlfach-Bookit (id −10) | 321 | 12 | C | P3 |
| conditions/electivenotbookable.php | electivenotbookable | Sperre: Wahlfach gesperrt (id −5) | 331 | 12 | C | P3 |
| conditions/askforconfirmation.php | askforconfirmation | Warteliste/Bestätigung (id 0) | 390 | 12 | C | P3 |
| conditions/confirmaskforconfirmation.php | confirmaskforconfirmation | Bestätigung Warteliste (id 1) | 280 | 11 | B | - |
| conditions/slotbooking.php | slotbooking | Slot-Auswahl-Gate (id 2) | 444 | 14 | C | P2 |
| conditions/capbookingchoose.php | capbookingchoose | Capability-Gate (id 4) | 249 | 11 | B | - |
| conditions/instanceavailability.php | instanceavailability | Instanz-Verfügbarkeit (id 5) | 291 | 11 | B | - |
| conditions/hascompetency.php | hascompetency | JSON: Kompetenz erforderlich (id 10) | 610 | 16 | D | P2 |
| conditions/userprofilefield_1_default.php | userprofilefield_1_default | JSON: Standard-Profilfeld (id 11) | 710 | 16 | D | P2 |
| conditions/userprofilefield_2_custom.php | userprofilefield_2_custom | JSON: Custom-Profilfeld (id 12) | 1096 | 17 | E | P1 |
| conditions/previouslybooked.php | previouslybooked | JSON: zuvor gebucht (id 13) | 596 | 16 | D | P2 |
| conditions/selectusers.php | selectusers | JSON: Userliste (id 14) | 572 | 16 | D | P2 |
| conditions/enrolledincourse.php | enrolledincourse | JSON: Kurseinschreibung (id 15) | 757 | 16 | D | P2 |
| conditions/customform.php | customform | JSON: Custom-Formular (id 16) | 988 | 19 | E | P1 |
| conditions/enrolledincohorts.php | enrolledincohorts | JSON: Cohort-Mitgliedschaft (id 17) | 738 | 16 | D | P2 |
| conditions/allowedtobookininstance.php | allowedtobookininstance | JSON: Instanz-Buchlimit (id 18) | 548 | 17 | D | P2 |
| conditions/maxoptionsfromcategory.php | maxoptionsfromcategory | Limit pro Kategorie (id 28) | 500 | 17 | D | P2 |
| conditions/nooverlappingproxy.php | nooverlappingproxy | Hardcoded-Proxy zu nooverlapping (id 29) | 601 | 17 | D | P2 |
| conditions/nooverlapping.php | nooverlapping | JSON: Terminüberschneidung (id 30) | 596 | 18 | D | P2 |
| conditions/otheroptionsavailable.php | otheroptionsavailable | Andere Optionen verfügbar (id 31) | 296 | 11 | B | - |
| conditions/subbooking.php | subbooking | Subbooking-Gate (id 40) | 291 | 11 | B | P3 |
| conditions/subbooking_blocks.php | subbooking_blocks | Subbooking blockiert (id 45) | 296 | 11 | B | P3 |
| conditions/bookingpolicy.php | bookingpolicy | Buchungsrichtlinie (id 50) | 303 | 11 | B | - |
| conditions/booking_time.php | booking_time | Buchungszeitfenster (id 60) | 1352 | 19 | E | P1 |
| conditions/optionhasstarted.php | optionhasstarted | Option bereits gestartet (id 70) | 273 | 11 | B | - |
| conditions/campaign_blockbooking.php | campaign_blockbooking | Kampagnen-Sperre (id 71) | 279 | 11 | B | - |
| conditions/isloggedin.php | isloggedin | Login erforderlich (id 74) | 302 | 11 | B | - |
| conditions/isloggedinprice.php | isloggedinprice | Login bei Preis (id 75) | 315 | 12 | B | - |
| conditions/max_number_of_bookings.php | max_number_of_bookings | Max. Buchungen pro User (id 80) | 288 | 11 | B | - |
| conditions/fullybooked.php | fullybooked | Ausgebucht (id 90) | 319 | 12 | B | P3 |
| conditions/notifymelist.php | notifymelist | Benachrichtigungsliste (id 100) | 329 | 12 | C | P3 |
| conditions/alreadyreserved.php | alreadyreserved | Bereits reserviert (id 102) | 302 | 11 | B | - |
| conditions/bookondetail.php | bookondetail | Buchen nur auf Detailseite (id 104) | 304 | 11 | B | - |
| conditions/cancelmyself.php | cancelmyself | Selbst-Stornierung (id 105) | 455 | 13 | C | P2 |
| conditions/onwaitinglist.php | onwaitinglist | Auf Warteliste (id 110) | 335 | 12 | B | - |
| conditions/isbookable.php | isbookable | Option buchbar-Flag (id 120) | 271 | 11 | B | - |
| conditions/isbookableinstance.php | isbookableinstance | Instanz buchbar (id 125) | 268 | 11 | B | - |
| conditions/iscancelled.php | iscancelled | Option storniert (id 130) | 276 | 11 | B | - |
| conditions/alreadybooked.php | alreadybooked | Bereits gebucht (id 150) | 355 | 12 | C | P3 |
| conditions/slotmove.php | slotmove | Self-Service Slot-Umbuchung (id 155) | 234 | 14 | B | P3 |
| conditions/confirmcancel.php | confirmcancel | Storno-Bestätigung (id 170) | 330 | 12 | C | P3 |
| subconditions/bookitbutton.php | bookitbutton (sub) | Sub: Bookit-Button | 205 | 7 | B | - |
| subconditions/alreadybooked.php | alreadybooked (sub) | Sub: bereits gebucht | 233 | 8 | B | - |
| subconditions/isbookable.php | isbookable (sub) | Sub: buchbar | 234 | 8 | B | - |
| subconditions/priceisset.php | priceisset (sub) | Sub: Preis gesetzt | 221 | 8 | B | - |

Score-Heuristik: A = sauberes Interface / kleine SRP-Klasse; B = typische Condition, viel
Boilerplate aber überschaubar; C = >300 LOC oder mehrere Verantwortlichkeiten (Form+SQL+Render);
D = >500 LOC mit großem `add_condition_to_mform`/`return_sql`; E = >950 LOC, mehrere lange Methoden,
SQL-Generierung + Form + Persistenz + Render in einer Klasse.

## Methoden-Inventar (nicht-triviale Klassen)

### bo_info (Orchestrator, `bo_info.php`)
Zentrale God-Class. Trägt zugleich Constants-Definition, Condition-Loading, Evaluations-Loop,
Override-Logik, Prepage-Sequenzierung, Button-Rendering, SQL-Aggregation, mform-Save und
DB-JSON-Helfer.
- `set_enrollink_context(bool): void` (static, public) — setzt statisches Flag für Enrollink-Kontext (beeinflusst Skip-Liste). `bo_info.php:90`
- `__construct(booking_option_settings)` (public) — merkt optionid + aktuellen $USER. `:100`
- `is_available(?int,int,bool,bool,array): array` (public) — Haupteinstieg; wählt das blockierende Ergebnis mit höchster ID, inkl. Cashier/bookforothers-Bypass. `:134`
- `get_condition_results(?int,int,bool,array): array` (static, public) — Kern-Loop über alle Conditions, Override-Verrechnung, Per-Request-Memo. ~220 LOC. `:199`
- `get_full_information(?course_modinfo): string` (public) — Stub, liefert leer. `:435`
- `get_description(booking_option_settings,…): array` (public) — Delegiert an `is_available`. `:452`
- `add_conditions_to_mform(MoodleQuickForm,int,?moodleform): void` (static, public) — fügt mform-Conditions ein, wendet Skip/Freeze via `condition_visibility_manager` an. `:465`
- `set_defaults(stdClass,$jsonobject): void` (static, public) — lädt Defaults je JSON-Condition. `:490`
- `save_json_conditions_from_form(stdClass): void` (static, public) — serialisiert JSON-Conditions ins Feld `availability`, setzt `sqlfilter`-Flag. `:510`
- `return_sql_from_conditions(int): array` (static, public) — aggregiert `return_sql()` aller mform-Conditions zu einem WHERE inkl. „bereits gebucht"-Bypass; gated durch `usesqlfilteravailability` + Capability-Override. `:563`
- `get_conditions(int): array` (static, public) — instanziiert alle Condition-Klassen, filtert nach CONDPARAM. `:647`
- `get_available_conditions(int): array` (static, public) — wie oben + `exclude_conditions`. `:703`
- `get_condition($name): ?object` (static, private) — Einzel-Instanz (Namensbug: hängt `.php` an Klassennamen). `:715`
- `load_pre_booking_page(int,int,int,string): array` (static, public) — baut Prepage-Modal-Sequenz, triggert ggf. echte Buchung/Cart, rendert Header+Condition+Footer. ~100 LOC. `:733`
- `render_conditionmessage(string,…): string` (static, public) — rendert Preis/Notify-Button-Box (Description-Teil auskommentiert). `:848`
- `render_button(booking_option_settings,…): array` (static, public) — Standard-Bookit-Button-Datenbau inkl. Preis-/Notify-Logik. ~130 LOC. `:923`
- `return_sorted_priceitems($itemid,$userid): array` (static, private) — sortiert Preisitems nach Kategorie. `:1067`
- `apply_billboard(bo_condition,booking_option_settings): string` (static, public) — überschreibt Warnung mit Billboard-Text. `:1103`
- `return_sorted_conditions(array,string): array` (static, public) — sortiert blockierende Conditions in pre/book/post-Seiten, unterdrückt 1-Seiten-Modals. ~100 LOC. `:1132`
- `return_data_for_steps(array,int): array` (static, private) — Tab-Daten der Prepage-Schritte. `:1242`
- `return_class_of_current_page(array,int): string` (static, private) — Classname der aktuellen Seite. `:1273`
- `has_price_set(array): bool` / `booked_on_waitinglist(array): bool` (static, private) — Suchen nach priceisset/askforconfirmation in Ergebnissen. `:1285/:1300`
- `add_continue_button(...)` / `add_back_button(...)` (static, private) — Footer-Button-Logik der Prepage-Modals inkl. Checkout/Slotmove-Sonderfälle. `:1321/:1419`
- `check_for_sqljson_key_in_array(...)` / `check_for_sqljson_key_in_object(...)` (static, public) — DB-dialektabhängige JSON-Extraktion (Postgres/MySQL). `:1453/:1480`
- `validation(array,array,array): array` (static, public) — ruft `validation()` der gespeicherten JSON-Conditions. `:1512`
- `get_for_user_button_string(int|string): string` (static, public) — „buchen für …"-Label. `:1544`
- `get_skippable_conditions(): array` (static, public) — id→Name aller skippbaren Conditions (Settings-UI). `:1562`
- `get_condition_classes(): array` (static, private) — Namespace-Scan via `core_component`. `:1583`
- `exclude_conditions(array): void` (static, private) — entfernt skip-Conditions via `condition_state_helper`. `:1599`
- `destroy_singletons(): void` (static, public) — Reset aller Condition-Singletons (Tests/Caching). `:1615`

### bo_subinfo (`bo_subinfo.php`)
Spiegel von `bo_info` für Subbooking-Availability. Deutlich schlanker, ohne Override/Memo/SQL.
Subconditions werden via `glob()` geladen (statt `core_component`).
- `is_available(?int,int): array`, `get_subcondition_results(int,int,int): array` (static) — Evaluations-Loop ohne hard_block/override. `:99/:134`
- `get_description(...)`, `get_full_information(...)` — Delegation/Stub. `:215/:197`
- `add_conditions_to_mform(...)`, `save_json_conditions_from_form(...)` — Subbooking-Formular. `:227/:250`
- `get_subconditions(): array` (static) — `glob` über `subconditions/*.php`. `:278`
- `load_pre_booking_page(...)`, `return_class_of_current_page(...)`, `return_sorted_conditions(...)` — Prepage-Sortierung. `:328/:429/:445`
- `render_conditionmessage(...)` — Description/Preis/Notify-Rendering (hier mit Description, anders als bo_info). `:366`

### bo_condition / bo_subcondition / freezable_condition (Interfaces)
- `bo_condition`: definiert den vollen Condition-Vertrag — `is_json_compatible`, `is_shown_in_mform`,
  `is_available`, `hard_block`, `get_description`, `add_condition_to_mform`, `render_page`,
  `render_button`, `return_sql`, `get_id`, `get_name`, `is_skippable`. `bo_condition.php:42`
- `bo_subcondition`: reduzierter Vertrag für Subbooking-Conditions (mit `$subbookingid`-Parameter),
  ohne `hard_block`/`return_sql`/`render_page`. `bo_subcondition.php:47`. Lädt redundant
  `bo_info.php` per `require_once` (Kommentar Zeile 34: Kompat nach Konstanten-Dedup).
- `freezable_condition`: ein einziger Hook `get_condition_form_elements(): string[]` — erlaubt
  Conditions, ihre Form-Elemente für Freeze/Hide selbst zu deklarieren. `freezable_condition.php:38`

### condition_state_helper (`condition_state_helper.php`)
Auflösung des Tri-State (0 inaktiv / 1 freeze / 2 skip+freeze) je Condition-ID aus Config, mit
Legacy-Mapping (`skipableconditions`/`enrollinkskipconditions` → skip+freeze).
- `get_condition_state(int,bool): int` — Hauptauflösung (neues Format vor Legacy). `:55`
- `should_skip_condition(int,bool): bool` / `should_freeze_condition(int,bool): bool` — abgeleitete Booleans. `:76/:87`
- `get_configured_states(): array` (private) — JSON-Config-Decode inkl. zweier Backward-Compat-Pfade. `:97`
- `get_legacy_skipped_conditions(bool): array` (private) — Legacy-Liste, Enrollink-Defaults. `:134`

### condition_visibility_manager (`condition_visibility_manager.php`)
Wendet Skip/Freeze auf das Options-mform an (Freeze + Warnhinweis für Berechtigte, Hide für andere).
- `get_skipped_conditions(): array`, `is_condition_frozen(int): bool`, `is_condition_skipped(int): bool` — Status-Abfragen über `condition_state_helper`. `:45/:156/:206`
- `disable_elements_in_mform(MoodleQuickForm,bo_condition,bool): void` — Capability-gesteuertes Freeze vs. Hide. `:133`
- `freeze_fields_for_condition(...)`, `hide_fields_for_condition(...)` — wenden über `freezable_condition::get_condition_form_elements()` an, platzieren Warnung (Top oder Bottom via `conditionwarningatbottom`). `:70/:119`
- `disable_element_without_warning(...)`, `hide_element(...)` (private) — Element-Manipulation; `hide_element` injiziert inline-`<script>` zum Entfernen des `<hr>`. `:170/:185`

### Conditions — gemeinsames Muster
Jede Condition implementiert `bo_condition`, trägt eine feste `$id` und das Boilerplate
(`get_id`/`get_name`/`is_json_compatible`/`is_shown_in_mform`/`is_skippable`/`return_sql`).
Die fachliche Logik steckt in `is_available()` (+ `hard_block()`), die Anzeige in
`get_description()` (4-Tupel) und `get_description_string()`, die Buchungs-UI in `render_button()`
/ `render_page()`.

**Hardcoded-Conditions** (`is_json_compatible() === false`, immer aktiv, kein mform-Save):
- *Aktionen/Buttons* (negative IDs): `bookitbutton`, `confirmation`, `confirmbookit`,
  `bookwithcredits`/`confirmbookwithcredits`, `bookwithsubscription`/`confirmbookwithsubscription`,
  `electivebookitbutton`/`electivenotbookable`, `noshoppingcart`. Geben i. d. R. konstant
  `is_available=false` zurück und steuern primär `$button`/`$insertpage`. `bookitbutton` ist
  außerdem Single-Source-of-Truth der „book-intent"-Override-IDs (`get_book_intent_override_condition_ids`).
- *Status-Sperren* (positive IDs): `alreadybooked`, `alreadyreserved`, `onwaitinglist`,
  `fullybooked`, `iscancelled`, `isbookable`, `isbookableinstance`, `instanceavailability`,
  `max_number_of_bookings`, `optionhasstarted`, `campaign_blockbooking`, `isloggedin`,
  `isloggedinprice`, `bookondetail`, `otheroptionsavailable`, `capbookingchoose`, `bookingpolicy`,
  `notifymelist`, `askforconfirmation`/`confirmaskforconfirmation`, `subbooking`/`subbooking_blocks`,
  `cancelmyself`/`confirmcancel`, `slotbooking`/`slotmove`.

**JSON-Conditions** (`is_json_compatible() === true`, pro Option konfigurierbar, mit
`instance()`/`reset_instance()`-Singleton, `get_condition_object_for_json()`, `set_defaults()`,
großem `add_condition_to_mform()` und eigenem `return_sql()`):
`allowedtobookininstance`, `enrolledincohorts`, `enrolledincourse`, `hascompetency`,
`previouslybooked`, `selectusers`, `userprofilefield_1_default`, `userprofilefield_2_custom`,
`nooverlapping`, `customform`. Mehrere implementieren zusätzlich `freezable_condition`.
`nooverlappingproxy` und `maxoptionsfromcategory` sind Hardcoded-Wrapper, die JSON-Logik der
zugehörigen Condition spiegeln (`is_json_compatible() === false`, aber gleiche Struktur).

**Besonders große/komplexe Conditions** (eigene Abschnitte wert):
- `booking_time` (1352 LOC): Buchungs-Öffnungs-/Schließzeit; mform-Aufbau (`add_condition_to_mform`
  ~330 LOC, `:308`), Persistenz-Normalisierung (`resolve_persistence_data` static `:856`,
  `upsert_condition_in_availability` static `:1116`), relative/absolute Zeitmodi, eigener
  `return_sql` (`:177`). Mischt Domänenlogik, Persistenz, Form und SQL → höchster Refactor-Druck.
- `userprofilefield_2_custom` (1096 LOC): Custom-Profilfeld-Vergleich; `compare_operation`
  (`:231`)/`compare_fields` (`:333`) plus ~250 LOC `return_sql` (`:374`) mit dialektabhängiger
  JSON-/Operator-Erzeugung. `userprofilefield_1_default` (710) analog für Standardfelder.
- `customform` (988 LOC): rendert beliebige Custom-Form-Elemente in die Buchung; schreibt
  Antworten in den Booking-Answer (`add_json_to_booking_answer` static `:755`,
  `get_customform_field_value` static `:908`, `validation` static `:985`).
- `enrolledincourse` (757) / `enrolledincohorts` (738) / `hascompetency` (610) /
  `previouslybooked` (596) / `selectusers` (572) / `allowedtobookininstance` (548): jeweils großer
  `add_condition_to_mform` + `return_sql`, Core-API-Kopplung (enrol/cohort/competency).
- `nooverlapping` (596) + `nooverlappingproxy` (601): Terminüberschneidungs-Prüfung; Proxy ist
  Hardcoded-Variante derselben Logik (Code-Duplikat).
- `cancelmyself` (455): Selbst-Storno inkl. `apply_coolingoff_period` (static `:442`).
- `slotbooking` (444): Slotbooking-Gate inkl. License-Check (`is_slot_booking_available_in_license`),
  `add_json_to_booking_answer` (static).

### Subconditions (`subconditions/*.php`)
Vier Subbooking-Pendants, die `bo_subcondition` implementieren: `bookitbutton`, `alreadybooked`,
`isbookable`, `priceisset`. Struktur identisch zu den Hauptconditions, aber mit `$subbookingid`-
Parameter, ohne `hard_block`/`return_sql`/`render_page`/`is_skippable`-Pflichten. Teilen die
`MOD_BOOKING_BO_COND_*`-IDs mit den gleichnamigen Hauptconditions (Namens-/ID-Kollision nur durch
Namespace getrennt).

## Persistenz

- **DB-Tabelle `booking_options`:** Feld `availability` (JSON-Array der konfigurierten
  JSON-Conditions), Feld `sqlfilter` (Flag, ob mind. eine Condition SQL beisteuert —
  `MOD_BOOKING_SQL_FILTER_ACTIVE_JSON_BO`). Geschrieben in
  `bo_info::save_json_conditions_from_form` (`:510`).
- **DB-Tabelle `booking_answers`:** gelesen für Buchungsstatus (`alreadybooked`, `onwaitinglist`,
  `fullybooked` …); `customform`/`slotbooking` schreiben Zusatz-JSON in den Answer-Datensatz
  (`add_json_to_booking_answer`). Bypass-SQL in `return_sql_from_conditions` referenziert
  `booking_answers` direkt.
- **Plugin-Config (`get_config('booking', …)`):** `skipableconditions`,
  `enrollinkskipconditions`, `availabilityconditionsettings` (neu, JSON-Tri-State),
  `availabilityconditionstates` (Legacy), `usesqlfilteravailability`, `conditionwarningatbottom`,
  `conditionsoverwritingbillboard`, `turnoffmodals`, `bookonlyondetailspage`,
  `showpriceifnotloggedin`, `priceisalwayson`, `displayemptyprice`.
- **Cache/Memo:** `singleton_service->conditionresults[$optionid][$key]` (Per-Request-Memo der
  Condition-Ergebnisse); `singleton_service`-Caches für Settings/Answers/User/Booking. Condition-
  Singletons (`instance()`/`destroy_instance()`/`reset_instance()`).

## Extension-Points

- **`bo_condition` / `bo_subcondition` (Interfaces):** neue Conditions = neue Datei in
  `conditions/` bzw. `subconditions/`; werden automatisch via
  `core_component::get_component_classes_in_namespace` (Hauptconditions) bzw. `glob()`
  (Subconditions) entdeckt. Eine neue ID muss in `lib.php` definiert werden.
- **`freezable_condition`:** Opt-in-Interface, damit eine Condition ihre mform-Elemente für
  Freeze/Hide selbst deklariert (ersetzt zentralen Switch im `condition_visibility_manager`).
- **`return_sql()`:** Erweiterungspunkt, um Gating in Listen-Queries zu spiegeln (Ausblenden statt
  nur Blocken).
- **`overrides`/`overrideoperator` (JSON):** erlauben, dass eine Condition das Ergebnis anderer
  Conditions umkippt (AND/OR).
- **Billboard (`apply_billboard`):** überschreibt Condition-Warnungen instanzweit mit
  konfiguriertem Text.
- **Skip/Freeze-Config:** Conditions können administrativ deaktiviert/eingefroren werden
  (`condition_state_helper`), inkl. Enrollink-spezifischer Sonderbehandlung.

## Bekannte Schulden (→ Blueprint)

- **`bo_info` ist eine God-Class (1624 LOC, 30 Methoden):** vermischt mind. 6 Verantwortungen —
  Konstanten-Definition (`bo_info.php:47–59`), Condition-Loading/-Discovery (`:647–722`),
  Evaluations-Loop (`:199`), Override-Evaluator (`:339–402`), Prepage-/Modal-Sequenzierung
  (`:733–1442`), Button-/SQL-/DB-JSON-Helfer (`:923,:563,:1453`). Kandidat für Aufspaltung in
  `condition_registry`, `condition_evaluator`, `prepage_builder`, `availability_sql_builder`. (P1)
- **`get_condition_results` zu lang/komplex (~220 LOC, `:199–418`):** doppelter Instanziierungs-
  Pfad (stdClass vs. Instanz, `:276`/`:293`), verschachtelte Override-Logik mit
  „reciprocal"/`isavailable:original`-Heuristik schwer testbar. (P1)
- **`booking_time` (1352 LOC) und `userprofilefield_2_custom` (1096), `customform` (988):**
  Domänenlogik + Persistenz + ~330-LOC-Formularaufbau + dialektabhängige SQL-Erzeugung in je einer
  Klasse. `booking_time.php:308` (mform), `:856`/`:1116` (Persistenz/static),
  `userprofilefield_2_custom.php:374` (~250-LOC `return_sql`). (P1)
- **Code-Duplikation Proxy-Conditions:** `nooverlappingproxy.php` ist eine Hardcoded-Kopie von
  `nooverlapping.php` (gleiche `get_string_with_url`, `has_valid_timing`, `set_data`); analog
  `maxoptionsfromcategory` als „is_json_compatible=false"-Variante. (P2)
- **Massiv-Boilerplate über ~50 Conditions:** identische Getter/`return_sql`-Stubs/`render_page`-
  Stubs in jeder Datei (je 250–400 LOC, davon ~150 Boilerplate). Eine abstrakte Basisklasse
  (`abstract bo_condition_base`) statt des reinen Interface würde tausende LOC sparen. (P2)
- **`is_json_compatible()`-Semantik widersprüchlich kommentiert:** `nooverlapping.php:131`
  `return true; // Hardcoded condition.` und `maxoptionsfromcategory.php:138`
  `return false; // Hardcoded condition.` — irreführende Kommentare, Marker-Bedeutung unklar. (P3)
- **`bo_info::get_condition()` (`:715`) wirkt tot/fehlerhaft:** hängt `.php` an einen Klassennamen
  und ruft damit `class_exists` — kann nie greifen; private, scheinbar ungenutzt. (P3)
- **`hide_element` injiziert inline-`<script>`** (`condition_visibility_manager.php:195`) zum
  Entfernen des `<hr>` — fragiler DOM-Hack im Formular. (P3)
- **`bo_subcondition.php:35` `require_once(bo_info.php)`** als Kompat-Krücke nach Konstanten-Dedup —
  versteckte Kopplung Interface→Orchestrator. (P3)
- **Doppelter Config-Key/Backward-Compat-Wildwuchs in `condition_state_helper`:** drei Formate
  (`availabilityconditionsettings` / `availabilityconditionstates` / Legacy `skipableconditions`)
  parallel unterstützt (`:97–157`). (P3)
- **Fehlende gezielte Unit-Tests pro Condition:** Gating wird primär über Integrationstests/Behat
  abgedeckt; die großen `return_sql`-Pfade (dialektabhängig) sind schwer ohne dedizierte Tests
  abzusichern (vgl. bestehende `condition_sqlfilter_*`-Tests, die nur Teilbereiche treffen). (P2)
</content>
</invoke>
