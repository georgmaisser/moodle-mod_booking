# send_mail_to_teachers — Methoden-Doku
**Datei:** `classes/form/send_mail_to_teachers.php` · **LOC:** 179 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`send_mail_to_teachers` ist eine `dynamic_form` fuer den Bulk-Versand einer freien E-Mail (Betreff + HTML-Body) an die Teacher einer Menge von Buchungsoptionen. Die Options-IDs kommen als komma-separierte `checkedids` aus der wunderbyte_table-Bulk-Aktion. Pro Option werden die Teacher aufgeloest und je Teacher genau eine Nachricht ueber den `message_controller` (Custom-Message, Send-Now) versandt; eine Dedup-Liste verhindert Mehrfachversand an denselben Teacher quer ueber Optionen. Persistenz: keine eigene; Versand delegiert an `message_controller`. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `message_controller`, Seite `editoption.php`. Kontext ist modulbasiert (`cmid` aus AJAX-Daten), faellt sonst auf `context_system` zurueck.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut das Formular: Text-Feld `subject`, Editor `emailbody`, und — falls vorhanden — verstecktes `checkedids` aus den AJAX-Daten. **Seiteneffekte:** liest `$this->_ajaxformdata`. **Bewertung:** B — `checkedids` wird hier nur als verstecktes Feld durchgereicht; das versteckte Element bekommt kein `setType`, was bei dynamic_forms ueblicherweise toleriert wird, aber sauberer waere.

### `public function validation($data, $files)` — public
- **Zweck:** Formularvalidierung. **Seiteneffekte:** keine. **Rueckgabe:** immer leeres `array` (keine Validierung). **Bewertung:** C — leerer Validierungsrumpf; weder `subject` noch `emailbody` werden gegen Leere geprueft, sodass leere Massenmails versendet werden koennen. Der umfangreiche `@throws`-Docblock ist irrefuehrend (es wird nichts geworfen).

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert den Modulkontext aus `_ajaxformdata['cmid']`, sonst `context_system`. **Seiteneffekte:** `context_module::instance($cmid)` bzw. `context_system::instance()`. **Rueckgabe:** `context`. **Bewertung:** B — Fallback auf System-Kontext bei fehlendem cmid ist hier gefaehrlich, weil der Access-Check leer ist (siehe unten).

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Soll den Zugriff absichern. **Seiteneffekte:** keine — der Methodenrumpf ist LEER. **Bewertung:** C/P2 — **kein Capability-Check.** Da `dynamic_form`-Submits per AJAX getriggert werden koennen, kann jeder eingeloggte Nutzer mit beliebigen `checkedids` Massenmails an die Teacher fremder Buchungsoptionen ausloesen (Spam-/Missbrauchsvektor). Es fehlt ein `require_capability` (z.B. `mod/booking:updatebooking` o.ae.) auf dem Modulkontext. Siehe Findings.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt die AJAX-Eingangsdaten als Form-Daten. **Seiteneffekte:** `set_data((object)$this->_ajaxformdata)`. **Bewertung:** B.

### `public function process_dynamic_submission()` — public
- **Zweck:** Fuehrt den eigentlichen Bulk-Versand aus. **Seiteneffekte:** `get_data()`; iteriert ueber `explode(',', checkedids)`; je Option `singleton_service::get_instance_of_booking_option_settings($id)` (potenziell N Lookups), je Teacher `new message_controller(...)->send_or_queue()`; Exceptions pro Teacher werden mit `continue` verschluckt. **Rueckgabe:** das `$data`-Objekt. **Bewertung:** C — funktional korrekt inkl. Teacher-Dedup ueber `$alreadysentto`. Schwaechen: (1) keine Eingangs-Validierung (siehe `validation`); (2) `catch (Exception) { continue; }` verschluckt Versandfehler still — kein Logging, der Nutzer bekommt keinerlei Fehlerrueckmeldung; (3) bei sehr vielen `checkedids` linear viele Settings-Lookups und Message-Sends ohne Batching (per-Option N+1-artig, fuer eine bewusste Bulk-Aktion aber vertretbar).

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Rueckkehr-URL. **Seiteneffekte:** keine. **Rueckgabe:** `new moodle_url('/mod/booking/editoption.php')`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional erfuellt die Form ihren Zweck (Bulk-Teacher-Mail mit Dedup), aber drei Schwaechen druecken den Score: der **leere `check_access_for_dynamic_submission`** (Missbrauchs-/IDOR-Vektor auf einen Mail-Versand), die voellig fehlende Validierung (leere Mails moeglich) und das stille Verschlucken von Versand-Exceptions. Klassen-Score **C / P2**.
