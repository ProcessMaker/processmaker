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
        Menu::make('sidebar_admin', function ($menu) {
            $task_items = [
            [
              'label' => 'Organization',
              'header' => true,
              'route' => '',
              'icon' => '',
              'id' => ''
            ],
            [
              'label' => 'Users',
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
            ],
            [
              'label' => 'Groups',
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
            ],
            [
              'label' => 'Roles',
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
            ],
            [
              'label' => 'Security',
              'header' => true,
              'route' => '',
              'icon' => '',
              'id' => ''
              ],
              [
                'label' => 'Login',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-key',
                'id' => 'homeid'
              ],
              [
                'label' => 'Authentication',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-user-secret',
                'id' => 'homeid'
              ],
              [
                'label' => 'System Preferences',
                'route' => '',
                'icon' => '',
                'header' => true,
                'id' => ''
              ],
              [
                'label' => 'Localization',
                'route' => 'home',
                'icon' => 'fa-globe',
                'header' => false,
                'id' => 'homeid'
              ],
              [
                'label' => 'Email Configuration',
                'route' => 'home',
                'icon' => 'fa-envelope',
                'header' => false,
                'id' => 'homeid'
              ],
              [
                'label' => 'Notifications',
                'route' => 'home',
                'icon' => 'fa-bell',
                'header' => false,
                'id' => 'homeid'
              ],
              [
                'label' => 'Appearance',
                'header' => true,
                'route' => '',
                'icon' => '',
                'id' => ''
              ],
              [
                'label' => 'Custom',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-cogs',
                'id' => 'homeid'
              ],
              [
                'label' => 'Themes',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-th',
                'id' => 'homeid'
              ],
              [
                'label' => 'System Information',
                'header' => true,
                'route' => '',
                'icon' => '',
                'id' => ''
              ],
              [
                'label' => 'Software Requirements',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-laptop',
                'id' => 'homeid'
              ],
              [
                'label' => 'Plugins',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-puzzle-piece',
                'id' => 'homeid'
              ],
              [
                'label' => 'Tools',
                'header' => true,
                'route' => '',
                'icon' => '',
                'id' => ''
              ],
              [
                'label' => 'Manage Cache',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-bolt',
                'id' => 'homeid'
              ],
              [
                'label' => 'Audit Log',
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-list-ul',
                'id' => 'homeid'
              ],
            ];

            $tasks = $menu;
            foreach ($task_items as $item) {
                if ($item['header'] === false) {
                    $tasks->add($item['label'], ['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
                } else {
                    $tasks->add($item['label'], ['class' => 'h5 text-muted font-weight-light']);
                }
            }
        });

        Menu::make('sidebar_task', function ($menu) {
            $task_items = [
            [
              'label' => 'Assigned',
              'header' => true,
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
            ],
            [
              'label' => 'Unassigned',
              'header' => true,
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
            ],
            [
              'label' => 'Completed',
              'header' => true,
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
            ],
            [
              'label' => 'Paused',
              'header' => true,
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
            ],
            [
              'label' => 'task',
              'header' => true,
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
            ]
          ];

            $tasks = $menu;
            foreach ($task_items as $item) {
                if ($item['header'] === false) {
                    $tasks->add($item['label'], ['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
                } else {
                    $tasks->add($item['label'], ['class' => 'h5 text-muted font-weight-light']);
                }
            }
        });

        Menu::make('manage', function ($menu) {
            $execute = $menu;
            $tasks = $execute->add('Manage', ['class' => 'sidebar-header'])->prepend('<i class="fa fa-list-ul"></i> ');
            $tasks->add('Pending', ['route'  => 'home', 'id' => 'home']);
            $tasks->add('Unclaimed', ['route'  => 'home', 'id' => 'home']);
            $tasks->add('Completed', ['route'  => 'home', 'id' => 'home']);

            $cases = $execute->add('Cases', ['class' => 'sidebar-header'])->prepend('<i class="fa fa-briefcase"></i> ');
            $cases->add('New');
            $cases->add('Drafts');
            $cases->add('Mine');
            $cases->add('Find');
        });

        Menu::make('build', function ($menu) {
            $execute = $menu;
            $tasks = $execute->add('Build', ['class' => 'sidebar-header'])
            ->prepend('<i class="fa fa-list-ul"></i> ');
            $tasks->add('Pending', ['route'  => 'home', 'id' => 'home']);
            $tasks->add('Unclaimed', ['route'  => 'home', 'id' => 'home']);
            $tasks->add('Completed', ['route'  => 'home', 'id' => 'home']);

            $cases = $execute->add('Cases', ['class' => 'sidebar-header'])
            ->prepend('<i class="fa fa-briefcase"></i> ');
            $cases->add('New');
            $cases->add('Drafts');
            $cases->add('Mine');
            $cases->add('Find');
        });

        return $next($request);
    }
}
