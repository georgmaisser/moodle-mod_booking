# booking_advanced_testcase — Methoden-Doku
**Datei:** `classes/local/testing/booking_advanced_testcase.php` · **LOC:** 33 · **Subsystem:** S22 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`mod_booking\local\testing\booking_advanced_testcase` ist eine reine Rueckwaerts-Kompatibilitaets-Aliasklasse: eine abstrakte Klasse ohne eigenen Body, die lediglich `\mod_booking\tests\booking_advanced_testcase` erweitert. Sie existiert, damit aeltere/agent-spezifische Tests, die noch den `local\testing`-Namespace importieren, weiterhin gegen die kanonische Test-Basisklasse unter `tests/` laufen. Keine Persistenz, keine Properties, keine Methoden, keine Kollaborateure ausser der Elternklasse.

## Methoden
Keine. Die Klasse deklariert keinerlei eigene Methoden oder Properties; ihr gesamtes Verhalten wird von `\mod_booking\tests\booking_advanced_testcase` geerbt.

## Bewertungs-Resümee
Triviale, korrekt umgesetzte Alias-/Shim-Klasse mit einem einzigen Zweck (Namespace-Kompatibilitaet fuer Legacy-Tests). Kein Zustand, keine Logik, keine Risiken. Klassen-Score **A / -**.
