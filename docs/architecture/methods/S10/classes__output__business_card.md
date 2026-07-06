# business_card — Methoden-Doku
**Datei:** `classes/output/business_card.php` · **LOC:** 141 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`business_card` ist ein renderable/templatable-DTO fuer die „Visitenkarte" eines Lehrenden (Name, Profilbild, Profil-/Nachrichten-Link, Beschreibung, Dauer, Punkte). Der Konstruktor laedt den User, baut die URLs/Felder und behandelt den Fall eines nicht gefundenen Users mit einer kapazitaetsgesteuerten Fehlermeldung. Properties (alle public, nullable): `username`, `sendmessageurl`, `description`, `userdescription`, `userprofileurl`, `userpictureurl`, `duration`, `points`, `errormessage`. Kollaborateure: `user_get_users_by_id()`, `singleton_service::get_instance_of_user()`, `user_picture`, `context_system`, `$PAGE`, `format_text`.

## Methoden

### `public function __construct($bookingsettings, $userid)` — public
- **Zweck:** Laedt den User (mit Singleton-Fallback) und befuellt Bild-/Profil-/Nachrichten-URLs, formatierte Beschreibung, Name, Dauer und (nur wenn > 0) Punkte. Bei unauffindbarem User setzt es `errormessage` nur fuer Nutzer mit `mod/booking:updatebooking`. **Seiteneffekte:** `context_system::instance()`, `user_get_users_by_id()`, `singleton_service::get_instance_of_user()`, `has_capability()`, `new user_picture(...)->get_url($PAGE)`, `format_text()`, `new moodle_url(...)`. **Bewertung:** C — die Profil- und Nachrichten-Links nutzen relative Pfade (`'../../user/profile.php'`, `'../../message/index.php'`, Z.104–105); relative `moodle_url`-Basen sind kontextabhaengig von der aktuellen Seite und brechen, wenn die Karte aus einer anders verschachtelten URL gerendert wird — robuster waere ein absoluter `/user/profile.php`-Pfad. Ansonsten gute Defensive (User-Not-Found-Pfad mit Capability-Gate).

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt alle Properties als flaches Template-Array zurueck (inkl. `errormessage`). **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — reiner Property-Dump.

## Bewertungs-Resümee
Sauberes DTO mit ordentlicher User-Not-Found-Behandlung; einziger nennenswerter Kritikpunkt sind die relativen URL-Basen, die bei abweichendem Render-Kontext fehlleiten koennen. Funktional in den ueblichen Pfaden korrekt. Klassen-Score **B / P3**.
