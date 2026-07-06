# button_notifyme — Methoden-Doku
**Datei:** `classes/output/button_notifyme.php` · **LOC:** 117 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`button_notifyme` ist ein renderable/templatable-DTO fuer den „Benachrichtige mich"-Button (Wartelisten-Notify). Der Konstruktor speichert `userid`/`itemid`/`onlist` und laedt den fuer den User geltenden Preis. Properties (alle private): `$userid`, `$itemid`, `$onlist`, `$price`. Kollaborateure: `singleton_service::get_instance_of_user()`, `price::get_price()`.

## Methoden

### `public function __construct(int $userid, int $itemid, bool $onlist = false)` — public
- **Zweck:** Initialisiert die Instanz und laedt `$this->price` als den fuer den User geltenden Optionspreis. **Seiteneffekte:** `singleton_service::get_instance_of_user($userid)`, `price::get_price('option', $itemid, $user)`. **Bewertung:** A — kompakt, klare Verantwortung.

### `public function return_as_array()` — public
- **Zweck:** Baut das Template-Array `userid/itemid/price/area`, plus `onlist => true` nur wenn der User bereits auf der Notify-Liste steht. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Delegiert an `return_as_array()`. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — reiner Delegations-Wrapper.

### Triviale Properties
`$userid`, `$itemid`, `$onlist`, `$price` (Z.43–69), reine Werte-Halter mit Default-Initialisierung.

## Bewertungs-Resümee
Minimales, fehlerfreies DTO; die Aufteilung in `return_as_array()` + `export_for_template()` ist leicht redundant, aber harmlos (erlaubt direkte Array-Nutzung ohne Renderer). Keine funktionalen Schwaechen. Klassen-Score **B / P3**.
