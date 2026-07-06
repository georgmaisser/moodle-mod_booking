# condition_visibility_manager — Methoden-Doku
**Datei:** `classes/bo_availability/condition_visibility_manager.php` · **LOC:** 210 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`condition_visibility_manager` wendet die von `condition_state_helper` aufgeloesten Skip/Freeze-States konkret auf ein `MoodleQuickForm` (Options-mform) an. Logik: Berechtigte (Capability `mod/booking:updatebooking` auf System-Kontext) sehen die Felder eingefroren mit Warn-Hinweis, alle anderen sehen sie gar nicht (Hide). Die Liste der einzufrierenden/zu versteckenden Form-Elemente liefert die jeweilige Condition selbst, sofern sie das `freezable_condition`-Interface implementiert (`get_condition_form_elements()`). Persistenz: keine; liest `get_config('booking','conditionwarningatbottom')`. Kollaborateure: `condition_state_helper` (frisch instanziiert je Aufruf), `bo_info::get_skippable_conditions()`, `freezable_condition`, `bo_condition`, `MoodleQuickForm`, `context_system`, `moodle_url`.

## Methoden

### `public function get_skipped_conditions(): array` — public
- **Zweck:** Liefert die IDs aller aktuell als „skip" konfigurierten skippable Conditions. **Seiteneffekte:** instanziiert `condition_state_helper`, iteriert `bo_info::get_skippable_conditions()`. **Rueckgabe:** Liste der Condition-IDs (int). **Bewertung:** B — pro Schleifendurchlauf ein `should_skip_condition()`-Aufruf, der intern erneut die Config liest; bei vielen Conditions redundante Reads.

### `public function freeze_fields_for_condition(MoodleQuickForm &$mform, freezable_condition $condition, bool $skipandfreeze = true): void` — public
- **Zweck:** Friert alle von der Condition deklarierten Form-Elemente ein und fuegt eine statische Warnung hinzu (oben am ersten Element, oder optional unten ueber dem abschliessenden `<hr>`-Divider, wenn `conditionwarningatbottom` gesetzt ist). **Seiteneffekte:** ruft `freeze()` je Element; erstellt/insert-/add-Element; liest `get_config('booking','conditionwarningatbottom')`; greift fuer die Bottom-Variante direkt auf das private `$mform->_elements` und `array_key_last` zu. **Rueckgabe:** void. **Bewertung:** C — die Bottom-Platzierung haengt von QuickForm-Internas ab: Zugriff auf die protected/private Property `_elements`, Annahme dass unbenannte Elemente unter dem leeren Namen `''` indiziert sind, und HTML-String-Sniffing (`strpos(..., '<hr')`). Fragil gegenueber QuickForm-Aenderungen, aber mit Fallback (`addElement`) abgesichert.

### `public function hide_fields_for_condition(MoodleQuickForm &$mform, freezable_condition $condition): void` — public
- **Zweck:** Versteckt alle von der Condition deklarierten Form-Elemente. **Seiteneffekte:** ruft `hide_element()` je Element. **Rueckgabe:** void. **Bewertung:** A.

### `public function disable_elements_in_mform(MoodleQuickForm &$mform, bo_condition $condition, bool $skipandfreeze = true): void` — public
- **Zweck:** Capability-Weiche: Berechtigte -> Freeze+Warnung, andere -> Hide; No-op wenn die Condition nicht `freezable_condition` implementiert. **Seiteneffekte:** `has_capability('mod/booking:updatebooking', context_system::instance())`; delegiert an freeze/hide. **Rueckgabe:** void. **Bewertung:** B — Capability ausschliesslich gegen System-Kontext geprueft; fuer eine pro-Option-Berechtigung (Kurs-/Modulkontext) waere das zu grob, hier aber bewusst als globale Admin-Sicht gewollt.

### `public function is_condition_frozen(int $conditionid): bool` — public
- **Zweck:** Praedikat, ob die Condition eingefroren werden soll. **Seiteneffekte:** instanziiert `condition_state_helper`, delegiert an `should_freeze_condition()`. **Rueckgabe:** bool. **Bewertung:** A.

### `private function disable_element_without_warning(MoodleQuickForm &$mform, string $elementname)` — private
- **Zweck:** Friert ein einzelnes Element ein (nur wenn vorhanden). **Seiteneffekte:** `$mform->freeze()`. **Rueckgabe:** void (untypisiert). **Bewertung:** A.

### `private function hide_element(MoodleQuickForm &$mform, string $elementname)` — private
- **Zweck:** Versteckt ein Element via `hideIf` gegen ein einmalig angelegtes verstecktes Anker-Feld `permanentvalueone` und entfernt zusaetzlich den zugehoerigen `<hr>`-Wrapper per injiziertem Inline-`<script>`. **Seiteneffekte:** fuegt ggf. Hidden-Feld `permanentvalueone` hinzu; `hideIf`; **addElement('html', '<script>...remove()</script>')**. **Rueckgabe:** void (untypisiert). **Bewertung:** C — das Injizieren von Inline-JavaScript ins Formular zum DOM-seitigen Entfernen des `<hr>` (`getElementById(...)?.remove()`) ist ein Anti-Pattern (kein AMD, potenzielle CSP-Probleme, serverseitig nicht testbar) und mischt View-Manipulation in eine Form-Builder-Methode.

### `public function is_condition_skipped(int $conditionid): bool` — public
- **Zweck:** Praedikat, ob die Condition uebersprungen wird. **Seiteneffekte:** instanziiert `condition_state_helper`, delegiert an `should_skip_condition()`. **Rueckgabe:** bool. **Bewertung:** A.

## Bewertungs-Resümee
Funktional korrekter Visibility-Dirigent mit klarer Capability-Weiche. Die Schwaechen sind qualitativer Natur und konzentrieren sich in zwei Methoden: `freeze_fields_for_condition()` koppelt an QuickForm-Internas (`_elements`, HTML-Sniffing), und `hide_element()` injiziert Inline-JavaScript zur `<hr>`-Entfernung (CSP/Testbarkeit/AMD-Konvention). Beide sind mit Guards/Fallbacks abgesichert und keine harten Bugs. Mehrfaches frisches Newing von `condition_state_helper` plus redundante Config-Reads sind ein kleiner Perf-Schoenheitsfehler. Klassen-Score **B / P3**.
