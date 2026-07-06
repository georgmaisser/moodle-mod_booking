# certificateconditionsform — Methoden-Doku
**Datei:** `classes/form/certificateconditionsform.php` · **LOC:** 232 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`certificateconditionsform` ist eine `core_form\dynamic_form` zum Anlegen/Bearbeiten von Zertifikats-Bedingungen (Booking-Cert-Conditions). Sie komponiert drei austauschbare Bloecke — Filter, Logic (Condition), Action — die jeweils ueber `*_info`-Services in die mform eingehaengt und sub-element-spezifisch validiert werden. Persistenz: Tabelle `booking_cert_cond` (Spalten `filterjson`/`logicjson`/`actionjson`/`name`/`isactive`/`contextid`), geschrieben ueber `certificate_conditions::save_certificate_condition()` + `save_items_for_condition()`. Kollaborateure: `filters_info`, `conditions_info`, `actions_info`, `certificate_conditions`, `$DB`. Kontext-Capability: `mod/booking:editcertificateconditions`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut Formular: bei vorhandener `id` zuerst `prepare_ajaxformdata()` (laedt persistierte Selector-Defaults), dann Hidden `id`/`contextid`, `name`, `isactive` (advcheckbox, Default abgeleitet) und die drei Bloecke ueber `filters_info`/`conditions_info`/`actions_info`, getrennt durch `<hr>`. **Seiteneffekte:** mutiert `$this->_form` und `$this->_ajaxformdata`; bei Edit DB-Lesezugriff via `prepare_ajaxformdata`. **Bewertung:** B — `$active`-Ableitung (`isset && empty ? 0 : 1`) defaultet auf aktiv, auch wenn kein Wert gesetzt ist; gewollt, aber subtil.

### `public function process_dynamic_submission()` — public
- **Zweck:** Speichert die Bedingung: `certificate_conditions::save_certificate_condition($data)` liefert die `$conditionid`, danach `save_items_for_condition($conditionid, $data)`. **Seiteneffekte:** DB-Schreibzugriff (zwei Stufen). **Rueckgabe:** `$data`. **Bewertung:** A.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Vorbelegung; bei `id` reichert `certificate_conditions::set_data_for_form()` an, sonst rohe AJAX-Daten. **Seiteneffekte:** ggf. DB-Lesezugriff; `set_data()`. **Bewertung:** B — wie bei den Schwester-Dynamic-Forms leichte if/else-Redundanz.

### `public function validation($data, $files)` — public
- **Zweck:** Pflicht-`name`; Filter ist optional (`norestriction` uebersprungen), sonst delegiert an `filters_info::get_filter(...)->validate()`; Logic und Action sind Pflicht (`'0'` => Fehler), sonst delegiert an `conditions_info::get_condition(...)->validate()` bzw. `actions_info::get_action(...)->validate()`; Sub-Fehler werden gemerged. **Seiteneffekte:** keine (Service-Lookups). **Rueckgabe:** `array`. **Bewertung:** B — Vergleich `=== '0'` setzt String-Typ der Hidden/Select-Werte voraus (Form garantiert das); klare Delegation.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Tragerseite `/mod/booking/edit_certificateconditions.php`. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Form-Kontext = `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Liest `contextid` aus `_ajaxformdata` (Fallback System), loest den Kontext und verlangt `mod/booking:editcertificateconditions`. **Seiteneffekte:** wirft bei fehlender Capability. **Bewertung:** C — die `contextid` stammt aus den **client-gelieferten** AJAX-Daten und wird nicht gegen die `contextid` des per `id` referenzierten Records geprueft. Ein Nutzer mit der Capability in einem beliebigen Kontext koennte beim Bearbeiten eine fremde Bedingung adressieren (potenzielle IDOR/Berechtigungsumgehung). Siehe Findings.

### `private function prepare_ajaxformdata(array &$ajaxformdata)` — private
- **Zweck:** Im Edit-Fall (`id>0`) den Record aus `booking_cert_cond` laden und fehlende AJAX-Keys aus den JSON-Spalten rekonstruieren: `certificatefiltertype` aus `filterjson.filtername`, `certificateconditiontype` aus `logicjson.conditionname`/`logicname`, `certificateactiontype` aus `actionjson.actionname`, plus `name`/`contextid`/`isactive`. **Seiteneffekte:** `$DB->get_record(...)`; mutiert `$ajaxformdata` per Referenz. **Rueckgabe:** void (Early-Return bei `id<=0` oder fehlendem Record). **Bewertung:** B — robuste Null-Coalescing-Kette; `json_decode` ohne Fehlerpruefung (kaputtes JSON => Property-Zugriff auf null faellt auf Default via `??`, ok).

### `public function definition_after_data()` — public
- **Zweck:** Reserviert fuer dynamische Folge-Updates nach `set_data()`. **Seiteneffekte:** keine — leerer Stub. **Bewertung:** C — toter Platzhalter ohne Implementierung; ueberschreibt absichtlich den Eltern-Hook mit No-op.

## Bewertungs-Resümee
Gut strukturierte, blockbasierte Dynamic-Form mit konsequenter Delegation an die `*_info`-Services und sauberer Sub-Element-Validierung. Hauptschwaeche ist der `check_access`-Pfad, der auf eine client-kontrollierte `contextid` vertraut (Berechtigungsrisiko), dazu der leere `definition_after_data`-Stub. Klassen-Score **B / P3**.
