# userprofilefield — Methoden-Doku
**Datei:** `classes/bo_actions/action_types/userprofilefield.php` · **LOC:** 168 · **Subsystem:** S04 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S04_*.md)

## Klassenueberblick
`userprofilefield` ist ein bo_action-Typ (erweitert `booking_action`), der nach einer Buchung ein benutzerdefiniertes User-Profilfeld manipuliert — Operatoren `set`, `add`, `subtract` und `adddate` (Datums-Spanne fortschreiben). Die Klasse haelt keinen Zustand; sie liefert das Form-Fragment (`add_action_to_mform`) und die Ausfuehrung (`apply_action`). Persistenz erbt sie aus `booking_action::save_action`; das eigentliche Schreiben des Profilfelds laeuft ueber die Core-Profil-API. Kollaborateure: `singleton_service` (User/Option-Settings), Core `profile_load_data`/`profile_save_data`/`profile_get_custom_fields`.

## Methoden

### `public function apply_action(stdClass $actiondata, int $userid = 0)` — public
- **Zweck:** Laedt die Profilfelder des Users und wendet die konfigurierte Operation auf das gewaehlte Custom-Field an. **Seiteneffekte:** `require_once(user/profile/lib.php)`, `profile_load_data($user)` (befuellt `$user->profile[...]` und `$user->profile_field_*`), je nach Operator Mutation von `$user->{$key}`, abschliessend `profile_save_data($user)` → DB-Schreibzugriff am User-Profil. **Rueckgabe:** `int` 0 — erlaubt alle folgenden After-Actions (z.B. Events). **Bewertung:** C — der `adddate`-Zweig ist fragil: im else-Fall werden `$startstring`/`$endstring` per `explode(' - ', ...)` gesetzt, im if-Fall (leeres Feld) aber NICHT; die nachfolgende Zeile `if (!empty($startstring))` liest dann eine nicht initialisierte Variable (PHP-8-Warning, undefiniertes Verhalten je nach Pfad). Zudem mischt der Code zwei Zugriffswege (`$user->profile[...]` lesen, `$user->{$key}` schreiben), was korrekt, aber fehleranfaellig ist. `set`/`add`/`subtract` sind solide.

### `public static function add_action_to_mform(&$mform)` — public static
- **Zweck:** Baut das Aktions-Formular: Namensfeld, Dropdown der Custom-Profilfelder (mit `noselection`-Eintrag 0), Operator-Select (`set`/`add`/`subtract`/`adddate`) und ein Wert-Textfeld. **Seiteneffekte:** `profile_get_custom_fields()`; mutiert `$mform`. **Rueckgabe:** void. **Bewertung:** B — sauber; das Wert-Feld erhaelt keinen `setType` (kein PARAM-Filter → faellt auf PARAM_RAW), was bewusst sein kann (adddate erwartet strtotime-Strings), aber ungetypt bleibt. `$userprofilefieldsarray` ist nur innerhalb des `if (!empty(...))` definiert — bei null Custom-Fields wuerde das Select mit undefinierter Variable gebaut (Edge-Case).

## Bewertungs-Resümee
Nuetzlicher, aber im `adddate`-Pfad unsauberer Aktionstyp: nicht initialisierte `$startstring`-Variable und doppelter Zugriffsweg machen die Datumslogik schwer nachvollziehbar und in Randfaellen warnungs-/fehleranfaellig. `set`/`add`/`subtract` funktionieren zuverlaessig. Klassen-Score **B / P3**.
