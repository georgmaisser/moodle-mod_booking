# S09 — messaging_placeholders

## Zweck & Grenzen

Dieses Subsystem erzeugt und versendet alle E-Mail-Benachrichtigungen von mod_booking
(Buchungsbestätigung, Warteliste, Erinnerungen, Statuswechsel, Stornierungen, Änderungs-
benachrichtigungen, Pollurl, Custom-Messages aus Rules, Raten-/Installment-Mails) und stellt
die Platzhalter-Substitution (`{firstname}`, `{dates}`, `{price}`, Custom-Felder, …) bereit,
mit der Mail-Templates, Optionsbeschreibungen und Webseiten-Texte mit Laufzeitwerten gefüllt
werden.

Kernbausteine:
- `message_controller` — orchestriert den kompletten Versandweg (Template-Auswahl, Platzhalter-
  Rendern, iCal-Anhänge, Send-Now vs. Adhoc-Queue, PHPMailer-Sonderweg, Event-Logging).
- `placeholders\placeholders_info` — zentrale Substitutions-Engine (Regex-Erkennung, Klassen-
  Dispatch, `{#sec}…{/sec}`-Abschnittslogik, statischer Request-Cache).
- `placeholders\placeholder_base` + 73 Platzhalter-Klassen — je ein `return_value()` pro Token.
- `local\scheduledmails` — Auflistung & Bereinigung geplanter (adhoc) Mails aus `task_adhoc`.
- `local\templaterule` — Auswahl-Liste der als Vorlage markierten Booking-Rules.

**Grenzen / außerhalb des Scopes:** Der konkrete Versand über `message_send()` ist Moodle-Core.
Die adhoc-Task-Klasse `task\send_confirmation_mails`, die iCal-Generierung `ical`, die Rules-Engine
`booking_rules\rules_info`, die Renderer (`output\bookingoption_changes`, `output\scheduledmails`,
`output\optiondates_only`) sowie `singleton_service` und `booking_option_settings` liegen außerhalb,
werden aber stark genutzt (siehe Kollaborateure).

## Position im Gesamtsystem

Aufrufer des `message_controller` sind primär die Booking-Rules-Aktionen (z.B.
`send_mail`/`send_copy_mail`), die adhoc-Task `send_confirmation_mails`, sowie direkte Buchungs-
und Stornovorgänge in `booking_option`. Die `placeholders_info::render_text()`-Engine wird darüber
hinaus überall genutzt, wo Texte mit Optionswerten gefüllt werden (Mail-Bodies, Betreffzeilen,
Optionsbeschreibungen via Shortcodes, Webseitentexte). `scheduledmails` und `templaterule` sind
Admin-/UI-nahe Helfer (Übersicht geplanter Mails, Rule-Template-Dropdown).

```
booking_rules / booking_option / send_confirmation_mails(task)
        │
        ▼
   message_controller ──► placeholders_info::render_text() ──► placeholders\*  (73 Klassen)
        │                         │
        │                         └─► singleton_service, booking_option_settings, customfields, profile
        ├─► ical (Anhänge)
        ├─► message_send() | PHPMailer (inline iCal)
        ├─► \core\task\manager::queue_adhoc_task(send_confirmation_mails)
        └─► event\message_sent / booking_debug
```

## Schlüsselkonzepte

- **Message-Param vs. Controller-Param:** `MOD_BOOKING_MSGPARAM_*` bestimmt den Mailtyp (→ Feldname
  des Templates, z.B. `bookedtext`), `MOD_BOOKING_MSGCONTRPARAM_*` den Versandmodus
  (`SEND_NOW`, `QUEUE_ADHOC`, `DO_NOT_SEND`). Mapping in `get_message_fieldname()`
  (message_controller.php:856).
- **Template-Quelle (Priorität):** Custom-Message > globale Mailtemplates (`mailtemplatessource==1`,
  `global<fieldname>` aus Plugin-Config) > instanzspezifisches Feld > Default-Langstring. Das
  Sonderzeichen `"0"` als Template schaltet den Mailtyp ab (message_controller.php:359-379, 517).
- **Platzhalter-Dispatch:** `placeholders_info::render_text()` extrahiert Tokens per Regex
  `/{(?!mlang)…}/`, entfernt Ziffern um den Klassennamen zu gewinnen (`{teacher2}` → Klasse `teacher`,
  Index 2), sucht die Klasse über mod_booking- und `bookingextension_*`-Namespaces und ruft
  `return_value()` statisch. Arrays werden per Indexsuffix oder `reset()` reduziert
  (placeholders_info.php:112-166).
- **Abschnitts-/Enclosing-Platzhalter:** `{#name}…{/name}` werden entfernt, wenn der Inhalt leer
  ist, bzw. die Marker entfernt, wenn er gefüllt ist (placeholders_info.php:198-231).
- **Request-Cache:** Auflösungen werden in `placeholders_info::$placeholders` (statisches Array,
  Lebensdauer = Request) zwischengespeichert; die Lokalisierungsliste in `$localizedplaceholders`.
  Cachekeys variieren je Platzhalter (`<class>-<userid>`, `<class>-<optionid>-<userid>`, …).
- **Related-User-Mechanik:** Platzhalter mit Suffix `-related` (z.B. `firstnamerelated`,
  Profilfeld `xyz-related`) lesen den `relateduserid` aus dem im `rulejson` serialisierten Event
  (`datafromevent`) statt aus dem Adressaten.
- **iCal & PHPMailer-Sonderweg:** Bei genau einem Termin und aktiviertem `usenonnativemailer` wird
  die Einladung inline (nicht als Anhang) per PHPMailer verschickt, damit Outlook Accept/Decline zeigt
  (message_controller.php:913). Sonst normaler `message_send()`.
- **Geplante Mails:** Adhoc-Mails liegen in `task_adhoc`; `scheduledmails` parst das `customdata`-JSON
  (DB-Dialekt-spezifisch) und verknüpft es mit `booking_rules` zur Anzeige; Bereinigung erfolgt
  durch Re-Evaluierung der Rule (`check_if_rule_still_applies`).

## Datenfluss

1. Aufrufer instanziiert `message_controller(...)`. Konstruktor: setzt Nutzer-Sprache, lädt
   Booking-/Option-Settings via `singleton_service`, rendert Subject-Platzhalter, baut den Mailbody
   (`get_email_body()` → Template-Auswahl → `placeholders_info::render_text()`), rendert Changes,
   baut je nach Modus `messagedata` (`get_message_data_send_now()`/`…_queue_adhoc()`).
2. Unaufgelöste Platzhalter, die eine `moodle_exception` werfen, setzen `preventsendingmessage=true`
   → Versand wird unterdrückt (message_controller.php:261, 324).
3. `send_or_queue()` entscheidet: `DO_NOT_SEND` (Template "0" / unsichtbare Option) → nichts;
   `QUEUE_ADHOC` → `send_mail_with_adhoc_task()` (queued `send_confirmation_mails`, optional Kopie an
   Booking-Manager); sonst direkter Versand inkl. iCal-Anhang-Handling + `message_sent`-Event.
4. Platzhalterklasse: `return_value()` löst per `singleton_service`/Settings/DB den Wert auf, cached
   ihn in `placeholders_info::$placeholders` und gibt String (oder Array) zurück.

## Dateien & Klassen

> Score A(best)…E(schlecht); Prio P0…P3 / '-'. "→ Quality-Index" = vorläufiger Verweis für Phase 3.

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Prio |
|---|---|---|---|---|---|---|
| classes/message_controller.php | message_controller | Service/Orchestrator | 1052 | 11 | D | P1 |
| classes/placeholders/placeholders_info.php | placeholders_info | Service (Substitutions-Engine) | 314 | 3 | C | P2 |
| classes/placeholders/placeholder_base.php | placeholder_base | Basisklasse | 58 | 2 | A | - |
| classes/local/scheduledmails.php | scheduledmails | Service (Query/Cleanup) | 232 | 4 | C | P2 |
| classes/local/templaterule.php | templaterule | Service/Util | 99 | 2 | B | - |
| classes/placeholders/placeholders/customfields.php | customfields | Placeholder (Sonderfall) | 186 | 4 | C | P2 |
| classes/placeholders/placeholders/changes.php | changes | Placeholder (Event) | 110 | 4 | B | - |
| classes/placeholders/placeholders/address.php | address | Placeholder | 97 | 2 | B | - |
| classes/placeholders/placeholders/baid.php | baid | Placeholder | 106 | 3 | B | - |
| classes/placeholders/placeholders/bookedplaces.php | bookedplaces | Placeholder | 81 | 2 | B | - |
| classes/placeholders/placeholders/bookedslotsfromevent.php | bookedslotsfromevent | Placeholder (Event) | 115 | 2 | B | - |
| classes/placeholders/placeholders/bookingconfirmationlink.php | bookingconfirmationlink | Placeholder (Link/Event) | 98 | 2 | B | - |
| classes/placeholders/placeholders/bookingdetails.php | bookingdetails | Placeholder | 119 | 2 | B | - |
| classes/placeholders/placeholders/bookinglink.php | bookinglink | Placeholder (Link) | 110 | 2 | B | - |
| classes/placeholders/placeholders/bookingoptiondetaillink.php | bookingoptiondetaillink | Placeholder (Link) | 126 | 2 | B | - |
| classes/placeholders/placeholders/bookingoptionname.php | bookingoptionname | Placeholder | 129 | 3 | B | - |
| classes/placeholders/placeholders/bookingreportlink.php | bookingreportlink | Placeholder (Link) | 103 | 2 | B | - |
| classes/placeholders/placeholders/certificateurl.php | certificateurl | Placeholder (DB/Event) | 100 | 2 | B | - |
| classes/placeholders/placeholders/coursecalendarurl.php | coursecalendarurl | Placeholder (Link) | 105 | 2 | B | - |
| classes/placeholders/placeholders/courseid.php | courseid | Placeholder | 119 | 3 | B | - |
| classes/placeholders/placeholders/courselink.php | courselink | Placeholder (Link) | 110 | 2 | B | - |
| classes/placeholders/placeholders/coursename.php | coursename | Placeholder | 121 | 3 | B | - |
| classes/placeholders/placeholders/datesandentities.php | datesandentities | Placeholder | 146 | 2 | B | - |
| classes/placeholders/placeholders/dates.php | dates | Placeholder (Renderer) | 113 | 2 | B | - |
| classes/placeholders/placeholders/department.php | department | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/description.php | description | Placeholder | 132 | 3 | B | - |
| classes/placeholders/placeholders/duedate.php | duedate | Placeholder (Installment) | 82 | 2 | B | - |
| classes/placeholders/placeholders/duration.php | duration | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/email.php | email | Placeholder | 110 | 3 | B | - |
| classes/placeholders/placeholders/emailrelated.php | emailrelated | Placeholder (Related/Event) | 112 | 2 | B | - |
| classes/placeholders/placeholders/enddate.php | enddate | Placeholder | 103 | 2 | B | - |
| classes/placeholders/placeholders/endtime.php | endtime | Placeholder | 103 | 2 | B | - |
| classes/placeholders/placeholders/enrollink.php | enrollink | Placeholder (Link/Event) | 121 | 2 | B | - |
| classes/placeholders/placeholders/eventdescription.php | eventdescription | Placeholder (Event) | 99 | 2 | B | - |
| classes/placeholders/placeholders/eventtype.php | eventtype | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/firstname.php | firstname | Placeholder | 110 | 3 | B | - |
| classes/placeholders/placeholders/firstnamerelated.php | firstnamerelated | Placeholder (Related/Event) | 112 | 2 | B | - |
| classes/placeholders/placeholders/gotobookingoption.php | gotobookingoption | Placeholder (Link) | 117 | 2 | B | - |
| classes/placeholders/placeholders/installmentprice.php | installmentprice | Placeholder (Installment) | 77 | 2 | B | - |
| classes/placeholders/placeholders/instancename.php | instancename | Placeholder | 101 | 2 | B | - |
| classes/placeholders/placeholders/institution.php | institution | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/journal.php | journal | Placeholder | 110 | 2 | B | - |
| classes/placeholders/placeholders/lastname.php | lastname | Placeholder | 110 | 3 | B | - |
| classes/placeholders/placeholders/lastnamerelated.php | lastnamerelated | Placeholder (Related/Event) | 112 | 2 | B | - |
| classes/placeholders/placeholders/location.php | location | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/numberofinstallment.php | numberofinstallment | Placeholder (Installment) | 79 | 2 | B | - |
| classes/placeholders/placeholders/numberparticipants.php | numberparticipants | Placeholder | 102 | 2 | B | - |
| classes/placeholders/placeholders/numberwaitinglist.php | numberwaitinglist | Placeholder | 102 | 2 | B | - |
| classes/placeholders/placeholders/optiondatefromevent.php | optiondatefromevent | Placeholder (DB/Event) | 104 | 2 | B | - |
| classes/placeholders/placeholders/optionid.php | optionid | Placeholder | 119 | 3 | B | - |
| classes/placeholders/placeholders/participant.php | participant | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/pollstartdate.php | pollstartdate | Placeholder | 102 | 2 | B | - |
| classes/placeholders/placeholders/pollurl.php | pollurl | Placeholder | 144 | 2 | B | - |
| classes/placeholders/placeholders/pollurlteachers.php | pollurlteachers | Placeholder | 144 | 2 | B | - |
| classes/placeholders/placeholders/price.php | price | Placeholder (Event/Cart) | 100 | 2 | B | - |
| classes/placeholders/placeholders/profilepicture.php | profilepicture | Placeholder | 119 | 2 | B | - |
| classes/placeholders/placeholders/qrenrollink.php | qrenrollink | Placeholder (Link/Event) | 122 | 2 | B | - |
| classes/placeholders/placeholders/qrid.php | qrid | Placeholder | 105 | 2 | B | - |
| classes/placeholders/placeholders/qrusername.php | qrusername | Placeholder | 108 | 2 | B | - |
| classes/placeholders/placeholders/restresponse.php | restresponse | Placeholder (Event) | 92 | 2 | B | - |
| classes/placeholders/placeholders/selflearningcourse.php | selflearningcourse | Placeholder | 119 | 3 | B | - |
| classes/placeholders/placeholders/semester.php | semester | Placeholder (DB) | 104 | 2 | B | - |
| classes/placeholders/placeholders/shoppingcartplaceholder.php | shoppingcartplaceholder | Placeholder (Cart/Event) | 108 | 2 | B | - |
| classes/placeholders/placeholders/slotsbooked.php | slotsbooked | Placeholder (Slots) | 75 | 2 | B | - |
| classes/placeholders/placeholders/slotscancelled.php | slotscancelled | Placeholder (Slots) | 75 | 2 | B | - |
| classes/placeholders/placeholders/slotsmovedfrom.php | slotsmovedfrom | Placeholder (Slots) | 75 | 2 | B | - |
| classes/placeholders/placeholders/slotsmovedto.php | slotsmovedto | Placeholder (Slots) | 75 | 2 | B | - |
| classes/placeholders/placeholders/startdate.php | startdate | Placeholder | 103 | 2 | B | - |
| classes/placeholders/placeholders/starttime.php | starttime | Placeholder | 100 | 2 | B | - |
| classes/placeholders/placeholders/status.php | status | Placeholder | 105 | 2 | B | - |
| classes/placeholders/placeholders/teacher.php | teacher | Placeholder | 97 | 2 | B | - |
| classes/placeholders/placeholders/teachers.php | teachers | Placeholder | 97 | 2 | B | - |
| classes/placeholders/placeholders/title.php | title | Placeholder | 90 | 3 | B | - |
| classes/placeholders/placeholders/type.php | type | Placeholder | 117 | 3 | B | - |
| classes/placeholders/placeholders/usercalendarurl.php | usercalendarurl | Placeholder (Link) | 104 | 2 | B | - |
| classes/placeholders/placeholders/userid.php | userid | Placeholder | 87 | 3 | B | - |
| classes/placeholders/placeholders/username.php | username | Placeholder | 97 | 2 | B | - |

### message_controller (classes/message_controller.php)

God-Class des Subsystems: kapselt Template-Wahl, Platzhalter-Rendern, iCal, Attachments,
PHPMailer-Sonderweg, Adhoc-Queue und Event-Logging in einer Klasse. 15 Konstruktorparameter,
~16 Felder.

Methoden-Inventar:
- `public __construct(int $msgcontrparam, int $messageparam, int $cmid, int $optionid, int $userid, ?int $bookingid=null, ?int $optiondateid=null, ?array $changes=null, string $customsubject='', string $custommessage='', int $installmentnr=0, int $duedate=0, float $price=0.0, string $rulejson='', ?int $ruleid=null)` — lädt Settings, setzt Sprache, rendert Subject + Body, baut `messagedata` (message_controller.php:159).
- `private get_email_body(): string` — wählt Template (Custom/Global/Instanz/Default-String), ersetzt `{changes}` und ruft `placeholders_info::render_text()` (message_controller.php:348).
- `private get_message_data_send_now(): message` — baut `\core\message\message` für Direktversand (message_controller.php:413).
- `private get_message_data_queue_adhoc(): stdClass` — baut Datenobjekt für die Adhoc-Task inkl. Attachments (message_controller.php:454).
- `public send_or_queue(): bool` — zentraler Versand: DO_NOT_SEND-Guards, Adhoc-Branch, iCal-Datei-Handling, Custom-Attachment, PHPMailer- vs. `message_send`-Wahl, `message_sent`-Event (message_controller.php:513; ~220 LOC, Hotspot).
- `private send_mail_with_adhoc_task(): bool` — queued `send_confirmation_mails`, optional Kopie an Booking-Manager (message_controller.php:742).
- `private get_attachments(bool $updated=false): array` — erzeugt iCal-Anhang (create/cancel) über `ical` (message_controller.php:793).
- `public get_messagebody(): string` — Getter (message_controller.php:833).
- `public set_custom_attachment(string $filepath, string $filename): void` — registriert temporären Datei-Anhang (message_controller.php:847).
- `private get_message_fieldname()` — mappt `MSGPARAM_*` → Template-Feldname, wirft bei Unbekanntem (message_controller.php:856).
- `private send_message_with_ical(message $eventdata): bool` — PHPMailer-Direktversand mit inline-iCal (message_controller.php:913).
- `private user_inrelevant_core_checks_for_mailsending(): bool` / `private user_relevant_core_checks_for_mailsending(array &$userlist, bool $mustnotbeempty=false): bool` — Mail-Vorabprüfungen (Behat/noemailever, gültige Empfänger) (message_controller.php:988, 1011).

Kollaborateure: `singleton_service`, `booking_settings`, `booking_option_settings`, `booking_option`,
`placeholders\placeholders_info`, `ical`, `output\renderer`/`bookingoption_changes`,
`task\send_confirmation_mails`, `\core\task\manager`, `event\message_sent`, `event\booking_debug`,
`booking_context_helper`. Persistenz: `booking_rules` (lesend), Moodle-Filestorage
(`message_attachments`-Filearea), Plugin-Config.

### placeholders_info (classes/placeholders/placeholders_info.php)

Substitutions-Engine und Registry der Platzhalterklassen.

- `public static render_text(string $text, int $cmid=0, int $optionid=0, int $userid=0, int $installmentnr=0, int $duedate=0, float $price=0, int $descriptionparam=…, ?string $rulejson=null, $pollurl=false): string` — Token-Erkennung, Klassen-Dispatch (mod_booking + bookingextension-Namespaces), Array-Reduktion, `{#}{/}`-Abschnittslogik, Fallback auf `customfields` (placeholders_info.php:71).
- `public static return_list_of_placeholders($pollurl=false): string` — baut HTML-`<ul>` der lokalisierten Platzhalter (Editor-Hilfe) (placeholders_info.php:243).
- `private static create_list_of_localized_placeholders($pollurl=false)` — sammelt alle `placeholders\placeholders\*`- und `bookingextension_*`-Klassen, filtert via `is_applicable()`/`for_pollurl()` (placeholders_info.php:269).

Statischer State: `public static array $placeholders` (Wert-Cache), `public static array $localizedplaceholders`.

### placeholder_base (classes/placeholders/placeholder_base.php)

Minimal-Basis: `public static is_applicable(): bool` (Default false) und
`public static for_pollurl(): bool` (Default false). Alle 73 Leaf-Platzhalter erweitern sie.

### Leaf-Platzhalter (classes/placeholders/placeholders/*.php) — 73 Klassen

Einheitliches Muster: jede Klasse implementiert
`public static return_value(int $cmid, int $optionid, int $userid, int $installmentnr, int $duedate, float $price, string &$text, array &$params, int $descriptionparam[, string $rulejson]): string|array`
plus `public static is_applicable(): bool` (i.d.R. `true`). Optional zusätzlich
`public static for_pollurl(): bool` (true bei `baid, bookingoptionname, coursename, courseid, email,
firstname, lastname, optionid, selflearningcourse, userid, type`) und/oder
`public static return_placeholder_text(): string` (lokalisierter Editor-Hinweis, z.B. bei
`customfields`, `changes`). Werte werden in `placeholders_info::$placeholders` (Request-Cache)
gehalten; Datenquelle ist meist `singleton_service::get_instance_of_booking_option_settings()` /
`…_of_user()`.

Bemerkenswerte Sonderfälle:
- **customfields** (customfields.php:55) — Catch-all-Fallback in der Engine für nicht als Klasse
  existierende Tokens; löst Booking-Custom-Felder *und* User-Profilfelder auf, behandelt das
  `-related`-Suffix (Profilfeld des `relateduserid` aus dem Event), mutiert `$text`/`$fieldexists`
  per Referenz. Doppelte Verantwortung (Booking-CF + Profil-CF) → Score C.
- **changes** (changes.php:58) — restauriert ein `bookingoption_updated`-Event aus `rulejson` und
  rendert dessen Simplified-Description.
- **price / installmentprice / numberofinstallment / duedate** — Installment-/Raten-Werte aus
  `installmentnr`/`duedate`/`price`-Parametern bzw. shopping_cart-Daten.
- **Related-Varianten** (`firstnamerelated`, `lastnamerelated`, `emailrelated`) und Event-getriebene
  (`bookedslotsfromevent`, `optiondatefromevent`, `restresponse`, `eventdescription`,
  `bookingconfirmationlink`, `enrollink`, `qrenrollink`, `shoppingcartplaceholder`) lesen Daten aus
  dem serialisierten `rulejson`/Event.
- **DB-berührend:** `certificateurl`, `optiondatefromevent`, `semester` (`global $DB`).
- **Slots:** `slotsbooked/slotscancelled/slotsmovedfrom/slotsmovedto` — kompakte Slotbooking-Listen.

### scheduledmails (classes/local/scheduledmails.php)

Auflistung und Bereinigung geplanter (adhoc) Mails.

- `public static get_sql($contextid=1): array` — liefert `[$fields,$from,$where,$params]` mit
  DB-Familien-spezifischer JSON-Extraktion aus `task_adhoc.customdata`, gejoint auf `booking_rules`
  und `user`, gefiltert nach Rule-Kontext (scheduledmails.php:40).
- `public static is_task_still_valid(stdClass $values): bool` — prüft via `rules_info::get_rule()` +
  `check_if_rule_still_applies()`, ob ein geplanter Task noch gilt (scheduledmails.php:113).
- `public static cleanup_invalid_tasks_in_context(int $contextid, int $pagesize=100000): array` —
  baut Tabelle und delegiert (scheduledmails.php:173).
- `public static cleanup_invalid_tasks_from_table(scheduledmails_table $table, int $pagesize): array` —
  inspiziert **gerenderte Tabellenzeilen** (`status`-Spalten-Text == lokalisiertes "no") und löscht
  passende `task_adhoc`-Records; purged Caches (scheduledmails.php:187).

Kollaborateure: `booking_rules\rules_info`, `output\scheduledmails`, `table\scheduledmails_table`.
Persistenz: `task_adhoc` (lesend/löschend), `booking_rules`, `user`; Cache `scheduledmailscache`.

### templaterule (classes/local/templaterule.php)

- `public static get_template_rules(): array` — Select-Optionen: Default + Code-Template-Klassen
  (`booking_rules\rules\templates`, nur wenn `bookingruletemplatesactive`) + DB-Rules mit
  `useastemplate=1` (templaterule.php:46).
- `public static get_template_record_by_id(int $id): object` — liefert Template-Record einer
  Code-Template-Klasse über negierte `$templateid` (templaterule.php:85).

Persistenz: `booking_rules` (lesend), Plugin-Config.

## Persistenz

- **DB-Tabellen:** `booking_rules` (Rule-JSON für Mailaktionen, Templates; lesend in
  message_controller/scheduledmails/templaterule), `task_adhoc` (geplante Mails; gelesen+gelöscht in
  scheduledmails), `user` (Empfänger/Sender; join), diverse Optionstabellen indirekt über
  `booking_option_settings`.
- **Filestorage:** Filearea `mod_booking/message_attachments` (itemid = Empfänger-User-ID) für iCal-
  und Custom-Attachments; Dateien werden nach Versand wieder gelöscht (außer unter PHPUNIT).
- **Caches:** MUC-`scheduledmailscache` (+ Event `setbackscheduledmailscache`), Invalidierung
  `setbackbookinginstances` vor Versand, `setbackeventlogtable` nach `message_sent`. Request-Cache
  `placeholders_info::$placeholders`/`$localizedplaceholders` (statisch, kein MUC).
- **Config:** `global<fieldname>`-Mailtemplates, `mailtemplatessource`, `usenonnativemailer`,
  `sendmessagesforinvisibleoptions`, `bookingdebugmode`, `bookingruletemplatesactive`.

## Extension-Points

- **Platzhalter-Plugins:** Beliebige `bookingextension_*`-Subplugins können eigene Platzhalter unter
  `…\placeholders\` bereitstellen; sie werden automatisch über die Namespace-Iteration in
  `placeholders_info` (render_text Z.108, create_list Z.282) erkannt — kein zentraler Registry-Eintrag
  nötig.
- **placeholder_base-Vertrag:** Neue Platzhalter erben von `placeholder_base` und liefern
  `return_value()`/`is_applicable()`/(optional) `for_pollurl()`/`return_placeholder_text()`.
- **Rule-Templates:** Code-Klassen unter `booking_rules\rules\templates` (statisches `$templateid`)
  erweitern `templaterule`/Template-Dropdown automatisch.
- **iCal/Versand-Verhalten** wird per Rule-`actiondata` (`sendical`, `sendicalcreateorcancel`) und
  Plugin-Config gesteuert; `set_custom_attachment()` ist generischer Anhang-Hook für Rules.

## Bekannte Schulden (→ Blueprint)

- **message_controller ist eine God-Class (P1):** 1052 LOC, 15 Konstruktorparameter, ~16 Felder, und
  `send_or_queue()` ~220 LOC (message_controller.php:513-736) vermengt Versandentscheidung, iCal-Datei-
  IO, Custom-Attachment-IO, PHPMailer-Sonderweg und Eventlogging. Kandidaten zum Herauslösen:
  Attachment-Storage-Helfer, iCal-Branch, PHPMailer-Versand, Template-Resolver.
- **Versandlogik im Konstruktor (P1):** Der Konstruktor rendert bereits Texte und baut `messagedata`
  (message_controller.php:248-340) inkl. `cache_helper::invalidate_by_event` (selbst als „bad idea"
  kommentiert, Z.196) — schlecht testbar, Seiteneffekte vor `send_or_queue()`.
- **scheduledmails-Cleanup über gerenderte Tabelle (P2):** `cleanup_invalid_tasks_from_table()`
  parst den lokalisierten `status`-Spaltentext (`== "no"`) aus formatierten HTML-Zeilen
  (scheduledmails.php:199-218) statt direkt `is_task_still_valid()` zu nutzen → sprach-/render-
  abhängig und fragil; `is_task_still_valid()` existiert, wird hier aber nicht aufgerufen.
- **Roh-SQL mit String-LIKE-JSON-Filter (P2):** `get_sql()` filtert `customdata LIKE '{%'` /
  `LIKE '%"ruleid"%'` (scheduledmails.php:92-93) und dupliziert DB-Dialekt-Zweige; brüchig bei
  JSON-Formatvarianten.
- **placeholders_info::render_text Komplexität (P2):** Regex-basierte Token-/Abschnittslogik
  (placeholders_info.php:198-231) mit Mehrfach-`str_replace`, totem Zweig
  `'/\$\{placeholder\}/'` (Z.221) und doppelter Ersetzung (Z.166 und Z.183) — schwer nachvollziehbar,
  ungetestete Edge-Cases bei verschachtelten `{#}{/}`-Sektionen.
- **Statischer, nie geleerter Request-Cache (P3):** `placeholders_info::$placeholders` wächst über den
  Request; in Long-running-/Cron-Kontexten (Massenmails) potenziell stale/anwachsend, ohne
  Invalidierung pro Option/User.
- **Boilerplate-Duplikation (P3):** 73 Leaf-Klassen wiederholen Caching- und
  `get_called_class()`-Muster nahezu identisch; ließe sich teils in `placeholder_base` heben.
- **PHPMailer-Sonderweg (P3):** `send_message_with_ical()` (message_controller.php:913) repliziert
  Core-Mailer-Setup (From/CC/Divert) und schluckt Exceptions still (`catch{return false;}`),
  fehlerhafte `$subject`-Referenz in Divert-Zweig (message_controller.php:1028, Variable nicht im
  Scope).
