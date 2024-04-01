# Rekapager

Rekapager is a pagination library for PHP, supporting both offset-based and
keyset-based pagination.

Full documentation is available at [rekalogika.dev/rekapager](https://rekalogika.dev/rekapager)

## Keyset Pagination

Keyset pagination is a method of pagination that uses the last row of the
current page as an anchor for the next page. This method is more efficient than
offset pagination because it leverages the index, and does not require the
database to scan all rows from the beginning to reach the desired page.

Instead of using page numbers, the keyset pagination implementation of this
library uses a page identifier object to reference a page. This identifier is
encoded into a string and passed as a single query parameter.

The library works separately from the filtering and sorting logic, and does not
require a specific way to filter or sort your data. It just needs the query (or
comparable information), and it will automatically modify the query to perform
the pagination. The only requirement is that the query needs to have a
deterministic sort order. Queries with multiple sort columns are also supported.

Bidirectional navigation is supported. The user will be able to navigate forward
and backward from the current page. It also supports offset seeking, allowing
the user to skip the immediate next or previous page up to the configured
proximity setting.

In the user interface, the pager will look like a regular pagination control:

![with pages around the current page](https://rekalogika.dev/rekapager/middle.png)

The page number is informational only, and carried over from the start page.

Seeking to the last page is possible. And with keyset pagination, it will be as
fast as seeking to the first page:

![last page](https://rekalogika.dev/rekapager/last-without-count.png)

The page numbers at the end are negative because by default the pager does not
fetch the total count from the underlying data, which is another common
performance issue involving pagination. It can work without knowing the total
count, but if the count is available, the pager will use it:

![last page with count](https://rekalogika.dev/rekapager/last-with-count.png)

It can query the count from the underlying data, or the caller can supply the
count. The count can also be an approximation, and the pager will work without
an exact count.

## Offset Pagination

The library also supports the traditional offset pagination method with several
important improvements. First, it can paginate without the total count of the
data. If the count is not available, the pager won't allow the user to navigate
to the last page:

![no last page](https://rekalogika.dev/rekapager/unknown-last.png)

It also limits the maximum page number that can be navigated to. By default, the
limit is 100. The UI will indicate that the disabled page exists, but the user
is not allowed to navigate to it:

![page limit](https://rekalogika.dev/rekapager/limit.png)

This feature prevents denials of service, either maliciously or accidentally. In
most cases, a real user won't have a good reason for accessing page 56267264,
but doing so can cause a denial of service to the web server, application, and
the database.

## Supported Underlying Data Types

* Doctrine ORM `QueryBuilder`
* Doctrine Collections `Selectable` and `Collection`
* Pagerfanta adapters

## Usage

### Transforming the underlying data into a `PageableInterface` object

This part is framework-independent.

```php
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;

// The underlying data in this example is a Doctrine ORM QueryBuilder

/** @var EntityRepository $postRepository */
$queryBuilder = $postRepository
    ->createQueryBuilder('p')
    ->where('p.setName = :setName')
    ->setParameter('setName', $setName)
    ->addOrderBy('p.date', 'DESC')
    ->addOrderBy('p.title', 'ASC')
    ->addOrderBy('p.id', 'ASC');

// The adapter provides an uniform interface for the different types of
// underlying data collection

$adapter = new QueryBuilderAdapter(
    queryBuilder: $queryBuilder,
    typeMapping: [
        'p.date' => Types::DATE_MUTABLE
    ]
),

// A pageable represents something that can be partitioned into pages. This
// example uses KeysetPageable, which is a pageable that supports keyset
// pagination.

$pageable = new KeysetPageable(
    adapter: $adapter,
    itemsPerPage: $itemsPerPage,
    count: $count,
);
```

### Transforming the `PageableInterface` into a `PagerInterface` object

In this phase, we start involving the framework used in the application. The
example below uses Symfony integration provided by
`rekalogika/rekapager-bundle`.

```php
use Rekalogika\Rekapager\Bundle\Contracts\PagerFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/** @var PagerFactoryInterface $pagerFactory */
/** @var Request $request */

// The pager factory is a service that creates a PagerInterface from a
// PageableInterface

$pager = $pagerFactory->createFromPageable(
    pageable: $pageable,
    request: $request,
    options: new PagerOptions(
        proximity: 3,
    )
);

$currentPage = $pager->getCurrentPage();

foreach ($currentPage as $item) {
    // Do something with the item
}
```

### Rendering the Pager

The `PagerInterface` object contains all the necessary information to render the
pagination control in the user interface. The example below uses the Twig
integration provided by `rekalogika/rekapager-bundle`.

```twig
{# Outputs the item from the current page #}

<table class="table">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Date</th>
        <th>Content</th>
    </tr>
    {% for post in pager.currentPage %}
        <tr>
            <td>{{ post.id }}</td>
            <td>{{ post.title }}</td>
            <td>{{ post.date|date('Y-m-d') }}</td>
            <td>{{ post.content }}</td>
        </tr>
    {% endfor %}
</table>

{# Render the pager #}

{{ rekapager(pager) }}
```

### Batch Processing

A `PageableInterface` object can also be used for batch processing a large
amount of underlying data. The example below demonstrates how to do batch
processing using Doctrine.

```php
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Rekapager\PageableInterface;

/** @var PageableInterface $pageable */
/** @var EntityManagerInterface $entityManager */

$page = $pageable->withItemsPerPage(1000)->getFirstPage();

while ($page = $page->getNextPage()) {
    foreach ($page as $item) {
        // Do something with the item
    }

    $entityManager->flush(); // if required
    $entitymanager->clear();
}
```

## Demo

You can try the demo by running the following command:

```bash
git clone https://github.com/rekalogika/rekapager.git
cd rekapager
composer install
make doctrine serve
```

## Documentation

[rekalogika.dev/rekapager](https://rekalogika.dev/rekapager)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/rekapager`](https://github.com/rekalogika/rekapager) monorepo.