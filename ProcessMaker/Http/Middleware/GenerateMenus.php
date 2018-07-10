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
              'route' => 'management-users-index',
              'icon' => 'fa-user',
              'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.groups'), [
              'route' => 'management-groups-index',
              'icon' => 'fa-users',
              'id' => 'homeid'
          ]);
            $submenu->add(__('menus.sidebar_admin.roles'), [
              'route' => 'management-roles-index',
              'icon' => 'fa-key',
              'id' => 'homeid'
          ]);
          $submenu = $menu->add(__('menus.sidebar_admin.security'));
          $submenu->add(__('menus.sidebar_admin.authentication'), [
                'route' => 'home',
                'icon' => 'fa-shield-alt',
                'id' => 'homeid'
          ]);

          $submenu = $menu->add(__('menus.sidebar_admin.localization'));
          $submenu->add(__('menus.sidebar_admin.preferences'), [
                'route' => 'home',
                'icon' => 'fa-globe',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.languages'), [
                'route' => 'home',
                'icon' => 'fa-language',
                'id' => 'homeid'
          ]);

          $submenu = $menu->add(__('menus.sidebar_admin.configuration'));
          $submenu->add(__('menus.sidebar_admin.notifications_configuration'), [
                'route' => 'home',
                'icon' => 'fa-bell',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.inbox_configuration'), [
                'route' => 'home',
                'icon' => 'fa-inbox',
                'id' => 'homeid'
          ]);


          $submenu = $menu->add(__('menus.sidebar_admin.appearance'));
          $submenu->add(__('menus.sidebar_admin.inbox_customization'), [
                'route' => 'home',
                'icon' => 'fa-columns',
                'id' => 'homeid'
          ]);
          $submenu->add(__('menus.sidebar_admin.ui_customization'), [
                'route' => 'home',
                'icon' => 'fa-palette',
                'id' => 'homeid'
          ]);

          $submenu = $menu->add(__('menus.sidebar_admin.system_information'));
          $submenu->add(__('menus.sidebar_admin.app_version_details'), [
                'route' => 'home',
                'icon' => 'fa-desktop',
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
            'route' => 'profile',
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
