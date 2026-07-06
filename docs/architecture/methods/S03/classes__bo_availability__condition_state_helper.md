# condition_state_helper — Methoden-Doku
**Datei:** `classes/bo_availability/condition_state_helper.php` · **LOC:** 158 · **Subsystem:** S03 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`condition_state_helper` loest fuer eine gegebene Condition-ID einen Tri-State auf: `STATE_INACTIVE` (0), `STATE_FREEZE` (1) oder `STATE_SKIP_AND_FREEZE` (2). Es kapselt die Konfigurations-Kompatibilitaet: zuerst wird das neue Config-Format (`availabilityconditionsettings`, mit Fallback auf den experimentellen Key `availabilityconditionstates`) gelesen, andernfalls werden die Legacy-Skip-Listen (`skipableconditions`, im Enrollink-Kontext zusaetzlich `enrollinkskipconditions` plus drei hartkodierte Defaults) auf Skip-and-Freeze gemappt. Persistenz: keine eigene; liest ausschliesslich `get_config('booking', ...)`. Kollaborateure: Konsument ist `condition_visibility_manager`, der die drei `should_*`-Praedikate fuer Freeze/Hide im Options-mform nutzt. Keine Properties; drei oeffentliche State-Konstanten.

## Methoden

### `public function get_condition_state(int $conditionid, bool $isenrollinkcontext = false): int` — public
- **Zweck:** Zentrale Aufloesung des effektiven States; neue Config gewinnt, sonst Legacy-Skip-Liste -> SKIP_AND_FREEZE, sonst INACTIVE. **Seiteneffekte:** liest Config ueber `get_configured_states()` und `get_legacy_skipped_conditions()` (jeweils `get_config`). **Rueckgabe:** State-Int (0/1/2). **Bewertung:** B — Lesbare Praezedenz-Kaskade; minimaler Nachteil: bei jedem Aufruf erneutes Config-Lesen ohne Memoisierung, im mform-Rendering ueber alle Conditions wiederholt aufgerufen.

### `public function should_skip_condition(int $conditionid, bool $isenrollinkcontext = false): bool` — public
- **Zweck:** Praedikat, ob die Condition bei der Auswertung uebersprungen wird. **Seiteneffekte:** delegiert an `get_condition_state()`. **Rueckgabe:** true nur bei State == SKIP_AND_FREEZE. **Bewertung:** A.

### `public function should_freeze_condition(int $conditionid, bool $isenrollinkcontext = false): bool` — public
- **Zweck:** Praedikat, ob die Felder der Condition im Form eingefroren werden. **Seiteneffekte:** delegiert an `get_condition_state()`. **Rueckgabe:** true bei State FREEZE oder SKIP_AND_FREEZE. **Bewertung:** A.

### `private function get_configured_states(): array` — private
- **Zweck:** Liest und normalisiert das neue Config-Format zu `[conditionid => state]`; unterstuetzt sowohl das verschachtelte Format (`{skipstate: n}`) als auch die flache Skalar-Map (Rueckwaerts-Kompatibilitaet). **Seiteneffekte:** `get_config('booking','availabilityconditionsettings')`, Fallback `availabilityconditionstates`; `json_decode`. **Rueckgabe:** `array<int,int>` (leer bei fehlender/ungueltiger Config). **Bewertung:** B — robust gegen Nicht-Array/leere Werte; zwei verschachtelte Format-Pfade machen die Methode etwas dicht, aber defensiv korrekt.

### `private function get_legacy_skipped_conditions(bool $isenrollinkcontext = false): array` — private
- **Zweck:** Baut die Legacy-Skip-Liste aus `skipableconditions`, ergaenzt im Enrollink-Kontext `enrollinkskipconditions` plus die drei hartkodierten Defaults (CAPBOOKINGCHOOSE, JSON_ALLOWEDTOBOOKININSTANCE, JSON_CUSTOMFORM), filtert leere/`'0'`-Eintraege und castet auf int. **Seiteneffekte:** zwei `get_config`-Reads. **Rueckgabe:** `array<int>`. **Bewertung:** B — der Filter `$value !== '' && $value !== '0'` verwirft bewusst die Condition-ID 0; das ist korrekt, da 0 keine valide skippable Condition-ID ist, aber die Doppelbedeutung (leer vs. „0") ist subtil und undokumentiert.

## Bewertungs-Resümee
Saubere, gut getestete Kompatibilitaetsschicht zwischen altem Skip-Listen- und neuem Tri-State-Config-Format. Funktional korrekt und defensiv (Array-/JSON-Guards). Schwaechen sind ausschliesslich nicht-funktional: wiederholtes, nicht memoisiertes Config-Lesen bei Aufruf je Condition und die subtile `'0'`-Filterregel. Klassen-Score **B / P3**.
