# Copilot Coding Guidelines fuer mod_booking (Draft v0.2)

Status: Arbeitsleitfaden fuer den Coding-Agent (Copilot), nicht fuer Laufzeitverhalten des Booking-AI-Agents.
Scope: Nur Implementierungsverhalten bei Code-Aenderungen in mod_booking.

## 1. Scope und Fokus

- Arbeite ausschliesslich in mod_booking.
- Ignoriere alle Diffs ausserhalb von booking.
- Frage nicht erneut nach Erweiterung des Scopes, solange booking ausreicht.

## 2. Arbeitsstil bei Code-Aenderungen

- Minimal-invasive Aenderungen bevorzugen.
- Bestehende Architektur, APIs und Dateistil beibehalten.
- Keine unnötigen Refactors oder Formatierungswellen.
- Kommentare nur dort, wo der Code sonst schwer nachvollziehbar ist.

## 3. Sicherheit im Dirty Workspace

- Fremde, nicht selbst verursachte Aenderungen nie zuruecksetzen.
- Keine destruktiven Git-Operationen.
- Wenn ausserhalb booking unerwartete Konflikte blockieren, kurz stoppen und Rueckfrage stellen.

## 4. Sprache und Kommunikation

- Antworten in der Sprache des Nutzers.
- Fortschritt kurz und regelmaessig melden.
- Nach Aenderungen klar sagen: was wurde geaendert, wo, und warum.

## 5. Test- und Validierungsstrategie

- Nach relevanten Aenderungen mindestens Syntax/Static-Checks fuer betroffene booking-Dateien ausfuehren.
- Bei Testfehlern zuerst Test-Robustheit pruefen, bevor Produktivcode geaendert wird.
- Nur die wirklich betroffenen Tests/Checks laufen lassen.

## 6. Agentic-First Implementierung

- Orchestrierungslogik im Framework halten.
- Task-Code bewusst datenorientiert und schlank halten.
- Steuerlogik nicht auf fragile Freitext-Heuristik aufbauen, wenn strukturierte Signale verfuegbar sind.

## 7. UI- und Lokalisierungsdisziplin

- Nutzertexte ueber Sprachstrings pflegen, nicht hart codiert.
- Ausgabe- und Fehlersignale semantisch korrekt behandeln.
- UI-Regressionen (falsche Farbe/Status) als funktionale Fehler betrachten.

## 8. Definition von "Done"

Eine Aenderung gilt als abgeschlossen, wenn alle Punkte erfuellt sind:

1. Scope eingehalten (nur booking).
2. Minimaler, nachvollziehbarer Patch.
3. Relevante Validierung erfolgreich.
4. Ergebnis klar dokumentiert (Dateien + Verhalten).

## 9. Do / Don’t

Do:

- Kleine, praezise Patches.
- Reproduzierbare Fehlerbilder mit Logs absichern.
- Konsistenz zwischen Backend-Verhalten und UI-Darstellung pruefen.

Don’t:

- Breite Umbauten ohne klare Notwendigkeit.
- Harte Annahmen ueber Sprache, wenn Kontext vorhanden ist.
- Seiteneffekte ausserhalb des booking-Scopes.
