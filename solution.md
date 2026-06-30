# Solution – Product Detail API

> Řešení úlohy popsané v `task.md`: REST endpoint pro získání detailu produktu s cachováním a sledováním počtu dotazů.

---

## 1. Přehled architektury

Aplikace je postavena na frameworku **Nette** (PHP 8.2+) a dělí se do čtyř vrstev inspirovaných **Clean Architecture** / **Layered Architecture** s DDD-inspirovaným namespace dělením (žádné Aggregates, Entities, Domain Events, Value Objects etc.):

```
┌────────────────────────────────────────────────────┐
│  Presentation  (HTTP vrstva, Presentery/Controllery)│
├────────────────────────────────────────────────────┤
│  Domain        (Byznys logika, Repository, Service) │
├────────────────────────────────────────────────────┤
│  Infrastructure (Adaptery, Drivery, Cache, DB)      │
├────────────────────────────────────────────────────┤
│  Core           (Router, Bootstrap)                 │
└────────────────────────────────────────────────────┘
```

| Vrstva | Namespace | Obsah |
|---|---|---|
| Presentation | `App\Presentation\Product` | `ProductPresenter` – HTTP vstup / JSON výstup |
| Domain | `App\Domain\Product` | `ProductService`, `ProductRepository`, `IProductRepository` |
| Domain | `App\Domain\Tracking` | `TrackingRepository` – počítadlo dotazů |
| Infrastructure | `App\Infrastructure\DB` | `DBRepository`, `IDBAdapter` – abstrakce DB |
| Infrastructure | `App\Infrastructure\ElasticSearch` | `ElasticSearchAdapter` + `IElasticSearchDriver` |
| Infrastructure | `App\Infrastructure\MySQL` | `MySQLAdapter` + `IMySQLDriver` |
| Infrastructure | `App\Infrastructure\Cache` | `ICacheAdapter`, `ICacheRepository` |
| Infrastructure | `App\Infrastructure\FileStorage` | `FileCacheAdapter` – file-based cache |
| Infrastructure | `App\Infrastructure\Redis` | `RedisAdapter` – Redis cache (připraveno) |
| Core | `App\Core` | `RouterFactory`, `Bootstrap` |

---

## 2. Použité návrhové vzory

### 2.1 Adapter Pattern

**Problém:** Drivery ElasticSearch (`IElasticSearchDriver`) a MySQL (`IMySQLDriver`) mají různé metody (`findByID` vs. `findProduct`). Domain vrstva nesmí znát konkrétní technologii.

**Řešení:** Každý driver je obalený vlastním *Adapterem*, který implementuje společné rozhraní `IDBAdapter`:

```
IElasticSearchDriver ──► ElasticSearchAdapter ──┐
                                                  ├──► IDBAdapter ──► DBRepository
IMySQLDriver         ──► MySQLAdapter         ──┘
```

**Přepnutí databáze** (dle požadavku I.1 v `task.md`) je tak triviální – v `services.neon` se zakomentuje jeden adapter a odkomentuje druhý, kód se nijak nemění:

```neon
# Přepnutí ze MySQL na ElasticSearch:
# mySQLAdapter: App\Infrastructure\MySQL\MySQLAdapter
elasticSearchAdapter: App\Infrastructure\ElasticSearch\ElasticSearchAdapter
```

Stejná logika platí pro cache (I.2 v `task.md`):

```
FileStorage (Nette) ──► FileCacheAdapter ──┐
                                            ├──► ICacheAdapter ──► ProductRepository / TrackingRepository
RedisDriver         ──► RedisAdapter      ──┘
```

---

### 2.2 Repository Pattern

**Domain vrstva** definuje pouze interface `IProductRepository`:

```php
interface IProductRepository
{
    public function getProduct(string $id): array;
}
```

`ProductRepository` je konkrétní implementace, která:
1. Dědí `DBRepository` (přístup k `IDBAdapter`).
2. Implementuje `IProductRepository` (kontrakt pro domain).
3. Implementuje `ICacheRepository` (kontrakt pro cachování – klíčování cache).

```
ProductRepository
  extends DBRepository          (IDBAdapter $storageAdapter)
  implements IProductRepository (getProduct)
  implements ICacheRepository   (getCacheKey)
```

`TrackingRepository` je oddělený repository pro agregát sledování – uchovává pouze páry `id => count` přes `ICacheAdapter`.

---

### 2.3 Strategy Pattern (přepínatelné implementace přes DI)

Výběr konkrétní databáze a cache je **strategií** injektovanou kontejnerem závislostí (Nette DI). Nové strategie (např. MongoDB, Memcached) lze přidat bez změny existujícího kódu – stačí vytvořit nový adapter implementující příslušné rozhraní a zaregistrovat ho v konfiguraci.

---

### 2.4 Dependency Injection + IoC

Nette DI Container spravuje celý životní cyklus objektů. Závislosti jsou předávány přes konstruktor (preferred) nebo přes `#[Inject]` atribut (v Presenterech – Nette konvence):

```php
// Presenter – Nette konvence
#[Inject]
public ProductService $productService;

// Doménová třída – konstruktor injection (čistší, testovatelné)
public function __construct(
    private readonly IProductRepository $productRepository,
    private readonly TrackingRepository $trackingRepository,
) {}
```

---

### 2.5 Interface Segregation (část SOLID)

Rozhraní jsou navrhována malá a soudržná:

| Interface | Metody | Účel |
|---|---|---|
| `IProductRepository` | `getProduct()` | Přístup k produktu |
| `IDBAdapter` | `getByID()` | Sjednocení DB přístupu |
| `ICacheAdapter` | `get()`, `set()` | Cache operace |
| `ICacheRepository` | `getCacheKey()` | Generování klíče |
| `IElasticSearchDriver` | `findByID()` | ES driver |
| `IMySQLDriver` | `findProduct()` | MySQL driver |

---

## 3. Workflow volání

```
GET /product/detail/<id>
        │
        ▼
ProductPresenter::actionDetail(id)
        │
        ▼
ProductService::getProductDetail(id)
        │
        ├─► ProductRepository::getProduct(id)
        │         │
        │         ├─► ICacheAdapter::get("product_<id>")
        │         │         (FileCacheAdapter / RedisAdapter)
        │         │
        │         └─[cache miss]─► IDBAdapter::getByID(id)
        │                              (ElasticSearchAdapter / MySQLAdapter)
        │                              └─► Driver::findByID/findProduct(id)
        │                          └─► ICacheAdapter::set("product_<id>", data)
        │
        ├─► TrackingRepository::increment(id)
        │         └─► ICacheAdapter::get/set("tracking_<id>")
        │
        └─► sendJson([...product data, tracking_count])
```

---

## 4. Výměnitelnost technologií (požadavky I.1, I.2, I.3)

### Přepnutí DB (ElasticSearch ↔ MySQL)

Editace v `config/services.neon`:

```neon
# Aktuálně aktivní: MySQL
mySQLAdapter: App\Infrastructure\MySQL\MySQLAdapter
# Přepnutí na ElasticSearch:
# elasticSearchAdapter: App\Infrastructure\ElasticSearch\ElasticSearchAdapter
```

Žádný kód v Domain nebo Presentation vrstvě se nemění. `ProductRepository` dostane jinou implementaci `IDBAdapter` automaticky přes DI.

### Přepnutí Cache (File ↔ Redis)

```neon
# Aktuálně aktivní: file cache
fileCacheAdapter: App\Infrastructure\FileStorage\FileCacheAdapter
# Přepnutí na Redis:
# redisCacheAdapter: App\Infrastructure\Redis\RedisAdapter
```

Stejný princip – `ICacheAdapter` je injektován do `ProductRepository` a `TrackingRepository`.

### Přidání nové technologie

Stačí:
1. Vytvořit novou třídu implementující `IDBAdapter` nebo `ICacheAdapter`.
2. Zaregistrovat ji v `services.neon`.

Žádné změny v domain nebo presentation vrstvě.

---

## 5. Struktura souborů

```
nette/app/
├── Bootstrap.php
├── Core/
│   └── RouterFactory.php               # URL routing
├── Domain/
│   ├── Product/
│   │   ├── IProductRepository.php      # Domain kontrakt
│   │   ├── ProductRepository.php       # Implementace s cache
│   │   ├── ProductService.php          # Business logika
│   │   ├── UseCase/                    # (připraveno pro CQRS/use cases)
│   │   └── ValueObject/               # (připraveno pro value objects)
│   └── Tracking/
│       └── TrackingRepository.php      # Počítadlo dotazů
├── Infrastructure/
│   ├── Cache/
│   │   ├── ICacheAdapter.php
│   │   └── ICacheRepository.php
│   ├── DB/
│   │   ├── DBRepository.php
│   │   └── IDBAdapter.php
│   ├── ElasticSearch/
│   │   ├── ElasticSearchAdapter.php    # Adapter: ES → IDBAdapter
│   │   ├── IElasticSearchDriver.php
│   │   └── Mock/ElasticSearchDriver.php
│   ├── FileStorage/
│   │   └── FileCacheAdapter.php        # Adapter: Nette FileStorage → ICacheAdapter
│   ├── MySQL/
│   │   ├── MySQLAdapter.php            # Adapter: MySQL → IDBAdapter
│   │   ├── IMySQLDriver.php
│   │   └── Mock/MySQLDriver.php
│   └── Redis/
│       ├── RedisAdapter.php            # Adapter: Redis → ICacheAdapter
│       └── Mock/RedisDriver.php
└── Presentation/
    └── Product/
        └── ProductPresenter.php        # Controller, JSON response
```

---

## 6. Co je připraveno, ale ještě neimplementováno

| Místo | Poznámka |
|---|---|
| `Domain/Product/UseCase/` | Připraveno pro budoucí CQRS use-case třídy (GetProductUseCase, apod.) |
| `Domain/Product/ValueObject/` | Připraveno pro `ProductId` a další value objects (typová bezpečnost ID) |
| `Infrastructure/Redis/RedisAdapter` | Implementován, pouze zakomentován – produkčně použitelný po připojení reálného Redis klienta |

---

## 7. Shrnutí

| Požadavek | Řešení |
|---|---|
| ElasticSearch nebo MySQL | **Adapter Pattern** + přepínání přes DI konfiguraci |
| Cachování, snadno zaměnitelné | **Adapter Pattern** (`ICacheAdapter`) + přepínání v `services.neon` |
| Počítadlo dotazů | `TrackingRepository` s `ICacheAdapter` (file nebo Redis) |
| Oddělení vrstev | **Layered Architecture** (Presentation / Domain / Infrastructure) |
| Testovatelnost | Vše za interface, mock drivery v `Mock/` adresářích |
| DI / IoC | Nette DI Container, constructor injection |
