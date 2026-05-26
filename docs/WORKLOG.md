# Work Log

This file records project work done by Codex so changes can be reviewed later.

## 2026-05-26 - Document cart flow

### User request

- "i want document everything you do and you done"
- "do it"

### Inspected

- `README.md` to find the existing project documentation structure.
- `Users/cart.php` to understand the cart page, drawer mode, POST actions, checkout redirect, and cart-count message behavior.
- `Users/includes/shop_backend.php` to confirm cart storage, quantity normalization, stock validation, product lookup, login helper, and money/output helper functions.
- `Templates/header.php` to confirm the cart drawer iframe, badge update, and `open_cart` behavior.
- `Users/shop.php` to confirm products are added to the cart with `shop_cart_add_item()`.
- `Users/checkout.php`, `Users/payment.php`, `Users/profile.php`, and related files were identified while mapping cart and checkout references.

### Changed

- Added a `Legacy PHP Cart Flow` section to `README.md`.
- Created this `docs/WORKLOG.md` file to record what was done.

### Why

- The cart behavior is spread across the cart page, backend helper functions, and the shared header drawer. Central documentation makes it easier to understand or modify without tracing every file again.

### Verification

- No runtime code was changed.
- Documentation was written from local source inspection.
- Git status was checked before editing and showed no existing tracked changes.

### Files modified

- `README.md`
- `docs/WORKLOG.md`
