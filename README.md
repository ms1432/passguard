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
`Random\Randomizer` (domyślnie bezpieczny silnik); można też wstrzyknąć `Mt19937`
z ziarnem, aby uzyskać powtarzalny wynik. Nie użyto `rand`, `mt_rand`
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

## Interfejs użytkownika

Strona `GET /` ([`resources/views/password.blade.php`](resources/views/password.blade.php))
zawiera dwie sekcje — „Sprawdź hasło" oraz „Wygeneruj hasło" — pole wyniku (siła, wynik
`X/5`, lista niespełnionych reguł) oraz pole błędu walidacji. Etykiety są po polsku, a styl
i logika (`fetch`) są wbudowane w widok. Kluczowe elementy mają stałe `id`:
`password-input`, `toggle-visibility`, `check-button`, `length-input`, `generate-button`,
`result-strength`, `result-score`, `failed-rules`, `error-box`.

## Technologie użyte w projekcie

- **PHP 8.3+** (uruchamiane na 8.5)
- **Laravel 13** (framework; routing, kontener DI, walidacja Form Requests)
- **Blade** z wbudowanym, czystym **vanilla JavaScript** (`fetch`) i **CSS** (bez frameworka JS/CSS, bez build-stepu)
- **Composer** — menedżer zależności
- **`Random\Randomizer`** (bezpieczny silnik, wstrzykiwany) do generowania haseł
- Konfiguracja **bez bazy danych**: sesja i cache na plikach, kolejka `sync`
