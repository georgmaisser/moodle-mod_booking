# tagtemplatesadd_form — Methoden-Doku
**Datei:** `tagtemplatesadd_form.php` · **LOC:** 86 · **Subsystem:** S21 · **Klassen-Score:** B
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
`tagtemplatesadd_form` ist ein klassisches `moodleform` (globaler Namespace, kein `mod_booking\form\...`) zum Anlegen/Bearbeiten eines Tag-Templates. Es definiert drei Felder (Tag-Name, Editor-Text, verstecktes `tagid`) und normalisiert in `get_data()` das Editor-Array auf einen reinen Text. Persistenz erfolgt nicht hier, sondern im Controller `tagtemplatesadd.php`. Kollaborateur: `moodleform` (formslib).

## Methoden

### `public function definition()` — public
- **Zweck:** Baut das Formular: Text-Feld `tag` (`PARAM_NOTAGS`, required client-side), Editor-Feld `text` (`PARAM_CLEANHTML`, required client-side), verstecktes `tagid` (`PARAM_RAW`), plus Action-Buttons mit Label `savenewtagtemplate`. **Seiteneffekte:** mutiert `$this->_form`. **Bewertung:** B — Funktional korrekt; `tagid` als `PARAM_RAW` ist unnoetig lax (sollte `PARAM_INT` sein), wird aber im Controller separat per `PARAM_INT` neu eingelesen.

### `public function validation($data, $files)` — public
- **Zweck:** Server-seitige Validierung. **Rueckgabe:** immer `[]` (leer = keine Fehler). **Seiteneffekte:** keine. **Bewertung:** C — No-op-Validierung; die `required`-Regeln sind nur `'client'`-seitig (Z.48/52). Bei deaktiviertem JS oder direktem POST gibt es keinerlei serverseitige Pflichtfeld-Pruefung. Fuer ein admin-only Form (Capability `updatebooking`) niedrige Prio.

### `public function get_data()` — public
- **Zweck:** Holt `parent::get_data()` und flacht das Editor-Feld ab: `$data->text = $data->text['text']` (verwirft das `format`-Element des Editors). **Rueckgabe:** das Daten-Objekt oder null. **Seiteneffekte:** keine. **Bewertung:** B — Pragmatische Normalisierung; verwirft bewusst das Editor-`format`, was zu den Anwendungsfaellen (reiner Template-Text) passt.

## Bewertungs-Resümee
Kleines, konventionelles moodleform mit korrekter Feld-Definition und sinnvoller `get_data()`-Normalisierung. Schwaechen: rein client-seitige `required`-Regeln bei leerer serverseitiger `validation()` (kein Pflichtfeld-Schutz bei Direkt-POST) und ein zu laxes `PARAM_RAW` auf `tagid`. Funktional unkritisch (admin-gated). Klassen-Score **B**.
