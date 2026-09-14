---
paths:
  - 'app/Http/Requests/**/*.php'
---

# Requests

## Guard Carbon::createFromFormat('H:i', ...) against malformed time strings in custom validation
When a `withValidator()`/`after()` closure does its own time-duration math (e.g. computing hours between two `H:i` fields), a `date_format:H:i` rule failing on that field does NOT stop the closure from running — Laravel still executes it. If the closure then calls `Carbon::createFromFormat('H:i', $value)` on a value that doesn't actually match `H:i` (letters, out-of-range digits), it throws an uncaught `InvalidArgumentException` and the request 500s instead of returning a normal validation error. Guard with a regex like `preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)` before parsing, and skip the custom check silently if it fails — the `date_format:H:i` rule already reports the error for that field.
