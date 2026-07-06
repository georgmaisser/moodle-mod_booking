# places — Methoden-Doku
**Datei:** `classes/places.php` · **LOC:** 80 · **Subsystem:** S01 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`places` ist ein reines Werte-DTO fuer den Platz-/Kapazitaetszustand einer Buchungsoption. Es kapselt vier int-Zaehler: `maxanswers` (Gesamtplaetze), `available` (freie Plaetze), `maxoverbooking` (Warteliste-Kapazitaet) und `overbookingavailable` (freie Warteliste-Plaetze). Keine Logik, kein DB-Zugriff — wird von der Availability-/Answers-Schicht befuellt und herumgereicht. Kollaborateure: `booking_answers`/`booking_option` (Erzeuger/Konsumenten).

## Methoden

### `public function __construct($maxanswers, $available, $maxoverbooking, $overbookingavailable)` — public
- **Zweck:** Initialisiert die vier Kapazitaets-Properties direkt aus den Konstruktor-Argumenten. **Seiteneffekte:** keine. **Bewertung:** B — Parameter sind untypisiert (`mixed` laut Docblock), obwohl die Properties als `int` deklariert sind; ein Typehint wuerde fehlerhafte Befuellung frueh fangen.

### Triviale Akzessoren / Properties
Vier oeffentliche `int`-Properties (`maxanswers`, `available`, `maxoverbooking`, `overbookingavailable`, Z.42–63) ohne Getter/Setter — direkter Feldzugriff. Reines Value-Object.

## Bewertungs-Resümee
Minimaler, klarer Daten-Container ohne Verhalten. Einziger Smell: untypisierte Konstruktor-Parameter trotz typisierter Ziel-Properties. Funktional unkritisch. Klassen-Score **A / P3**.
