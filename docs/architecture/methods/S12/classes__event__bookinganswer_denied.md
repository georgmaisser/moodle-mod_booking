# bookinganswer_denied — Methoden-Doku
**Datei:** `classes/event/bookinganswer_denied.php` · **LOC:** 90 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_denied` ist ein Moodle-Logevent (`\core\event\base`), das das Ablehnen eines Buchungsantrags signalisiert. Zustandslos; beschreibt nur Standard-Event-Metadaten und Praesentations-Helfer. Strukturell identisch zu `bookinganswer_confirmed` (nur andere Sprachstrings). Persistenz: Moodle-Logstore, `objecttable = booking_options`. Kollaborateure: Event-Manager, `get_string` (`bookingoptiondenied[:description]`), `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Basismetadaten: `crud = 'u'`, `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_options'`. **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Menschenlesbarer Eventname. **Seiteneffekte:** `get_string('bookingoptiondenied', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Beschreibungstext fuer den Log-Eintrag. **Seiteneffekte:** `get_string('bookingoptiondenied:description', 'mod_booking', $this->data)`. **Rueckgabe:** string. **Bewertung:** B — uebergibt wie bei `bookinganswer_confirmed` das gesamte `$this->data`-Array als Platzhalterquelle statt eines kuratierten Objekts.

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Buchungsinstanz-Ansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt gesetzte `relateduserid` (der abgelehnte Teilnehmer). **Seiteneffekte:** `parent::validate_data()`; wirft `\coding_exception` bei Fehlen. **Bewertung:** A.

## Bewertungs-Resümee
Praktisch ein Klon von `bookinganswer_confirmed` mit anderen Sprachstrings; korrekt und schlank. Dieselbe latente Kopplung in `get_description` (volles `data`-Array). Funktional unkritisch. Klassen-Score **B / P3**.
