# <h1 align="center">NimblePHP - Secure</h1>

Pakiet dostarcza dodatkową warstwę bezpieczeństwa dla aplikacji opartych o **NimblePHP**. Skupia się na dwóch obszarach:

1. **Maskowanie danych w logach** – automatycznie ukrywa wrażliwe informacje (np. hasła, tokeny, klucze API, dane osobowe) w treści logów oraz w tablicach kontekstu.
2. **Sanityzacja danych modeli przed zapytaniem do bazy** – filtruje i normalizuje payload na podstawie rzeczywistych kolumn tabeli MySQL (m.in. usuwa nieznane pola, przycina stringi, rzutuje typy liczbowe, waliduje `enum`, normalizuje `tinyint(1)` do bool/int).

> Moduł działa jako middleware i rejestruje się w kontenerze usług NimblePHP.

## Wymagania
- PHP: `>=8.2`
- `nimblephp/framework`: `>=0.4.1`

## Co jest rejestrowane przez moduł?
Po rejestracji modułu dodawane są usługi:

- `secure.mysql` → `NimblePHP\Secure\Services\MysqlService`
- `secure.array` → `NimblePHP\Secure\Services\ArrayService`

oraz middleware:

- `NimblePHP\Secure\SecureMiddleware` (podpinany do obsługi modeli i logów)

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