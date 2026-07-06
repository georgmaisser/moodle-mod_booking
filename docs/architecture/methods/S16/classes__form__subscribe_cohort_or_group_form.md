# subscribe_cohort_or_group_form — Methoden-Doku
**Datei:** `classes/form/subscribe_cohort_or_group_form.php` · **LOC:** 114 · **Subsystem:** S16 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`subscribe_cohort_or_group_form` ist eine klassische `moodleform` (kein Dynamic-Form) zur Massen-Einschreibung einer Buchungsoption per Kohorte und/oder Gruppe. Sie definiert nur die Eingabemaske; die eigentliche Subscription-Logik (Aufloesen von Cohort-/Group-Mitgliedern und Buchen) liegt beim aufrufenden Skript (`subscribeusers.php`-Pfad). Keine eigene Persistenz. Kollaborateure: `$COURSE` (Gruppen des Kurses), `groups_get_all_groups()`, `context_system` (Cohort-Autocomplete-Kontext), Core-AMD `core/form-cohort-selector`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut die Maske: versteckte `id`/`optionid`, einen aufklappbaren Cohort-Header mit AJAX-Cohort-Autocomplete (`multiple`, `data-includes=all`, systemweiter Kontext) und einen Group-Header mit Autocomplete, das aus `groups_get_all_groups($COURSE->id)` befuellt wird; beide Felder sind `required`. Abschluss mit `add_action_buttons`. **Seiteneffekte:** `context_system::instance()`, `groups_get_all_groups($COURSE->id)` (DB-Read aller Kursgruppen), mutiert `$this->_form`. **Bewertung:** B — beide Felder per `addRule('required')`, aber der globale `$COURSE`-Zugriff bindet das Formular an den ambienten Kurskontext statt an die `optionid`; bei Aufruf ausserhalb eines Kurs-Page-Setups liefert `groups_get_all_groups` ein leeres Set.

### `public function validation($data, $files)` — public
- **Zweck:** Formvalidierung. **Seiteneffekte:** keine. **Rueckgabe:** immer `[]`. **Bewertung:** B — keine zusaetzliche Validierung; da Cohort und Group beide `required` sind, kann ein Submit nur mit beiden Feldern gefuellt erfolgen, was inhaltlich fraglich ist (entweder-oder waere erwartbar), aber kein Crash-Risiko.

## Bewertungs-Resümee
Reine Eingabemaske ohne Geschaeftslogik. Die `required`-Regel auf *beiden* Selektoren erzwingt das gleichzeitige Angeben von Kohorte UND Gruppe, was den eigentlich gedachten Entweder-oder-Anwendungsfall einschraenkt; die Abhaengigkeit von `$COURSE` statt von der `optionid` ist eine latente Kontext-Falle. Funktional unkritisch. Klassen-Score **B / P3**.
