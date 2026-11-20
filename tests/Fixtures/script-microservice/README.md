# Script microservice error contracts

These sanitized fixtures preserve the response structures observed during FOUR-34463
validation. Identifiers and executor diagnostics are illustrative; no credentials or
request documents are included.

- API 1.0.0: the generic execution message is in `message`, with diagnostics in `output`.
- API 1.2.1 (`script-executor-service:1.2.1-develop`): the generic execution message is
  in `error_message`, with diagnostics in `error`. The actionable cause is `error.error`.

`ScriptMicroserviceErrorContractTest` verifies that both formats expose the actionable
cause while preserving sanitized diagnostics in application logs. An actionable top-level
message retains precedence. An exit-status message is a fallback when no cause is available.
Exit codes 1 and 255 follow the same policy. API versions and executor image versions are
separate; response interpretation must not depend on an executor image tag.

Text traces are deliberately omitted from diagnostic logs because positional arguments
can contain document content or unlabeled secrets. Only structured frames with scalar
`file`, `line`, `class`, `function`, and `type` fields are retained; arguments, objects,
text entries, and unknown trace formats are excluded. The fixtures retain text traces
to verify this omission for both response contracts.

Future microservice upgrades should validate these contracts and add fixtures for changed
responses. A valid error JSON response alone does not ensure compatible message selection.
