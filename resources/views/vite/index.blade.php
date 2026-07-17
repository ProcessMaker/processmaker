<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vite Demo | ProcessMaker</title>
    @vite(['resources/js/vite/app.js', 'resources/css/vite/app.css'])
</head>
<body class="vite-demo-page">
    <main class="vite-demo-shell">
        <div id="vite-demo-app">
            <h1>@{{ title }}</h1>
            <p>
                Vite corre en paralelo a Laravel Mix. Esta página solo usa assets de
                <code>public/build</code> generados por Vite.
            </p>
            <p class="vite-demo-meta">
                Tick counter: <strong>@{{ ticks }}</strong>
            </p>
            <demo-badge label="Vue 2 component"></demo-badge>
        </div>

        <p class="vite-demo-meta" style="margin-top: 2rem;">
            Mix sigue disponible con <code>npm run development</code> /
            <code>npm run watch</code>. Vite usa
            <code>npm run vite:dev</code> / <code>npm run vite:build</code>.
        </p>
    </main>
</body>
</html>
