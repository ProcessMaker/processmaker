# ETag k6 tests

The ETag tests require a valid ProcessMaker OAuth bearer token. They do not run with a missing, `null`, `undefined`, or `FAKE_JWT` token.

Set the target URL and token through environment variables:

```bash
PM_K6_BASE_URL=https://performance2-qa.processmaker.net \
PM_K6_TOKEN='<access-token>' \
k6 run tests/k6/etag/performance-test.js
```

Use the same variables for the other tests:

```bash
k6 run tests/k6/etag/long-duration-test.js
k6 run tests/k6/etag/detect-dynamic-endpoints.js
```

Do not set an `Authorization` header outside these scripts when the token is unavailable. Test setup must fail instead of sending `Bearer null` or `Bearer undefined`.
