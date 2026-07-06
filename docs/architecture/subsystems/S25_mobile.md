# S25 — mobile

## Zweck & Grenzen

Dieses Subsystem bündelt die Integration von mod_booking in die **Moodle Mobile App (Ionic)** sowie zwei eng damit verwandte **Cache-Stores für mehrstufige Buchungs-Flows** (Prepages). Konkret umfasst der Scope:

- die **Handler-Registrierung** für die App (`db/mobile.php`),
- den **Ionic-Form-Renderer** (`mobileformbuilder`), der HTML/`<ion-*>`-Markup für das App-Frontend zusammenbaut,
- zwei **Cache-Helper** (`customformstore`, `slotbookingstore`), die Zwischenwerte von Custom-Formularen bzw. Slot-Auswahlen zwischen Prepage und finaler Buchung halten und serverseitig validieren/auswerten,
- den **Entity-Callback-Adapter** (`entities\service_provider`), der `local_entities` an `mod_booking` anbindet.

**Grenzen:** Die eigentliche App-Output-Logik (View-Rendering, WS-Endpunkte wie `mobile_course_view`, `get_submission_mobile`, `save_slot_selection`) liegt **außerhalb** dieses Scopes (`classes/output/mobile.php`, `classes/external/*`). Diese Doku beschreibt nur die im Scope liegenden Bausteine; zentrale Kollaborateure außerhalb werden in den Notes benannt. Die Einordnung von `classes/entities/service_provider.php` und der beiden Stores unter „mobile" ist primär scope-bedingt — die Stores werden auch außerhalb des App-Kontextes (Web-Prepages) genutzt.

## Position im Gesamtsystem

```
Moodle Mobile App (Ionic)
        │  (Handler-Registrierung)
        ▼
   db/mobile.php  ──delegate──▶  classes/output/mobile.php  [außerhalb Scope]
                                        │ mobile_course_view()
                                        │ get_submission_mobile (WS)
                                        ▼
                              mobileformbuilder  (rendert <ion-*>-Markup)
                                        │
                                        ▼  liest/schreibt Cache
                              customformstore / slotbookingstore
                                        │
                                        ▼  Auswertung in Buchungs-Bedingungen
        bo_availability\conditions\customform / slotbooking,
        price.php, observer.php  [außerhalb Scope]

local_entities ──service_provider callback──▶ booking::return_array_of_entity_dates
```

## Schlüsselkonzepte

- **App-Handler-Manifest (`db/mobile.php`):** Deklarativer `$addons`-Array. Registriert genau einen Handler `coursebooking` am `CoreCourseModuleDelegate` mit Methode `mobile_course_view` plus eine Liste vorzuladender Sprachstrings. Keine Logik, reine Konfiguration.
- **Ionic-Markup-Rendering (`mobileformbuilder`):** Statische Helfer, die Custom-Form-Elemente in App-taugliches HTML übersetzen — teils per Mustache-Templates (`mod_booking/mobile/ionform/*`), teils per inline zusammengesetztem String mit `core-site-plugins-call-ws`-Attributen, die das App-Frontend wieder an den WS `mod_booking_get_submission_mobile` zurückbinden.
- **Prepage-Cache-Stores:** `customformstore` und `slotbookingstore` kapseln denselben MUC-Cache `customformuserdata`, jeweils mit eigenem, aus `userid + itemid/optionid` zusammengesetztem Schlüssel-Suffix (`_customform` bzw. `_slotbooking`). Sie halten Zwischenstände einer mehrstufigen Buchung und bieten get/set/delete plus domänenspezifische Auswertung/Validierung.
- **Serverseitige Custom-Form-Validierung:** `customformstore::validation()` prüft URL/Mail/Pflichtfelder, Kontingente pro Select-Option (gegen `booking_answers`) und `enrolusersaction`-Kapazität — also fachliche Buchungsregeln, nicht nur Formatprüfung.
- **Preis-Modifikation:** `customformstore::modify_price()` verändert den Buchungspreis abhängig von Custom-Form-Eingaben (Select-Aufpreis, `enrolusersaction`-Multiplikator) inkl. Preiskategorie-Auflösung pro Nutzer.
- **Entity-Callback (`service_provider`):** Dünner Adapter, der das von `local_entities` erwartete `service_provider`-Interface implementiert und nur an `booking::return_array_of_entity_dates()` weiterleitet.

## Datenfluss

1. **App-Start:** Mobile App liest `db/mobile.php`, registriert den `coursebooking`-Handler, ruft `mobile_course_view` (in `classes/output/mobile.php`) auf.
2. **Custom-Form anzeigen:** Der Output-Handler ermittelt die Form-Elemente (`customform::return_formelements`) und ruft `mobileformbuilder::build_submission_entitites()`, das pro Element ein Ionic-Template rendert und mit `build_submission_form()` zu einem absende­fähigen `<ion-card>` zusammenbaut.
3. **Absenden:** Die App ruft per WS `mod_booking_get_submission_mobile` (außerhalb Scope) zurück; die Eingaben werden via `customformstore::set_customform_data()` in den Cache gelegt und mit `validation()` geprüft. Fehler werden per `translate_errors()` an die Form-Elemente gehängt und im nächsten Render angezeigt.
4. **Slot-Flow:** Slot-Auswahl (WS `save_slot_selection`) landet via `slotbookingstore::set_slotbooking_data()` im Cache; bei der finalen Buchung lesen Bedingungen die Werte über `get_selected_ranges()` / `get_selected_teachers_by_slot()` aus.
5. **Preis/Bedingungen:** Bei Preisberechnung und Verfügbarkeitsprüfung greifen `price.php`, `customform`- und `slotbooking`-Conditions auf die Stores zu (`return_value_for_label`, `modify_price`, Range-Parser).
6. **Aufräumen:** Nach abgeschlossener Buchung wird der Cache via `delete_*` invalidiert (Aufruf von außerhalb, u. a. `observer.php`).

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|---|---|---|---|---|---|---|
| `db/mobile.php` | — (Config) | App-Handler-Manifest (`$addons`) | 74 | 0 | A | P3 |
| `classes/local/mobile/mobileformbuilder.php` | `mobileformbuilder` | Ionic-/HTML-Renderer für Custom-Forms | 203 | 5 | C | P2 |
| `classes/local/mobile/customformstore.php` | `customformstore` | Cache-Store + Validierung + Preis-Modifikation für Custom-Forms | 346 | 11 | C | P2 |
| `classes/local/mobile/slotbookingstore.php` | `slotbookingstore` | Cache-Store + Parser für Slot-Auswahl | 193 | 7 | B | P3 |
| `classes/entities/service_provider.php` | `service_provider` | Adapter für `local_entities`-Callback | 43 | 1 | A | - |

### `mobileformbuilder` (`classes/local/mobile/mobileformbuilder.php`)

Verantwortung: Übersetzt Custom-Form-Definitionen in Ionic-App-Markup. Reiner statischer Renderer ohne Zustand; kombiniert Mustache-Templates mit handgebautem HTML-String, der WS-Rückbindungen (`core-site-plugins-call-ws`) enthält.

Kollaborateure: `$OUTPUT->render_from_template` (Templates `mod_booking/mobile/ionform/{advcheckbox,static,shorttext,select}`), `get_string`, `sesskey`. Wird gerufen von `classes/output/mobile.php` (außerhalb Scope).

Methoden-Inventar:
- `public static submission_form_submitted(): string` — liefert statisches Erfolgs-`<ion-card>` nach erfolgreichem Submit.
- `public static reset_submission_form_btn($dataglobal): string` — baut Reset-Button mit WS-Rückbindung (`reset: true`).
- `public static build_submission_form($dataglobal, $ionichtml, $resetsubmissionform): string` — umschließt gerenderte Felder mit Submit-Button (`sesskey`, `CoreUtilsProvider.objectToArrayOfObjects`-Datenbindung).
- `public static build_submission_entitites(object $formsarray, array $dataglobal)` — Hauptmethode: iteriert Form-Elemente, wählt je `formtype` das Template, ergänzt Header-/Cancel-/Submit-Strings, baut Gesamtform.
- `public static get_select_options(array $myform): array` — parst `key => value`-Zeilen eines Select-Felds in `values`-Array fürs Template.

Schulden: `build_submission_form`/`reset_submission_form_btn` enthalten **inline gepflegtes HTML/JS-Markup** (`mobileformbuilder.php:69-118`) statt Templates — schlecht testbar, fehleranfällig bei Quoting. Klassen-/PHPDoc-Header spricht fälschlich von „cartstore" (`mobileformbuilder.php:18,33`, Copy-Paste-Artefakt). `switch` ohne `default` für unbekannte `formtype` (`:145`). Keine Unit-Tests.

### `customformstore` (`classes/local/mobile/customformstore.php`)

Verantwortung: Kapselt den MUC-Cache `customformuserdata` für Custom-Form-Zwischenstände **und** beherbergt fachliche Logik: serverseitige Validierung (inkl. Kontingent-/Kapazitätsprüfung), Fehler-Übersetzung, Wert-Lookup per Label und Preis-Modifikation. Mehrere Verantwortlichkeiten in einer Klasse (Store + Validator + Pricing).

Kollaborateure: `cache::make`, `singleton_service` (booking_option_settings, booking_answers, user, pricecategory), `booking_answers::count_places`, `bo_availability\conditions\customform::return_formelements`. Genutzt von `customform`-Condition, `price.php`, `observer.php`, `book_all_students`, `customform_prefill`, `subbooking_additionalitem` (alle außerhalb Scope).

Persistenz: Cache `mod_booking/customformuserdata`, Key `"{userid}_{itemid}_customform"`.

Methoden-Inventar:
- `public __construct(int $userid, int $itemid)` — initialisiert Cache + Cache-Key.
- `public get_customform_data()` / `set_customform_data($data)` / `delete_customform_data()` — get/set/delete des Cache-Eintrags (triviale Wrapper).
- `public validation($customform, $data): array` — serverseitige Validierung: URL/Mail, Pflichtfeld (`notempty`), Select-Kontingent gegen gebuchte Nutzer, `enrolusersaction` numerisch + Restkapazität; liefert `errors`-Map.
- `public isvalidhttpurl($url)` — prüft `http(s)`-URL.
- `public translate_errors($customform, $errors)` — hängt Fehlertexte per Referenz an Form-Elemente.
- `public return_value_for_label(string $key)` — liefert vom Nutzer gesetzten Wert für ein Form-Label aus dem Cache.
- `public modify_price(float $price, string $priceidentifier): float` — passt Preis nach Select-Aufpreis / `enrolusersaction`-Faktor an.
- `public get_price_and_currency_for_user(string $pricedata): string` — formatiert Preis+Währung.
- `private get_price_for_user(string $pricedata, string $priceidentifier = ""): float` — löst kategorie-/nutzerspezifischen Preis aus `key:value,...`-String auf.

Schulden: **Multi-Responsibility** (Cache + Validierung + Pricing in einer Klasse, `customformstore.php:45-346`). `validation()` ist mit ~77 Zeilen und tiefer Verschachtelung (`:101-178`) komplex und mischt String-Parsing (`explode(' => ')`) mit DB-Abfragen via Singletons. Fragiles **konventionsbasiertes String-Parsing** des Select-`value` (`:118-121`, `:262-270`) — Spalten-Semantik (`[2]`=Limit, `[3]`=Preis) implizit. Klassen-/PHPDoc spricht fälschlich von „cartstore" (`:18,33`). `$priceidentifier`-Parameter in `modify_price` ungenutzt (`:246`). Keine Unit-Tests im Scope.

### `slotbookingstore` (`classes/local/mobile/slotbookingstore.php`)

Verantwortung: Cache-Store für Slot-Auswahl-Daten zwischen Prepage und finaler Buchung; zusätzlich Parser, der die als String/JSON gehaltenen Auswahlen in Timestamp-Ranges bzw. Lehrer-Zuordnungen pro Slot übersetzt. Klarer als `customformstore`, da nur Store + Parsing.

Kollaborateure: `cache::make`, `$USER` (Normalisierung userid=0 → aktueller Nutzer). Genutzt von `slotbooking`-Condition und `save_slot_selection` (außerhalb Scope).

Persistenz: Cache `mod_booking/customformuserdata`, Key `"{userid}_{optionid}_slotbooking"`.

Methoden-Inventar:
- `public __construct(int $userid, int $optionid)` — normalisiert userid (0 → `$USER->id`), baut Cache + Key.
- `public get_slotbooking_data()` / `set_slotbooking_data(object $data)` / `delete_slotbooking_data()` — get/set/delete (triviale Wrapper).
- `public get_selected_range($data): array` — liefert erste `[start,end]`-Range (Bequemlichkeits-Wrapper über `get_selected_ranges`).
- `public get_selected_ranges($data): array` — parst kommaseparierten `start:end`-String in validierte Timestamp-Range-Liste.
- `public get_selected_teachers_by_slot($data): array` — dekodiert JSON `slot_teacher_selection` in `{slotkey: [teacherids]}`, bereinigt/dedupliziert IDs.

Schulden: Parser akzeptiert sowohl `object` als auch `array` (`:121-125,163-167`) — leichte Duplikation, Folge eines uneinheitlichen Cache-Payload-Vertrags. Eigenständiger Store für denselben Cache wie `customformstore` (überlappende Verantwortung, kein gemeinsamer Basistyp). Keine Unit-Tests im Scope.

### `service_provider` (`classes/entities/service_provider.php`)

Verantwortung: Implementiert das von `local_entities` definierte `service_provider`-Callback-Interface und delegiert die einzige Methode an `mod_booking\booking`. Reiner Adapter / Anti-Corruption-Layer.

Kollaborateure: `local_entities\local\callback\service_provider` (Interface), `mod_booking\booking::return_array_of_entity_dates`.

Methoden-Inventar:
- `public static return_array_of_entity_dates(array $areas): array` — leitet an `booking::return_array_of_entity_dates($areas)` weiter.

Schulden: keine nennenswerten; minimal und korrekt. Kopplung an `local_entities` ist gewollt (Extension-Point).

## Persistenz

- **MUC-Cache `mod_booking/customformuserdata`** (gemeinsam von `customformstore` und `slotbookingstore` genutzt, Definition in `db/caches.php`, außerhalb Scope):
  - Key `"{userid}_{itemid}_customform"` → serialisiertes Custom-Form-Daten-Objekt.
  - Key `"{userid}_{optionid}_slotbooking"` → serialisiertes Slot-Auswahl-Objekt (`slot_selection`-String, `slot_teacher_selection`-JSON).
- Keine direkten DB-Schreibzugriffe in diesem Subsystem; Validierung liest indirekt über `singleton_service`/`booking_answers` aus den Buchungs-Tabellen.

## Extension-Points

- **App-Handler (`db/mobile.php`):** `$addons['mod_booking']['handlers']` ist der offizielle Moodle-Mobile-Erweiterungspunkt; weitere Delegates/Handler könnten hier registriert werden.
- **`entities\service_provider`:** implementiert das `local_entities`-Callback-Interface — der Anbindungspunkt für die Entities-Integration (Orte/Ressourcen).
- **Ionic-Templates:** `mobileformbuilder` rendert über austauschbare Mustache-Templates `mod_booking/mobile/ionform/*` (außerhalb Scope); neue `formtype`-Renderer würden hier ergänzt.

## Bekannte Schulden (→ Blueprint)

- **`customformstore` Multi-Responsibility (P2):** Cache-Store, fachliche Validierung und Preis-Logik in einer Klasse (`customformstore.php:45-346`). Aufteilen in `customform_cache`, `customform_validator`, `customform_pricing`.
- **Inline-HTML/JS in `mobileformbuilder` (P2):** WS-rückgebundenes Markup als String zusammengebaut (`mobileformbuilder.php:69-118`) statt durchgängig per Template — schwer testbar, Quoting-anfällig.
- **Fragiles String-Parsing der Select-Werte (P2):** implizite Spalten-Semantik `linearray[0..3]` (`customformstore.php:118-131,262-270`) ohne definiertes DTO — schwer wartbar, fehleranfällig.
- **Copy-Paste-PHPDoc „cartstore" (P3):** falsche Klassenbeschreibungen in `mobileformbuilder.php:18,33` und `customformstore.php:18,33`.
- **Zwei separate Stores auf demselben Cache (P3):** `customformstore`/`slotbookingstore` ohne gemeinsamen Basistyp — Konsolidierung/abstrakter Prepage-Store denkbar.
- **Ungenutzter Parameter (P3):** `modify_price($price, $priceidentifier)` nutzt `$priceidentifier` nicht (`customformstore.php:246`).
- **Fehlende Unit-Tests (P2):** keine Tests im Scope für Validierungs-, Parser- und Preis-Logik, obwohl diese fachlich kritisch sind.
