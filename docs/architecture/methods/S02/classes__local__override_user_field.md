# override_user_field — Methoden-Doku
**Datei:** `classes/local/override_user_field.php` · **LOC:** 212 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`override_user_field` implementiert den „circumvent condition"-Mechanismus: Ein User kann ein (Custom- oder Standard-) User-Profilfeld temporaer und cmid-scoped auf einen vorgegebenen Wert „mocken", damit Verfuegbarkeitsbedingungen vom Typ `userprofilefield_*` als erfuellt gelten. Der gemockte Wert wird nicht im echten Profil persistiert, sondern als User-Preference im Format `value:::cmid` abgelegt und beim Auslesen gegen die aktuelle cmid validiert. Persistenz: Moodle-User-Preferences (`set_user_preference`/`get_user_preferences`), Konfiguration aus dem Booking-Instanz-JSON (`circumventcond`). Kollaborateure: `$DB` (Spalten-/Feldpruefung), `singleton_service` (Booking-Instanz, Option-Settings), `booking::get_value_of_json_by_key`, `moodle_url`. Die Klasse ist um eine cmid herum aufgebaut (Konstruktor-Parameter).

## Methoden

### `public function __construct(int $cmid)` — public
- **Zweck:** Bindet die Instanz an eine Course-Module-id; alle Folgeoperationen sind cmid-scoped. **Seiteneffekte:** Keine (reine Property-Zuweisung). **Bewertung:** A.

### `public function set_userprefs(string $param, int $userid = 0): bool` — public
- **Zweck:** Zerlegt einen `fieldname_value`-Parameter, prueft ob `fieldname` ein Standard-User-Feld oder ein Custom-Profilfeld ist, und legt bei Treffer die User-Preference `fieldname => "value:::cmid"` ab. **Seiteneffekte:** `$DB->get_columns('user', true)`, `$DB->get_record('user_info_field', ...)`, `set_user_preference(...)`; faellt bei leerem oder formatwidrigem `$param` auf `false` zurueck; default `$userid` = `$USER->id`. **Rueckgabe:** `bool` (true = Preference gesetzt). **Bewertung:** C — die Regex `^([a-zA-Z0-9_]+)_([a-zA-Z0-9_]+)$` ist mit `_` in beiden Gruppen mehrdeutig: die erste Gruppe ist greedy und greift bis zum LETZTEN Unterstrich, d.h. `value` ist immer nur das letzte Segment; Feld- oder Wert-Namen mit Unterstrichen werden falsch zerlegt. Standard- und Custom-Feld nutzen denselben Preference-Schluessel (`$this->key`) ohne Namespacing — bei gleichnamigen Feldern Kollisionsrisiko. Vertrauensgrenze: der gemockte Wert wird ungeprueft uebernommen (Berechtigung erfolgt ueber `password_is_valid`/Aufrufkontext, nicht hier).

### `public function password_is_valid(string $pwd = ''): bool` — public
- **Zweck:** Prueft das uebergebene Passwort gegen `circumventcond.cvpwd` der Booking-Instanz; leeres konfiguriertes Passwort bedeutet „kein Schutz" (immer gueltig), gesetztes Passwort verlangt exakten Match. **Seiteneffekte:** `singleton_service::get_instance_of_booking_by_cmid`, `booking::get_value_of_json_by_key`; setzt `$this->password`. **Rueckgabe:** `bool`; fail-closed, wenn der Key `cvpwd` gar nicht gesetzt ist. **Bewertung:** C — Plaintext-Vergleich (`===`) gegen ein im JSON unverschluesselt gespeichertes Passwort; nicht timing-safe, aber Schutzniveau ist ohnehin „weak by design". Logik korrekt fail-closed bei fehlendem Key.

### `public function get_value_for_user(string $profilefield, int $userid): string` — public
- **Zweck:** Liest die zuvor gesetzte Preference, validiert ueber das `:::cmid`-Suffix, dass der Mock fuer die aktuelle cmid gilt, und liefert sonst Leerstring. **Seiteneffekte:** `get_user_preferences(...)`. **Rueckgabe:** der gemockte Feldwert oder `""`. **Bewertung:** B — saubere cmid-Validierung verhindert Cross-Instance-Leaks des Mocks; `[$fieldvalue, $cmid] = $array;` nimmt nur die ersten zwei Segmente, ein Wert mit `:::` darin wuerde abgeschnitten (konsistent zur Set-Seite, daher unkritisch).

### `public function get_circumvent_link(int $optionid): string` — public
- **Zweck:** Baut den Deep-Link auf `optionview.php`, der die circumvent-Bedingung aktiviert, indem er aus der `availability`-Konfiguration der Option das erste passende `userprofilefield_*`-Kriterium (Operatoren `=`, `~`, `[~]`) extrahiert und als `cvfield=profilefield_value` plus optional `cvpwd` anhaengt. **Seiteneffekte:** `singleton_service::get_instance_of_booking_by_cmid`, `booking::get_value_of_json_by_key`, `singleton_service::get_instance_of_booking_option_settings`, `json_decode`; gibt `""` zurueck, wenn circumvent deaktiviert, keine availability gesetzt oder kein passendes Kriterium gefunden. **Rueckgabe:** absolute URL-String oder `""`. **Bewertung:** D — **das konfigurierte circumvent-Passwort `cvpwd` wird im Klartext als URL-Query-Parameter angehaengt** (Z.205-207) und damit ueber Server-Logs, Referrer-Header und Browser-History leakbar; siehe Findings. Die `foreach`-Schleife bricht beim ersten Treffer ab und ignoriert mehrere/widerspruechliche Kriterien stillschweigend.

### Triviale Properties
Vier Properties (`key`, `value` public; `password`, `cmid` protected, Z.32-42) als Werte-Halter; das Docblock von `$cmid` ist faelschlich von `$password` kopiert („a password if given").

## Bewertungs-Resümee
Kompakte Hilfsklasse fuer einen bewusst „schwachen" Bypass-Mechanismus. Funktional weitgehend korrekt und fail-closed bei fehlender Konfiguration; die cmid-Bindung der Preference ist sauber. Hauptmangel ist das Klartext-`cvpwd` in der erzeugten URL (Informationsleck), sekundaer die mehrdeutige Underscore-Regex in `set_userprefs` und das fehlende Schluessel-Namespacing der Preference. Klassen-Score **C / P2**.
