# Code Review & Analýza řešení

> Objektivní hodnocení kódu v `nette/app/` vůči zadání v `task.md` a obecným best practices.

---

## 0. Opravy v `solution.md` (nepřesnosti zanesené uživatelem)

### ❌ PHP 8.5
`composer.json` specifikuje `"php": ">= 8.2"`. PHP 8.5 neexistuje (aktuální stable je 8.3/8.4). **Správně: PHP 8.2+.**

### ❌ DDD (Domain-Driven Design)
Architektura je inspirována DDD jmenným prostorem (Domain / Infrastructure / Presentation), ale **neplní základní DDD kontrakty**:

| DDD koncept | Přítomný? | Poznámka |
|---|---|---|
| Aggregate Root | ❌ | Produkt je prostý `array`, ne entita |
| Value Objects | ❌ | Složka existuje, ale je prázdná |
| Domain Events | ❌ | Žádné |
| Application Services / Use Cases | ❌ | Složka existuje, ale je prázdná |
| Bounded Contexts | ❌ | Jen namespace dělení |
| Entity s identitou | ❌ | Jen `array` s `id` klíčem |

Správné pojmenování: **Layered Architecture** s DDD-inspirovaným namespace dělením.

---

## 1. Závažné problémy (🔴 Critical / High)

### 🔴 C1 — Narušení vrstvové závislosti (Dependency Rule)

**Soubor:** `app/Domain/Product/ProductRepository.php`

```php
use App\Infrastructure\Cache\ICacheAdapter;    // ❌ Domain závisí na Infrastructure
use App\Infrastructure\Cache\ICacheRepository; // ❌ Domain závisí na Infrastructure
use App\Infrastructure\DB\DBRepository;        // ❌ Domain závisí na Infrastructure
use App\Infrastructure\DB\IDBAdapter;          // ❌ Domain závisí na Infrastructure
```

V Layered Architecture (a zejména v DDD / Clean Architecture) platí pravidlo:
> **Domain vrstva nesmí záviset na Infrastructure vrstvě.**

`ProductRepository` i `TrackingRepository` jsou v namespace `App\Domain`, ale přímo importují rozhraní z `App\Infrastructure`. To je zásadní porušení separace vrstev.

**Správné řešení:** Rozhraní `ICacheAdapter` a `IDBAdapter` (nebo jejich doménové ekvivalenty) patří do **Domain** vrstvy; implementace do Infrastructure. Konkrétní repozitáře (`ProductRepository`) by měly být v Infrastructure, nebo interface v Domain a implementace v Infrastructure.

---

### 🔴 C2 — `RedisAdapter` závisí natvrdo na Mock třídě

**Soubor:** `app/Infrastructure/Redis/RedisAdapter.php`

```php
use App\Infrastructure\Redis\Mock\RedisDriver; // ❌ hardcoded na Mock!

class RedisAdapter implements ICacheAdapter
{
    public function __construct(
        private readonly RedisDriver $redisDriver  // konkrétní Mock třída, ne interface
    ) {}
}
```

`RedisAdapter` je navržen jako produkční adapter (implementuje `ICacheAdapter`), ale jeho závislost je **napevno svázána** s `Mock\RedisDriver`. Není definováno žádné `IRedisDriver` rozhraní. Při použití reálného Redis klienta je nutno **modifikovat třídu adapteru** – to porušuje Open/Closed Principle.

**Správné řešení:**
```php
// Definovat interface:
interface IRedisDriver {
    public function get(string $key): array;
    public function set(string $key, array $data): void;
}

// RedisAdapter závislý na interface, ne na Mock:
class RedisAdapter implements ICacheAdapter {
    public function __construct(
        private readonly IRedisDriver $redisDriver  // ✅
    ) {}
}
```

Srovnej: `ElasticSearchAdapter` a `MySQLAdapter` jsou správně navrženy – závisejí na svém interface (`IElasticSearchDriver`, `IMySQLDriver`), ne na Mock implementaci.

---

### 🔴 C3 — Nesprávná detekce cache miss

**Soubor:** `app/Domain/Product/ProductRepository.php`

```php
$data = $this->cacheAdapter->get($cacheKey);
if ($data === []) {   // ❌ fragile podmínka
    $data = $this->storageAdapter->getByID($id);
    ...
}
```

**Problémy:**
1. Pokud produkt v databázi legitimně vrátí prázdné pole `[]`, bude považován za cache miss a DB bude dotázána při každém požadavku – **nekonečná smyčka dotazů na DB**.
2. Rozhraní `ICacheAdapter::get()` vrací `array`, nikoli `?array`. Správná signalizace "nenalezeno" by měla být `null` (viz bod C4).

---

### 🔴 C4 — Nekonzistentní return typ `ICacheAdapter::get()`

**Soubor:** `app/Infrastructure/Cache/ICacheAdapter.php`

```php
interface ICacheAdapter
{
    public function get(string $key): array;  // ❌ nikdy nemůže vrátit null
}
```

- `FileCacheAdapter::get()` vrací `[]` na miss.
- `TrackingRepository::getById()` používá `?? [$id => 0]` — ale `array` je nikdy `null`, takže **null-coalescing nikdy nevykoná výchozí hodnotu**. Toto je mrtvý kód / logická chyba.

```php
// TrackingRepository.php
return $this->cacheAdapter->get($this->getCacheKey($id)) ?? [$id => 0];
//                                                          ^^^^^^^^^^^
//                          toto NIKDY nenastane, get() vrací array (i prázdný)
```

**Správné řešení:** Změnit return type na `?array` a vrátit `null` při cache miss:
```php
public function get(string $key): ?array;
```

---

## 2. Střední problémy (🟡 Medium)

### 🟡 M1 — `ProductRepository` v Domain vrstvě místo Infrastructure

Jak popsáno v C1, `ProductRepository` patří svou implementací (závislost na cache a DB) do Infrastructure. V Domain vrstvě by mělo zůstat jen rozhraní `IProductRepository`.

Doporučená struktura:
```
Domain/Product/
  IProductRepository.php      ← zůstane v Domain (interface)

Infrastructure/Product/
  ProductRepository.php       ← přesunout sem (implementace)
```

---

### 🟡 M2 — Sdílená instance `ICacheAdapter` pro produkty i tracking

Oba `ProductRepository` i `TrackingRepository` dostávají **stejnou instanci** `ICacheAdapter` z DI. To means:
- Přepnutí z file cache na Redis cache ovlivní **obě** funkce najednou.
- Nelze nezávisle nastavit jiný backend pro tracking (plain text, jak naznačuje zadání) a jiný pro produkty.

**Doporučení:** Pojmenovat služby v DI separátně a injektovat explicitně různé instance, nebo použít dekorátory/factory.

---

### 🟡 M3 — `#[Inject]` na public property v Presenteru

**Soubor:** `app/Presentation/Product/ProductPresenter.php`

```php
#[Inject]
public ProductService $productService;  // public property
```

Atribut `#[Inject]` funguje, ale vyžaduje `public` viditelnost property — ta je pak přístupná zvenku. Moderní Nette (3.2+) plně podporuje constructor injection i v Presenterech:

```php
public function __construct(
    private readonly ProductService $productService
) {
    parent::__construct();
}
```

Constructor injection je explicitnější, testovatelný a nevyžaduje public property.

---

### 🟡 M4 — Mock drivery registrovány v produkční konfiguraci

**Soubor:** `config/services.neon`

```neon
- App\Infrastructure\ElasticSearch\Mock\ElasticSearchDriver  # mock v produkci
- App\Infrastructure\MySQL\Mock\MySQLDriver                  # mock v produkci
- App\Infrastructure\Redis\Mock\RedisDriver                  # mock v produkci
```

Mock implementace jsou registrovány v hlavním `services.neon` (ne v test configu). V produkci by tyto třídy měly být registrovány jen v testovacím/dev prostředí (`services.local.neon` nebo `services.test.neon`).

---

### 🟡 M5 — Test pro `TrackingRepository` chybí

**Soubor:** `tests/ProductRepositoryTest.php`

Test suite pokrývá:
- ✅ `ProductService::getProductDetail()`
- ✅ `ProductRepository` – cache hit
- ✅ `ProductRepository` – cache miss

Chybí testy pro:
- ❌ `TrackingRepository::increment()` – klíčová byznys logika
- ❌ `TrackingRepository::getById()` – zejména chování při prvním volání (bug z C4)
- ❌ `ProductService::getProductTrackingData()`

---

## 3. Menší problémy / doporučení (🟢 Low / Suggestions)

### 🟢 L1 — `TrackingRepository` neposkytuje interface

`TrackingRepository` nemá vlastní interface (`ITrackingRepository`). `ProductService` závisí na konkrétní třídě:

```php
class ProductService
{
    public function __construct(
        private readonly IProductRepository $productRepository,  // ✅ interface
        private readonly TrackingRepository $trackingRepository  // ❌ konkrétní třída
    ) {}
}
```

Pro konzistenci a testovatelnost by mělo existovat `ITrackingRepository`.

---

### 🟢 L2 — Cache klíče nejsou testovány na kolize

Cache prefix `product_` a `tracking_` jsou hardcoded konstanty. Pokud ID produktu obsahuje speciální znaky nebo je velmi dlouhé, mohlo by dojít ke kolizi nebo problémům s file cache. Doporučení: použít `md5()` nebo strukturovaný klíč.

---

### 🟢 L3 — `ProductService` vrací `array` místo DTO/Value Object

```php
public function getProductDetail(string $id): array
```

Produkt je v celém systému reprezentován jako `array`. To znamená:
- Žádná typová bezpečnost (IDE neví, jaká pole existují).
- Přidání `tracking_count` do pole produktu mísí doménová data s infrastrukturními.
- Složky `UseCase/` a `ValueObject/` jsou prázdné, ačkoli byly připraveny právě pro toto.

---

### 🟢 L4 — Komentář `@property` v `ProductService` je nesprávný

```php
/**
 * @property ProductRepository $productRepository   ❌ (concrete type, ne interface)
 * @property TrackingRepository $trackingRepository
 */
class ProductService
{
    public function __construct(
        private readonly IProductRepository $productRepository,  // IProductRepository
```

PHPDoc `@property` neodpovídá skutečnému typu (`IProductRepository` vs. `ProductRepository`).

---

### 🟢 L5 — Absence `declare(strict_types=1)` v některých souborech

`ProductService.php` a `ProductRepository.php` nezačínají `declare(strict_types=1)`, ostatní soubory ano. Nekonzistence.

---

## 4. Pozitiva 👍

| Co funguje dobře | |
|---|---|
| `IDBAdapter` + adaptery | Správný Adapter Pattern pro DB, ElasticSearch a MySQL mají vlastní interface |
| `ICacheAdapter` + FileCacheAdapter | Dobrá abstrakce pro cache (až na bug null/empty) |
| `services.neon` switchování | Přepnutí technologie komentářem je elegantní řešení pro demo účely |
| Testovatelnost přes interface | `IProductRepository`, `ICacheAdapter`, `IDBAdapter` umožňují čisté unit testy |
| Existující testy | 3 unit testy s použitím anonymous class mocků – dobrý základ |
| `readonly` properties | Správné použití `readonly` pro dependency injection |
| Nette DI | Správné využití DI kontejneru |
| `CACHE_PREFIX` konstanty | Pojmenované konstanty pro cache klíče |

---

## 5. Souhrn priorit oprav

| Priorita | ID | Popis |
|---|---|---|
| 🔴 Kritická | C1 | Opravit vrstvové závislosti (Domain → Infrastructure) |
| 🔴 Kritická | C2 | Přidat `IRedisDriver` interface, odebrat závislost na Mock |
| 🔴 Kritická | C3 | Opravit cache miss detekci (empty array vs null) |
| 🔴 Kritická | C4 | Změnit `ICacheAdapter::get()` return typ na `?array` |
| 🟡 Střední | M1 | Přesunout `ProductRepository` implementaci do Infrastructure |
| 🟡 Střední | M2 | Oddělit cache adapter pro produkty a tracking |
| 🟡 Střední | M3 | Použít constructor injection v Presenteru |
| 🟡 Střední | M4 | Přesunout mock služby do dev/test konfigurace |
| 🟡 Střední | M5 | Doplnit testy pro `TrackingRepository` |
| 🟢 Nízká | L1 | Přidat `ITrackingRepository` interface |
| 🟢 Nízká | L3 | Zavést DTO/Value Object pro produkt místo `array` |
| 🟢 Nízká | L5 | Přidat `declare(strict_types=1)` do všech souborů |
