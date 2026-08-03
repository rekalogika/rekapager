# rekapager: three defects in keyset pagination, with fixes

**Package:** `rekalogika/rekapager-keyset-pagination`
**Version tested:** v1.2.0 (`1041458326e3e6ba3273ed72fff35d3ab6bbc55f`), PHP 8.4.24
**Adapter used for reproduction:** `rekalogika/rekapager-doctrine-collections-adapter` v1.2.0 (`SelectableAdapter` over an `ArrayCollection`) — no database needed
**Patch:** `rekapager-keyset-null-boundary.patch`, three files, applies from the package root with `git apply -p1`

Found while implementing JSON:API `links.last` in a downstream app. All three
are reproducible in isolation and independent of any ORM.

---

## 1. A null boundary does not survive `encode()`/`decode()` (the blocker)

`getFirstPage()` and `getLastPage()` are the only identifiers whose
`boundaryValues` is null — meaning *unbounded*, "anchored at the end of the
set" rather than "after these column values".
`SymfonySerializerKeysetPageIdentifierEncoder` loses that:

```php
$encodedBoundaryValues = [];          // initialised to []

if (\is_array($boundaryValues)) {     // skipped when null
    ...
}

$array = ['v' => $encodedBoundaryValues, ...];   // written unconditionally
```

so null is encoded as `[]`, and `decode()` (`$array['v'] ?? null` → `[]`)
hands `[]` straight back.

`KeysetPage` decides both neighbour questions on identity with null:

```php
private function hasNextPage(): bool
{
    if ($this->pageIdentifier->getBoundaryType() === BoundaryType::Lower) { ... }

    return $this->pageIdentifier->getBoundaryValues() !== null;   // [] !== null
}
```

A decoded last page therefore claims a next page it does not have, and
following that link throws.

**Observed** (650 items, 50 per page):

```
server-side getLastPage(): boundaryValues=NULL   next=null
after round-trip:          boundaryValues=array() next=PRESENT
following the bogus next link: OutOfBoundsException: The page does not exist.
```

Note the rows themselves are fine — both adapters already treat `[]` as
unbounded (`QueryBuilderAdapter::getKeysetItems()` normalises `[] → null`
explicitly; `SelectableAdapter::createCalculatorFields()` skips null values).
Only the two `!== null` predicates in `KeysetPage` disagree, which is what
makes the failure look like "the last page has a next page".

### Fix

Canonicalise in `KeysetPageIdentifier` rather than in the encoder alone. An
empty boundary array is not a boundary anywhere in this library, so making the
value object refuse to hold one closes every construction path at once —
including the decode side of *any* encoder, present or future, and including
cursors already handed out to clients:

```php
$this->boundaryValues = $boundaryValues === [] ? null : $boundaryValues;
```

in both `__construct()` and `__unserialize()`. The encoder is also fixed to
emit null rather than `[]`, so the payload stops lying — but the identifier
change alone is what repairs in-flight cursors.

While in `decode()`: `$decodedBoundaryValues` was accumulated and then
discarded — denormalised objects were written back into `$boundaryValues`, and
that is what got returned. Harmless today, since both arrays end up with the
same contents, but it is a trap. The patch returns `$decodedBoundaryValues`.

---

## 2. `__unserialize()` rejects a legitimately-null `pageNumber` / `limit`

```php
if (!\is_int($pageNumber)) {                  // property is ?int
    throw new UnexpectedValueException('Invalid page number');
}

if (!(\is_int($limit) && $limit >= 1)) {      // property is ?int
    throw new UnexpectedValueException('Invalid limit');
}
```

Both properties are nullable, both are documented as nullable, and both are
routinely null: `getNextPage()`, `getPreviousPage()` and `getFirstPage()` all
construct identifiers with `limit: null`, and `getLastPage()` does too when the
pageable has no count.

This makes `SerializeSecretKeysetPageIdentifierEncoder` — which round-trips
via `serialize()`/`unserialize()` and therefore through these guards — unusable
for essentially every real cursor. Three of four shapes fail:

```
next-page style (limit null, pageNo 2)     UnexpectedValueException: Invalid limit
last-page style (boundary null, no count)  UnexpectedValueException: Invalid limit
page number null                           UnexpectedValueException: Invalid page number
fully populated                            OK
```

### Fix

Let null through; keep the type and range checks for non-null values.

---

## 3. `getNextPages()` returns `[]` on an upper-bounded page, depending on call order

`getRealResult()` ends with a stray unconditional write:

```php
} else {
    if ($direction === BoundaryType::Lower) {
        $this->hasNextPage = false;
    } else {
        $this->hasPreviousPage = false;
    }

    $this->hasNextPage = false;      // <-- also runs for BoundaryType::Upper
}
```

For an upper-bounded page the `hasNextPage` *field* is meaningless —
`hasNextPage()` answers from the identifier — so the write looks harmless. It
is not, because `getNextPages()` optimises on the field:

```php
if ($this->result !== null && $this->hasNextPage === false) {
    return [];
}
```

So a page reached by paging *backwards* reports no next pages once its result
has been loaded, while `getNextPage()` (singular) on the same object correctly
returns one. It is order-dependent, which is the unpleasant part — a template
that renders the rows and then the pager list hits it; one that builds the
pager first does not.

**Observed** (650 items, 50 per page; page 1 reached backwards from page 2):

```
getNextPage()                              : 51..100
getNextPages(5)                            : 0 pages      (expected 5)

getNextPages(5) BEFORE iterating the page  : 5
getNextPages(5) AFTER  iterating the page  : 0
```

The equivalent hazard does not exist on `getPreviousPages()`: for a
lower-bounded page `hasPreviousPage` is left null, so its early return never
fires spuriously.

### Fix

Delete the stray line. The inner `if/else` already sets the correct field for
each direction.

---

## Verification

A walk harness over six configurations — countable × uncountable, 650 / 630 /
30 items at 50 per page — with **every hop pushed through `encode()` then
`decode()`**, asserting:

- the forward walk visits every page and sees every item exactly once
- the backward walk retraces the forward walk exactly
- the `last` cursor renders what `getLastPage()` renders, ends on the final
  item, offers no `next`, and offers a `prev` unless it is the only page
- `prev` from `last` is contiguous with it
- the `first` cursor lands on page 1 and offers no `prev`

All pass after the patch. Before it, the `last` cases fail as described in §1.

Downstream regression check: the app's own suite, 194 tests / 2997 assertions,
unaffected.

Scripts, if useful: `walk.php` (the matrix above), `repro-last.php` (§1 in
isolation), `secret.php` (§2), `nextpages.php` + `nextpages2.php` (§3).

### Suggested regression tests for the library

1. **Round-trip identity** — for each encoder, assert
   `decode(encode($id)) == $id` over a table of identifiers covering null and
   non-null `boundaryValues`, `pageNumber` and `limit`. This one test catches
   §1 and §2 together, and would have caught both at the time they were
   introduced.
2. **`[]` is normalised** — `(new KeysetPageIdentifier(0, $type, [], 1, null))->getBoundaryValues()`
   is null.
3. **Page-list consistency** — on an upper-bounded page, assert
   `getNextPages(1) == [] iff getNextPage() === null`, once before and once
   after iterating the page. That pins §3 including its order dependence.

---

## Behaviour and BC notes

- **Cursors already issued keep working.** Old cursors carrying `"v":[]`
  decode to a null boundary now, which is what they always meant. No cursor
  format change is required by the fix; the encoder change only stops new
  cursors from encoding null as `[]`.
- **No API signature changes.** `KeysetPageIdentifier::__construct()` still
  accepts `?array` — `boundaryValues` merely stops being a promoted property so
  the constructor body can normalise it.
- **One inherent behaviour, unchanged and worth documenting.** On an
  *uncountable* pageable, `getLastPage()` returns the final `itemsPerPage`
  items as an unaligned window — 581–630 rather than 601–630 on a 630-item set
  — because without a count there is no page boundary to align to. Correct, but
  it means `last` overlaps the penultimate page there, which surprises anyone
  comparing it against a forward walk.
