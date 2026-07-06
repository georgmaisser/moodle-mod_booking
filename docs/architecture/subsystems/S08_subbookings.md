# S08 — subbookings

## Zweck & Grenzen

Das Subsystem `subbookings` modelliert **Zusatzbuchungen** zu einer Buchungsoption: Dinge,
die zusätzlich zur eigentlichen Option gebucht werden können (z. B. zusätzliche Personen,
Zusatz-Artikel, oder die zeitfensterbasierte Reservierung einer Option). Eine Buchungsoption
kann beliebig viele konfigurierte Subbookings tragen; jeder Subbooking-Typ entscheidet selbst,
wie er sich im Formular konfiguriert, im Buchungsprozess gerendert wird, den Preis beeinflusst
und ob er die Hauptbuchung **blockiert** (also eine eigene Prepage im Buchungsfluss erzwingt).

Grenzen:
- Die Engine kennt zwei DB-Tabellen: `booking_subbooking_options` (Konfiguration je Option)
  und `booking_subbooking_answers` (Antworten/Reservierungen je User).
- Das Subsystem liefert nur Daten + Templatenamen für das Rendering; das eigentliche Rendering
  (`output\subbooking_*_output`, Mustache `mod_booking/subbooking/*`) liegt im Renderer-Subsystem.
- Preis, Entitäten und Customform werden aus Fremd-Subsystemen gezogen
  (`price`, `local_entities\entitiesrelation_handler`, `bo_availability\conditions\customform`).
- Aktuell sind im UI **nur** `subbooking_additionalperson` und `subbooking_additionalitem`
  aktiviert; `subbooking_timeslot` ist implementiert, aber per Whitelist deaktiviert
  (`subbookings_info.php:91`).

Das Feature steht nur in der PRO-Version und nur bei aktivierter Config `showsubbookings`
zur Verfügung (`subbookings_info.php:58`).

## Position im Gesamtsystem

```
booking_option / booking_option_settings
        │  settings->subbookings = subbookings_info::load_subbookings()
        ▼
subbookings_info  ──(Factory)──►  sb_types\subbooking_*  (implements booking_subbooking)
        │                                   │
        │ save/delete/load/save_response    │ return_interface ──► output\subbooking_*_output ──► Mustache
        ▼                                   │ return_price      ──► price
booking_subbooking_options (Config)         │ Entitäten         ──► entitiesrelation_handler
booking_subbooking_answers  (Answers)       │ Customform-Link   ──► bo_availability\conditions\customform
```

- **Einbindung Optionsformular:** `subbookings_info::add_subbookings_to_mform()` wird vom
  Option-Editform aufgerufen, listet bestehende Subbookings (Renderer `subbookingslist`) und
  bindet den Add-Dialog ein.
- **Einbindung Buchungsfluss:** Eine `bo_condition` (Availability-Subsystem) wertet
  `subbookings_info::is_blocked()` / `has_soft_subbookings()` aus und ruft `return_interface()`
  des jeweiligen Typs, um die Subbooking-Prepage zu rendern.
- **Einbindung shopping_cart:** `return_array_of_subbookings()` liefert area/itemid-Paare zum
  Entladen aus dem Warenkorb; `save_response()` ist der zentrale Status-Übergang
  (reserved → booked → deleted) und wird vom Warenkorb-Callback getriggert.

## Schlüsselkonzepte

- **booking_subbooking (Interface):** Vertrag jedes Subbooking-Typs. Lifecycle in drei Phasen:
  (1) Konfiguration im Optionsformular (`add_subbooking_to_mform`, `save_subbooking`,
  `set_defaults`), (2) Hydration aus DB/JSON (`set_subbookingdata`, `set_subbookingdata_from_json`),
  (3) Laufzeit/Buchung (`return_interface`, `return_price`, `is_blocking`,
  `return_subbooking_information`, `return_answer_json`, plus die drei Action-Hooks).
- **JSON-getriebene Persistenz:** Die Spalten von `booking_subbooking_options` halten nur
  generische Felder (`name`, `type`, `optionid`, `block`); der typ-spezifische Zustand lebt in
  `json` (z. B. `duration`, `slots`, `description`, `subbookingadditemformlink`).
- **block vs. soft:** `block=1` (nur `timeslot` default) erzwingt eine blockierende Prepage;
  `block=0`-Subbookings sind „soft" und melden via `is_blocking()` selbst, ob sie angezeigt
  werden müssen (z. B. abhängig von Customform-Werten oder waitforconfirmation).
- **„Last item rendert alles":** Bei mehreren Subbookings desselben Typs rendert nur das letzte
  Element das gemeinsame Interface (`return_interface()` filtert via `array_filter`/`end`).
- **itemid-Semantik:** Bei den meisten Typen ist `itemid == subbookingoptionid`. Bei `timeslot`
  ist die `itemid` ein **Slot-Index**, und die Subbooking-ID wird in das `area`-Feld gepackt
  (`'subbooking-' . $this->id`), siehe `get_subbooking_by_area_and_id()`.
- **Status-Maschine:** `save_response()` setzt die `status`-Spalte der Answers über
  `MOD_BOOKING_STATUSPARAM_*` (BOOKED/WAITINGLIST/RESERVED/NOTBOOKED/DELETED) und ruft die
  passenden Action-Hooks.

## Datenfluss

**Konfiguration (Admin/Trainer):**
1. `add_subbookings_to_mform()` → `add_list_of_existing_subbookings_for_this_option()` rendert
   Liste; Add-Dialog ruft `add_subbooking()` → Typ-`add_subbooking_to_mform()`.
2. Speichern: `save_subbooking()` (statisch) → Typ-`save_subbooking()` schreibt
   `booking_subbooking_options` (+ `price::save_from_form`, ggf. Entitäten + Editor-Files) →
   `booking_option::trigger_updated_event(... 'subbookings')` invalidiert Caches.

**Laden für eine Option:**
- `load_subbookings($optionid)` liest alle Config-Records, instanziiert je Typ und hydratisiert
  via `set_subbookingdata()`. Das Ergebnis hängt an `booking_option_settings->subbookings`.

**Buchungsprozess (User):**
1. Availability-Condition prüft `is_blocked()` / `has_soft_subbookings()`.
2. `return_interface()` des letzten Subbookings liefert `[output-DTO, templatename]`.
3. User wählt → Warenkorb → `save_response($area, $itemid, $status, $userid)`:
   - `get_subbooking_by_area_and_id()` rekonstruiert den Subbooking aus area/itemid.
   - `update_or_insert_answer()` macht den Status-Übergang in `booking_subbooking_answers`.
   - Je nach Status werden `after_booking_action` / `reservation_action` /
     `reservation_deletion_action` aufgerufen (z. B. `additionalperson` schreibt `places` in
     `booking_answers`).

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| classes/subbookings.php | `mod_booking\subbookings` | Domänenobjekt (veraltet/teilredundant) | 98 | 2 | D | P2 |
| classes/subbookings/booking_subbooking.php | `booking_subbooking` (Interface) | Extension-Point | 169 | 16 (Decl.) | B | - |
| classes/subbookings/subbookings_info.php | `subbookings_info` | Service / Factory / Status-Maschine | 613 | 14 | C | P2 |
| classes/subbookings/subbookings_cache.php | `subbookings_cache` | Cache-Marker (leer) | 36 | 0 | D | P3 |
| classes/subbookings/sb_types/subbooking_additionalperson.php | `subbooking_additionalperson` | sb_type | 529 | 14 | C | P2 |
| classes/subbookings/sb_types/subbooking_additionalitem.php | `subbooking_additionalitem` | sb_type | 480 | 14 | C | P2 |
| classes/subbookings/sb_types/subbooking_timeslot.php | `subbooking_timeslot` | sb_type | 545 | 16 | C | P2 |

### `mod_booking\subbookings` (classes/subbookings.php)

Verantwortung: Vermeintliches Aggregat „Subbookings einer Option". **Wirkt unfertig/verwaist**:
Der Konstruktor lädt den Cache, verwirft das Ergebnis aber sofort (Dead Code, `subbookings.php:50-55`).
Kollaborateure: nur `\cache` + `$DB`. Persistenz: schreibt direkt in `booking_subbooking_answers`.

Methoden-Inventar:
- `public __construct(int $optionid)` — initialisiert `$id` nicht, holt Cache und verwirft ihn (No-op).
- `public user_submit_response(int $userid, int $sboid, string $json='', int $timestart=0, int $timeend=0, bool $addedtocart=false)` —
  fügt einen Answer-Record ein; speichert `json` aber hartkodiert als `''` (Param ignoriert, `subbookings.php:88`).

Schuld: redundant zur Status-Logik in `subbookings_info::save_response`; Cache-Holen ohne Nutzung;
Parameter `$json` wird verworfen.

### `booking_subbooking` (Interface) (classes/subbookings/booking_subbooking.php)

Verantwortung: Vertrag aller Subbooking-Typen. Sauberer Extension-Point, alle `sb_types`
implementieren ihn. Methoden (Deklarationen, alle `public`):
- `add_subbooking_to_mform(MoodleQuickForm &$mform, array &$formdata)` — Formelemente des Typs.
- `get_name_of_subbooking($localized=true): string` — lokalisierter Anzeigename.
- `save_subbooking(stdClass &$data)` — persistiert Config in DB.
- `set_defaults(stdClass &$data, stdClass $record)` — Formular-Defaults aus Record.
- `set_subbookingdata(stdClass $record)` — Hydration aus DB-Record.
- `set_subbookingdata_from_json(string $json)` — Hydration aus JSON.
- `return_interface(booking_option_settings $settings, int $userid): array` — [DTO, Templatename].
- `return_price($user): array` — ggf. modifizierter Preis.
- `return_subbooking_information(int $itemid=0, int $userid=0): array` — Item-Details (Slot etc.).
- `return_answer_json(int $itemid, ?object $user=null): string` — Supplement-JSON für Answer.
- `is_blocking(booking_option_settings $settings, int $userid=0): bool` — soll Prepage zeigen?
- `after_booking_action / reservation_action / reservation_deletion_action(...): bool` — Status-Hooks.

Anmerkung: Doc-Kommentar nennt es fälschlich „availability condition" (Copy-Paste-Reste,
`booking_subbooking.php:17-25`).

### `subbookings_info` (classes/subbookings/subbookings_info.php)

Verantwortung: Zentraler statischer Service. Vereint Factory (Typ-Discovery via `glob`),
Form-Integration, CRUD auf `booking_subbooking_options` und die **Answer-Status-Maschine**.
Kollaborateure: `booking_subbooking`-Typen, `price`, `output\subbookingslist`,
`singleton_service`, `booking_option`, `wb_payment`, `customformstore` (indirekt).
Persistenz: `booking_subbooking_options`, `booking_subbooking_answers`; Cache-Invalidierung via
`booking_option::trigger_updated_event(... 'subbookings')`.

Methoden-Inventar (alle `static`, sofern nicht anders vermerkt):
- `public add_subbookings_to_mform(&$mform, &$formdata=[])` — Header + Liste/Hinweis (PRO+Config-Gate).
- `public get_subbooking_types()` — Factory: scannt `sb_types/*.php`, filtert per Whitelist, instanziiert.
- `public get_subbooking(string $type)` — Factory: einzelnen Typ per Klassennamen instanziieren.
- `public set_data_for_form(object &$data)` — lädt Config-Record, ruft Typ-`set_defaults`.
- `public save_subbooking(stdClass &$data)` — delegiert an Typ-`save_subbooking`, triggert Update-Event.
- `public delete_subbooking(int $subbookingid, int $cmid, int $optionid)` — löscht Config-Record + Event.
- `private add_list_of_existing_subbookings_for_this_option(&$mform, &$formdata=[])` — rendert `subbookingslist`.
- `public add_subbooking(&$mform, &$formdata)` — Typ-Auswahl (NoSubmit) + Elemente des Typs.
- `public is_blocked(object $settings): bool` — true, wenn irgendein Subbooking `block==1`.
- `public has_soft_subbookings(booking_option_settings $settings, $userid)` — true, wenn ein soft-Subbooking `is_blocking()`.
- `public load_subbookings(int $optionid)` — instanziiert + hydratisiert alle Subbookings einer Option.
- `public get_subbooking_by_area_and_id(string $area, int $itemid)` — rekonstruiert Subbooking aus area/itemid (Slot-Trick).
- `public save_response(string $area, int $itemid, int $status, $userid=0): bool` — Status-Maschine, ruft Action-Hooks.
- `private update_or_insert_answer(object $subbooking, int $itemid, int $userid, int $newstatus, array $oldstatus)` —
  Kern-Übergang: alte Status löschen/updaten, ggf. neuen Answer-Record einfügen.
- `private return_subbooking_answers(int $sboid, int $itemid, int $optionid, int $userid, array $status=[])` — SQL-Lookup Answers.
- `public return_array_of_subbookings(int $optionid): array` — area/itemid-Paare für shopping_cart.

Schuld:
- Bug-Verdacht: `update_or_insert_answer` Else-If `$newstatus !== DELETED || $newstatus !== NOTBOOKED`
  ist immer wahr (Tautologie, `subbookings_info.php:524-527`) → für DELETED/NOTBOOKED ohne
  Vorbestand wird trotzdem ein Insert-Pfad betreten.
- Factory-Whitelist hartkodiert im Code (`subbookings_info.php:91-94`) — Discovery via `glob` wird
  dadurch teilweise ausgehebelt; toter `timeslot`-Pfad.
- Gemischte Verantwortung (Form + CRUD + Status-Maschine) in einer 613-LOC-Klasse.

### `subbookings_cache` (classes/subbookings/subbookings_cache.php)

Verantwortung: Leere Marker-Klasse, vermutlich als Cache-Definitionsanker gedacht. **Keine
Methoden, keine Felder** (`subbookings_cache.php:35-36`). Der real genutzte Cache `subbookings`
wird in `subbookings.php:50` via `\cache::make('mod_booking','subbookings')` angesprochen (in
`db/caches.php` definiert, außerhalb des Scopes). Schuld: toter Code / verwaiste Klasse.

### `subbooking_additionalperson` (sb_types)

Verantwortung: Subbooking zum Hinzubuchen zusätzlicher Personen mit Multiplikator-Preis und
Namens-/Altersangaben. Soft (`block=0`). Kollaborateure: `price`,
`form\subbooking\additionalperson_form` (Cache-Zwischenspeicher der User-Eingaben),
`output\subbooking_additionalperson_output`, `singleton_service`, `booking_option`.
Persistenz: `booking_subbooking_options` (Config, JSON: name/description/useprice);
schreibt bei Reservation `places` in `booking_answers` (Subsystem-übergreifend!).

Methoden-Inventar (alle `public`):
- `set_subbookingdata / set_subbookingdata_from_json` — Hydration; liest name/description aus JSON.
- `add_subbooking_to_mform` — Editor-Beschreibung + Preis (kein Formula).
- `get_name_of_subbooking` — lokalisierter Name.
- `save_subbooking` — schreibt/updated Config-Record, Editor-Files, Preis.
- `set_defaults` — Formular-Defaults inkl. Editor-Prepare + Preis.
- `return_interface` — nur letztes Element rendert; DTO `subbooking_additionalperson_output`.
- `return_price($user)` — Basispreis × `subbooking_addpersons` aus Cache-Form (Multiplikator).
- `return_description($user)` — rendert gebuchte Personen aus Cache-Daten (Template).
- `return_subbooking_information` — leer (`[]`).
- `return_answer_json` — serialisiert Cache-Formdaten als Answer-JSON.
- `is_blocking` — false außer bei `waitforconfirmation` + User auf reserved/waitinglist.
- `after_booking_action` — No-op (`return true`).
- `reservation_action` — setzt `places = addpersons+1` in `booking_answers`, purged Answer-Cache.
- `reservation_deletion_action` — setzt `places = 1` zurück.

Schuld: greift schreibend in fremde Tabelle `booking_answers` (`:487`); Kopplung an
`additionalperson_form::get_data_from_cache`; nahezu identische Boilerplate zu den anderen Typen.

### `subbooking_additionalitem` (sb_types)

Verantwortung: Subbooking für einen zusätzlichen Artikel; optional an einen Customform-Wert der
Hauptoption gekoppelt (`subbookingadditemformlink`). Soft (`block=0`). Kollaborateure: `price`,
`bo_availability\conditions\customform`, `local\mobile\customformstore`,
`output\subbooking_additionalitem_output`, `singleton_service`. Persistenz:
`booking_subbooking_options` (JSON: name/description/useprice/formlink+value).

Methoden-Inventar (alle `public`):
- `set_subbookingdata / set_subbookingdata_from_json` — Hydration inkl. formlink-Felder.
- `add_subbooking_to_mform` — Formlink-Select (aus Customform der Option) + Wert + Editor + Preis.
- `get_name_of_subbooking` — lokalisierter Name.
- `save_subbooking` — Config-Record (zwei Update-Phasen wegen Editor-Files), Preis.
- `set_defaults` — Defaults inkl. formlink/value + Editor + Preis.
- `return_interface` — nur letztes Element rendert; DTO `subbooking_additionalitem_output`.
- `return_price($user)` — Basispreis (kein Multiplikator).
- `return_description($user)` — gibt `$this->description` zurück.
- `return_subbooking_information` — leer (`[]`).
- `return_answer_json` — leerer String.
- `is_blocking` — waitforconfirmation-Gate + Customform-Wert-Abgleich: blockt, wenn kein
  Formlink/keine Formdaten oder wenn der hinterlegte Wert dem gewählten entspricht (`:398-436`).
- `after_booking_action / reservation_action / reservation_deletion_action` — No-ops.

Schuld: doppelter Save-Roundtrip wegen Editor-Files; Boilerplate-Duplikat zu `additionalperson`.

### `subbooking_timeslot` (sb_types) — aktuell deaktiviert

Verantwortung: Zeitfenster-Reservierung. Zerlegt die Session-Zeiten der Hauptoption in Slots
fester `duration`, optional an eine Entität gebunden; jeder Slot ist einzeln buchbar. Blockierend
(`block=1`). Kollaborateure: `price`, `local_entities\entitiesrelation_handler`,
`option\dates_handler` (`create_slots`, `prettify_datetime`), `singleton_service`,
`output\subbooking_timeslot_output`, `booking_option`. Persistenz: `booking_subbooking_options`
(JSON enthält serialisierte Slot-Tabelle); liest `booking_subbooking_answers`.

Methoden-Inventar:
- `public set_subbookingdata / set_subbookingdata_from_json` — Hydration; liest `duration`.
- `public add_subbooking_to_mform` — Duration-Feld + Preis + Entitäten-Formdefinition.
- `public get_name_of_subbooking` — lokalisierter Name.
- `public save_subbooking` — Insert (für ID) dann Slot-Berechnung + Update; Preis + Entitäten.
- `public set_defaults` — Defaults inkl. Entitäten + Preis.
- `public return_interface` — nur letztes Element rendert; DTO `subbooking_timeslot_output`.
- `public return_subbooking_information(int $itemid, int $userid)` — sucht Slot per itemid, baut
  shopping_cart-Item (area=`subbooking-{id}`, canceluntil hartkodiert 1h, `:295`).
- `public return_answer_json` — leerer String.
- `public return_answers($itemid=0)` — Answers-Lookup je Subbooking/Slot.
- `private return_slots()` — erzeugt Slot-Array aus Sessions × duration (+ Entität, + Preis).
- `public return_price / return_description` — Basispreis / `$this->description`.
- `public add_booking_information_to_slots(array $slots, int $userid=0)` — markiert gebuchte/eigene Slots.
- `public is_blocking` — `!empty($this->block)` (immer true).
- `public after_booking_action / reservation_action / reservation_deletion_action` — No-ops.

Schuld: per Whitelist (`subbookings_info.php:91-94`) nie instanziiert → de facto toter Pfad;
`return_slots()` ist die komplexeste Methode des Subsystems (Sessions/Entitäten/Preis verschränkt);
`canceluntil` hartkodiert (`:295`); `$timeslot` nach `foreach`-`break` ohne Not-Found-Guard genutzt
(`:279-289`).

## Persistenz

| Tabelle | Zweck | Schreibende Stellen |
|---------|-------|---------------------|
| `booking_subbooking_options` | Konfiguration je Option (`name`, `type`, `optionid`, `block`, `json`, timestamps) | `*::save_subbooking`, `subbookings_info::delete_subbooking` |
| `booking_subbooking_answers` | Antworten/Reservierungen je User (`itemid`, `sboptionid`, `optionid`, `userid`, `status`, `json`, `timestart/end`) | `subbookings_info::update_or_insert_answer`, `subbookings::user_submit_response` |
| `booking_answers` (fremd) | Plätze-Update bei additionalperson-Reservation | `subbooking_additionalperson::reservation_action/_deletion_action` |

Cache: MUC-Cache `mod_booking/subbookings` (in `subbookings.php` angesprochen, Definition außerhalb
des Scopes in `db/caches.php`); zusätzlich Form-Zwischencache via
`additionalperson_form::get_data_from_cache`. Invalidierung erfolgt indirekt über
`booking_option::trigger_updated_event(..., 'subbookings')` bei Save/Delete.

## Extension-Points

- **`booking_subbooking` Interface** + Konvention „Dateiname = Klassenname" im Ordner
  `sb_types/`: neue Subbooking-Typen werden per `glob`-Discovery in `get_subbooking_types()`
  erkannt. **Achtung:** zusätzliche Whitelist `$supportedsubookingtypes`
  (`subbookings_info.php:91`) muss erweitert werden, sonst bleibt der Typ inaktiv.
- Rendering-Erweiterung über `return_interface()` → `[output-DTO, 'mod_booking/subbooking/<tpl>']`.
- Status-Hooks `after_booking_action` / `reservation_action` / `reservation_deletion_action`
  pro Typ für Nebenwirkungen (z. B. Plätze-Anpassung).

## Bekannte Schulden (→ Blueprint)

- **P2 — Tautologie-Bug** in `subbookings_info::update_or_insert_answer` (`subbookings_info.php:524-527`):
  `$newstatus !== DELETED || $newstatus !== NOTBOOKED` ist konstant `true`; gewünschte
  „weder noch"-Logik braucht `&&`. Potenziell ungewollte Answer-Inserts.
- **P2 — God-Service:** `subbookings_info` mischt Form-UI, CRUD, Factory und Status-Maschine
  (613 LOC). Status-Maschine (`save_response`/`update_or_insert_answer`) sollte ausgelagert werden.
- **P2 — Massive Code-Duplikation** über die drei `sb_types`: identische Hydration/Save/Defaults/
  Action-No-ops. Eine abstrakte Basisklasse (statt nur Interface) würde ~60 % Boilerplate sparen.
- **P2 — Verwaiste/halbfertige Klasse `subbookings`** (`classes/subbookings.php`): Cache-Holen
  ohne Nutzung (`:50-55`), `$json`-Parameter wird verworfen (`:88`), Doppelung zur Status-Maschine.
- **P3 — Leere `subbookings_cache`-Klasse** (toter Marker, `subbookings_cache.php`).
- **P2 — Hartkodierte Whitelist** entkoppelt von der `glob`-Discovery; `subbooking_timeslot` ist
  voll implementiert, aber unerreichbar (toter Pfad + Wartungslast).
- **Querschnitt — Schreibzugriff über Subsystem-Grenzen:** `additionalperson` mutiert direkt
  `booking_answers.places` (`:487`); enge Kopplung an `*_form`-Caches und an `price`.
- **Tests:** keine PHPUnit-Tests im Scope gefunden; Status-Übergänge und Slot-Berechnung
  ungetestet (schlechte Testbarkeit durch statische God-Calls + Cache-Abhängigkeit).
