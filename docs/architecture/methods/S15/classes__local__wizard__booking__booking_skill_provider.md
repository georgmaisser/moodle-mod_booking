# booking_skill_provider — Methoden-Doku
**Datei:** `classes/local/wizard/booking/booking_skill_provider.php` · **LOC:** 29 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`booking_skill_provider` ist ein leerer, als deprecated markierter Kompatibilitaets-Wrapper. Er `extends booking_skill_support` und definiert keinerlei eigene Member — sein einziger Zweck ist es, Legacy-Referenzen auf den alten Provider-Klassennamen weiter aufloesbar zu halten. Die neue Provider-Discovery laeuft laut Klassen-Doc ueber `\bookingextension_agent\local\wizard\skill_provider`. Persistenz: keine. Kollaborateure: erbt vollstaendig die (sehr umfangreiche) `booking_skill_support`-God-Klasse.

## Methoden
Die Klasse deklariert **keine eigenen Methoden, Properties oder Konstanten**; ihr gesamtes Verhalten stammt aus `booking_skill_support`.

## Bewertungs-Resümee
Reiner Alias zur Rueckwaertskompatibilitaet ohne eigene Logik — das ist ein legitimes, minimal-invasives Deprecation-Pattern. Einziger latenter Nachteil: durch das `extends booking_skill_support` zieht der Wrapper die komplette P0-God-Klasse als Oberflaeche mit, statt nur eine schmale Fassade zu bieten; bei kuenftigem Aufraeumen ist die Vererbungsrichtung zu beachten. Da rein deklarativ und ohne Verhalten, funktional unkritisch. Klassen-Score **B / P3**.
