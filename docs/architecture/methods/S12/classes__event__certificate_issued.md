# certificate_issued — Methoden-Doku
**Datei:** `classes/event/certificate_issued.php` · **LOC:** 95 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`certificate_issued` ist ein Moodle-Standard-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn fuer einen User ueber `tool_certificate` ein Zertifikat ausgestellt wurde (vgl. `\mod_booking\local\certificateclass`, S19). Es traegt einen `relateduserid` (der Zertifikatsempfaenger) und verweist mit `objecttable` auf die Fremd-Plugin-Tabelle `tool_certificate_issues`. `validate_data()` erzwingt die Praesenz von `relateduserid`. Persistenz: keine eigene. Kollaborateure: `\core\event\base`, `get_string()`, `\moodle_url`, Trigger im Zertifikatsausstellungspfad (S19).

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten: `crud = 'c'`, `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'tool_certificate_issues'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** B — funktional korrekt; `objecttable` zeigt bewusst auf eine `tool_certificate`-Tabelle, was eine cross-plugin Kopplung im Logstore bedeutet (akzeptabel, da das Objekt tatsaechlich dort lebt).

### `public static function get_name()` — public static
- **Zweck:** Uebersetzter Anzeigename (`certificateissued`). **Seiteneffekte:** `get_string('certificateissued', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert die uebersetzte Beschreibung (`certificateissueddesc`), befuellt mit `userid`/`relateduserid`/`objectid`. **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** A — uebersetzbar (besser als die Optiondate-Events).

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Instanz-Ansicht (`view.php?id=<contextinstanceid>`). **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url`. **Bewertung:** B — generischer Instanz-Link statt direktem Verweis aufs ausgestellte Zertifikat; vertretbar.

### `protected function validate_data()` — protected
- **Zweck:** Ruft `parent::validate_data()` und erzwingt, dass `relateduserid` gesetzt ist, sonst `coding_exception`. **Seiteneffekte:** ggf. Exception. **Rueckgabe:** void. **Bewertung:** A — prueft konsistent `$this->data['relateduserid']`.

## Bewertungs-Resümee
Konventionelles, sauber uebersetztes Event mit korrekter Pflichtfeld-Validierung. Bemerkenswert nur die bewusste cross-plugin `objecttable`-Kopplung an `tool_certificate_issues`. Klassen-Score **B / P3**.
