# slotrules_page_form — Methoden-Doku
**Datei:** `classes/form/slotrules_page_form.php` · **LOC:** 203 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`slotrules_page_form` ist eine klassische `\moodleform` (kein dynamic_form) zum Anlegen/Bearbeiten von Slot-Regeln des Slotbooking-Subsystems. Eine Slot-Rule ist entweder vom Typ `closed` (Slot sperren) oder `price` (Preis-Override mit Kategorie/Modus/Wert/Waehrung). Die Form rendert versteckte Kontext-IDs (cmid/optionid/ruleid), die allgemeinen Regelfelder (Typ, Prioritaet, optionaler Aktiv-Zeitraum, Wochentage 1-7, Tageszeit-Range) sowie einen per `hideIf` an `ruletype=price` gekoppelten Preisblock. Persistenz: keine eigene (die Form liefert nur Daten an `slot_rule_manager`-Konsumenten). Kollaborateure: `slot_rule_manager` (Konstanten RULETYPE_*/PRICEMODE_*), `mod_booking\price` (Preiskategorien zum aktuellen Option).

## Methoden

### `public function definition(): void` — public
- **Zweck:** Baut alle Formularfelder auf. **Seiteneffekte:** liest `$this->_customdata` (cmid/optionid/ruleid); registriert versteckte Felder, Typ-Select, Prioritaet (Default 100), `useactiverange`-Checkbox mit zwei `date_time_selector` (per `disabledIf` gekoppelt), sieben Wochentag-Checkboxen, zwei Tageszeit-Textfelder, sowie den per `hideIf` auf `ruletype=price` gegateten Preisblock (Kategorie-Select via `get_pricecategory_options($optionid)`, Preismodus, Wert, Waehrung); `add_action_buttons`. **Bewertung:** B — sauber strukturiert; das verstecke Feld heisst `id`, traegt aber den **cmid**-Wert (`$mform->addElement('hidden', 'id', $cmid)`), was leicht zu verwechseln ist, aber konsistent mit Moodle-`id=cmid`-Konvention.

### `public function validation($data, $files): array` — public
- **Zweck:** Validiert Tageszeit-Format (`HH:MM` via Regex), Aktiv-Zeitraum (`activeuntil > activefrom`, nur wenn `useactiverange` gesetzt) und — bei `ruletype=price` — Pflicht und Gueltigkeit der Preiskategorie gegen die erlaubte Liste. **Seiteneffekte:** ruft bei Preis-Regeln erneut `self::get_pricecategory_options($data['optionid'])` (re-instanziiert `mod_booking\price`, DB-Read). **Rueckgabe:** `array` der Fehler-Map. **Bewertung:** B — robuste Validierung mit defensivem Casting/`trim`. Schwaeche: es gibt keine Cross-Pruefung, dass `timerangeend` nach `timerangestart` liegt (nur Format), und ein einzeln gesetztes Range-Ende ohne Start bleibt erlaubt; semantisch wohl tolerierbar.

### `private static function get_pricecategory_options(int $optionid): array` — private static
- **Zweck:** Liefert die auswaehlbaren aktiven Preiskategorien als `identifier => "Name (identifier)"`-Map fuer das Option. **Seiteneffekte:** `new booking_price('option', $optionid)` und Iteration ueber `->pricecategories` (DB-Read ueber den Price-Handler). **Rueckgabe:** `array`; faellt bei leerem Ergebnis auf `['default' => 'default']` zurueck. **Bewertung:** B — defensiver Fallback verhindert ein leeres Select. Wird sowohl in `definition()` als auch in `validation()` aufgerufen, also pro Request potenziell zweimal instanziiert (kleiner doppelter Price-Load; unkritisch).

## Bewertungs-Resümee
Klare, gut validierte klassische moodleform fuer die Slot-Rule-Pflege mit sinnvollem `hideIf`-gegating des Preisblocks und defensivem Kategorie-Fallback. Keine funktionalen Bugs; lediglich die `id=cmid`-Doppelbelegung und der fehlende Start/Ende-Cross-Check der Tageszeit-Range als kosmetische Punkte. Klassen-Score **B / P3**.
