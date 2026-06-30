# Vyhledání informací o produktu


## I. Popis problému

1. Náš systém uchovává informace o jednotlivých produktech v ElasticSeach a MySQL databázi. Data
jsou identická v obou databázích, primárně se dotazujeme ElasticSearch databáze, ale čas od času je
třeba získávat data přímo z MySQL (třeba když provádíme různé experimenty s ElasticSeach) – v tuto
chvíli samozřejmě musíme dál našim zákazníkům na fron-endu zobrazovat data, takže je třeba
přepnout na MySQL.

2. Vzhledem k tomu, že provoz na našich serverech je často extrémní, potřebujeme samozřejmě i data o
produktech cachovat. Z počátku stačí cachovat do souboru, ale je třeba počítat s tím, že v budoucnu
použijete nějakou pokročilejší technologii - a to snadno jen přepnutím v konfiguraci aplikace.

3. Protože marketingové oddělení chce dlouhodobě sledovat zájem o jednotlivé produkty, je třeba také
počítat počet dotazů na konkrétní produkty. Z počátku opět postačí ukládání v plain-textu, ale je
třeba počítat s tím, že v budoucnu použijete pokročilejší technologii, nebo budete chtít změnit
umístění těchto dat.

## Úkol

Vytvořte controller s metodou přijímající ID produktu. Metoda bude vracet data o produktu ve
formátu JSON.

**Základní workflow:**
- Je zavolán dotaz na produkt s jeho ID
- Jestliže je produkt zacachován, je vrácen z cache
- Jestliže produkt není v cache, dotážeme se ElasticSearch/MySQL databáze a výsledek uložíme do
  cache
- Počet dotazů na tento produkt zvýšíme o jedna
- Vrátíme data o produktu ve formátu JSON
  Controller může vypadat například takto:
```php
class ProductController
{
    /**
    * @param string $id
    * @return string
    */
    public function detail($id)
    {
    // do stuff and return json
    }
}
```

## Dodatečné informace

Máme drivery pro ElasticSearch i MySQL, takže je nemusíte psát. Po zavolání metody získáte data o
produktu v poli. Drivery implentují následující interface:
```php
interface IElasticSearchDriver
{
    /**
    * @param string $id
    * @return array
    */
    public function findById($id);
}
interface IMySQLDriver
{
    /**
    * @param string $id
    * @return array
    */
    public function findProduct($id);
}
```
Náš Framework předává všechno, co potřebujete. Jestliže potřebujete předat nějaké parametry do
konstruktoru controlleru, vždy vám předá sám ty správné (nemusíte se tedy o toto starat).
Předáte-li jakékoli ID produktu do driveru, vždy najde produkt – nemusíte se tedy zabývat výjimkami
typu „Not found“.

Cache je nekonečná – nemusíte se starat o její invalidaci. Jednou zacachovaná data nepotřebujeme
nikdy mazat.

Informace o počtech dotazů na produkt jsou jen jednoduché páry (ID Produktu) => (počet dotazů),
žádná jiná data nejsou potřeba.
