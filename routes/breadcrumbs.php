<?php

Breadcrumbs::for('processes.index', function ($trail) {
    $trail->push('Processes', route('processes.index'));
});

Breadcrumbs::for('requests.index', function ($trail) {
    $trail->push('Requests', route('requests.index'));
});

Breadcrumbs::for('requests.show', function ($trail, $request) {
    $trail->parent('requests.index');
    $trail->push("{$request->name} #{$request->id}", route('requests.show', $request));
});

Breadcrumbs::for('tasks.edit', function ($trail, $process_request_token) {
    $trail->parent('requests.show', $process_request_token->processRequest);
    $trail->push("{$process_request_token->element_name}", route('tasks.edit', $process_request_token));
});

Breadcrumbs::for('scripts.index', function ($trail) {
    $trail->parent('processes.index');
    $trail->push('Scripts', route('scripts.index'));
});

Breadcrumbs::for('scripts.edit', function ($trail, $script) {
    $trail->parent('scripts.index');
    $trail->push(
        "Edit '{$script->title}'",
        route('scripts.show', $script->id)
    );
});

Breadcrumbs::for('scripts.builder', function ($trail, $script) {
    $trail->parent('scripts.edit', $script);
    $trail->push(
        "Build '{$script->title}'",
        route('scripts.show', $script->id)
    );
});