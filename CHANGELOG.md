# Changelog

# 0.7.0

* feat: infinite scrolling
* feat: AssetMapper compatibility
* fix: js module
* fix: change infinite scrolling breakpoint to 768px
* feat: infinite scrolling demo
* feat: `rekapager_infinite_scrolling_content` Twig function

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
