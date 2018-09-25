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
            // $menu->add(__('Requests'), ['route' => 'requests']);
            // $menu->add(__('Tasks'), ['route' => 'tasks']);
            // $menu->add(__('Processes'), ['route' => 'processes']);
            $menu->group(['prefix' => 'admin'], function($admin_items) {
                $admin_items->add(__('Admin'), ['route' => 'users.index']);
            });
        });

        // Build the menus
        Menu::make('sidebar_admin', function ($menu) {
        //     $submenu = $menu->add(__('menus.sidebar_admin.organization'));
        //     $submenu->add(__('menus.sidebar_admin.users'), [
        //       'route' => 'management-users-index',
        //       'icon' => 'fa-user',
        //       'id' => 'homeid'
        //   ]);
        //     $submenu->add(__('menus.sidebar_admin.groups'), [
        //       'route' => 'management-groups-index',
        //       'icon' => 'fa-users',
        //       'id' => 'homeid'
        //   ]);

        //   $submenu = $menu->add(__('menus.sidebar_admin.configuration'));
        //   $submenu->add(__('menus.sidebar_admin.notifications_configuration'), [
        //         'route' => 'home',
        //         'icon' => 'fa-bell',
        //         'id' => 'homeid'
        //   ]);
        //   $submenu->add(__('menus.sidebar_admin.environment_variables'), [
        //         'route' => 'management-environment-variables',
        //         'icon' => 'fa-globe',
        //   ]);
        //   $submenu->add(__('menus.sidebar_admin.preferences'), [
        //         'route' => 'preferences',
        //         'icon' => 'fa-globe',
        //         'id' => 'homeid'
        //   ]);
          
        //   $submenu->add(__('menus.sidebar_admin.colors_and_logos'), [
        //         'route' => 'home',
        //         'icon' => 'fa-palette',
        //         'id' => 'homeid'
        //   ]);

        //   $submenu = $menu->add(__('menus.sidebar_admin.system_information'));
        //   $submenu->add(__('menus.sidebar_admin.app_version_details'), [
        //         'route' => 'home',
        //         'icon' => 'fa-desktop',
        //         'id' => 'homeid'
        //   ]);
        //   $submenu->add(__('menus.sidebar_admin.queue_management'), [
        //         'route' => 'horizon.index',
        //         'icon' => 'fa-infinity',
        //   ]);

        });
        Menu::make('sidebar_task', function ($menu) {
        //   $submenu = $menu->add(__('menus.sidebar_task.tasks'));
        //   $submenu->add(__('menus.sidebar_task.assigned'), [
        //       'route' => 'home',
        //       'icon' => 'icon-assigned',
        //       'id' => 'homeid'
        //   ]);
        //   $submenu->add(__('menus.sidebar_task.unassigned'), [
        //       'route' => 'home',
        //       'icon' => 'icon-unassigned',
        //       'id' => 'homeid'
        //     ]);
        //   $submenu->add(__('menus.sidebar_task.completed'), [
        //       'route' => 'home',
        //       'icon' => 'icon-completed-1',
        //       'id' => 'homeid'
        //     ]);
        //   $submenu->add(__('menus.sidebar_task.paused'), [
        //       'route' => 'home',
        //       'icon' => 'icon-paused-2',
        //       'id' => 'homeid'
        //     ]);
        });
        Menu::make('sidebar_request', function ($menu) {
        //   $submenu = $menu->add(__('menus.sidebar_request.request'));
        //   $submenu->add(__('menus.sidebar_request.in_progress'), [
        //         'route' => 'requests',
        //         'icon' => 'icon-assigned',
        //         'id' => 'homeid'
        //   ]);
        //   $submenu->add(__('menus.sidebar_request.draft'), [
        //       'route' => 'requests.drafts',
        //       'icon' => 'icon-draft',
        //       'id' => 'homeid'
        //   ]);
        //   $submenu->add(__('menus.sidebar_request.completed'), [
        //       'route' => 'requests.completed',
        //       'icon' => 'icon-completed-1',
        //       'id' => 'homeid'
        //   ]);
        //   $submenu->add(__('menus.sidebar_request.paused'), [
        //       'route' => 'requests.paused',
        //       'icon' => 'icon-paused-2',
        //       'id' => 'homeid'
        //   ]);
       });

        Menu::make('sidebar_processes', function ($menu) {
        //   $submenu = $menu->add(__('menus.sidebar_processes.processes'));
        //   $submenu->add(__('menus.sidebar_processes.processes'), [
        //       'route' => 'processes',
        //       'icon' => 'fa-play-circle',
        //       'id' => 'processes'
        //   ]);
        //   $submenu->add(__('menus.sidebar_processes.categories'), [
        //       'route' => 'process-categories-index',
        //       'icon' => 'fa-list',
        //       'id' => 'process-categories'
        //   ]);
        });

        Menu::make('sidebar_designer', function ($menu) {});

        Menu::make('dropdown_nav', function ($menu) {
        //   $task_items = [
        //   [
        //     'label' =>__('Profile'),
        //     'header' => false,
        //     'route' => 'profile',
        //     'icon' => 'fa-user',
        //     'img' => '',
        //     'id' => 'dropdownItem'
        //   ],
        //   [
        //     'label' => __('Help'),
        //     'header' => false,
        //     'route' => 'home',
        //     'icon' => 'fa-info',
        //     'img' => '',
        //     'id' => 'dropdownItem'
        //   ],
        //   [
        //     'label' => __('Log Out'),
        //     'header' => false,
        //     'route' => 'logout',
        //     'icon' => 'fa-sign-out-alt',
        //     'img' => '',
        //     'id' => 'dropdownItem'
        //   ],
        // // ];
        //     $tasks = $menu;
        //     foreach ($task_items as $item) {
        //         if ($item['header'] === false) {
        //             $tasks->add($item['label'], ['route'  => $item['route'], 'id' => $item['id'], 'icon' => $item['icon']]);
        //         } else {
        //             $tasks->add($item['label'], ['class' => 'dropdown-item drop-header']);
        //         }
        //     }
        });
        return $next($request);
    }
}
