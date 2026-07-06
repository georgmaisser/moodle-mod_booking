# custom_completion — Methoden-Doku
**Datei:** `classes/completion/custom_completion.php` · **LOC:** 99 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`custom_completion` ist die mod_booking-Implementierung von `core_completion\activity_custom_completion` und definiert die aktivitaetsbezogene benutzerdefinierte Abschlussregel `completionoptioncompleted`. Sie haelt keinen eigenen Zustand (Properties `$cm`, `$userid` stammen aus der Basisklasse) und liest den Abschlussstatus direkt aus `booking_answers`. Persistenz: nur lesend auf Tabelle `booking_answers`; Konfigurationswert `enablecompletion` kommt aus `booking->settings`. Kollaborateure: `singleton_service` (Booking-Instanz per cmid), `$DB`, Core-Completion-Framework (ruft `get_state`/`get_sort_order`/`get_custom_rule_descriptions` auf), `cm->customdata['customcompletionrules']`.

## Methoden

### `public function get_state(string $rule): int` — public
- **Zweck:** Liefert den Abschlusszustand der Regel: COMPLETE, wenn der Nutzer mindestens `enablecompletion` viele als `completed=1` markierte Buchungsantworten in dieser Booking-Instanz hat. **Seiteneffekte:** `validate_rule($rule)` (wirft bei unbekannter Regel), `singleton_service::get_instance_of_booking_by_cmid($this->cm->id)`, `$DB->count_records('booking_answers', [...])`; wirft `Exception`, wenn die Booking-Instanz nicht gefunden wird. **Rueckgabe:** `COMPLETION_COMPLETE` / `COMPLETION_INCOMPLETE`. **Bewertung:** B — Logik korrekt; der Kommentar „Feedback only supports completionsubmit" (Z.59) ist ein irrefuehrender Copy-Paste-Rest aus mod_feedback und passt nicht zur Booking-Semantik. Die Schwellenwert-Logik `enablecompletion <= $status` bedeutet: `enablecompletion` ist die geforderte Anzahl abgeschlossener Optionen, nicht ein Boolean — funktional korrekt, aber der Settingsname suggeriert ein Flag.

### `public static function get_defined_custom_rules(): array` — public static
- **Zweck:** Deklariert die von diesem Modul definierte Regelmenge `['completionoptioncompleted']`. **Seiteneffekte:** keine. **Rueckgabe:** Liste der Regelnamen. **Bewertung:** A — triviale, frameworkkonforme Deklaration.

### `public function get_custom_rule_descriptions(): array` — public
- **Zweck:** Liefert die menschenlesbare Beschreibung der Regel fuer die UI, mit dem konfigurierten Schwellenwert aus `cm->customdata` als String-Parameter. **Seiteneffekte:** `get_string('completionoptioncompletedcminfo', 'booking', ...)`. **Rueckgabe:** Map `regelname => Beschreibungstext`. **Bewertung:** A — Null-Coalesce-Default 0 schuetzt vor fehlendem customdata.

### `public function get_sort_order(): array` — public
- **Zweck:** Bestimmt die Anzeigereihenfolge der Regeln (hier eine einzige). **Seiteneffekte:** keine. **Rueckgabe:** geordnete Regelnamenliste. **Bewertung:** A.

## Bewertungs-Resümee
Schlanke, frameworkkonforme Custom-Completion-Implementierung mit einer einzigen Regel. Einziger Schwachpunkt ist der irrefuehrende mod_feedback-Restkommentar in `get_state`; funktional und Performance-seitig unkritisch (ein `count_records` pro Aufruf). Klassen-Score **B / P3**.
