# save_slot_selection — Methoden-Doku
**Datei:** `classes/external/save_slot_selection.php` · **LOC:** 183 · **Subsystem:** S11 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`save_slot_selection` ist eine externe Webservice-Funktion (`extends core_external\external_api`) fuer das Slotbooking-Subsystem (S14): Sie validiert serverseitig die von einem Nutzer ausgewaehlten Zeitslots (Schluessel `"start:end"`), prueft Maximalanzahl, Lehrer-Anforderungen und Verfuegbarkeit, persistiert eine gueltige Auswahl im Slot-Store und berechnet den Gesamtpreis. Kollaborateure: `singleton_service` (Option-Settings), `slot_availability` (Lehrerbedarf + Verfuegbarkeits-Evaluation), `slot_price` (Preis), `slotbookingstore` (Persistenz), Capability `mod/booking:conditionforms` (System-Kontext), Kontextvalidierung ueber `context_module`. Mehr Domaenenlogik als die uebrigen WS-Klassen im Subsystem (daher eigene private Helper-Methode).

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Beschreibt `optionid` (PARAM_INT), `userid` (PARAM_INT, Default 0), `selection` (PARAM_RAW, JSON-Liste von Slot-Keys) und `teacherselection` (PARAM_RAW, JSON-Map Key→Lehrer-Ids, Default `'{}'`). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(int $optionid, int $userid, string $selection, string $teacherselection = '{}'): array` — public static
- **Zweck:** Kernmethode. Validiert Parameter, loest `userid` (0 → aktueller User), laedt Option-Settings, validiert den Modul-Kontext und die Capability `mod/booking:conditionforms`, normalisiert die Slot-Keys, dekodiert die Lehrer-Map, prueft Max-Slots, iteriert ueber jeden Key (Start<End, korrekte Lehreranzahl, Verfuegbarkeit via `slot_availability::evaluate_slot_for_user`), persistiert bei Fehlerfreiheit die Auswahl im `slotbookingstore` und berechnet den Preis. **Seiteneffekte:** `validate_parameters`, `validate_context(context_module::instance($settings->cmid))`, `require_capability`, pro Slot ein `slot_availability::evaluate_slot_for_user`-Aufruf, `slotbookingstore::set_slotbooking_data` (DB/Store-Schreibzugriff), `slot_price::calculate_price`. **Rueckgabe:** `['valid' => bool, 'errors' => json, 'price' => float]`. **Bewertung:** C — funktional dicht und defensiv (Typ-Casts, `array_pad`, leere Sammlungen). Zwei Auffaelligkeiten: (1) **IDOR-Vektor:** `userid` ist frei vom Client setzbar (`$params['userid'] ?: $USER->id`); ein User mit `conditionforms` kann eine Slot-Auswahl im Store *eines anderen Users* persistieren und dessen Preis berechnen, ohne dass die fremde userid gegen den eigenen Account geprueft wird. (2) `evaluate_slot_for_user` wird pro Slot-Key in der Schleife aufgerufen — potenzielles N+1; laut Projektnotiz wird der teure Teil bereits intern in `slot_availability.php` hochgehoben, der Aufruf-Overhead pro Slot bleibt aber bestehen. Fehlersammlung ueberschreibt denselben Key `slot_selection` mehrfach (nur die letzte Fehlerursache bleibt sichtbar).

### `private static function normalise_keys(string $selection): array` — private static
- **Zweck:** Dekodiert die Selection (JSON-Array oder Komma-getrennter String) zu einer getrimmten, deduplizierten, leerstrings-freien Key-Liste. **Seiteneffekte:** keine (rein funktional). **Rueckgabe:** `string[]`. **Bewertung:** A — robuster Fallback (JSON → CSV), saubere Filterkette.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt das Ergebnis (`valid` PARAM_BOOL, `errors` PARAM_RAW JSON, `price` PARAM_FLOAT). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Die inhaltsreichste WS-Klasse des Subsystems: serverseitige Slot-Validierung mit sauberer Normalisierung und defensiver Iteration. Hauptkritik ist der frei setzbare `userid`-Parameter (IDOR-Potenzial in den Slot-Store eines fremden Users) sowie die Per-Slot-Evaluations-Schleife und die einander ueberschreibenden Fehler-Keys. Klassen-Score **C / P3**.
