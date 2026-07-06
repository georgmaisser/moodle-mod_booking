# department — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/department.php` · **LOC:** 101 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_*.md)

## Klassenueberblick
`department` ist ein schlanker Platzhalter-Handler (`extends placeholder_base`), der das `department`-Feld des Nutzers zurueckgibt. Trotz des Klassennamens (im CLASS_INDEX als "Department-Feld der Option" beschrieben) liefert die Implementierung das Standard-User-Feld `$user->department`, nicht ein Optionsfeld. Persistenz: keine eigene; liest den Nutzer via `singleton_service::get_instance_of_user()`. Request-Memoisierung ueber `placeholders_info::$placeholders`. Kollaborateure: `singleton_service`, `placeholders_info`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert bei vorhandenem `$userid` das `department`-Feld des Nutzers; bei leerem `$userid` eine lokalisierte Fehlermeldung.
- **Seiteneffekte:** `singleton_service::get_instance_of_user($userid)`; `placeholders_info::$placeholders[$cachekey] = $value` (Request-Memo, Key `$classname-$userid`).
- **Rueckgabe:** String (User-Department bzw. Fehlermeldung).
- **Bewertung:** B — korrekt user-scoped gecacht. Kleiner Hinweis: liest `$user->department` direkt; ist das Feld leer, wird ein leerer String gecacht und zurueckgegeben (kein Fallback), was fachlich vermutlich gewollt ist. Name/CLASS_INDEX-Beschreibung suggerieren ein Optionsfeld, geliefert wird das User-Feld (Doku-Diskrepanz, kein Code-Bug).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate fuer den Aufruf.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A.

## Bewertungs-Resümee
Trivialer, korrekter User-Feld-Platzhalter mit sauberer Memoisierung. Einzige Anmerkung ist die Namens-/Beschreibungs-Diskrepanz (User- vs. Options-Department). Klassen-Score **B / P3**.
