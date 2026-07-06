# modal_send_custom_message — Methoden-Doku
**Datei:** `classes/form/modal_send_custom_message.php` · **LOC:** 345 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`modal_send_custom_message` ist ein `core_form\dynamic_form` (AJAX-Modal aus `report2.php`), das eine frei formulierte Nachricht samt optionalem Datei-Anhang an ausgewaehlte gebuchte User einer Buchungsoption versendet. Die Empfaengerauswahl ist ein Autocomplete ueber alle gebuchten User der Option; vorab abgehakte Zeilen (`checkedids` = `booking_answers`-IDs) werden vorselektiert. Versand erfolgt pro User ueber `message_controller`; zusaetzlich werden `custom_message_sent`- und (ab Schwellwert) `custom_bulk_message_sent`-Events gefeuert. Keine eigene Tabelle. Kollaborateure: `$DB`, `message_controller`, `singleton_service` (Settings/Answers), File-Storage, Event-API, `cache_helper`.

## Methoden

### `private function get_possible_recipients_for_custom_message(int $optionid): array` — private
- **Zweck:** Liefert alle gebuchten User der Option als `id => "Vorname Nachname (id)"`-Map fuer das Autocomplete und als Allow-List beim Versand. **Seiteneffekte:** ein `$DB->get_records_sql` Join `booking_answers` × `user`, gefiltert auf `waitinglist = MOD_BOOKING_STATUSPARAM_BOOKED`. **Rueckgabe:** `array<int,string>`. **Bewertung:** B — saubere, indizierte Einzelabfrage; wird allerdings sowohl in `definition()` als auch in `process_dynamic_submission()` erneut ausgefuehrt (zweifacher Query pro Submit), was vertretbar ist.

### `public function definition()` — public
- **Zweck:** Baut das Formular: Hidden `cmid`/`optionid`/`checkedids`, Empfaenger-Autocomplete (Pflicht), `subject`-Text (Pflicht), `message`-Editor (Pflicht) und einen `attachment`-Filepicker. **Seiteneffekte:** ruft `get_possible_recipients_for_custom_message()` (DB). **Bewertung:** B — vollstaendig; `message`-Editor mit `PARAM_RAW` (Editor-Inhalt, beim Versand nur als Text genutzt).

### `public function validation($data, $files)` — public
- **Zweck:** Erzwingt, dass `selecteduserids` ein nicht-leeres Array ist. **Seiteneffekte:** keine. **Rueckgabe:** Fehler-Array. **Bewertung:** A — minimal, aber ausreichend (Subject/Message via client-`required`).

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Erzwingt `mod/booking:communicate` im Submission-Kontext. **Seiteneffekte:** `require_capability()`. **Bewertung:** A — korrektes Gate auf Modul-Kontext.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Initialwerte setzen: falls keine `selecteduserids` uebergeben, aus `checkedids` (kommaseparierte `booking_answers`-IDs) die zugehoerigen `userid`s aufloesen; ausserdem den Filepicker mit frischem Draft-Itemid initialisieren. **Seiteneffekte:** `$DB->get_in_or_equal` + `$DB->get_fieldset_select('booking_answers', 'userid', ...)`; `file_get_unused_draft_itemid()`. **Bewertung:** B — korrekt; `checkedids` werden als Answer-IDs (nicht User-IDs) interpretiert und sauber gemappt.

### `public function process_dynamic_submission()` — public
- **Zweck:** Versendet die Nachricht. Schneidet die Empfaenger gegen die Allow-List (`array_intersect`), liest einen evtl. hochgeladenen Draft-Anhang einmalig in eine Temp-Datei, baut pro User einen `message_controller` (`SEND_NOW` / `CUSTOM_MESSAGE`) mit optionalem Anhang und ruft `send_or_queue()`. Fuer jeden erfolgreichen User wird `custom_message_sent` getriggert; ab `>=3` Empfaengern und `>=75%` der gebuchten User zusaetzlich `custom_bulk_message_sent`. Baut abschliessend eine Erfolgsmeldung mit Empfaengernamen. **Seiteneffekte:** Singleton-Settings/Answers, File-Storage (Draft lesen + `delete_area_files`), pro User `message_controller::send_or_queue()` (DB/Mailversand), Event-Trigger, mehrfach `cache_helper::purge_by_event('setbackeventlogtable')`, abschliessend `$DB->get_records_list('user', ...)`. **Rueckgabe:** `$data` (mit `message`=Erfolgstext, `success`=1). **Bewertung:** C — funktional korrekt und gegen Empfaenger-Manipulation abgesichert (Intersect mit Allow-List), aber: **(1)** `cache_helper::purge_by_event('setbackeventlogtable')` wird *innerhalb der User-Schleife* fuer jeden Empfaenger erneut aufgerufen — bei vielen Empfaengern ein wiederholter Cache-Purge pro Iteration (Perf, P2); ein einmaliger Purge nach der Schleife wuerde genuegen. **(2)** Pro-User-`message_controller`-Instanziierung kann je nach Controller-Internas N+1-artige Last erzeugen (Placeholder-/Settings-Lookups pro User), hier nicht gebatcht. **(3)** Exceptions beim Versand werden per `continue` still verschluckt — fehlgeschlagene User erscheinen dennoch im Erfolgs-Namelist (`$userids` ungefiltert), die Erfolgsmeldung kann also User auffuehren, an die nichts ging (irrefuehrende Rueckmeldung, P3).

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Modul-Kontext aus `cmid`, sonst System-Kontext. **Seiteneffekte:** `context_module::instance()`. **Rueckgabe:** `context`. **Bewertung:** A.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** URL `/mod/booking/report2.php?optionid=...`. **Seiteneffekte:** keine. **Bewertung:** A — passend zum Aufruf-Kontext.

## Bewertungs-Resümee
Durchdachte Bulk-Messaging-Form mit serverseitiger Allow-List-Absicherung der Empfaenger, Anhang-Handling und differenzierten Events. Schwaechen liegen in `process_dynamic_submission`: der Cache-Purge in der Schleife (P2), potenziell N+1-artige per-User-Controller-Last und die irrefuehrende Erfolgsmeldung bei still verschluckten Versandfehlern. Klassen-Score **C / P2**.
