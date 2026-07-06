# permissions — Methoden-Doku
**Datei:** `classes/permissions.php` · **LOC:** 53 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
Statische Helferklasse fuer Berechtigungspruefungen im Plugin. Enthaelt aktuell genau eine Methode, die eine Capability ueber alle Kontexte einer Kontextebene prueft. Kollaborateure: Moodle-Core (`context`, `has_capability`, `$DB`).

## Methoden

### `has_capability_anywhere(string $capability = 'moodle/course:manageactivities', int $contextlevel = CONTEXT_MODULE): bool` — public static
- **Zweck:** Prueft, ob der aktuelle User die angegebene Capability in IRGENDEINEM Kontext der gegebenen Kontextebene besitzt. Per DocBlock selbst als "expensive" markiert.
- **Parameter:** `$capability` Capability-String; `$contextlevel` Moodle-Kontextlevel-Konstante (Default CONTEXT_MODULE).
- **Rueckgabe:** `bool` — true, sobald ein Kontext mit der Capability gefunden wird, sonst false.
- **Seiteneffekte:** DB-Read auf `{context}` (alle IDs der Kontextebene); pro Kontext `context::instance_by_id()` (Core-Cache) + `has_capability()`. Keine Writes/Events.
- **Aufrufkette:** Statisch von beliebigen Stellen rufbar (Sichtbarkeitschecks/Menue-Gates). Ruft Core-`has_capability` in Schleife.
- **Bewertung:** C. Potenzielles Performance-/Skalierungsproblem: laedt ALLE Kontexte einer Ebene und iteriert sequentiell mit `has_capability` (N+1-artig, kein Early-Limit, keine Caching-Strategie). Auf grossen Instanzen teuer (DocBlock raeumt das selbst ein). Smell: skalierender Schleifen-Capability-Check `permissions.php:45-50`.
