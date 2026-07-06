# S12 — events

## Zweck & Grenzen

Das Subsystem S12 buendelt die **Moodle-Event-Schicht** von `mod_booking`: 47 konkrete Event-Klassen
(`classes/event/*.php`) sowie den zentralen **Event-Observer** (`classes/observer.php`). Die Event-Klassen
sind reine Daten-/Beschreibungsobjekte im Sinne von `\core\event\base` (kein Geschaeftslogik-Code, sondern
`init()`/`get_name()`/`get_description()`/`get_url()`). Der Observer ist der einzige Ort mit Logik: er reagiert
auf eigene und fremde (core/shopping_cart/wunderbyte_table) Events und stoesst Folgeaktionen an
(Cache-Invalidierung, Kalender-Pflege, Regel-Engine, Zertifikate, Enrolment-Sync).

Grenzen:
- S12 **erzeugt** keine Events selbst aus Geschaeftslogik heraus — das tun andere Subsysteme (booking_option,
  booking_answers, message-Controller, Rules-Engine). S12 stellt nur die Event-*Typen* und die Reaktion bereit.
- Die eigentliche Folge-Arbeit liegt fast vollstaendig in *fremden* Klassen, die der Observer aufruft (siehe
  „ausserhalb des Scopes" in Notes). Der Observer ist damit primaer ein **Dispatcher/Glue**.

## Position im Gesamtsystem

```
Geschaeftslogik (booking_option, booking_answers, rules, messages, ...)
        |  $event->trigger()
        v
[S12 Event-Klassen]  --- in Moodle-Logstore protokolliert (get_name/get_description/get_url)
        |
        |  Moodle Event-Manager dispatcht laut db/events.php
        v
[mod_booking_observer]  --- callbacks (db/events.php Z.30ff)
        |
        +--> rules_info (Regel-Engine, Sammeln/Ausfuehren)          [S-rules]
        +--> calendar / calendar_helper (Kalendereintraege)         [S-calendar]
        +--> booking_option::purge_cache_for_option / cache_helper  [S-cache]
        +--> certificate_conditions / certificateclass              [S-certificate]
        +--> checkanswers (Re-Validierung Buchungsantworten)        [S-checkanswers]
        +--> booking_enrolment (Group/Cohort-Sync)                  [S-sync]
        +--> elective::enrol_booked_users_to_course                 [S-elective]
        +--> view / wunderbyte_table (Template-Switch)              [S-output]
```

Die Registrierung der Observer-Callbacks erfolgt in `db/events.php` (ausserhalb des Scopes, aber zwingend
relevant): u.a. der Wildcard `'eventname' => '*'` → `execute_rule` (Z.114f), der **jedes** Event durch die
Regel-Engine schleust.

## Schluesselkonzepte

- **Event-Klasse als Logstore-DTO**: Jede `*.php` unter `classes/event/` erweitert `\core\event\base`
  (Ausnahme: `course_module_viewed` erweitert `\core\event\course_module_viewed`). Pflicht: `init()` setzt
  `crud`/`edulevel`/`objecttable`; `get_name()`/`get_description()`/`get_url()` liefern Log-Darstellung.
- **`other`-Payload-Robustheit**: Mehrere Events dekodieren `other` defensiv (Array | stdClass | JSON-String),
  weil das Feld je nach Lifecycle (Trigger vs. aus Log rehydriert) unterschiedlich vorliegt — siehe
  `bookingoption_updated::extract_changes_from_event_other` (Z.141), `message_sent` (Z.78), `slotmoved`.
- **`validate_data()`** wird nur von einer Teilmenge der Events implementiert (die „answer"/„option-booked"-
  Familie), um Pflichtfelder wie `relateduserid`/`other[optionid]` zu erzwingen.
- **Deferred Rule Execution**: Der Observer schreibt Folgeaktionen nicht sofort aus, sondern haengt Closures an
  `rules_info::$eventstoexecute[]` (z.B. `bookinganswer_cancelled` Z.169, `bookingoption_cancelled` Z.211),
  die spaeter abgearbeitet werden. Unter PHPUNIT_TEST wird in `execute_rule` (Z.476) synchron in einer
  Schleife (Counter-Guard gegen Endlosschleifen, max. 10) abgearbeitet.
- **Cache-Invalidierung als Querschnitt**: Viele Observer-Methoden purgen MUC-Caches via
  `booking_option::purge_cache_for_option` bzw. `cache_helper::invalidate_by_event/purge_by_event`. Kommentare
  warnen, dass Tests brechen, wenn Purges entfernt werden (Z.266).
- **Doppelte Beschreibung**: `bookingoption_updated` liefert zusaetzlich `get_simplified_description()` (HTML
  ohne Rahmtext, fuer Mails) ueber den Renderer `bookingoption_changes`.

## Datenfluss

1. Eine Geschaeftslogik-Operation (z.B. Stornierung) ruft `\mod_booking\event\bookinganswer_cancelled::create([...])->trigger()`.
2. Moodle protokolliert via `get_description()`/`get_url()` in den Logstore und dispatcht an registrierte Observer.
3. Spezifischer Callback (`mod_booking_observer::bookinganswer_cancelled`) haengt eine Aufraeum-Closure an
   `rules_info::$eventstoexecute` und invalidiert `setbackoptionsanswers` fuer die optionid.
4. Zusaetzlich faengt der Wildcard-Observer `execute_rule` das Event und ruft
   `rules_info::collect_rules_for_execution($event)` — die Regel-Engine prueft, ob konfigurierte Rules feuern.
5. Folgewirkungen (Mails, Kalender, Enrolments, Zertifikate) werden ueber die jeweiligen Fremd-Klassen erzeugt.

## Dateien & Klassen

Alle Event-Klassen liegen im Namespace `mod_booking\event`. „Std-Event" = Standardmuster
`init/get_name/get_description/get_url` (ggf. `validate_data`). LOC-Spalte aus `wc -l`.

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| observer.php | mod_booking_observer | Observer/Dispatcher | 786 | 30 | C | P1 |
| bookingoption_updated.php | bookingoption_updated | Event (Renderer-gestuetzt) | 157 | 6 | B | P3 |
| message_sent.php | message_sent | Event (HTML-Desc) | 157 | 4 | C | P2 |
| custom_message_sent.php | custom_message_sent | Event (HTML-Desc) | 147 | 4 | C | P2 |
| bookinganswer_slotmoved.php | bookinganswer_slotmoved | Event (Slot-Normalisierung) | 195 | 6 | B | P3 |
| bookinganswer_cancelled.php | bookinganswer_cancelled | Event | 108 | 4 | B | - |
| bookinganswer_notesedited.php | bookinganswer_notesedited | Event | 108 | 5 | B | - |
| bookinganswer_presencechanged.php | bookinganswer_presencechanged | Event | 109 | 5 | B | - |
| bookinganswer_confirmed.php | bookinganswer_confirmed | Event | 91 | 5 | B | - |
| bookinganswer_denied.php | bookinganswer_denied | Event | 90 | 5 | B | - |
| bookinganswer_movedupfromwaitinglist.php | bookinganswer_movedupfromwaitinglist | Event | 84 | 5 | B | - |
| bookinganswer_slotbooked.php | bookinganswer_slotbooked | Event | 100 | 5 | B | - |
| bookinganswer_slotcancelled.php | bookinganswer_slotcancelled | Event | 100 | 5 | B | - |
| bookinganswer_waitingforconfirmation.php | bookinganswer_waitingforconfirmation | Event | 95 | 5 | B | - |
| bookinganswercustomformconditions_deleted.php | bookinganswercustomformconditions_deleted | Event | 99 | 4 | B | - |
| bookinginstance_updated.php | bookinginstance_updated | Event | 98 | 4 | B | - |
| bookingoption_booked.php | bookingoption_booked | Event | 99 | 5 | B | - |
| bookingoption_bookedviaautoenrol.php | bookingoption_bookedviaautoenrol | Event | 96 | 5 | B | - |
| bookingoption_cancelled.php | bookingoption_cancelled | Event | 77 | 4 | B | - |
| bookingoption_completed.php | bookingoption_completed | Event | 95 | 5 | B | - |
| bookingoption_created.php | bookingoption_created | Event | 79 | 4 | B | - |
| bookingoption_deleted.php | bookingoption_deleted | Event | 77 | 4 | B | - |
| bookingoption_freetobookagain.php | bookingoption_freetobookagain | Event | 78 | 4 | B | - |
| bookingoption_uncompleted.php | bookingoption_uncompleted | Event | 97 | 5 | B | - |
| bookingoptiondate_created.php | bookingoptiondate_created | Event | 79 | 4 | B | - |
| bookingoptiondate_deleted.php | bookingoptiondate_deleted | Event | 80 | 4 | B | - |
| bookingoptionwaitinglist_booked.php | bookingoptionwaitinglist_booked | Event | 99 | 5 | B | - |
| certificate_issued.php | certificate_issued | Event | 95 | 5 | B | - |
| course_module_viewed.php | course_module_viewed | Event (Core-Subclass) | 44 | 1 | B | - |
| custom_bulk_message_sent.php | custom_bulk_message_sent | Event | 74 | 3 | B | - |
| custom_field_changed.php | custom_field_changed | Event | 72 | 4 | B | - |
| enrollink_triggered.php | enrollink_triggered | Event | 68 | 3 | B | - |
| optiondates_teacher_added.php | optiondates_teacher_added | Event | 81 | 4 | B | - |
| optiondates_teacher_deleted.php | optiondates_teacher_deleted | Event | 81 | 4 | B | - |
| pricecategory_changed.php | pricecategory_changed | Event | 70 | 4 | B | - |
| records_imported.php | records_imported | Event | 86 | 4 | B | - |
| reminder_teacher_sent.php | reminder_teacher_sent | Event | 68 | 3 | B | - |
| reminder1_sent.php | reminder1_sent | Event | 69 | 3 | B | - |
| reminder2_sent.php | reminder2_sent | Event | 69 | 3 | B | - |
| report_viewed.php | report_viewed | Event | 71 | 4 | B | - |
| rest_script_failed.php | rest_script_failed | Event | 78 | 4 | B | - |
| rest_script_success.php | rest_script_success | Event | 76 | 4 | B | - |
| teacher_added.php | teacher_added | Event | 86 | 4 | B | - |
| teacher_removed.php | teacher_removed | Event | 86 | 4 | B | - |
| booking_afteractionsfailed.php | booking_afteractionsfailed | Event (Fehler) | 77 | 4 | B | - |
| booking_failed.php | booking_failed | Event (Fehler) | 77 | 4 | B | - |
| booking_rulesexecutionfailes.php | booking_rulesexecutionfailed | Event (Fehler) | 77 | 4 | B | - |
| booking_debug.php | booking_debug | Event (Debug-Log) | 72 | 3 | B | - |

> Hinweis: Dateiname `booking_rulesexecutionfailes.php` (Tippfehler „failes") enthaelt die Klasse
> `booking_rulesexecutionfailed` — Datei-/Klassenname inkonsistent.

### mod_booking_observer (`observer.php`, Score C / Prio P1)

Zentraler Event-Observer; 30 statische Callback-Methoden, registriert in `db/events.php`. Reine
Glue-/Dispatcher-Schicht mit hoher Auswaerts-Kopplung.

Methoden-Inventar (alle `public static`, Rueckgabe meist `void`):
- `user_created($event)` (Z.62) — startet `rules_info::execute_rules_for_user` fuer neuen User.
- `user_updated($event)` (Z.75) — purgt Preis-Cache (`setbackprices`) + fuehrt User-Rules aus.
- `user_deleted($event)` (Z.91) — loescht alle Booking-Datensaetze des Users aus 6 Tabellen + Teacher-Journal-Cache-Purge.
- `user_enrolment_deleted($event)` (Z.110) — bei letztem Unenrol: `user_delete_response` + Teacher-Cleanup; planet `checkanswers`-Task (Re-Check Antworten).
- `bookingoption_created($event)` (Z.156) — No-op (Kalender wird ueber `bookingoption_updated` erledigt).
- `bookinganswer_cancelled($event)` (Z.167) — deferred Closure: loescht `booking_userevents`/`event`; invalidiert `setbackoptionsanswers`.
- `checkout_completed($event)` (Z.194) — invalidiert Option-Cache fuer Ratenzahlungs-Belege (nur mod_booking/option).
- `bookingoption_cancelled($event)` (Z.210) — deferred: loescht alle Antworten + Userevents der Option; purgt Option-Cache.
- `bookingoption_updated($event)` (Z.243) — pflegt Optiondate-Kalender + Teacher-Kalender, setzt Sichtbarkeit, purgt Cache (zweimal, Z.267/278).
- `bookingoptiondate_created($event)` (Z.287) — legt Kalendereintraege fuer Option, alle gebuchten User und Teacher an.
- `bookingoptiondate_deleted($event)` (Z.336) — No-op (Platzhalter).
- `bookingoption_completed($event)` (Z.345) — Aktivitaetsabschluss + Zertifikatsbedingungen + ggf. Abschluss-Mail (legacy Templates).
- `bookingoption_uncompleted($event)` (Z.394) — ruft `booking_activitycompletion` zum Zuruecknehmen.
- `custom_field_changed($event)` (Z.420) — aktualisiert Teacher-Kalendereintraege ueber alle Optionen mit `addtocalendar`.
- `pricecategory_changed($event)` (Z.459) — schreibt neue Preiskategorie-Identifier in `booking_prices`.
- `execute_rule($event)` (Z.476) — Wildcard-Callback fuer **jedes** Event; `rules_info::collect_rules_for_execution`; im Test synchrone Abarbeitung mit Counter-Guard (max 10).
- `course_completed($event)` (Z.504) — bei Elective: enrolt gebuchte User in Folgekurs; optional Auto-Abschluss; optionales `booking_debug`-Logging.
- `course_module_updated($event)` (Z.561) — purgt Booking-Instanz-Cache nach CM-Update.
- `group_membership_changed($event)` (Z.579) — `checkanswers`-Task (CM-Sichtbarkeit) + `booking_enrolment::queue_source_membership_sync('group', ...)`.
- `cohort_membership_changed($event)` (Z.606) — Cohort-Variante des Membership-Sync.
- `template_switched($event)` (Z.621) — baut wunderbyte_table fuer gewaehltes View-Template (cards/list-Varianten) neu.
- `bookinganswer_presencechanged($event)` (Z.672) — stellt bei passendem Praesenzstatus automatisch Zertifikat aus.
- `bookinganswer_notesedited($event)` (Z.694) — No-op (Reserve).
- `shoppingcart_item_added($event)` (Z.705) — loescht zwischengespeicherte Custom-Form-Daten (`customformstore`) fuer mod_booking-Items.
- `competency_updated/_deleted/_user_competency_rated[_in_plan/_in_course]` (Z.724–769) — Competency-Cache-Invalidierung.
- `customfield_created_updated_deleted($event)` (Z.782) — invalidiert `setbackcustomfields`.

Auffaellig: `bookingoption_updated` enthaelt eine doppelte `purge_cache_for_option` (Z.267 + Z.278) mit
Kommentar „Tests will fail if removed" — Indiz fuer fragile Cache-Kopplung statt sauberer Invalidierungsstrategie.

### bookingoption_updated (`bookingoption_updated.php`, Score B / Prio P3)

Event mit Renderer-Anbindung. Neben Standardmethoden:
- `get_simplified_description()` (Z.88) — HTML-Diff ohne Rahmtext (fuer Mails).
- `private generate_description($simplified=false)` (Z.100) — rendert ueber `bookingoption_changes`-Output-Klasse + `$PAGE->get_renderer`; Throwable-Fallback auf simplen String.
- `private extract_changes_from_event_other($other): array` (Z.141) — normalisiert `other` (String/Object/Array → Array).

Kopplung an `$PAGE`/Renderer in einem Event-Objekt ist untypisch (Events sollten darstellungsfrei sein) — leichte Schuld.

### message_sent / custom_message_sent (Score C / Prio P2)

Beide bauen in `get_description()` (message_sent Z.71, custom Z.66) **inline HTML mit Bootstrap-Collapse-Markup**
direkt im Event zusammen und haben je eine `private transform_msgparam(int): string` (message_sent Z.122) mit
hartkodierten **englischen** Strings statt `get_string()`. Datenvertrag fuer `other` wird defensiv behandelt
(JSON|Array). Schuld: Praesentations-/i18n-Logik im Event, nicht uebersetzt, HTML-im-PHP.

### bookinganswer_slotmoved (`bookinganswer_slotmoved.php`, Score B / Prio P3)

Slotbooking-Event mit eigener Aufbereitung:
- `private normalise_slots($slots): array` (Z.118) — dedupliziert/validiert Slot-Paare (start/end).
- `private format_slot_list(array): string` (Z.151) — formatiert Slots als `userdate`-Liste.
- `validate_data()` (Z.184) — erzwingt `relateduserid` und `other[optionid]`.
- `get_description()` waehlt Single-/Multi-String anhand `slotcount`.

### Standard-Event-Familie (uebrige ~40 Klassen, Score B / Prio -)

Identisches Muster: `protected init()` (setzt `crud`/`edulevel`/`objecttable`), `public static get_name()`
(`get_string(...)`), `public get_description()` (baut Beschreibung, meist via `singleton_service`-Lookups fuer
User/Option), `public get_url()` (verlinkt report.php/subscribeusers.php). Die „answer"/„booked"-Untergruppe
(`bookinganswer_confirmed/denied/...`, `bookingoption_booked`, `certificate_issued`, ...) ergaenzt
`protected validate_data()` zur Pflichtfeld-Pruefung. Triviale, gut testbare DTOs.

Sondervarianten:
- `course_module_viewed` (Z.35) — erweitert `\core\event\course_module_viewed`, nur `init()` ueberschrieben.
- `booking_debug` (Z.35) — Debug-Logging-Event ohne `validate_data`, getriggert nur bei `bookingdebugmode`.
- `booking_failed` / `booking_afteractionsfailed` / `booking_rulesexecutionfailed` — Fehler-/Diagnose-Events.

## Persistenz

Event-Klassen selbst persistieren nur in den **Moodle-Logstore** (Standard `\core\event\base`-Mechanik;
`objecttable` zeigt auf `booking_answers` bzw. `booking_options`). Der Observer schreibt/loescht aktiv in:

- Tabellen (direkt via `$DB`): `booking_answers`, `booking_history`, `booking_teachers`,
  `booking_optiondates_teachers`, `booking_userevents`, `booking_icalsequence`, `booking_optiondates`,
  `booking_options`, `booking_prices`, `event` (Core-Kalender).
- Caches (`cache_helper` / MUC): `setbackprices`, `setbackoptionsanswers`, `setbackcachedteachersjournal`,
  `setbackcustomfields`, `setbackcompetenciesshortnamescache`, `setbackusercompetenciescache`,
  sowie `booking_option::purge_cache_for_option` und `booking::purge_cache_for_booking_instance_by_cmid`.

## Extension-Points

- **Event-Typen als Erweiterungspunkt**: Jede `mod_booking\event\*`-Klasse ist ein oeffentlich nutzbarer
  Trigger-Punkt; Rules-Engine und Drittsubplugins koennen darauf reagieren.
- **`db/events.php`-Observer-Registry** (ausserhalb Scope): bindet eigene + fremde Events
  (`core`, `local_shopping_cart`, `local_wunderbyte_table`) an `mod_booking_observer`. Wildcard `*` →
  `execute_rule` macht **jedes** Moodle-Event zum Rule-Trigger.
- **`rules_info::$eventstoexecute`** — Closure-Queue, ueber die der Observer verzoegerte Aktionen einreiht
  (kein formales Interface, aber faktischer Hook).
- **Vererbung**: `\core\event\base` (bzw. `course_module_viewed`) ist die Basis; `validate_data()` als
  optionaler Hook.

## Bekannte Schulden (→ Blueprint)

- **observer.php als God-Dispatcher** (786 LOC, 30 Methoden, Prio P1): vermischt Cache, Kalender, Enrolment,
  Zertifikate, Rules, Templates. Hohe Auswaerts-Kopplung (`booking_option`, `calendar`, `rules_info`,
  `checkanswers`, `certificate_conditions`, `elective`, `view`, `customformstore`, `booking_enrolment`).
  Kandidat fuer Aufspaltung in fachliche Handler.
- **Fragile Cache-Invalidierung**: doppelter `purge_cache_for_option` in `bookingoption_updated`
  (`observer.php:267` & `:278`) mit Kommentar „Tests will fail if this cache purge is removed" (`:266`) —
  Symptom impliziter Reihenfolgeabhaengigkeiten.
- **Praesentationslogik in Events**: Inline-HTML + hartkodierte **englische** Strings in
  `message_sent.php:99` / `:122` und `custom_message_sent.php` (`transform_msgparam`), nicht i18n-faehig;
  `bookingoption_updated.php:101` zieht `$PAGE`-Renderer ins Event.
- **Datei/Klassen-Namensinkonsistenz**: `booking_rulesexecutionfailes.php` enthaelt
  `booking_rulesexecutionfailed` (Tippfehler im Dateinamen).
- **No-op-Observer**: `bookingoption_created` (`:156`), `bookingoptiondate_deleted` (`:336`),
  `bookinganswer_notesedited` (`:694`) sind leere Platzhalter — toter/Reserve-Code, sollte dokumentiert
  oder entfernt werden.
- **Test-bedingte Sonderpfade in Produktivcode**: `execute_rule` (`:479`) und `bookingoption_cancelled`
  (`:216`) verzweigen explizit auf `PHPUNIT_TEST` — Vermischung von Test- und Produktionslogik.
- **TODO-Reste**: mehrere Events tragen `// TODO MDL-00000: Create a proper description.`
  (z.B. `bookinganswer_cancelled.php:73`).
