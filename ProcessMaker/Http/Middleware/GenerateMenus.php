<?php
namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lavary\Menu\Facade as Menu;

class GenerateMenus
{

    /**
     * Generate the core menus that are used in web requests for our application
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Build the menu
        Menu::make('main', function ($menu) {
            $execute = $menu->raw('Execute')->data([
                'icon' => 'glyphicon glyphicon-briefcase'
            ]);
            $tasks = $execute->add('Tasks');
            $tasks->add('Pending');
            $tasks->add('Unclaimed');
            $tasks->add('Completed');

            $cases = $execute->add('Cases');
            $cases->add('New');
            $cases->add('Drafts');
            $cases->add('Mine');
            $cases->add('Find');

            $build = $menu->raw('Build')->data([
                'icon' => 'glyphicon glyphicon-wrench'
            ]);
            $app = $build->add('Application');
            $app->add('Processes');
            $app->add('Tables');
            $app->add('Forms');
            $app->add('Scripts');

            $manage = $menu->raw('Manage')->data([
               'icon' => 'glyphicon glyphicon-cog'
            ]);
            $org = $manage->add('Organization');
            $org->add('Users', ['route' => 'management-users-index']);
            $org->add('Groups');
            $org->add('Roles');

            $appearance = $manage->add('Appearance');
            $appearance->add('Themes');
            $appearance->add('Logo Management');

            $manage->add('Localization and Internationalization');
            $manage->add('Notifications');
            $manage->add('Plugin Manager');
            $manage->add('Logs & Audit');
        });

        return $next($request);
    }
}
