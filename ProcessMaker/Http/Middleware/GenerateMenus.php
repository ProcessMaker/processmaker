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
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.groups'), [
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.roles'), [
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.security'));
          $submenu->add(__('menus.sidebar_admin.login'), [
                'route' => 'home',
                'icon' => 'fa-key',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.authentication'), [
                'route' => 'home',
                'icon' => 'fa-user-secret',
                'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.system_preferences'));
          $submenu->add(__('menus.sidebar_admin.localization'), [
                'route' => 'home',
                'icon' => 'fa-globe',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.email_configuration'), [
                'route' => 'home',
                'icon' => 'fa-envelope',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.notifications'), [
                'route' => 'home',
                'icon' => 'fa-bell',
                'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.apperance'));
          $submenu->add(__('menus.sidebar_admin.customize'), [
                'route' => 'home',
                'icon' => 'fa-cogs',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.themes'), [
                'route' => 'home',
                'icon' => 'fa-th',
                'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.system_information'));
          $submenu->add(__('menus.sidebar_admin.software_requirements'), [
                'route' => 'home',
                'icon' => 'fa-laptop',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.plugins'), [
                'route' => 'home',
                'icon' => 'fa-puzzle-piece',
                'id' => 'homeid'
          ]);
            $submenu = $menu->add(__('menus.sidebar_admin.tools'));
            $submenu->add(__('menus.sidebar_admin.manage_cache'), [
                'route' => 'home',
                'icon' => 'fa-bolt',
                'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.audit_log'), [
                'route' => 'home',
                'icon' => 'fa-list-ul',
                'id' => 'homeid'
          ]);
        });
        Menu::make('sidebar_task', function ($menu) {
          $submenu = $menu->add(__('menus.sidebar_task.tasks'));
          $submenu->add(__('menus.sidebar_task.assigned'), [
              'route' => 'home',
              'icon' => 'icon-assigned',
              'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_task.unassigned'), [
              'route' => 'home',
              'icon' => 'icon-unassigned',
              'id' => 'homeid'
            ]);
          $submenu->add(__('menus.sidebar_task.completed'), [
              'route' => 'home',
              'icon' => 'icon-completed-1',
              'id' => 'homeid'
            ]);
          $submenu->add(__('menus.sidebar_task.paused'), [
              'route' => 'home',
              'icon' => 'icon-paused-2',
              'id' => 'homeid'
            ]);
        });
        Menu::make('sidebar_request', function ($menu) {
          $submenu = $menu->add(__('menus.sidebar_request.request'));
          $submenu->add(__('menus.sidebar_request.in_progress'), [
                'route' => 'home',
                'icon' => 'fa-user',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_request.draft'), [
              'route' => 'home',
              'icon' => 'fa-user',
              'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_request.completed'), [
              'route' => 'home',
              'icon' => 'fa-users',
              'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_request.paused'), [
              'header' => false,
              'route' => 'home',
              'icon' => 'fa-user-plus',
              'id' => 'homeid'
          ]);
       });
        Menu::make('sidebar_designer', function ($menu) {});

        Menu::make('dropdown_nav', function ($menu) {
          $task_items = [
          [
            'label' =>__('Profile'),
            'header' => false,
            'route' => 'home',
            'icon' => 'fa-user',
            'img' => '',
            'id' => 'dropdownItem'
          ],
          [
            'label' => __('Help'),
            'header' => false,
            'route' => 'home',
            'icon' => 'fa-info',
            'img' => '',
            'id' => 'dropdownItem'
          ],
          [
            'label' => __('Log Out'),
            'header' => false,
            'route' => 'logout',
            'icon' => 'fa-sign-out-alt',
            'img' => '',
            'id' => 'dropdownItem'
          ],
        ];
            $tasks = $menu;
            foreach ($task_items as $item) {
                if ($item['header'] === false) {
                    $tasks->add($item['label'], ['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
                } else {
                    $tasks->add($item['label'], ['class' => 'dropdown-item drop-header']);
                }
            }
        });
        return $next($request);
    }
}
