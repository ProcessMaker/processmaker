# Tasks Page ETag Sequence

This diagram captures the current request flow for the Tasks page shell (`/tasks`). The route-specific middleware computes a stable Tasks page ETag before rendering, short-circuits matching conditional requests with `304 Not Modified`, and sets private revalidation headers. The legacy inbox route (`/inbox/{router?}`) remains on the original `no-cache` middleware path and does not use this ETag flow. The global browser-cache middleware preserves ETag-enabled responses instead of applying `no-store`.

![Tasks page ETag sequence](tasks-page-etag-sequence.svg)

```mermaid
sequenceDiagram
    autonumber
    participant Browser as Browser
    participant BrowserCache as BrowserCache<br/>ProcessMaker\Http\Middleware\BrowserCache
    participant Router as Laravel Router
    participant TasksPageEtagMw as TasksPageEtag middleware<br/>ProcessMaker\Http\Middleware\Etag\TasksPageEtag
    participant Payload as TasksPageEtag payload<br/>ProcessMaker\Http\Resources\Caching\TasksPageEtag
    participant SymfonyResponse as Response<br/>Symfony\Component\HttpFoundation\Response
    participant Controller as TaskController@index<br/>ProcessMaker\Http\Controllers\TaskController

    Browser->>BrowserCache: GET /tasks<br/>Cookie + optional If-None-Match
    BrowserCache->>Router: pass request through global middleware stack
    Router->>TasksPageEtagMw: dispatch route with tasks-page-etag middleware

    alt ETags disabled or method is not GET/HEAD
        TasksPageEtagMw->>Controller: bypass ETag logic
        Controller-->>TasksPageEtagMw: normal page response
        TasksPageEtagMw-->>BrowserCache: response without Tasks page ETag handling
    else ETags enabled and method is GET/HEAD
        TasksPageEtagMw->>Payload: getEtag(request)
        Payload->>Payload: collect route name/path/router/query
        Payload->>Payload: collect user and tenant version markers
        Payload->>Payload: collect permission table/session/direct assignment markers
        Payload->>Payload: collect saved search id/updated_at/columns hash
        Payload->>Payload: collect user config hash and task drafts flag
        Payload->>Payload: collect feature config, package list, manifest, asset versions
        Payload->>Payload: exclude CSRF, session id, randomized favicon URL
        Payload-->>TasksPageEtagMw: quoted stable hash
        TasksPageEtagMw->>SymfonyResponse: create empty response + set weak ETag
        TasksPageEtagMw->>SymfonyResponse: isNotModified(request)

        alt If-None-Match matches weak ETag
            SymfonyResponse-->>TasksPageEtagMw: true
            TasksPageEtagMw->>SymfonyResponse: build 304 response with same weak ETag
            TasksPageEtagMw->>TasksPageEtagMw: Cache-Control: private, must-revalidate
            TasksPageEtagMw->>TasksPageEtagMw: remove Pragma and Expires
            TasksPageEtagMw-->>BrowserCache: 304 Not Modified
        else Missing/stale If-None-Match
            SymfonyResponse-->>TasksPageEtagMw: false
            TasksPageEtagMw->>Controller: render Tasks page shell
            Controller->>Controller: resolve title, router mode, mobile check
            Controller->>Controller: load ScreenBuilderManager scripts
            Controller->>Controller: load task filter, default columns, drafts flag
            Controller->>Controller: load user configuration and default saved search
            Controller-->>TasksPageEtagMw: tasks.index response
            TasksPageEtagMw->>TasksPageEtagMw: attach weak ETag to 200 response
            TasksPageEtagMw->>TasksPageEtagMw: Cache-Control: private, must-revalidate
            TasksPageEtagMw->>TasksPageEtagMw: remove Pragma and Expires
            TasksPageEtagMw-->>BrowserCache: 200 OK with weak ETag
        end
    end

    alt Response has ETag
        BrowserCache->>BrowserCache: preserve response headers
        BrowserCache->>BrowserCache: skip no-store / Pragma override
    else No ETag and BROWSER_CACHE=false
        BrowserCache->>BrowserCache: add Pragma: no-cache
        BrowserCache->>BrowserCache: add Cache-Control: no-store
    end

    BrowserCache-->>Browser: 200 OK + ETag or 304 Not Modified
    Browser->>Browser: Store private validator and send If-None-Match on later reload
```

## ETag Context

The payload intentionally includes content-affecting values:

- Route path/query/router state.
- Authenticated user id, update timestamp, locale, timezone, display fields, and admin status.
- Tenant id and tenant update timestamp when present.
- Permission table version, session permission snapshot, direct user/group assignment hashes, and direct group membership version.
- Task filter cache, user configuration hash, task draft flag, and saved search defaults hash.
- Page-relevant feature config, registered package list, package manifest, app version, `composer.lock`, and `mix-manifest.json`.

The payload intentionally excludes volatile values that do not define the rendered page, such as CSRF token, session id, and randomized favicon URLs.

## Header Outcome

Successful Tasks page responses should use private revalidation rather than storage blocking:

```http
Cache-Control: private, must-revalidate
ETag: W/"..."
Vary: Accept-Encoding
```

They should not include `no-store` or `Pragma: no-cache`; otherwise the browser will not keep the validator and will not send `If-None-Match`.
