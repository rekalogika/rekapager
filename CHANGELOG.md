# Changelog

## 0.5.3

* test: add tests for zero proximity pager, empty pager, and test current count for all
  pagers
* fix(`OffsetPage`): fix `OutOfBoundsException` on an empty first page.
* fix(`Pager`): fetch only 2 * proximity ahead and behind.

## 0.5.2

* refactor: `PagerItemInterface` methods now returns itself, instead of
  `PageInterface`
* fix(`QueryBuilderAdapter`): throws an exception if the query does not have an
  order by clause.
* feat: add `PagerfantaPageable`
* refactor(`PagerFactoryInterface`): rename method to `createPager()`

## 0.5.0

* build: initial commit
