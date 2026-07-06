# col_responsiblecontacts — Methoden-Doku
**Datei:** `classes/output/col_responsiblecontacts.php` · **LOC:** 76 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_responsiblecontacts` ist ein schlankes Renderable/Templatable-DTO fuer die Spalte „verantwortliche Kontakte" einer Buchungsoption. Es loest die in `booking_option_settings->responsiblecontact` gespeicherten User-IDs zu Anzeigeobjekten (Name + Profil-URL) auf. Keine Persistenz; Kollaborateur: `singleton_service::get_instance_of_user` (gecachter User-Lookup), `moodle_url`.

## Methoden

### `public function __construct(int $optionid, booking_option_settings $settings)` — public
- **Zweck:** Iteriert ueber `$settings->responsiblecontact` und baut fuer jeden aufloesbaren User ein `stdClass` mit `name` (`"firstname lastname"`) und `url` (Link auf `/user/profile.php?id=...`) in `$this->contacts`.
- **Seiteneffekte:** Pro Kontakt ein `singleton_service::get_instance_of_user((int)$contact)` (gecacht); nicht aufloesbare User werden uebersprungen.
- **Bewertung:** A — kompakt und defensiv (Cast auf int, Existenz-Guard). `$optionid` wird nicht genutzt (reiner Signatur-Parameter). Pro Zeile ein User-Lookup je Kontakt, aber durch den Singleton-Cache unkritisch.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt `['contacts' => $this->contacts]` fuer das Mustache-Template zurueck.
- **Seiteneffekte:** keine.
- **Rueckgabe:** Array mit der Kontaktliste.
- **Bewertung:** A — trivialer Passthrough.

## Bewertungs-Resümee
Minimales, sauberes DTO ohne funktionale Auffaelligkeiten. Einziger kosmetischer Punkt: der ungenutzte `$optionid`-Parameter. Klassen-Score **A / P3**.
