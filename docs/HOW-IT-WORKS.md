# How gemlot works

Read this before an interview. It explains the project in plain words so you can walk someone through it and answer questions.

## The idea

Sellers list wholesale gemstones. Trade buyers search the catalogue, send an inquiry (optionally with a price), and if the seller accepts, an order is created and moves through confirm, ship and complete. An admin approves new sellers.

Three roles, stored in `users.role`: **buyer**, **seller**, **admin**.

## Where things live

| Folder / file | What it does |
|---|---|
| `routes/web.php` | Website URLs, grouped by role. |
| `routes/api.php` | The same features as a token API (`/api/v1`). |
| `app/Http/Middleware/EnsureRole.php` | Allows a route only for certain roles. |
| `app/Http/Middleware/EnsureApproved.php` | Blocks sellers that the admin has not approved yet. |
| `app/Http/Controllers/` | Web controllers. `Api/V1` has the API ones, `Seller` and `Admin` have role-only pages. |
| `app/Models/Stone.php` | Stones, with the catalogue filter and sorting logic. |
| `app/Models/Order.php` | Orders, with the `ACTIONS` table that holds the status rules. |
| `app/Services/` | The "accept inquiry" logic that creates an order safely. |
| `app/Policies/` | "Is this record yours?" checks. |
| `tests/Feature/` | Tests per area: catalogue, inquiries, order workflow, seller stones, admin, API. |

## Three things that are worth understanding well

### 1. Catalogue filters

`Stone` has a query scope that takes the filter array from the URL (shape, colour, clarity, lab, carat range, price range, sort). Each filter is added only if it was sent (`when(...)`). Only stones that are *available* and belong to *approved* sellers are included.

`total_price` is saved in the database (carat x price per carat, calculated when the stone is saved). Reason: you can't sort or filter by a number that only exists in PHP, and saving it keeps those queries simple and indexable.

### 2. Seller approval

A seller can register straight away, but `EnsureApproved` stops them reaching the stock pages until an admin presses approve. Their stones are also hidden from the catalogue while they are not approved, so revoking a seller hides their stock at once.

### 3. Accepting an inquiry (the tricky part)

Two buyers can ask about the same stone. If the seller accepts one, the other must not also get the stone.

`OrderService::acceptInquiry` does this inside a **database transaction** and takes **row locks** (`lockForUpdate`) on the stone and the inquiry. While one accept is running, a second one waits. When it gets its turn it sees the stone is already on hold and fails with a message. After a successful accept the stone is put on hold, an order is created, and the other open inquiries for that stone are declined.

### Order status rules

`Order::ACTIONS` is a small table: for each action (confirm, ship, complete, cancel) it lists which statuses it can start from, where it ends, and who may do it. The policy, the pages and the API all read this table, so the rules exist in one place only.

```
pending -> confirmed -> shipped -> completed
   \________ cancel (buyer or seller) ________/  -> cancelled (stone goes back on sale)
```

## Following one request: buyer sends an inquiry

1. `POST /stones/{stone}/inquiries`
2. `auth` middleware: must be logged in. `role:buyer`: must be a buyer.
3. The controller validates the message and the optional price, and checks the stone is available.
4. An `inquiries` row is saved with status `open`.
5. The seller sees it under *Inquiries*, and can accept or decline (policy `respond`: only the stone's own seller).

## The API

Same ideas, JSON instead of pages. Login at `/api/v1/login` gives a Sanctum token. A `ForceJson` middleware makes errors come back as JSON even if the client forgot the `Accept` header. A mobile app can browse the catalogue, send inquiries and move orders along.

## Questions you may get

**How did you prevent a double sale?** Transaction plus `lockForUpdate` on the stone and inquiry. After taking the lock the code re-checks that the inquiry is still open and the stone is still available. The tests check that other inquiries are declined; a true two-connection race test would need MySQL, so be honest about that.

**Why store `total_price` instead of calculating it?** To filter and sort in SQL, and the saving hook keeps it correct.

**Why one `ACTIONS` table for orders?** One source of truth. Adding a step means editing one array, and the tests show every allowed and blocked move.

**Why MySQL and not SQLite?** The app targets MySQL. The tests use in-memory SQLite for speed, so row locks are not truly exercised there. Say this honestly if asked, and say how you would test it on MySQL (a test with two connections).

**What would you improve?** Email notifications through queues, image resizing, full-text search for stone descriptions, audit log for order changes.

## Try it yourself

Follow the README to run it. Log in as an admin, approve a seller, log in as the seller and add a stone, log in as a buyer and send an inquiry, then accept it as the seller and move the order to completed. Then read `tests/Feature/OrderWorkflowTest.php` from top to bottom.
