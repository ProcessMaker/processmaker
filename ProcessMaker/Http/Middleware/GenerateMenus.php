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
          //set’s application’s locale
        app()->setLocale('en');


        // Build the menu
        Menu::make('main', function ($menu) {
          $task_items = [
            [
              'label' => 'Users',
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
            ],
            [
              'label' => 'Groups',
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
            ],
            [
              'label' => 'Roles',
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
            ]
          ];

            $execute = $menu;

            $tasks = $execute->add('Organization',['class' => 'h5 text-muted font-weight-light']);


            foreach ($task_items as $item) {
              $tasks->add($item['label'],['route' => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
            }

            $task_items = [
              [
                'label' => 'Login',
                'route' => 'home',
                'icon' => 'fa-key',
                'id' => 'homeid'
              ],
              [
                'label' => 'Authentication',
                'route' => 'home',
                'icon' => 'fa-user-secret',
                'id' => 'homeid'
              ],
            ];

            $tasks = $execute->add('Security',['class' => 'h5 text-muted font-weight-light']);

            foreach ($task_items as $item) {
              $tasks->add($item['label'],['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
            }

            $task_items = [
              [
                'label' => 'Localization',
                'route' => 'home',
                'icon' => 'fa-globe',
                'id' => 'homeid'
              ],
              [
                'label' => 'Email Configuration',
                'route' => 'home',
                'icon' => 'fa-envelope',
                'id' => 'homeid'
              ],
              [
                'label' => 'Notifications',
                'route' => 'home',
                'icon' => 'fa-bell',
                'id' => 'homeid'
              ],
            ];

            $tasks = $execute->add('System Prefrences',['class' => 'h5 text-muted font-weight-light']);

            foreach ($task_items as $item) {
              $tasks->add($item['label'],['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
            }

            $task_items = [
              [
                'label' => 'Custom',
                'route' => 'home',
                'icon' => 'fa-cogs',
                'id' => 'homeid'
              ],
              [
                'label' => 'Themes',
                'route' => 'home',
                'icon' => 'fa-th',
                'id' => 'homeid'
              ],
            ];

            $tasks = $execute->add('Apperence',['class' => 'h5 text-muted font-weight-light']);

            foreach ($task_items as $item) {
              $tasks->add($item['label'],['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
            }

            $task_items = [
              [
                'label' => 'Software Requirements',
                'route' => 'home',
                'icon' => 'fa-laptop',
                'id' => 'homeid'
              ],
              [
                'label' => 'Plugins',
                'route' => 'home',
                'icon' => 'fa-puzzle-piece',
                'id' => 'homeid'
              ],
            ];

            $tasks = $execute->add('System Information',['class' => 'h5 text-muted font-weight-light']);

            foreach ($task_items as $item) {
              $tasks->add($item['label'],['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
            }

            $task_items = [
              [
                'label' => 'Manage Cache',
                'route' => 'home',
                'icon' => 'fa-bolt',
                'id' => 'homeid'
              ],
              [
                'label' => 'Audit Log',
                'route' => 'home',
                'icon' => 'fa-list-ul',
                'id' => 'homeid'
              ],
            ];

            $tasks = $execute->add('Tools',['class' => 'h5 text-muted font-weight-light']);

            foreach ($task_items as $item) {
              $tasks->add($item['label'],['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
            }
        });

        Menu::make('run', function ($menu) {
            $execute = $menu;
            $tasks = $execute->add('Execute',['class' => 'h5 text-muted font-weight-light'])
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
