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

        Menu::make('topnav', function ($menu) {
            $menu->group(['prefix' => 'requests'], function ($request_items) {
                $request_items->add(__('Requests'), ['route' => 'requests.index'])->active('requests/*');
            });
            //@TODO change the index to the correct blade
            $menu->group(['prefix' => 'tasks'], function ($request_items) {
                $request_items->add(__('Tasks'), ['route' => 'tasks.index'])->active('tasks/*');
            });
            if (\Auth::check() && \Auth::user()->canAny('view-processes|view-categories|view-scripts|view-screens|view-environment_variables')) {
                $menu->group(['prefix' => 'processes'], function ($request_items) {
                    $request_items->add(__('Designer'), ['route' => 'processes.index'])->active('processes/*');
                });
            }
            if (\Auth::check() && \Auth::user()->canAny('view-users|view-groups|view-auth_clients')) {
                $menu->group(['prefix' => 'admin'], function ($admin_items) {
                    $admin_items->add(__('Admin'), ['route' => 'admin.index'])->active('admin/*');
                });
            }
        });

        // Build the menus
        Menu::make('sidebar_admin', function ($menu) {
            $submenu = $menu->add(__('admin'));
            if (\Auth::check() && \Auth::user()->can('view-users')) {
                $submenu->add(__('Users'), [
                    'route' => 'users.index',
                    'icon' => 'fa-user',
                    'id' => 'homeid'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-groups')) {
                $submenu->add(__('Groups'), [
                    'route' => 'groups.index',
                    'icon' => 'fa-users',
                    'id' => 'homeid'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-auth_clients')) {
                $submenu->add(__('Auth Clients'), [
                    'route' => 'auth-clients.index',
                    'icon' => 'fa-key',
                    'id' => 'auth-login'
                ]);
            }
            if (\Auth::check() && \Auth::user()->is_administrator) {
                $submenu->add(__('Customize UI'), [
                    'route' => 'customize-ui.edit',
                    'icon' => 'fa-palette',
                ]);
            }
            if (\Auth::check() && \Auth::user()->is_administrator) {
                $submenu->add(__('Queue Management'), [
                    'route' => 'horizon.index',
                    'icon' => 'fa-infinity',
                ]);
            }
        });
        Menu::make('sidebar_task', function ($menu) {
            $submenu = $menu->add(__('Tasks'));
            $submenu->add(__('To Do'), [
                'route' => 'tasks.index',
                'icon' => 'fa-list',
                'id' => 'homeid'
            ]);
            $submenu->add(__('Completed'), [
                'route' => ['tasks.index', 'status' => 'CLOSED'],
                'icon' => 'fa-check-square',
                'id' => 'homeid'
            ]);
        });
        Menu::make('sidebar_request', function ($menu) {
            $submenu = $menu->add(__('Request'));
            $submenu->add(__('My Requests'), [
                'route' => ['requests_by_type', ''],
                'icon' => 'fa-id-badge',
            ]);
            $submenu->add(__('In Progress'), [
                'route' => ['requests_by_type', 'in_progress'],
                'icon' => 'fa-clipboard-list',
            ]);
            $submenu->add(__('Completed'), [
                'route' => ['requests_by_type', 'completed'],
                'icon' => 'fa-clipboard-check',
            ]);
            if (\Auth::check() && \Auth::user()->can('view-all_requests')) {
                $submenu->add(__('All Requests'), [
                    'route' => ['requests_by_type', 'all'],
                    'icon' => 'fa-clipboard',
                ]);
            }
        });

        Menu::make('sidebar_processes', function ($menu) {
            $submenu = $menu->add(__('Designer'));
            if (\Auth::check() && \Auth::user()->can('view-processes')) {
                $submenu->add(__('Processes'), [
                    'route' => 'processes.index',
                    'icon' => 'fa-play-circle',
                    'id' => 'processes'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-categories')) {
                $submenu->add(__('Process Categories'), [
                    'route' => 'categories.index',
                    'icon' => 'fa-sitemap',
                    'id' => 'process-categories'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('archive-processes')) {
                $submenu->add(__('Archived Processes'), [
                    'route' => ['processes.index', 'status' => 'inactive'],
                    'icon' => 'fa-archive',
                    'id' => 'process-environment'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-scripts')) {
                $submenu->add(__('Scripts'), [
                    'route' => 'scripts.index',
                    'icon' => 'fa-code',
                    'id' => 'process-scripts'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-categories')) {
                $submenu->add(__('Script Categories'), [
                    'route' => 'script-categories.index',
                    'icon' => 'fa-sitemap',
                    'id' => 'script-categories'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-screens')) {
                $submenu->add(__('Screens'), [
                    'route' => 'screens.index',
                    'icon' => 'fa-file-alt',
                    'id' => 'process-screens'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-categories')) {
                $submenu->add(__('Screen Categories'), [
                    'route' => 'screen-categories.index',
                    'icon' => 'fa-sitemap',
                    'id' => 'screen-categories'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-environment_variables')) {
                $submenu->add(__('Environment Variables'), [
                    'route' => 'environment-variables.index',
                    'icon' => 'fa-lock',
                    'id' => 'process-environment'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-datasources')) {
                $submenu->add(__('Datasources'), [
                    'route' => 'datasources.index',
                    'icon' => 'fa-file-alt',
                    'id' => 'process-datasources'
                ]);
            }
            if (\Auth::check() && \Auth::user()->can('view-categories')) {
                $submenu->add(__('Datasource Categories'), [
                    'route' => 'datasource-categories.index',
                    'icon' => 'fa-database',
                    'id' => 'datasource-categories'
                ]);
            }
        });

        Menu::make('sidebar_designer', function ($menu) { });

        Menu::make('sidebar_about', function ($menu) {
            $submenu = $menu->add(__('About'));
            $submenu->add(__('Profile'), [
                'route' => 'profile.edit',
                'icon' => 'fa-user',
                'id' => 'dropdownItem'
            ]);
            $submenu->add(__('Documentation'), [
                'url' => 'https://processmaker.gitbook.io/processmaker',
                'icon' => 'fa-question-circle',
                'id' => 'dropdownItem',
                'target' => '_blank'
            ]);
            $submenu->add(__('Report an issue'), [
                'url' => 'https://docs.google.com/forms/d/e/1FAIpQLScnYje8uTACYwp3VxdRoA26OFkbfFs6kuXofqY-QXXsG-h9xA/viewform',
                'icon' => 'fa-bug',
                'id' => 'dropdownItem',
                'target' => '_blank'
            ]);
            $submenu->add(__('Log Out'), [
                'route' => 'logout',
                'icon' => 'fa-sign-out-alt',
                'id' => 'dropdownItem'
            ]);
        });

        Menu::make('sidebar_notifications', function ($menu) {
            $submenu = $menu->add(__('Notifications'));
            $submenu->add(__('Unread Notifications'), [
                'route' => ['notifications.index', 'status' => 'unread'],
                'icon' => 'fa-inbox',
                'id' => 'notifications-unread'
            ]);
            $submenu->add(__('All Notifications'), [
                'route' => 'notifications.index',
                'icon' => 'fa-archive',
                'id' => 'notifications-all'
            ]);
        });

        Menu::make('dropdown_nav', function ($menu) {
            $task_items = [
                [
                    'label' => __('Profile'),
                    'header' => false,
                    'route' => 'profile.edit',
                    'icon' => 'fa-user',
                    'img' => '',
                    'id' => 'dropdownItem'
                ],
                [
                    'label' => __('About'),
                    'header' => false,
                    'route' => 'about.index',
                    'icon' => 'fa-info-circle',
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
