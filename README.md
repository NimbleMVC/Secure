# <h1 align="center">NimblePHP - Secure</h1>

Pakiet dostarcza dodatkową warstwę bezpieczeństwa dla aplikacji opartych o **NimblePHP**. Skupia się na dwóch obszarach:

1. **Maskowanie danych w logach** – automatycznie ukrywa wrażliwe informacje (np. hasła, tokeny, klucze API, dane osobowe) w treści logów oraz w tablicach kontekstu.
2. **Sanityzacja danych modeli przed zapytaniem do bazy** – filtruje i normalizuje payload na podstawie rzeczywistych kolumn tabeli MySQL (m.in. usuwa nieznane pola, przycina stringi, rzutuje typy liczbowe, waliduje `enum`, normalizuje `tinyint(1)` do bool/int).
3. **Rate limiting dla kontrolerów** – opcjonalne ograniczanie liczby żądań per IP/route w określonym oknie czasu.

> Moduł działa jako middleware i rejestruje się w kontenerze usług NimblePHP.

## Wymagania
- PHP: `>=8.2`
- `nimblephp/framework`: `>=0.4.1`

## Co jest rejestrowane przez moduł?
Po rejestracji modułu dodawane są usługi:

- `secure.mysql` → `NimblePHP\Secure\Services\MysqlService`
- `secure.array` → `NimblePHP\Secure\Services\ArrayService`
- `secure.rateLimiter` → `NimblePHP\Secure\Services\RateLimiterService`

oraz middleware:

- `NimblePHP\Secure\SecureMiddleware` (podpinany do obsługi modeli i logów)

## Konfiguracja rate limit (.env)

```env
SECURE_RATE_LIMIT_ENABLED=false
SECURE_RATE_LIMIT_STORAGE=cache
SECURE_RATE_LIMIT_MAX_ATTEMPTS=120
SECURE_RATE_LIMIT_WINDOW=60
SECURE_RATE_LIMIT_KEY_MODE=ip
SECURE_RATE_LIMIT_TABLE=module_secure_rate_limit
```

Parametry:
- `SECURE_RATE_LIMIT_ENABLED` – włącza/wyłącza limiter.
- `SECURE_RATE_LIMIT_STORAGE`:
	- `cache` (domyślnie): zapis liczników w plikach cache,
	- `database`: zapis liczników w bazie danych (tabela rate limit).
- `SECURE_RATE_LIMIT_MAX_ATTEMPTS` – maksymalna liczba żądań w oknie.
- `SECURE_RATE_LIMIT_WINDOW` – długość okna w sekundach.
- `SECURE_RATE_LIMIT_KEY_MODE`:
	- `ip` (domyślnie): limit globalny per IP (na wszystkich URL),
	- `route`: limit per IP + metoda + URI.
- `SECURE_RATE_LIMIT_TABLE` – nazwa tabeli dla trybu `database`.

Przy `SECURE_RATE_LIMIT_STORAGE=database` uruchom aktualizację migracji modułów:

```bash
php vendor/bin/nimble project:update
```

To utworzy tabelę `module_secure_rate_limit` (lub użyj własnej migracji, jeśli zmieniasz nazwę tabeli).

## Użycie

### 1) Maskowanie danych w logach (automatyczne)
Middleware przechwytuje logowanie i maskuje wrażliwe dane w:
- treści wiadomości (`message`)
- kontekście (`content`)
- tablicy parametrów GET (`get`)

Maskowanie obejmuje m.in. pola typu `password`, `token`, `api_key`, `authorization`, dane kart, email, telefon itp. (lista jest wstępnie zdefiniowana i rozszerzalna).

### 2) Sanityzacja danych modeli (automatyczna)
Podczas przetwarzania danych modelu, pakiet:
- pobiera listę kolumn tabeli i **usuwa pola, których nie ma w tabeli**
- dla stringów (`varchar`, `text`, `longtext`) wykonuje `trim()` i przycina do maksymalnej długości (dla `varchar(n)`)
- dla typów liczbowych rzutuje wartości na `int/float` lub ustawia `null`, jeśli nie są liczbami
- dla `enum` ustawia `null`, jeśli wartość jest spoza dozwolonego zestawu
- dla `tinyint(1)` normalizuje do `0/1`

## Współtworzenie
Zachęcamy do współtworzenia! Masz sugestie, znalazłeś błąd albo chcesz dorzucić usprawnienia? Otwórz issue lub prześlij pull request.

## Pomoc
Pytania i problemy zgłaszaj przez zakładkę **Discussions** w repozytorium GitHub tego modułu.