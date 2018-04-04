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
        });

        Menu::make('execute', function ($menu) {
            $execute = $menu;
            $tasks = $execute->add('Execute',['class' => 'sidebar-header'])
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
        });

        Menu::make('manage', function ($menu) {
            $execute = $menu;
            $tasks = $execute->add('Manage',['class' => 'sidebar-header'])
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
        });

        Menu::make('build', function ($menu) {
            $execute = $menu;
            $tasks = $execute->add('Build',['class' => 'sidebar-header'])
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
        });

        return $next($request);
    }
}
