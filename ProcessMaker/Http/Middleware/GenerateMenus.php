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
            $execute = $menu;
            $tasks = $execute->add('Tasks',['class' => 'sidebar-header'])
            ->prepend('<i class="fa fa-list-ul"></i> ');
            $tasks->add('Pending',['route'  => 'home', 'id' => 'home']);
            $tasks->add('Unclaimed',['route'  => 'home', 'id' => 'home']);
            $tasks->add('Completed',['route'  => 'home', 'id' => 'home']);

            $cases = $execute->add('Cases',['class' => 'sidebar-header'])
            ->prepend('<i class="fa fa-briefcase"></i> ');
            $cases->add('New');
            $cases->add('Drafts');
            $cases->add('Mine');
            $cases->add('Find');
            //
            // $build = $menu->raw('Build',['class' => 'sidebar-header'])
            // ->prepend('<i class="fa fa-wrench"></i> ');
            // $app = $build->add('Application');
            // $app->add('Processes');
            // $app->add('Tables');
            // $app->add('Forms');
            // $app->add('Scripts');
            //
            // $manage = $menu->raw('Manage',['class' => 'sidebar-header'])
            // ->prepend('<i class="fa fa-list-ul"></i> ');
            // $org = $manage->add('Organization',['class' => 'sidebar-header']);
            // $org->add('Users', ['route' => 'management-users-index']);
            // $org->add('Groups');
            // $org->add('Roles');
            //
            // $appearance = $manage->add('Appearance');
            // $appearance->add('Themes');
            // $appearance->add('Logo Management');
            //
            // $manage->add('Localization and Internationalization');
            // $manage->add('Notifications');
            // $manage->add('Plugin Manager');
            // $manage->add('Logs & Audit');
        });

        return $next($request);
    }
}
