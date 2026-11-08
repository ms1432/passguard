# PassGuard — sprawdzanie siły i generowanie haseł

## Nazwa kursu

Testowanie i Jakość Oprogramowania II

## Autor

Michał Stręk

## Temat projektu

**Password Strength Checker & Generator** — aplikacja webowa do oceny siły hasła
oraz generowania bezpiecznych haseł.

## Opis projektu

Aplikacja pozwala użytkownikowi:

- wpisać hasło i otrzymać jego **siłę** (`słabe` / `średnie` / `silne`), **wynik punktowy 0–5**
  oraz **listę niespełnionych reguł** w postaci komunikatów po polsku,
- **wygenerować hasło** o wybranej długości (8–64, domyślnie 16); wygenerowane hasło
  jest automatycznie wstawiane do pola i od razu sprawdzane.

Reguły oceny hasła zrealizowano wzorcem **Strategia** — każda reguła to osobna klasa
implementująca wspólny interfejs [`PasswordRule`](app/Password/Rules/PasswordRule.php),
dzięki czemu dodanie nowej reguły nie wymaga zmian w klasie oceniającej (OCP/DIP).

### Reguły

| Reguła | `name()` | Spełniona gdy | Komunikat (PL) |
|---|---|---|---|
| [`MinLengthRule`](app/Password/Rules/MinLengthRule.php) | `min_length` | `mb_strlen` ≥ 8 | Hasło musi mieć co najmniej 8 znaków |
| [`ContainsDigitRule`](app/Password/Rules/ContainsDigitRule.php) | `digit` | pasuje `/\p{Nd}/u` | Hasło musi zawierać cyfrę |
| [`ContainsLowercaseRule`](app/Password/Rules/ContainsLowercaseRule.php) | `lowercase` | pasuje `/\p{Ll}/u` | Hasło musi zawierać małą literę |
| [`ContainsUppercaseRule`](app/Password/Rules/ContainsUppercaseRule.php) | `uppercase` | pasuje `/\p{Lu}/u` | Hasło musi zawierać wielką literę |
| [`ContainsSpecialCharRule`](app/Password/Rules/ContainsSpecialCharRule.php) | `special_char` | pasuje `/[^\p{L}\p{N}\s]/u` | Hasło musi zawierać znak specjalny |

Sprawdzanie jest **świadome Unicode** (`mb_strlen` oraz wyrażenia regularne z flagą `u`).
Spacja **nie** jest traktowana jako znak specjalny.

### Punktacja i siła

- `score` = liczba spełnionych reguł (0–5),
- `0–2` → **słabe**, `3–4` → **średnie**, `5` → **silne**
  (zob. [`PasswordStrength::fromScore()`](app/Password/PasswordStrength.php)).

### Generator

[`PasswordGenerator`](app/Password/PasswordGenerator.php) gwarantuje, że wynik zawiera co
najmniej jedną małą literę, jedną wielką literę, jedną cyfrę oraz jeden znak specjalny ze
zbioru `!@#$%^&*()-_=+[]{};:,.?`. Losowość pochodzi z wstrzykiwanego
`Random\Randomizer` (domyślnie bezpieczny silnik), dzięki czemu testy mogą podać
`Mt19937` z ziarnem i uzyskać **powtarzalny** wynik. Nie użyto `rand`, `mt_rand`
ani `str_shuffle`. Długość spoza zakresu 8–64 powoduje `InvalidArgumentException`.

## Uruchomienie projektu

Wymagania: **PHP 8.3+** (z rozszerzeniem `mbstring`) oraz **Composer**.
Aplikacja **nie wymaga bazy danych** — sesja i cache działają na plikach, kolejka w trybie `sync`.

```bash
# 1. Zależności
composer install

# 2. Konfiguracja środowiska
cp .env.example .env
php artisan key:generate

# 3. Start serwera deweloperskiego
php artisan serve
```

Aplikacja działa pod adresem **http://127.0.0.1:8000** (strona `GET /`).
Nie jest potrzebny build npm/Vite — CSS i JavaScript są wbudowane w widok Blade.

## Testy

> **Status:** testy są dodawane w kolejnej sesji projektu. Obecnie katalog
> [`tests/`](tests) zawiera jedynie bazową klasę [`TestCase.php`](tests/TestCase.php)
> (domyślne testy `ExampleTest` zostały usunięte). Poniżej opisano zaplanowany zakres
> testów oraz ich docelowe lokalizacje — kod aplikacji został napisany tak, by był łatwy
> do przetestowania (czyste klasy, wstrzykiwane zależności, wstrzykiwany `Randomizer`).

Uruchomienie testów (po ich dodaniu):

```bash
php artisan test
# lub
./vendor/bin/phpunit
```

### Testy jednostkowe (planowane) — `tests/Unit/`

- **Reguły** (`tests/Unit/Password/Rules/`) — dla każdej z pięciu reguł: hasło spełniające
  i niespełniające regułę, przypadki Unicode (np. cyfra arabsko-indyjska `١`), potwierdzenie,
  że spacja **nie** jest znakiem specjalnym. Kod reguł: [`app/Password/Rules/`](app/Password/Rules).
- **Enum siły** (`tests/Unit/Password/PasswordStrengthTest.php`) — mapowanie `score` → siła
  dla granic 0, 2, 3, 4, 5. Kod: [`PasswordStrength`](app/Password/PasswordStrength.php).
- **Checker** (`tests/Unit/Password/PasswordStrengthCheckerTest.php`) — poprawny `score`,
  zawartość `failedRules` i kształt `toArray()`. Kod:
  [`PasswordStrengthChecker`](app/Password/PasswordStrengthChecker.php),
  [`StrengthResult`](app/Password/StrengthResult.php).
- **Generator** (`tests/Unit/Password/PasswordGeneratorTest.php`) — poprawna długość,
  obecność wszystkich wymaganych klas znaków, **determinizm** przy `Randomizer(new Mt19937(seed))`
  oraz `InvalidArgumentException` dla długości 7 i 65. Kod:
  [`PasswordGenerator`](app/Password/PasswordGenerator.php).

### Testy integracyjne / funkcjonalne (planowane) — `tests/Feature/`

- **`tests/Feature/Api/CheckPasswordTest.php`** — `POST /api/password/check`: hasło silne
  (200, `score=5`), słabe (200, odpowiednie `failed_rules`), brak hasła (**422**).
- **`tests/Feature/Api/GeneratePasswordTest.php`** — `GET /api/password/generate`: długość
  domyślna (200, `length=16`), `length=7` (**422**), poprawny kształt odpowiedzi.
- **`tests/Feature/HomePageTest.php`** — `GET /` zwraca stronę zawierającą pola o stałych
  `id` (m.in. `password-input`).

Kod objęty testami integracyjnymi:
[`Api/PasswordController`](app/Http/Controllers/Api/PasswordController.php),
[`CheckPasswordRequest`](app/Http/Requests/CheckPasswordRequest.php),
[`GeneratePasswordRequest`](app/Http/Requests/GeneratePasswordRequest.php),
[`routes/api.php`](routes/api.php).

## Dokumentacja API

Bazowy adres: `http://127.0.0.1:8000`. Wszystkie odpowiedzi są w formacie JSON.
Nagłówek `Accept: application/json` zapewnia zwracanie błędów walidacji jako JSON (status **422**).

### `POST /api/password/check`

Sprawdza siłę podanego hasła.

**Żądanie (body JSON):**

```json
{ "password": "Abcdef1!" }
```

| Pole | Walidacja |
|---|---|
| `password` | `required\|string\|max:256` |

**Odpowiedź 200:**

```json
{
  "strength": "strong",
  "score": 5,
  "failed_rules": []
}
```

Przykład dla hasła słabego (`"abc"`):

```json
{
  "strength": "weak",
  "score": 1,
  "failed_rules": [
    { "name": "min_length",   "message": "Hasło musi mieć co najmniej 8 znaków" },
    { "name": "digit",        "message": "Hasło musi zawierać cyfrę" },
    { "name": "uppercase",    "message": "Hasło musi zawierać wielką literę" },
    { "name": "special_char", "message": "Hasło musi zawierać znak specjalny" }
  ]
}
```

**Odpowiedź 422** (np. brak pola `password`):

```json
{
  "message": "The password field is required.",
  "errors": { "password": ["The password field is required."] }
}
```

Przykład:

```bash
curl -X POST http://127.0.0.1:8000/api/password/check \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"password":"Abcdef1!"}'
```

### `GET /api/password/generate`

Generuje bezpieczne hasło.

| Parametr (query) | Walidacja | Domyślnie |
|---|---|---|
| `length` | `nullable\|integer\|between:8,64` | 16 |

**Odpowiedź 200:**

```json
{ "password": "qI]rcX5A2fLG]h2c", "length": 16 }
```

**Odpowiedź 422** (np. `length=7`):

```json
{
  "message": "The length field must be between 8 and 64.",
  "errors": { "length": ["The length field must be between 8 and 64."] }
}
```

Przykład:

```bash
curl 'http://127.0.0.1:8000/api/password/generate?length=16' -H 'Accept: application/json'
```

## Przypadki testowe dla testera manualnego (TestCase)

Testy manualne wykonujemy na stronie `GET /`. Elementy interfejsu mają stałe `id`:
`password-input`, `toggle-visibility`, `check-button`, `length-input`, `generate-button`,
`result-strength`, `result-score`, `failed-rules`, `error-box`.

| ID | Tytuł | Warunki wstępne | Kroki | Oczekiwany rezultat |
|---|---|---|---|---|
| TC-01 | Sprawdzenie silnego hasła | Strona otwarta | 1. W `password-input` wpisz `Abcdef1!` 2. Kliknij `check-button` | `result-strength` = **Silne** (zielony), `result-score` = „Wynik: 5/5”, lista `failed-rules` pusta |
| TC-02 | Sprawdzenie słabego hasła | Strona otwarta | 1. W `password-input` wpisz `abc` 2. Kliknij `check-button` | `result-strength` = **Słabe** (czerwony), `result-score` = „Wynik: 1/5”, `failed-rules` zawiera 4 komunikaty PL |
| TC-03 | Sprawdzenie hasła średniego | Strona otwarta | 1. W `password-input` wpisz `abcdefgh1` 2. Kliknij `check-button` | `result-strength` = **Średnie** (pomarańczowy), `result-score` = „Wynik: 3/5”, `failed-rules` zawiera brakujące reguły (wielka litera, znak specjalny) |
| TC-04 | Pokaż / ukryj hasło | W `password-input` wpisane hasło | 1. Kliknij `toggle-visibility` 2. Kliknij ponownie | Po 1. hasło widoczne (typ `text`), przycisk „ukryj”; po 2. hasło zamaskowane (typ `password`), przycisk „pokaż” |
| TC-05 | Generowanie hasła (domyślna długość) | `length-input` = 16 | 1. Kliknij `generate-button` | `password-input` wypełnione hasłem o długości 16, wynik pojawia się automatycznie: `result-strength` = **Silne**, „Wynik: 5/5” |
| TC-06 | Generowanie hasła o wybranej długości | Strona otwarta | 1. Ustaw `length-input` = 24 2. Kliknij `generate-button` | Wygenerowane hasło ma 24 znaki i jest automatycznie oceniane jako **Silne** |
| TC-07 | Walidacja długości (poza zakresem) | Strona otwarta | 1. Ustaw `length-input` = 7 2. Kliknij `generate-button` | `error-box` widoczny z komunikatem „The length field must be between 8 and 64.”; brak nowego hasła |
| TC-08 | Puste hasło | `password-input` puste | 1. Kliknij `check-button` | `error-box` widoczny z komunikatem „The password field is required.” |

## Technologie użyte w projekcie

- **PHP 8.3+** (testowane na 8.5)
- **Laravel 13** (framework; routing, kontener DI, walidacja Form Requests)
- **Blade** z wbudowanym, czystym **vanilla JavaScript** (`fetch`) i **CSS** (bez frameworka JS/CSS, bez build-stepu)
- **Composer** — menedżer zależności
- **`Random\Randomizer`** (bezpieczny silnik, wstrzykiwany) do generowania haseł
- **PHPUnit** — framework testów jednostkowych/funkcjonalnych (testy w kolejnej sesji)
- **Laravel Pint** — formatowanie kodu (PSR-12)
- Konfiguracja **bez bazy danych**: sesja i cache na plikach, kolejka `sync`
