# S11 — external_api

## Zweck & Grenzen

Das Subsystem bündelt alle **Moodle-Webservice-Endpunkte** des Plugins `mod_booking`.
Jede Klasse unter `classes/external/` erweitert `external_api` (bzw. das namespaced
`core_external\external_api` bei den neueren Slot-Services) und liefert das klassische
Dreigespann `execute_parameters()` / `execute()` / `execute_returns()`. Die Klassen sind die
**dünne Transport- und Validierungsschicht** zwischen AJAX/Mobile-Clients und der
Domänenlogik (`booking_option`, `booking_bookit`, `bo_info`, `singleton_service`,
`coursecategories`, `slot_*`, `optionformconfig_info`, `performance_*` …).

Grenzen:
- **Geschäftslogik gehört NICHT hierher**; sie soll an die jeweiligen Domänen-/Service-Klassen
  delegiert werden. Im Ist-Zustand ist das überwiegend so, aber mehrere Endpunkte
  (`bookings`, `addbookingoption`, `save_slot_selection`, `get_submission_mobile`) tragen
  noch nennenswerte Logik bzw. Datenaufbereitung in sich.
- Die Registrierung der Funktionen erfolgt in **`db/services.php`** (31 Funktions-Definitionen,
  1:1 auf die 31 Klassen abgebildet). Diese Datei liegt außerhalb des Scopes, ist aber der
  zwingende Gegenpart.

## Position im Gesamtsystem

```
AJAX / Mobile-App / externe Integration
        │  (mod_booking_<funktion>)
        ▼
db/services.php  ──► classes/external/<klasse>::execute()
        │
        ├─ validate_parameters() / validate_context() / require_capability()
        ▼
Domänenschicht:
  booking_bookit, bo_info, booking_option, booking,
  singleton_service, coursecategories, webservice_import,
  optionformconfig_info, performance_facade/renderer,
  slot_dto / slot_availability / slot_price / slot_mover / slotbookingstore,
  shopping_cart (local_shopping_cart, optional)
        ▼
DB-Tabellen / MUC-Caches / Mustache-Templates (clientseitig gerendert)
```

Die Endpunkte sind die **einzige offizielle Programmierschnittstelle** des Plugins. Das
Service `Booking module API` (services.php:254) exportiert zusätzlich `mod_booking_bookings`
und `mod_booking_categories` als benanntes externes Service (Kommentar warnt: nicht umbenennen,
sonst bricht `local_bookingapi`).

## Schlüsselkonzepte

- **Stateless statische Klassen**: Alle Methoden sind `public static`. Kein Zustand, keine
  Vererbungshierarchie über `external_api` hinaus.
- **Parameter-/Rückgabe-Verträge** via `external_function_parameters`,
  `external_single_structure`, `external_multiple_structure`, `external_value`.
- **JSON-in-PARAM_RAW-Muster**: Viele Endpunkte transportieren strukturierte Nutzdaten als
  JSON-String in `PARAM_RAW`-Feldern (`json`, `template`, `slots`, `meta`, `errors`,
  `selection`, `teacherselection`). Das umgeht die Moodle-Strukturvalidierung bewusst und
  verlagert Parsing auf Client/Domäne.
- **Render-Daten-Vertrag**: `bookit`, `load_pre_booking_page`, `get_booking_option_description`
  liefern `template` + `json`, das clientseitige AMD-Module dann mit Mustache rendern.
- **Zwei Generationen**: Ältere Klassen nutzen die globalen Klassennamen (`external_api`,
  `external_value`, …) + `require_once($CFG->libdir.'/externallib.php')`. Die 2026er
  Slot-Services (`get_slots`, `get_booked_slots`, `save_slot_selection`, `release_slots`)
  nutzen die namespaced `core_external\*`-Variante und verzichten auf `MOODLE_INTERNAL`-Guard.
- **Uneinheitliche Berechtigungsprüfung**: Manche Endpunkte prüfen sauber Kontext + Capability
  (`get_slots`, `get_booked_slots`, `save_slot_selection`, `search_sync_sources`,
  `save_measurement`, `update_bookingnotes`, `addbookingoption`, `save_option_field_config`,
  `search_users`), andere prüfen **gar nichts** (`optiontemplate`, `search_courses`,
  `search_booking_options`, `search_teachers`, `search_templates`, `get_option_field_config`,
  `categories`, `bookings` nur teilweise).

## Datenfluss

Beispiel `bookit` (Buchen): Client ruft `mod_booking_bookit(area,itemid,userid,data)` →
`bookit::execute()` validiert Parameter, `require_login()`, delegiert an
`booking_bookit::bookit()`, ermittelt Settings (Option oder Subbooking), rendert via
`booking_bookit::render_bookit_template_data()` neue Button-Templatedaten, **invalidiert
manuell** den `bookingoptionsanswers`-MUC-Cache für den User und liefert
`status/message/template/json`.

Beispiel `addbookingoption` (Import/Upsert): 47-Parameter-Signatur → Capability-Check
(`mod/booking:updatebooking`, Kontext je nach `bookingoptionid`/`bookingcmid`/System) →
`validate_parameters` → `array_filter` (Null-Werte entfernen) → `webservice_import::process_data()`.

Beispiel Slotbooking: `get_slots` liest selektierbare Slots (`slot_dto`), `save_slot_selection`
validiert Auswahl serverseitig (`slot_availability`, `slot_price`) und cached sie
(`slotbookingstore`), `release_slots` storniert einzelne Slots (`slot_mover`), `get_booked_slots`
liefert Reportdaten.

## Dateien & Klassen

Alle Pfade relativ zu `mod/booking`. Alle Klassen liegen in `classes/external/`, Namespace
`mod_booking\external`, alle Methoden `public static`. „Methoden" = inkl. der drei
Standardmethoden.

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| addbookingoption.php | addbookingoption | WS / Import-Upsert | 533 | 3 | C | P2 |
| bookings.php | bookings | WS / Read-Aggregat | 303 | 3 | C | P2 |
| get_submission_mobile.php | get_submission_mobile | WS / Mobile-Form-Cache | 226 | 5 | C | P2 |
| save_slot_selection.php | save_slot_selection | WS / Slot-Validierung | 183 | 4 | C | P3 |
| categories.php | categories (+ globale fn) | WS / Read-Kategorien | 151 | 3 | C | P2 |
| bookit.php | bookit | WS / Buchung | 142 | 3 | B | P3 |
| get_parent_categories.php | get_parent_categories | WS / Read-Coursecat | 142 | 3 | C | P3 |
| load_pre_booking_page.php | load_pre_booking_page | WS / Pre-Booking-Page | 126 | 3 | B | P3 |
| delete_measurement.php | delete_measurement | WS / Performance-Delete | 124 | 3 | C | P2 |
| search_sync_sources.php | search_sync_sources | WS / Cohort/Group-Suche | 125 | 3 | B | - |
| get_booking_option_description.php | get_booking_option_description | WS / Option-Beschreibung | 119 | 3 | B | P3 |
| update_bookingnotes.php | update_bookingnotes | WS / Notiz-Update | 119 | 3 | B | P3 |
| save_option_field_config.php | save_option_field_config | WS / Optionform-Config-Save | 116 | 3 | B | P3 |
| set_checked_booking_instance.php | set_checked_booking_instance | WS / urise-Helper | 114 | 3 | C | P3 |
| release_slots.php | release_slots | WS / Slot-Storno | 109 | 3 | B | - |
| save_measurement.php | save_measurement | WS / Performance-Note-Save | 109 | 3 | B | P3 |
| allow_add_item_to_cart.php | allow_add_item_to_cart | WS / Cart-Gate | 108 | 3 | B | - |
| get_option_field_config.php | get_option_field_config | WS / Optionform-Config-Read | 97 | 3 | C | P3 |
| toggle_notify_user.php | toggle_notify_user | WS / Warteliste-Notify | 97 | 3 | B | - |
| instancetemplate.php | instancetemplate | WS / Instanz-Template-Read | 96 | 3 | C | P3 |
| search_users.php | search_users | WS / User-Suche | 96 | 3 | B | P3 |
| get_slots.php | get_slots | WS / Slot-Picker-Read | 92 | 3 | B | - |
| performance.php | performance | WS / Performance-Dispatch | 92 | 3 | B | - |
| optiontemplate.php | optiontemplate | WS / Option-Template-Read | 91 | 3 | C | P2 |
| get_booked_slots.php | get_booked_slots | WS / Slot-Report-Read | 88 | 3 | B | - |
| init_comments.php | init_comments | WS / Comment-Init | 88 | 3 | B | - |
| search_booking_options.php | search_booking_options | WS / Optionssuche | 93 | 3 | C | P2 |
| search_teachers.php | search_teachers | WS / Teacher-Suche | 86 | 3 | C | P3 |
| search_templates.php | search_templates | WS / Template-Suche | 86 | 3 | C | P3 |
| search_courses.php | search_courses | WS / Kurssuche | 83 | 3 | C | P2 |
| get_performance_chart.php | get_performance_chart | WS / Chart-Read | 82 | 3 | B | - |

### addbookingoption
Verantwortung: Erstellt oder aktualisiert (`bookingoptionid` gesetzt) eine Buchungsoption per
Webservice. Kollaborateure: `singleton_service`, `webservice_import`, `context_module/system`.
Persistenz: indirekt über `webservice_import` (booking_options u.a.).
- `execute_parameters(): external_function_parameters` — definiert 47 Eingabefelder (Zeilen 57–337).
- `execute(string $name, string $identifier, …47 Params…): array` — Capability-Check je nach
  vorhandenem `bookingoptionid`/`bookingcmid`/System (`addbookingoption.php:442-458`),
  Parametervalidierung, Null-Filter, Delegation an `webservice_import::process_data()`.
- `execute_returns(): external_single_structure` — nur `status:bool`.

### bookings
Verantwortung: Liefert pro `booking`-Instanz eines Kurses ein verschachteltes Aggregat
(Instanz-Metadaten, Kategorien, Optionen, Teilnehmer, Teacher, Sessions) für externe API.
Kollaborateure: `singleton_service` (booking/option_settings/booking_answers/user), `$DB`,
`external_util`/`external_files`. Persistenz: liest `booking`, `booking_category`, `user`.
- `execute_parameters()` — `courseid/printusers/days` (alle PARAM_TEXT).
- `execute($courseid,$printusers,$days): array` — verschachtelte Schleifen über Instanzen →
  Optionen → User/Teacher/Sessions (`bookings.php:90-226`); Sichtbarkeits-/`showinapi`-Gate,
  `apply_tags`, Bulk-Load der Kategorienamen (`bookings.php:149`).
- `execute_returns(): external_multiple_structure` — großes geschachteltes Strukturschema.

### get_submission_mobile
Verantwortung: Speichert/merged/reset Custom-Form-Daten der Mobile-App im MUC-Cache
`customformuserdata`. Kollaborateure: `cache`. Persistenz: MUC-Cache (kein DB).
- `execute_parameters()` — `itemid,userid,sessionkey,reset,data[]`.
- `execute($itemid,$userid,$sessionkey,$reset,$data): array` — try/catch gibt Fehler als
  String-Payload zurück; Reset löscht Cachekey, sonst Merge + Set.
- `merge_data($cacheddata,$data,$itemid,$userid): array` — verschmilzt neue mit alten Feldern.
- `build_formdata_string($itemid,$userid,$sesskey,$data): string` — baut urlencoded Formstring;
  wirkt **ungenutzt** (kein interner/externer Aufrufer im Scope erkennbar).
- `execute_returns()` — `submitted/message/template/json`.
Schuld: keine sichtbare `sessionkey`-Verifikation trotz Parameter; if/else-Zweige in
`build_formdata_string` (Zeilen 216-222) identisch (toter Zweig).

### save_slot_selection
Verantwortung: Validiert serverseitig eine Slot-Auswahl (Max-Slots, Teacher-Pflicht,
Verfügbarkeit), berechnet Preis, persistiert gültige Auswahl in den Slot-Store.
Kollaborateure: `singleton_service`, `slot_availability`, `slot_price`, `slotbookingstore`.
- `execute_parameters()` — `optionid,userid,selection,teacherselection`.
- `execute(int $optionid,int $userid,string $selection,string $teacherselection): array` —
  Kontext-/Capability-Check (`conditionforms`), Validierungsschleife (`save_slot_selection.php:99-144`).
- `normalise_keys(string): array` (private) — dedupliziert/säubert Slot-Keys.
- `execute_returns()` — `valid/errors/price`.
Schuld: ~45 Zeilen Validierungs-Geschäftslogik in `execute()` — gehört in einen Service.

### categories
Verantwortung: Liefert (Top-)Kategorien eines Kurses. Datei enthält zusätzlich die **globale
Funktion** `mod_booking_showsubcategories()` (rekursiv). Kollaborateure: `$DB`.
- globale fn `mod_booking_showsubcategories($catid,$DB,$courseid)` — rekursive Subkategorie-Sammlung.
- `execute($courseid)`, `execute_parameters()`, `execute_returns()`.
Schuld: globale Funktion in Klassendatei (categories.php:49); toter Zweig
`if (count((array)$subcategories) < 0)` (categories.php:117) — nie wahr.

### bookit
Verantwortung: Bucht Option/Subbooking, rendert neue Button-Templatedaten, invalidiert
User-Cache. Kollaborateure: `booking_bookit`, `subbookings_info`, `price`, `singleton_service`,
`cache`. Persistenz: MUC `bookingoptionsanswers`.
- `execute(string $area,int $itemid,int $userid,string $data)` — Delegation +
  manuelle Cache-Invalidierung (`bookit.php:108-119`).
Schuld: direkte MUC-Cache-Manipulation in der WS-Schicht (gehört in Domäne).

### get_parent_categories
Verantwortung: Liefert (für Dashboard/Shopping-Cart-Kontext) Coursecat-Knoten; aktuell für
`coursecategoryid==0` nur einen statischen Summary-Knoten, sonst leer.
Kollaborateure: `coursecategories::return_course_categories`.
Schuld: deklariert/initialisiert fünf Zählervariablen (get_parent_categories.php:83-87), die nie
verwendet werden; aufwendiges Rückgabeschema vs. fast leere Logik (Stub/Halbfertig).

### load_pre_booking_page
Verantwortung: Lädt eine Pre-Booking-Page (Modal-Step). Delegation an
`bo_info::load_pre_booking_page`. Dünner Pass-Through.

### delete_measurement
Verantwortung: Löscht Performance-Messpunkt(e). Kollaborateure: `$DB`,
`performance_renderer::TABLE`, `cache_helper`. Capability `mod/booking:editperformance`.
Schuld: **Bug** `compact('measurementid', 'note')` (delete_measurement.php:73) referenziert
undefinierte Variable `$note` (kein `note`-Parameter) → Notice/leerer Wert;
`cache_helper::purge_all()` (Zeile 107) ist eine globale Purge für eine Einzeloperation.

### search_sync_sources
Verantwortung: Sucht Cohorts/Groups für den Sync-Regel-Modal-Selector. Kontext-Validierung +
`require_capability('mod/booking:bookforothers')`, parametrisiertes `sql_like`. Sauberster Endpunkt.

### get_booking_option_description
Verantwortung: Liefert JSON-Renderdaten der Optionsbeschreibung (`bookingoption_description`)
+ Templatename. Kollaborateure: `singleton_service`, `bookingoption_description`.

### update_bookingnotes
Verantwortung: Aktualisiert `notes` in `booking_answers`. Capability `mod/booking:updatenotes`
am Optionskontext. Kollaborateure: `$DB`, `singleton_service`. Persistenz: `booking_answers`.

### save_option_field_config / get_option_field_config
Verantwortung: Lesen/Schreiben der Optionformular-Feldkonfiguration je Kontext/Capability.
Kollaborateure: `optionformconfig_info`. `save_*` prüft `mod/booking:editoptionformconfig`;
`get_*` prüft **keine** Capability (Schuld: get_option_field_config.php:73-81).

### set_checked_booking_instance
Verantwortung: Helper für Fremdprojekt `local_urise` (markiert konfigurierte Booking-Instanzen).
Kollaborateure: `coursecategories`, `singleton_service`. No-op falls `local_urise` fehlt.
Schuld: Rückgabeschlüssel-Tippfehler `'successs'` (set_checked_booking_instance.php:98,110).

### release_slots
Verantwortung: Self-Service-Teilstorno gebuchter Slots. Delegation an `slot_mover::release_self`.
Kontext-Validierung vorhanden, aber **keine** explizite Capability-Prüfung (Selbstbedienung
über `baid`-Eigentum vorausgesetzt).

### save_measurement
Verantwortung: Speichert Notiz zu Performance-Messpunkt. Capability `mod/booking:editperformance`.
Persistenz: `performance_renderer::TABLE`. Schuld: `cache_helper::purge_all()` (Zeile 90).

### allow_add_item_to_cart
Verantwortung: Prüft, ob eine Option in den Warenkorb gelegt werden darf; kurzschließt bei
preislosen Optionen und wenn `local_shopping_cart` fehlt. Kollaborateure: `singleton_service`,
optional `local_shopping_cart\shopping_cart`. Gut isoliert gegen fehlendes Optional-Plugin.

### toggle_notify_user
Verantwortung: Schaltet „Benachrichtige mich" (Warteliste) um. Delegation an
`booking_option::toggle_notify_user`.

### instancetemplate / optiontemplate
Verantwortung: Lesen ein Instanz- bzw. Options-Template als JSON. `instancetemplate` prüft
`permissions::has_capability_anywhere()`; `optiontemplate` prüft **keine** Berechtigung und gibt
den kompletten `booking_options`-Datensatz als JSON zurück (Schuld: optiontemplate.php:65-76).
`instancetemplate` greift ohne Null-Guard auf `$template->name` zu trotz `IGNORE_MISSING`.

### search_users / search_booking_options / search_teachers / search_templates / search_courses
Verantwortung: Autocomplete-Suchen, delegieren an `booking::load_*` bzw.
`booking_option::load_booking_options_filtered` / `connectedcourse::return_tagged_template_courses`.
`search_users` prüft `has_capability_anywhere(...)` (gut), enthält aber **toten Code** nach dem
throw (search_users.php:74). `search_booking_options`, `search_courses`, `search_teachers`,
`search_templates` prüfen **keine** Capability — potentielle Daten-Exposition (Schuld).

### get_slots / get_booked_slots
Verantwortung: Liefern Picker-Slots+Meta bzw. Slot-Report als JSON (`slot_dto`).
Beide validieren Kontext + Capability (`conditionforms` bzw. `mod/booking:view`). Saubere
neue Generation (`core_external\*`).

### performance / get_performance_chart
Verantwortung: Dispatch von Performance-Aktionen (`performance_facade::execute`) bzw. Chartdaten
(`performance_renderer::get_chart`). Dünne Delegates. `performance` prüft keine Capability auf
WS-Ebene (Facade-abhängig).

### init_comments
Verantwortung: Initialisiert die Moodle-Kommentar-Engine (`comment::init()`), parameterlos.

## Persistenz

- **DB-Tabellen (Lesen/Schreiben über Domäne oder direkt via `$DB`)**: `booking`,
  `booking_options`, `booking_category`, `booking_answers`, `booking_instancetemplate`,
  `user`, `cohort`, `groups`, `performance_renderer::TABLE` (booking_performance_measurements),
  sowie alle von `webservice_import` / `booking_bookit` / `slot_*` berührten Tabellen.
- **MUC-Caches**: `mod_booking/customformuserdata` (get_submission_mobile),
  `mod_booking/bookingoptionsanswers` (bookit, manuelle Invalidierung),
  Slot-Store-Cache via `slotbookingstore`. `save_measurement`/`delete_measurement` rufen
  `cache_helper::purge_all()` (global, grob).
- **Templates** werden nicht serverseitig gerendert, sondern als Name + JSON an den Client geliefert.

## Extension-Points

- **`db/services.php`** (außerhalb Scope) ist der Registrierungs-Extension-Point; das benannte
  Service `Booking module API` (mod_booking_bookings, mod_booking_categories) ist externer
  Vertrag für `local_bookingapi` — nicht umbenennen.
- **Optionale Fremd-Plugins**: `local_shopping_cart` (allow_add_item_to_cart) und `local_urise`
  (set_checked_booking_instance) werden via `class_exists`-Guard lose gekoppelt.
- Keine eigenen Interfaces/Hooks; Erweiterung erfolgt durch Hinzufügen weiterer `external_api`-Klassen.

## Bekannte Schulden (→ Blueprint)

- **Uneinheitliche/fehlende Autorisierung** (Sicherheit, P2): `optiontemplate` (voller Record ohne
  Check), `search_courses`, `search_booking_options`, `search_teachers`, `search_templates`,
  `get_option_field_config` prüfen keine Capability. → konsistentes Auth-Pattern (Kontext +
  Capability) für alle Read-Endpunkte definieren.
- **delete_measurement.php:73 Bug**: `compact('measurementid', 'note')` mit undefinierter
  Variable `$note`.
- **Globale Cache-Purges**: `save_measurement.php:90`, `delete_measurement.php:107`
  (`cache_helper::purge_all()` für Einzeloperationen) — zu grob.
- **WS-Schicht mit Geschäftslogik**: `addbookingoption` (47-Param-Signatur + Param-Schema 280
  Zeilen), `bookings` (tief verschachteltes Read-Aggregat, ~150 Zeilen execute),
  `save_slot_selection` (Validierungslogik gehört in Service), `get_submission_mobile`
  (Cache-Merge-Logik + toter `build_formdata_string`/identische if-Zweige), `bookit`
  (manuelle MUC-Invalidierung).
- **Toter/Stub-Code**: `search_users.php:74` (unreachable return), `categories.php:117`
  (`count(...) < 0` nie wahr) + globale Funktion in Klassendatei, `get_parent_categories`
  (ungenutzte Zähler / fast leere Logik), `get_submission_mobile.php:216-222` (identische Zweige).
- **Tippfehler im Vertrag**: `set_checked_booking_instance` Rückgabeschlüssel `'successs'`.
- **Fehlende Null-Guards**: `instancetemplate.php:79`/`optiontemplate.php:73` greifen nach
  `IGNORE_MISSING` ungeprüft auf Felder zu.
- **Zwei Coding-Generationen** (global vs. `core_external\*`-Namespace, mit/ohne
  `MOODLE_INTERNAL`-Guard) — Vereinheitlichung sinnvoll.
</content>
</invoke>
