# Rekapager

Rekapager is a pagination library for PHP, supporting both offset-based and
keyset-based pagination (also called cursor-based pagination).

Full documentation is available at [rekalogika.dev/rekapager](https://rekalogika.dev/rekapager)

## Keyset Pagination (or Cursor-Based Pagination)

Keyset pagination is a method of pagination that uses the last row of the
current page as an anchor for the next page. This method has these advantages
compared to the traditional offset-based pagination:

* It is more efficient because it leverages the index. It does not require the
  database to scan all rows from the beginning to reach the desired page.
* It is more resilient to data changes. The data will not drift when rows are
  inserted or deleted.

### Identifying Pages

Instead of using page numbers, a page identifier object is used to reference a
page. This identifier is encoded into a string and passed as a single query
parameter.

Because it requires only a single query parameter, it works similarly on the
surface with offset pagination. Migrating from offset pagination to keyset
pagination will be straightforward. The difference is that instead of having
page numbers in the URL, we'll be getting an 'ugly' identifier, which is opaque
to the user, but meaningful to the application.

It also easily allows us to keep the pagination job separate from the filtering
and sorting logic. The library does not require a specific way to filter or sort
your data.

### Queries

Multiple sort columns are supported. The library will automatically generate the
WHERE query for you, including the complex cases involving more than two sort
columns. The only requirement is that the query needs to have a deterministic
sort order.

Some backends also have the option to use SQL row values syntax for a slightly
better performance.

### Bidirectional Navigation and Page Skipping

Bidirectional navigation is supported. The user will be able to navigate forward
and backward from the current page. It also supports offset seeking, allowing
the user to skip the immediate next or previous page up to the configured
proximity setting.

In the user interface, the pager will look like a regular pagination control:

![with pages around the current page](https://rekalogika.dev/rekapager/middle.png)

The page number is informational only, and carried over from the start page.

### Jumping to the Last Page

Seeking to the last page is possible. And with keyset pagination, it will be as
fast as seeking to the first page:

![last page](https://rekalogika.dev/rekapager/last-without-count.png)

### Page Numbers and Counting

Negative page numbers shown above indicate the page numbers from the end. The
last page is -1, the second to last is -2, and so on. It is done this way
because by default the pager does not fetch the total count from the underlying
data, which is another common performance issue involving pagination.

The pager can work without knowing the total count, but if the count is
available, the pager will use it:

![last page with count](https://rekalogika.dev/rekapager/last-with-count.png)

It can query the count from the underlying data, or the caller can supply the
count. The count can also be an approximation, and the pager will work without
an exact count.

## Offset Pagination

The library also supports the traditional offset pagination method with several
important improvements. 

### No Counting by Default

It can paginate without the total count of the data. If the count is not known,
the pager won't allow the user to navigate to the last page:

![no last page](https://rekalogika.dev/rekapager/unknown-last.png)

As with keyset pagination, the count can be supplied by the caller, or the pager
can query the count from the underlying data.

### Page Number Limit

It also limits the maximum page number that can be navigated to. By default, the
limit is 100. The UI will indicate that the disabled page exists, but the user
is not allowed to navigate to it:

![page limit](https://rekalogika.dev/rekapager/limit.png)

This limit can be configured, or disabled entirely. But if you need the function
to seek beyond a certain number of pages, you should consider switching to
keyset pagination instead.

### Pagerfanta Interoperability

For interoperability, the library supports offset pagination using any of the
existing Pagerfanta adapters, as well as adapting a Pagerfanta instance into an
`OffsetPageableInterface` instance.

### Same Interface for Both Offset and Keyset Pagination

Both offset pagination and keyset pagination use the same interface. This allows
you to keep using offset pagination and later switch to keyset pagination
without too much effort.

## Secure by Default (or Prevents AI Crawlers from Wreaking Havoc)

With keyset pagination, not counting by default, and limiting pages in
offset pagination, the library is secure by default. It prevents denials of
service, either maliciously or accidentally. In most cases, a real user won't
have a good reason for accessing page 56267264, but doing so can cause a denial
of service to the web server, application, and the database.

After the AI craze, there is a surge of web crawlers that are looking for
contents for AI training. Unlike traditional search engine crawlers, these new
crawlers tend to be much dumber and much less respectful. Some would foolishly
traverse thousands of paginated contents with a sub-second delay, causing a
denial of service to the server. If your application is public and uses
pagination, this library can help to prevent this problem.

## Supported Data Types

* Doctrine Collections `Selectable` and `Collection`
* Doctrine ORM `QueryBuilder` and `NativeQuery`
* Doctrine DBAL `QueryBuilder`
* Pagerfanta adapters

## Framework Integrations

* Symfony
* API Platform

## Usage

### Transforming the underlying data into a `PageableInterface` object

This part is framework-independent.

```php
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Rekalogika\Rekapager\Doctrine\ORM\QueryBuilderAdapter;
use Rekalogika\Rekapager\Keyset\KeysetPageable;
use Rekalogika\Rekapager\Offset\OffsetPageable;

// The underlying data in this example is a Doctrine ORM QueryBuilder

/** @var EntityRepository $postRepository */

$queryBuilder = $postRepository
    ->createQueryBuilder('p')
    ->where('p.group = :group')
    ->setParameter('group', $group)
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

// There is also an OffsetPageable for offset pagination. An adapter can
// support either or both types of pagination.

$pageable = new OffsetPageable(
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
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Date</th>
            <th>Content</th>
        </tr>
    </thead>
    <tbody {{ rekapager_infinite_scrolling_content() }}>
        {% for post in pager.currentPage %}
            <tr>
                <td>{{ post.id }}</td>
                <td>{{ post.title }}</td>
                <td>{{ post.date|date('Y-m-d') }}</td>
                <td>{{ post.content }}</td>
            </tr>
        {% endfor %}
    </tbody>
</table>

{# Render the pager #}

{{ rekapager(pager) }}
```

## Batch Processing

A `PageableInterface` object can also be used for batch processing a large
amount of underlying data. The example below demonstrates how to do batch
processing using Doctrine.

```php
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Rekapager\PageableInterface;

/** @var PageableInterface $pageable */
/** @var EntityManagerInterface $entityManager */

foreach ($pageable->withItemsPerPage(1000)->getPages() as $page) {
    foreach ($page as $item) {
        // Do something with the item
    }

    // Do something after each page here
    $entityManager->flush(); // if required
    $entitymanager->clear();
}
```

There is also a console command framework, so you can easily create console
commands to run your batch jobs. Your console commands will come with all these
features: informative output, batch process resuming, progress file, running the
command up to the specified duration, signal handling, and more.

## Demo

You can try the demo by running the following command:

```bash
docker run --rm -p 8187:80 ghcr.io/rekalogika/rekapager:latest
```

Then access the demo at [http://localhost:8187](http://localhost:8187).

## Acknowledgements

* [Use the Index, Luke](https://use-the-index-luke.com/no-offset)
* [Pagerfanta](https://www.babdev.com/open-source/packages/pagerfanta/docs/4.x/intro)
* [PagerWave](https://gitlab.com/pagerwave/PagerWave)
* [fast-doctrine-paginator](https://github.com/mentionapp/fast-doctrine-paginator)

## Documentation

[rekalogika.dev/rekapager](https://rekalogika.dev/rekapager)

## License

MIT

## Contributing

This framework consists of multiple repositories split from a monorepo. Be
sure to submit issues and pull requests to the
[`rekalogika/rekapager`](https://github.com/rekalogika/rekapager) monorepo.