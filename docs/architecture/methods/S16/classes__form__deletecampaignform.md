# deletecampaignform — Methoden-Doku
**Datei:** `classes/form/deletecampaignform.php` · **LOC:** 126 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`deletecampaignform` ist eine schlanke `core_form\dynamic_form` (AJAX-Modal) zur Loesch-Bestaetigung einer Booking-Campaign. Sie zeigt nur einen Bestaetigungstext mit dem Campaign-Namen und delegiert die eigentliche Loeschung an `campaigns_info::delete_campaign()`. Keine eigene Persistenz. Kollaborateure: `campaigns_info` (Loeschung), `context_system`, Edit-Seite `edit_campaigns.php`.

## Methoden

### `public function definition()` — public
- **Zweck:** Fuegt ein Hidden-Field `id` (falls vorhanden) und einen HTML-Bestaetigungstext mit dem in Rot hervorgehobenen Campaign-Namen hinzu. **Seiteneffekte:** mutiert `$this->_form`. **Bewertung:** B — `$ajaxformdata['name']` wird ohne `s()`/Escaping direkt in das HTML-Element interpoliert; bei XSS-faehigem Namen denkbar, jedoch System-Config-gated (nur Admins).

### `public function process_dynamic_submission()` — public
- **Zweck:** Holt die Daten und ruft `campaigns_info::delete_campaign((int)$data->id)`. **Seiteneffekte:** loescht die Campaign. **Rueckgabe:** das Daten-Objekt. **Bewertung:** A.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Setzt die AJAX-Formdaten als Defaults. **Seiteneffekte:** `set_data`. **Bewertung:** A.

### `public function validation($data, $files)` — public
- **Zweck:** Keine Validierung (Bestaetigungs-Dialog). **Rueckgabe:** leeres array. **Bewertung:** A — bewusst leer.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Liefert die Edit-Campaigns-URL. **Bewertung:** A.

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert `context_system::instance()`. **Bewertung:** A.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Verlangt `moodle/site:config` im System-Kontext. **Seiteneffekte:** `require_capability`. **Bewertung:** A — strenger Admin-Schutz passend zur globalen Campaign-Verwaltung.

## Bewertungs-Resümee
Triviale, korrekt strukturierte Bestaetigungs-Form. Einziger Hinweis: der Campaign-Name wird ungeescaped ins Bestaetigungs-HTML eingesetzt — durch das `site:config`-Gate praktisch unkritisch. Klassen-Score **B / P3**.
