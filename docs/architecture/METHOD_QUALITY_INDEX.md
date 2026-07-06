# mod_booking — METHOD_QUALITY_INDEX

> Aggregierter Methoden-/Klassen-Qualitätsindex über **alle Phase-3-Method-Docs** (768 Dateien). Auto-generiert aus den Doc-Headern (`**Klassen-Score:** X / Pn`) und den darin vermerkten Findings. Quelle je Eintrag verlinkt. Strukturindex: [CLASS_INDEX](CLASS_INDEX.md) · [FILE_INDEX](FILE_INDEX.md).

## 1. Verteilung

**Klassen-Score:** A=104 · B=373 · C=208 · D=41 · E=4

**Refactor-Prio:** P0=2 · P1=47 · P2=210 · P3=471

## 2. Hotlist — P0/P1 & Score D/E (58 Klassen)

Priorisierte Refactor-/Bug-Kandidaten. Belege (file:line) stehen je Eintrag in der verlinkten Doc.

| Prio | Score | Klasse / Datei | Doc | belegte Findings (Auszug) |
|:--:|:--:|---|---|---|
| P0 | E | `mod/booking/classes/booking_option.php` | [doc](methods/S01/classes__booking_option.md) | — |
| P0 | E | `report.php` | [doc](methods/S21/report.md) | 1. **Architektur (P0):** Einzeldatei-God-Controller mit ~1600 Zeilen, der Routing, Autorisierung, SQL-Konstruktion (per String-Konkatenation), Geschaeftsaktionen und Rendering vermischt; faktisch nicht unit-testbar, hohe Aenderungs-/Regress<br>2. **N+1 im Download-Zweig (P2):** Z.1577-1592 ruft je Tabellenzeile `groups_get_user_groups()` + `get_fieldset_select('groups', ...)` - eine Query pro Nutzer. |
| P1 | E | `classes/signinsheet/signinsheet_generator.php` | [doc](methods/S17/classes__signinsheet__signinsheet_generator.md) | — |
| P1 | E | `mod/booking/mod_form.php` | [doc](methods/S21/mod_form.md) | — |
| P1 | D | `amd/src/condition/slotBooking.js` | [doc](methods/S23/amd__src__condition__slotBooking.md) | — |
| P1 | D | `classes/all_userbookings.php` | [doc](methods/S01/classes__all_userbookings.md) | — |
| P1 | D | `classes/bo_availability/bo_info.php` | [doc](methods/S03/classes__bo_availability__bo_info.md) | — |
| P1 | D | `classes/booking_answers/scopes/optionstoconfirm.php` | [doc](methods/S01/classes__booking_answers__scopes__optionstoconfirm.md) | — |
| P1 | D | `classes/booking_bookit.php` | [doc](methods/S01/classes__booking_bookit.md) | `booking_bookit` ist der Dreh- und Angelpunkt von S01, leidet aber unter denselben Smells wie sein Subbooking-Pendant in verschaerfter Form: zwei extrem lange, hochverzweigte Methoden (`render_bookit_template_data`, `bookit`), globale `$PAG |
| P1 | D | `classes/booking_bookit.php` | [doc](methods/S04/classes__booking_bookit.md) | — |
| P1 | D | `classes/booking_option_settings.php` | [doc](methods/S01/classes__booking_option_settings.md) | — |
| P1 | D | `classes/booking_rules/conditions/select_user_shopping_cart.php` | [doc](methods/S06/classes__booking_rules__conditions__select_user_shopping_cart.md) | Eine atypische, aber funktional notwendige Condition: sie webt komplexe shopping-cart-spezifische JSON-SQL in das Regel-Query, doppelt in zwei DB-Dialekten gepflegt. Hauptschwaechen: hohe Wartungs-/Performance-Last der JSON-Joins, irrefuehr |
| P1 | D | `classes/coursecategories.php` | [doc](methods/S01/classes__coursecategories.md) | — |
| P1 | D | `classes/dates.php` | [doc](methods/S01/classes__dates.md) | — |
| P1 | D | `classes/elective.php` | [doc](methods/S01/classes__elective.md) | `elective.php` ist ein historisch gewachsener Mischmasch aus statischen Utilities mit deutlich uneinheitlicher Qualitaet. Positiv: `is_bookable`, `load_combinations`, `return_sorted_array_of_options_from_cache` und die Form-Definitionen sin |
| P1 | D | `classes/elective.php` | [doc](methods/S04/classes__elective.md) | — |
| P1 | D | `classes/local/wizard/booking/booking_skill_mutation_execute_service.php` | [doc](methods/S15/classes__local__wizard__booking__booking_skill_mutation_execute_service.md) | — |
| P1 | D | `classes/local/wizard/booking/booking_skill_support.php` | [doc](methods/S15/classes__local__wizard__booking__booking_skill_support.md) | — |
| P1 | D | `classes/local/wizard/booking/support/booking_mutation_validation.php` | [doc](methods/S15/classes__local__wizard__booking__support__booking_mutation_validation.md) | — |
| P1 | D | `classes/message_controller.php` | [doc](methods/S09/classes__message_controller.md) | `message_controller` ist funktional zentral, aber strukturell stark belastet. Die Hauptlast tragen ein 182-zeiliger God-Constructor (DB-Read, Cache-Purge, Sprachumschaltung, Platzhalter-Rendering in einem) und ein ~224-zeiliges `send_or_que |
| P1 | D | `classes/option/fields/recurringoptions.php` | [doc](methods/S02/classes__option__fields__recurringoptions.md) | — |
| P1 | D | `classes/option/fields/slotbooking.php` | [doc](methods/S02/classes__option__fields__slotbooking.md) | — |
| P1 | D | `classes/output/bookingoption_description.php` | [doc](methods/S10/classes__output__bookingoption_description.md) | — |
| P1 | D | `classes/output/mobile.php` | [doc](methods/S10/classes__output__mobile.md) | — |
| P1 | D | `classes/price.php` | [doc](methods/S05/classes__price.md) | `price` ist eine ueber 1300 LOC grosse God-Klasse mit klar gemischter Verantwortung (mform-UI, Formel-Geschaeftslogik, `booking_prices`-Persistenz, mehrstufiges MUC-Caching, Eventing). Strukturell dominieren zwei Probleme: durchgaengige Dup |
| P1 | D | `classes/shortcodes.php` | [doc](methods/S10/classes__shortcodes.md) | Zweck:** Ruft `$args['service']::execute(...array_values($args))` auf — also eine dynamisch aus dem Shortcode-Argument benannte Klasse/Methode. **Seiteneffekte:** beliebige Service-Ausfuehrung; gegated nur durch `is_siteadmin()`. **Bewertun |
| P1 | D | `classes/table/bookingoptions_wbtable.php` | [doc](methods/S10/classes__table__bookingoptions_wbtable.md) | — |
| P1 | D | `classes/task/confirm_bookinganswer_by_rule_adhoc.php` | [doc](methods/S13/classes__task__confirm_bookinganswer_by_rule_adhoc.md) | Diese Klasse ist die einzige mit substanzieller Geschaeftslogik im Task-Subsystem und entsprechend riskant. Genannte Schwaechen: - **Toter Guard (Z.79-81):** `if (empty($ruleinstance)) return;` ist unerreichbar — direkt zuvor (Z.73) sorgt d |
| P1 | D | `classes/utils/webservice_import.php` | [doc](methods/S22/classes__utils__webservice_import.md) | — |
| P1 | D | `mod/booking/classes/booking.php` | [doc](methods/S01/classes__booking.md) | — |
| P1 | D | `mod/booking/classes/calendar.php` | [doc](methods/S01/classes__calendar.md) | — |
| P1 | D | `mod/booking/classes/output/view.php` | [doc](methods/S10/classes__output__view.md) | — |
| P1 | D | `mod/booking/subscribeusers.php` | [doc](methods/S21/subscribeusers.md) | — |
| P1 | D | `optiontemplatessettings.php` | [doc](methods/S21/optiontemplatessettings.md) | `optiontemplatessettings.php:65-69` — `delete`-Aktion ohne Capability-Pruefung und ohne `confirm_sesskey()`: jeder im Kurs eingeloggte User kann per praepariertem GET (`?id=..&action=delete&optionid=..`) ein Options-Template loeschen — fehl |
| P1 | D | `report2.php` | [doc](methods/S21/report2.md) | Scope-Kaskade ist nachvollziehbar, aber das Skript leidet an drei Problemen: (1) **das Aktivierungs-/PRO-Gate fehlt das `die()`** und ist damit wirkungslos (P1, Security/Funktional), (2) der optiondate- und option-Scope sind zu ~100 Zeilen  |
| P1 | D | `slotrules.php` | [doc](methods/S21/slotrules.md) | Funktionsreicher, aber stark ueberladener Editor-Endpoint mit korrekter Escaping- und Upsert-Logik. Zwei substanzielle Schwaechen: (1) **P2/P1 IDOR** — die Loesch-Pfade fuer Regeln und Preise scopen die uebergebene ID nicht auf `optionid`/K |
| P1 | C | `amd/src/bookit.js` | [doc](methods/S23/amd__src__bookit.md) | — |
| P1 | C | `classes/booking_answers/booking_answers.php` | [doc](methods/S01/classes__booking_answers__booking_answers.md) | — |
| P1 | C | `classes/booking_rules/actions/confirm_bookinganswer.php` | [doc](methods/S06/classes__booking_rules__actions__confirm_bookinganswer.md) | Anspruchsvolle, bewusst inkrementelle WL-Bestaetigungskette. Die Logik ist durchdacht (Nachzuegler-Handling via Repeat-Task, Dedupe ueber `usersalreadytreated`), aber die Korrektheit haengt fragil am Lebenszyklus des aufrufenden Objekts (In |
| P1 | C | `classes/booking_rules/actions/send_mail_interval.php` | [doc](methods/S06/classes__booking_rules__actions__send_mail_interval.md) | Konzeptionell wertvolle, aber heikle Action: korrektes Staffeln nur unter der impliziten Voraussetzung instanz-wiederverwendeter Schleife; mehrere Defensiv-/Hygiene-Maengel (ungeschuetztes `interval`/`subject`/`template`, ungenutztes `globa |
| P1 | C | `classes/enrollink.php` | [doc](methods/S20/classes__enrollink.md) | Strikte Typvergleiche gegen DB-Strings** in `add_consumed_item` (`enrollink.php:219-221`) — die Doppel-Konsum-Schutzpruefung greift faktisch nie; zusammen mit dem toten `consumed === 0`-Zweig (No-op-Update) ist die Idempotenz der Platzverbu |
| P1 | C | `classes/local/slotbooking/slot_mover.php` | [doc](methods/S14/classes__local__slotbooking__slot_mover.md) | — |
| P1 | C | `classes/local/sync/booking_enrolment.php` | [doc](methods/S20/classes__local__sync__booking_enrolment.md) | — |
| P1 | C | `classes/shopping_cart/service_provider.php` | [doc](methods/S05/classes__shopping_cart__service_provider.md) | — |
| P1 | C | `classes/subbookings.php` | [doc](methods/S08/classes__subbookings.md) | Wirkt wie ein unfertiges/teilweise verwaistes Fragment: toter Konstruktor (Cache-Read ohne Wirkung, `$id` nie gesetzt), `user_submit_response` ignoriert den `$json`-Parameter (Datenverlust-Risiko), mehrere Dead-Imports. Funktional riskant t |
| P1 | C | `classes/table/manageusers_table.php` | [doc](methods/S10/classes__table__manageusers_table.md) | — |
| P1 | C | `classes/task/send_mail_by_rule_adhoc.php` | [doc](methods/S13/classes__task__send_mail_by_rule_adhoc.md) | Sicherheits-/Korrektheits-bewusster Rule-Mail-Task mit sorgfaeltiger Re-Validierung und Repeat-Mechanik. Hauptrisiken: das strikte, ganzheitliche JSON-Diff (`!==` bzw. Objekt-Vergleich) kann legitime geplante Mails still unterdruecken (P1), |
| P1 | C | `edit_optiontemplates.php` | [doc](methods/S21/edit_optiontemplates.md) | Zweck:** Bei Cancel -> Redirect. Bei validierten Daten (`$mform->get_data()`): erneute Gate-Pruefung (`confirm_sesskey()` UND (`updatebooking` ODER `addeditownoption`)), Default fuer `limitanswers`, dann **`$nbooking = booking_option::updat |
| P1 | C | `mod/booking/lib.php` | [doc](methods/S22/lib.md) | — |
| P2 | D | `classes/form/customfield.php` | [doc](methods/S16/classes__form__customfield.md) | Funktional arbeitende, aber strukturell problematische Form: Persistenz und DB-Loeschungen liegen im `get_data()`-Lesepfad, der `showcustfields`-`trim` ist ein wirkungsloser No-op (verbleibende Kommata) und die doppelte `disabledif`-Zuweisu |
| P2 | D | `classes/form/dynamicdeputyselect.php` | [doc](methods/S16/classes__form__dynamicdeputyselect.md) | Die Form bündelt Form-Lifecycle, Profilfeld-Persistenz und systemweites Rollen-Enrolment mit mehreren Cross-Plugin-Config-Abhaengigkeiten (`bookingextension_confirmation_supervisor`, `local_taskflow`). Substanzielle Schwaechen: die `LIKE '% |
| P2 | D | `classes/local/sql/operator_builder.php` | [doc](methods/S22/classes__local__sql__operator_builder.md) | — |
| P2 | D | `mod/booking/settings.php` | [doc](methods/S22/settings.md) | — |
| P2 | D | `optionview.php` | [doc](methods/S21/optionview.md) | Sicherheitssensibler oeffentlicher Entry-Point mit absichtlich gelockerter Login-Pflicht. Die Sichtbarkeits-/Policy-Logik ist korrekt gemeint, aber stark verschachtelt und mehrfach wiederholt; ein undefiniertes `$modalcounter` beim Cart-Ini |
| P2 | D | `subbooking_timetabletest.php` | [doc](methods/S21/subbooking_timetabletest.md) | Leftover-Entwicklungs-/Demo-Seite mit hartkodiertem Template-JSON und ohne Capability-Gate, die produktiv ausgeliefert wird. Sollte aus dem Release entfernt oder hinter ein Debug-/Dev-Gate gestellt werden; der `del`-Parameter ist toter Code |
| P2 | D | `teacher_performed_units_report.php` | [doc](methods/S21/teacher_performed_units_report.md) | Inhaltlich solide Aggregations-Logik, aber typischer „SQL-im-Controller, zweimal kopiert"-Report mit Preference-getriebenem Filterzustand. Wartungslast hoch, Datenkorrektheit beim Download-Pfad an User-Preferences gekoppelt. Klassen-Score * |
| P2 | D | `teachers_instance_report.php` | [doc](methods/S21/teachers_instance_report.md) | Funktional sauber abgesicherter Instanz-Report (Modul-Context-Capability, cmid-Validierung, parametrisiertes SQL), aber klassisches Copy-Paste-SQL ueber zwei Pfade mit preference-getriebenem Download-Filter. Klassen-Score **D / P2**. |
| P3 | D | `classes/subbookings/subbookings_cache.php` | [doc](methods/S08/classes__subbookings__subbookings_cache.md) | Toter Platzhalter ohne Verhalten. Kein funktionales Risiko, aber Dead Code: entweder mit der tatsaechlich genutzten Cache-Logik fuellen oder ersatzlos entfernen. Klassen-Score **D / P3**. |

## 3. Vollständiger Index nach Subsystem

### S01 (46 Docs · P0:1 · P1:10 · P2:20 · P3:15)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| E | P0 | `mod/booking/classes/booking_option.php` | [doc](methods/S01/classes__booking_option.md) |
| D | P1 | `classes/all_userbookings.php` | [doc](methods/S01/classes__all_userbookings.md) |
| D | P1 | `classes/booking_answers/scopes/optionstoconfirm.php` | [doc](methods/S01/classes__booking_answers__scopes__optionstoconfirm.md) |
| D | P1 | `classes/booking_bookit.php` | [doc](methods/S01/classes__booking_bookit.md) |
| D | P1 | `classes/booking_option_settings.php` | [doc](methods/S01/classes__booking_option_settings.md) |
| D | P1 | `classes/coursecategories.php` | [doc](methods/S01/classes__coursecategories.md) |
| D | P1 | `classes/dates.php` | [doc](methods/S01/classes__dates.md) |
| D | P1 | `classes/elective.php` | [doc](methods/S01/classes__elective.md) |
| D | P1 | `mod/booking/classes/booking.php` | [doc](methods/S01/classes__booking.md) |
| D | P1 | `mod/booking/classes/calendar.php` | [doc](methods/S01/classes__calendar.md) |
| C | P1 | `classes/booking_answers/booking_answers.php` | [doc](methods/S01/classes__booking_answers__booking_answers.md) |
| C | P2 | `classes/booking_answers/scope_base.php` | [doc](methods/S01/classes__booking_answers__scope_base.md) |
| C | P2 | `classes/booking_answers/scope_base_answers.php` | [doc](methods/S01/classes__booking_answers__scope_base_answers.md) |
| C | P2 | `classes/booking_answers/scope_base_options.php` | [doc](methods/S01/classes__booking_answers__scope_base_options.md) |
| C | P2 | `classes/booking_answers/scopes/course.php` | [doc](methods/S01/classes__booking_answers__scopes__course.md) |
| C | P2 | `classes/booking_answers/scopes/instance.php` | [doc](methods/S01/classes__booking_answers__scopes__instance.md) |
| C | P2 | `classes/booking_answers/scopes/instanceanswers.php` | [doc](methods/S01/classes__booking_answers__scopes__instanceanswers.md) |
| C | P2 | `classes/booking_answers/scopes/option.php` | [doc](methods/S01/classes__booking_answers__scopes__option.md) |
| C | P2 | `classes/booking_answers/scopes/supervisorteamreduced.php` | [doc](methods/S01/classes__booking_answers__scopes__supervisorteamreduced.md) |
| C | P2 | `classes/booking_answers/scopes/systemanswers.php` | [doc](methods/S01/classes__booking_answers__scopes__systemanswers.md) |
| C | P2 | `classes/booking_potential_user_selector.php` | [doc](methods/S01/classes__booking_potential_user_selector.md) |
| C | P2 | `classes/booking_settings.php` | [doc](methods/S01/classes__booking_settings.md) |
| C | P2 | `classes/booking_subbookit.php` | [doc](methods/S01/classes__booking_subbookit.md) |
| C | P2 | `classes/booking_utils.php` | [doc](methods/S01/classes__booking_utils.md) |
| C | P2 | `classes/ical.php` | [doc](methods/S01/classes__ical.md) |
| C | P2 | `classes/permissions.php` | [doc](methods/S01/classes__permissions.md) |
| C | P2 | `classes/singleton_service.php` | [doc](methods/S01/classes__singleton_service.md) |
| C | P2 | `classes/teachers_handler.php` | [doc](methods/S01/classes__teachers_handler.md) |
| B | P2 | `classes/booking_existing_user_selector.php` | [doc](methods/S01/classes__booking_existing_user_selector.md) |
| B | P2 | `classes/local/calendar/calendar_helper.php` | [doc](methods/S01/classes__local__calendar__calendar_helper.md) |
| B | P2 | `classes/potential_subscriber_selector.php` | [doc](methods/S01/classes__potential_subscriber_selector.md) |
| C | P3 | `classes/booking_answers/scopes/alloptions.php` | [doc](methods/S01/classes__booking_answers__scopes__alloptions.md) |
| C | P3 | `classes/booking_answers/scopes/optiondate.php` | [doc](methods/S01/classes__booking_answers__scopes__optiondate.md) |
| B | P3 | `classes/booking_answers/scopes/courseanswers.php` | [doc](methods/S01/classes__booking_answers__scopes__courseanswers.md) |
| B | P3 | `classes/booking_answers/scopes/optionstoconfirmreduced.php` | [doc](methods/S01/classes__booking_answers__scopes__optionstoconfirmreduced.md) |
| B | P3 | `classes/booking_answers/scopes/supervisorteam.php` | [doc](methods/S01/classes__booking_answers__scopes__supervisorteam.md) |
| B | P3 | `classes/booking_answers/scopes/system.php` | [doc](methods/S01/classes__booking_answers__scopes__system.md) |
| B | P3 | `classes/booking_context_helper.php` | [doc](methods/S01/classes__booking_context_helper.md) |
| B | P3 | `classes/booking_tags.php` | [doc](methods/S01/classes__booking_tags.md) |
| B | P3 | `classes/booking_user_selector_base.php` | [doc](methods/S01/classes__booking_user_selector_base.md) |
| B | P3 | `classes/local/optiondates/optiondate_answer.php` | [doc](methods/S01/classes__local__optiondates__optiondate_answer.md) |
| B | P3 | `classes/semester.php` | [doc](methods/S01/classes__semester.md) |
| B | P3 | `classes/subscriber_selector_base.php` | [doc](methods/S01/classes__subscriber_selector_base.md) |
| A | P3 | `classes/bookit_request_overrides.php` | [doc](methods/S01/classes__bookit_request_overrides.md) |
| A | P3 | `classes/existing_subscriber_selector.php` | [doc](methods/S01/classes__existing_subscriber_selector.md) |
| A | P3 | `classes/places.php` | [doc](methods/S01/classes__places.md) |

### S02 (90 Docs · P1:2 · P2:23 · P3:65)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/option/fields/recurringoptions.php` | [doc](methods/S02/classes__option__fields__recurringoptions.md) |
| D | P1 | `classes/option/fields/slotbooking.php` | [doc](methods/S02/classes__option__fields__slotbooking.md) |
| C | P2 | `classes/customfield/optiondate_cfields.php` | [doc](methods/S02/classes__customfield__optiondate_cfields.md) |
| C | P2 | `classes/local/override_user_field.php` | [doc](methods/S02/classes__local__override_user_field.md) |
| C | P2 | `classes/option/dates_handler.php` | [doc](methods/S02/classes__option__dates_handler.md) |
| C | P2 | `classes/option/field_base.php` | [doc](methods/S02/classes__option__field_base.md) |
| C | P2 | `classes/option/fields/availability.php` | [doc](methods/S02/classes__option__fields__availability.md) |
| C | P2 | `classes/option/fields/certificate.php` | [doc](methods/S02/classes__option__fields__certificate.md) |
| C | P2 | `classes/option/fields/competencies.php` | [doc](methods/S02/classes__option__fields__competencies.md) |
| C | P2 | `classes/option/fields/courseid.php` | [doc](methods/S02/classes__option__fields__courseid.md) |
| C | P2 | `classes/option/fields/customfields.php` | [doc](methods/S02/classes__option__fields__customfields.md) |
| C | P2 | `classes/option/fields/entities.php` | [doc](methods/S02/classes__option__fields__entities.md) |
| C | P2 | `classes/option/fields/moveoption.php` | [doc](methods/S02/classes__option__fields__moveoption.md) |
| C | P2 | `classes/option/fields/optiontype.php` | [doc](methods/S02/classes__option__fields__optiontype.md) |
| C | P2 | `classes/option/fields/price.php` | [doc](methods/S02/classes__option__fields__price.md) |
| C | P2 | `classes/option/fields/sharedplaces.php` | [doc](methods/S02/classes__option__fields__sharedplaces.md) |
| C | P2 | `classes/option/fields/shoppingcart.php` | [doc](methods/S02/classes__option__fields__shoppingcart.md) |
| C | P2 | `classes/option/fields/teachers.php` | [doc](methods/S02/classes__option__fields__teachers.md) |
| C | P2 | `classes/option/fields/template.php` | [doc](methods/S02/classes__option__fields__template.md) |
| C | P2 | `classes/option/fields_info.php` | [doc](methods/S02/classes__option__fields_info.md) |
| C | P2 | `classes/option/optiondate.php` | [doc](methods/S02/classes__option__optiondate.md) |
| C | P2 | `classes/settings/optionformconfig/optionformconfig_info.php` | [doc](methods/S02/classes__settings__optionformconfig__optionformconfig_info.md) |
| B | P2 | `classes/customfield/booking_handler.php` | [doc](methods/S02/classes__customfield__booking_handler.md) |
| B | P2 | `classes/option/fields/duplication.php` | [doc](methods/S02/classes__option__fields__duplication.md) |
| B | P2 | `classes/option/fields/optiondates.php` | [doc](methods/S02/classes__option__fields__optiondates.md) |
| C | P3 | `classes/option/fields/applybookingrules.php` | [doc](methods/S02/classes__option__fields__applybookingrules.md) |
| C | P3 | `classes/option/fields/attachment.php` | [doc](methods/S02/classes__option__fields__attachment.md) |
| C | P3 | `classes/option/fields/bookingclosingtime.php` | [doc](methods/S02/classes__option__fields__bookingclosingtime.md) |
| C | P3 | `classes/option/fields/bookingopeningtime.php` | [doc](methods/S02/classes__option__fields__bookingopeningtime.md) |
| C | P3 | `classes/option/fields/bookingoptionimage.php` | [doc](methods/S02/classes__option__fields__bookingoptionimage.md) |
| C | P3 | `classes/option/fields/bookusers.php` | [doc](methods/S02/classes__option__fields__bookusers.md) |
| C | P3 | `classes/option/fields/canceluntil.php` | [doc](methods/S02/classes__option__fields__canceluntil.md) |
| C | P3 | `classes/option/fields/coursestarttime.php` | [doc](methods/S02/classes__option__fields__coursestarttime.md) |
| C | P3 | `classes/option/fields/credits.php` | [doc](methods/S02/classes__option__fields__credits.md) |
| C | P3 | `classes/option/fields/duration.php` | [doc](methods/S02/classes__option__fields__duration.md) |
| C | P3 | `classes/option/fields/easy_availability_previouslybooked.php` | [doc](methods/S02/classes__option__fields__easy_availability_previouslybooked.md) |
| C | P3 | `classes/option/fields/easy_availability_selectusers.php` | [doc](methods/S02/classes__option__fields__easy_availability_selectusers.md) |
| C | P3 | `classes/option/fields/easy_bookingclosingtime.php` | [doc](methods/S02/classes__option__fields__easy_bookingclosingtime.md) |
| C | P3 | `classes/option/fields/easy_bookingopeningtime.php` | [doc](methods/S02/classes__option__fields__easy_bookingopeningtime.md) |
| C | P3 | `classes/option/fields/elective.php` | [doc](methods/S02/classes__option__fields__elective.md) |
| C | P3 | `classes/option/fields/enrolmentstatus.php` | [doc](methods/S02/classes__option__fields__enrolmentstatus.md) |
| C | P3 | `classes/option/fields/groupid.php` | [doc](methods/S02/classes__option__fields__groupid.md) |
| C | P3 | `classes/option/fields/invisible.php` | [doc](methods/S02/classes__option__fields__invisible.md) |
| C | P3 | `classes/option/fields/pollurl.php` | [doc](methods/S02/classes__option__fields__pollurl.md) |
| C | P3 | `classes/option/fields/prepare_import.php` | [doc](methods/S02/classes__option__fields__prepare_import.md) |
| C | P3 | `classes/option/fields/responsiblecontact.php` | [doc](methods/S02/classes__option__fields__responsiblecontact.md) |
| C | P3 | `classes/option/fields/waitforconfirmation.php` | [doc](methods/S02/classes__option__fields__waitforconfirmation.md) |
| B | P3 | `classes/option/fields/actions.php` | [doc](methods/S02/classes__option__fields__actions.md) |
| B | P3 | `classes/option/fields/addastemplate.php` | [doc](methods/S02/classes__option__fields__addastemplate.md) |
| B | P3 | `classes/option/fields/address.php` | [doc](methods/S02/classes__option__fields__address.md) |
| B | P3 | `classes/option/fields/addtocalendar.php` | [doc](methods/S02/classes__option__fields__addtocalendar.md) |
| B | P3 | `classes/option/fields/addtogroup.php` | [doc](methods/S02/classes__option__fields__addtogroup.md) |
| B | P3 | `classes/option/fields/aftercompletedtext.php` | [doc](methods/S02/classes__option__fields__aftercompletedtext.md) |
| B | P3 | `classes/option/fields/aftersubmitaction.php` | [doc](methods/S02/classes__option__fields__aftersubmitaction.md) |
| B | P3 | `classes/option/fields/annotation.php` | [doc](methods/S02/classes__option__fields__annotation.md) |
| B | P3 | `classes/option/fields/beforebookedtext.php` | [doc](methods/S02/classes__option__fields__beforebookedtext.md) |
| B | P3 | `classes/option/fields/beforecompletedtext.php` | [doc](methods/S02/classes__option__fields__beforecompletedtext.md) |
| B | P3 | `classes/option/fields/courseendtime.php` | [doc](methods/S02/classes__option__fields__courseendtime.md) |
| B | P3 | `classes/option/fields/description.php` | [doc](methods/S02/classes__option__fields__description.md) |
| B | P3 | `classes/option/fields/disablebookingusers.php` | [doc](methods/S02/classes__option__fields__disablebookingusers.md) |
| B | P3 | `classes/option/fields/disablecancel.php` | [doc](methods/S02/classes__option__fields__disablecancel.md) |
| B | P3 | `classes/option/fields/easy_text.php` | [doc](methods/S02/classes__option__fields__easy_text.md) |
| B | P3 | `classes/option/fields/eventslist.php` | [doc](methods/S02/classes__option__fields__eventslist.md) |
| B | P3 | `classes/option/fields/formconfig.php` | [doc](methods/S02/classes__option__fields__formconfig.md) |
| B | P3 | `classes/option/fields/howmanyusers.php` | [doc](methods/S02/classes__option__fields__howmanyusers.md) |
| B | P3 | `classes/option/fields/id.php` | [doc](methods/S02/classes__option__fields__id.md) |
| B | P3 | `classes/option/fields/identifier.php` | [doc](methods/S02/classes__option__fields__identifier.md) |
| B | P3 | `classes/option/fields/institution.php` | [doc](methods/S02/classes__option__fields__institution.md) |
| B | P3 | `classes/option/fields/json.php` | [doc](methods/S02/classes__option__fields__json.md) |
| B | P3 | `classes/option/fields/location.php` | [doc](methods/S02/classes__option__fields__location.md) |
| B | P3 | `classes/option/fields/maxanswers.php` | [doc](methods/S02/classes__option__fields__maxanswers.md) |
| B | P3 | `classes/option/fields/maxoverbooking.php` | [doc](methods/S02/classes__option__fields__maxoverbooking.md) |
| B | P3 | `classes/option/fields/minanswers.php` | [doc](methods/S02/classes__option__fields__minanswers.md) |
| B | P3 | `classes/option/fields/multiplebookings.php` | [doc](methods/S02/classes__option__fields__multiplebookings.md) |
| B | P3 | `classes/option/fields/notificationtext.php` | [doc](methods/S02/classes__option__fields__notificationtext.md) |
| B | P3 | `classes/option/fields/priceformulaadd.php` | [doc](methods/S02/classes__option__fields__priceformulaadd.md) |
| B | P3 | `classes/option/fields/priceformulamultiply.php` | [doc](methods/S02/classes__option__fields__priceformulamultiply.md) |
| B | P3 | `classes/option/fields/priceformulaoff.php` | [doc](methods/S02/classes__option__fields__priceformulaoff.md) |
| B | P3 | `classes/option/fields/removeafterminutes.php` | [doc](methods/S02/classes__option__fields__removeafterminutes.md) |
| B | P3 | `classes/option/fields/returnurl.php` | [doc](methods/S02/classes__option__fields__returnurl.md) |
| B | P3 | `classes/option/fields/subbookings.php` | [doc](methods/S02/classes__option__fields__subbookings.md) |
| B | P3 | `classes/option/fields/text.php` | [doc](methods/S02/classes__option__fields__text.md) |
| B | P3 | `classes/option/fields/timecreated.php` | [doc](methods/S02/classes__option__fields__timecreated.md) |
| B | P3 | `classes/option/fields/timemodified.php` | [doc](methods/S02/classes__option__fields__timemodified.md) |
| B | P3 | `classes/option/fields/titleprefix.php` | [doc](methods/S02/classes__option__fields__titleprefix.md) |
| B | P3 | `classes/option/fields/usercreated.php` | [doc](methods/S02/classes__option__fields__usercreated.md) |
| B | P3 | `classes/option/fields/usermodified.php` | [doc](methods/S02/classes__option__fields__usermodified.md) |
| A | P3 | `classes/option/fields.php` | [doc](methods/S02/classes__option__fields.md) |
| A | P3 | `classes/option/time_handler.php` | [doc](methods/S02/classes__option__time_handler.md) |
| A | P3 | `classes/option/type_resolver.php` | [doc](methods/S02/classes__option__type_resolver.md) |

### S03 (61 Docs · P1:1 · P2:25 · P3:34)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/bo_availability/bo_info.php` | [doc](methods/S03/classes__bo_availability__bo_info.md) |
| C | P2 | `classes/bo_availability/bo_subinfo.php` | [doc](methods/S03/classes__bo_availability__bo_subinfo.md) |
| C | P2 | `classes/bo_availability/conditions/askforconfirmation.php` | [doc](methods/S03/classes__bo_availability__conditions__askforconfirmation.md) |
| C | P2 | `classes/bo_availability/conditions/booking_time.php` | [doc](methods/S03/classes__bo_availability__conditions__booking_time.md) |
| C | P2 | `classes/bo_availability/conditions/bookwithsubscription.php` | [doc](methods/S03/classes__bo_availability__conditions__bookwithsubscription.md) |
| C | P2 | `classes/bo_availability/conditions/cancelmyself.php` | [doc](methods/S03/classes__bo_availability__conditions__cancelmyself.md) |
| C | P2 | `classes/bo_availability/conditions/customform.php` | [doc](methods/S03/classes__bo_availability__conditions__customform.md) |
| C | P2 | `classes/bo_availability/conditions/enrolledincohorts.php` | [doc](methods/S03/classes__bo_availability__conditions__enrolledincohorts.md) |
| C | P2 | `classes/bo_availability/conditions/enrolledincourse.php` | [doc](methods/S03/classes__bo_availability__conditions__enrolledincourse.md) |
| C | P2 | `classes/bo_availability/conditions/hascompetency.php` | [doc](methods/S03/classes__bo_availability__conditions__hascompetency.md) |
| C | P2 | `classes/bo_availability/conditions/previouslybooked.php` | [doc](methods/S03/classes__bo_availability__conditions__previouslybooked.md) |
| C | P2 | `classes/bo_availability/conditions/userprofilefield_1_default.php` | [doc](methods/S03/classes__bo_availability__conditions__userprofilefield_1_default.md) |
| C | P2 | `classes/bo_availability/conditions/userprofilefield_2_custom.php` | [doc](methods/S03/classes__bo_availability__conditions__userprofilefield_2_custom.md) |
| B | P2 | `classes/bo_availability/conditions/allowedtobookininstance.php` | [doc](methods/S03/classes__bo_availability__conditions__allowedtobookininstance.md) |
| B | P2 | `classes/bo_availability/conditions/bookitbutton.php` | [doc](methods/S03/classes__bo_availability__conditions__bookitbutton.md) |
| B | P2 | `classes/bo_availability/conditions/bookwithcredits.php` | [doc](methods/S03/classes__bo_availability__conditions__bookwithcredits.md) |
| B | P2 | `classes/bo_availability/conditions/confirmcancel.php` | [doc](methods/S03/classes__bo_availability__conditions__confirmcancel.md) |
| B | P2 | `classes/bo_availability/conditions/fullybooked.php` | [doc](methods/S03/classes__bo_availability__conditions__fullybooked.md) |
| B | P2 | `classes/bo_availability/conditions/maxoptionsfromcategory.php` | [doc](methods/S03/classes__bo_availability__conditions__maxoptionsfromcategory.md) |
| B | P2 | `classes/bo_availability/conditions/nooverlapping.php` | [doc](methods/S03/classes__bo_availability__conditions__nooverlapping.md) |
| B | P2 | `classes/bo_availability/conditions/nooverlappingproxy.php` | [doc](methods/S03/classes__bo_availability__conditions__nooverlappingproxy.md) |
| B | P2 | `classes/bo_availability/conditions/notifymelist.php` | [doc](methods/S03/classes__bo_availability__conditions__notifymelist.md) |
| B | P2 | `classes/bo_availability/conditions/onwaitinglist.php` | [doc](methods/S03/classes__bo_availability__conditions__onwaitinglist.md) |
| B | P2 | `classes/bo_availability/conditions/priceisset.php` | [doc](methods/S03/classes__bo_availability__conditions__priceisset.md) |
| B | P2 | `classes/bo_availability/conditions/selectusers.php` | [doc](methods/S03/classes__bo_availability__conditions__selectusers.md) |
| B | P2 | `classes/bo_availability/conditions/slotbooking.php` | [doc](methods/S03/classes__bo_availability__conditions__slotbooking.md) |
| C | P3 | `classes/bo_availability/conditions/confirmbookwithsubscription.php` | [doc](methods/S03/classes__bo_availability__conditions__confirmbookwithsubscription.md) |
| B | P3 | `classes/bo_availability/condition_state_helper.php` | [doc](methods/S03/classes__bo_availability__condition_state_helper.md) |
| B | P3 | `classes/bo_availability/condition_visibility_manager.php` | [doc](methods/S03/classes__bo_availability__condition_visibility_manager.md) |
| B | P3 | `classes/bo_availability/conditions/alreadyreserved.php` | [doc](methods/S03/classes__bo_availability__conditions__alreadyreserved.md) |
| B | P3 | `classes/bo_availability/conditions/bookingpolicy.php` | [doc](methods/S03/classes__bo_availability__conditions__bookingpolicy.md) |
| B | P3 | `classes/bo_availability/conditions/bookondetail.php` | [doc](methods/S03/classes__bo_availability__conditions__bookondetail.md) |
| B | P3 | `classes/bo_availability/conditions/campaign_blockbooking.php` | [doc](methods/S03/classes__bo_availability__conditions__campaign_blockbooking.md) |
| B | P3 | `classes/bo_availability/conditions/capbookingchoose.php` | [doc](methods/S03/classes__bo_availability__conditions__capbookingchoose.md) |
| B | P3 | `classes/bo_availability/conditions/confirmaskforconfirmation.php` | [doc](methods/S03/classes__bo_availability__conditions__confirmaskforconfirmation.md) |
| B | P3 | `classes/bo_availability/conditions/confirmation.php` | [doc](methods/S03/classes__bo_availability__conditions__confirmation.md) |
| B | P3 | `classes/bo_availability/conditions/confirmbookit.php` | [doc](methods/S03/classes__bo_availability__conditions__confirmbookit.md) |
| B | P3 | `classes/bo_availability/conditions/confirmbookwithcredits.php` | [doc](methods/S03/classes__bo_availability__conditions__confirmbookwithcredits.md) |
| B | P3 | `classes/bo_availability/conditions/electivebookitbutton.php` | [doc](methods/S03/classes__bo_availability__conditions__electivebookitbutton.md) |
| B | P3 | `classes/bo_availability/conditions/electivenotbookable.php` | [doc](methods/S03/classes__bo_availability__conditions__electivenotbookable.md) |
| B | P3 | `classes/bo_availability/conditions/isbookable.php` | [doc](methods/S03/classes__bo_availability__conditions__isbookable.md) |
| B | P3 | `classes/bo_availability/conditions/isbookableinstance.php` | [doc](methods/S03/classes__bo_availability__conditions__isbookableinstance.md) |
| B | P3 | `classes/bo_availability/conditions/iscancelled.php` | [doc](methods/S03/classes__bo_availability__conditions__iscancelled.md) |
| B | P3 | `classes/bo_availability/conditions/isloggedin.php` | [doc](methods/S03/classes__bo_availability__conditions__isloggedin.md) |
| B | P3 | `classes/bo_availability/conditions/max_number_of_bookings.php` | [doc](methods/S03/classes__bo_availability__conditions__max_number_of_bookings.md) |
| B | P3 | `classes/bo_availability/conditions/noshoppingcart.php` | [doc](methods/S03/classes__bo_availability__conditions__noshoppingcart.md) |
| B | P3 | `classes/bo_availability/conditions/optionhasstarted.php` | [doc](methods/S03/classes__bo_availability__conditions__optionhasstarted.md) |
| B | P3 | `classes/bo_availability/conditions/otheroptionsavailable.php` | [doc](methods/S03/classes__bo_availability__conditions__otheroptionsavailable.md) |
| B | P3 | `classes/bo_availability/conditions/subbooking.php` | [doc](methods/S03/classes__bo_availability__conditions__subbooking.md) |
| B | P3 | `classes/bo_availability/conditions/subbooking_blocks.php` | [doc](methods/S03/classes__bo_availability__conditions__subbooking_blocks.md) |
| B | P3 | `classes/bo_availability/subconditions/alreadybooked.php` | [doc](methods/S03/classes__bo_availability__subconditions__alreadybooked.md) |
| B | P3 | `classes/bo_availability/subconditions/bookitbutton.php` | [doc](methods/S03/classes__bo_availability__subconditions__bookitbutton.md) |
| B | P3 | `classes/bo_availability/subconditions/isbookable.php` | [doc](methods/S03/classes__bo_availability__subconditions__isbookable.md) |
| B | P3 | `classes/bo_availability/subconditions/priceisset.php` | [doc](methods/S03/classes__bo_availability__subconditions__priceisset.md) |
| A | P3 | `classes/bo_availability/bo_condition.php` | [doc](methods/S03/classes__bo_availability__bo_condition.md) |
| A | P3 | `classes/bo_availability/bo_subcondition.php` | [doc](methods/S03/classes__bo_availability__bo_subcondition.md) |
| A | P3 | `classes/bo_availability/conditions/alreadybooked.php` | [doc](methods/S03/classes__bo_availability__conditions__alreadybooked.md) |
| A | P3 | `classes/bo_availability/conditions/isloggedinprice.php` | [doc](methods/S03/classes__bo_availability__conditions__isloggedinprice.md) |
| A | P3 | `classes/bo_availability/conditions/slotmove.php` | [doc](methods/S03/classes__bo_availability__conditions__slotmove.md) |
| A | P3 | `classes/bo_availability/freezable_condition.php` | [doc](methods/S03/classes__bo_availability__freezable_condition.md) |
| — | — | `classes/bo_availability/conditions/instanceavailability.php` | [doc](methods/S03/classes__bo_availability__conditions__instanceavailability.md) |

### S04 (10 Docs · P1:2 · P2:3 · P3:5)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/booking_bookit.php` | [doc](methods/S04/classes__booking_bookit.md) |
| D | P1 | `classes/elective.php` | [doc](methods/S04/classes__elective.md) |
| C | P2 | `classes/bo_actions/action_types/executerestscript.php` | [doc](methods/S04/classes__bo_actions__action_types__executerestscript.md) |
| C | P2 | `classes/local/book_all_students.php` | [doc](methods/S04/classes__local__book_all_students.md) |
| B | P2 | `classes/bo_actions/actions_info.php` | [doc](methods/S04/classes__bo_actions__actions_info.md) |
| B | P3 | `classes/bo_actions/action_types/bookotheroptions.php` | [doc](methods/S04/classes__bo_actions__action_types__bookotheroptions.md) |
| B | P3 | `classes/bo_actions/action_types/userprofilefield.php` | [doc](methods/S04/classes__bo_actions__action_types__userprofilefield.md) |
| B | P3 | `classes/bo_actions/booking_action.php` | [doc](methods/S04/classes__bo_actions__booking_action.md) |
| B | P3 | `classes/local/confirmationworkflow/confirmation.php` | [doc](methods/S04/classes__local__confirmationworkflow__confirmation.md) |
| A | P3 | `classes/bo_actions/action_types/cancelbooking.php` | [doc](methods/S04/classes__bo_actions__action_types__cancelbooking.md) |

### S05 (3 Docs · P1:2 · P3:1)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/price.php` | [doc](methods/S05/classes__price.md) |
| C | P1 | `classes/shopping_cart/service_provider.php` | [doc](methods/S05/classes__shopping_cart__service_provider.md) |
| B | P3 | `classes/local/pricecategories_handler.php` | [doc](methods/S05/classes__local__pricecategories_handler.md) |

### S06 (42 Docs · P1:3 · P2:16 · P3:23)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/booking_rules/conditions/select_user_shopping_cart.php` | [doc](methods/S06/classes__booking_rules__conditions__select_user_shopping_cart.md) |
| C | P1 | `classes/booking_rules/actions/confirm_bookinganswer.php` | [doc](methods/S06/classes__booking_rules__actions__confirm_bookinganswer.md) |
| C | P1 | `classes/booking_rules/actions/send_mail_interval.php` | [doc](methods/S06/classes__booking_rules__actions__send_mail_interval.md) |
| C | P2 | `classes/booking_rules/actions/send_copy_of_mail.php` | [doc](methods/S06/classes__booking_rules__actions__send_copy_of_mail.md) |
| C | P2 | `classes/booking_rules/conditions/enter_userprofilefield.php` | [doc](methods/S06/classes__booking_rules__conditions__enter_userprofilefield.md) |
| C | P2 | `classes/booking_rules/conditions/match_userprofilefield.php` | [doc](methods/S06/classes__booking_rules__conditions__match_userprofilefield.md) |
| C | P2 | `classes/booking_rules/conditions/select_deputy_of_supervisor.php` | [doc](methods/S06/classes__booking_rules__conditions__select_deputy_of_supervisor.md) |
| C | P2 | `classes/booking_rules/conditions/select_responsible_contact_in_bo.php` | [doc](methods/S06/classes__booking_rules__conditions__select_responsible_contact_in_bo.md) |
| C | P2 | `classes/booking_rules/conditions/select_student_in_bo.php` | [doc](methods/S06/classes__booking_rules__conditions__select_student_in_bo.md) |
| C | P2 | `classes/booking_rules/conditions/select_user_from_event.php` | [doc](methods/S06/classes__booking_rules__conditions__select_user_from_event.md) |
| C | P2 | `classes/booking_rules/conditions/select_users_from_userfield_of_eventuser.php` | [doc](methods/S06/classes__booking_rules__conditions__select_users_from_userfield_of_eventuser.md) |
| C | P2 | `classes/booking_rules/rules/rule_daysbefore.php` | [doc](methods/S06/classes__booking_rules__rules__rule_daysbefore.md) |
| C | P2 | `classes/booking_rules/rules/rule_react_on_event.php` | [doc](methods/S06/classes__booking_rules__rules__rule_react_on_event.md) |
| C | P2 | `classes/booking_rules/rules/rule_specifictime.php` | [doc](methods/S06/classes__booking_rules__rules__rule_specifictime.md) |
| C | P2 | `classes/booking_rules/rules_info.php` | [doc](methods/S06/classes__booking_rules__rules_info.md) |
| B | P2 | `classes/booking_rules/actions/send_mail.php` | [doc](methods/S06/classes__booking_rules__actions__send_mail.md) |
| B | P2 | `classes/booking_rules/actions_info.php` | [doc](methods/S06/classes__booking_rules__actions_info.md) |
| B | P2 | `classes/booking_rules/booking_rules.php` | [doc](methods/S06/classes__booking_rules__booking_rules.md) |
| B | P2 | `classes/booking_rules/conditions_info.php` | [doc](methods/S06/classes__booking_rules__conditions_info.md) |
| B | P3 | `classes/booking_rules/actions/delete_conditions_from_bookinganswer.php` | [doc](methods/S06/classes__booking_rules__actions__delete_conditions_from_bookinganswer.md) |
| B | P3 | `classes/booking_rules/conditions/select_booking_manager.php` | [doc](methods/S06/classes__booking_rules__conditions__select_booking_manager.md) |
| B | P3 | `classes/booking_rules/conditions/select_teacher_in_bo.php` | [doc](methods/S06/classes__booking_rules__conditions__select_teacher_in_bo.md) |
| B | P3 | `classes/booking_rules/conditions/select_users.php` | [doc](methods/S06/classes__booking_rules__conditions__select_users.md) |
| B | P3 | `classes/booking_rules/rules/templates/ruletemplate_courseupdate.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_courseupdate.md) |
| A | P3 | `classes/booking_rules/booking_rule.php` | [doc](methods/S06/classes__booking_rules__booking_rule.md) |
| A | P3 | `classes/booking_rules/booking_rule_action.php` | [doc](methods/S06/classes__booking_rules__booking_rule_action.md) |
| A | P3 | `classes/booking_rules/booking_rule_condition.php` | [doc](methods/S06/classes__booking_rules__booking_rule_condition.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_bookingoption_booked.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_bookingoption_booked.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_bookingoptioncompleted.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_bookingoptioncompleted.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_bookingoptionuncompleted.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_bookingoptionuncompleted.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_confirmwaitinglist.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_confirmwaitinglist.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_daysbeforestart.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_daysbeforestart.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacheradded.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_optiondatesteacheradded.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacherdeleted.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_optiondatesteacherdeleted.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_paymentconfirmation.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_paymentconfirmation.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_sessionreminders.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_sessionreminders.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_trainercancellation.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_trainercancellation.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_trainerpoll.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_trainerpoll.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_trainerreminderbeforestart.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_trainerreminderbeforestart.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_usercancellation.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_usercancellation.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_userpoll.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_userpoll.md) |
| A | P3 | `classes/booking_rules/rules/templates/ruletemplate_userstorno.php` | [doc](methods/S06/classes__booking_rules__rules__templates__ruletemplate_userstorno.md) |

### S07 (4 Docs · P2:3 · P3:1)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/booking_campaigns/campaigns_info.php` | [doc](methods/S07/classes__booking_campaigns__campaigns_info.md) |
| B | P2 | `classes/booking_campaigns/campaigns/campaign_blockbooking.php` | [doc](methods/S07/classes__booking_campaigns__campaigns__campaign_blockbooking.md) |
| B | P2 | `classes/booking_campaigns/campaigns/campaign_customfield.php` | [doc](methods/S07/classes__booking_campaigns__campaigns__campaign_customfield.md) |
| A | P3 | `classes/booking_campaigns/booking_campaign.php` | [doc](methods/S07/classes__booking_campaigns__booking_campaign.md) |

### S08 (7 Docs · P1:1 · P2:4 · P3:2)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P1 | `classes/subbookings.php` | [doc](methods/S08/classes__subbookings.md) |
| C | P2 | `classes/subbookings/sb_types/subbooking_additionalitem.php` | [doc](methods/S08/classes__subbookings__sb_types__subbooking_additionalitem.md) |
| C | P2 | `classes/subbookings/sb_types/subbooking_additionalperson.php` | [doc](methods/S08/classes__subbookings__sb_types__subbooking_additionalperson.md) |
| C | P2 | `classes/subbookings/sb_types/subbooking_timeslot.php` | [doc](methods/S08/classes__subbookings__sb_types__subbooking_timeslot.md) |
| C | P2 | `classes/subbookings/subbookings_info.php` | [doc](methods/S08/classes__subbookings__subbookings_info.md) |
| D | P3 | `classes/subbookings/subbookings_cache.php` | [doc](methods/S08/classes__subbookings__subbookings_cache.md) |
| A | P3 | `classes/subbookings/booking_subbooking.php` | [doc](methods/S08/classes__subbookings__booking_subbooking.md) |

### S09 (78 Docs · P1:1 · P2:11 · P3:66)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/message_controller.php` | [doc](methods/S09/classes__message_controller.md) |
| C | P2 | `classes/local/scheduledmails.php` | [doc](methods/S09/classes__local__scheduledmails.md) |
| C | P2 | `classes/placeholders/placeholders/bookingconfirmationlink.php` | [doc](methods/S09/classes__placeholders__placeholders__bookingconfirmationlink.md) |
| C | P2 | `classes/placeholders/placeholders/customfields.php` | [doc](methods/S09/classes__placeholders__placeholders__customfields.md) |
| C | P2 | `classes/placeholders/placeholders/customform.php` | [doc](methods/S09/classes__placeholders__placeholders__customform.md) |
| C | P2 | `classes/placeholders/placeholders/profilepicture.php` | [doc](methods/S09/classes__placeholders__placeholders__profilepicture.md) |
| C | P2 | `classes/placeholders/placeholders/selflearningcourse.php` | [doc](methods/S09/classes__placeholders__placeholders__selflearningcourse.md) |
| C | P2 | `classes/placeholders/placeholders/semester.php` | [doc](methods/S09/classes__placeholders__placeholders__semester.md) |
| C | P2 | `classes/placeholders/placeholders_info.php` | [doc](methods/S09/classes__placeholders__placeholders_info.md) |
| B | P2 | `classes/local/templaterule.php` | [doc](methods/S09/classes__local__templaterule.md) |
| B | P2 | `classes/placeholders/placeholders/certificateurl.php` | [doc](methods/S09/classes__placeholders__placeholders__certificateurl.md) |
| B | P2 | `classes/placeholders/placeholders/eventdescription.php` | [doc](methods/S09/classes__placeholders__placeholders__eventdescription.md) |
| C | P3 | `classes/placeholders/placeholders/starttime.php` | [doc](methods/S09/classes__placeholders__placeholders__starttime.md) |
| B | P3 | `classes/placeholders/placeholders/address.php` | [doc](methods/S09/classes__placeholders__placeholders__address.md) |
| B | P3 | `classes/placeholders/placeholders/baid.php` | [doc](methods/S09/classes__placeholders__placeholders__baid.md) |
| B | P3 | `classes/placeholders/placeholders/bookedplaces.php` | [doc](methods/S09/classes__placeholders__placeholders__bookedplaces.md) |
| B | P3 | `classes/placeholders/placeholders/bookedslotsfromevent.php` | [doc](methods/S09/classes__placeholders__placeholders__bookedslotsfromevent.md) |
| B | P3 | `classes/placeholders/placeholders/bookingdetails.php` | [doc](methods/S09/classes__placeholders__placeholders__bookingdetails.md) |
| B | P3 | `classes/placeholders/placeholders/bookinglink.php` | [doc](methods/S09/classes__placeholders__placeholders__bookinglink.md) |
| B | P3 | `classes/placeholders/placeholders/bookingoptiondetaillink.php` | [doc](methods/S09/classes__placeholders__placeholders__bookingoptiondetaillink.md) |
| B | P3 | `classes/placeholders/placeholders/bookingoptionname.php` | [doc](methods/S09/classes__placeholders__placeholders__bookingoptionname.md) |
| B | P3 | `classes/placeholders/placeholders/bookingreportlink.php` | [doc](methods/S09/classes__placeholders__placeholders__bookingreportlink.md) |
| B | P3 | `classes/placeholders/placeholders/changes.php` | [doc](methods/S09/classes__placeholders__placeholders__changes.md) |
| B | P3 | `classes/placeholders/placeholders/coursecalendarurl.php` | [doc](methods/S09/classes__placeholders__placeholders__coursecalendarurl.md) |
| B | P3 | `classes/placeholders/placeholders/courseid.php` | [doc](methods/S09/classes__placeholders__placeholders__courseid.md) |
| B | P3 | `classes/placeholders/placeholders/courselink.php` | [doc](methods/S09/classes__placeholders__placeholders__courselink.md) |
| B | P3 | `classes/placeholders/placeholders/coursename.php` | [doc](methods/S09/classes__placeholders__placeholders__coursename.md) |
| B | P3 | `classes/placeholders/placeholders/dates.php` | [doc](methods/S09/classes__placeholders__placeholders__dates.md) |
| B | P3 | `classes/placeholders/placeholders/datesandentities.php` | [doc](methods/S09/classes__placeholders__placeholders__datesandentities.md) |
| B | P3 | `classes/placeholders/placeholders/department.php` | [doc](methods/S09/classes__placeholders__placeholders__department.md) |
| B | P3 | `classes/placeholders/placeholders/description.php` | [doc](methods/S09/classes__placeholders__placeholders__description.md) |
| B | P3 | `classes/placeholders/placeholders/duedate.php` | [doc](methods/S09/classes__placeholders__placeholders__duedate.md) |
| B | P3 | `classes/placeholders/placeholders/duration.php` | [doc](methods/S09/classes__placeholders__placeholders__duration.md) |
| B | P3 | `classes/placeholders/placeholders/email.php` | [doc](methods/S09/classes__placeholders__placeholders__email.md) |
| B | P3 | `classes/placeholders/placeholders/emailrelated.php` | [doc](methods/S09/classes__placeholders__placeholders__emailrelated.md) |
| B | P3 | `classes/placeholders/placeholders/enddate.php` | [doc](methods/S09/classes__placeholders__placeholders__enddate.md) |
| B | P3 | `classes/placeholders/placeholders/endtime.php` | [doc](methods/S09/classes__placeholders__placeholders__endtime.md) |
| B | P3 | `classes/placeholders/placeholders/enrollink.php` | [doc](methods/S09/classes__placeholders__placeholders__enrollink.md) |
| B | P3 | `classes/placeholders/placeholders/eventtype.php` | [doc](methods/S09/classes__placeholders__placeholders__eventtype.md) |
| B | P3 | `classes/placeholders/placeholders/firstname.php` | [doc](methods/S09/classes__placeholders__placeholders__firstname.md) |
| B | P3 | `classes/placeholders/placeholders/firstnamerelated.php` | [doc](methods/S09/classes__placeholders__placeholders__firstnamerelated.md) |
| B | P3 | `classes/placeholders/placeholders/gotobookingoption.php` | [doc](methods/S09/classes__placeholders__placeholders__gotobookingoption.md) |
| B | P3 | `classes/placeholders/placeholders/installmentprice.php` | [doc](methods/S09/classes__placeholders__placeholders__installmentprice.md) |
| B | P3 | `classes/placeholders/placeholders/instancename.php` | [doc](methods/S09/classes__placeholders__placeholders__instancename.md) |
| B | P3 | `classes/placeholders/placeholders/institution.php` | [doc](methods/S09/classes__placeholders__placeholders__institution.md) |
| B | P3 | `classes/placeholders/placeholders/journal.php` | [doc](methods/S09/classes__placeholders__placeholders__journal.md) |
| B | P3 | `classes/placeholders/placeholders/lastname.php` | [doc](methods/S09/classes__placeholders__placeholders__lastname.md) |
| B | P3 | `classes/placeholders/placeholders/lastnamerelated.php` | [doc](methods/S09/classes__placeholders__placeholders__lastnamerelated.md) |
| B | P3 | `classes/placeholders/placeholders/location.php` | [doc](methods/S09/classes__placeholders__placeholders__location.md) |
| B | P3 | `classes/placeholders/placeholders/numberparticipants.php` | [doc](methods/S09/classes__placeholders__placeholders__numberparticipants.md) |
| B | P3 | `classes/placeholders/placeholders/numberwaitinglist.php` | [doc](methods/S09/classes__placeholders__placeholders__numberwaitinglist.md) |
| B | P3 | `classes/placeholders/placeholders/optiondatefromevent.php` | [doc](methods/S09/classes__placeholders__placeholders__optiondatefromevent.md) |
| B | P3 | `classes/placeholders/placeholders/optionid.php` | [doc](methods/S09/classes__placeholders__placeholders__optionid.md) |
| B | P3 | `classes/placeholders/placeholders/participant.php` | [doc](methods/S09/classes__placeholders__placeholders__participant.md) |
| B | P3 | `classes/placeholders/placeholders/pollstartdate.php` | [doc](methods/S09/classes__placeholders__placeholders__pollstartdate.md) |
| B | P3 | `classes/placeholders/placeholders/pollurl.php` | [doc](methods/S09/classes__placeholders__placeholders__pollurl.md) |
| B | P3 | `classes/placeholders/placeholders/pollurlteachers.php` | [doc](methods/S09/classes__placeholders__placeholders__pollurlteachers.md) |
| B | P3 | `classes/placeholders/placeholders/price.php` | [doc](methods/S09/classes__placeholders__placeholders__price.md) |
| B | P3 | `classes/placeholders/placeholders/qrenrollink.php` | [doc](methods/S09/classes__placeholders__placeholders__qrenrollink.md) |
| B | P3 | `classes/placeholders/placeholders/qrid.php` | [doc](methods/S09/classes__placeholders__placeholders__qrid.md) |
| B | P3 | `classes/placeholders/placeholders/qrusername.php` | [doc](methods/S09/classes__placeholders__placeholders__qrusername.md) |
| B | P3 | `classes/placeholders/placeholders/restresponse.php` | [doc](methods/S09/classes__placeholders__placeholders__restresponse.md) |
| B | P3 | `classes/placeholders/placeholders/shoppingcartplaceholder.php` | [doc](methods/S09/classes__placeholders__placeholders__shoppingcartplaceholder.md) |
| B | P3 | `classes/placeholders/placeholders/slotsmovedfrom.php` | [doc](methods/S09/classes__placeholders__placeholders__slotsmovedfrom.md) |
| B | P3 | `classes/placeholders/placeholders/slotsmovedto.php` | [doc](methods/S09/classes__placeholders__placeholders__slotsmovedto.md) |
| B | P3 | `classes/placeholders/placeholders/startdate.php` | [doc](methods/S09/classes__placeholders__placeholders__startdate.md) |
| B | P3 | `classes/placeholders/placeholders/status.php` | [doc](methods/S09/classes__placeholders__placeholders__status.md) |
| B | P3 | `classes/placeholders/placeholders/teacher.php` | [doc](methods/S09/classes__placeholders__placeholders__teacher.md) |
| B | P3 | `classes/placeholders/placeholders/teachers.php` | [doc](methods/S09/classes__placeholders__placeholders__teachers.md) |
| B | P3 | `classes/placeholders/placeholders/title.php` | [doc](methods/S09/classes__placeholders__placeholders__title.md) |
| B | P3 | `classes/placeholders/placeholders/type.php` | [doc](methods/S09/classes__placeholders__placeholders__type.md) |
| B | P3 | `classes/placeholders/placeholders/usercalendarurl.php` | [doc](methods/S09/classes__placeholders__placeholders__usercalendarurl.md) |
| B | P3 | `classes/placeholders/placeholders/userid.php` | [doc](methods/S09/classes__placeholders__placeholders__userid.md) |
| B | P3 | `classes/placeholders/placeholders/username.php` | [doc](methods/S09/classes__placeholders__placeholders__username.md) |
| A | P3 | `classes/placeholders/placeholder_base.php` | [doc](methods/S09/classes__placeholders__placeholder_base.md) |
| A | P3 | `classes/placeholders/placeholders/numberofinstallment.php` | [doc](methods/S09/classes__placeholders__placeholders__numberofinstallment.md) |
| A | P3 | `classes/placeholders/placeholders/slotsbooked.php` | [doc](methods/S09/classes__placeholders__placeholders__slotsbooked.md) |
| A | P3 | `classes/placeholders/placeholders/slotscancelled.php` | [doc](methods/S09/classes__placeholders__placeholders__slotscancelled.md) |

### S10 (70 Docs · P1:6 · P2:12 · P3:46)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/output/bookingoption_description.php` | [doc](methods/S10/classes__output__bookingoption_description.md) |
| D | P1 | `classes/output/mobile.php` | [doc](methods/S10/classes__output__mobile.md) |
| D | P1 | `classes/shortcodes.php` | [doc](methods/S10/classes__shortcodes.md) |
| D | P1 | `classes/table/bookingoptions_wbtable.php` | [doc](methods/S10/classes__table__bookingoptions_wbtable.md) |
| D | P1 | `mod/booking/classes/output/view.php` | [doc](methods/S10/classes__output__view.md) |
| C | P1 | `classes/table/manageusers_table.php` | [doc](methods/S10/classes__table__manageusers_table.md) |
| C | P2 | `classes/local/htmlcomponents.php` | [doc](methods/S10/classes__local__htmlcomponents.md) |
| C | P2 | `classes/output/booked_users.php` | [doc](methods/S10/classes__output__booked_users.md) |
| C | P2 | `classes/output/bookingoption_changes.php` | [doc](methods/S10/classes__output__bookingoption_changes.md) |
| C | P2 | `classes/output/bookit_price.php` | [doc](methods/S10/classes__output__bookit_price.md) |
| C | P2 | `classes/output/col_availableplaces.php` | [doc](methods/S10/classes__output__col_availableplaces.md) |
| C | P2 | `classes/table/optiondates_teachers_table.php` | [doc](methods/S10/classes__table__optiondates_teachers_table.md) |
| C | P2 | `classes/table/optiontemplatessettings_table.php` | [doc](methods/S10/classes__table__optiontemplatessettings_table.md) |
| C | P2 | `classes/table/scheduledmails_table.php` | [doc](methods/S10/classes__table__scheduledmails_table.md) |
| C | P2 | `classes/table/teachers_instance_report_table.php` | [doc](methods/S10/classes__table__teachers_instance_report_table.md) |
| B | P2 | `classes/mybookings_table.php` | [doc](methods/S10/classes__mybookings_table.md) |
| B | P2 | `classes/output/col_price.php` | [doc](methods/S10/classes__output__col_price.md) |
| B | P2 | `classes/output/renderer.php` | [doc](methods/S10/classes__output__renderer.md) |
| B | P3 | `classes/output/bookit_button.php` | [doc](methods/S10/classes__output__bookit_button.md) |
| B | P3 | `classes/output/business_card.php` | [doc](methods/S10/classes__output__business_card.md) |
| B | P3 | `classes/output/button_notifyme.php` | [doc](methods/S10/classes__output__button_notifyme.md) |
| B | P3 | `classes/output/campaignslist.php` | [doc](methods/S10/classes__output__campaignslist.md) |
| B | P3 | `classes/output/certificateconditionslist.php` | [doc](methods/S10/classes__output__certificateconditionslist.md) |
| B | P3 | `classes/output/col_coursestarttime.php` | [doc](methods/S10/classes__output__col_coursestarttime.md) |
| B | P3 | `classes/output/col_teacher.php` | [doc](methods/S10/classes__output__col_teacher.md) |
| B | P3 | `classes/output/coursepage_shortinfo_and_button.php` | [doc](methods/S10/classes__output__coursepage_shortinfo_and_button.md) |
| B | P3 | `classes/output/description/description_base.php` | [doc](methods/S10/classes__output__description__description_base.md) |
| B | P3 | `classes/output/elective_modal.php` | [doc](methods/S10/classes__output__elective_modal.md) |
| B | P3 | `classes/output/eventslist.php` | [doc](methods/S10/classes__output__eventslist.md) |
| B | P3 | `classes/output/page_allteachers.php` | [doc](methods/S10/classes__output__page_allteachers.md) |
| B | P3 | `classes/output/page_teacher.php` | [doc](methods/S10/classes__output__page_teacher.md) |
| B | P3 | `classes/output/prepagemodal.php` | [doc](methods/S10/classes__output__prepagemodal.md) |
| B | P3 | `classes/output/ruleslist.php` | [doc](methods/S10/classes__output__ruleslist.md) |
| B | P3 | `classes/output/scheduledmails.php` | [doc](methods/S10/classes__output__scheduledmails.md) |
| B | P3 | `classes/output/signin_downloadform.php` | [doc](methods/S10/classes__output__signin_downloadform.md) |
| B | P3 | `classes/output/subbooking_timeslot_output.php` | [doc](methods/S10/classes__output__subbooking_timeslot_output.md) |
| B | P3 | `classes/shortcodes_handler.php` | [doc](methods/S10/classes__shortcodes_handler.md) |
| B | P3 | `classes/table/event_log_table.php` | [doc](methods/S10/classes__table__event_log_table.md) |
| B | P3 | `classes/table/instancetemplatessettings_table.php` | [doc](methods/S10/classes__table__instancetemplatessettings_table.md) |
| B | P3 | `classes/table/teacher_performed_units_table.php` | [doc](methods/S10/classes__table__teacher_performed_units_table.md) |
| A | P3 | `classes/bookinginstancetemplatessettings_table.php` | [doc](methods/S10/classes__bookinginstancetemplatessettings_table.md) |
| A | P3 | `classes/output/bookingoption_dates.php` | [doc](methods/S10/classes__output__bookingoption_dates.md) |
| A | P3 | `classes/output/col_action.php` | [doc](methods/S10/classes__output__col_action.md) |
| A | P3 | `classes/output/col_responsiblecontacts.php` | [doc](methods/S10/classes__output__col_responsiblecontacts.md) |
| A | P3 | `classes/output/col_text.php` | [doc](methods/S10/classes__output__col_text.md) |
| A | P3 | `classes/output/col_text_with_description.php` | [doc](methods/S10/classes__output__col_text_with_description.md) |
| A | P3 | `classes/output/description/description_calendarevent.php` | [doc](methods/S10/classes__output__description__description_calendarevent.md) |
| A | P3 | `classes/output/description/description_cartitem.php` | [doc](methods/S10/classes__output__description__description_cartitem.md) |
| A | P3 | `classes/output/description/description_dates.php` | [doc](methods/S10/classes__output__description__description_dates.md) |
| A | P3 | `classes/output/description/description_ical.php` | [doc](methods/S10/classes__output__description__description_ical.md) |
| A | P3 | `classes/output/description/description_mail.php` | [doc](methods/S10/classes__output__description__description_mail.md) |
| A | P3 | `classes/output/description/description_optionview.php` | [doc](methods/S10/classes__output__description__description_optionview.md) |
| A | P3 | `classes/output/description/description_teachers.php` | [doc](methods/S10/classes__output__description__description_teachers.md) |
| A | P3 | `classes/output/description/description_website.php` | [doc](methods/S10/classes__output__description__description_website.md) |
| A | P3 | `classes/output/instance_description.php` | [doc](methods/S10/classes__output__instance_description.md) |
| A | P3 | `classes/output/optiondates_only.php` | [doc](methods/S10/classes__output__optiondates_only.md) |
| A | P3 | `classes/output/optiondates_with_entities.php` | [doc](methods/S10/classes__output__optiondates_with_entities.md) |
| A | P3 | `classes/output/prepageinlinestart.php` | [doc](methods/S10/classes__output__prepageinlinestart.md) |
| A | P3 | `classes/output/pricecategories.php` | [doc](methods/S10/classes__output__pricecategories.md) |
| A | P3 | `classes/output/report_edit_bookingnotes.php` | [doc](methods/S10/classes__output__report_edit_bookingnotes.md) |
| A | P3 | `classes/output/semesters_holidays.php` | [doc](methods/S10/classes__output__semesters_holidays.md) |
| A | P3 | `classes/output/subbooking_additionalitem_output.php` | [doc](methods/S10/classes__output__subbooking_additionalitem_output.md) |
| A | P3 | `classes/output/subbooking_additionalperson_output.php` | [doc](methods/S10/classes__output__subbooking_additionalperson_output.md) |
| A | P3 | `classes/output/subbookingslist.php` | [doc](methods/S10/classes__output__subbookingslist.md) |
| — | — | `classes/filters/available_places.php` | [doc](methods/S10/classes__filters__available_places.md) |
| — | — | `classes/local/shortcode_filterfield.php` | [doc](methods/S10/classes__local__shortcode_filterfield.md) |
| — | — | `classes/output/actionslist.php` | [doc](methods/S10/classes__output__actionslist.md) |
| — | — | `classes/table/booking_history_table.php` | [doc](methods/S10/classes__table__booking_history_table.md) |
| — | — | `classes/table/bookingoptions_simple_table.php` | [doc](methods/S10/classes__table__bookingoptions_simple_table.md) |
| — | — | `classes/table/bulkoperations_table.php` | [doc](methods/S10/classes__table__bulkoperations_table.md) |

### S11 (31 Docs · P2:10 · P3:21)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/external/bookings.php` | [doc](methods/S11/classes__external__bookings.md) |
| C | P2 | `classes/external/categories.php` | [doc](methods/S11/classes__external__categories.md) |
| C | P2 | `classes/external/delete_measurement.php` | [doc](methods/S11/classes__external__delete_measurement.md) |
| C | P2 | `classes/external/get_submission_mobile.php` | [doc](methods/S11/classes__external__get_submission_mobile.md) |
| C | P2 | `classes/external/optiontemplate.php` | [doc](methods/S11/classes__external__optiontemplate.md) |
| C | P2 | `classes/external/search_booking_options.php` | [doc](methods/S11/classes__external__search_booking_options.md) |
| C | P2 | `classes/external/search_teachers.php` | [doc](methods/S11/classes__external__search_teachers.md) |
| B | P2 | `classes/external/addbookingoption.php` | [doc](methods/S11/classes__external__addbookingoption.md) |
| B | P2 | `classes/external/bookit.php` | [doc](methods/S11/classes__external__bookit.md) |
| B | P2 | `classes/external/search_courses.php` | [doc](methods/S11/classes__external__search_courses.md) |
| C | P3 | `classes/external/get_option_field_config.php` | [doc](methods/S11/classes__external__get_option_field_config.md) |
| C | P3 | `classes/external/get_parent_categories.php` | [doc](methods/S11/classes__external__get_parent_categories.md) |
| C | P3 | `classes/external/instancetemplate.php` | [doc](methods/S11/classes__external__instancetemplate.md) |
| C | P3 | `classes/external/save_slot_selection.php` | [doc](methods/S11/classes__external__save_slot_selection.md) |
| C | P3 | `classes/external/search_templates.php` | [doc](methods/S11/classes__external__search_templates.md) |
| C | P3 | `classes/external/set_checked_booking_instance.php` | [doc](methods/S11/classes__external__set_checked_booking_instance.md) |
| B | P3 | `classes/external/allow_add_item_to_cart.php` | [doc](methods/S11/classes__external__allow_add_item_to_cart.md) |
| B | P3 | `classes/external/get_booked_slots.php` | [doc](methods/S11/classes__external__get_booked_slots.md) |
| B | P3 | `classes/external/get_booking_option_description.php` | [doc](methods/S11/classes__external__get_booking_option_description.md) |
| B | P3 | `classes/external/get_performance_chart.php` | [doc](methods/S11/classes__external__get_performance_chart.md) |
| B | P3 | `classes/external/get_slots.php` | [doc](methods/S11/classes__external__get_slots.md) |
| B | P3 | `classes/external/init_comments.php` | [doc](methods/S11/classes__external__init_comments.md) |
| B | P3 | `classes/external/load_pre_booking_page.php` | [doc](methods/S11/classes__external__load_pre_booking_page.md) |
| B | P3 | `classes/external/performance.php` | [doc](methods/S11/classes__external__performance.md) |
| B | P3 | `classes/external/release_slots.php` | [doc](methods/S11/classes__external__release_slots.md) |
| B | P3 | `classes/external/save_measurement.php` | [doc](methods/S11/classes__external__save_measurement.md) |
| B | P3 | `classes/external/save_option_field_config.php` | [doc](methods/S11/classes__external__save_option_field_config.md) |
| B | P3 | `classes/external/search_sync_sources.php` | [doc](methods/S11/classes__external__search_sync_sources.md) |
| B | P3 | `classes/external/search_users.php` | [doc](methods/S11/classes__external__search_users.md) |
| B | P3 | `classes/external/toggle_notify_user.php` | [doc](methods/S11/classes__external__toggle_notify_user.md) |
| B | P3 | `classes/external/update_bookingnotes.php` | [doc](methods/S11/classes__external__update_bookingnotes.md) |

### S12 (48 Docs · P2:3 · P3:45)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/event/custom_message_sent.php` | [doc](methods/S12/classes__event__custom_message_sent.md) |
| C | P2 | `classes/event/message_sent.php` | [doc](methods/S12/classes__event__message_sent.md) |
| C | P2 | `classes/observer.php` | [doc](methods/S12/classes__observer.md) |
| B | P3 | `classes/event/booking_afteractionsfailed.php` | [doc](methods/S12/classes__event__booking_afteractionsfailed.md) |
| B | P3 | `classes/event/booking_debug.php` | [doc](methods/S12/classes__event__booking_debug.md) |
| B | P3 | `classes/event/booking_failed.php` | [doc](methods/S12/classes__event__booking_failed.md) |
| B | P3 | `classes/event/booking_rulesexecutionfailes.php` | [doc](methods/S12/classes__event__booking_rulesexecutionfailes.md) |
| B | P3 | `classes/event/bookinganswer_cancelled.php` | [doc](methods/S12/classes__event__bookinganswer_cancelled.md) |
| B | P3 | `classes/event/bookinganswer_confirmed.php` | [doc](methods/S12/classes__event__bookinganswer_confirmed.md) |
| B | P3 | `classes/event/bookinganswer_denied.php` | [doc](methods/S12/classes__event__bookinganswer_denied.md) |
| B | P3 | `classes/event/bookinganswer_movedupfromwaitinglist.php` | [doc](methods/S12/classes__event__bookinganswer_movedupfromwaitinglist.md) |
| B | P3 | `classes/event/bookinganswer_notesedited.php` | [doc](methods/S12/classes__event__bookinganswer_notesedited.md) |
| B | P3 | `classes/event/bookinganswer_presencechanged.php` | [doc](methods/S12/classes__event__bookinganswer_presencechanged.md) |
| B | P3 | `classes/event/bookinganswer_slotbooked.php` | [doc](methods/S12/classes__event__bookinganswer_slotbooked.md) |
| B | P3 | `classes/event/bookinganswer_slotcancelled.php` | [doc](methods/S12/classes__event__bookinganswer_slotcancelled.md) |
| B | P3 | `classes/event/bookinganswer_slotmoved.php` | [doc](methods/S12/classes__event__bookinganswer_slotmoved.md) |
| B | P3 | `classes/event/bookinganswer_waitingforconfirmation.php` | [doc](methods/S12/classes__event__bookinganswer_waitingforconfirmation.md) |
| B | P3 | `classes/event/bookinganswercustomformconditions_deleted.php` | [doc](methods/S12/classes__event__bookinganswercustomformconditions_deleted.md) |
| B | P3 | `classes/event/bookinginstance_updated.php` | [doc](methods/S12/classes__event__bookinginstance_updated.md) |
| B | P3 | `classes/event/bookingoption_booked.php` | [doc](methods/S12/classes__event__bookingoption_booked.md) |
| B | P3 | `classes/event/bookingoption_bookedviaautoenrol.php` | [doc](methods/S12/classes__event__bookingoption_bookedviaautoenrol.md) |
| B | P3 | `classes/event/bookingoption_cancelled.php` | [doc](methods/S12/classes__event__bookingoption_cancelled.md) |
| B | P3 | `classes/event/bookingoption_completed.php` | [doc](methods/S12/classes__event__bookingoption_completed.md) |
| B | P3 | `classes/event/bookingoption_created.php` | [doc](methods/S12/classes__event__bookingoption_created.md) |
| B | P3 | `classes/event/bookingoption_deleted.php` | [doc](methods/S12/classes__event__bookingoption_deleted.md) |
| B | P3 | `classes/event/bookingoption_freetobookagain.php` | [doc](methods/S12/classes__event__bookingoption_freetobookagain.md) |
| B | P3 | `classes/event/bookingoption_uncompleted.php` | [doc](methods/S12/classes__event__bookingoption_uncompleted.md) |
| B | P3 | `classes/event/bookingoption_updated.php` | [doc](methods/S12/classes__event__bookingoption_updated.md) |
| B | P3 | `classes/event/bookingoptiondate_created.php` | [doc](methods/S12/classes__event__bookingoptiondate_created.md) |
| B | P3 | `classes/event/bookingoptiondate_deleted.php` | [doc](methods/S12/classes__event__bookingoptiondate_deleted.md) |
| B | P3 | `classes/event/bookingoptionwaitinglist_booked.php` | [doc](methods/S12/classes__event__bookingoptionwaitinglist_booked.md) |
| B | P3 | `classes/event/certificate_issued.php` | [doc](methods/S12/classes__event__certificate_issued.md) |
| B | P3 | `classes/event/course_module_viewed.php` | [doc](methods/S12/classes__event__course_module_viewed.md) |
| B | P3 | `classes/event/custom_bulk_message_sent.php` | [doc](methods/S12/classes__event__custom_bulk_message_sent.md) |
| B | P3 | `classes/event/custom_field_changed.php` | [doc](methods/S12/classes__event__custom_field_changed.md) |
| B | P3 | `classes/event/enrollink_triggered.php` | [doc](methods/S12/classes__event__enrollink_triggered.md) |
| B | P3 | `classes/event/optiondates_teacher_added.php` | [doc](methods/S12/classes__event__optiondates_teacher_added.md) |
| B | P3 | `classes/event/optiondates_teacher_deleted.php` | [doc](methods/S12/classes__event__optiondates_teacher_deleted.md) |
| B | P3 | `classes/event/pricecategory_changed.php` | [doc](methods/S12/classes__event__pricecategory_changed.md) |
| B | P3 | `classes/event/records_imported.php` | [doc](methods/S12/classes__event__records_imported.md) |
| B | P3 | `classes/event/reminder1_sent.php` | [doc](methods/S12/classes__event__reminder1_sent.md) |
| B | P3 | `classes/event/reminder2_sent.php` | [doc](methods/S12/classes__event__reminder2_sent.md) |
| B | P3 | `classes/event/reminder_teacher_sent.php` | [doc](methods/S12/classes__event__reminder_teacher_sent.md) |
| B | P3 | `classes/event/report_viewed.php` | [doc](methods/S12/classes__event__report_viewed.md) |
| B | P3 | `classes/event/rest_script_failed.php` | [doc](methods/S12/classes__event__rest_script_failed.md) |
| B | P3 | `classes/event/rest_script_success.php` | [doc](methods/S12/classes__event__rest_script_success.md) |
| B | P3 | `classes/event/teacher_added.php` | [doc](methods/S12/classes__event__teacher_added.md) |
| B | P3 | `classes/event/teacher_removed.php` | [doc](methods/S12/classes__event__teacher_removed.md) |

### S13 (19 Docs · P1:2 · P2:8 · P3:9)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/task/confirm_bookinganswer_by_rule_adhoc.php` | [doc](methods/S13/classes__task__confirm_bookinganswer_by_rule_adhoc.md) |
| C | P1 | `classes/task/send_mail_by_rule_adhoc.php` | [doc](methods/S13/classes__task__send_mail_by_rule_adhoc.md) |
| C | P2 | `classes/task/delete_conditions_from_bookinganswer_by_rule_adhoc.php` | [doc](methods/S13/classes__task__delete_conditions_from_bookinganswer_by_rule_adhoc.md) |
| C | P2 | `classes/task/enrol_bookedusers_tocourse.php` | [doc](methods/S13/classes__task__enrol_bookedusers_tocourse.md) |
| C | P2 | `classes/task/purge_campaign_caches.php` | [doc](methods/S13/classes__task__purge_campaign_caches.md) |
| C | P2 | `classes/task/remove_activity_completion.php` | [doc](methods/S13/classes__task__remove_activity_completion.md) |
| C | P2 | `classes/task/send_confirmation_mails.php` | [doc](methods/S13/classes__task__send_confirmation_mails.md) |
| C | P2 | `classes/task/send_notification_mails.php` | [doc](methods/S13/classes__task__send_notification_mails.md) |
| C | P2 | `classes/task/send_reminder_mails.php` | [doc](methods/S13/classes__task__send_reminder_mails.md) |
| B | P2 | `classes/task/finalize_template_course.php` | [doc](methods/S13/classes__task__finalize_template_course.md) |
| B | P3 | `classes/task/clean_booking_db.php` | [doc](methods/S13/classes__task__clean_booking_db.md) |
| B | P3 | `classes/task/recalculate_prices.php` | [doc](methods/S13/classes__task__recalculate_prices.md) |
| B | P3 | `classes/task/send_completion_mails.php` | [doc](methods/S13/classes__task__send_completion_mails.md) |
| A | P3 | `classes/task/assign_competency.php` | [doc](methods/S13/classes__task__assign_competency.md) |
| A | P3 | `classes/task/book_all_students_task.php` | [doc](methods/S13/classes__task__book_all_students_task.md) |
| A | P3 | `classes/task/check_answers.php` | [doc](methods/S13/classes__task__check_answers.md) |
| A | P3 | `classes/task/cleanup_invalid_scheduled_mails.php` | [doc](methods/S13/classes__task__cleanup_invalid_scheduled_mails.md) |
| A | P3 | `classes/task/process_source_membership_adhoc.php` | [doc](methods/S13/classes__task__process_source_membership_adhoc.md) |
| A | P3 | `classes/task/task_adhoc_reset_optiondates_for_semester.php` | [doc](methods/S13/classes__task__task_adhoc_reset_optiondates_for_semester.md) |

### S14 (13 Docs · P1:1 · P2:3 · P3:9)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P1 | `classes/local/slotbooking/slot_mover.php` | [doc](methods/S14/classes__local__slotbooking__slot_mover.md) |
| C | P2 | `classes/local/slotbooking/slot_availability.php` | [doc](methods/S14/classes__local__slotbooking__slot_availability.md) |
| C | P2 | `classes/local/slotbooking/slot_dto.php` | [doc](methods/S14/classes__local__slotbooking__slot_dto.md) |
| C | P2 | `classes/local/slotbooking/slot_update_service.php` | [doc](methods/S14/classes__local__slotbooking__slot_update_service.md) |
| B | P3 | `classes/local/slotbooking/slot_move_store.php` | [doc](methods/S14/classes__local__slotbooking__slot_move_store.md) |
| B | P3 | `classes/local/slotbooking/slot_price.php` | [doc](methods/S14/classes__local__slotbooking__slot_price.md) |
| B | P3 | `classes/local/slotbooking/slot_rule_manager.php` | [doc](methods/S14/classes__local__slotbooking__slot_rule_manager.md) |
| B | P3 | `classes/local/slotbooking/target_price_policy.php` | [doc](methods/S14/classes__local__slotbooking__target_price_policy.md) |
| A | P3 | `classes/local/slotbooking/slot_answer.php` | [doc](methods/S14/classes__local__slotbooking__slot_answer.md) |
| A | P3 | `classes/local/slotbooking/slot_change_policy.php` | [doc](methods/S14/classes__local__slotbooking__slot_change_policy.md) |
| A | P3 | `classes/local/slotbooking/slot_event_placeholders.php` | [doc](methods/S14/classes__local__slotbooking__slot_event_placeholders.md) |
| A | P3 | `classes/local/slotbooking/slot_feature.php` | [doc](methods/S14/classes__local__slotbooking__slot_feature.md) |
| A | P3 | `classes/local/slotbooking/slot_rules.php` | [doc](methods/S14/classes__local__slotbooking__slot_rules.md) |

### S15 (39 Docs · P1:3 · P2:16 · P3:20)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/local/wizard/booking/booking_skill_mutation_execute_service.php` | [doc](methods/S15/classes__local__wizard__booking__booking_skill_mutation_execute_service.md) |
| D | P1 | `classes/local/wizard/booking/booking_skill_support.php` | [doc](methods/S15/classes__local__wizard__booking__booking_skill_support.md) |
| D | P1 | `classes/local/wizard/booking/support/booking_mutation_validation.php` | [doc](methods/S15/classes__local__wizard__booking__support__booking_mutation_validation.md) |
| C | P2 | `classes/local/wizard/options/skills/analyze_rules_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__analyze_rules_skill.md) |
| C | P2 | `classes/local/wizard/options/skills/book_users_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__book_users_skill.md) |
| C | P2 | `classes/local/wizard/options/skills/booking_skill_base.php` | [doc](methods/S15/classes__local__wizard__options__skills__booking_skill_base.md) |
| C | P2 | `classes/local/wizard/options/skills/create_option_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__create_option_skill.md) |
| C | P2 | `classes/local/wizard/options/skills/create_rule_from_template_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__create_rule_from_template_skill.md) |
| C | P2 | `classes/local/wizard/options/skills/diagnose_booking_issue_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__diagnose_booking_issue_skill.md) |
| C | P2 | `classes/local/wizard/options/skills/diagnose_cancellation_issue_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__diagnose_cancellation_issue_skill.md) |
| C | P2 | `classes/local/wizard/options/skills/option_input_verification.php` | [doc](methods/S15/classes__local__wizard__options__skills__option_input_verification.md) |
| B | P2 | `classes/local/wizard/booking/support/booking_rules_agent_service.php` | [doc](methods/S15/classes__local__wizard__booking__support__booking_rules_agent_service.md) |
| B | P2 | `classes/local/wizard/options/skills/bulk_update_options_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__bulk_update_options_skill.md) |
| B | P2 | `classes/local/wizard/options/skills/diagnose_user_booking_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__diagnose_user_booking_skill.md) |
| B | P2 | `classes/local/wizard/options/skills/get_option_details_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__get_option_details_skill.md) |
| B | P2 | `classes/local/wizard/options/skills/option_schema_definition.php` | [doc](methods/S15/classes__local__wizard__options__skills__option_schema_definition.md) |
| B | P2 | `classes/local/wizard/options/skills/search_options_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__search_options_skill.md) |
| B | P2 | `classes/local/wizard/options/skills/update_option_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__update_option_skill.md) |
| B | P2 | `classes/local/wizard/options/skills/update_option_trainer_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__update_option_trainer_skill.md) |
| B | P3 | `classes/local/interfaces/bookingextension/confirmbooking_interface.php` | [doc](methods/S15/classes__local__interfaces__bookingextension__confirmbooking_interface.md) |
| B | P3 | `classes/local/wizard/booking/booking_readiness_provider.php` | [doc](methods/S15/classes__local__wizard__booking__booking_readiness_provider.md) |
| B | P3 | `classes/local/wizard/booking/booking_skill_provider.php` | [doc](methods/S15/classes__local__wizard__booking__booking_skill_provider.md) |
| B | P3 | `classes/local/wizard/booking/support/slot_booking_normalizer.php` | [doc](methods/S15/classes__local__wizard__booking__support__slot_booking_normalizer.md) |
| B | P3 | `classes/local/wizard/options/skills/add_price_category_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__add_price_category_skill.md) |
| B | P3 | `classes/local/wizard/options/skills/create_selflearning_option_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__create_selflearning_option_skill.md) |
| B | P3 | `classes/local/wizard/options/skills/list_option_properties_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__list_option_properties_skill.md) |
| B | P3 | `classes/local/wizard/options/skills/update_rule_from_template_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__update_rule_from_template_skill.md) |
| B | P3 | `classes/local/wizard/services/mutation/entity_mutation_service.php` | [doc](methods/S15/classes__local__wizard__services__mutation__entity_mutation_service.md) |
| A | P3 | `classes/local/wizard/booking/provider_skill_input_normalizer.php` | [doc](methods/S15/classes__local__wizard__booking__provider_skill_input_normalizer.md) |
| A | P3 | `classes/local/wizard/booking_option_preview_renderer.php` | [doc](methods/S15/classes__local__wizard__booking_option_preview_renderer.md) |
| A | P3 | `classes/local/wizard/dto/bulk_update_options_input_dto.php` | [doc](methods/S15/classes__local__wizard__dto__bulk_update_options_input_dto.md) |
| A | P3 | `classes/local/wizard/dto/create_entity_input_dto.php` | [doc](methods/S15/classes__local__wizard__dto__create_entity_input_dto.md) |
| A | P3 | `classes/local/wizard/dto/create_option_input_dto.php` | [doc](methods/S15/classes__local__wizard__dto__create_option_input_dto.md) |
| A | P3 | `classes/local/wizard/dto/update_option_input_dto.php` | [doc](methods/S15/classes__local__wizard__dto__update_option_input_dto.md) |
| A | P3 | `classes/local/wizard/options/skills/configure_booking_instance_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__configure_booking_instance_skill.md) |
| A | P3 | `classes/local/wizard/options/skills/create_slotbooking_option_skill.php` | [doc](methods/S15/classes__local__wizard__options__skills__create_slotbooking_option_skill.md) |
| A | P3 | `classes/local/wizard/services/lookup/option_lookup_service.php` | [doc](methods/S15/classes__local__wizard__services__lookup__option_lookup_service.md) |
| A | P3 | `classes/local/wizard/services/mutation/option_mutation_service.php` | [doc](methods/S15/classes__local__wizard__services__mutation__option_mutation_service.md) |
| A | P3 | `classes/local/wizard/skill_provider.php` | [doc](methods/S15/classes__local__wizard__skill_provider.md) |

### S16 (47 Docs · P2:14 · P3:30)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P2 | `classes/form/customfield.php` | [doc](methods/S16/classes__form__customfield.md) |
| D | P2 | `classes/form/dynamicdeputyselect.php` | [doc](methods/S16/classes__form__dynamicdeputyselect.md) |
| C | P2 | `classes/form/condition/customform_form.php` | [doc](methods/S16/classes__form__condition__customform_form.md) |
| C | P2 | `classes/form/condition/slotbooking_form.php` | [doc](methods/S16/classes__form__condition__slotbooking_form.md) |
| C | P2 | `classes/form/editteachersforoptiondate_form.php` | [doc](methods/S16/classes__form__editteachersforoptiondate_form.md) |
| C | P2 | `classes/form/modal_send_custom_message.php` | [doc](methods/S16/classes__form__modal_send_custom_message.md) |
| C | P2 | `classes/form/option_form_bulk.php` | [doc](methods/S16/classes__form__option_form_bulk.md) |
| C | P2 | `classes/form/optiondates/modal_change_notes.php` | [doc](methods/S16/classes__form__optiondates__modal_change_notes.md) |
| C | P2 | `classes/form/optiondates/modal_change_status.php` | [doc](methods/S16/classes__form__optiondates__modal_change_status.md) |
| C | P2 | `classes/form/rulesform.php` | [doc](methods/S16/classes__form__rulesform.md) |
| C | P2 | `classes/form/send_mail_to_teachers.php` | [doc](methods/S16/classes__form__send_mail_to_teachers.md) |
| C | P2 | `classes/form/subbooking/additionalperson_form.php` | [doc](methods/S16/classes__form__subbooking__additionalperson_form.md) |
| C | P2 | `classes/form/teacherunavailability_form.php` | [doc](methods/S16/classes__form__teacherunavailability_form.md) |
| B | P2 | `classes/form/slotteacherassignments_form.php` | [doc](methods/S16/classes__form__slotteacherassignments_form.md) |
| C | P3 | `classes/form/confirmactivity.php` | [doc](methods/S16/classes__form__confirmactivity.md) |
| C | P3 | `classes/form/dynamicholidaysform.php` | [doc](methods/S16/classes__form__dynamicholidaysform.md) |
| C | P3 | `classes/form/dynamicsemestersform.php` | [doc](methods/S16/classes__form__dynamicsemestersform.md) |
| B | P3 | `classes/form/actions/actionsform.php` | [doc](methods/S16/classes__form__actions__actionsform.md) |
| B | P3 | `classes/form/campaignsform.php` | [doc](methods/S16/classes__form__campaignsform.md) |
| B | P3 | `classes/form/certificateconditionsform.php` | [doc](methods/S16/classes__form__certificateconditionsform.md) |
| B | P3 | `classes/form/condition/bookingpolicy_form.php` | [doc](methods/S16/classes__form__condition__bookingpolicy_form.md) |
| B | P3 | `classes/form/condition/slotupdate_form.php` | [doc](methods/S16/classes__form__condition__slotupdate_form.md) |
| B | P3 | `classes/form/csvimport.php` | [doc](methods/S16/classes__form__csvimport.md) |
| B | P3 | `classes/form/deletecampaignform.php` | [doc](methods/S16/classes__form__deletecampaignform.md) |
| B | P3 | `classes/form/deletecertificateconditionform.php` | [doc](methods/S16/classes__form__deletecertificateconditionform.md) |
| B | P3 | `classes/form/deleteruleform.php` | [doc](methods/S16/classes__form__deleteruleform.md) |
| B | P3 | `classes/form/dynamicchangesemesterform.php` | [doc](methods/S16/classes__form__dynamicchangesemesterform.md) |
| B | P3 | `classes/form/dynamicoptiondateform.php` | [doc](methods/S16/classes__form__dynamicoptiondateform.md) |
| B | P3 | `classes/form/importoptions_form.php` | [doc](methods/S16/classes__form__importoptions_form.md) |
| B | P3 | `classes/form/modal_confirmcancel.php` | [doc](methods/S16/classes__form__modal_confirmcancel.md) |
| B | P3 | `classes/form/modal_editteacherdescription.php` | [doc](methods/S16/classes__form__modal_editteacherdescription.md) |
| B | P3 | `classes/form/modaloptiondateform.php` | [doc](methods/S16/classes__form__modaloptiondateform.md) |
| B | P3 | `classes/form/option_form.php` | [doc](methods/S16/classes__form__option_form.md) |
| B | P3 | `classes/form/pricecategories_form.php` | [doc](methods/S16/classes__form__pricecategories_form.md) |
| B | P3 | `classes/form/slotrules_page_form.php` | [doc](methods/S16/classes__form__slotrules_page_form.md) |
| B | P3 | `classes/form/subbookingsdeleteform.php` | [doc](methods/S16/classes__form__subbookingsdeleteform.md) |
| B | P3 | `classes/form/subbookingsform.php` | [doc](methods/S16/classes__form__subbookingsform.md) |
| B | P3 | `classes/form/subscribe_cohort_or_group_form.php` | [doc](methods/S16/classes__form__subscribe_cohort_or_group_form.md) |
| B | P3 | `classes/form/subscribeusersactivity.php` | [doc](methods/S16/classes__form__subscribeusersactivity.md) |
| B | P3 | `classes/form/sync_rule_activate_form.php` | [doc](methods/S16/classes__form__sync_rule_activate_form.md) |
| B | P3 | `classes/form/sync_rule_delete_form.php` | [doc](methods/S16/classes__form__sync_rule_delete_form.md) |
| B | P3 | `classes/form/sync_rule_form.php` | [doc](methods/S16/classes__form__sync_rule_form.md) |
| B | P3 | `classes/local/customform_prefill.php` | [doc](methods/S16/classes__local__customform_prefill.md) |
| A | P3 | `classes/form/instancetemplateadd_form.php` | [doc](methods/S16/classes__form__instancetemplateadd_form.md) |
| — | — | `classes/form/actions/deleteactionsform.php` | [doc](methods/S16/classes__form__actions__deleteactionsform.md) |
| — | — | `classes/form/teacher_performed_units_report_form.php` | [doc](methods/S16/classes__form__teacher_performed_units_report_form.md) |
| — | — | `classes/form/teachers_instance_report_form.php` | [doc](methods/S16/classes__form__teachers_instance_report_form.md) |

### S17 (28 Docs · P1:1 · P2:5 · P3:14)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| E | P1 | `classes/signinsheet/signinsheet_generator.php` | [doc](methods/S17/classes__signinsheet__signinsheet_generator.md) |
| C | P2 | `classes/local/performance/performance_measurer.php` | [doc](methods/S17/classes__local__performance__performance_measurer.md) |
| B | P2 | `classes/checklist/checklist_generator.php` | [doc](methods/S17/classes__checklist__checklist_generator.md) |
| B | P2 | `classes/local/checkanswers/checkanswers.php` | [doc](methods/S17/classes__local__checkanswers__checkanswers.md) |
| B | P2 | `classes/local/performance/performance_facade.php` | [doc](methods/S17/classes__local__performance__performance_facade.md) |
| B | P2 | `classes/local/performance/performance_renderer.php` | [doc](methods/S17/classes__local__performance__performance_renderer.md) |
| B | P3 | `classes/local/performance/table/measurements_table.php` | [doc](methods/S17/classes__local__performance__table__measurements_table.md) |
| B | P3 | `classes/local/performance/table/performance_table.php` | [doc](methods/S17/classes__local__performance__table__performance_table.md) |
| B | P3 | `classes/reportbuilder/datasource/booking_answers_datasource.php` | [doc](methods/S17/classes__reportbuilder__datasource__booking_answers_datasource.md) |
| B | P3 | `classes/reportbuilder/local/entities/booking_answers.php` | [doc](methods/S17/classes__reportbuilder__local__entities__booking_answers.md) |
| B | P3 | `classes/reportbuilder/local/entities/booking_options.php` | [doc](methods/S17/classes__reportbuilder__local__entities__booking_options.md) |
| A | P3 | `classes/local/bookingstracker/bookingstracker_helper.php` | [doc](methods/S17/classes__local__bookingstracker__bookingstracker_helper.md) |
| A | P3 | `classes/local/checkanswers/actions/deleteanswer.php` | [doc](methods/S17/classes__local__checkanswers__actions__deleteanswer.md) |
| A | P3 | `classes/local/checkanswers/checks/cmvisibility.php` | [doc](methods/S17/classes__local__checkanswers__checks__cmvisibility.md) |
| A | P3 | `classes/local/checkanswers/checks/enrolledincourse.php` | [doc](methods/S17/classes__local__checkanswers__checks__enrolledincourse.md) |
| A | P3 | `classes/local/performance/actions/action_executor.php` | [doc](methods/S17/classes__local__performance__actions__action_executor.md) |
| A | P3 | `classes/local/performance/actions/action_registry.php` | [doc](methods/S17/classes__local__performance__actions__action_registry.md) |
| A | P3 | `classes/reportbuilder/datasource/booking_options_datasource.php` | [doc](methods/S17/classes__reportbuilder__datasource__booking_options_datasource.md) |
| A | P3 | `classes/reportbuilder/local/filters/cohort_selector.php` | [doc](methods/S17/classes__reportbuilder__local__filters__cohort_selector.md) |
| A | P3 | `classes/reportbuilder/local/filters/profile_field_current_user.php` | [doc](methods/S17/classes__reportbuilder__local__filters__profile_field_current_user.md) |
| — | — | `classes/checklist/checklist_pdf.php` | [doc](methods/S17/classes__checklist__checklist_pdf.md) |
| — | — | `classes/local/performance/actions/execution_point.php` | [doc](methods/S17/classes__local__performance__actions__execution_point.md) |
| — | — | `classes/local/performance/actions/execution_times.php` | [doc](methods/S17/classes__local__performance__actions__execution_times.md) |
| — | — | `classes/local/performance/actions/performance_action_interface.php` | [doc](methods/S17/classes__local__performance__actions__performance_action_interface.md) |
| — | — | `classes/local/performance/actions/purge_cache_action_before.php` | [doc](methods/S17/classes__local__performance__actions__purge_cache_action_before.md) |
| — | — | `classes/local/performance/actions/purge_cache_action_inbetween.php` | [doc](methods/S17/classes__local__performance__actions__purge_cache_action_inbetween.md) |
| — | — | `classes/reportbuilder/local/filters/timestamp_years_past.php` | [doc](methods/S17/classes__reportbuilder__local__filters__timestamp_years_past.md) |
| — | — | `classes/signinsheet/signin_pdf.php` | [doc](methods/S17/classes__signinsheet__signin_pdf.md) |

### S18 (4 Docs · P2:2 · P3:2)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/import/fileparser.php` | [doc](methods/S18/classes__import__fileparser.md) |
| B | P2 | `classes/importer/bookingoptionsimporter.php` | [doc](methods/S18/classes__importer__bookingoptionsimporter.md) |
| C | P3 | `classes/import/csvcolumn.php` | [doc](methods/S18/classes__import__csvcolumn.md) |
| B | P3 | `classes/import/csvsettings.php` | [doc](methods/S18/classes__import__csvsettings.md) |

### S19 (14 Docs · P2:5 · P3:6)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/local/certificate_conditions/certificate_conditions.php` | [doc](methods/S19/classes__local__certificate_conditions__certificate_conditions.md) |
| C | P2 | `classes/local/certificate_conditions/conditions/taggedoptions.php` | [doc](methods/S19/classes__local__certificate_conditions__conditions__taggedoptions.md) |
| C | P2 | `classes/local/certificateclass.php` | [doc](methods/S19/classes__local__certificateclass.md) |
| B | P2 | `classes/local/certificate_conditions/conditions/bookingoption.php` | [doc](methods/S19/classes__local__certificate_conditions__conditions__bookingoption.md) |
| B | P2 | `classes/local/competencies/competencies_handler.php` | [doc](methods/S19/classes__local__competencies__competencies_handler.md) |
| B | P3 | `classes/local/certificate_conditions/actions/createcertificate.php` | [doc](methods/S19/classes__local__certificate_conditions__actions__createcertificate.md) |
| B | P3 | `classes/local/certificate_conditions/actions_info.php` | [doc](methods/S19/classes__local__certificate_conditions__actions_info.md) |
| B | P3 | `classes/local/certificate_conditions/filters/userprofilefield.php` | [doc](methods/S19/classes__local__certificate_conditions__filters__userprofilefield.md) |
| B | P3 | `classes/local/certificate_conditions/filters_info.php` | [doc](methods/S19/classes__local__certificate_conditions__filters_info.md) |
| B | P3 | `classes/local/certificate_conditions/option_conditions_info.php` | [doc](methods/S19/classes__local__certificate_conditions__option_conditions_info.md) |
| A | P3 | `classes/local/certificate_conditions/action_interface.php` | [doc](methods/S19/classes__local__certificate_conditions__action_interface.md) |
| — | — | `classes/local/certificate_conditions/certificate_conditions_interface.php` | [doc](methods/S19/classes__local__certificate_conditions__certificate_conditions_interface.md) |
| — | — | `classes/local/certificate_conditions/conditions_info.php` | [doc](methods/S19/classes__local__certificate_conditions__conditions_info.md) |
| — | — | `classes/local/certificate_conditions/filter_interface.php` | [doc](methods/S19/classes__local__certificate_conditions__filter_interface.md) |

### S20 (3 Docs · P1:2 · P2:1)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P1 | `classes/enrollink.php` | [doc](methods/S20/classes__enrollink.md) |
| C | P1 | `classes/local/sync/booking_enrolment.php` | [doc](methods/S20/classes__local__sync__booking_enrolment.md) |
| C | P2 | `classes/local/connectedcourse.php` | [doc](methods/S20/classes__local__connectedcourse.md) |

### S21 (72 Docs · P0:1 · P1:6 · P2:14 · P3:37)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| E | P0 | `report.php` | [doc](methods/S21/report.md) |
| E | P1 | `mod/booking/mod_form.php` | [doc](methods/S21/mod_form.md) |
| D | P1 | `mod/booking/subscribeusers.php` | [doc](methods/S21/subscribeusers.md) |
| D | P1 | `optiontemplatessettings.php` | [doc](methods/S21/optiontemplatessettings.md) |
| D | P1 | `report2.php` | [doc](methods/S21/report2.md) |
| D | P1 | `slotrules.php` | [doc](methods/S21/slotrules.md) |
| C | P1 | `edit_optiontemplates.php` | [doc](methods/S21/edit_optiontemplates.md) |
| D | P2 | `optionview.php` | [doc](methods/S21/optionview.md) |
| D | P2 | `subbooking_timetabletest.php` | [doc](methods/S21/subbooking_timetabletest.md) |
| D | P2 | `teacher_performed_units_report.php` | [doc](methods/S21/teacher_performed_units_report.md) |
| D | P2 | `teachers_instance_report.php` | [doc](methods/S21/teachers_instance_report.md) |
| C | P2 | `importexcel.php` | [doc](methods/S21/importexcel.md) |
| C | P2 | `link.php` | [doc](methods/S21/link.md) |
| C | P2 | `recalculateprices.php` | [doc](methods/S21/recalculateprices.md) |
| C | P2 | `sendmessage.php` | [doc](methods/S21/sendmessage.md) |
| C | P2 | `view.php` | [doc](methods/S21/view.md) |
| B | P2 | `bookinginstancetemplatessettings.php` | [doc](methods/S21/bookinginstancetemplatessettings.md) |
| B | P2 | `edit_certificateconditions.php` | [doc](methods/S21/edit_certificateconditions.md) |
| B | P2 | `edit_rules.php` | [doc](methods/S21/edit_rules.md) |
| B | P2 | `enrollink.php` | [doc](methods/S21/enrollink.md) |
| B | P2 | `instancetemplatessettings.php` | [doc](methods/S21/instancetemplatessettings.md) |
| C | P3 | `availabilityconditions.php` | [doc](methods/S21/availabilityconditions.md) |
| C | P3 | `categories.php` | [doc](methods/S21/categories.md) |
| C | P3 | `category.php` | [doc](methods/S21/category.md) |
| C | P3 | `categoryadd.php` | [doc](methods/S21/categoryadd.md) |
| C | P3 | `moveoption.php` | [doc](methods/S21/moveoption.md) |
| C | P3 | `optiondates_teachers_report.php` | [doc](methods/S21/optiondates_teachers_report.md) |
| C | P3 | `otherbooking.php` | [doc](methods/S21/otherbooking.md) |
| C | P3 | `otherbookingaddrule.php` | [doc](methods/S21/otherbookingaddrule.md) |
| C | P3 | `otherbookingaddrule_form.php` | [doc](methods/S21/otherbookingaddrule_form.md) |
| C | P3 | `rating_rest.php` | [doc](methods/S21/rating_rest.md) |
| C | P3 | `scheduledmails.php` | [doc](methods/S21/scheduledmails.md) |
| C | P3 | `teachers.php` | [doc](methods/S21/teachers.md) |
| C | P3 | `teachers_form.php` | [doc](methods/S21/teachers_form.md) |
| B | P3 | `bookingredirect.php` | [doc](methods/S21/bookingredirect.md) |
| B | P3 | `categoriesform.class.php` | [doc](methods/S21/categoriesform.class.md) |
| B | P3 | `confirmactivity.php` | [doc](methods/S21/confirmactivity.md) |
| B | P3 | `download.php` | [doc](methods/S21/download.md) |
| B | P3 | `download_optiondates_teachers_report.php` | [doc](methods/S21/download_optiondates_teachers_report.md) |
| B | P3 | `download_report2.php` | [doc](methods/S21/download_report2.md) |
| B | P3 | `editoptions.php` | [doc](methods/S21/editoptions.md) |
| B | P3 | `index.php` | [doc](methods/S21/index.md) |
| B | P3 | `instancetemplateadd.php` | [doc](methods/S21/instancetemplateadd.md) |
| B | P3 | `mybookings.php` | [doc](methods/S21/mybookings.md) |
| B | P3 | `option_date_template.php` | [doc](methods/S21/option_date_template.md) |
| B | P3 | `optionformconfig.php` | [doc](methods/S21/optionformconfig.md) |
| B | P3 | `sendmessageform.class.php` | [doc](methods/S21/sendmessageform.class.md) |
| B | P3 | `slotcalendar.php` | [doc](methods/S21/slotcalendar.md) |
| B | P3 | `subscribeusersactivity.php` | [doc](methods/S21/subscribeusersactivity.md) |
| B | P3 | `tag.php` | [doc](methods/S21/tag.md) |
| B | P3 | `tagtemplates.php` | [doc](methods/S21/tagtemplates.md) |
| B | P3 | `tagtemplatesadd.php` | [doc](methods/S21/tagtemplatesadd.md) |
| B | P3 | `teacherunavailability.php` | [doc](methods/S21/teacherunavailability.md) |
| B | P3 | `unsubscribe.php` | [doc](methods/S21/unsubscribe.md) |
| A | P3 | `customfieldsettings.php` | [doc](methods/S21/customfieldsettings.md) |
| A | P3 | `edit_campaigns.php` | [doc](methods/S21/edit_campaigns.md) |
| A | P3 | `viewconfirmation.php` | [doc](methods/S21/viewconfirmation.md) |
| A | P3 | `viewpolicy.php` | [doc](methods/S21/viewpolicy.md) |
| — | — | `bulk_book_handler.php` | [doc](methods/S21/bulk_book_handler.md) |
| — | — | `customfield.php` | [doc](methods/S21/customfield.md) |
| — | — | `importexcel_form.php` | [doc](methods/S21/importexcel_form.md) |
| — | — | `importoptions.php` | [doc](methods/S21/importoptions.md) |
| — | — | `moveslot.php` | [doc](methods/S21/moveslot.md) |
| — | — | `performance.php` | [doc](methods/S21/performance.md) |
| — | — | `pricecategories.php` | [doc](methods/S21/pricecategories.md) |
| — | — | `rebookslot.php` | [doc](methods/S21/rebookslot.md) |
| — | — | `search_sync_sources.php` | [doc](methods/S21/search_sync_sources.md) |
| — | — | `semesters.php` | [doc](methods/S21/semesters.md) |
| — | — | `slotteacherassignments.php` | [doc](methods/S21/slotteacherassignments.md) |
| — | — | `sync_diagnostics.php` | [doc](methods/S21/sync_diagnostics.md) |
| — | — | `tagtemplatesadd_form.php` | [doc](methods/S21/tagtemplatesadd_form.md) |
| — | — | `teacher.php` | [doc](methods/S21/teacher.md) |

### S22 (22 Docs · P1:2 · P2:3 · P3:14)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `classes/utils/webservice_import.php` | [doc](methods/S22/classes__utils__webservice_import.md) |
| C | P1 | `mod/booking/lib.php` | [doc](methods/S22/lib.md) |
| D | P2 | `classes/local/sql/operator_builder.php` | [doc](methods/S22/classes__local__sql__operator_builder.md) |
| D | P2 | `mod/booking/settings.php` | [doc](methods/S22/settings.md) |
| B | P2 | `classes/utils/wb_payment.php` | [doc](methods/S22/classes__utils__wb_payment.md) |
| C | P3 | `classes/GoogleUrlApi.php` | [doc](methods/S22/classes__GoogleUrlApi.md) |
| C | P3 | `classes/local/modechecker.php` | [doc](methods/S22/classes__local__modechecker.md) |
| C | P3 | `classes/utils/db.php` | [doc](methods/S22/classes__utils__db.md) |
| C | P3 | `db/upgrade.php` | [doc](methods/S22/db__upgrade.md) |
| B | P3 | `classes/completion/custom_completion.php` | [doc](methods/S22/classes__completion__custom_completion.md) |
| B | P3 | `classes/local/sql/operators/contains.php` | [doc](methods/S22/classes__local__sql__operators__contains.md) |
| B | P3 | `classes/local/sql/operators/equals.php` | [doc](methods/S22/classes__local__sql__operators__equals.md) |
| B | P3 | `classes/local/sql/operators/not_equals.php` | [doc](methods/S22/classes__local__sql__operators__not_equals.md) |
| B | P3 | `classes/plugininfo/bookingextension.php` | [doc](methods/S22/classes__plugininfo__bookingextension.md) |
| B | P3 | `db/upgradelib.php` | [doc](methods/S22/db__upgradelib.md) |
| B | P3 | `locallib.php` | [doc](methods/S22/locallib.md) |
| A | P3 | `classes/local/sql/operators/base_operator.php` | [doc](methods/S22/classes__local__sql__operators__base_operator.md) |
| A | P3 | `cli/audit_booking_invariants.php` | [doc](methods/S22/cli__audit_booking_invariants.md) |
| A | P3 | `mod/booking/db/access.php` | [doc](methods/S22/db__access.md) |
| — | — | `classes/local/testing/booking_advanced_testcase.php` | [doc](methods/S22/classes__local__testing__booking_advanced_testcase.md) |
| — | — | `classes/plugininfo/bookingextension_interface.php` | [doc](methods/S22/classes__plugininfo__bookingextension_interface.md) |
| — | — | `version.php` | [doc](methods/S22/version.md) |

### S23 (7 Docs · P1:2 · P2:4 · P3:1)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| D | P1 | `amd/src/condition/slotBooking.js` | [doc](methods/S23/amd__src__condition__slotBooking.md) |
| C | P1 | `amd/src/bookit.js` | [doc](methods/S23/amd__src__bookit.md) |
| C | P2 | `amd/src/csvimport.js` | [doc](methods/S23/amd__src__csvimport.md) |
| C | P2 | `amd/src/slotCalendarPicker.js` | [doc](methods/S23/amd__src__slotCalendarPicker.md) |
| B | P2 | `amd/src/bookingpage/prepageFooter.js` | [doc](methods/S23/amd__src__bookingpage__prepageFooter.md) |
| B | P2 | `amd/src/condition/slotUpdate.js` | [doc](methods/S23/amd__src__condition__slotUpdate.md) |
| B | P3 | `amd/src/jquery.barrating.js` | [doc](methods/S23/amd__src__jquery.barrating.md) |

### S24 (5 Docs · P2:2 · P3:3)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `backup/moodle2/restore_booking_stepslib.php` | [doc](methods/S24/backup__moodle2__restore_booking_stepslib.md) |
| B | P2 | `backup/moodle2/backup_booking_stepslib.php` | [doc](methods/S24/backup__moodle2__backup_booking_stepslib.md) |
| C | P3 | `backup/moodle2/backup_booking_settingslib.php` | [doc](methods/S24/backup__moodle2__backup_booking_settingslib.md) |
| A | P3 | `backup/moodle2/backup_booking_activity_task.class.php` | [doc](methods/S24/backup__moodle2__backup_booking_activity_task.class.md) |
| A | P3 | `backup/moodle2/restore_booking_activity_task.class.php` | [doc](methods/S24/backup__moodle2__restore_booking_activity_task.class.md) |

### S25 (4 Docs · P2:2 · P3:2)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/local/mobile/customformstore.php` | [doc](methods/S25/classes__local__mobile__customformstore.md) |
| C | P2 | `classes/local/mobile/mobileformbuilder.php` | [doc](methods/S25/classes__local__mobile__mobileformbuilder.md) |
| B | P3 | `classes/local/mobile/slotbookingstore.php` | [doc](methods/S25/classes__local__mobile__slotbookingstore.md) |
| A | P3 | `classes/entities/service_provider.php` | [doc](methods/S25/classes__entities__service_provider.md) |

### S26 (1 Docs · P2:1)

| Score | Prio | Klasse / Datei | Doc |
|:--:|:--:|---|---|
| C | P2 | `classes/privacy/provider.php` | [doc](methods/S26/classes__privacy__provider.md) |

