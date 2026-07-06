# mod_booking — CLASS_INDEX

> Jede Klasse → Verantwortung, Methodenzahl, Vorab-Qualität (A–E) & Refactor-Prio (P0–P3). Tiefe Methoden-Doku folgt in Phase 3. Qualitäts-Detail: [QUALITY_INDEX](../blueprints/QUALITY_INDEX.md).

## S01 — core_domain

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\local\calendar\calendar_helper` | `classes/local/calendar/calendar_helper.php` | Service | 5 | B | - | Statische Helfer zum Setzen/Loeschen/Updaten der Kalender-Events einer Option. |
| `mod_booking\booking_answers\scopes\systemanswers` | `classes/booking_answers/scopes/systemanswers.php` | Condition | 2 | B | - | Nicht-aggregierte Antwortsicht systemweit. |
| `mod_booking\booking_answers\scopes\courseanswers` | `classes/booking_answers/scopes/courseanswers.php` | Condition | 2 | B | - | Nicht-aggregierte Antwortsicht ueber einen Kurs. |
| `mod_booking\booking_answers\scopes\instanceanswers` | `classes/booking_answers/scopes/instanceanswers.php` | Condition | 2 | B | - | Nicht-aggregierte Antwortsicht ueber eine Instanz. |
| `mod_booking\booking_answers\scopes\instance` | `classes/booking_answers/scopes/instance.php` | Condition | 2 | B | - | Aggregierte Scope-Sicht ueber eine Buchungsinstanz. |
| `mod_booking\booking_answers\scopes\course` | `classes/booking_answers/scopes/course.php` | Condition | 2 | B | - | Aggregierte Scope-Sicht ueber einen Kurs. |
| `mod_booking\booking_answers\scopes\system` | `classes/booking_answers/scopes/system.php` | Condition | 2 | B | - | Aggregierte Scope-Sicht systemweit. |
| `mod_booking\local\optiondates\optiondate_answer` | `classes/local/optiondates/optiondate_answer.php` | DTO | 8 | B | - | DTO/Repository fuer Praesenz/Notes pro Sitzung (booking_optiondates-Antwort). |
| `mod_booking\booking_answers\scopes\supervisorteam` | `classes/booking_answers/scopes/supervisorteam.php` | Condition | 2 | B | - | Scope fuer Supervisor-Team-Sicht (erbt optionstoconfirm). |
| `mod_booking\semester` | `classes/semester.php` | DTO | 4 | A | - | Schlankes gecachtes DTO/Lookup fuer Semester. |
| `mod_booking\booking_answers\scopes\supervisorteamreduced` | `classes/booking_answers/scopes/supervisorteamreduced.php` | Condition | 2 | B | - | Reduzierte Supervisor-Team-Sicht. |
| `mod_booking\mybookings_table` | `classes/mybookings_table.php` | Renderer | 4 | A | - | Schlanker table_sql-Renderer fuer Meine-Buchungen. |
| `mod_booking\booking_answers\scope_base_answers` | `classes/booking_answers/scope_base_answers.php` | Condition | 3 | B | - | Scope-Basis fuer nicht-aggregierte Antwortsicht (select/end-Part, Spalten). |
| `mod_booking\booking_answers\scopes\optionstoconfirmreduced` | `classes/booking_answers/scopes/optionstoconfirmreduced.php` | Condition | 2 | B | - | Reduzierte Variante des Confirm-Scopes. |
| `mod_booking\booking_answers\scope_base_options` | `classes/booking_answers/scope_base_options.php` | Condition | 3 | B | - | Scope-Basis fuer aggregierte Optionssicht (select/end-Part, Spalten). |
| `mod_booking\places` | `classes/places.php` | DTO | 1 | A | - | Reines Wert-Objekt fuer Platzkapazitaet (maxanswers, available, maxoverbooking, overbookingavailable). |
| `mod_booking\booking_context_helper` | `classes/booking_context_helper.php` | Util | 1 | A | - | Setzt $PAGE-Context defensiv (Shortcodes/Webservices). |
| `mod_booking\booking_option` | `classes/booking_option.php` | Domaenenobjekt | 90 | E | P0 | Zentrales God-Objekt der Option: Buchen, Warteliste-Sync, Enrolment, Gruppen, Praesenz, Messaging, Kalender, Favoriten, Loeschen, Cache. |
| `mod_booking\booking` | `classes/booking.php` | Domaenenobjekt | 50 | D | P1 | Repraesentiert eine Buchungsinstanz (Course Module); haelt State und liefert Optionslisten/SQL sowie viele statische Utility-Helfer. |
| `mod_booking\booking_answers\booking_answers` | `classes/booking_answers/booking_answers.php` | Service | 45 | D | P1 | Aggregiert/cached Antworten einer Option; Userlisten nach Status, Belegung, Overlap-/Limit-Pruefung, statische Zaehl-/SQL-Helfer. |
| `mod_booking\singleton_service` | `classes/singleton_service.php` | Service | 45 | C | P1 | Zentraler Service-Locator/Identity-Map; liefert und cached alle Domaenenobjekte pro Request, Bruecke zu MUC-Caches. |
| `mod_booking\booking_option_settings` | `classes/booking_option_settings.php` | DTO | 33 | C | P2 | Gecachtes lesendes DTO mit voll aufgeloestem Option-State (Sessions, Teacher, Customfields, Entity, Bild, Subbookings). |
| `mod_booking\all_userbookings` | `classes/all_userbookings.php` | Renderer | 33 | C | P2 | table_sql-Renderer fuer Teilnehmerliste einer Option (Status, Praesenz, Rating, Notes, Slots, Zertifikate). |
| `mod_booking\dates` | `classes/dates.php` | Form | 14 | C | P2 | Statische Form-/Parsing-Schicht fuer Optionstermine (Collapsibles, Submit-Parsing, Speichern der optiondates). |
| `mod_booking\teachers_handler` | `classes/teachers_handler.php` | Service | 23 | C | P2 | (Orphan-Nachtrag) |
| `mod_booking\booking_utils` | `classes/booking_utils.php` | Util | 16 | C | P2 | Gemischte Utility-Sammlung (Dauer, Template-Params, Change-Events, Userevent-Sichtbarkeit, Kohort/Gruppen-Buchung). |
| `mod_booking\booking_answers\scopes\optionstoconfirm` | `classes/booking_answers/scopes/optionstoconfirm.php` | Condition | 8 | C | P2 | Scope fuer Bestaetigungs-Workflow; bindet Subplugin-Confirmation-Klassen dynamisch ein. |
| `mod_booking\calendar` | `classes/calendar.php` | Service | 4 | C | P2 | Erzeugt/aktualisiert/loescht Moodle {event}-Eintraege fuer Optionen, Termine und Teacher. |
| `mod_booking\booking_answers\scopes\option` | `classes/booking_answers/scopes/option.php` | Condition | 6 | C | P2 | Option-Scope mit echtem return_users_table und optionspezifischer Capability-Pruefung. |
| `mod_booking\booking_settings` | `classes/booking_settings.php` | DTO | 4 | B | P3 | Schlankes gecachtes DTO fuer eine Buchungsinstanz (Name, Mail/Template-Settings, Booking-Manager). |
| `mod_booking\ical` | `classes/ical.php` | Service | 13 | C | P3 | Erzeugt iCal-Attachments (VEVENTs) fuer Buchungsbestaetigungen. |
| `mod_booking\booking_answers\scopes\optiondate` | `classes/booking_answers/scopes/optiondate.php` | Condition | 4 | C | P3 | Scope fuer Praesenzlisten pro Sitzung/Termin. |
| `mod_booking\booking_answers\scopes\alloptions` | `classes/booking_answers/scopes/alloptions.php` | Condition | 3 | C | P3 | Scope ueber alle Optionen (erbt option). |
| `mod_booking\coursecategories` | `classes/coursecategories.php` | Service | 3 | C | P3 | Kurskategorie-Reports mit aggregierendem SQL; Konfig-Toggle fuer Multibooking-Instanzen. |
| `mod_booking\booking_answers\scope_base` | `classes/booking_answers/scope_base.php` | Condition | 8 | B | P3 | Basisklasse der Reporting-Scope-Strategy: SQL/Spalten/Tabellen/Capability-Defaults fuer Antwortsichten. |
| `mod_booking\booking_tags` | `classes/booking_tags.php` | Domaenenobjekt | 7 | B | P3 | (Orphan-Nachtrag) |
| `mod_booking\permissions` | `classes/permissions.php` | Util | 1 | B | P3 | Capability-Pruefung ueber alle Kontexte einer Ebene. |

## S02 — option_fields

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\option\fields\description` | `classes/option/fields/description.php` | Field | 4 | B | - | Beschreibung (Editor, GENERAL). |
| `mod_booking\option\fields\aftersubmitaction` | `classes/option/fields/aftersubmitaction.php` | Field | 3 | B | - | Aktion nach Formular-Submit. |
| `mod_booking\option\fields\duplication` | `classes/option/fields/duplication.php` | Field | 3 | B | - | Necessary-Feld: Duplizierungs-Steuerung. |
| `mod_booking\option\fields\id` | `classes/option/fields/id.php` | Field | 3 | B | - | Necessary-Feld: Options-ID-Handling. |
| `mod_booking\option\fields\aftercompletedtext` | `classes/option/fields/aftercompletedtext.php` | Field | 3 | B | - | Editor-Text nach Abschluss. |
| `mod_booking\option\fields\annotation` | `classes/option/fields/annotation.php` | Field | 3 | B | - | Interne Anmerkung (GENERAL). |
| `mod_booking\option\fields\beforebookedtext` | `classes/option/fields/beforebookedtext.php` | Field | 3 | B | - | Editor-Text vor Buchung. |
| `mod_booking\option\fields\beforecompletedtext` | `classes/option/fields/beforecompletedtext.php` | Field | 3 | B | - | Editor-Text vor Abschluss. |
| `mod_booking\option\fields\notificationtext` | `classes/option/fields/notificationtext.php` | Field | 3 | B | - | Benachrichtigungstext (Editor). |
| `mod_booking\option\fields\credits` | `classes/option/fields/credits.php` | Field | 3 | B | - | Credits (PRICE); add_json_to_booking_answer-Hook. |
| `mod_booking\option\fields\timecreated` | `classes/option/fields/timecreated.php` | Field | 2 | B | - | Erstellungs-Zeitstempel. |
| `mod_booking\option\fields\institution` | `classes/option/fields/institution.php` | Field | 2 | B | - | Institution/Anbieter. |
| `mod_booking\option\fields\identifier` | `classes/option/fields/identifier.php` | Field | 3 | B | - | Eindeutiger Identifier inkl. Validierung. |
| `mod_booking\option\fields\actions` | `classes/option/fields/actions.php` | Field | 3 | B | - | Postsave-Feld fuer Booking-Actions einer Option. |
| `mod_booking\option\fields\maxanswers` | `classes/option/fields/maxanswers.php` | Field | 3 | B | - | Maximale Teilnehmerzahl. |
| `mod_booking\option\fields\location` | `classes/option/fields/location.php` | Field | 2 | B | - | Veranstaltungsort (Freitext). |
| `mod_booking\option\fields\disablecancel` | `classes/option/fields/disablecancel.php` | Field | 3 | B | - | Checkbox: Stornieren deaktivieren. |
| `mod_booking\option\fields\easy_text` | `classes/option/fields/easy_text.php` | Field | 4 | B | - | Easy-Mode Titel/Text. |
| `mod_booking\option\fields\addtogroup` | `classes/option/fields/addtogroup.php` | Field | 3 | B | - | Postsave: Buchende in Moodle-Gruppe einschreiben. |
| `mod_booking\option\fields\timemodified` | `classes/option/fields/timemodified.php` | Field | 2 | B | - | Aenderungs-Zeitstempel. |
| `mod_booking\option\fields\json` | `classes/option/fields/json.php` | Field | 3 | B | - | Verwaltung des JSON-Felds der Option. |
| `mod_booking\option\fields\courseendtime` | `classes/option/fields/courseendtime.php` | Field | 4 | B | - | Kursende-Zeitpunkt. |
| `mod_booking\option\fields\address` | `classes/option/fields/address.php` | Field | 2 | B | - | Adressfeld (GENERAL). |
| `mod_booking\option\fields\maxoverbooking` | `classes/option/fields/maxoverbooking.php` | Field | 2 | B | - | Maximale Warteliste/Ueberbuchung. |
| `mod_booking\option\fields\titleprefix` | `classes/option/fields/titleprefix.php` | Field | 2 | B | - | Titel-Praefix. |
| `mod_booking\option\fields\removeafterminutes` | `classes/option/fields/removeafterminutes.php` | Field | 2 | B | - | Automatisches Entfernen nach X Minuten. |
| `mod_booking\option\fields\returnurl` | `classes/option/fields/returnurl.php` | Field | 2 | B | - | Return-URL nach Buchung. |
| `mod_booking\option\fields\eventslist` | `classes/option/fields/eventslist.php` | Field | 3 | B | - | Anzeige der Termin-Events (definition_after_data). |
| `mod_booking\option\fields\howmanyusers` | `classes/option/fields/howmanyusers.php` | Field | 2 | B | - | Anzahl buchbarer Nutzer pro Buchung. |
| `mod_booking\option\fields\minanswers` | `classes/option/fields/minanswers.php` | Field | 2 | B | - | Mindest-Teilnehmerzahl. |
| `mod_booking\option\fields\disablebookingusers` | `classes/option/fields/disablebookingusers.php` | Field | 2 | B | - | Checkbox: Buchen deaktivieren. |
| `mod_booking\option\fields\subbookings` | `classes/option/fields/subbookings.php` | Field | 2 | B | - | Subbookings-Verknuepfung (Form-Anzeige). |
| `mod_booking\option\fields\priceformulaadd` | `classes/option/fields/priceformulaadd.php` | Field | 2 | B | - | Preisformel-Additiv-Wert. |
| `mod_booking\option\fields\priceformulamultiply` | `classes/option/fields/priceformulamultiply.php` | Field | 2 | B | - | Preisformel-Multiplikator. |
| `mod_booking\option\fields\priceformulaoff` | `classes/option/fields/priceformulaoff.php` | Field | 2 | B | - | Preisformel deaktivieren. |
| `mod_booking\option\fields\usercreated` | `classes/option/fields/usercreated.php` | Field | 1 | B | - | Ersteller-User-ID. |
| `mod_booking\option\type_resolver` | `classes/option/type_resolver.php` | Service | 4 | A | - | Optiontyp aufloesen/normalisieren und abhaengige Flags (selflearningcourse/slot_enabled) synchronisieren. |
| `mod_booking\option\fields\usermodified` | `classes/option/fields/usermodified.php` | Field | 1 | B | - | Letzter-Bearbeiter-User-ID. |
| `mod_booking\option\fields` | `classes/option/fields.php` | Interface | 2 | A | - | Minimaler Feld-Vertrag: prepare_save_field + instance_form_definition fuer alle Optionsfelder. |
| `mod_booking\option\time_handler` | `classes/option/time_handler.php` | Util | 2 | A | - | Zeit-Helfer: Intervall-Step und Runden auf volle Stunde. |
| `mod_booking\option\dates_handler` | `classes/option/dates_handler.php` | Service | 19 | D | P1 | Datumsserien-Parsing, Lokalisierung, Formatierung, Termin-Persistenz und Slot-Erzeugung in einer Klasse. |
| `mod_booking\option\fields\recurringoptions` | `classes/option/fields/recurringoptions.php` | Field | 12 | D | P1 | Mutter-/Kind-Optionen: erzeugt und propagiert Aenderungen auf Kind-Optionen. |
| `mod_booking\option\fields\slotbooking` | `classes/option/fields/slotbooking.php` | Field | 8 | D | P1 | Slotbooking-Konfiguration (JSON in booking_options.json); Semester-Defaults, Teacher-Pool. |
| `mod_booking\customfield\booking_handler` | `classes/customfield/booking_handler.php` | Service | 18 | C | P2 | core_customfield-Handler fuer Options-Customfields (Form, Validierung, Save, Sichtbarkeit, Cache). |
| `mod_booking\option\fields_info` | `classes/option/fields_info.php` | Service | 12 | C | P2 | Orchestrator/Dispatcher: laeuft ueber konfigurierte Feld-Klassen und ruft deren Lifecycle-Methoden. |
| `mod_booking\option\fields\competencies` | `classes/option/fields/competencies.php` | Field | 11 | D | P2 | core_competency-Anbindung: Zuweisung, Framework-Lookup, Filter/Similar-Options. |
| `mod_booking\option\fields\sharedplaces` | `classes/option/fields/sharedplaces.php` | Field | 8 | D | P2 | Platz-Teilung zwischen Optionen; eigene check_for_changes + SQL/Sync-Logik. |
| `mod_booking\option\field_base` | `classes/option/field_base.php` | Field | 13 | B | P2 | Abstrakte Basis aller Feld-Klassen; Default-Lifecycle + Metadaten + Aenderungs-Tracking. |
| `mod_booking\option\fields\courseid` | `classes/option/fields/courseid.php` | Field | 6 | D | P2 | Verknuepfter Moodle-Kurs inkl. Kurs-Duplizierung. |
| `mod_booking\settings\optionformconfig\optionformconfig_info` | `classes/settings/optionformconfig/optionformconfig_info.php` | Service | 9 | C | P2 | Liefert je Kontext+Capability die aktive Feldliste (booking_form_config), inkl. Discovery + Merge. |
| `mod_booking\option\fields\certificate` | `classes/option/fields/certificate.php` | Field | 5 | C | P2 | Zertifikat-Konfiguration (tool_certificate) inkl. Datumsschluessel-Mapping. |
| `mod_booking\option\fields\entities` | `classes/option/fields/entities.php` | Field | 7 | D | P2 | local_entities-Anbindung (Ort/Equipment); order_all_dates, save_data, get_changes_description. |
| `mod_booking\option\fields\optiondates` | `classes/option/fields/optiondates.php` | Field | 7 | D | P2 | Postsave-Feld fuer Terminserien; Bruecke zu dates_handler/optiondate; get_changes_description. |
| `mod_booking\option\fields\shoppingcart` | `classes/option/fields/shoppingcart.php` | Field | 6 | C | P2 | Postsave: shopping_cart-Integration; eigene check_for_changes. |
| `mod_booking\option\fields\availability` | `classes/option/fields/availability.php` | Field | 4 | C | P2 | Postsave: Verfuegbarkeits-Bedingungen (JSON availability). |
| `mod_booking\option\fields\optiontype` | `classes/option/fields/optiontype.php` | Field | 4 | C | P2 | Optiontyp-Auswahl (Default/Selflearning/Slot); Bruecke zu type_resolver. |
| `mod_booking\option\fields\customfields` | `classes/option/fields/customfields.php` | Field | 6 | C | P2 | Postsave-Bruecke zu booking_handler (Options-Customfields); liefert get_subfields. |
| `mod_booking\option\fields\price` | `classes/option/fields/price.php` | Field | 5 | C | P2 | Postsave: Preiskategorien der Option; Bruecke zu mod_booking\price. |
| `mod_booking\option\fields\teachers` | `classes/option/fields/teachers.php` | Field | 5 | C | P2 | Postsave: Lehrer-Zuordnung via teachers_handler; changes_collected_action. |
| `mod_booking\option\optiondate` | `classes/option/optiondate.php` | Domaenenobjekt | 5 | C | P3 | Domaenenobjekt eines einzelnen Termins inkl. CRUD, Kalender-Event, Entities und Termin-Customfields. |
| `mod_booking\customfield\optiondate_cfields` | `classes/customfield/optiondate_cfields.php` | Service | 7 | C | P3 | Eigene Mini-Infrastruktur fuer Termin-Customfields/Kommentare (Tabelle booking_customfields). |
| `mod_booking\option\fields\template` | `classes/option/fields/template.php` | Field | 4 | C | P3 | Option aus Template instanziieren; definition_after_data. |
| `mod_booking\option\fields\moveoption` | `classes/option/fields/moveoption.php` | Field | 4 | C | P3 | Option in andere Instanz verschieben; save_data. |
| `mod_booking\option\fields\easy_availability_previouslybooked` | `classes/option/fields/easy_availability_previouslybooked.php` | Field | 4 | C | P3 | Easy-Mode Verfuegbarkeit: vorher gebucht. |
| `mod_booking\option\fields\duration` | `classes/option/fields/duration.php` | Field | 4 | C | P3 | Dauer (COURSES) inkl. Validierung/Anzeige. |
| `mod_booking\option\fields\easy_availability_selectusers` | `classes/option/fields/easy_availability_selectusers.php` | Field | 4 | C | P3 | Easy-Mode Verfuegbarkeit: ausgewaehlte Nutzer. |
| `mod_booking\option\fields\bookingoptionimage` | `classes/option/fields/bookingoptionimage.php` | Field | 4 | C | P3 | Postsave: Header-Bild der Option (Draft-Area). |
| `mod_booking\option\fields\applybookingrules` | `classes/option/fields/applybookingrules.php` | Field | 5 | C | P3 | Booking-Rules auf Option anwenden; apply_rule-Helfer. |
| `mod_booking\option\fields\responsiblecontact` | `classes/option/fields/responsiblecontact.php` | Field | 4 | C | P3 | Verantwortliche Kontaktperson(en); save_data, User-ID-Aufloesung. |
| `mod_booking\option\fields\attachment` | `classes/option/fields/attachment.php` | Field | 4 | C | P3 | Postsave: Datei-Anhaenge (filemanager/Draft-Area). |
| `mod_booking\option\fields\bookingclosingtime` | `classes/option/fields/bookingclosingtime.php` | Field | 4 | C | P3 | Buchungsschluss-Zeitpunkt inkl. Validierung. |
| `mod_booking\option\fields\bookingopeningtime` | `classes/option/fields/bookingopeningtime.php` | Field | 4 | C | P3 | Buchungsoeffnungs-Zeitpunkt inkl. Validierung. |
| `mod_booking\option\fields\canceluntil` | `classes/option/fields/canceluntil.php` | Field | 3 | C | P3 | Stornierbar-bis-Zeitpunkt (ADVANCED). |
| `mod_booking\option\fields\multiplebookings` | `classes/option/fields/multiplebookings.php` | Field | 4 | C | P3 | Mehrfachbuchung; book_again_due-Logik. |
| `mod_booking\option\fields\pollurl` | `classes/option/fields/pollurl.php` | Field | 4 | C | P3 | Umfrage-URLs inkl. Validierung. |
| `mod_booking\option\fields\enrolmentstatus` | `classes/option/fields/enrolmentstatus.php` | Field | 4 | C | P3 | Einschreibungs-Status; eigene check_for_changes. |
| `mod_booking\option\fields\bookusers` | `classes/option/fields/bookusers.php` | Field | 4 | C | P3 | Postsave: Nutzer direkt in Option buchen. |
| `mod_booking\option\fields\groupid` | `classes/option/fields/groupid.php` | Field | 3 | C | P3 | Gruppen-ID-Zuordnung der Option. |
| `mod_booking\local\override_user_field` | `classes/local/override_user_field.php` | Service | 5 | C | P3 | Mockt User-Profilfelder als cmid-scoped User-Preference fuer Verfuegbarkeitsbedingungen (circumvent). |
| `mod_booking\option\fields\addastemplate` | `classes/option/fields/addastemplate.php` | Field | 3 | B | P3 | Option als Template speichern. |
| `mod_booking\option\fields\invisible` | `classes/option/fields/invisible.php` | Field | 3 | C | P3 | Sichtbarkeits-Flag der Option. |
| `mod_booking\option\fields\coursestarttime` | `classes/option/fields/coursestarttime.php` | Field | 4 | C | P3 | Kursstart-Zeitpunkt (DATES) inkl. Validierung. |
| `mod_booking\option\fields\waitforconfirmation` | `classes/option/fields/waitforconfirmation.php` | Field | 3 | C | P3 | Buchung erst nach Bestaetigung (Warteliste). |
| `mod_booking\option\fields\addtocalendar` | `classes/option/fields/addtocalendar.php` | Field | 3 | B | P3 | Postsave: Eintrag in Moodle-Kalender. |
| `mod_booking\option\fields\text` | `classes/option/fields/text.php` | Field | 4 | B | P3 | Titel/Text der Option inkl. Validierung. |
| `mod_booking\option\fields\easy_bookingopeningtime` | `classes/option/fields/easy_bookingopeningtime.php` | Field | 4 | C | P3 | Easy-Mode Buchungsoeffnung. |
| `mod_booking\option\fields\easy_bookingclosingtime` | `classes/option/fields/easy_bookingclosingtime.php` | Field | 4 | C | P3 | Easy-Mode Buchungsschluss. |
| `mod_booking\option\fields\elective` | `classes/option/fields/elective.php` | Field | 4 | C | P3 | Wahlpflicht-Logik (elective); save_data. |
| `mod_booking\option\fields\formconfig` | `classes/option/fields/formconfig.php` | Field | 3 | B | P3 | Bindeglied zur optionformconfig-Steuerung im Formular. |
| `mod_booking\option\fields\prepare_import` | `classes/option/fields/prepare_import.php` | Field | 3 | C | P3 | Vorverarbeitung von Importdaten vor anderen Feldern. |

## S03 — availability

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\bo_availability\conditions\onwaitinglist` | `classes/bo_availability/conditions/onwaitinglist.php` | Condition | 12 | B | - | Status (id 110): User steht auf der Warteliste. |
| `mod_booking\bo_availability\conditions\isloggedinprice` | `classes/bo_availability/conditions/isloggedinprice.php` | Condition | 12 | B | - | Gate (id 75): Login erforderlich bei kostenpflichtigen Optionen. |
| `mod_booking\bo_availability\conditions\bookondetail` | `classes/bo_availability/conditions/bookondetail.php` | Condition | 11 | B | - | Gate (id 104): Buchung nur auf der Detailseite erlaubt. |
| `mod_booking\bo_availability\conditions\bookingpolicy` | `classes/bo_availability/conditions/bookingpolicy.php` | Condition | 11 | B | - | Gate (id 50): Buchungsrichtlinie muss akzeptiert werden (Prepage). |
| `mod_booking\bo_availability\conditions\isloggedin` | `classes/bo_availability/conditions/isloggedin.php` | Condition | 11 | B | - | Gate (id 74): Login erforderlich zum Buchen. |
| `mod_booking\bo_availability\conditions\alreadyreserved` | `classes/bo_availability/conditions/alreadyreserved.php` | Condition | 11 | B | - | Status (id 102): bereits im Warenkorb reserviert. |
| `mod_booking\bo_availability\conditions\otheroptionsavailable` | `classes/bo_availability/conditions/otheroptionsavailable.php` | Condition | 11 | B | - | Hinweis (id 31): andere Optionen verfuegbar. |
| `mod_booking\bo_availability\conditions\instanceavailability` | `classes/bo_availability/conditions/instanceavailability.php` | Condition | 11 | B | - | Gate (id 5): Instanz-weite Verfuegbarkeitsregeln. |
| `mod_booking\bo_availability\conditions\max_number_of_bookings` | `classes/bo_availability/conditions/max_number_of_bookings.php` | Condition | 11 | B | - | Limit (id 80): max. Buchungen pro User in der Instanz. |
| `mod_booking\bo_availability\conditions\confirmbookit` | `classes/bo_availability/conditions/confirmbookit.php` | Condition | 11 | B | - | Aktion (id -80): Bestaetigungsschritt fuer einfaches Bookit. |
| `mod_booking\bo_availability\conditions\confirmbookwithcredits` | `classes/bo_availability/conditions/confirmbookwithcredits.php` | Condition | 11 | B | - | Aktion (id -40): Bestaetigungsschritt fuer Credit-Buchung. |
| `mod_booking\bo_availability\conditions\confirmbookwithsubscription` | `classes/bo_availability/conditions/confirmbookwithsubscription.php` | Condition | 11 | B | - | Aktion (id -20): Bestaetigungsschritt fuer Abo-Buchung. |
| `mod_booking\bo_availability\conditions\confirmaskforconfirmation` | `classes/bo_availability/conditions/confirmaskforconfirmation.php` | Condition | 11 | B | - | Gate (id 1): Bestaetigungsschritt der Warteliste-/Confirmation-Buchung. |
| `mod_booking\bo_availability\conditions\campaign_blockbooking` | `classes/bo_availability/conditions/campaign_blockbooking.php` | Condition | 11 | B | - | Sperre (id 71): Kampagne blockiert Buchung in einem Zeitraum. |
| `mod_booking\bo_availability\conditions\iscancelled` | `classes/bo_availability/conditions/iscancelled.php` | Condition | 11 | B | - | Sperre (id 130): Option ist storniert. |
| `mod_booking\bo_availability\conditions\optionhasstarted` | `classes/bo_availability/conditions/optionhasstarted.php` | Condition | 11 | B | - | Sperre (id 70): Option hat bereits begonnen. |
| `mod_booking\bo_availability\conditions\isbookable` | `classes/bo_availability/conditions/isbookable.php` | Condition | 11 | B | - | Sperre (id 120): Option-Flag bookings nicht erlaubt. |
| `mod_booking\bo_availability\conditions\confirmation` | `classes/bo_availability/conditions/confirmation.php` | Condition | 11 | B | - | Aktion (id -100): finale Abschluss-/Bestaetigungsseite nach Buchung; ermittelt booked/reserved/waitinglist-Status. |
| `mod_booking\bo_availability\conditions\isbookableinstance` | `classes/bo_availability/conditions/isbookableinstance.php` | Condition | 11 | B | - | Sperre (id 125): Instanz erlaubt keine Buchungen. |
| `mod_booking\bo_availability\conditions\noshoppingcart` | `classes/bo_availability/conditions/noshoppingcart.php` | Condition | 11 | B | - | Sperre (id -60): Preis gesetzt aber kein shopping_cart installiert -> nicht buchbar. |
| `mod_booking\bo_availability\conditions\capbookingchoose` | `classes/bo_availability/conditions/capbookingchoose.php` | Condition | 11 | B | - | Gate (id 4): Capability-basierte Buchungserlaubnis (mod/booking:choose). |
| `mod_booking\bo_availability\subconditions\isbookable` | `classes/bo_availability/subconditions/isbookable.php` | Condition | 8 | B | - | Subbooking-Sperre: Subbooking nicht buchbar. |
| `mod_booking\bo_availability\subconditions\alreadybooked` | `classes/bo_availability/subconditions/alreadybooked.php` | Condition | 8 | B | - | Subbooking-Status: Subbooking bereits gebucht. |
| `mod_booking\bo_availability\subconditions\priceisset` | `classes/bo_availability/subconditions/priceisset.php` | Condition | 8 | B | - | Subbooking-Aktion: Preis fuer Subbooking gesetzt -> Warenkorb-Flow. |
| `mod_booking\bo_availability\subconditions\bookitbutton` | `classes/bo_availability/subconditions/bookitbutton.php` | Condition | 7 | B | - | Subbooking-Aktion: zeigt Bookit-Button im Subbooking-Flow (teilt id mit Hauptcondition). |
| `mod_booking\bo_availability\bo_condition` | `classes/bo_availability/bo_condition.php` | DTO | 11 | A | - | Interface: voller Condition-Vertrag (is_available, hard_block, get_description, render_*, return_sql, get_id/name, is_skippable). |
| `mod_booking\bo_availability\bo_subcondition` | `classes/bo_availability/bo_subcondition.php` | DTO | 5 | A | - | Interface: reduzierter Subbooking-Condition-Vertrag mit subbookingid-Parameter, ohne hard_block/return_sql/render_page. |
| `mod_booking\bo_availability\freezable_condition` | `classes/bo_availability/freezable_condition.php` | DTO | 1 | A | - | Opt-in-Interface: Condition deklariert ihre mform-Element-Namen fuer Freeze/Hide selbst (get_condition_form_elements). |
| `mod_booking\bo_availability\bo_info` | `classes/bo_availability/bo_info.php` | Service | 30 | D | P1 | Zentraler Orchestrator der Gating-Engine: laedt Conditions, fuehrt Evaluations-Loop + Override-Logik aus, sequenziert Prepage-Modals, rendert Buttons, aggregiert SQL und speichert JSON-Conditions. God-Class. |
| `mod_booking\bo_availability\conditions\booking_time` | `classes/bo_availability/conditions/booking_time.php` | Condition | 19 | E | P1 | Gate (id 60): Buchungs-Oeffnungs-/Schliesszeitfenster (absolut/relativ); mform, Persistenz-Normalisierung, return_sql. |
| `mod_booking\bo_availability\conditions\userprofilefield_2_custom` | `classes/bo_availability/conditions/userprofilefield_2_custom.php` | Condition | 17 | E | P1 | JSON-Condition (id 12): Vergleich gegen Custom-Profilfelder; ~250-LOC dialektabhaengiges return_sql, compare_operation/compare_fields. |
| `mod_booking\bo_availability\conditions\customform` | `classes/bo_availability/conditions/customform.php` | Condition | 19 | E | P1 | JSON-Condition (id 16): rendert beliebiges Custom-Formular in die Buchung, schreibt Antworten in Booking-Answer. |
| `mod_booking\bo_availability\conditions\enrolledincourse` | `classes/bo_availability/conditions/enrolledincourse.php` | Condition | 16 | D | P2 | JSON-Condition (id 15): erfordert Kurseinschreibung; grosser mform + return_sql mit enrol-Kopplung. |
| `mod_booking\bo_availability\conditions\enrolledincohorts` | `classes/bo_availability/conditions/enrolledincohorts.php` | Condition | 16 | D | P2 | JSON-Condition (id 17): erfordert Cohort-Mitgliedschaft; grosser mform + return_sql. |
| `mod_booking\bo_availability\conditions\userprofilefield_1_default` | `classes/bo_availability/conditions/userprofilefield_1_default.php` | Condition | 16 | D | P2 | JSON-Condition (id 11): Vergleich gegen Standard-Userprofilfelder; grosser return_sql. |
| `mod_booking\bo_availability\conditions\hascompetency` | `classes/bo_availability/conditions/hascompetency.php` | Condition | 16 | D | P2 | JSON-Condition (id 10): erfordert Moodle-Kompetenz; grosser mform-Aufbau + return_sql + Persistenz. |
| `mod_booking\bo_availability\conditions\nooverlappingproxy` | `classes/bo_availability/conditions/nooverlappingproxy.php` | Condition | 17 | D | P2 | Hardcoded-Proxy (id 29) zu nooverlapping: spiegelt dessen Terminueberschneidungs-Logik. |
| `mod_booking\bo_availability\conditions\previouslybooked` | `classes/bo_availability/conditions/previouslybooked.php` | Condition | 16 | D | P2 | JSON-Condition (id 13): User muss zuvor eine bestimmte Option gebucht haben. |
| `mod_booking\bo_availability\conditions\nooverlapping` | `classes/bo_availability/conditions/nooverlapping.php` | Condition | 18 | D | P2 | JSON-Condition (id 30): verhindert Buchung bei zeitlicher Ueberschneidung mit anderen Optionen. |
| `mod_booking\bo_availability\conditions\selectusers` | `classes/bo_availability/conditions/selectusers.php` | Condition | 16 | D | P2 | JSON-Condition (id 14): nur ausgewaehlte User duerfen buchen. |
| `mod_booking\bo_availability\conditions\allowedtobookininstance` | `classes/bo_availability/conditions/allowedtobookininstance.php` | Condition | 17 | D | P2 | JSON-Condition (id 18): instanzweites Buchlimit/Erlaubnis; apply_customdata. |
| `mod_booking\bo_availability\conditions\maxoptionsfromcategory` | `classes/bo_availability/conditions/maxoptionsfromcategory.php` | Condition | 17 | D | P2 | Limit (id 28): max. Anzahl Optionen pro Kategorie buchbar. Hardcoded-Variante (is_json_compatible=false) mit JSON-aehnlicher Struktur. |
| `mod_booking\bo_availability\bo_subinfo` | `classes/bo_availability/bo_subinfo.php` | Service | 12 | C | P2 | Spiegel von bo_info fuer Subbooking-Availability; schlanker, ohne Override/Memo/SQL. Laedt Subconditions via glob(). |
| `mod_booking\bo_availability\conditions\cancelmyself` | `classes/bo_availability/conditions/cancelmyself.php` | Condition | 13 | C | P2 | Aktion (id 105): Selbst-Stornierung durch User inkl. Cooling-off-Periode. |
| `mod_booking\bo_availability\conditions\slotbooking` | `classes/bo_availability/conditions/slotbooking.php` | Condition | 14 | C | P2 | Gate (id 2): Slot-Auswahl vor Buchung; schreibt Slot-JSON in Answer, License-Check. |
| `mod_booking\bo_availability\conditions\askforconfirmation` | `classes/bo_availability/conditions/askforconfirmation.php` | Condition | 12 | C | P3 | Gate (id 0): Buchung erfordert Bestaetigung/Warteliste durch Trainer. |
| `mod_booking\bo_availability\conditions\bookitbutton` | `classes/bo_availability/conditions/bookitbutton.php` | Condition | 13 | B | P3 | Aktion (id -90): zeigt immer den Bookit-Button, blockt strukturell. Single-Source der book-intent Override-IDs. |
| `mod_booking\bo_availability\conditions\bookwithcredits` | `classes/bo_availability/conditions/bookwithcredits.php` | Condition | 12 | C | P3 | Aktion (id -50): Buchen mit Credits statt Geld. |
| `mod_booking\bo_availability\conditions\bookwithsubscription` | `classes/bo_availability/conditions/bookwithsubscription.php` | Condition | 12 | C | P3 | Aktion (id -30): Buchen via Abonnement. |
| `mod_booking\bo_availability\conditions\alreadybooked` | `classes/bo_availability/conditions/alreadybooked.php` | Condition | 12 | C | P3 | Status (id 150): User hat die Option bereits gebucht. |
| `mod_booking\bo_availability\conditions\priceisset` | `classes/bo_availability/conditions/priceisset.php` | Condition | 12 | C | P3 | Aktion (id -70): erkennt gesetzten Preis und steuert Warenkorb-/Checkout-Flow. |
| `mod_booking\bo_availability\conditions\electivenotbookable` | `classes/bo_availability/conditions/electivenotbookable.php` | Condition | 12 | C | P3 | Sperre (id -5): Wahlfach-Regeln verhindern Buchung. |
| `mod_booking\bo_availability\conditions\confirmcancel` | `classes/bo_availability/conditions/confirmcancel.php` | Condition | 12 | C | P3 | Aktion (id 170): Bestaetigungsschritt einer Stornierung. |
| `mod_booking\bo_availability\conditions\notifymelist` | `classes/bo_availability/conditions/notifymelist.php` | Condition | 12 | C | P3 | Gate (id 100): Benachrichtigungsliste statt Buchung (wenn ausgebucht). |
| `mod_booking\bo_availability\conditions\electivebookitbutton` | `classes/bo_availability/conditions/electivebookitbutton.php` | Condition | 12 | C | P3 | Aktion (id -10): Bookit-Button im Wahlfach-/Elective-Kontext. |
| `mod_booking\bo_availability\conditions\fullybooked` | `classes/bo_availability/conditions/fullybooked.php` | Condition | 12 | B | P3 | Sperre (id 90): Option ausgebucht (kein freier Platz). |
| `mod_booking\bo_availability\conditions\subbooking_blocks` | `classes/bo_availability/conditions/subbooking_blocks.php` | Condition | 11 | B | P3 | Sperre (id 45): ein Subbooking blockiert die Hauptbuchung. |
| `mod_booking\bo_availability\conditions\subbooking` | `classes/bo_availability/conditions/subbooking.php` | Condition | 11 | B | P3 | Gate (id 40): Subbooking-Seite vor der Hauptbuchung. |
| `mod_booking\bo_availability\conditions\slotmove` | `classes/bo_availability/conditions/slotmove.php` | Condition | 14 | B | P3 | Aktion (id 155): Self-Service Slot-Umbuchung fuer bereits gebuchte User. |
| `mod_booking\bo_availability\condition_visibility_manager` | `classes/bo_availability/condition_visibility_manager.php` | Service | 7 | B | P3 | Wendet Skip/Freeze auf Options-mform an: Freeze+Warnung fuer Berechtigte, Hide fuer andere; ueber freezable_condition-Elementliste. |
| `mod_booking\bo_availability\condition_state_helper` | `classes/bo_availability/condition_state_helper.php` | Service | 5 | B | P3 | Loest Tri-State je Condition-ID (0 inaktiv/1 freeze/2 skip+freeze) aus Config inkl. Legacy-Mapping und Enrollink-Defaults. |

## S04 — booking_process_bookit

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\bookit_request_overrides` | `classes/bookit_request_overrides.php` | DTO | 2 | A | - | Parst/validiert optionale overrideids aus dem Webservice-Payload und konsumiert sie einmalig eng begrenzt (multiplebookings + cancelmyself). |
| `mod_booking\bo_actions\action_types\cancelbooking` | `classes/bo_actions/action_types/cancelbooking.php` | Condition | 2 | A | - | bo_action-Typ: storniert die Buchung des Users (user_delete_response) und bricht weitere Aktionen ab. |
| `mod_booking\booking_bookit` | `classes/booking_bookit.php` | Service | 5 | D | P1 | Zentraler Orchestrator des Buchungs-Flows: rendert kontextabhaengig Bookit-Button/Modal und fuehrt die zustandsbehaftete Webservice-Buchung (bookit) aus. |
| `mod_booking\elective` | `classes/elective.php` | Domaenenobjekt | 18 | D | P1 | Wahlpflicht-Logik: Formularfelder, Pflicht-/Ausschluss-Kombinationen, Credit-Berechnung, Reihenfolge und verzoegerte Kurseinschreibung. |
| `mod_booking\local\book_all_students` | `classes/local/book_all_students.php` | Service | 12 | C | P2 | Bulk-Buchung aller Student:innen eines Kurses in eine Option (Adhoc-Task) inkl. Slotbooking-Auswahl und Kapazitaets-Stopp. |
| `mod_booking\bo_actions\actions_info` | `classes/bo_actions/actions_info.php` | Service | 11 | C | P2 | Registry/Service fuer bo_actions: Discovery, Formularaufbau, Speichern/Loeschen und Ausfuehrung (apply_actions) nach Buchung/Storno. |
| `mod_booking\booking_subbookit` | `classes/booking_subbookit.php` | Service | 4 | D | P2 | Strukturell paralleler Flow fuer Subbookings: Button-Rendering und Webservice-Buchung von Subbooking-Slots. |
| `mod_booking\bo_actions\action_types\executerestscript` | `classes/bo_actions/action_types/executerestscript.php` | Condition | 4 | C | P2 | bo_action-Typ: ruft externes REST-Script per cURL auf (Form-POST oder JSON-Body mit Placeholder-Substitution). |
| `mod_booking\bo_actions\action_types\userprofilefield` | `classes/bo_actions/action_types/userprofilefield.php` | Condition | 2 | B | P3 | bo_action-Typ: manipuliert ein User-Profilfeld (set/add/subtract/adddate) nach Buchung. |
| `mod_booking\bo_actions\action_types\bookotheroptions` | `classes/bo_actions/action_types/bookotheroptions.php` | Condition | 2 | B | P3 | bo_action-Typ: bucht den User in weitere ausgewaehlte Optionen mit Force-Modus. |
| `mod_booking\bo_actions\booking_action` | `classes/bo_actions/booking_action.php` | Condition | 4 | B | P3 | Abstrakte Basisklasse aller bo_action-Typen: speichert Aktionen ins Options-JSON und definiert das apply_action-Contract. |
| `mod_booking\local\confirmationworkflow\confirmation` | `classes/local/confirmationworkflow/confirmation.php` | Service | 2 | B | P3 | Bruecke zur Bestaetigungs-Capability der bookingextension-Subplugins (Confirm-Recht + benoetigte Bestaetigungsanzahl). |

## S05 — pricing_shoppingcart

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\price` | `classes/price.php` | Service/Domänenobjekt (Preise + Formel + Kampagnen) | 21 | D | P1 | Zentrale, überwiegend statische Preis-API: Auflösung des für einen User gültigen Preises, Speichern/Löschen von Preis-Records, JSON-Preisformel, Kampagnen-Anwendung, Caching und mform-Handling. |
| `mod_booking\shopping_cart\service_provider` | `classes/shopping_cart/service_provider.php` | Integration/Callback-Adapter zu local_shopping_cart | 19 | C | P2 | Implementiert den local_shopping_cart service_provider-Callback-Vertrag: reserviert/lädt Cartitems (option/subbooking/moveslot), baut Beschreibung, bucht bei Checkout, storniert/gibt frei, prüft Hinzufügbarkeit und Stückzahl. |
| `mod_booking\local\pricecategories_handler` | `classes/local/pricecategories_handler.php` | Service (CRUD Preiskategorien) | 7 | B | P3 | Verarbeitet das pricecategories_form (diff-basiert update/insert), liefert indexierte Kategorienlisten und bietet idempotenten programmatischen upsert_pricecategory inkl. Reaktivierung. |

## S06 — booking_rules

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\booking_rules\rules\templates\ruletemplate_bookingoption_booked` | `classes/booking_rules/rules/templates/ruletemplate_bookingoption_booked.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 1): react_on_event bookingoption_booked -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_confirmwaitinglist` | `classes/booking_rules/rules/templates/ruletemplate_confirmwaitinglist.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 2): waitinglist_booked -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_userstorno` | `classes/booking_rules/rules/templates/ruletemplate_userstorno.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 5): bookinganswer_cancelled -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_courseupdate` | `classes/booking_rules/rules/templates/ruletemplate_courseupdate.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 6): bookingoption_updated + select_student_in_bo. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_bookingoptioncompleted` | `classes/booking_rules/rules/templates/ruletemplate_bookingoptioncompleted.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 9): bookingoption_completed -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_usercancellation` | `classes/booking_rules/rules/templates/ruletemplate_usercancellation.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 10): bookingoption_cancelled + select_student_in_bo. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_paymentconfirmation` | `classes/booking_rules/rules/templates/ruletemplate_paymentconfirmation.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 13): shopping_cart payment_confirmed -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_userpoll` | `classes/booking_rules/rules/templates/ruletemplate_userpoll.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 7): rule_daysbefore + select_student_in_bo (Poll). |
| `mod_booking\booking_rules\rules\templates\ruletemplate_trainercancellation` | `classes/booking_rules/rules/templates/ruletemplate_trainercancellation.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 12): bookingoption_cancelled + select_teacher_in_bo. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_daysbeforestart` | `classes/booking_rules/rules/templates/ruletemplate_daysbeforestart.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 3): rule_daysbefore + select_student_in_bo. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_trainerpoll` | `classes/booking_rules/rules/templates/ruletemplate_trainerpoll.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 8): rule_daysbefore + select_teacher_in_bo (Poll). |
| `mod_booking\booking_rules\rules\templates\ruletemplate_trainerreminderbeforestart` | `classes/booking_rules/rules/templates/ruletemplate_trainerreminderbeforestart.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 4): rule_daysbefore + select_teacher_in_bo. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_optiondatesteacheradded` | `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacheradded.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 15): optiondates_teacher_added -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_optiondatesteacherdeleted` | `classes/booking_rules/rules/templates/ruletemplate_optiondatesteacherdeleted.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 16): optiondates_teacher_deleted -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_bookingoptionuncompleted` | `classes/booking_rules/rules/templates/ruletemplate_bookingoptionuncompleted.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 14): bookingoption_uncompleted -> send_mail. |
| `mod_booking\booking_rules\rules\templates\ruletemplate_sessionreminders` | `classes/booking_rules/rules/templates/ruletemplate_sessionreminders.php` | Template/Seed | 2 | A | - | Vordefiniertes Regel-Record (id 11): rule_daysbefore optiondatestarttime + select_student_in_bo. |
| `mod_booking\booking_rules\rules_info` | `classes/booking_rules/rules_info.php` | Service/Orchestrator | 16 | D | P1 | Zentraler Orchestrator: Discovery, Save, Event-Sammlung, cancelrules-Filter, Ausfuehrung. |
| `mod_booking\booking_rules\rules\rule_react_on_event` | `classes/booking_rules/rules/rule_react_on_event.php` | Rule/Trigger | 11 | D | P1 | Reagiert auf Moodle-/Subplugin-/shopping_cart-Events; Zustands-Conditions; baut Options-SQL. |
| `mod_booking\booking_rules\conditions\select_user_shopping_cart` | `classes/booking_rules/conditions/select_user_shopping_cart.php` | Condition | 8 | D | P1 | User aus offenen Ratenzahlungen (local_shopping_cart_history JSON-SQL), nur pg/mysql8/mariadb10.6. |
| `mod_booking\booking_rules\actions\send_mail_interval` | `classes/booking_rules/actions/send_mail_interval.php` | Action | 9 | C | P1 | Gestaffelter Mailversand fuer Warteliste (one-user-at-a-time-Kette), erzeugt inline confirm-Action. |
| `mod_booking\booking_rules\actions\confirm_bookinganswer` | `classes/booking_rules/actions/confirm_bookinganswer.php` | Action | 11 | C | P1 | WL-Bestaetigung als one-at-a-time-Kette; queued confirm_bookinganswer_by_rule_adhoc. |
| `mod_booking\booking_rules\rules\rule_specifictime` | `classes/booking_rules/rules/rule_specifictime.php` | Rule/Trigger | 9 | C | P2 | Zeit-Trigger mit Sekunden-Granularitaet (Nachfolger von daysbefore); deprecated days->seconds. |
| `mod_booking\booking_rules\rules\rule_daysbefore` | `classes/booking_rules/rules/rule_daysbefore.php` | Rule/Trigger | 9 | C | P2 | Zeit-Trigger N Tage vor/nach Datumsfeld; DST-sichere nextruntime; optiondate-daystonotify-Override. |
| `mod_booking\booking_rules\conditions\select_user_from_event` | `classes/booking_rules/conditions/select_user_from_event.php` | Condition | 9 | C | P2 | User = Ausloeser(userid) oder betroffener(relateduserid) des Events; restauriert Event. |
| `mod_booking\booking_rules\conditions\select_deputy_of_supervisor` | `classes/booking_rules/conditions/select_deputy_of_supervisor.php` | Condition | 8 | C | P2 | Stellvertreter ueber zwei Profilfelder (Supervisor->Deputy-Subquery). |
| `mod_booking\booking_rules\conditions\enter_userprofilefield` | `classes/booking_rules/conditions/enter_userprofilefield.php` | Condition | 8 | C | P2 | Match user_info_data.data gegen eingegebenen Wert (= / ~ contains). |
| `mod_booking\booking_rules\conditions\match_userprofilefield` | `classes/booking_rules/conditions/match_userprofilefield.php` | Condition | 8 | C | P2 | Match Profilfeld gegen Optionsfeld (text/location/address). |
| `mod_booking\booking_rules\actions\send_mail` | `classes/booking_rules/actions/send_mail.php` | Action | 9 | B | P2 | Sofortiger Mailversand (+ical) via send_mail_by_rule_adhoc. |
| `mod_booking\booking_rules\actions\send_copy_of_mail` | `classes/booking_rules/actions/send_copy_of_mail.php` | Action | 9 | C | P2 | Kopie einer Event-Mail an Empfaengerkreis; nur custom_message_sent/custom_bulk. |
| `mod_booking\booking_rules\conditions\select_student_in_bo` | `classes/booking_rules/conditions/select_student_in_bo.php` | Condition | 8 | C | P2 | Teilnehmer nach Buchungsstatus (booked/wl/notify/deleted/<=wl); userstotreat-Subquery aus Event. |
| `mod_booking\booking_rules\conditions\select_users_from_userfield_of_eventuser` | `classes/booking_rules/conditions/select_users_from_userfield_of_eventuser.php` | Condition | 8 | C | P2 | User-IDs aus Profilfeld des Event-Users (Vorabquery + IN). |
| `mod_booking\booking_rules\conditions\select_responsible_contact_in_bo` | `classes/booking_rules/conditions/select_responsible_contact_in_bo.php` | Condition | 8 | C | P2 | Verantwortliche Kontakte aus CSV-Feld; getrennte Postgres-/MySQL-MariaDB-Split-SQL. |
| `mod_booking\booking_rules\booking_rules` | `classes/booking_rules/booking_rules.php` | Service/Repository | 6 | B | P2 | Liest/filtert gespeicherte Regeln je Kontext (UNION Module+System), Kontext-Vererbung per path, rendert Liste. |
| `mod_booking\booking_rules\conditions_info` | `classes/booking_rules/conditions_info.php` | Service/Discovery | 3 | B | P2 | Instanziert Conditions per glob+Extension-Scan, baut Condition-Form-Teil. |
| `mod_booking\booking_rules\actions_info` | `classes/booking_rules/actions_info.php` | Service/Discovery | 3 | B | P2 | Instanziert Actions per glob, baut Action-Form-Teil mit Kompatibilitaetsfilter. |
| `mod_booking\booking_rules\conditions\select_users` | `classes/booking_rules/conditions/select_users.php` | Condition | 8 | B | P3 | Empfaenger = explizit gewaehlte User-IDs (IN-Liste), AJAX-User-Picker. |
| `mod_booking\booking_rules\conditions\select_booking_manager` | `classes/booking_rules/conditions/select_booking_manager.php` | Condition | 8 | B | P3 | Empfaenger = Booking-Manager der Instanz (JOIN booking.bookingmanager username->user). |
| `mod_booking\booking_rules\conditions\select_teacher_in_bo` | `classes/booking_rules/conditions/select_teacher_in_bo.php` | Condition | 8 | B | P3 | Empfaenger = Trainer der Option (JOIN booking_teachers). |
| `mod_booking\booking_rules\actions\delete_conditions_from_bookinganswer` | `classes/booking_rules/actions/delete_conditions_from_bookinganswer.php` | Action | 9 | B | P3 | Loescht Bedingungen aus booking_answer via delete_conditions_..._adhoc (baid). |
| `mod_booking\booking_rules\booking_rule` | `classes/booking_rules/booking_rule.php` | Interface | 8 | A | P3 | Vertrag aller Trigger-Rules (Form, save/set rulejson, execute, check_if_rule_still_applies). |
| `mod_booking\booking_rules\booking_rule_action` | `classes/booking_rules/booking_rule_action.php` | Interface | 8 | A | P3 | Vertrag der Actions (Wirkung); queued i.d.R. Adhoc-Tasks. |
| `mod_booking\booking_rules\booking_rule_condition` | `classes/booking_rules/booking_rule_condition.php` | Interface | 8 | A | P3 | Vertrag der Empfaengerauswahl-Conditions; execute mutiert SQL-Skelett per Referenz. |

## S07 — campaigns

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\booking_campaigns\campaigns_info` | `classes/booking_campaigns/campaigns_info.php` | Service/Factory | 12 | C | P2 | Statische Fassade: Discovery/Factory der Kampagnentypen, Persistenz-Orchestrierung, gemeinsamer mform-Teil und Regel-Helfer (active?/profilefield). |
| `mod_booking\booking_campaigns\campaigns\campaign_customfield` | `classes/booking_campaigns/campaigns/campaign_customfield.php` | Domaenenobjekt/Kampagnentyp | 13 | C | P2 | Kampagne, die Preis (pricefactor, optional userspezifisch) und Buchungslimit (limitfactor, Overbooking-Korrektur) modifiziert. |
| `mod_booking\booking_campaigns\campaigns\campaign_blockbooking` | `classes/booking_campaigns/campaigns/campaign_blockbooking.php` | Domaenenobjekt/Kampagnentyp | 13 | C | P2 | Kampagne, die Buchung nach Auslastung blockiert (blockbelow/above/always, percentageavailableplaces) und blockinglabel liefert. |
| `mod_booking\booking_campaigns\booking_campaign` | `classes/booking_campaigns/booking_campaign.php` | Interface/Extension-Point | 12 | A | P3 | Vertrag aller Kampagnentypen: Form-Aufbau, Persistenz, Aktivitaets-/Preis-/Limit-/Blocking-Logik. |

## S08 — subbookings

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\subbookings\booking_subbooking` | `classes/subbookings/booking_subbooking.php` | Interface/Extension-Point | 16 | B | - | Vertrag aller Subbooking-Typen (Config-, Hydration-, Laufzeit-/Buchungs-Methoden inkl. Status-Hooks); sauberer Extension-Point, von allen sb_types implementiert. |
| `mod_booking\subbookings\subbookings_info` | `classes/subbookings/subbookings_info.php` | Service/Factory/Status-Maschine | 14 | C | P2 | Zentraler statischer Service: Typ-Discovery (glob+Whitelist), Form-Integration, CRUD auf booking_subbooking_options und die Answer-Status-Maschine (save_response/update_or_insert_answer). |
| `mod_booking\subbookings\sb_types\subbooking_timeslot` | `classes/subbookings/sb_types/subbooking_timeslot.php` | sb_type | 16 | C | P2 | Zeitfenster-Reservierung: zerlegt Session-Zeiten der Option in Slots fester duration (optional Entitaet), jeder Slot einzeln buchbar; blockierend (block=1). Aktuell per Whitelist deaktiviert. |
| `mod_booking\subbookings\sb_types\subbooking_additionalperson` | `classes/subbookings/sb_types/subbooking_additionalperson.php` | sb_type | 14 | C | P2 | Subbooking zum Hinzubuchen zusaetzlicher Personen mit Multiplikator-Preis und Namens-/Altersangaben; soft (block=0), schreibt bei Reservation places in fremde booking_answers. |
| `mod_booking\subbookings\sb_types\subbooking_additionalitem` | `classes/subbookings/sb_types/subbooking_additionalitem.php` | sb_type | 14 | C | P2 | Subbooking fuer Zusatz-Artikel, optional an einen Customform-Wert der Hauptoption gekoppelt (subbookingadditemformlink); soft (block=0), is_blocking abhaengig von Customform-Daten. |
| `mod_booking\subbookings` | `classes/subbookings.php` | Domaenenobjekt | 2 | D | P2 | Vermeintliches Aggregat 'Subbookings einer Option'; wirkt halbfertig/verwaist, Konstruktor holt Cache ohne Nutzung und user_submit_response verwirft json-Parameter, redundant zur Status-Maschine in subbookings_info. |
| `mod_booking\subbookings\subbookings_cache` | `classes/subbookings/subbookings_cache.php` | Util/Cache-Marker | 0 | D | P3 | Leere Marker-Klasse ohne Methoden/Felder; realer Cache 'subbookings' wird via cache::make in subbookings.php angesprochen, Definition in db/caches.php ausserhalb des Scopes. |

## S09 — messaging_placeholders

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\placeholders\placeholders\datesandentities` | `classes/placeholders/placeholders/datesandentities.php` | Field | 2 | B | - | Rendert Termine inkl. zugeordneter Entities (Ort/Equipment) als Platzhalter. |
| `mod_booking\placeholders\placeholders\pollurl` | `classes/placeholders/placeholders/pollurl.php` | Field | 2 | B | - | Erzeugt die Pollurl (Umfrage-Link) fuer Teilnehmer-Mails. |
| `mod_booking\placeholders\placeholders\pollurlteachers` | `classes/placeholders/placeholders/pollurlteachers.php` | Field | 2 | B | - | Erzeugt die Pollurl fuer Lehrer-Mails. |
| `mod_booking\placeholders\placeholders\description` | `classes/placeholders/placeholders/description.php` | Field | 3 | B | - | Rendert die Optionsbeschreibung (kontextabhaengig Mail/Website/Calendar). |
| `mod_booking\placeholders\placeholders\bookingoptionname` | `classes/placeholders/placeholders/bookingoptionname.php` | Field | 3 | B | - | Liefert den Namen der Buchungsoption (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\bookingoptiondetaillink` | `classes/placeholders/placeholders/bookingoptiondetaillink.php` | Field | 2 | B | - | Erzeugt den Link zur Detailseite der Buchungsoption. |
| `mod_booking\placeholders\placeholders\qrenrollink` | `classes/placeholders/placeholders/qrenrollink.php` | Field | 2 | B | - | Erzeugt einen QR-Code-Einschreibe-Link (ggf. aus Event-Daten). |
| `mod_booking\placeholders\placeholders\coursename` | `classes/placeholders/placeholders/coursename.php` | Field | 3 | B | - | Liefert den Namen des verknuepften Kurses (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\enrollink` | `classes/placeholders/placeholders/enrollink.php` | Field | 2 | B | - | Erzeugt einen Einschreibe-Link (ggf. aus Event-Daten). |
| `mod_booking\placeholders\placeholders\bookingdetails` | `classes/placeholders/placeholders/bookingdetails.php` | Field | 2 | B | - | Rendert die Detailansicht der Buchungsoption als Platzhalterwert. |
| `mod_booking\placeholders\placeholders\courseid` | `classes/placeholders/placeholders/courseid.php` | Field | 3 | B | - | Liefert die zugeordnete Moodle-Kurs-ID (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\optionid` | `classes/placeholders/placeholders/optionid.php` | Field | 3 | B | - | Liefert die Options-ID (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\profilepicture` | `classes/placeholders/placeholders/profilepicture.php` | Field | 2 | B | - | Liefert das Profilbild des Nutzers als Platzhalter. |
| `mod_booking\placeholders\placeholders\selflearningcourse` | `classes/placeholders/placeholders/selflearningcourse.php` | Field | 3 | B | - | Liefert Self-Learning-Course-Information (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\gotobookingoption` | `classes/placeholders/placeholders/gotobookingoption.php` | Field | 2 | B | - | Erzeugt einen Direktlink zur Buchungsoption. |
| `mod_booking\placeholders\placeholders\type` | `classes/placeholders/placeholders/type.php` | Field | 3 | B | - | Liefert den Typ der Option (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\bookedslotsfromevent` | `classes/placeholders/placeholders/bookedslotsfromevent.php` | Field | 2 | B | - | Liefert gebuchte Slots aus den Event-Daten (Slotbooking) als Platzhalter. |
| `mod_booking\placeholders\placeholders\dates` | `classes/placeholders/placeholders/dates.php` | Field | 2 | B | - | Rendert die Optionstermine via output\optiondates_only-Template als Platzhalter. |
| `mod_booking\placeholders\placeholders\emailrelated` | `classes/placeholders/placeholders/emailrelated.php` | Field | 2 | B | - | Liefert die E-Mail des related users aus den Event-Daten. |
| `mod_booking\placeholders\placeholders\firstnamerelated` | `classes/placeholders/placeholders/firstnamerelated.php` | Field | 2 | B | - | Liefert den Vornamen des related users aus Event-Daten. |
| `mod_booking\placeholders\placeholders\lastnamerelated` | `classes/placeholders/placeholders/lastnamerelated.php` | Field | 2 | B | - | Liefert den Nachnamen des related users aus Event-Daten. |
| `mod_booking\placeholders\placeholders\changes` | `classes/placeholders/placeholders/changes.php` | Field | 4 | B | - | Restauriert ein bookingoption_updated-Event aus rulejson und rendert dessen Simplified-Description fuer Aenderungsbenachrichtigungen. |
| `mod_booking\placeholders\placeholders\bookinglink` | `classes/placeholders/placeholders/bookinglink.php` | Field | 2 | B | - | Erzeugt den Link zur Buchungsinstanz/-uebersicht. |
| `mod_booking\placeholders\placeholders\courselink` | `classes/placeholders/placeholders/courselink.php` | Field | 2 | B | - | Erzeugt den Link zum verknuepften Moodle-Kurs. |
| `mod_booking\placeholders\placeholders\email` | `classes/placeholders/placeholders/email.php` | Field | 3 | B | - | Liefert die E-Mail-Adresse des Nutzers (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\firstname` | `classes/placeholders/placeholders/firstname.php` | Field | 3 | B | - | Liefert den Vornamen des Nutzers (Referenz-Muster, pollurl-faehig). |
| `mod_booking\placeholders\placeholders\journal` | `classes/placeholders/placeholders/journal.php` | Field | 2 | B | - | Liefert Journal-bezogene Daten der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\lastname` | `classes/placeholders/placeholders/lastname.php` | Field | 3 | B | - | Liefert den Nachnamen des Nutzers (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\qrusername` | `classes/placeholders/placeholders/qrusername.php` | Field | 2 | B | - | Liefert einen QR-Code mit Username als Platzhalter. |
| `mod_booking\placeholders\placeholders\shoppingcartplaceholder` | `classes/placeholders/placeholders/shoppingcartplaceholder.php` | Field | 2 | B | - | Liefert shopping_cart-bezogene Werte (Preis/Cart) aus Event-Daten. |
| `mod_booking\placeholders\placeholders\baid` | `classes/placeholders/placeholders/baid.php` | Field | 3 | B | - | Liefert die Booking-Answer-ID (Teilnahme-Datensatz) als Platzhalter. |
| `mod_booking\placeholders\placeholders\coursecalendarurl` | `classes/placeholders/placeholders/coursecalendarurl.php` | Field | 2 | B | - | Erzeugt die Kurs-Kalender-URL als Platzhalter. |
| `mod_booking\placeholders\placeholders\qrid` | `classes/placeholders/placeholders/qrid.php` | Field | 2 | B | - | Liefert eine QR-Code-ID als Platzhalter. |
| `mod_booking\placeholders\placeholders\status` | `classes/placeholders/placeholders/status.php` | Field | 2 | B | - | Liefert den Buchungsstatus (gebucht/Warteliste/...) als Platzhalter. |
| `mod_booking\placeholders\placeholders\optiondatefromevent` | `classes/placeholders/placeholders/optiondatefromevent.php` | Field | 2 | B | - | Liefert ein konkretes Optionsdatum aus Event-Daten; DB-Zugriff. |
| `mod_booking\placeholders\placeholders\semester` | `classes/placeholders/placeholders/semester.php` | Field | 2 | B | - | Liefert den Semester-Namen der Option; DB-Zugriff. |
| `mod_booking\placeholders\placeholders\usercalendarurl` | `classes/placeholders/placeholders/usercalendarurl.php` | Field | 2 | B | - | Erzeugt die persoenliche Kalender-URL des Nutzers. |
| `mod_booking\placeholders\placeholders\bookingreportlink` | `classes/placeholders/placeholders/bookingreportlink.php` | Field | 2 | B | - | Erzeugt den Link zum Buchungsreport (Lehrer/Manager). |
| `mod_booking\placeholders\placeholders\enddate` | `classes/placeholders/placeholders/enddate.php` | Field | 2 | B | - | Liefert das Enddatum der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\endtime` | `classes/placeholders/placeholders/endtime.php` | Field | 2 | B | - | Liefert die Endzeit der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\startdate` | `classes/placeholders/placeholders/startdate.php` | Field | 2 | B | - | Liefert das Startdatum der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\numberparticipants` | `classes/placeholders/placeholders/numberparticipants.php` | Field | 2 | B | - | Liefert die Anzahl der Teilnehmer einer Option. |
| `mod_booking\placeholders\placeholders\numberwaitinglist` | `classes/placeholders/placeholders/numberwaitinglist.php` | Field | 2 | B | - | Liefert die Anzahl Personen auf der Warteliste. |
| `mod_booking\placeholders\placeholders\pollstartdate` | `classes/placeholders/placeholders/pollstartdate.php` | Field | 2 | B | - | Liefert das Startdatum fuer Pollurl-Mails. |
| `mod_booking\placeholders\placeholders\instancename` | `classes/placeholders/placeholders/instancename.php` | Field | 2 | B | - | Liefert den Namen der Buchungsinstanz als Platzhalter. |
| `mod_booking\placeholders\placeholders\certificateurl` | `classes/placeholders/placeholders/certificateurl.php` | Field | 2 | B | - | Liefert die Zertifikats-URL (tool_certificate) fuer den Nutzer; DB-Zugriff. |
| `mod_booking\placeholders\placeholders\department` | `classes/placeholders/placeholders/department.php` | Field | 2 | B | - | Liefert das Department-Feld der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\duration` | `classes/placeholders/placeholders/duration.php` | Field | 2 | B | - | Liefert die Dauer der Buchungsoption als Platzhalter. |
| `mod_booking\placeholders\placeholders\eventtype` | `classes/placeholders/placeholders/eventtype.php` | Field | 2 | B | - | Liefert den Event-Typ der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\institution` | `classes/placeholders/placeholders/institution.php` | Field | 2 | B | - | Liefert das Institution-Feld der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\location` | `classes/placeholders/placeholders/location.php` | Field | 2 | B | - | Liefert den Ort/Location der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\participant` | `classes/placeholders/placeholders/participant.php` | Field | 2 | B | - | Liefert den vollen Namen des Teilnehmers als Platzhalter. |
| `mod_booking\placeholders\placeholders\price` | `classes/placeholders/placeholders/price.php` | Field | 2 | B | - | Liefert den Preis der Buchung (ggf. aus Event/shopping_cart-Daten). |
| `mod_booking\placeholders\placeholders\starttime` | `classes/placeholders/placeholders/starttime.php` | Field | 2 | B | - | Liefert die Startzeit der Option als Platzhalter. |
| `mod_booking\local\templaterule` | `classes/local/templaterule.php` | Service | 2 | B | - | Baut die Auswahl-Liste der Rule-Vorlagen (Default + Code-Templates + DB-Rules mit useastemplate=1) und liefert Template-Records. |
| `mod_booking\placeholders\placeholders\eventdescription` | `classes/placeholders/placeholders/eventdescription.php` | Field | 2 | B | - | Liefert die Beschreibung eines Events/Termins als Platzhalter. |
| `mod_booking\placeholders\placeholders\bookingconfirmationlink` | `classes/placeholders/placeholders/bookingconfirmationlink.php` | Field | 2 | B | - | Erzeugt einen Bestaetigungs-/Zahlungslink (ggf. aus Event/Cart-Daten). |
| `mod_booking\placeholders\placeholders\address` | `classes/placeholders/placeholders/address.php` | Field | 2 | B | - | Liefert die Adresse/Location der Buchungsoption als Platzhalterwert. |
| `mod_booking\placeholders\placeholders\teacher` | `classes/placeholders/placeholders/teacher.php` | Field | 2 | B | - | Liefert einen einzelnen Lehrer (indexierbar) als Platzhalter. |
| `mod_booking\placeholders\placeholders\teachers` | `classes/placeholders/placeholders/teachers.php` | Field | 2 | B | - | Liefert die Liste aller Lehrer der Option als Platzhalter. |
| `mod_booking\placeholders\placeholders\username` | `classes/placeholders/placeholders/username.php` | Field | 2 | B | - | Liefert den Username des Nutzers als Platzhalter. |
| `mod_booking\placeholders\placeholders\restresponse` | `classes/placeholders/placeholders/restresponse.php` | Field | 2 | B | - | Liefert eine REST-Antwort/Response-Wert aus Event-Daten als Platzhalter. |
| `mod_booking\placeholders\placeholders\title` | `classes/placeholders/placeholders/title.php` | Field | 3 | B | - | Liefert den Titel der Buchungsoption als Platzhalter. |
| `mod_booking\placeholders\placeholders\userid` | `classes/placeholders/placeholders/userid.php` | Field | 3 | B | - | Liefert die User-ID (pollurl-faehig). |
| `mod_booking\placeholders\placeholders\duedate` | `classes/placeholders/placeholders/duedate.php` | Field | 2 | B | - | Liefert das Faelligkeitsdatum einer Ratenzahlung (Installment). |
| `mod_booking\placeholders\placeholders\bookedplaces` | `classes/placeholders/placeholders/bookedplaces.php` | Field | 2 | B | - | Liefert die Anzahl der gebuchten Plaetze einer Option. |
| `mod_booking\placeholders\placeholders\numberofinstallment` | `classes/placeholders/placeholders/numberofinstallment.php` | Field | 2 | B | - | Liefert die laufende Ratennummer (Installment). |
| `mod_booking\placeholders\placeholders\installmentprice` | `classes/placeholders/placeholders/installmentprice.php` | Field | 2 | B | - | Liefert den Preis einer einzelnen Rate (Installment). |
| `mod_booking\placeholders\placeholders\slotsbooked` | `classes/placeholders/placeholders/slotsbooked.php` | Field | 2 | B | - | Liefert die Liste gebuchter Slots (Slotbooking). |
| `mod_booking\placeholders\placeholders\slotscancelled` | `classes/placeholders/placeholders/slotscancelled.php` | Field | 2 | B | - | Liefert die Liste stornierter Slots (Slotbooking). |
| `mod_booking\placeholders\placeholders\slotsmovedfrom` | `classes/placeholders/placeholders/slotsmovedfrom.php` | Field | 2 | B | - | Liefert die Quell-Slots eines Slot-Umzugs (Slotbooking). |
| `mod_booking\placeholders\placeholders\slotsmovedto` | `classes/placeholders/placeholders/slotsmovedto.php` | Field | 2 | B | - | Liefert die Ziel-Slots eines Slot-Umzugs (Slotbooking). |
| `mod_booking\placeholders\placeholder_base` | `classes/placeholders/placeholder_base.php` | DTO | 2 | A | - | Minimal-Basisklasse aller Platzhalter mit Default-Flags is_applicable()/for_pollurl(). |
| `mod_booking\message_controller` | `classes/message_controller.php` | Service | 11 | D | P1 | Orchestriert den gesamten Mailversand: Template-Auswahl, Platzhalter-Rendern, iCal-Anhaenge, Send-Now vs. Adhoc-Queue, PHPMailer-Sonderweg und message_sent-Event. |
| `mod_booking\placeholders\placeholders_info` | `classes/placeholders/placeholders_info.php` | Service | 3 | C | P2 | Zentrale Substitutions-Engine: erkennt Tokens per Regex, dispatcht an Platzhalterklassen (mod_booking + bookingextension), verwaltet Abschnittslogik und Request-Cache. |
| `mod_booking\local\scheduledmails` | `classes/local/scheduledmails.php` | Service | 4 | C | P2 | Listet und bereinigt geplante Adhoc-Mails aus task_adhoc, verknuepft sie mit booking_rules und re-evaluiert Rule-Gueltigkeit. |
| `mod_booking\placeholders\placeholders\customfields` | `classes/placeholders/placeholders/customfields.php` | Field | 4 | C | P2 | Catch-all-Fallback der Engine: loest Booking-Customfields UND User-Profilfelder auf, behandelt -related-Suffix; mutiert text/fieldexists per Referenz. |
| `mod_booking\placeholders\placeholders\customform` | `classes/placeholders/placeholders/customform.php` | Placeholder | 2 | B | P3 | (Orphan-Nachtrag) |

## S10 — output_rendering

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\local\htmlcomponents` | `classes/local/htmlcomponents.php` | Util | 7 | B | - | Statische UI-Helfer; baut BS4/BS5-kompatible Bootstrap-Tabs/Accordions via html_writer. |
| `mod_booking\shortcodes_handler` | `classes/shortcodes_handler.php` | Util | 11 | B | - | Validierung der Shortcode-Vorbedingungen (Aktiv/Passwort/PRO/Pflichtargumente) und Arg-Helfer. |
| `mod_booking\table\bookingoptions_simple_table` | `classes/table/bookingoptions_simple_table.php` | WB-Table | 10 | B | - | Einfache wunderbyte_table-Variante der Optionsliste (Text/Termin/Teacher/Link). |
| `mod_booking\table\booking_history_table` | `classes/table/booking_history_table.php` | WB-Table | 6 | B | - | WB-Table der Buchungshistorie mit JSON-Detailspalte und Statusrendering. |
| `mod_booking\output\col_coursestarttime` | `classes/output/col_coursestarttime.php` | DTO | 2 | B | - | DTO der Kursstart-Spalte (Termine/collapsed-Darstellung). |
| `mod_booking\output\bookit_price` | `classes/output/bookit_price.php` | DTO | 2 | B | - | DTO des Preis-Anteils des Buchen-Buttons. |
| `mod_booking\output\campaignslist` | `classes/output/campaignslist.php` | DTO | 5 | B | - | DTO der Kampagnenliste fuer Admin-UI. |
| `mod_booking\output\col_price` | `classes/output/col_price.php` | DTO | 2 | B | - | DTO der Preis-Spalte (Preis/Waehrung/Buchen-Integration). |
| `mod_booking\output\col_availableplaces` | `classes/output/col_availableplaces.php` | DTO | 3 | B | - | DTO der Spalte freie Plaetze inkl. Warteliste/Buyforuser. |
| `mod_booking\output\elective_modal` | `classes/output/elective_modal.php` | DTO | 4 | B | - | DTO des Elective-Auswahl-Modals. |
| `mod_booking\output\page_allteachers` | `classes/output/page_allteachers.php` | DTO | 2 | B | - | DTO der Uebersichtsseite aller Teacher. |
| `mod_booking\output\scheduledmails` | `classes/output/scheduledmails.php` | DTO | 3 | B | - | Wrapper-DTO um die scheduledmails_table fuer die Settingsseite. |
| `mod_booking\table\teacher_performed_units_table` | `classes/table/teacher_performed_units_table.php` | WB-Table | 11 | B | - | table_sql der je Teacher geleisteten Unterrichtseinheiten/Termine. |
| `mod_booking\output\certificateconditionslist` | `classes/output/certificateconditionslist.php` | DTO | 3 | B | - | DTO der Zertifikatsbedingungen-Liste mit Add-Button. |
| `mod_booking\output\ruleslist` | `classes/output/ruleslist.php` | DTO | 3 | B | - | DTO der Regelliste (booking_rules) je Kontext mit Add-Button. |
| `mod_booking\output\prepagemodal` | `classes/output/prepagemodal.php` | DTO | 2 | B | - | DTO des Vor-Buchungs-Modals (Prepage-Schritte/Buttons). |
| `mod_booking\table\optiontemplatessettings_table` | `classes/table/optiontemplatessettings_table.php` | WB-Table | 4 | B | - | table_sql der Options-Templates (Name/Optionen/Aktion). |
| `mod_booking\output\description\description_base` | `classes/output/description/description_base.php` | DTO | 4 | B | - | Strategy-Basis fuer Beschreibungsrendering: kapselt bookingoption_description, Template und Description-Param. |
| `mod_booking\output\eventslist` | `classes/output/eventslist.php` | DTO | 2 | B | - | DTO einer Event-Log-Liste (gefiltert nach Eventnamen/Spalten). |
| `mod_booking\output\bookingoption_changes` | `classes/output/bookingoption_changes.php` | DTO | 2 | B | - | DTO der angezeigten Aenderungen einer Buchungsoption (z. B. Benachrichtigungen). |
| `mod_booking\output\business_card` | `classes/output/business_card.php` | DTO | 2 | B | - | DTO der Teacher-Visitenkarte (Kontakt/Bild/Statistik). |
| `mod_booking\table\bulkoperations_table` | `classes/table/bulkoperations_table.php` | WB-Table | 2 | B | - | WB-Table fuer Bulk-Operationen auf Buchungsoptionen (Auswahl/Aktion). |
| `mod_booking\table\event_log_table` | `classes/table/event_log_table.php` | WB-Table | 5 | B | - | WB-Table des Event-Logs (Eventname/Beschreibung/User/Zeit). |
| `mod_booking\output\button_notifyme` | `classes/output/button_notifyme.php` | DTO | 3 | B | - | DTO des 'Benachrichtige mich'-Buttons (Wartelisten-Notify). |
| `mod_booking\output\prepageinlinestart` | `classes/output/prepageinlinestart.php` | DTO | 2 | B | - | DTO der inline gerenderten Prepage-Startstufe (z. B. Slotbooking). |
| `mod_booking\output\signin_downloadform` | `classes/output/signin_downloadform.php` | DTO | 2 | B | - | DTO des Anwesenheitslisten-Download-Formulars. |
| `mod_booking\output\coursepage_shortinfo_and_button` | `classes/output/coursepage_shortinfo_and_button.php` | DTO | 2 | B | - | DTO der Kurzinfo+Buchen-Button-Box auf der Kursseite. |
| `mod_booking\output\bookit_button` | `classes/output/bookit_button.php` | DTO | 2 | B | - | DTO des Buchen-Buttons (Status/Label/Aktion). |
| `mod_booking\output\subbooking_timeslot_output` | `classes/output/subbooking_timeslot_output.php` | DTO | 2 | B | - | DTO eines Subbooking-Zeitslots. |
| `mod_booking\output\semesters_holidays` | `classes/output/semesters_holidays.php` | DTO | 2 | A | - | DTO das vorgerenderte Semester-/Feiertags-/Semesterwechsel-Formulare umhuellt. |
| `mod_booking\table\instancetemplatessettings_table` | `classes/table/instancetemplatessettings_table.php` | WB-Table | 2 | B | - | table_sql der Instanz-Templates (Settingsseite). |
| `mod_booking\output\col_teacher` | `classes/output/col_teacher.php` | DTO | 2 | A | - | DTO der Teacher-Spalte (Namen/optional Profilbild). |
| `mod_booking\bookinginstancetemplatessettings_table` | `classes/bookinginstancetemplatessettings_table.php` | Renderer/Table | 3 | B | - | (Orphan-Nachtrag) |
| `mod_booking\output\subbooking_additionalitem_output` | `classes/output/subbooking_additionalitem_output.php` | DTO | 2 | A | - | DTO eines Subbooking-Zusatzartikels. |
| `mod_booking\output\report_edit_bookingnotes` | `classes/output/report_edit_bookingnotes.php` | DTO | 2 | A | - | DTO zum Inline-Editieren von Buchungsnotizen im Report. |
| `mod_booking\output\subbooking_additionalperson_output` | `classes/output/subbooking_additionalperson_output.php` | DTO | 2 | A | - | DTO einer Subbooking-Zusatzperson. |
| `mod_booking\output\col_text_with_description` | `classes/output/col_text_with_description.php` | DTO | 2 | A | - | DTO der Text-Spalte mit Titel-Prefix und Beschreibung. |
| `mod_booking\output\actionslist` | `classes/output/actionslist.php` | DTO | 2 | A | - | DTO der BO-Aktionsliste (boactions) je Option. |
| `mod_booking\output\subbookingslist` | `classes/output/subbookingslist.php` | DTO | 2 | A | - | DTO der Subbookings-Liste je Option. |
| `mod_booking\output\optiondates_only` | `classes/output/optiondates_only.php` | DTO | 2 | A | - | DTO das nur die Termine einer Option ausgibt. |
| `mod_booking\output\instance_description` | `classes/output/instance_description.php` | DTO | 2 | A | - | DTO der Buchungsinstanz-Beschreibung. |
| `mod_booking\output\optiondates_with_entities` | `classes/output/optiondates_with_entities.php` | DTO | 2 | A | - | DTO der Termine inkl. zugeordneter local_entities (Ort/Equipment). |
| `mod_booking\output\col_responsiblecontacts` | `classes/output/col_responsiblecontacts.php` | DTO | 2 | A | - | DTO der Spalte verantwortliche Kontakte. |
| `mod_booking\local\shortcode_filterfield` | `classes/local/shortcode_filterfield.php` | Field | 2 | A | - | Mimt die configdata-Struktur eines Customfields, damit booking_options-Spalten ueber die Customfield-Filterinfrastruktur filterbar sind. |
| `mod_booking\output\col_action` | `classes/output/col_action.php` | DTO | 2 | A | - | DTO der Aktions-Spalte (Edit/Manage-Links je Option). |
| `mod_booking\filters\available_places` | `classes/filters/available_places.php` | Filter | 1 | A | - | Factory fuer einen wiederverwendbaren customfieldfilter 'availableplaces' mit Subquery-SQL ueber freie Plaetze. |
| `mod_booking\output\pricecategories` | `classes/output/pricecategories.php` | DTO | 2 | A | - | DTO das ein vorgerendertes Preiskategorien-Formular umhuellt. |
| `mod_booking\output\bookingoption_dates` | `classes/output/bookingoption_dates.php` | DTO | 2 | A | - | DTO der Terminliste einer Option. |
| `mod_booking\output\col_text` | `classes/output/col_text.php` | DTO | 2 | A | - | DTO der einfachen Text-Spalte. |
| `mod_booking\output\description\description_ical` | `classes/output/description/description_ical.php` | DTO | 1 | A | - | Beschreibungs-Strategy fuer iCal-Ausgabe (eigenes render()). |
| `mod_booking\output\description\description_calendarevent` | `classes/output/description/description_calendarevent.php` | DTO | 1 | A | - | Beschreibungs-Strategy fuer Kalender-Events (eigenes render()). |
| `mod_booking\output\description\description_optionview` | `classes/output/description/description_optionview.php` | DTO | 1 | A | - | Beschreibungs-Strategy fuer die Optionview-Ansicht (eigenes render()). |
| `mod_booking\output\description\description_website` | `classes/output/description/description_website.php` | DTO | 0 | A | - | Beschreibungs-Strategy fuer Web-Ausgabe (setzt nur Template/Param). |
| `mod_booking\output\description\description_mail` | `classes/output/description/description_mail.php` | DTO | 0 | A | - | Beschreibungs-Strategy fuer Mail-Ausgabe (setzt nur Template/Param). |
| `mod_booking\output\description\description_cartitem` | `classes/output/description/description_cartitem.php` | DTO | 0 | A | - | Beschreibungs-Strategy fuer Warenkorb-Item-Ausgabe (setzt nur Template/Param). |
| `mod_booking\output\description\description_teachers` | `classes/output/description/description_teachers.php` | DTO | 0 | A | - | Beschreibungs-Strategy fuer Teacher-Ausgabe (setzt nur Template). |
| `mod_booking\output\description\description_dates` | `classes/output/description/description_dates.php` | DTO | 0 | A | - | Beschreibungs-Strategy fuer Termine-Ausgabe (setzt nur Template). |
| `mod_booking\shortcodes` | `classes/shortcodes.php` | Script | 34 | D | P1 | Statischer Dispatcher fuer alle Booking-Shortcodes; baut pro Shortcode eine wunderbyte_table, setzt Spalten/Filter und WHERE/SQL-Params. |
| `mod_booking\table\bookingoptions_wbtable` | `classes/table/bookingoptions_wbtable.php` | WB-Table | 92 | D | P1 | Haupt-wunderbyte_table der Optionsliste mit 30+ col_*-Renderern, Favoriten-AJAX und gecachtem DB-Query. |
| `mod_booking\output\view` | `classes/output/view.php` | Renderer | 34 | D | P1 | Aggregiert die Plugin-Hauptseite; waehlt je whichview Tab-Tabellen, rendert sie vor und exportiert Flag-/HTML-Array. |
| `mod_booking\table\manageusers_table` | `classes/table/manageusers_table.php` | WB-Table | 25 | C | P2 | WB-Table gebuchter/wartelistiger User pro Option inkl. Verwaltungsaktionen (Bestaetigen/Stornieren/Loeschen/Zertifikat). |
| `mod_booking\output\mobile` | `classes/output/mobile.php` | Renderer | 23 | C | P2 | Statische Output-Klasse fuer Moodle-Mobile-App (System-/Optionsdetail-/MyBookings-/Kursansicht). |
| `mod_booking\output\renderer` | `classes/output/renderer.php` | Renderer | 51 | C | P2 | Einzige plugin_renderer_base; 50 render_*-Methoden delegieren an render_from_template, einige bauen HTML via html_writer. |
| `mod_booking\output\bookingoption_description` | `classes/output/bookingoption_description.php` | DTO | 5 | C | P2 | Bereitet alle Anzeigedaten einer Buchungsoption (Termine/Teacher/Preis/Status/Placeholder) je Description-Param auf. |
| `mod_booking\output\booked_users` | `classes/output/booked_users.php` | DTO | 11 | B | P3 | DTO das je Scope die manageusers_table und booking_history_table rendert und Aktionsbuttons liefert. |
| `mod_booking\table\teachers_instance_report_table` | `classes/table/teachers_instance_report_table.php` | WB-Table | 15 | B | P3 | table_sql-Report ueber geleistete/fehlende Teacher-Stunden je Instanz mit Vertretungen. |
| `mod_booking\output\page_teacher` | `classes/output/page_teacher.php` | DTO | 5 | B | P3 | DTO der Teacher-Profilseite (Stammdaten, Bild, Optionen). |
| `mod_booking\table\scheduledmails_table` | `classes/table/scheduledmails_table.php` | WB-Table | 13 | B | P3 | WB-Table geplanter Mails inkl. col_message-Vorschau und action_deleteitem/cleanupinvalid. |
| `mod_booking\table\optiondates_teachers_table` | `classes/table/optiondates_teachers_table.php` | WB-Table | 13 | B | P3 | WB-Table fuer Termin-Teacher-Zuordnung/Vertretung mit Edit/Abzug/Review und Toggle-AJAX. |

## S11 — external_api

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\external\search_sync_sources` | `classes/external/search_sync_sources.php` | WS | 3 | B | - | Sucht Cohorts/Groups fuer Sync-Regel-Modal; Kontext+Capability geprueft, parametrisiertes sql_like. |
| `mod_booking\external\release_slots` | `classes/external/release_slots.php` | WS | 3 | B | - | Self-Service-Teilstorno gebuchter Slots via slot_mover::release_self. |
| `mod_booking\external\allow_add_item_to_cart` | `classes/external/allow_add_item_to_cart.php` | WS | 3 | B | - | Prueft Warenkorb-Zulaessigkeit einer Option; kurzschliesst bei preislosen Optionen/fehlendem shopping_cart. |
| `mod_booking\external\toggle_notify_user` | `classes/external/toggle_notify_user.php` | WS | 3 | B | - | Schaltet Warteliste-Benachrichtigung um via booking_option::toggle_notify_user. |
| `mod_booking\external\get_slots` | `classes/external/get_slots.php` | WS | 3 | B | - | Liefert selektierbare Slots + Picker-Meta als JSON (slot_dto); Kontext+Capability geprueft. |
| `mod_booking\external\performance` | `classes/external/performance.php` | WS | 3 | B | - | Dispatch von Performance-Aktionen an performance_facade::execute. |
| `mod_booking\external\get_booked_slots` | `classes/external/get_booked_slots.php` | WS | 3 | B | - | Liefert Slot-Report-Daten als JSON (slot_dto); Kontext+Capability mod/booking:view. |
| `mod_booking\external\init_comments` | `classes/external/init_comments.php` | WS | 3 | B | - | Initialisiert die Moodle-Kommentar-Engine (comment::init), parameterlos. |
| `mod_booking\external\get_performance_chart` | `classes/external/get_performance_chart.php` | WS | 3 | B | - | Liefert Chartdaten via performance_renderer::get_chart. |
| `mod_booking\external\addbookingoption` | `classes/external/addbookingoption.php` | WS | 3 | C | P2 | Erstellt oder aktualisiert eine Buchungsoption per Webservice; reicht 47 Felder an webservice_import durch. |
| `mod_booking\external\bookings` | `classes/external/bookings.php` | WS | 3 | C | P2 | Liefert verschachteltes Read-Aggregat (Instanz/Kategorien/Optionen/User/Teacher/Sessions) je Kurs. |
| `mod_booking\external\get_submission_mobile` | `classes/external/get_submission_mobile.php` | WS | 5 | C | P2 | Speichert/merged/reset Mobile-Custom-Form-Daten im MUC-Cache customformuserdata. |
| `mod_booking\external\categories` | `classes/external/categories.php` | WS | 3 | C | P2 | Liefert Top-Kategorien eines Kurses; Datei enthaelt zusaetzlich globale rekursive Hilfsfunktion. |
| `mod_booking\external\delete_measurement` | `classes/external/delete_measurement.php` | WS | 3 | C | P2 | Loescht Performance-Messpunkt(e); Capability editperformance. |
| `mod_booking\external\search_booking_options` | `classes/external/search_booking_options.php` | WS | 3 | C | P2 | Optionssuche via booking_option::load_booking_options_filtered. |
| `mod_booking\external\optiontemplate` | `classes/external/optiontemplate.php` | WS | 3 | C | P2 | Liest kompletten booking_options-Datensatz als JSON-Template. |
| `mod_booking\external\search_courses` | `classes/external/search_courses.php` | WS | 3 | C | P2 | Kurssuche via booking::load_courses. |
| `mod_booking\external\save_slot_selection` | `classes/external/save_slot_selection.php` | WS | 4 | C | P3 | Serverseitige Validierung+Preisberechnung einer Slot-Auswahl, persistiert gueltige Auswahl im Slot-Store. |
| `mod_booking\external\bookit` | `classes/external/bookit.php` | WS | 3 | B | P3 | Bucht Option/Subbooking, rendert Button-Templatedaten, invalidiert User-Answers-Cache. |
| `mod_booking\external\get_parent_categories` | `classes/external/get_parent_categories.php` | WS | 3 | C | P3 | Liefert Coursecat-Knoten fuer Dashboard/Cart-Kontext; derzeit nur statischer Summary-Knoten bzw. leer. |
| `mod_booking\external\load_pre_booking_page` | `classes/external/load_pre_booking_page.php` | WS | 3 | B | P3 | Laedt eine Pre-Booking-Page (Modal-Step) via bo_info::load_pre_booking_page. |
| `mod_booking\external\get_booking_option_description` | `classes/external/get_booking_option_description.php` | WS | 3 | B | P3 | Liefert JSON-Renderdaten der Optionsbeschreibung + Templatename. |
| `mod_booking\external\update_bookingnotes` | `classes/external/update_bookingnotes.php` | WS | 3 | B | P3 | Aktualisiert notes-Feld in booking_answers; Capability updatenotes am Optionskontext. |
| `mod_booking\external\save_option_field_config` | `classes/external/save_option_field_config.php` | WS | 3 | B | P3 | Speichert Optionformular-Feldkonfiguration je Kontext/Capability; Capability editoptionformconfig. |
| `mod_booking\external\set_checked_booking_instance` | `classes/external/set_checked_booking_instance.php` | WS | 3 | C | P3 | Helper fuer local_urise zum Markieren konfigurierter Booking-Instanzen; No-op ohne urise. |
| `mod_booking\external\save_measurement` | `classes/external/save_measurement.php` | WS | 3 | B | P3 | Speichert Notiz zu Performance-Messpunkt; Capability editperformance. |
| `mod_booking\external\get_option_field_config` | `classes/external/get_option_field_config.php` | WS | 3 | C | P3 | Liest Optionformular-Feldkonfiguration je Kontext via optionformconfig_info. |
| `mod_booking\external\instancetemplate` | `classes/external/instancetemplate.php` | WS | 3 | C | P3 | Liest ein Booking-Instanz-Template als JSON; Capability has_capability_anywhere. |
| `mod_booking\external\search_users` | `classes/external/search_users.php` | WS | 3 | B | P3 | User-Autocomplete-Suche via booking::load_users; prueft has_capability_anywhere. |
| `mod_booking\external\search_teachers` | `classes/external/search_teachers.php` | WS | 3 | C | P3 | Teacher-Autocomplete-Suche via booking::load_teachers_for_webservice. |
| `mod_booking\external\search_templates` | `classes/external/search_templates.php` | WS | 3 | C | P3 | Template-Kurs-Suche via connectedcourse::return_tagged_template_courses. |

## S12 — events

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\event\bookinganswer_presencechanged` | `classes/event/bookinganswer_presencechanged.php` | Event | 5 | B | - | Event: Praesenzstatus einer Buchungsantwort geaendert (alt/neu in other); kann Zertifikat ausloesen. |
| `mod_booking\event\bookinganswer_cancelled` | `classes/event/bookinganswer_cancelled.php` | Event | 4 | B | - | Event: Buchungsantwort eines Users storniert; liefert kontextabhaengige Beschreibung (self/fremd). |
| `mod_booking\event\bookinganswer_notesedited` | `classes/event/bookinganswer_notesedited.php` | Event | 5 | B | - | Event: Notizen zu einer Buchungsantwort bearbeitet; mit validate_data. |
| `mod_booking\event\bookinganswer_slotbooked` | `classes/event/bookinganswer_slotbooked.php` | Event (Slotbooking) | 5 | B | - | Event: einzelner Slot gebucht; mit validate_data. |
| `mod_booking\event\bookinganswer_slotcancelled` | `classes/event/bookinganswer_slotcancelled.php` | Event (Slotbooking) | 5 | B | - | Event: einzelner Slot storniert; mit validate_data. |
| `mod_booking\event\bookinganswercustomformconditions_deleted` | `classes/event/bookinganswercustomformconditions_deleted.php` | Event | 4 | B | - | Event: Custom-Form-Bedingungen einer Buchungsantwort geloescht. |
| `mod_booking\event\bookingoption_booked` | `classes/event/bookingoption_booked.php` | Event | 5 | B | - | Event: Buchungsoption gebucht; mit validate_data. |
| `mod_booking\event\bookingoptionwaitinglist_booked` | `classes/event/bookingoptionwaitinglist_booked.php` | Event | 5 | B | - | Event: User auf Warteliste gebucht; mit validate_data. |
| `mod_booking\event\bookinginstance_updated` | `classes/event/bookinginstance_updated.php` | Event | 4 | B | - | Event: Booking-Aktivitaetsinstanz aktualisiert. |
| `mod_booking\event\bookingoption_uncompleted` | `classes/event/bookingoption_uncompleted.php` | Event | 5 | B | - | Event: Abschluss einer Buchungsoption zurueckgenommen; mit validate_data. |
| `mod_booking\event\bookingoption_bookedviaautoenrol` | `classes/event/bookingoption_bookedviaautoenrol.php` | Event | 5 | B | - | Event: Buchung via Auto-Enrolment ausgeloest; mit validate_data. |
| `mod_booking\event\bookinganswer_waitingforconfirmation` | `classes/event/bookinganswer_waitingforconfirmation.php` | Event | 5 | B | - | Event: Buchung wartet auf Bestaetigung; mit validate_data. |
| `mod_booking\event\bookingoption_completed` | `classes/event/bookingoption_completed.php` | Event | 5 | B | - | Event: Buchungsoption fuer User abgeschlossen; loest Aktivitaetsabschluss/Zertifikat aus. |
| `mod_booking\event\certificate_issued` | `classes/event/certificate_issued.php` | Event | 5 | B | - | Event: Zertifikat fuer User ausgestellt; mit validate_data. |
| `mod_booking\event\bookinganswer_confirmed` | `classes/event/bookinganswer_confirmed.php` | Event | 5 | B | - | Event: Buchungsantwort bestaetigt; Pflichtfeldpruefung via validate_data. |
| `mod_booking\event\bookinganswer_denied` | `classes/event/bookinganswer_denied.php` | Event | 5 | B | - | Event: Buchungsantrag abgelehnt; mit validate_data. |
| `mod_booking\event\records_imported` | `classes/event/records_imported.php` | Event | 4 | B | - | Event: Datensaetze (CSV) importiert. |
| `mod_booking\event\teacher_added` | `classes/event/teacher_added.php` | Event | 4 | B | - | Event: Lehrkraft einer Buchungsoption hinzugefuegt. |
| `mod_booking\event\teacher_removed` | `classes/event/teacher_removed.php` | Event | 4 | B | - | Event: Lehrkraft von Buchungsoption entfernt. |
| `mod_booking\event\bookinganswer_movedupfromwaitinglist` | `classes/event/bookinganswer_movedupfromwaitinglist.php` | Event | 5 | B | - | Event: User von Warteliste nachgerueckt; mit validate_data. |
| `mod_booking\event\optiondates_teacher_added` | `classes/event/optiondates_teacher_added.php` | Event | 4 | B | - | Event: Lehrkraft zu Optionsterminen hinzugefuegt (Teacher-Journal). |
| `mod_booking\event\optiondates_teacher_deleted` | `classes/event/optiondates_teacher_deleted.php` | Event | 4 | B | - | Event: Lehrkraft aus Optionsterminen entfernt. |
| `mod_booking\event\bookingoptiondate_deleted` | `classes/event/bookingoptiondate_deleted.php` | Event | 4 | B | - | Event: Optionstermin geloescht. |
| `mod_booking\event\bookingoption_created` | `classes/event/bookingoption_created.php` | Event | 4 | B | - | Event: neue Buchungsoption angelegt. |
| `mod_booking\event\bookingoptiondate_created` | `classes/event/bookingoptiondate_created.php` | Event | 4 | B | - | Event: neuer Optionstermin (Session) angelegt. |
| `mod_booking\event\bookingoption_freetobookagain` | `classes/event/bookingoption_freetobookagain.php` | Event | 4 | B | - | Event: Option wieder buchbar (Platz frei geworden). |
| `mod_booking\event\rest_script_failed` | `classes/event/rest_script_failed.php` | Event (Diagnose) | 4 | B | - | Event: externes REST-Skript fehlgeschlagen. |
| `mod_booking\event\bookingoption_cancelled` | `classes/event/bookingoption_cancelled.php` | Event | 4 | B | - | Event: gesamte Buchungsoption storniert (alle User). |
| `mod_booking\event\bookingoption_deleted` | `classes/event/bookingoption_deleted.php` | Event | 4 | B | - | Event: Buchungsoption geloescht. |
| `mod_booking\event\booking_afteractionsfailed` | `classes/event/booking_afteractionsfailed.php` | Event (Fehler) | 4 | B | - | Event: After-Actions nach Buchung fehlgeschlagen (Diagnose). |
| `mod_booking\event\booking_failed` | `classes/event/booking_failed.php` | Event (Fehler) | 4 | B | - | Event: Buchungsvorgang fehlgeschlagen (Diagnose). |
| `mod_booking\event\booking_rulesexecutionfailed` | `classes/event/booking_rulesexecutionfailes.php` | Event (Fehler) | 4 | B | - | Event: Ausfuehrung einer Booking-Rule fehlgeschlagen (Diagnose). |
| `mod_booking\event\rest_script_success` | `classes/event/rest_script_success.php` | Event (Diagnose) | 4 | B | - | Event: externes REST-Skript erfolgreich. |
| `mod_booking\event\custom_bulk_message_sent` | `classes/event/custom_bulk_message_sent.php` | Event | 3 | B | - | Event: Bulk-Nachricht an mehrere Empfaenger versendet. |
| `mod_booking\event\custom_field_changed` | `classes/event/custom_field_changed.php` | Event | 4 | B | - | Event: Custom-Field einer Option geaendert; triggert Kalender-Update im Observer. |
| `mod_booking\event\booking_debug` | `classes/event/booking_debug.php` | Event (Debug) | 3 | B | - | Debug-Logging-Event, nur bei bookingdebugmode getriggert; kein validate_data. |
| `mod_booking\event\report_viewed` | `classes/event/report_viewed.php` | Event | 4 | B | - | Event: Buchungs-Report angesehen. |
| `mod_booking\event\pricecategory_changed` | `classes/event/pricecategory_changed.php` | Event | 4 | B | - | Event: Preiskategorie-Identifier geaendert; Observer schreibt Preise um. |
| `mod_booking\event\reminder1_sent` | `classes/event/reminder1_sent.php` | Event | 3 | B | - | Event: erste Teilnehmer-Erinnerung versendet. |
| `mod_booking\event\reminder2_sent` | `classes/event/reminder2_sent.php` | Event | 3 | B | - | Event: zweite Teilnehmer-Erinnerung versendet. |
| `mod_booking\event\enrollink_triggered` | `classes/event/enrollink_triggered.php` | Event | 3 | B | - | Event: Enrolment-Link ausgeloest. |
| `mod_booking\event\reminder_teacher_sent` | `classes/event/reminder_teacher_sent.php` | Event | 3 | B | - | Event: Erinnerung an Lehrkraft versendet. |
| `mod_booking\event\course_module_viewed` | `classes/event/course_module_viewed.php` | Event (Core-Subclass) | 1 | B | - | Event: Booking-Kursmodul angesehen; erweitert core course_module_viewed, ueberschreibt nur init. |
| `mod_booking_observer` | `classes/observer.php` | Observer/Dispatcher | 30 | C | P1 | Zentraler Event-Observer mit 30 statischen Callbacks; reagiert auf eigene + fremde Events und stoesst Cache-Invalidierung, Kalenderpflege, Rules-Engine, Zertifikate, Enrolment-Sync an. |
| `mod_booking\event\message_sent` | `classes/event/message_sent.php` | Event (HTML-Desc) | 4 | C | P2 | Event ueber versendete System-Mail; baut Collapse-HTML und Nachrichtentyp-Label in get_description. |
| `mod_booking\event\custom_message_sent` | `classes/event/custom_message_sent.php` | Event (HTML-Desc) | 4 | C | P2 | Event ueber versendete benutzerdefinierte Nachricht; analog message_sent mit eigener msgparam-Transformation. |
| `mod_booking\event\bookinganswer_slotmoved` | `classes/event/bookinganswer_slotmoved.php` | Event (Slotbooking) | 6 | B | P3 | Event fuer verschobene Buchungs-Slots; normalisiert/dedupliziert Slotlisten und formatiert sie fuer die Logbeschreibung. |
| `mod_booking\event\bookingoption_updated` | `classes/event/bookingoption_updated.php` | Event (Renderer-gestuetzt) | 6 | B | P3 | Event fuer Optionsaenderung; rendert Aenderungs-Diff via bookingoption_changes-Output und normalisiert other-Payload. |

## S13 — tasks

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\task\task_adhoc_reset_optiondates_for_semester` | `classes/task/task_adhoc_reset_optiondates_for_semester.php` | Task | 2 | A | - | Adhoc-Adapter: delegiert Semesterwechsel an dates_handler::change_semester und purged Caches. |
| `mod_booking\task\book_all_students_task` | `classes/task/book_all_students_task.php` | Task | 2 | A | - | Adhoc-Adapter: Bulk-Buchung aller Studenten via book_all_students::execute(); setzt PAGE-Context. |
| `mod_booking\task\cleanup_invalid_scheduled_mails` | `classes/task/cleanup_invalid_scheduled_mails.php` | Task | 2 | A | - | Scheduled-Adapter: delegiert an scheduledmails::cleanup_invalid_tasks_in_context(1). |
| `mod_booking\task\process_source_membership_adhoc` | `classes/task/process_source_membership_adhoc.php` | Task | 2 | A | - | Adhoc-Adapter: delegiert Cohort/Group-Membership-Sync an booking_enrolment::process_source_membership(). |
| `mod_booking\task\confirm_bookinganswer_by_rule_adhoc` | `classes/task/confirm_bookinganswer_by_rule_adhoc.php` | Task | 2 | D | P1 | Adhoc-Rule-Task: bestätigt Wartelisten-Buchungsantworten (preisfrei via user_submit_response, sonst write_user_answer_to_db inkl. Exklusiv-Modus). |
| `mod_booking\task\send_mail_by_rule_adhoc` | `classes/task/send_mail_by_rule_adhoc.php` | Task | 2 | D | P1 | Adhoc-Rule-Task: versendet Rule-Mail nach Reload+JSON-Vergleich+Re-Validate; Repeat re-triggert Rule. |
| `mod_booking\task\send_reminder_mails` | `classes/task/send_reminder_mails.php` | Task | 5 | C | P2 | Scheduled-Task: versendet Erinnerungen (Teilnehmer/Session/Teacher) anhand daystonotify-Flags und triggert reminder*_sent-Events. |
| `mod_booking\task\delete_conditions_from_bookinganswer_by_rule_adhoc` | `classes/task/delete_conditions_from_bookinganswer_by_rule_adhoc.php` | Task | 2 | C | P2 | Adhoc-Rule-Task: entfernt condition_customform aus booking_answers.json wenn Lösch-Flag gesetzt; triggert Event. |
| `mod_booking\task\send_notification_mails` | `classes/task/send_notification_mails.php` | Task | 2 | C | P2 | Scheduled-Task: benachrichtigt Notify-Me-Liste über frei gewordene Optionen, entfernt abgelaufene Einträge. |
| `mod_booking\task\finalize_template_course` | `classes/task/finalize_template_course.php` | Task | 2 | C | P2 | Adhoc-Task: entfernt Template-Tags vom kopierten Kurs und re-enrolt User/Kontakte/Teacher; reschedult via Exception solange Copy läuft. |
| `mod_booking\task\purge_campaign_caches` | `classes/task/purge_campaign_caches.php` | Task | 2 | C | P2 | Adhoc-Task: purged Kampagnen-Caches und triggert bei Kapazitätsfreigabe sync_waiting_list + freetobookagain für alle limitierten Optionen. |
| `mod_booking\task\send_confirmation_mails` | `classes/task/send_confirmation_mails.php` | Task | 2 | C | P2 | Adhoc-Task: versendet Bestätigungsmail via email_to_user inkl. ICS-Attachment-Cleanup; triggert message_sent. |
| `mod_booking\task\enrol_bookedusers_tocourse` | `classes/task/enrol_bookedusers_tocourse.php` | Task | 2 | C | P2 | Scheduled-Task: enrolt gebuchte User in Kurse gestarteter Optionen, setzt enrolmentstatus. |
| `mod_booking\task\remove_activity_completion` | `classes/task/remove_activity_completion.php` | Task | 2 | C | P2 | Scheduled-Task: setzt abgeschlossene Antworten älter als removeafterminutes auf incomplete und aktualisiert completion_info. |
| `mod_booking\task\recalculate_prices` | `classes/task/recalculate_prices.php` | Task | 2 | B | P3 | Adhoc-Task: rechnet Preise je Option/Preiskategorie per Formel neu (price::calculate+add_price). |
| `mod_booking\task\send_completion_mails` | `classes/task/send_completion_mails.php` | Task | 2 | B | P3 | Adhoc-Mail-Adapter: sendet Completion-Mail via message_controller (Legacy-gated). |
| `mod_booking\task\clean_booking_db` | `classes/task/clean_booking_db.php` | Task | 2 | B | P3 | Scheduled-Cleanup: löscht verwaiste optiondates_teachers/teachers-Records via Subselects, purged Teacher-Cache. |
| `mod_booking\task\check_answers` | `classes/task/check_answers.php` | Task | 2 | A | P3 | Adhoc-Adapter: bei aktiven Unenrol-Settings checkanswers::process_booking_option(). |
| `mod_booking\task\assign_competency` | `classes/task/assign_competency.php` | Task | 2 | A | P3 | Adhoc-Adapter: validiert cmid/optionid/userid und delegiert an competencies::assign_competencies(). |

## S14 — slotbooking

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\local\slotbooking\slot_change_policy` | `classes/local/slotbooking/slot_change_policy.php` | Condition | 5 | A | - | Single Source of Truth fuer die relative Move/Cancel-Deadline (signierter Minuten-Offset je Slot, Aufloesung Option->Instanz->Plugin) und Partition actionable/locked. |
| `mod_booking\local\slotbooking\target_price_policy` | `classes/local/slotbooking/target_price_policy.php` | Condition | 3 | B | - | Policy fuer Self-Rebooking-Zielslots: V1-Filter auf preisgleiche Zielslots und Berechnung des Move-Preisdeltas (Summe neu - Summe alt). |
| `mod_booking\local\slotbooking\slot_answer` | `classes/local/slotbooking/slot_answer.php` | DTO | 2 | A | - | Adapter zum Lesen/Schreiben der Slot-Daten unter Key slot im booking_answers.json. |
| `mod_booking\local\slotbooking\slot_event_placeholders` | `classes/local/slotbooking/slot_event_placeholders.php` | Renderer | 1 | A | - | Gemeinsamer Renderer fuer Booking-Rule-Placeholders der Slot-Events: formatiert Slot-Listen aus dem Event-Payload. |
| `mod_booking\local\slotbooking\slot_feature` | `classes/local/slotbooking/slot_feature.php` | Condition | 1 | A | - | Single Source of Truth, ob Slot-Buchung verfuegbar ist (PRO + Admin-Toggle slotbookingactive, default-on). |
| `mod_booking\local\slotbooking\slot_availability` | `classes/local/slotbooking/slot_availability.php` | Service | 28 | D | P1 | Generiert virtuelle Slots aus der Slot-Konfiguration und beantwortet alle Verfuegbarkeitsfragen (Kapazitaet, Lehrkraefte, Entity-Konflikt, Teilnehmer-Overlap). De-facto God-Service des Subsystems. |
| `mod_booking\local\slotbooking\slot_mover` | `classes/local/slotbooking/slot_mover.php` | Service | 14 | D | P1 | Einziger Move-Kern fuer Verschieben/Stornieren gebuchter Slots, geteilt von Manager, Self-Service und Checkout-Commit; owns Validierung, JSON-Persistenz, Events und Notifications. |
| `mod_booking\local\slotbooking\slot_update_service` | `classes/local/slotbooking/slot_update_service.php` | Service | 7 | C | P2 | Form-unabhaengige Commit-/Routing-Engine des Update-Booking-Flows: berechnet Netto-Preisdelta und routet direct/refund/cart/cancel; plan() lesend, apply() schreibend. |
| `mod_booking\local\slotbooking\slot_rules` | `classes/local/slotbooking/slot_rules.php` | Service | 11 | C | P2 | Liest Slot-Regeln (Schliess- + Preisregeln) aus DB/MUC/Request-Cache und wendet sie auf generierte Slots bzw. Slot-Basispreise an. |
| `mod_booking\local\slotbooking\slot_dto` | `classes/local/slotbooking/slot_dto.php` | DTO | 7 | C | P2 | Kanonischer Builder der Slot-Datenstrukturen fuer Picker, Report und Move-Flow; buendelt Label-/Preis-Formatierung an einer Stelle. |
| `mod_booking\local\slotbooking\slot_move_store` | `classes/local/slotbooking/slot_move_store.php` | DTO | 9 | B | P3 | Repository/State-Maschine fuer preis-differente Umbuchungen (booking_slot_moves): PENDING-Hold waehrend Checkout, COMMITTED nach Kauf, CANCELLED bei Abbruch/Ablauf. |
| `mod_booking\local\slotbooking\slot_rule_manager` | `classes/local/slotbooking/slot_rule_manager.php` | DTO | 6 | B | P3 | Schreibseite der Slot-Regeln (CRUD fuer booking_slot_rule/booking_slot_rule_price) inkl. konsistenter Cache-Invalidierung. |
| `mod_booking\local\slotbooking\slot_price` | `classes/local/slotbooking/slot_price.php` | Service | 3 | B | P3 | Berechnet Slot-Preise aus Standard-Optionspreisen plus Slot-Preisregeln, mit lasttragendem Fallback wenn mod_booking\price keine Preiszeile aufloest. |

## S15 — wizard_ai

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\local\wizard\options\skills\list_option_properties_skill` | `classes/local/wizard/options/skills/list_option_properties_skill.php` | Skill | 7 | B | - | Read-only Skill mod_booking.list_option_properties: unterstuetzte Option-Eigenschaften/Scope auflisten (Selbstbeschreibung fuer den Agenten). |
| `mod_booking\local\wizard\options\skills\create_selflearning_option_skill` | `classes/local/wizard/options/skills/create_selflearning_option_skill.php` | Skill | 7 | B | - | Skill mod_booking.create_selflearning_option (extends create_option_skill): Self-Learning-Schema/Trigger und Normalisierung. |
| `mod_booking\local\wizard\services\mutation\option_mutation_service` | `classes/local/wizard/services/mutation/option_mutation_service.php` | Service | 7 | A | - | Application-Service: validate/create/update/bulk_update Optionen ueber booking_task_support, Ergebnis auf mutation_result_dto gemappt (DTO-Pfad spiegelt Task-Pfad). |
| `mod_booking\local\wizard\skill_provider` | `classes/local/wizard/skill_provider.php` | Service | 6 | A | - | Entrypoint: implementiert skill_provider_interface + input_normalizer_provider; liefert Skills (Discovery+Sort), Prompt-Packs, Diagnostics, Normalizer. |
| `mod_booking\local\wizard\booking_option_preview_renderer` | `classes/local/wizard/booking_option_preview_renderer.php` | Renderer | 1 | A | - | Serverseitiger Preview-Renderer: rendert Optionen als Karten (view/CARDS); cmid aus Option-Settings statt WS-Kontext. |
| `mod_booking\local\wizard\dto\create_option_input_dto` | `classes/local/wizard/dto/create_option_input_dto.php` | DTO | 4 | A | - | Value Object fuer create_option-Input; validiert Pflichtfeld text in from_array. |
| `mod_booking\local\wizard\dto\create_entity_input_dto` | `classes/local/wizard/dto/create_entity_input_dto.php` | DTO | 4 | A | - | Value Object fuer create_entity-Input; validiert Pflichtfeld name in from_array. |
| `mod_booking\local\wizard\dto\bulk_update_options_input_dto` | `classes/local/wizard/dto/bulk_update_options_input_dto.php` | DTO | 4 | A | - | Value Object fuer bulk_update_options-Input (from_array/to_array/get), keine Validierung. |
| `mod_booking\local\wizard\dto\update_option_input_dto` | `classes/local/wizard/dto/update_option_input_dto.php` | DTO | 4 | A | - | Value Object fuer update_option-Input (from_array/to_array/get), keine Validierung. |
| `mod_booking\local\wizard\services\lookup\option_lookup_service` | `classes/local/wizard/services/lookup/option_lookup_service.php` | Service | 2 | A | - | Read-only Application-Service: search_options + resolve_single_option ueber booking_task_support. |
| `mod_booking\local\wizard\booking\booking_readiness_provider` | `classes/local/wizard/booking/booking_readiness_provider.php` | Service | 1 | B | - | Duck-typed Provider fuer Engine-Readiness-Panel: liefert num_options/num_booked einer Booking-Instanz. |
| `mod_booking\local\interfaces\bookingextension\confirmbooking_interface` | `classes/local/interfaces/bookingextension/confirmbooking_interface.php` | Interface | 3 | B | - | (Orphan-Nachtrag) |
| `mod_booking\local\wizard\booking\provider_skill_input_normalizer` | `classes/local/wizard/booking/provider_skill_input_normalizer.php` | Service | 2 | A | - | Adapter: implementiert skill_input_normalizer_interface, delegiert an slot_booking_normalizer. |
| `mod_booking\local\wizard\booking\booking_skill_provider` | `classes/local/wizard/booking/booking_skill_provider.php` | Service | 0 | B | - | Leerer Deprecated-Kompatibilitaets-Wrapper (extends booking_skill_support) fuer Legacy-Referenzen. |
| `mod_booking\local\wizard\booking\booking_skill_support` | `classes/local/wizard/booking/booking_skill_support.php` | Service | 85 | E | P0 | God-Klasse: Skill-Discovery, Domaenen-Resolver (User/Course/Cohort/Competency/Option/Price), Datums-/Preis-/Sichtbarkeits-Normalisierung, Permission-Validierung, Booking-Ausfuehrung, Thread-Memory, Lokalisierung/Output. |
| `mod_booking\local\wizard\booking\booking_skill_mutation_execute_service` | `classes/local/wizard/booking/booking_skill_mutation_execute_service.php` | Service | 12 | E | P0 | Zentraler Mutations-Executor fuer create/update/update_trainer/bulk_update: baut form-style data, ruft booking_option::update, bucht Nutzer, Header-Image-Token, Postcondition-Verifikation. |
| `mod_booking\local\wizard\options\skills\create_option_skill` | `classes/local/wizard/options/skills/create_option_skill.php` | Skill | 30 | D | P1 | Skill mod_booking.create_option: Input-Normalisierung (Aliase/Optiondates/Slot/Self-Learning), Schema/Trigger, typ-spezifische Pflichtfeld-Preflight, Execute via Mutation-Service. Basisklasse fuer Slot/Self-Learning-Subskills. |
| `mod_booking\local\wizard\options\skills\booking_skill_base` | `classes/local/wizard/options/skills/booking_skill_base.php` | Skill | 30 | D | P1 | Abstrakte Basisklasse aller Booking-Skills (extends base_skill): geteilter Support, Prompt-Meta, Schema-Anreicherung, Mutation-Struktur-Validierung, Preview, Capability/Scoping, Lokalisierung. |
| `mod_booking\local\wizard\options\skills\diagnose_booking_issue_skill` | `classes/local/wizard/options/skills/diagnose_booking_issue_skill.php` | Skill | 21 | D | P1 | Read-only Diagnose, warum Buchung blockiert: loest Nutzer/Option, prueft bo_info-Bedingungen, baut erklaerende Reason-Lines. |
| `mod_booking\local\wizard\options\skills\diagnose_user_booking_skill` | `classes/local/wizard/options/skills/diagnose_user_booking_skill.php` | Skill | 28 | D | P1 | Read-only Diagnose einer Nutzer-Buchung inkl. Status, Nachrichten und tool_certificate-Daten; Option- oder nutzerweiter Report. |
| `mod_booking\local\wizard\options\skills\diagnose_cancellation_issue_skill` | `classes/local/wizard/options/skills/diagnose_cancellation_issue_skill.php` | Skill | 19 | D | P2 | Read-only Diagnose, warum Stornierung blockiert: hoechste blockierende Bedingung + Reason-Lines. |
| `mod_booking\local\wizard\options\skills\book_users_skill` | `classes/local/wizard/options/skills/book_users_skill.php` | Skill | 17 | C | P2 | Skill mod_booking.book_users: Nutzer in Option einbuchen (auch letzte-Option-Referenz), Bedingungs-Blocker + Bestaetigungs-Issues im Preflight. |
| `mod_booking\local\wizard\options\skills\get_option_details_skill` | `classes/local/wizard/options/skills/get_option_details_skill.php` | Skill | 19 | C | P2 | Read-only Skill mod_booking.get_option_details: Standard-/Custom-Felder + Capability-Snapshot einer/mehrerer Optionen, auch im System-Kontext. |
| `mod_booking\local\wizard\booking\support\booking_rules_agent_service` | `classes/local/wizard/booking/support/booking_rules_agent_service.php` | Service | 13 | C | P2 | Support fuer Rule-Skills: Templates listen/aufloesen (Fuzzy), Rules je Kontext listen, Rule aus Template erstellen/aktualisieren ueber rules_info-Pipeline. |
| `mod_booking\local\wizard\options\skills\configure_booking_instance_skill` | `classes/local/wizard/options/skills/configure_booking_instance_skill.php` | Skill | 16 | C | P2 | Skill mod_booking.configure_booking_instance: Instanz-Felder ueber CONFIGURABLE_FIELDS-Whitelist konfigurieren (changes-Envelope, Typ-Cast/Validierung). |
| `mod_booking\local\wizard\booking\support\booking_mutation_validation` | `classes/local/wizard/booking/support/booking_mutation_validation.php` | Condition | 1 | D | P2 | Geteilte Validierung mutierender Tasks: eine statische validate_common mit allen Feld-/Restriction-/Datums-/Preis-/Customform-Checks. |
| `mod_booking\local\wizard\options\skills\option_schema_definition` | `classes/local/wizard/options/skills/option_schema_definition.php` | DTO | 1 | C | P3 | Schema-Provider: liefert geteilte Option-Properties (common_properties) fuer create/update/bulk-Schemata. |
| `mod_booking\local\wizard\options\skills\update_option_skill` | `classes/local/wizard/options/skills/update_option_skill.php` | Skill | 13 | C | P3 | Skill mod_booking.update_option: Option per Query aufloesen + Felder aktualisieren, Preflight/Verify, Execute via Mutation-Service. |
| `mod_booking\local\wizard\options\skills\create_rule_from_template_skill` | `classes/local/wizard/options/skills/create_rule_from_template_skill.php` | Skill | 10 | C | P3 | Skill mod_booking.create_rule_from_template: Template aufloesen (inkl. Auto-Select-Bestaetigung) und Rule erstellen via booking_rules_agent_service. |
| `mod_booking\local\wizard\options\skills\bulk_update_options_skill` | `classes/local/wizard/options/skills/bulk_update_options_skill.php` | Skill | 12 | C | P3 | Skill mod_booking.bulk_update_options: mehrere Optionen per Auswahl aktualisieren; nahezu identisch zu update_option (geteilter Persist-Kern). |
| `mod_booking\local\wizard\options\skills\analyze_rules_skill` | `classes/local/wizard/options/skills/analyze_rules_skill.php` | Skill | 7 | B | P3 | Read-only Skill mod_booking.analyze_rules: Buchungsregeln im Kontext (und ggf. System) listen/analysieren ueber rules-Service. |
| `mod_booking\local\wizard\options\skills\create_slotbooking_option_skill` | `classes/local/wizard/options/skills/create_slotbooking_option_skill.php` | Skill | 12 | B | P3 | Skill mod_booking.create_slotbooking_option (extends create_option_skill): Slotbooking-spezifisches Schema/Trigger, Wochentage/Zeit-Normalisierung. |
| `mod_booking\local\wizard\options\skills\update_option_trainer_skill` | `classes/local/wizard/options/skills/update_option_trainer_skill.php` | Skill | 10 | B | P3 | Skill mod_booking.update_option_trainer: nur Trainer/Teacher einer Option aktualisieren (gefilterter Input), Execute via Mutation-Service. |
| `mod_booking\local\wizard\options\skills\search_options_skill` | `classes/local/wizard/options/skills/search_options_skill.php` | Skill | 9 | B | P3 | Read-only Skill mod_booking.search_options: Freitextsuche nach Buchungsoptionen, strukturierte Treffer + observation_full. |
| `mod_booking\local\wizard\options\skills\option_input_verification` | `classes/local/wizard/options/skills/option_input_verification.php` | Condition | 5 | B | P3 | Postcondition-Verifikation persistierter Optionswerte: verify_common_fields (Strings) + verify_common_fields_structured (Failure-Codes). |
| `mod_booking\local\wizard\options\skills\update_rule_from_template_skill` | `classes/local/wizard/options/skills/update_rule_from_template_skill.php` | Skill | 9 | B | P3 | Skill mod_booking.update_rule_from_template: bestehende kontext-lokale Rule aktualisieren, optional Template neu anwenden. |
| `mod_booking\local\wizard\options\skills\add_price_category_skill` | `classes/local/wizard/options/skills/add_price_category_skill.php` | Skill | 8 | B | P3 | Skill mod_booking.add_price_category: neue Preiskategorie anlegen; Preflight mit Bestaetigungs-Issues. |
| `mod_booking\local\wizard\booking\support\slot_booking_normalizer` | `classes/local/wizard/booking/support/slot_booking_normalizer.php` | Util | 8 | B | P3 | Domain-Normalizer: kanonisiert LLM-Input fuer create/update_option (Slotbooking-Defaults, Self-Learning kein-Limit->999999). |
| `mod_booking\local\wizard\services\mutation\entity_mutation_service` | `classes/local/wizard/services/mutation/entity_mutation_service.php` | Service | 3 | B | P3 | Application-Service fuer Entity-Erstellung mit Name/Shortname-Dedup gegen local_wb_entity; Schreibpfad aktuell unimplementiert. |

## S16 — forms

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\form\deleteruleform` | `classes/form/deleteruleform.php` | Form | 7 | B | - | Bestaetigungs-Form zum Loeschen einer Booking-Rule via rules_info::delete_rule. |
| `mod_booking\form\subbookingsdeleteform` | `classes/form/subbookingsdeleteform.php` | Form | 7 | B | - | Bestaetigungs-Form zum Loeschen eines Subbookings via subbookings_info. |
| `mod_booking\form\deletecampaignform` | `classes/form/deletecampaignform.php` | Form | 7 | B | - | Bestaetigungs-Form zum Loeschen einer Campaign via campaigns_info. |
| `mod_booking\form\actions\deleteactionsform` | `classes/form/actions/deleteactionsform.php` | Form | 7 | B | - | Bestaetigungs-Form zum Loeschen einer Action via actions_info::delete_action. |
| `mod_booking\form\subscribe_cohort_or_group_form` | `classes/form/subscribe_cohort_or_group_form.php` | Form | 2 | B | - | Klassische moodleform zur Cohort-/Gruppen-Subscription einer Option. |
| `mod_booking\form\deletecertificateconditionform` | `classes/form/deletecertificateconditionform.php` | Form | 7 | B | - | Bestaetigungs-Form zum Loeschen einer Certificate-Condition. |
| `mod_booking\form\subscribeusersactivity` | `classes/form/subscribeusersactivity.php` | Form | 2 | B | - | Klassische moodleform zur Auswahl einer Ziel-Option fuer User-Transfer. |
| `mod_booking\form\importoptions_form` | `classes/form/importoptions_form.php` | Form | 2 | B | - | Klassische moodleform fuer CSV-Upload (Optionsimport). |
| `mod_booking\form\teachers_instance_report_form` | `classes/form/teachers_instance_report_form.php` | Form | 2 | A | - | Klassische moodleform zur Teacher-Auswahl im Instanz-Report. |
| `mod_booking\form\teacher_performed_units_report_form` | `classes/form/teacher_performed_units_report_form.php` | Form | 2 | A | - | Klassische moodleform mit Datumsfilter fuer Teacher-Units-Report. |
| `mod_booking\form\instancetemplateadd_form` | `classes/form/instancetemplateadd_form.php` | Form | 1 | A | - | Minimale moodleform fuer Instanz-Template-Namen. |
| `mod_booking\form\teacherunavailability_form` | `classes/form/teacherunavailability_form.php` | Form | 22 | D | P2 | Slot-(Un)Verfuegbarkeit pro Teacher scope-abhaengig; direkte DB-CRUD in booking_teacher_unavailability mit Transaktion. |
| `mod_booking\form\condition\slotbooking_form` | `classes/form/condition/slotbooking_form.php` | Form | 22 | C | P2 | Basisklasse Slot-Picker-Prepage; rendert je slot_type/view-mode, validiert gegen slot_availability, speichert in slotbookingstore. |
| `mod_booking\form\editteachersforoptiondate_form` | `classes/form/editteachersforoptiondate_form.php` | Form | 8 | C | P2 | Teacher-Zuordnung + Substitutions-Deductions pro optiondate mit Events und Cache-Purge. |
| `mod_booking\form\slotteacherassignments_form` | `classes/form/slotteacherassignments_form.php` | Form | 17 | C | P2 | Student->Teacher(Examiner)-Zuordnung fuer Slotbooking; DB-Mapping booking_slot_student_teacher in Transaktion. |
| `mod_booking\form\condition\customform_form` | `classes/form/condition/customform_form.php` | Form | 9 | D | P2 | Rendert dynamische Custom-Felder der customform-Condition und schreibt Eingaben in customformstore-Cache. |
| `mod_booking\form\rulesform` | `classes/form/rulesform.php` | Form | 9 | C | P2 | Rule-Editor-Dynamic-Form; delegiert an rules_info, grosse typabhaengige Validierung + Template-Override. |
| `mod_booking\form\modal_send_custom_message` | `classes/form/modal_send_custom_message.php` | Form | 8 | C | P2 | Bulk-Custom-Message an gebuchte User via message_controller, mit Events und Datei-Anhang. |
| `mod_booking\form\option_form_bulk` | `classes/form/option_form_bulk.php` | Form | 11 | C | P2 | Bulk-Bearbeitung ausgewaehlter Optionsfelder ueber mehrere Optionen via booking_option::update. |
| `mod_booking\form\dynamicdeputyselect` | `classes/form/dynamicdeputyselect.php` | Form | 11 | D | P2 | Auswahl/Speicherung von Deputies in Custom-Profile-Field inkl. Supervisor-Rollen-Enrol. |
| `mod_booking\form\optiondates\modal_change_status` | `classes/form/optiondates/modal_change_status.php` | Form | 8 | C | P2 | Presence-Status pro optiondate/option-Scope aendern via optiondate_answer / booking_option::changepresencestatus. |
| `mod_booking\form\subbooking\additionalperson_form` | `classes/form/subbooking/additionalperson_form.php` | Form | 11 | C | P2 | Subbooking-Prepage-Form (zusaetzliche Personen); speichert in subbookingforms-Cache, rendert bookit-Button. |
| `mod_booking\form\optiondates\modal_change_notes` | `classes/form/optiondates/modal_change_notes.php` | Form | 8 | C | P2 | Notes pro optiondate/option-Scope aendern via optiondate_answer / booking_option::edit_notes. |
| `mod_booking\form\customfield` | `classes/form/customfield.php` | Form | 3 | D | P2 | Verwaltung der Booking-Customfields als Plugin-Config; CRUD direkt in get_data(). |
| `mod_booking\local\customform_prefill` | `classes/local/customform_prefill.php` | Util | 10 | B | P3 | Mappt prefill_*-URL-Parameter auf customformstore-Cache-Werte zur Vorbefuellung der Customform-Felder. |
| `mod_booking\form\dynamicsemestersform` | `classes/form/dynamicsemestersform.php` | Form | 8 | C | P3 | Repeat-Elements-Form mit direktem CRUD-Diff fuer booking_semesters. |
| `mod_booking\form\condition\slotupdate_form` | `classes/form/condition/slotupdate_form.php` | Form | 7 | B | P3 | Erbt slotbooking_form; Update-Booking (Move/Cancel/Change) via slot_update_service mit Zwei-Pass-Confirm. |
| `mod_booking\form\pricecategories_form` | `classes/form/pricecategories_form.php` | Form | 9 | B | P3 | Repeat-Elements-Form fuer Preiskategorien, Save via pricecategories_handler. |
| `mod_booking\form\dynamicholidaysform` | `classes/form/dynamicholidaysform.php` | Form | 8 | C | P3 | Repeat-Elements-Form mit direktem CRUD-Diff fuer booking_holidays. |
| `mod_booking\form\csvimport` | `classes/form/csvimport.php` | Form | 8 | B | P3 | Generische CSV-Import-Dynamic-Form mit Preview/Settings-Callback-Mechanik. |
| `mod_booking\form\option_form` | `classes/form/option_form.php` | Form | 9 | B | P3 | Duenne dynamic_form-Huelle fuer Buchungsoption; Felder/Save komplett via fields_info / booking_option::update. |
| `mod_booking\form\certificateconditionsform` | `classes/form/certificateconditionsform.php` | Form | 9 | B | P3 | Dynamic-Form fuer Certificate-Conditions; Filter/Logic/Action-Bloecke via *_info, validiert pro Sub-Element. |
| `mod_booking\form\modaloptiondateform` | `classes/form/modaloptiondateform.php` | Form | 9 | B | P3 | Repeat-Elements-Form fuer einzelne Custom-Optiondates (kein DB-Save, gibt Array zurueck). |
| `mod_booking\form\sync_rule_form` | `classes/form/sync_rule_form.php` | Form | 7 | B | P3 | Dynamic-Form zum Erstellen/Bearbeiten einer Sync-Enrolment-Rule, delegiert an booking_enrolment. |
| `mod_booking\form\slotrules_page_form` | `classes/form/slotrules_page_form.php` | Form | 3 | B | P3 | Klassische moodleform zum Erstellen/Bearbeiten von Slot-Rules (closed/price). |
| `mod_booking\form\dynamicoptiondateform` | `classes/form/dynamicoptiondateform.php` | Form | 8 | B | P3 | Dynamic-Form zur Erzeugung von Optiondate-Serien via dates_handler. |
| `mod_booking\form\dynamicchangesemesterform` | `classes/form/dynamicchangesemesterform.php` | Form | 8 | B | P3 | Dynamic-Form, startet Adhoc-Task zum Zuruecksetzen der Optiondates auf ein Semester. |
| `mod_booking\form\send_mail_to_teachers` | `classes/form/send_mail_to_teachers.php` | Form | 7 | B | P3 | Bulk-Mail an Teacher gewaehlter Optionen via message_controller. |
| `mod_booking\form\modal_editteacherdescription` | `classes/form/modal_editteacherdescription.php` | Form | 7 | B | P3 | Editor-Dynamic-Form fuer Teacher-Beschreibung (user.description) mit File-Editor. |
| `mod_booking\form\sync_rule_delete_form` | `classes/form/sync_rule_delete_form.php` | Form | 7 | B | P3 | Bestaetigungs-Form zum Loeschen einer Sync-Rule mit Delete-Mode, delegiert an booking_enrolment. |
| `mod_booking\form\sync_rule_activate_form` | `classes/form/sync_rule_activate_form.php` | Form | 7 | B | P3 | Bestaetigungs-Form zur Aktivierung einer Sync-Rule mit Impact-Anzeige, delegiert an booking_enrolment. |
| `mod_booking\form\condition\bookingpolicy_form` | `classes/form/condition/bookingpolicy_form.php` | Form | 7 | B | P3 | Prepage-Conditionform fuer Buchungspolicy-Zustimmung; speichert Checkbox im conditionforms-Cache. |
| `mod_booking\form\subbookingsform` | `classes/form/subbookingsform.php` | Form | 8 | B | P3 | Dynamic-Form fuer Subbookings; delegiert Felder/Save an subbookings_info. |
| `mod_booking\form\campaignsform` | `classes/form/campaignsform.php` | Form | 7 | B | P3 | Dynamic-Form fuer Campaigns; delegiert an campaigns_info, typabhaengige Validierung. |
| `mod_booking\form\modal_confirmcancel` | `classes/form/modal_confirmcancel.php` | Form | 7 | B | P3 | Dynamic-Form zum Stornieren/Entstornieren einer Buchungsoption via booking_option::cancelbookingoption. |
| `mod_booking\form\actions\actionsform` | `classes/form/actions/actionsform.php` | Form | 8 | B | P3 | Dynamic-Form fuer bo_actions; delegiert Felder/Speichern an actions_info, triggert booking_option updated-Event. |
| `mod_booking\form\confirmactivity` | `classes/form/confirmactivity.php` | Form | 2 | C | P3 | Klassische moodleform zur Auswahl Badge/Activity fuer Aktivitaetsbestaetigung. |

## S17 — reporting

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\reportbuilder\datasource\booking_options_datasource` | `classes/reportbuilder/datasource/booking_options_datasource.php` | Datasource | 6 | A | - | Schlanke Report-Builder-Datasource fuer Buchungsoptionen plus Course/Category-Entities ohne Conditions. |
| `mod_booking\reportbuilder\local\filters\profile_field_current_user` | `classes/reportbuilder/local/filters/profile_field_current_user.php` | Filter/Condition | 3 | A | - | Condition-Filter, der ein Profilfeld gegen $USER->id (current user) oder Freitext vergleicht; Supervisor-Audience. |
| `mod_booking\reportbuilder\local\filters\timestamp_years_past` | `classes/reportbuilder/local/filters/timestamp_years_past.php` | Filter | 4 | A | - | Custom-Report-Filter: Timestamp innerhalb der letzten X Jahre (COALESCE+BETWEEN, Clock via DI). |
| `mod_booking\signinsheet\signin_pdf` | `classes/signinsheet/signin_pdf.php` | PDF-Adapter | 3 | A | - | Duenner TCPDF-Adapter fuer Sign-in-Sheet: Page-Break-Helper und Fusszeilen-Logo. |
| `mod_booking\checklist\checklist_pdf` | `classes/checklist/checklist_pdf.php` | PDF-Adapter | 3 | A | - | Duenner TCPDF-Adapter fuer Checklist-PDF: Fusszeilen-Logo, leerer custom_page_header. |
| `mod_booking\local\performance\actions\execution_times` | `classes/local/performance/actions/execution_times.php` | Action | 7 | A | - | No-op-Action, die nur die Anzahl der Wiederholungen (Mess-Zyklen) traegt. |
| `mod_booking\local\performance\actions\purge_cache_action_before` | `classes/local/performance/actions/purge_cache_action_before.php` | Action | 6 | A | - | Performance-Action: purge_all_caches einmal vor allen Mess-Zyklen (BEFORE_ALL). |
| `mod_booking\local\performance\actions\purge_cache_action_inbetween` | `classes/local/performance/actions/purge_cache_action_inbetween.php` | Action | 6 | A | - | Performance-Action: purge_all_caches vor jedem Mess-Zyklus (BEFORE_EACH). |
| `mod_booking\local\performance\actions\action_registry` | `classes/local/performance/actions/action_registry.php` | Registry | 4 | A | - | Feste Registry der verfuegbaren Performance-Actions; liefert Instanzen und Filter nach execution_point. |
| `mod_booking\reportbuilder\local\filters\cohort_selector` | `classes/reportbuilder/local/filters/cohort_selector.php` | Filter/Condition | 3 | A | - | Condition-Filter, der eine Cohort-Auswahl gegen cohort_members.cohortid matcht. |
| `mod_booking\local\performance\actions\action_executor` | `classes/local/performance/actions/action_executor.php` | Service | 2 | A | - | Fuehrt fuer einen execution_point alle aktivierten Performance-Actions aus. |
| `mod_booking\local\performance\actions\performance_action_interface` | `classes/local/performance/actions/performance_action_interface.php` | Interface | 6 | A | - | Formaler Kontrakt fuer Performance-Actions (id/label/execution_point/configure/execute/export_for_template). |
| `mod_booking\local\checkanswers\checks\cmvisibility` | `classes/local/checkanswers/checks/cmvisibility.php` | Check | 2 | A | - | Check: prueft, ob das Booking-CM fuer den User sichtbar/zugaenglich ist. |
| `mod_booking\local\checkanswers\checks\enrolledincourse` | `classes/local/checkanswers/checks/enrolledincourse.php` | Check | 2 | A | - | Check: prueft, ob der User noch im Kurs eingeschrieben ist (is_enrolled). |
| `mod_booking\local\checkanswers\actions\deleteanswer` | `classes/local/checkanswers/actions/deleteanswer.php` | Action | 2 | A | - | Check-Action: loescht eine ungueltige Buchungsantwort via booking_option::user_delete_response. |
| `mod_booking\local\performance\actions\execution_point` | `classes/local/performance/actions/execution_point.php` | Enum | 0 | A | - | Enum der Ausfuehrungs-Zeitpunkte (EXECUTION_TIMES/BEFORE_ALL/BEFORE_EACH). |
| `mod_booking\signinsheet\signinsheet_generator` | `classes/signinsheet/signinsheet_generator.php` | Renderer/Export | 20 | E | P0 | Monolithischer Generator fuer Anwesenheitslisten als PDF/Word mit zwei parallelen Render-Pipelines (HTML-Template und zellenweises TCPDF). |
| `mod_booking\local\performance\performance_measurer` | `classes/local/performance/performance_measurer.php` | Service/Singleton | 13 | C | P2 | Singleton-Mess-Engine mit statischem Zustand; misst µs-Intervalle und persistiert sie in booking_performance_measurements. |
| `mod_booking\checklist\checklist_generator` | `classes/checklist/checklist_generator.php` | Renderer/Export | 8 | B | P2 | Erzeugt eine konfigurierbare Vorbereitungs-Checkliste pro Buchungsoption als PDF via Platzhalter-Ersetzung. |
| `mod_booking\local\performance\performance_renderer` | `classes/local/performance/performance_renderer.php` | Renderer/Aggregator | 6 | B | P2 | Aggregiert Mess-Records zu Chart.js-Datasets und liefert Sidebar-Tabelle. |
| `mod_booking\local\checkanswers\checkanswers` | `classes/local/checkanswers/checkanswers.php` | Service/Orchestrator | 5 | B | P2 | Orchestriert Validierung von Buchungsantworten: erzeugt Adhoc-Tasks und fuehrt discoverte Checks/Actions pro Answer aus. |
| `mod_booking\local\performance\performance_facade` | `classes/local/performance/performance_facade.php` | Service/Facade | 5 | B | P2 | Statische Facade, die einen Shortcode-Mess-Lauf ueber N Zyklen mit Actions, format_text und Singleton-/Cache-Reset orchestriert. |
| `mod_booking\reportbuilder\local\entities\booking_answers` | `classes/reportbuilder/local/entities/booking_answers.php` | Entity | 4 | B | P3 | Report-Builder-Entity ueber {booking_answers} (User-Option-Pivot) mit Status-/Waitinglist-Callbacks und Year-Filtern. |
| `mod_booking\local\bookingstracker\bookingstracker_helper` | `classes/local/bookingstracker/bookingstracker_helper.php` | Helper/Renderer | 14 | B | P3 | Helper fuer die option-Spalte im report2-Tracker; haelt scope-spezifische moodle_url-Links und rendert das option-Template. |
| `mod_booking\reportbuilder\local\entities\booking_options` | `classes/reportbuilder/local/entities/booking_options.php` | Entity | 4 | B | P3 | Report-Builder-Entity ueber {booking_options}; merged eigene Spalten/Filter mit core custom_fields-Helper. |
| `mod_booking\reportbuilder\datasource\booking_answers_datasource` | `classes/reportbuilder/datasource/booking_answers_datasource.php` | DTO/Datasource | 6 | B | P3 | Report-Builder-Datasource fuer abgeschlossene Buchungen samt Option-Customfields und User-/Cohort-/Supervisor-Conditions. |
| `mod_booking\local\performance\table\measurements_table` | `classes/local/performance/table/measurements_table.php` | Table | 3 | B | P3 | wunderbyte_table fuer einzelne Messungen mit Edit/Delete-Collapsibles und µs->Datum-Formatierung. |
| `mod_booking\local\performance\table\performance_table` | `classes/local/performance/table/performance_table.php` | Table | 3 | B | P3 | wunderbyte_table fuer Mess-Laeufe pro Shortcode mit Modal, Delete-Aktion und Sidebar-Link. |

## S18 — import_export

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `(keine Klasse - Entwicklerdoku)` | `classes/import/README.md` | Util | 0 | E | - | Entwicklerdokumentation des generischen CSV-Importers (Spalten-/Settings-Definition, Rendering, UX-Feedback). Kein Code. |
| `(keine Klasse - Beispieldatei)` | `classes/importer/demo.csv` | Util | 0 | E | - | Beispiel-CSV-Datei fuer den Buchungsoptionen-Import. Kein Code. |
| `mod_booking\import\fileparser` | `classes/import/fileparser.php` | Service | 22 | C | P1 | Callback-generische CSV-Verarbeitung: laden via csv_import_reader, Header-/Zeilen-Validierung, Typ-Casting, Callback-Ausfuehrung pro Zeile, Ergebnis-/Fehleraggregation; plus Preview-Dry-Run mit Rollback-Transaktion und Observer-Suspendierung. |
| `mod_booking\importer\bookingoptionsimporter` | `classes/importer/bookingoptionsimporter.php` | Service | 8 | B | P2 | mod_booking-spezifische Importer-Fassade: definiert das Spaltenschema fuer Buchungsoptionen, konfiguriert csvsettings (Callback booking_option::update, injiziert cmid) und startet fileparser im Import- bzw. Preview-Modus; liefert AJAX-Formdaten und Template-Spalten. |
| `mod_booking\import\csvsettings` | `classes/import/csvsettings.php` | DTO | 14 | B | P3 | Konfigurations-DTO eines Import-Laufs (delimiter/enclosure/encoding/dateformat/callback/columnswithvalues) und Fabrik, die rohe Spalten-Arrays (assoziativ oder sequentiell) in csvcolumn-Objekte umwandelt. |
| `mod_booking\import\csvcolumn` | `classes/import/csvcolumn.php` | DTO | 5 | B | P3 | DTO einer einzelnen importierbaren CSV-Spalte (name, localizedname, mandatory, unique, format, type, defaultvalue, transform, importinstruction) mit optionaler transform-Closure. |

## S19 — certificates

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\local\certificate_conditions\certificate_conditions_interface` | `classes/local/certificate_conditions/certificate_conditions_interface.php` | Interface | 9 | A | - | Vertrag fuer Condition-Handler (Form, save, set_*, evaluate, save_items). |
| `mod_booking\local\certificate_conditions\conditions_info` | `classes/local/certificate_conditions/conditions_info.php` | Service | 3 | A | - | Discovery der Condition-Klassen (core_component) + Form-Selector mit Default-Handling. |
| `mod_booking\local\certificate_conditions\filter_interface` | `classes/local/certificate_conditions/filter_interface.php` | Interface | 9 | A | - | Vertrag fuer Filter-Handler (Form, save, set_*, evaluate, validate). |
| `mod_booking\local\certificate_conditions\action_interface` | `classes/local/certificate_conditions/action_interface.php` | Interface | 9 | A | - | Vertrag fuer Action-Handler (Form, save, set_*, execute_action, validate). |
| `mod_booking\local\certificateclass` | `classes/local/certificateclass.php` | Service | 8 | C | P2 | Stellt ueber tool_certificate ein PDF-Zertifikat aus (Daten aus Buchungsoption, Custom-Fields, Kompetenzen) und triggert certificate_issued; prueft Pflichtoptions-Erfuellung. |
| `mod_booking\local\certificate_conditions\certificate_conditions` | `classes/local/certificate_conditions/certificate_conditions.php` | Service | 12 | C | P2 | Zentrale CRUD-/Repository- und Orchestrierungsklasse fuer booking_cert_cond: speichern, loeschen, Form-Hydration, Caching, Evaluierung aktiver Bedingungen. |
| `mod_booking\local\certificate_conditions\conditions\bookingoption` | `classes/local/certificate_conditions/conditions/bookingoption.php` | Condition | 9 | C | P2 | Condition-Handler: prueft ob (genuegend) ausgewaehlte Buchungsoptionen vom User abgeschlossen wurden; Option-Auswahl via AJAX-Autocomplete. |
| `mod_booking\local\certificate_conditions\conditions\taggedoptions` | `classes/local/certificate_conditions/conditions/taggedoptions.php` | Condition | 9 | C | P2 | Condition-Variante fuer Self-Tagging der Buchungsoption an bestehende Bedingungen ueber das Option-Formular; Evaluierung wie bookingoption. |
| `mod_booking\local\certificate_conditions\actions\createcertificate` | `classes/local/certificate_conditions/actions/createcertificate.php` | Action | 10 | B | P3 | Einzige Action: stellt via certificateclass::issue_certificate ein Zertifikat aus, mit Idempotenz-Pruefung gegen Doppelausstellung. |
| `mod_booking\local\certificate_conditions\filters\userprofilefield` | `classes/local/certificate_conditions/filters/userprofilefield.php` | Filter | 9 | B | P3 | Einziger Filter: vergleicht ein User-Profilfeld mit konfiguriertem Wert (= oder contains) im Event-Kontext. |
| `mod_booking\local\certificate_conditions\option_conditions_info` | `classes/local/certificate_conditions/option_conditions_info.php` | Util | 5 | B | P3 | Helper fuer Anzeige/Tagging von Zertifikatsbedingungen im Booking-Option-Formular inkl. Roh-SQL-Lookups und Edit-Links. |
| `mod_booking\local\certificate_conditions\actions_info` | `classes/local/certificate_conditions/actions_info.php` | Service | 4 | B | P3 | Discovery der Action-Klassen (core_component) + Form-Selector + Idempotenz-Check gegen tool_certificate_issues. |
| `mod_booking\local\certificate_conditions\filters_info` | `classes/local/certificate_conditions/filters_info.php` | Service | 3 | B | P3 | Discovery der Filter-Klassen + Form-Selector inkl. norestriction-Option und optionalem Kompatibilitaets-Skip. |
| `README.md` | `classes/local/certificate_conditions/README.md` | Script | 0 | D | P3 | Entwickler-Doku des Drei-Saeulen-Frameworks; beschreibt aber veralteten Stand (logics/, logic_interface, abweichende Namespaces/Signaturen). |

## S20 — sync_enrolment

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\potential_subscriber_selector` | `classes/potential_subscriber_selector.php` | Form | 5 | B | - | Selektor fuer potenzielle Lehrkraefte (nicht bereits abonniert) bzw. forcesubscribed-Modus. |
| `mod_booking\local\competencies\competencies_handler` | `classes/local/competencies/competencies_handler.php` | Service | 4 | B | - | Lese-Cache fuer Nutzerkompetenzen (statisch + MUC), genutzt von Availability-Conditions; Shortname-Aufloesung. |
| `mod_booking\booking_user_selector_base` | `classes/booking_user_selector_base.php` | Form | 3 | B | - | Abstrakte Basis fuer Buchungs-User-Selektoren; haelt bookingid/optionid/potentialusers/course/cm und serialisiert sie in get_options. |
| `mod_booking\subscriber_selector_base` | `classes/subscriber_selector_base.php` | Form | 2 | B | - | Abstrakte Basis fuer Subscriber-(Lehrkraefte-)Selektoren; haelt optionid/context/currentgroup. |
| `mod_booking\existing_subscriber_selector` | `classes/existing_subscriber_selector.php` | Form | 1 | A | - | Selektor fuer bereits abonnierte Lehrkraefte (booking_teachers) einer Option. |
| `mod_booking\local\sync\booking_enrolment` | `classes/local/sync/booking_enrolment.php` | Service | 21 | C | P1 | Regelbasierte Membership-Sync-Engine: CRUD von Sync-Regeln, Mitglieder-Aufloesung, enrol/unenrol mit Bedingungs-/Ownership-Logik, Audit-Log und Cache-Invalidierung. |
| `mod_booking\enrollink` | `classes/enrollink.php` | Service | 24 | C | P2 | Lizenz-Bundle-Mechanik: ein Nutzer reserviert n Plaetze, generiert erlid und ermoeglicht Dritten Self-Enrolment ueber enrollink.php; statische Customform-Auswertung. |
| `mod_booking\local\connectedcourse` | `classes/local/connectedcourse.php` | Service | 7 | C | P2 | Verbindet/erzeugt den Moodle-Kurs einer Buchungsoption: waehlen, neu anlegen, async aus Template kopieren (backup/restore + Adhoc-Tasks); findet getaggte Template-Kurse. |
| `mod_booking\booking_potential_user_selector` | `classes/booking_potential_user_selector.php` | Form | 2 | C | P3 | User-Selektor fuer buchbare Nutzer: enrolled/bookanyone, Gruppenmodus-Filter, Ausschluss bereits gebuchter (booking_answers). |
| `mod_booking\booking_existing_user_selector` | `classes/booking_existing_user_selector.php` | Form | 2 | B | P3 | User-Selektor zum Entfernen bereits gebuchter Nutzer; sucht in potentialusers mit Institutions-Filter fuer Lehrkraefte. |

## S21 — entry_scripts

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `edit_rules.php` | `edit_rules.php` | Script | 0 | B | - | Booking-Rules-Uebersicht (DynamicForm, PRO-gated, system/cm-Context, Debug-Tabs). |
| `editoptions.php` | `editoptions.php` | Script | 0 | B | - | Editiert/erstellt Buchungsoption via DynamicForm option_form (3-fach capability check). |
| `teacherunavailability.php` | `teacherunavailability.php` | Script | 0 | B | - | Slotbooking: Lehrer-Unavailability-Bloecke verwalten (DynamicForm). |
| `unsubscribe.php` | `unsubscribe.php` | Script | 0 | B | - | Self-Unsubscribe von Notify-Liste (Direkt-DB delete + History + Cache-Purge). |
| `edit_certificateconditions.php` | `edit_certificateconditions.php` | Script | 0 | B | - | Uebersicht Zertifikatsbedingungen (DynamicForm, PRO-gated, system/cm-Context). |
| `mod_booking_categories_form` | `categoriesform.class.php` | Form | 2 | B | - | moodleform fuer Booking-Kategorie inkl. rekursiver Sub-Kategorie-Auswahl. |
| `index.php` | `index.php` | Script | 0 | B | - | Listet alle Booking-Instanzen eines Kurses nach Section. |
| `recalculateprices.php` | `recalculateprices.php` | Task | 0 | B | - | Queued recalculate_prices-Adhoc-Task nach Validierung (Formel/Kategorien). |
| `semesters.php` | `semesters.php` | Script | 0 | B | - | Admin-Seite fuer Semester/Feiertage/Semesterwechsel (3 DynamicForms). |
| `slotteacherassignments.php` | `slotteacherassignments.php` | Script | 0 | B | - | Slotbooking Student-Teacher-Zuordnung (DynamicForm). |
| `tagtemplates.php` | `tagtemplates.php` | Script | 0 | B | - | Listet Tag-Templates eines Kurses + Edit/Delete (booking_tags). |
| `tagtemplatesadd.php` | `tagtemplatesadd.php` | Script | 0 | B | - | Add/Edit eines Tag-Templates (DB-CRUD im Controller). |
| `confirmactivity.php` | `confirmactivity.php` | Script | 0 | B | - | Bestaetigt Completion via Aktivitaet/Badge ueber utils\db + bookingoption. |
| `instancetemplateadd.php` | `instancetemplateadd.php` | Script | 0 | B | - | Speichert aktuelle Instanz als Template (PRO/erstes Template gated). |
| `mybookings.php` | `mybookings.php` | Script | 0 | B | - | Persoenliche Buchungsuebersicht via shortcodes::mycourselist (+ ShoppingCart-History). |
| `enrollink.php` | `enrollink.php` | Script | 0 | B | - | Einloesung eines Enrol-Links (Bedingungspruefung + enrol_user). |
| `viewconfirmation.php` | `viewconfirmation.php` | Script | 0 | A | - | Zeigt Buchungs-/Wartelisten-Bestaetigungstext mit Platzhalter-Rendering. |
| `slotcalendar.php` | `slotcalendar.php` | Script | 0 | B | - | Slot-Kalender-Report-Seite (JS laedt Slots via WS). |
| `moveslot.php` | `moveslot.php` | Script | 0 | B | - | Host-Seite (Manager) fuer slotUpdate-DynamicForm (move/cancel/change). |
| `rebookslot.php` | `rebookslot.php` | Script | 0 | B | - | Self-Service-Host-Seite fuer slotUpdate-DynamicForm (Ownership-gated). |
| `tagtemplatesadd_form` | `tagtemplatesadd_form.php` | Form | 3 | B | - | moodleform fuer Tag-Template (Tag + Editor-Text). |
| `mod_booking_sendmessage_form` | `sendmessageform.class.php` | Form | 1 | B | - | moodleform fuer Custom-Nachricht (Subject + Editor). |
| `optionformconfig.php` | `optionformconfig.php` | Script | 0 | B | - | Konfigurationsseite der Optionsformular-Felder (PRO-gated, global/instance). |
| `edit_campaigns.php` | `edit_campaigns.php` | Script | 0 | A | - | Admin-Seite fuer Booking-Kampagnen (DynamicForm, PRO-gated). |
| `subscribeusersactivity.php` | `subscribeusersactivity.php` | Script | 0 | B | - | Form-Seite zum Transferieren von Usern zwischen Optionen. |
| `importoptions.php` | `importoptions.php` | Script | 0 | A | - | CSV-Import-Seite fuer Optionen (DynamicForm csvimport + bookingoptionsimporter). |
| `instancetemplatessettings.php` | `instancetemplatessettings.php` | Script | 0 | B | - | Admin-Seite: Instanz-Templates auflisten/loeschen (instancetemplatessettings_table). |
| `download.php` | `download.php` | Download | 0 | B | - | Export der bookingoptions_wbtable aus tablecache-Hash mit get_bookingoptions_fields. |
| `bulk_book_handler.php` | `bulk_book_handler.php` | Task | 0 | A | - | Queued book_all_students_task fuer eine Option, redirect mit Notification. |
| `performance.php` | `performance.php` | Script | 0 | A | - | Performance-Dashboard via performance_renderer + Mustache-Chart. |
| `download_optiondates_teachers_report.php` | `download_optiondates_teachers_report.php` | Download | 0 | B | - | Export der Optiondates-Teachers-Tabelle aus tablecache-Hash. |
| `pricecategories.php` | `pricecategories.php` | Script | 0 | A | - | Admin-Seite fuer Preiskategorien (DynamicForm + pricecategories_handler). |
| `importexcel_form` | `importexcel_form.php` | Form | 2 | A | - | moodleform mit Filepicker fuer CSV-Completion-Import. |
| `tag.php` | `tag.php` | Script | 0 | B | - | Listet Booking-Instanzen zu einem Moodle-Tag. |
| `search_sync_sources.php` | `search_sync_sources.php` | WS | 0 | A | - | AJAX: lazy-load Cohorts/Groups fuer Sync-Rule-Modal als JSON. |
| `sync_diagnostics.php` | `sync_diagnostics.php` | WS | 0 | A | - | AJAX: rendert Sync-Diagnose-HTML (letzte Enrolment-Versuche) als JSON. |
| `download_report2.php` | `download_report2.php` | Download | 0 | B | - | Export der BookingsTracker-Tabelle aus tablecache-Hash, Spalten je Scope. |
| `teacher.php` | `teacher.php` | Script | 0 | A | - | Oeffentliche Lehrer-Profilseite via output\page_teacher. |
| `option_date_template.php` | `option_date_template.php` | Script | 0 | B | - | Host-Seite fuer Optiondate-Template-DynamicForm (loadform via JS). |
| `bookinginstancetemplatessettings.php` | `bookinginstancetemplatessettings.php` | Script | 0 | B | - | Listet/loescht Instanz-Templates ueber bookinginstancetemplatessettings_table. |
| `viewpolicy.php` | `viewpolicy.php` | Script | 0 | A | - | Zeigt die Buchungs-Policy einer Instanz (format_text). |
| `customfield.php` | `customfield.php` | Script | 0 | A | - | Admin-Verwaltungsseite der Booking-Customfields (core_customfield management). |
| `customfieldsettings.php` | `customfieldsettings.php` | Script | 0 | A | - | Admin-Seite fuer Custom-Field-Konfiguration via form\customfield. |
| `bookingredirect.php` | `bookingredirect.php` | Script | 0 | B | - | Dekodiert base64-URL und leitet weiter (Workaround fuer Kalender-Exporter-Escaping). |
| `mod_booking_mod_form` | `mod_form.php` | Form | 9 | E | P0 | Instanz-Einstellungsformular (moodleform_mod): Definition, Completion, Validation, Pre/Postprocessing. |
| `report.php` | `report.php` | Script | 0 | E | P0 | God-Controller: Teilnehmerverwaltung einer Option (SQL-Filter, PDF, viele POST-Aktionen). |
| `subscribeusers.php` | `subscribeusers.php` | Script | 0 | E | P1 | Subscribe/Unsubscribe anderer User + Sync-Toggle + Cohort/Group + booked_users-Render. |
| `report2.php` | `report2.php` | Script | 0 | D | P1 | BookingsTracker: scope-basierte Uebersicht (System/Course/Instance/Option/Optiondate). |
| `slotrules.php` | `slotrules.php` | Script | 0 | D | P1 | Slot-Regel-Editor (Form + Persistenz-Mapping + Regel-Tabelle inkl. Preise inline). |
| `teacher_performed_units_report.php` | `teacher_performed_units_report.php` | Script | 0 | D | P2 | Report geleisteter Einheiten eines Lehrers (Filter-Form + Table, SQL inline). |
| `teachers_instance_report.php` | `teachers_instance_report.php` | Script | 0 | D | P2 | Instanzweiter Lehrer-Report (Filter-Form + teachers_instance_report_table). |
| `view.php` | `view.php` | Script | 0 | C | P2 | Haupt-Einstiegsseite: Optionsliste (output\view) + optionaler AI-Tab + Attachments/Tags. |
| `subbooking_timetabletest.php` | `subbooking_timetabletest.php` | Script | 0 | D | P2 | Test-/Demo-Seite, rendert hartcodiertes Timetable-JSON-Template. |
| `optionview.php` | `optionview.php` | Script | 0 | D | P2 | Oeffentliche Detailseite einer Option (Policy/Login/Capability-Logik + Description-Render). |
| `send_custom_message` | `sendmessage.php` | Script | 1 | C | P2 | Custom-Nachrichten-Seite + globale send_custom_message (message_controller + Bulk-Event). |
| `importexcel.php` | `importexcel.php` | Script | 0 | C | P2 | CSV-Import zum Setzen von Activity-Completion (Parsing+DB-Update im Controller). |
| `edit_optiontemplates.php` | `edit_optiontemplates.php` | Script | 0 | C | P2 | Bearbeitet Options-Template via option_form, ruft booking_option::update + Draft-Files. |
| `availabilityconditions.php` | `availabilityconditions.php` | Script | 0 | C | P3 | Admin-Dashboard Verfuegbarkeitsbedingungen (State-Save + Tabellen-HTML inline). |
| `optiondates_teachers_report.php` | `optiondates_teachers_report.php` | Script | 0 | C | P3 | Report Lehrer pro Optiondate (optiondates_teachers_table, SQL inline). |
| `otherbooking.php` | `otherbooking.php` | Script | 0 | C | P3 | Verwaltet Other-Booking-Regeln (Liste + Add/Delete-Buttons, inline HTML). |
| `mod_booking_teachers_form` | `teachers_form.php` | Form | 1 | C | P3 | moodleform: Lehrer-Checkbox-Liste mit Completion/Editing-Buttons. |
| `link.php` | `link.php` | Script | 0 | C | P3 | Konferenz-Link-Join: validiert/redirectet oder zeigt Wartehinweis. |
| `categoryadd.php` | `categoryadd.php` | Script | 0 | C | P3 | Add/Edit/Delete Booking-Kategorie (DB-CRUD + Delete-Guards im Controller). |
| `teachers.php` | `teachers.php` | Script | 0 | C | P3 | Oeffentliche Alle-Lehrer-Seite via output\page_allteachers (mit Teacher-SQL). |
| `otherbookingaddrule_form` | `otherbookingaddrule_form.php` | Form | 3 | C | P3 | moodleform fuer booking_other-Regel (Option-Select + Limit). |
| `scheduledmails.php` | `scheduledmails.php` | Script | 0 | C | P3 | Debug-only Uebersicht geplanter Mails (PRO-gated). |
| `otherbookingaddrule.php` | `otherbookingaddrule.php` | Script | 0 | C | P3 | Add/Edit/Delete einer booking_other-Regel (DB-CRUD im Controller). |
| `moveoption.php` | `moveoption.php` | Script | 0 | C | P3 | Verschiebt Option in andere Instanz (move_option_otherbookinginstance). |
| `categories.php` | `categories.php` | Script | 0 | C | P3 | Listet Booking-Kategorien eines Kurses + Edit/Delete-Links (rekursiv). |
| `optiontemplatessettings.php` | `optiontemplatessettings.php` | Script | 0 | C | P3 | Listet Options-Templates (bookingid=0), copytotemplate/delete-Aktionen. |
| `category.php` | `category.php` | Script | 0 | C | P3 | Listet Booking-Instanzen einer Booking-Kategorie. |
| `rating_rest.php` | `rating_rest.php` | WS | 0 | C | P3 | AJAX-Endpunkt zum Speichern einer Bewertung + Rueckgabe Durchschnitt als JSON. |

## S22 — db_layer

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `$capabilities` | `db/access.php` | DTO | 0 | B | - | Capability-Definition mit 56 mod/booking:*-Rechten je Contextlevel/Archetyp. |
| `(13 Migrations-Funktionen)` | `db/upgradelib.php` | Script | 13 | B | - | Freistehende Daten-Migrationshelfer fuer komplexere upgrade.php-Schritte (JSON-Patches, NULL-Fixes, Identifier-Split). |
| `$functions/$services` | `db/services.php` | WS | 0 | B | - | Registrierung von 37 externen Webservice-Funktionen (mod_booking_*). |
| `$definitions` | `db/caches.php` | DTO | 0 | A | - | 24 MUC-Cache-Definitionen mit gezielten setback*-Invalidationevents (Application vs Session). |
| `(7 prozedurale Helper)` | `locallib.php` | Util | 7 | B | - | Prozedurale Helfer: Bestaetigungsseite, Start/Enddatum-Ableitung, Customfield-/Eventbeschreibungs-Render, Optionstatus. |
| `$observers` | `db/events.php` | Event | 0 | A | - | 35 Event-Observer-Verdrahtungen auf mod_booking_observer inkl. Wildcard *->execute_rule und optionalem shopping_cart-Checkout. |
| `mod_booking\utils\wb_payment` | `classes/utils/wb_payment.php` | Util | 3 | B | - | PRO-Lizenzverifikation per eingebettetem RSA-Public-Key; pro_version_is_activated gated PRO-Features. |
| `mod_booking\local\sql\operators\equals` | `classes/local/sql/operators/equals.php` | Service | 3 | B | - | Operator '=' : CTE-Snippet, das Userprofilwert via user_info_data-Join fuer aktuellen USER vergleicht. |
| `mod_booking\local\sql\operators\not_equals` | `classes/local/sql/operators/not_equals.php` | Service | 3 | B | - | Operator '!=' : dialektabhaengiges CTE-Snippet fuer Ungleichheit des Userprofilwerts. |
| `mod_booking\local\sql\operators\contains` | `classes/local/sql/operators/contains.php` | Service | 3 | B | - | Operator '~' : dialektabhaengiges LIKE-Snippet fuer Teilstring-Match des Userprofilwerts. |
| `mod_booking\plugininfo\bookingextension_interface` | `classes/plugininfo/bookingextension_interface.php` | DTO | 9 | A | - | Vertrag fuer bookingextension-Subplugins (Option-Felder, Settings, Optionview-Daten, Col-Actions, Rule-Event-Keys, History). |
| `mod_booking\completion\custom_completion` | `classes/completion/custom_completion.php` | Service | 4 | B | - | (Orphan-Nachtrag) |
| `$shortcodes` | `db/shortcodes.php` | DTO | 0 | A | - | 14 Shortcode-Callbacks in mod_booking\shortcodes (courselist, allbookingoptions, bookingoptionview ...). |
| `mod_booking\local\sql\operators\base_operator` | `classes/local/sql/operators/base_operator.php` | DTO | 3 | A | - | Interface fuer SQL-Vergleichsoperatoren (get_sql + dialektspezifische Varianten). |
| `$tasks` | `db/tasks.php` | Task | 0 | A | - | 6 scheduled Cron-Tasks (mod_booking\task\*) mit Zeitplaenen. |
| `mod_booking\booking_advanced_testcase` | `classes/booking_advanced_testcase.php` | TestBase | 2 | - | - | (Orphan-Nachtrag) |
| `xmldb_booking_install` | `db/install.php` | Script | 1 | A | - | Install-Hook: seedet Default-Preiskategorie 'default'. |
| `$messageproviders` | `db/messages.php` | DTO | 0 | A | - | 2 Message-Provider (bookingconfirmation, sendmessages mit Capability-Gate). |
| `mod_booking\local\testing\booking_advanced_testcase` | `classes/local/testing/booking_advanced_testcase.php` | Test | 0 | A | - | Backward-kompatibler Alias auf tests/booking_advanced_testcase fuer Legacy-Agent-Tests. |
| `$plugin` | `version.php` | DTO | 0 | A | - | Versionsdeklaration: 2026062700, Release 9.4.0, Requires 4.5, supported [405,502], dep local_wunderbyte_table. |
| `$logs` | `db/log.php` | DTO | 0 | A | - | 6 Legacy-Log-Aktionen (view/update/add/report/choose). |
| `(JSON subplugintypes)` | `db/subplugins.json` | DTO | 0 | A | - | Deklariert Subplugin-Typ bookingextension unter mod/booking/bookingextension. |
| `(MOD_BOOKING_*-Konstanten + booking_*-Callbacks)` | `lib.php` | Script | 40 | E | P1 | Core-Top-Level: ~286 globale Konstanten + ~40 Moodle-Lifecycle-Callbacks (Instanz-CRUD, Grading, Rating, Comments, pluginfile, Navigation). |
| `(Admin-Settings-Baum)` | `settings.php` | Script | 0 | D | P2 | Baut kompletten Admin-Settings-Baum; Integrationspunkt fuer bookingextension-Subplugins (load_settings-Loop). |
| `mod_booking\utils\webservice_import` | `classes/utils/webservice_import.php` | Service | 11 | C | P2 | Import-Controller (PRO): merge-or-create von Buchungsoptionen aus Webservice-Daten inkl. Customfields/Teacher. |
| `mod_booking\local\sql\operator_builder` | `classes/local/sql/operator_builder.php` | Service | 6 | D | P2 | Baut dialektabhaengige SQL-WHERE-Snippets fuer Profilfeld-Bedingungen (PostgreSQL/MySQL) mit Named-Param-Sicherheit. |
| `xmldb_booking_upgrade` | `db/upgrade.php` | Script | 1 | E | P3 | Append-only Schema-/Daten-Migration: 200 sequentielle if(oldversion<N)-Bloecke ab 2011 bis 2026062302 in einer Funktion. |
| `mod_booking\local\modechecker` | `classes/local/modechecker.php` | Util | 6 | C | P3 | Erkennt Request-Modus (AJAX/Webservice/CLI/normal) zur Steuerung von Render-/Booking-Verhalten. |
| `mod_booking\utils\db` | `classes/utils/db.php` | Util | 4 | C | P3 | Ad-hoc-DB-Helper: eigene Buchungen, Badges, User-Diff nach Aktivitaetsabschluss/Badge. |
| `mod_booking\plugininfo\bookingextension` | `classes/plugininfo/bookingextension.php` | Service | 6 | B | P3 | Subplugin-Info-Basisklasse (extends core base) mit No-op-Default-Implementierungen fuer Extension-Hooks. |
| `mod_booking\GoogleUrlApi` | `classes/GoogleUrlApi.php` | Util | 4 | C | P3 | (Orphan-Nachtrag) |
| `(40 TABLE-Definitionen)` | `db/install.xml` | DTO/Schema | 0 | B | P3 | Kanonische DDL-Schemaquelle mit 40 Tabellen (Kern, Slotbooking, Preise, Templates, Rules, Zertifikate, Subbookings, Sync, Performance). |

## S23 — frontend_js

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `jquery.barrating` | `amd/src/jquery.barrating.js` | Util | 0 | C | - | Vendor jQuery-Bar-Rating-Plugin (MIT) fuer Star-Rating. |
| `slot_day_renderers` | `amd/src/slotbooking/slot_day_renderers.js` | Renderer | 6 | B | - | Geteilte Tages-Renderer (Timeline + Liste) + Selection-Interface; entkoppelt Renderer von Selection-Modell. |
| `slotbooking/repository` | `amd/src/slotbooking/repository.js` | WS | 5 | A | - | Zentraler Webservice-Zugang fuer alle Slot-Calls (get/save/release), einzige JSON-Marshalling-Stelle. |
| `bookingfavorite` | `amd/src/bookingfavorite.js` | Controller | 1 | B | - | Optimistic-UI Favoriten-Stern-Toggle (Icon/Tooltip sofort), eigentlicher WS in local_wunderbyte_table. |
| `vue3/router` | `vue3/router/router.js` | Script | 0 | A | - | vue-router (Hash-History) mit Overview/Context/NotFound-Routen. |
| `SkeletonContent` | `vue3/components/helper/SkeletonContent.vue` | JS | 1 | A | - | Lade-Skeleton fuer Inhalt (zufaellige Breiten). |
| `bookingjslib` | `amd/src/bookingjslib.js` | Controller | 1 | A | - | Report2-Navigations-Dropdown: Navigation per Auswahl. |
| `SkeletonTab` | `vue3/components/helper/SkeletonTab.vue` | JS | 0 | A | - | Lade-Skeleton fuer Tabs. |
| `init_comments` | `amd/src/init_comments.js` | Service | 1 | B | - | Stoesst mod_booking_init_comments an (Init der Kommentar-Engine). |
| `FilterSearchbar` | `vue3/components/FilterSearchbar.vue` | JS | 1 | A | - | Suchfeld zum Filtern der Tabs (emit filteredTabs). |
| `NotFound` | `vue3/components/NotFound.vue` | JS | 0 | A | - | 404-Route-Anzeige. |
| `notifications` | `amd/src/notifications.js` | Util | 1 | A | - | Duenner Wrapper showNotification um core/notification.addNotification. |
| `bookit` | `amd/src/bookit.js` | Controller | 18 | D | P1 | Kern des Buchungs-Flows: delegierte Bookit-Button-Klicks, Bookit-Webservice und Orchestrierung der mehrseitigen Prepages (Modal/Inline, BS4+BS5). |
| `condition/slotBooking` | `amd/src/condition/slotBooking.js` | Condition | 25 | D | P1 | Controller der Slotbooking-Prepage: slotbooking_form DynamicForm, Kalender/Listen/Fixed-Editor, Live-Validierung, Book/Move-Tab. |
| `SlotCalendarPicker` | `amd/src/slotCalendarPicker.js` | Renderer | 25 | D | P1 | DOM-Kalender-Widget als Klasse: Monats/Wochen-Grid, Tages-Slot-Liste/Timeline, Preis-Skala, Selektion. |
| `prepageFooter` | `amd/src/bookingpage/prepageFooter.js` | Controller | 16 | C | P2 | Footer-Navigation der Prepages (continue/back/checkout/close) und Schließen von Modal/Inline inkl. BS5-API + DOM-Fallbacks. |
| `condition/slotUpdate` | `amd/src/condition/slotUpdate.js` | Condition | 12 | B | P2 | Controller 'Update booking' (move/cancel/change) ueber slotupdate_form mit Two-Pass-Confirm und Cart/Refund-Routing. |
| `csvimport` | `amd/src/csvimport.js` | Form | 6 | C | P2 | DynamicForm mod_booking\form\csvimport mit Preview-Modus, Tabellen-Pagination und Upload-Bestaetigung. |
| `BookingDashboard` | `vue3/components/BookingDashboard.vue` | JS | 10 | D | P2 | Haupt-Dashboard: Kategorie-Tabs, Scroll-Steuerung, Statistik/Info/Config-Subkomponenten, Unsaved-Confirm. |
| `teacherUnavailability` | `amd/src/teacherUnavailability.js` | Controller | 11 | C | P2 | Controller Lehrer-(Un)verfuegbarkeit: teacherunavailability_form mit Scope/Markmode/Viewmode-Watchern + Kalender. |
| `performance_chart` | `amd/src/performance_chart.js` | Renderer | 7 | C | P2 | ChartJS-Performance-Liniendiagramm (AMD) mit Sidebar/Save/Delete-Aktionen via Webservices. |
| `dynamiceditoptionform` | `amd/src/dynamiceditoptionform.js` | Form | 3 | D | P2 | DynamicForm option_form mit NoSubmit-Buttons (Template/Optiontype/Slot), Optionstermin-Loeschung, BS4/5-Workarounds. |
| `CapabilityButtons` | `vue3/components/helper/CapabilityButtons.vue` | JS | 8 | C | P2 | Capability-Auswahl + Save/Restore der Feldkonfiguration (setParentContent), Unsaved-Hinweis. |
| `CapabilityOptions` | `vue3/components/helper/CapabilityOptions.vue` | JS | 9 | C | P2 | Feldliste mit nativem HTML5-Drag&Drop-Reordering + Checkbox-Aktivierung. |
| `dynamicoptiondateform` | `amd/src/dynamicoptiondateform.js` | Form | 4 | D | P2 | DynamicForm + ModalForm fuer Optionstermine (reoccurring/custom dates), Datelist-Re-Mounting. |
| `TabInformation` | `vue3/components/dashboard/TabInformation.vue` | JS | 3 | C | P2 | Kategorie-Infoblock mit Aktionslinks (Rollen/Kurs/Kategorie anlegen). |
| `condition/subbookingAdditionalPerson` | `amd/src/condition/subbookingAdditionalPerson.js` | Condition | 5 | C | P2 | Controller Zusatzperson-Subbooking: additionalperson_form, Bookit/Cart-Button-Blocking bis Validierung. |
| `WunderByteJS` | `amd/src/wunderbyte.js` | Util | 10 | C | P2 | Prototyp-basiertes Mini-Framework fuer Drag-Sort (sortable) und Drag&Drop (dragable). |
| `bookinginstancetemplateselect` | `amd/src/bookinginstancetemplateselect.js` | Controller | 1 | D | P2 | Fuellt das Instanz-Settings-Formular aus einem gewaehlten Template (jQuery, mod_booking_instancetemplate). |
| `StatisticsView` | `vue3/components/dashboard/StatisticsView.vue` | JS | 4 | C | P2 | ChartJS-Liniendiagramm + DatePicker fuer Statistik-Ansicht. |
| `edit_note` | `amd/src/edit_note.js` | Controller | 6 | C | P2 | Inline-Edit der Buchungsnotiz (jQuery): textarea-Editing + mod_booking_update_bookingnotes. |
| `dynamicrulesform` | `amd/src/dynamicrulesform.js` | Form | 1 | C | P2 | ModalForm rulesform/deleteruleform (add/edit/delete von Rules). |
| `vue3/store` | `vue3/store.js` | Service | 8 | C | P2 | Vuex-Store: State (tabs/content/configlist) + WS-Actions (fetchTab/setParentContent) + String-Cache. |
| `dynamicactionsform` | `amd/src/dynamicactionsform.js` | Form | 1 | C | P2 | ModalForm actionsform/deleteactionsform. |
| `dynamiccampaignsform` | `amd/src/dynamiccampaignsform.js` | Form | 1 | C | P2 | ModalForm campaignsform/deletecampaignform. |
| `modal_init` | `amd/src/modal_init.js` | Service | 4 | C | P2 | Lazy-Nachladen der Buchungsoptions-Beschreibung in Modal sobald sichtbar (MutationObserver + Webservice). |
| `condition/customForm` | `amd/src/condition/customForm.js` | Condition | 2 | C | P2 | Controller Custom-Form-Prepage: customform_form, Continue-Button-Gating, Auto-Clear von Initialwerten. |
| `dynamicsubbookingsform` | `amd/src/dynamicsubbookingsform.js` | Form | 1 | C | P2 | ModalForm subbookingsform/subbookingsdeleteform. |
| `form_booking_options_selector` | `amd/src/form_booking_options_selector.js` | Field | 2 | C | P2 | Autocomplete-Transport fuer Buchungsoptionen (mod_booking_search_booking_options). |
| `dynamiccertificateconditionsform` | `amd/src/dynamiccertificateconditionsform.js` | Form | 1 | C | P2 | ModalForm certificateconditionsform/deletecertificateconditionform. |
| `condition/bookingPolicy` | `amd/src/condition/bookingPolicy.js` | Condition | 2 | C | P2 | Controller Policy-Prepage: bookingpolicy_form, Continue-Button-Gating. |
| `view_actions` | `amd/src/view_actions.js` | Controller | 1 | C | P2 | Report-Seiten-Logik (jQuery): Star-Rating (barrating), Massen-Checkboxen, Such-Reset. |
| `form_courses_selector` | `amd/src/form_courses_selector.js` | Field | 2 | C | P2 | Autocomplete-Transport fuer Kurse (mod_booking_search_courses). |
| `form_teachers_selector` | `amd/src/form_teachers_selector.js` | Field | 2 | C | P2 | Autocomplete-Transport fuer Lehrer (mod_booking_search_teachers). |
| `form_users_selector` | `amd/src/form_users_selector.js` | Field | 2 | C | P2 | Autocomplete-Transport fuer User (mod_booking_search_users). |
| `form_templates_selector` | `amd/src/form_templates_selector.js` | Field | 2 | C | P2 | Autocomplete-Transport fuer Templates (mod_booking_search_templates). |
| `form_sync_source_selector` | `amd/src/form_sync_source_selector.js` | Field | 2 | C | P2 | Autocomplete-Transport fuer Sync-Quelle (legacy define + fetch statt Ajax). |
| `app-lazy` | `amd/src/app-lazy.js` | Script | 0 | E | P2 | Eingecheckter minifizierter Build-Output (Vue/DatePicker-Bundle, ~1 MB in einer Zeile). |
| `slotCalendarReport` | `amd/src/slotCalendarReport.js` | Controller | 4 | B | P3 | Report-Kalender der gebuchten Slots: getBookedSlots, Detail-Panel je Slot via Mustache. |
| `performance_submit` | `amd/src/performance_submit.js` | Controller | 1 | C | P3 | Performance-Submit-Button (AMD): mod_booking_submit_performance + Chart-Reload. |
| `sync_rule_modal` | `amd/src/sync_rule_modal.js` | Form | 1 | B | P3 | ModalForm-Launcher fuer Sync-Rules (add/edit/delete/activate) (AMD). |
| `SubLists` | `vue3/components/helper/SubLists.vue` | JS | 3 | B | P3 | Verschachtelte Unterlisten innerhalb der Feldkonfiguration. |
| `button_notifyme` | `amd/src/button_notifyme.js` | Controller | 2 | B | P3 | Notify-me-Toggle-Button via mod_booking_toggle_notify_user. |
| `sync_diagnostics` | `amd/src/sync_diagnostics.js` | Service | 1 | B | P3 | Lazy-Fetch der Sync-Diagnose-Tabelle ueber sync_diagnostics.php (AMD). |
| `editteachersforoptiondate_form` | `amd/src/editteachersforoptiondate_form.js` | Form | 2 | C | P3 | ModalForm editteachersforoptiondate_form (Lehrer je Termin). |
| `bookingcompetencies` | `amd/src/bookingcompetencies.js` | Controller | 2 | C | P3 | Toggle des Kompetenz-Filters fuer eine wunderbyte_table. |
| `confirm_cancel` | `amd/src/confirm_cancel.js` | Form | 2 | B | P3 | ModalForm modal_confirmcancel für Storno-Bestätigung, Reload bei Submit. |
| `edit-teacher-description` | `amd/src/edit-teacher-description.js` | Form | 2 | C | P3 | ModalForm modal_editteacherdescription. |
| `BookingStats` | `vue3/components/dashboard/BookingStats.vue` | JS | 1 | B | P3 | Tabelle der Booking-Instanzen mit Buchungszahlen + Checkbox (setCheckedBookingInstance). |
| `dynamicdeputymodal` | `amd/src/dynamicdeputymodal.js` | Form | 2 | C | P3 | ModalForm dynamicdeputyselect (Stellvertreter-Auswahl). |
| `vue3/main` | `vue3/main.js` | Script | 1 | B | P3 | Bootstrap der Vue-3-SPA: createApp, PrimeVue/Notifications/Router/Store, Mount auf #mod-booking-app. |
| `ConfirmationModal` | `vue3/components/modal/ConfirmationModal.vue` | JS | 2 | B | P3 | Bestaetigungsdialog (emit confirmBack) fuer ungespeicherte Aenderungen. |
| `signinsheetdownload` | `amd/src/signinsheetdownload.js` | Controller | 1 | B | P3 | Toggle/Download-Buttons der Anwesenheitsliste (Orientierung waehlen + abschicken). |
| `elective-sorting` | `amd/src/elective-sorting.js` | Controller | 1 | C | P3 | Elective-Sortier-Init, nutzt WunderByteJS.sortable. |
| `dynamicsemestersform` | `amd/src/dynamicsemestersform.js` | Form | 1 | C | P3 | DynamicForm Semester (base64-data im DOM, reload-on-submit). |
| `dynamicchangesemesterform` | `amd/src/dynamicchangesemesterform.js` | Form | 1 | C | P3 | DynamicForm Semesterwechsel. |
| `dynamicholidaysform` | `amd/src/dynamicholidaysform.js` | Form | 1 | C | P3 | DynamicForm Feiertage (base64-data im DOM). |
| `slotteacherassignments_form` | `amd/src/slotteacherassignments_form.js` | Form | 1 | B | P3 | DynamicForm-Bootstrap fuer Slot-Lehrerzuordnung. |
| `ConfigForm` | `vue3/components/dashboard/ConfigForm.vue` | JS | 2 | B | P3 | Container fuer Kontext-Feldkonfiguration: CapabilityButtons + CapabilityOptions. |
| `dynamicpricecategoriesform` | `amd/src/dynamicpricecategoriesform.js` | Form | 1 | C | P3 | DynamicForm Preiskategorien (reload-on-submit/cancel). |

## S24 — backup_restore

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `restore_booking_activity_structure_step` | `backup/moodle2/restore_booking_stepslib.php` | Script | 18 | C | P1 | Kern des Restore: registriert config-/userinfo-gegatete restore_path_elements und 16 process_*-Handler mit ID-Remapping, Feld-Neutralisierung, Insert und manuellem Datei-/Customfield-Copy. |
| `backup_booking_activity_structure_step` | `backup/moodle2/backup_booking_stepslib.php` | Script | 1 | B | P2 | Definiert den kompletten backup_nested_element-XML-Baum der Booking-Instanz inkl. Source-Tables/-SQL, ID- und File-Annotationen; config- und userinfo-gegatete Zweige. |
| `restore_booking_activity_task` | `backup/moodle2/restore_booking_activity_task.class.php` | Task | 6 | A | P3 | Registriert den Restore-Strukturschritt und definiert Link-Decode-, Decode-Content- sowie Restore-Log-Regeln (Modul- und Kursebene). |
| `backup_booking_activity_task` | `backup/moodle2/backup_booking_activity_task.class.php` | Task | 3 | A | P3 | Registriert den einzigen Backup-Strukturschritt der Booking-Aktivitaet und definiert das URL-Link-Encoding (BOOKINGINDEX/BOOKINGVIEWBYID). |
| `-` | `backup/moodle2/backup_booking_settingslib.php` | Script | 0 | C | P3 | Platzhalter fuer instanz-spezifische Backup-Settings; enthaelt faktisch nur auskommentierten Code, keine Klasse/Logik. |

## S25 — mobile

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\entities\service_provider` | `classes/entities/service_provider.php` | DTO | 1 | A | - | Adapter, der das local_entities-Callback-Interface implementiert und die einzige Methode an booking::return_array_of_entity_dates delegiert. |
| `mod_booking\local\mobile\customformstore` | `classes/local/mobile/customformstore.php` | Service | 11 | C | P2 | Cache-Store fuer Custom-Form-Zwischenstaende plus fachliche Logik: serverseitige Validierung (Kontingent/Kapazitaet), Fehleruebersetzung, Label-Lookup und Preis-Modifikation. |
| `mod_booking\local\mobile\mobileformbuilder` | `classes/local/mobile/mobileformbuilder.php` | Renderer | 5 | C | P2 | Statischer Renderer, der Custom-Form-Definitionen in Ionic-App-Markup uebersetzt (Mustache-Templates + handgebauter HTML/JS-String mit WS-Rueckbindung). |
| `mod_booking\local\mobile\slotbookingstore` | `classes/local/mobile/slotbookingstore.php` | Service | 7 | B | P3 | Cache-Store fuer Slot-Auswahl zwischen Prepage und finaler Buchung; parst Auswahl-Strings/JSON in Timestamp-Ranges und Lehrer-Zuordnungen pro Slot. |
| `db/mobile.php (Config $addons)` | `db/mobile.php` | Config | 0 | A | P3 | Deklaratives Moodle-Mobile-Handler-Manifest: registriert den coursebooking-Handler (CoreCourseModuleDelegate, mobile_course_view) und vorzuladende Sprachstrings. Reine Konfiguration ohne Logik. |

## S26 — privacy_gdpr

| Klasse | Pfad | Rolle | Methoden | Score | Prio | Verantwortung |
|---|---|---|---:|:--:|:--:|---|
| `mod_booking\privacy\provider` | `classes/privacy/provider.php` | Privacy/GDPR-Provider (Service) | 8 | C | P2 | Implementiert die Moodle-core_privacy-Schnittstellen fuer mod_booking: deklariert GDPR-Metadaten von 13 Tabellen und liefert Context-/User-Discovery, Datenexport und Loeschung (3 Pfade) personenbezogener Booking-Daten. |
