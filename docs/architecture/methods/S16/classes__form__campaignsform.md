# campaignsform — Methoden-Doku
**Datei:** `classes/form/campaignsform.php` · **LOC:** 156 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`campaignsform` ist eine `core_form\dynamic_form` (Modal-/AJAX-Form) zum Anlegen und Bearbeiten von Booking-Campaigns. Sie haelt selbst keinen Zustand und keine Persistenz; das Element-Setup, Laden und Speichern delegiert sie vollstaendig an `mod_booking\booking_campaigns\campaigns_info`. Persistenz: indirekt ueber `campaigns_info::save_booking_campaign()` (Tabelle der Campaigns). Kollaborateure: `campaigns_info` (Element-Aufbau, Load, Save), `context_system`, `moodle_url`. Kontext ist immer System; geschuetzt durch `moodle/site:config`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Formularelemente; fuegt bei vorhandener `id` (Edit-Fall) ein Hidden-`id` ein und delegiert den restlichen Element-Aufbau an `campaigns_info::add_campaigns_to_mform($mform, $ajaxformdata)`. **Seiteneffekte:** mutiert `$this->_form`; keine Action-Buttons (Modal). **Bewertung:** A — schlanke Delegation; auskommentierter Action-Button-Block bewusst dokumentiert.

### `public function process_dynamic_submission()` — public
- **Zweck:** Holt die validierten Daten via `parent::get_data()` und speichert die Campaign ueber `campaigns_info::save_booking_campaign($data)`. **Seiteneffekte:** DB-Schreibzugriff (in `campaigns_info`). **Rueckgabe:** das `$data`-Objekt. **Bewertung:** A.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Vorbelegung; bei vorhandener `id` reichert `campaigns_info::set_data_for_form()` das aus den AJAX-Daten gecastete Objekt an, sonst werden die rohen AJAX-Daten verwendet. **Seiteneffekte:** ggf. DB-Lesezugriff in `campaigns_info`; `$this->set_data()`. **Bewertung:** B — der else-Zweig castet nur `(object)$this->_ajaxformdata`; identisch zum if-Vorlauf bis auf den Enrichment-Aufruf (leichte Redundanz).

### `public function validation($data, $files)` — public
- **Zweck:** Typ-abhaengige Server-Validierung: fuer `campaign_customfield` Wertebereich von `pricefactor` (0–1) und `limitfactor` (0–2); fuer `campaign_blockbooking` `percentageavailableplaces` (0–100) und Pflicht-`blockinglabel`; generisch Pflicht-`name` und `starttime < endtime`. **Seiteneffekte:** keine. **Rueckgabe:** `array` Feld→Fehlermeldung. **Bewertung:** B — greift `$data['bookingcampaigntype']`/`$data['pricefactor']` etc. ohne isset-Guard zu; die Fehler-Strings `error:limitfactornotbetween1and2` zu Bereich 0–2 sind sprachlich inkonsistent (kosmetisch). Funktional korrekt, da Form-Elemente die Keys garantieren.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Trager-Seite `/mod/booking/edit_campaigns.php`. **Seiteneffekte:** keine. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Kontext der Form = `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz; verlangt `moodle/site:config` im Systemkontext. **Seiteneffekte:** wirft bei fehlender Capability. **Bewertung:** B — Kommentar raeumt selbst ein, dass eine dedizierte Campaigns-Capability fehlt; `site:config` ist grob, aber sicher (nur Admins).

## Bewertungs-Resümee
Saubere, duenne Dynamic-Form als Adapter auf `campaigns_info`; gesamte Fachlogik liegt im Info-Service. Schwaechen rein kosmetisch (fehlende isset-Guards in `validation`, inkonsistenter Fehlertext, grobe `site:config`-Gate statt eigener Capability). Funktional unkritisch. Klassen-Score **B / P3**.
