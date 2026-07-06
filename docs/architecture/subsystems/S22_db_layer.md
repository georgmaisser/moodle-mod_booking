# S22 — db_layer

## Zweck & Grenzen

Das Subsystem `db_layer` bündelt die Moodle-Plugin-Fundamente von `mod_booking`:
das relationale Schema (`db/install.xml`, 40 Tabellen), die historische Schema-
Migration (`db/upgrade.php` + `db/upgradelib.php`), die deklarativen Plugin-
Definitionsdateien (Capabilities, Caches, Events, Tasks, Messages, Webservices,
Shortcodes, Mobile, Logs, Subplugins), die Top-Level-Lifecycle-Hooks (`lib.php`,
`locallib.php`), die Admin-Settings-Baum-Definition (`settings.php`), die
Versionsdeklaration (`version.php`), das Subplugin-Framework (`classes/plugininfo`),
die SQL-Operator-Bausteine für Verfügbarkeits-/Profilfeld-Bedingungen
(`classes/local/sql`), kleine Infrastruktur-Utils (`classes/utils`,
`classes/local/modechecker.php`) sowie einen Test-Kompatibilitäts-Alias
(`classes/local/testing`).

Grenzen: Hier liegt die *Deklaration* und das *Fundament*. Die eigentliche
Geschäftslogik (booking_option, booking, price, rules, conditions, renderers,
tasks-Implementierungen, Observer-Implementierung `mod_booking_observer`) lebt in
anderen Subsystemen und wird hier nur referenziert/verdrahtet. Die Datei-/Array-
Definitionen in `db/*.php` sind reine Konfiguration, die Moodle-Core einliest.

## Position im Gesamtsystem

- `version.php` deklariert Komponente `mod_booking`, Version `2026062700`,
  Release `9.4.0`, Requires Moodle `2024100700` (4.5), supported `[405, 502]`,
  Abhängigkeit `local_wunderbyte_table >= 2026061801`.
- `db/install.xml` ist die kanonische Schema-Quelle; `db/upgrade.php` führt jede
  Installation inkrementell auf den aktuellen Stand (200 `upgrade_mod_savepoint`-
  Schritte, letzter `2026062302`).
- `db/*.php` werden vom Moodle-Core gelesen, um Capabilities, Caches, Event-
  Observer, Cron-Tasks, Message-Provider, externe Webservices, Shortcodes,
  Mobile-Handler und Log-Aktionen zu registrieren.
- `lib.php` liefert die von Core erwarteten `booking_*`-Callbacks (Instanz-CRUD,
  Grading, Rating, Comments, pluginfile, Navigation, feature support) und ~286
  globale `MOD_BOOKING_*`-Konstanten, die das gesamte Plugin nutzt.
- `settings.php` baut den kompletten Admin-Settings-Baum und ist der Integrations-
  punkt für `bookingextension`-Subplugins (Loop bei `settings.php:1340`).
- `classes/local/sql` wird von der Availability-/Bedingungs-Engine (S-Conditions)
  konsumiert, um datenbankdialekt-spezifische WHERE-Snippets zu erzeugen.

## Schlüsselkonzepte

- **Schema-Append-only-Migration**: Moodle-Konvention — `install.xml` = Zielbild,
  `upgrade.php` = vollständige Historie ab 2011; alte Schritte bleiben stehen.
- **Deklarative Plugin-Definition**: Jede `db/*.php` exportiert ein wohldefiniertes
  Array (`$capabilities`, `$definitions`, `$observers`, `$tasks`,
  `$messageproviders`, `$functions`/`$services`, `$shortcodes`, `$addons`, `$logs`).
- **Subplugin-Typ `bookingextension`** (`db/subplugins.json`): erweiterbares
  Subplugin-Framework über `plugininfo\bookingextension` (extends `core\plugininfo\base`)
  und Vertrag `bookingextension_interface`.
- **MUC-Cache-Topologie**: 24 Cache-Definitionen mit gezielten
  `invalidationevents` (`setback*`) — Application- vs. Session-Mode, mit/ohne
  Static-Acceleration je nach Konsistenzbedarf (z. B. `bookingoptionsanswers`
  bewusst ohne Static-Acceleration).
- **Dialekt-aware SQL-Operatoren**: `operator_builder` + `operators\*` erzeugen
  getrennte PostgreSQL-/MySQL-Snippets für Profilfeld-Vergleiche, parametrisiert
  über Moodle-Named-Params (`generate_unique_param_name`).
- **PRO-Lizenz-Gate**: `utils\wb_payment::pro_version_is_activated()` (RSA-Public-
  Key-Entschlüsselung) schaltet PRO-Features frei und gated große Teile von
  `settings.php` (~30 `if ($proversion)`-Blöcke).
- **Request-Mode-Erkennung**: `local\modechecker` unterscheidet AJAX/Webservice/
  CLI/normale Requests, um Render-Verhalten (Buttons vs. Detailseiten-Link) zu
  steuern.

## Datenfluss

1. **Install**: Core liest `install.xml` → legt 40 Tabellen an →
   `xmldb_booking_install()` seedet Default-Preiskategorie `default`.
2. **Upgrade**: Core ruft `xmldb_booking_upgrade($oldversion)` →
   sequentielle `if ($oldversion < N)`-Blöcke (DDL via `xmldb_table`/`xmldb_field`,
   plus Daten-Migrationen aus `upgradelib.php`) → `upgrade_mod_savepoint`.
3. **Laufzeit-Registrierung**: Core liest `db/*.php`-Arrays → registriert
   Capabilities/Caches/Observer/Tasks/WS/Shortcodes.
4. **Instanz-Lifecycle**: Formular → `booking_add_instance()` /
   `booking_update_instance()` / `booking_delete_instance()` in `lib.php`
   schreiben in `booking` + Folgetabellen, lösen Rules/Calendar/Grade-Items aus.
5. **Bedingungsauswertung**: Condition-Engine ruft
   `operator_builder::build_profile_field_check($dbtype, ...)` →
   dialektabhängiges SQL-Snippet + `$params` → `$DB->get_records_sql`.
6. **Subplugin-Settings**: `settings.php` iteriert `get_plugins_of_type('bookingextension')`
   → `load_settings()` jeder Extension wird in den Admin-Baum gehängt.

## Dateien & Klassen

| Datei | Klasse / Symbol | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|-----------------|-------|-----|----------|-------------|-----------------|
| db/install.xml | (40 TABLE-Defs) | Schema/DDL | — | — | B | P3 |
| db/upgrade.php | `xmldb_booking_upgrade()` | Migration | 5566 | 1 (200 Steps) | E | P3 |
| db/upgradelib.php | 13 Migrations-Funktionen | Migration-Helper | 289 | 13 | B | - |
| db/install.php | `xmldb_booking_install()` | Install-Seed | 44 | 1 | A | - |
| db/access.php | `$capabilities` | Capability-Config | 674 | 0 (56 Caps) | B | - |
| db/caches.php | `$definitions` | MUC-Cache-Config | 220 | 0 (24 Caches) | A | - |
| db/events.php | `$observers` | Event-Verdrahtung | 168 | 0 (35 Obs.) | A | - |
| db/services.php | `$functions`/`$services` | Webservice-Config | 259 | 0 (37 Funcs) | B | - |
| db/tasks.php | `$tasks` | Cron-Config | 78 | 0 (6 Tasks) | A | - |
| db/messages.php | `$messageproviders` | Message-Config | 43 | 0 | A | - |
| db/mobile.php | `$addons` | Mobile-Handler-Config | 74 | 0 | A | - |
| db/shortcodes.php | `$shortcodes` | Shortcode-Config | 86 | 0 (14) | A | - |
| db/log.php | `$logs` | Legacy-Log-Config | 33 | 0 | A | - |
| db/subplugins.json | (JSON) | Subplugin-Typ-Decl | — | — | A | - |
| classes/plugininfo/bookingextension_interface.php | `bookingextension_interface` | Subplugin-Vertrag | 111 | 9 (iface) | A | - |
| classes/plugininfo/bookingextension.php | `bookingextension` | Subplugin-Info-Basis | 100 | 6 | B | P3 |
| classes/local/sql/operator_builder.php | `operator_builder` | SQL-Snippet-Builder | 367 | 6 | D | P2 |
| classes/local/sql/operators/base_operator.php | `base_operator` | Operator-Interface | 82 | 3 (iface) | A | - |
| classes/local/sql/operators/equals.php | `equals` | Operator (=) | 127 | 3 | B | - |
| classes/local/sql/operators/not_equals.php | `not_equals` | Operator (!=) | 125 | 3 | B | - |
| classes/local/sql/operators/contains.php | `contains` | Operator (~) | 119 | 3 | B | - |
| classes/utils/db.php | `utils\db` | Ad-hoc-SQL-Helper | 159 | 4 | C | P3 |
| classes/utils/wb_payment.php | `utils\wb_payment` | Lizenz-Verifikation | 144 | 3 | B | - |
| classes/utils/webservice_import.php | `utils\webservice_import` | Import-Controller | 400 | 11 | C | P2 |
| classes/local/modechecker.php | `local\modechecker` | Request-Mode-Detektor | 187 | 6 | C | P3 |
| classes/local/testing/booking_advanced_testcase.php | `booking_advanced_testcase` | Test-Compat-Alias | 36 | 0 | A | - |
| version.php | `$plugin` | Versionsdeklaration | 36 | 0 | A | - |
| lib.php | (~286 Konst. + ~40 Funcs) | Core-Lifecycle/Konstanten | 2941 | ~40 | E | P1 |
| locallib.php | 7 prozedurale Helper | Helper-Funktionen | 202 | 7 | B | - |
| settings.php | (Admin-Tree) | Admin-Settings-Baum | 2788 | 0 | D | P2 |

### db/upgrade.php — `xmldb_booking_upgrade($oldversion)`

`db/upgrade.php:33` — eine einzige Funktion mit 200 sequentiellen
`if ($oldversion < N)`-Blöcken (DDL via `$dbman`/`xmldb_table`/`xmldb_field`,
Indizes, Default-Werte) und eingestreuten Daten-Migrationen, jeweils mit
`upgrade_mod_savepoint(true, N, 'booking')`. Lädt `upgradelib.php` per `require_once`.
Reine Append-only-Historie ab `2011020401` bis `2026062302`.

- `function xmldb_booking_upgrade($oldversion)` — public/global: führt alle
  ausstehenden Schema-/Daten-Upgrades aus.

### db/upgradelib.php — Migrations-Helper

Frei stehende Funktionen, die von `upgrade.php` für komplexere Daten-Migrationen
aufgerufen werden. Alle global, je 1 Aufgabe:
- `migrate_booking_option_identifiers_2022090802()` — splittet alte `text`-IDs in `identifier`.
- `migrate_optionids_for_prices_2022112901()` — setzt `area='option'` für Preise.
- `migrate_optionsfields_2023022800()` — initialisiert `optionsfields`-Defaultliste.
- `fix_bookingoption_descriptionformat_2024022700()` — `descriptionformat 0→1`.
- `fix_showlistoncoursepage_2024030801()` — `showlistoncoursepage 2→1`.
- `migrate_contextids_2024040901()` — `booking_rules.contextid = 1`.
- `fix_booking_templateid()` — NULL-`templateid → 0`.
- `fix_places_for_booking_answers()` — NULL-`places → 1`.
- `remove_completiongradeitemnumber_2025010803()` — bereinigt `course_modules`.
- `booking_options_initialize_timecreated()` — `timecreated = timemodified` wo 0.
- `booking_upgrade_change_id_425_to_391()` — JSON-Patch in `booking_form_config`.
- `migrate_selflearningcourse_json_to_type_2025122201()` — JSON-Flag → `type`-Spalte.
- `delete_customfields_in_tool_certificate_2026030500()` — räumt CF-Kategorien auf.

### classes/plugininfo/bookingextension_interface.php — `bookingextension_interface`

Vertrag, den jede `bookingextension_*`-Subplugin-Klasse erfüllen muss. Methoden
(alle abstrakt): `get_plugin_name(): string`, `contains_option_fields(): bool`,
`get_option_fields_info_array(): array`, `load_settings(\part_of_admin_tree,
$parentnodename, $hassiteconfig): void`, statisch
`load_data_for_settings_singleton(int): object`,
`set_template_data_for_optionview(object): array`,
`add_options_to_col_actions(object, mixed): string`,
`get_allowedruleeventkeys(): array`,
`get_booking_history_description(\stdClass, array): string`.

### classes/plugininfo/bookingextension.php — `bookingextension`

`extends core\plugininfo\base`. Liefert die Subplugin-Typ-Verwaltung und Default-
No-op-Implementierungen.
- `is_enabled()` — public: immer `true`.
- `is_uninstall_allowed()` — public: erlaubt Deinstallation (`true`).
- `uninstall_cleanup()` — public: Pre-Uninstall-Hook (delegiert an parent).
- `add_options_to_col_actions(object $settings, $context): string` — static: Default `''`.
- `get_allowedruleeventkeys(): array` — static: Default `[]`.
- `get_booking_history_description(stdClass, array): string` — static: Default `''`.

Schuld: implementiert `bookingextension_interface` *nicht* explizit (`implements`
fehlt), obwohl es teilweise dieselben statischen No-op-Defaults bereitstellt —
Vertrag und Basisklasse sind entkoppelt (`bookingextension.php:33`).

### classes/local/sql/operator_builder.php — `operator_builder`

Zentraler Builder für SQL-WHERE-Snippets, die JSON-kodierte Profilfeld-Bedingungen
gegen Userprofil-Werte prüfen — getrennt für PostgreSQL und MySQL. Kollaborateure:
`operators\equals|not_equals|contains`, `singleton_service` (importiert, im Code
nicht sichtbar genutzt). Security-Modell via Named-Params dokumentiert im Klassen-Doc.
- `generate_unique_param_name(array $params, string $basename='param'): string` —
  static public: kollisionsfreier Named-Param-Schlüssel.
- `build_shortname_case(string $dbtype, object $user, string $tablealias, string $fieldkey, array &$params): string` —
  static private: baut `CASE`-Mapping Shortname→Profilwert.
- `get_operator_sql(string $operator, string $dbtype, ...): string` — static public:
  Dispatch auf 3 Operator-Klassen (`=`, `!=`, `~`), sonst `'FALSE'`.
- `build_profile_field_check(string $dbtype, object $user, ...&$params): string` —
  static public: Einstieg, delegiert nach Dialekt.
- `build_postgres_check(...): string` — static private: ~50-Zeilen-`CASE`-Block über
  14 Operatoren (`=,!=,<,>,~,!~,[],[!],[~],[!~],(),(!)`).
- `build_mysql_check(...): string` — static private: dito für MySQL.

Schuld: massive String-Konkatenation (`operator_builder.php:241-291`, `:314-365`),
`build_shortname_case` wird pro Operator-Zweig mehrfach re-evaluiert; vollständige
Dialekt-Duplikation; Inkonsistenz — `get_operator_sql` kennt nur 3 Operatoren,
`build_*_check` aber 14 (zwei parallele, nicht synchrone Operator-Wege).

### classes/local/sql/operators/* — `base_operator` + `equals`/`not_equals`/`contains`

`base_operator` ist trotz Dateinamens ein **Interface** (`base_operator.php:34`) mit
`get_sql()`, `get_sql_postgres()`, `get_sql_mysql()`. Die drei Implementierungen
bauen je ein CTE-basiertes Snippet, das den Userprofilwert via
`{user_info_data}`/`{user_info_field}`-Join für den aktuellen `$USER` ermittelt
und vergleicht. Methoden je Klasse: `get_sql(...)` (Dialekt-Dispatch),
`get_sql_postgres(...)`, `get_sql_mysql(...)`.

Schuld: `global $USER` direkt im SQL-Builder eingebettet (`equals.php:77/110`) —
schwer testbar, an Session-Identität gekoppelt; Namens-Mismatch
(Interface heißt `base_operator`).

### classes/utils/db.php — `utils\db`

Ad-hoc-DB-Helper außerhalb der Hauptmodelle.
- `mybookings(): array` — public: alle Optionen, in denen `$USER` gebucht ist (großes JOIN).
- `getbadges(?int $courseid=null): array` — public: aktive Badges als Menü.
- `getusersactivity($cmid, $optionid, $completed=false): array` — public: User-Diff
  Aktivitätsabschluss vs. Buchung.
- `getusersbadges($badgeid, ?int $optionid): array` — public: User mit Badge ∩ gebucht.

Schuld: lädt volle Recordsets nur um in PHP zu diffen (`db.php:108-128`); roh
formatiertes SQL; keine Tests.

### classes/utils/wb_payment.php — `utils\wb_payment`

PRO-Lizenz-Verifikation per eingebettetem RSA-Public-Key.
- `decryptlicensekey(string): string` — static: zweistufige base64/openssl-Entschlüsselung.
- `parse_license_content($decryptedcontent): array` — static: splittet
  `Y-m-d[;product]`.
- `pro_version_is_activated(): bool` — static: prüft Gültigkeit + Produkt-Token
  (`'' | bookingagent`); gibt unter Behat/PHPUnit immer `true`.

Schuld: hartcodierter Public-Key im Quelltext (`wb_payment.php:49`); Mischung aus
Kryptografie und Test-Override.

### classes/utils/webservice_import.php — `utils\webservice_import`

Import-Controller für Buchungsoptionen über Webservice (PRO-gated). Kollaborateure:
`booking`, `booking_option`, `singleton_service`, `customfield\booking_handler`,
`teachers_handler`, `wb_payment`.
- `__construct()` — public: leer (Instanz wird erst aus Daten interpretiert).
- `process_data($data): int[]` — public: Haupteinstieg, Merge-or-create.
- `update_option(&$data, $bookingoption)` — public: Update bestehender Option.
- `check_if_update_option(&$data)` — private: Erkennung Update vs. Neu.
- `return_booking_id(&$data)` — private: Auflösung Ziel-Instanz.
- `remap_data(&$data, $bookingoption)` — private: Feld-Remapping.
- `change_property(object &$data, string, string)` — static private: Property-Rename.
- `add_customfields_to_bookingoption($optionid, $data)` / `add_teacher_to_bookingoption($optionid, $data)` — private.

Schuld: durchgängige By-Reference-Mutation von `$data` (`:122/131/196/261`),
verteilte Verantwortung (Auflösung + Mapping + Persistenz in einer Klasse).

### classes/local/modechecker.php — `local\modechecker`

Erkennt den Request-Modus, um Render-/Booking-Verhalten zu steuern. Kollaborateur:
`price::return_user_to_buy_for()`. (Namespace `mod_booking\local`, Klassen-Doc-Block
fälschlich von `cartstore` kopiert — `modechecker.php:17-26`.)
- `is_ajax_or_webservice_request(): bool` — static public.
- `is_ajax_request(): bool` — static private.
- `use_special_details_page_treatment(): bool` — static public: tief verschachtelte
  Entscheidung (CLI/AJAX/`bookonlyondetailspage`/Cashier-URL).
- `is_mod_booking_bookit(): bool` / `is_load_pre_booking_page(): bool` — static public.
- `is_webservice_request(): bool` — static private.

Schuld: starke Kopplung an `$_SERVER`/`$_REQUEST`/Konstanten (`modechecker.php:52-57`);
`use_special_details_page_treatment` mit hoher zyklomatischer Komplexität
(`:98-122`); falscher Klassen-Doc-Block.

### lib.php — Core-Lifecycle & Konstanten

Mit 2941 LOC die zentrale Top-Level-Datei: ~286 globale `MOD_BOOKING_*`-Konstanten
(View-Params, Message-/Status-Params, ~110 `BO_COND_*`-IDs, ~120 `OPTION_FIELD_*`-
IDs, Header-IDs, Enrol-/Recurring-/Visibility-Enums) plus die von Moodle erwarteten
`booking_*`-Callbacks.
- `booking_get_coursemodule_info($cm)`, `booking_pluginfile(...)`,
  `booking_user_outline/complete(...)`, `booking_supports($feature)` — Core-Hooks.
- `booking_add_instance($booking)` (`lib.php:741`, ~258 LOC),
  `booking_update_instance($booking)` (`:999`, ~356 LOC),
  `booking_delete_instance($id)` (`:2407`) — Instanz-CRUD.
- `booking_extend_settings_navigation(...)` (`:1372`, ~426 LOC) — Navigations-Aufbau.
- Grading: `booking_get_user_grades`, `booking_update_grades`,
  `booking_grade_item_update/delete`, `booking_scale_used[_anywhere]`.
- Rating: `booking_rating_permissions/validate`, `booking_rate`.
- Comments: `booking_comment_permissions/validate`.
- Diverse Helfer: `booking_check_if_teacher`, `booking_activitycompletion[_teachers]`,
  `booking_pretty_duration`, `is_json`, `mod_booking_tool_certificate_fields`,
  `db_is_at_least_mariadb_106_or_mysql_8`, u. a.

Schuld: God-File — Konstanten-Registry und Verhaltenscode vermischt; mehrere
>250-LOC-Funktionen (`:741`, `:999`, `:1372`) verletzen SRP und sind kaum testbar.

### locallib.php — prozedurale Helper

- `booking_confirm_booking($optionid, $user, $cm, $url)` — Bestätigungsseite rendern.
- `booking_updatestartenddate($optionid)` — Start/Enddatum aus Optiondates
  ableiten, danach `rules_info::execute_rules_for_option`.
- `get_rendered_customfields($optiondateid)`, `get_rendered_eventdescription(...)` —
  Renderhilfen (delegieren an `bookingoption_description`-Renderer).
- `optiondate_duplicatecustomfields($old, $new)`, `booking_getoptionstatus($s, $e)`.

### settings.php — Admin-Settings-Baum

2788 LOC, kein Klassencode: baut `admin_category`/`admin_externalpage`/
`admin_settingpage` mit Dutzenden `admin_setting_*`-Einträgen. Integrationspunkt
für Subplugins: `foreach (...->get_plugins_of_type('bookingextension'))` →
`load_settings()` (`settings.php:1340-1351`). PRO-Gate über `$proversion =
wb_payment::pro_version_is_activated()` (`:190`), danach ~30 `if ($proversion)`-Blöcke.

Schuld: monolithisch, hohe Wiederholung (~30 identische PRO-Gates), keine
Strukturierung in Teil-Builder.

## Persistenz

**Schema (`db/install.xml`, 40 Tabellen)** — Hauptgruppen:
- Kern: `booking`, `booking_options`, `booking_answers`, `booking_teachers`,
  `booking_category`, `booking_tags`, `booking_other`, `booking_optiondates`,
  `booking_optiondates_teachers`, `booking_optiondates_answers`,
  `booking_customfields`, `booking_ratings`, `booking_icalsequence`,
  `booking_userevents`, `booking_history`.
- Slotbooking: `booking_slot_config`, `booking_teacher_unavailability`,
  `booking_slot_student_teacher`, `booking_slot_rule`, `booking_slot_rule_price`,
  `booking_slot_moves`.
- Preise/Semester: `booking_prices`, `booking_pricecategories`, `booking_semesters`,
  `booking_holidays`.
- Templates/Kampagnen/Rules: `booking_instancetemplate`, `booking_campaigns`,
  `booking_rules`, `booking_form_config`.
- Zertifikate: `booking_cert_cond`, `booking_cert_cond_item`.
- Subbookings/Elective/Enrollink: `booking_subbooking_options`,
  `booking_subbooking_answers`, `booking_combinations`, `booking_odt_deductions`,
  `booking_enrollink_bundles`, `booking_enrollink_items`.
- Sync/Performance: `booking_sync_rules`, `booking_sync_attempts`,
  `booking_performance_measurements`.

**Caches (`db/caches.php`, 24 Definitionen)** — u. a. `cachedbookinginstances`,
`cachedprices`, `cachedpricecategories`, `cachedsemesters`, `bookingoptionstable`,
`mybookingoptionstable` (Session), `bookingoptionsettings`, `bookingoptionsanswers`
(bewusst ohne Static-Acceleration), `bookinganswers` (Session), `bookedusertable`,
`subbookingforms`, `conditionforms`, `confirmbooking`, `customfields`, `syncrules`
u. v. m. Invalidierung über `setback*`-Events.

**Logs**: Legacy `db/log.php` (6 Aktionen) + Tabelle `booking_history`.

## Extension-Points

- **Subplugin-Typ `bookingextension`** (`db/subplugins.json`) mit Vertrag
  `bookingextension_interface` und Basisklasse `plugininfo\bookingextension`.
  Settings-Einhängung in `settings.php:1340`. Extensions können Option-Felder,
  Settings-Knoten, Optionview-Daten, Col-Actions, erlaubte Rule-Event-Keys und
  History-Beschreibungen beisteuern.
- **Event-Observer** (`db/events.php`): 35 Beobachter inkl. Wildcard `*` →
  `mod_booking_observer::execute_rule` (Rules-Engine), plus optionaler
  `shopping_cart`-Checkout-Observer (conditional registriert).
- **Webservices** (`db/services.php`): 37 externe Funktionen.
- **Shortcodes** (`db/shortcodes.php`): 14 Callbacks in `mod_booking\shortcodes`.
- **Cron-Tasks** (`db/tasks.php`): 6 scheduled tasks (`mod_booking\task\*`).
- **Message-Provider** (`db/messages.php`): `bookingconfirmation`, `sendmessages`.
- **SQL-Operator-Interface** `base_operator`: neue Vergleichsoperatoren erweiterbar.
- **PRO-Gate** `wb_payment::pro_version_is_activated()`: Feature-Schalter.

## Bekannte Schulden (→ Blueprint)

- **P1 `lib.php` God-File** (2941 LOC): Konstanten-Registry (~286 `define`) und
  Lifecycle-Code vermischt; `booking_add_instance` (~258 LOC), `booking_update_instance`
  (~356 LOC), `booking_extend_settings_navigation` (~426 LOC) sind nicht testbare
  Monolithen → Konstanten in dedizierte Klassen/Enum-Files auslagern, große
  Funktionen in Services zerlegen.
- **P2 `operator_builder`** (`operator_builder.php:241-365`): String-Konkatenations-
  Monster mit Dialekt-Duplikation und mehrfacher `build_shortname_case`-Auswertung;
  zwei nicht synchrone Operator-Wege (`get_operator_sql` 3 vs. `build_*_check` 14).
- **P2 `settings.php`** (2788 LOC): ~30-fach wiederholtes `if ($proversion)`-Gate,
  keine Modularisierung.
- **P2 `webservice_import`**: durchgängige By-Reference-Mutation, vermischte
  Verantwortlichkeiten (Auflösung/Mapping/Persistenz).
- **P3 `upgrade.php`** (5566 LOC, 1 Funktion): Append-only-Historie ab 2011 — alte
  Schritte könnten (bei sauberem Minimal-Requires-Bump) konsolidiert werden; Risiko hoch.
- **P3 `utils\db`**: In-PHP-Diffing über volle Recordsets statt SQL.
- **P3 `modechecker`**: Superglobal-Kopplung, hohe Komplexität in
  `use_special_details_page_treatment`; fehlerhafter (kopierter) Klassen-Doc-Block.
- **P3 `plugininfo\bookingextension`**: `implements bookingextension_interface` fehlt —
  Vertrag und Basisklasse entkoppelt.
- Namens-Mismatch: `base_operator` ist ein Interface, nicht eine Basisklasse.
