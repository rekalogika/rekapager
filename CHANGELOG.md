# Changelog

## 0.5.3

* test: add tests for zero proximity pager, empty pager, and test current count for all
  pagers
* fix(`OffsetPage`): fix `OutOfBoundsException` on an empty first page.

## 0.5.2

* refactor: `PagerItemInterface` methods now returns itself, instead of
  `PageInterface`
* fix(`QueryBuilderAdapter`): throws an exception if the query does not have an
  order by clause.
* feat: add `PagerfantaPageable`
* refactor(`PagerFactoryInterface`): rename method to `createPager()`

## 0.5.0

* build: initial commit
