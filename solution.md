# Solution – Product Detail API (DDD Architecture)

> Řešení úlohy popsané v `task.md`: REST endpoint pro získání detailu produktu s cachováním a sledováním počtu dotazů podle standardů Domain-Driven Design (DDD) a Clean Architecture.

---

## 1. Přehled architektury

Aplikace je rozdělena do 4 vrstev s důsledným oddělením zodpovědností. Doménová vrstva (`App\Domain`) je chráněna před závislostmi na konkrétních technologiích (DB, Cache) pomocí Dependency Inversion.

```
┌────────────────────────────────────────────────────────┐
│  Presentation Layer (HTTP Presentery)                  │
├────────────────────────────────────────────────────────┤
│  Domain Layer       (Value Objects, Aggregates,        │
│                      Repository Interfaces, Use Cases) │
├────────────────────────────────────────────────────────┤
│  Infrastructure    (Concrete Repositories, Adapters,  │
│                      Drivers, Cache & DB)              │
├────────────────────────────────────────────────────────┤
│  Core Layer         (Routing, DI Container Bootstrap)  │
└────────────────────────────────────────────────────────┘
```

| Vrstva | Namespace | Obsah |
|---|---|---|
| **Presentation** | `App\Presentation\Product` | [ProductPresenter](file:///Users/devel/logio-task/nette/app/Presentation/Product/ProductPresenter.php) – Přijímá requesty, validuje vstup do Value Objektů a volá Use Cases. |
| **Domain (Product)** | `App\Domain\Product` | [Product](file:///Users/devel/logio-task/nette/app/Domain/Product/Product.php) (Entity/Aggregate Root), [IProductRepository](file:///Users/devel/logio-task/nette/app/Domain/Product/IProductRepository.php) (Contract). |
| **Domain (Tracking)** | `App\Domain\Tracking` | [ProductTracking](file:///Users/devel/logio-task/nette/app/Domain/Tracking/ProductTracking.php) (Entity/Aggregate Root s doménovou logikou `increment()`), [ITrackingRepository](file:///Users/devel/logio-task/nette/app/Domain/Tracking/ITrackingRepository.php) (Contract). |
| **Domain (Value Objects)**| `App\Domain\Product\ValueObject` | [ProductId](file:///Users/devel/logio-task/nette/app/Domain/Product/ValueObject/ProductId.php), [ProductName](file:///Users/devel/logio-task/nette/app/Domain/Product/ValueObject/ProductName.php) (Typově bezpečné neměnné hodnoty). |
| **Domain (Use Cases)** | `App\Domain\Product\UseCase` | [GetProductDetailUseCase](file:///Users/devel/logio-task/nette/app/Domain/Product/UseCase/GetProductDetailUseCase.php), [GetProductTrackingUseCase](file:///Users/devel/logio-task/nette/app/Domain/Product/UseCase/GetProductTrackingUseCase.php) (Aplikační scénáře). |
| **Infrastructure (Repositories)** | `App\Infrastructure\Product`<br>`App\Infrastructure\Tracking` | [ProductRepository](file:///Users/devel/logio-task/nette/app/Infrastructure/Product/ProductRepository.php), [TrackingRepository](file:///Users/devel/logio-task/nette/app/Infrastructure/Tracking/TrackingRepository.php) (Concrete DB/Cache operations & Object Mapping). |
| **Infrastructure (DB & ES)** | `App\Infrastructure\DB`<br>`App\Infrastructure\MySQL`<br>`App\Infrastructure\ElasticSearch` | [IDBAdapter](file:///Users/devel/logio-task/nette/app/Infrastructure/DB/IDBAdapter.php), [MySQLAdapter](file:///Users/devel/logio-task/nette/app/Infrastructure/MySQL/MySQLAdapter.php), [ElasticSearchAdapter](file:///Users/devel/logio-task/nette/app/Infrastructure/ElasticSearch/ElasticSearchAdapter.php) (Unified DB Adapter pattern). |
| **Infrastructure (Cache & Redis)**| `App\Infrastructure\Cache`<br>`App\Infrastructure\FileStorage`<br>`App\Infrastructure\Redis` | [ICacheAdapter](file:///Users/devel/logio-task/nette/app/Infrastructure/Cache/ICacheAdapter.php), [FileCacheAdapter](file:///Users/devel/logio-task/nette/app/Infrastructure/FileStorage/FileCacheAdapter.php), [RedisAdapter](file:///Users/devel/logio-task/nette/app/Infrastructure/Redis/RedisAdapter.php) (Unified Cache Adapter pattern). |
| **Core** | `App\Core`, `App\` | [RouterFactory](file:///Users/devel/logio-task/nette/app/Core/RouterFactory.php), [Bootstrap](file:///Users/devel/logio-task/nette/app/Bootstrap.php) (Config booting & DI Container compilation). |

---

## 2. Použité návrhové vzory a principy

### 2.1 Rich Domain Model (Bohaté Entity)
Zamezili jsme chudému doménovému modelu (Anemic Domain Model). Veškerá pravidla chování jsou zapouzdřena přímo v entitách:
*   [ProductTracking](file:///Users/devel/logio-task/nette/app/Domain/Tracking/ProductTracking.php) spravuje stav a inkrementaci prostřednictvím metody `increment()`.
*   Zákonitosti identity a hodnot produktu jsou vynuceny přes Value Objecty [ProductId](file:///Users/devel/logio-task/nette/app/Domain/Product/ValueObject/ProductId.php) a [ProductName](file:///Users/devel/logio-task/nette/app/Domain/Product/ValueObject/ProductName.php).

### 2.2 Use Case Pattern (Application Services)
Logika aplikačních scénářů je rozčleněna do samostatných tříd (Use Cases) namísto jedné obří služby:
*   [GetProductDetailUseCase](file:///Users/devel/logio-task/nette/app/Domain/Product/UseCase/GetProductDetailUseCase.php) načte agregáty produktu a trackování z doménových repozitářů, provede inkrementaci dotazů, uloží změnu a zkompiluje odpověď.
*   [GetProductTrackingUseCase](file:///Users/devel/logio-task/nette/app/Domain/Product/UseCase/GetProductTrackingUseCase.php) se stará o rychlé vrácení metrik dotazů.

### 2.3 Adapter Pattern (Pružná DB a Cache)
Drivery databází (`IElasticSearchDriver`, `IMySQLDriver`) a cache mají rozdílná API. Vyřešeno adaptéry s jednotným rozhraním:
```
IElasticSearchDriver  ──►  ElasticSearchAdapter  ──┐
                                                    ├──►  IDBAdapter  ──►  ProductRepository
IMySQLDriver          ──►  MySQLAdapter          ──┘
```
Stejný vzor se uplatňuje na cache (`FileCacheAdapter` vs. `RedisAdapter` implementující `ICacheAdapter`).

### 2.4 Dependency Inversion (DIP)
Třídy v doméně závisí výhradně na abstrakcích (`IProductRepository`, `ITrackingRepository`, `ICacheAdapter`). Konkrétní repozitáře a adaptéry se nacházejí v infrastruktuře a doméně se injektují prostřednictvím DI. Doménový kód je tak 100% čistý od SQL, ElasticSearch či Redis ovladačů.

---

## 3. Workflow požadavku

```
GET /product/detail/<id>
        │
        ▼
ProductPresenter::actionDetail(string $id)
        │
        ├─► Převod string $id na Value Object ProductId
        ▼
GetProductDetailUseCase::execute(ProductId $productId)
        │
        ├─► ProductRepository::findById(ProductId)
        │         ├─► ICacheAdapter::get("product_<id>") -> [Cache Hit] -> Return Product
        │         └─► [Cache Miss]
        │                 ├─► IDBAdapter::getByID(id) -> SQL/ES Driver Query
        │                 ├─► ICacheAdapter::set("product_<id>", rawData)
        │                 └─► Return Product
        │
        ├─► TrackingRepository::findByProductId(ProductId)
        │         └─► ICacheAdapter::get("tracking_<id>") -> Return ProductTracking
        │
        ├─► ProductTracking::increment()
        │
        ├─► TrackingRepository::save(ProductTracking)
        │         └─► ICacheAdapter::set("tracking_<id>", serializedData)
        │
        ▼
ProductPresenter -> sendJson([...data, tracking_count])
```

---

## 4. Výměnitelnost technologií v DI (`nette/config/services.neon`)

### Záměna zdrojové DB (MySQL ↔ ElasticSearch)
Stačí povolit příslušný adaptér v konfiguraci:
```neon
    mySQLAdapter: App\Infrastructure\MySQL\MySQLAdapter
#   elasticSearchAdapter: App\Infrastructure\ElasticSearch\ElasticSearchAdapter
```
Díky autowiringu rozhraní `IDBAdapter` v [ProductRepository](file:///Users/devel/logio-task/nette/app/Infrastructure/Product/ProductRepository.php) proběhne změna transparentně na pozadí.

### Záměna Cache úložiště (Soubory ↔ Redis)
Protože produktová data a analytics data (tracking) mohou mít odlišné nároky, repozitáře mají samostatné, nezávisle konfigurovatelné cache adaptéry:
```neon
    # Cache adapter pro produktová data
    productCacheAdapter: App\Infrastructure\FileStorage\FileCacheAdapter
#   productCacheAdapter: App\Infrastructure\Redis\RedisAdapter

    # Cache adapter pro čítač dotazů (analytics data)
    trackingStorageAdapter: App\Infrastructure\FileStorage\FileCacheAdapter
#   trackingStorageAdapter: App\Infrastructure\Redis\RedisAdapter
```

---

## 5. Zajištění testovatelnosti a oddělení prostředí

- **Mocks:** Mockovací drivery pro ES/MySQL/Redis testy jsou přesunuty do lokální DI konfigurace [services.local.neon](file:///Users/devel/logio-task/nette/config/services.local.neon), aby se zabránilo znečištění produkčních definic.
- **Unit testy:** Soubor [ProductRepositoryTest.php](file:///Users/devel/logio-task/nette/tests/ProductRepositoryTest.php) plně testuje:
  1. `GetProductDetailUseCaseTest` – prověření správné orchestrace a inkrementace.
  2. `ProductRepositoryTest` – prověření cache hitů (kdy DB není vůbec dotázána) a cache missů.
  3. `TrackingRepositoryTest` – testování výchozí hodnoty na miss a uložení/načtení stavu trackeru.
