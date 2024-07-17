# Changelog

# 0.13.2

* feat: batch time limit

# 0.13.1

* feat: add `BatchProcess` and related classes
* feat(`BatchProcess`): add `SimpleBatchProcess`, simplify wiring, add
  process-file feature
* fix(`BatchProcess`): show interrupt message only once
* refactor(`BatchProcess`): `processItem` now accept an event
* refactor(`BatchProcess`): remove metric collection & logging from main class

# 0.13.0

* refactor: add `PageIdentifierEncoderResolverInterface` for simplification

# 0.12.4

* fix: fix deprecations

# 0.12.3

* feat: add `Closure` type parameter for count in all Pageable implementations

# 0.12.2

* fix(`PagerFantaAdapterAdapter`): rename `$pagerfanta` to `$adapter`

# 0.12.1

* fix: remove all `array_is_list()` checks

# 0.12.0

* fix(`PagerItem`): `withPageNumber` should return static
* build: update php-cs-fixer
* feat(`QueryBuilderAdapter`): add `indexBy` parameter
* feat(`PageableInterface`): add `$start` parameter to `getPages()` method to
  ease batch resuming.
* refactor: move common indexBy logic to separate package
* feat(`SelectableAdapter`): add `indexBy` parameter
* feat(`PagerfantaAdapterAdapter`): add `indexBy` parameter
* test: use SQL file for fixtures population. should fix CI race condition.

# 0.11.2

* fix: add interface covariance where applicable

# 0.11.1

* test: add `UnsupportedCollectionTest`
* fix: improve error message if a property does not exist or the value is null
* feat: friendly error message if the underlying collection's item is not an array or object
* feat(`SelectableAdapter`): refuse to continue if the supplied criteria has a
  first result or max results parameter set
* feat(`QueryBuilderAdapter`): refuse to continue if the supplied criteria has a
  first result or max results parameter set
* legal: add LICENSE to each of the subpackages

# 0.11.0

* refactor: change `PageableInterface::getPageIdentifierClass()` from static to
  instance method to simplify decoration

# 0.10.1

* chore: remove unneeded intermediate interfaces

# 0.10.0

* chore: remove TIdentifier template as it feels superfluous in userland

# 0.9.3

* fix: renumbering of pages if anchored to the first page.

# 0.9.2

* build: limit `zenstruck/foundry` to 1.37.* for now
* fix(`PagerFactory`): pager should not be lazy, so that if the page does not
  exist, it will throw an exception immediately, not inside template.
* feat(`PagerFactory`): wrap `OutOfBoundsException` and add the pager and
  options to the exception class.
* fix(`Pager`): fix offset pagination bug where the last page points to the
  first page.

# 0.9.1

* fix(`QueryBuilderAdapter`): use generated field names for our boundary fields
  in the select statement, avoids conflict with other fields in the query.

# 0.9.0

* build: Symfony 7.1 compatibility
* fix: bug of extra first & last page showing up in small data set.

# 0.8.4

* fix: assertion in QueryCounter

# 0.8.3

* fix(keyset): going from 2nd last page to last page now works properly

# 0.8.2

* refactor: remove configuration from API Platform bundle, will try to reuse
  standard API Platform extra properties.
* feat: throw exception if a boundary value is null

# 0.8.1

* feat: add `PagerFactoryInterface` for API Platform

# 0.8.0

* build: spinoff encoder service definition
* build: twig & twigbundle is now optional
* feat: API Platform support
* feat(`OpenApi`): change all 'page' parameters to accept string
* feat(ApiPlatform): add `PageNormalizer` & `PagerFactory`
* feat(`QueryBuilderAdapter`): add type detection

# 0.7.2

* fix: next page skipping bug & lazy loading Pager
* perf: flip SQL keyset expression for potential performance improvement

# 0.7.1

* build: update babel config according to symfony docs
* fix(`bootstrap5.html.twig`): fix label_unknown bug
* fix(`ProximityPager`): fix bug when going to next page from second to last
  page

# 0.7.0

* feat: infinite scrolling
* feat: AssetMapper compatibility
* fix: js module
* fix: change infinite scrolling breakpoint to 768px
* feat: infinite scrolling demo
* feat: `rekapager_infinite_scrolling_content` Twig function
* fix(`Dockerfile`): fix importmap
* demo: show page identifier

## 0.6.2

* fix: various pager numbering fixes

## 0.6.1

* fix(`QueryBuilderAdapter`): now refuses to continue if the same field appears
  multiple times in the order by clause.


## 0.6.0

* test: add tests for zero proximity pager, empty pager, and test current count for all
  pagers
* fix(`OffsetPage`): fix `OutOfBoundsException` on an empty first page.
* fix(`Pager`): fetch only 2 * proximity ahead and behind.
* feat(`PageableInterface`): add `getPages()` for easy batching

## 0.5.2

* refactor: `PagerItemInterface` methods now returns itself, instead of
  `PageInterface`
* fix(`QueryBuilderAdapter`): throws an exception if the query does not have an
  order by clause.
* feat: add `PagerfantaPageable`
* refactor(`PagerFactoryInterface`): rename method to `createPager()`

## 0.5.0

* build: initial commit
