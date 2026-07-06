# service_provider — Methoden-Doku
**Datei:** `classes/entities/service_provider.php` · **LOC:** 43 · **Subsystem:** S25 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S25_mobile.md)

## Klassenueberblick
`service_provider` ist ein duenner Adapter, der das Callback-Interface `local_entities\local\callback\service_provider` (hier aliasiert als `CallbackService_provider`) implementiert, damit das `local_entities`-Plugin von mod_booking belegte Termine/Datumsbereiche abfragen kann. Die Klasse haelt keinen Zustand und delegiert ihre einzige Methode unveraendert an `booking::return_array_of_entity_dates`. Persistenz: keine (Pass-through). Kollaborateure: `local_entities` (Aufrufer ueber das Callback-Interface), `mod_booking\booking` (eigentliche Implementierung). Anmerkung: Im CLASS_INDEX unter S25 (mobile) gefuehrt; thematisch ist es die local_entities-Integration.

## Methoden

### `public static function return_array_of_entity_dates(array $areas): array` — public static
- **Zweck:** Interface-Erfuellung — liefert die fuer die angefragten Bereiche (`$areas`) von Booking-Optionen belegten Datumsangaben an local_entities zurueck. **Seiteneffekte:** keine eigenen; delegiert vollstaendig an `booking::return_array_of_entity_dates($areas)` (dort liegen die DB-Zugriffe). **Rueckgabe:** Array der Entity-Datumsangaben (Shape von `booking::return_array_of_entity_dates` bestimmt). **Bewertung:** A — minimaler, korrekter Adapter ohne Eigenlogik; die lokale Zwischenvariable `$itemsarray` ist ueberfluessig, aber harmlos.

## Bewertungs-Resümee
Reiner Interface-Adapter (ein Methoden-Pass-through) zur Entkopplung von local_entities und mod_booking. Keine funktionalen oder Performance-Risiken; die gesamte Logik liegt in `booking::return_array_of_entity_dates`. Klassen-Score **A / P3**.
