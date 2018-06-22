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

        Menu::make('topnav', function ($menu) {
            $menu->add(__('Requests'), ['route' => 'request']);
            $menu->add(__('Tasks'), ['route' => 'task']);
            $menu->add(__('Processes'), ['route' => 'process']);
            $menu->add(__('Admin'), ['route' => 'admin']);
        });

        // Build the menus
        Menu::make('sidebar_admin', function ($menu) {
            $submenu = $menu->add(__('menus.sidebar_admin.organization'));
            $submenu->add(__('menus.sidebar_admin.users'), [
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.groups'), [
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.roles'), [
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.security'));
          $submenu->add(__('menus.sidebar_admin.login'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-key',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.authentication'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-user-secret',
                'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.system_preferences'));
          $submenu->add(__('menus.sidebar_admin.localization'), [
                'route' => 'home',
                'icon' => 'fa-globe',
                'header' => false,
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.email_configuration'), [
                'route' => 'home',
                'icon' => 'fa-envelope',
                'header' => false,
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.notifications'), [
                'route' => 'home',
                'icon' => 'fa-bell',
                'header' => false,
                'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.apperance'));
          $submenu->add(__('menus.sidebar_admin.customize'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-cogs',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.themes'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-th',
                'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.system_information'));
          $submenu->add(__('menus.sidebar_admin.software_requirements'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-laptop',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.plugins'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-puzzle-piece',
                'id' => 'homeid'
          ]);
            $submenu = $menu->add(__('menus.sidebar_admin.tools'));
            $submenu->add(__('menus.sidebar_admin.manage_cache'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-bolt',
                'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.audit_log'), [
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-list-ul',
                'id' => 'homeid'
          ]);
        });
        Menu::make('sidebar_task', function ($menu) {
            $task_items = [
              [
                'label' => __('menus.sidebar_task.tasks'),
                'header' => true,
                'route' => '',
                'icon' => '',
                'id' => ''
              ],
            [
              'label' => __('menus.sidebar_task.assigned'),
              'header' => false,
              'route' => 'home',
              'icon' => 'icon-assigned',
              'id' => 'homeid'
            ],
            [
              'label' => __('menus.sidebar_task.unassigned'),
              'header' => false,
              'route' => 'home',
              'icon' => 'icon-unassigned',
              'id' => 'homeid'
            ],
            [
              'label' => __('menus.sidebar_task.completed'),
              'header' => false,
              'route' => 'home',
              'icon' => 'icon-completed-1',
              'id' => 'homeid'
            ],
            [
              'label' => __('menus.sidebar_task.paused'),
              'header' => false,
              'route' => 'home',
              'icon' => 'icon-paused-2',
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
        Menu::make('sidebar_request', function ($menu) {
            $task_items = [
              [
                'label' => __('menus.sidebar_request.request'),
                'header' => true,
                'route' => '',
                'icon' => '',
                'id' => ''
              ],
              [
                'label' => __('menus.sidebar_request.in_progress'),
                'header' => false,
                'route' => 'home',
                'icon' => 'fa-user',
                'id' => 'homeid'
              ],
            [
              'label' => __('menus.sidebar_request.draft'),
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
            ],
            [
              'label' => __('menus.sidebar_request.completed'),
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
            ],
            [
              'label' => __('menus.sidebar_request.paused'),
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user-plus',
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
        Menu::make('sidebar_designer', function ($menu) {});

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
