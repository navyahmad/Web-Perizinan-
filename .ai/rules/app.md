---
paths:
  - 'app/**/*.php'
---

# App

## Carbon diffInMinutes/diffInHours is signed, not absolute, in this project's Carbon version
`$a->diffInMinutes($b)` returns a negative value when `$b` is chronologically before `$a` (it is NOT always positive like Carbon 2's default). Always wrap with `abs()` when you only care about magnitude, e.g. computing a duration between two times. The existing convention in `PublicLeaveRequestController` already does `abs($end->diffInMinutes($start))` — follow that pattern. Forgetting `abs()` silently breaks "duration > X" validation checks (a too-long duration computes as negative and passes the check).
