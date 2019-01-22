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
            $menu->group(['prefix' => 'requests'], function($request_items) {
                $request_items->add(__('menus.topnav.requests'), ['route' => 'requests.index'])->active('requests/*');
            });
            //@TODO change the index to the correct blade
            $menu->group(['prefix' => 'tasks'], function($request_items) {
                $request_items->add(__('menus.topnav.tasks'), ['route' => 'tasks.index'])->active('tasks/*');
            });
            if (\Auth::check() && (\Auth::user()->can('view-processes') || \Auth::user()->can('view-categories') || \Auth::user()->can('view-scripts') || \Auth::user()->can('view-screens') || \Auth::user()->can('view-environment_variables')|| \Auth::user()->is_administrator)) {
                $menu->group(['prefix' => 'processes'], function($request_items) {
                    $request_items->add(__('menus.topnav.processes'), ['route' => 'processes.dashboard'])->active('processes/*');
                });
            }
            if (\Auth::check() && (\Auth::user()->can('view-users') || \Auth::user()->can('view-groups') || \Auth::user()->is_administrator)) {
                $menu->group(['prefix' => 'admin'], function($admin_items) {
                    $admin_items->add(__('menus.topnav.admin'), ['route' => 'users.index'])->active('admin/*');
                });
            }
        });

        // Build the menus
        Menu::make('sidebar_admin', function ($menu) {
            $submenu = $menu->add(__('menus.sidebar_admin.organization'));
            if (\Auth::check() && \Auth::user()->can('view-users')) {
                $submenu->add(__('menus.sidebar_admin.users'), [
                'route' => 'users.index',
                'icon' => 'fa-user',
                'id' => 'homeid'
                ]);
            }
            if(\Auth::check() && \Auth::user()->can('view-groups')) {
                $submenu->add(__('menus.sidebar_admin.groups'), [
                'route' => 'groups.index',
                'icon' => 'fa-users',
                'id' => 'homeid'
                ]);
            }
            if(\Auth::check() && \Auth::user()->can('view-auth-clients')) {
                $submenu->add(__('menus.sidebar_admin.auth-clients'), [
                    'route' => 'auth-clients.index',
                    'icon' => 'fa-key',
                    'id' => 'auth-login'
                ]);
            }
            if(\Auth::check() && \Auth::user()->is_administrator) {
                $submenu->add(__('menus.sidebar_admin.queue_management'), [
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
          $submenu = $menu->add(__('menus.sidebar_request.request'));
          $submenu->add(__('menus.sidebar_request.started_me'), [
              'route' => ['requests_by_type', ''],
              'icon' => 'fa-id-badge',
          ]);
          $submenu->add(__('menus.sidebar_request.in_progress'), [
                'route' => ['requests_by_type', 'in_progress'],
                'icon' => 'fa-clipboard-list',
          ]);
          $submenu->add(__('menus.sidebar_request.completed'), [
              'route' => ['requests_by_type', 'completed'],
              'icon' => 'fa-clipboard-check',
          ]);
          if (\Auth::check() && \Auth::user()->can('view-all_requests')) {
              $submenu->add(__('menus.sidebar_request.all'), [
                  'route' => 'requests.all',
                  'icon' => 'fa-clipboard',
              ]);
          }
       });

        Menu::make('sidebar_processes', function ($menu) {
          $submenu = $menu->add(__('menus.sidebar_processes.processes'));
          if(\Auth::check() && \Auth::user()->can('view-processes')) {
            $submenu->add(__('menus.sidebar_processes.processes'), [
                'route' => 'processes.index',
                'icon' => 'fa-play-circle',
                'id' => 'processes'
            ]);
          }
          if(\Auth::check() && \Auth::user()->can('view-categories')) {
              $submenu->add(__('menus.sidebar_processes.categories'), [
                  'route' => 'categories.index',
                  'icon' => 'fa-sitemap',
                  'id' => 'process-categories'
              ]);
          }
          if(\Auth::check() && \Auth::user()->can('archive-processes')) {
            $submenu->add(__('menus.sidebar_processes.archived_processes'), [
                'route' => ['processes.index', 'status' => 'deleted'],
                'icon' => 'fa-archive',
                'id' => 'process-environment'
            ]);
          }
          if(\Auth::check() && \Auth::user()->can('view-scripts')) {
              $submenu->add(__('menus.sidebar_processes.scripts'), [
                  'route' => 'scripts.index',
                  'icon' => 'fa-code',
                  'id' => 'process-scripts'
              ]);
          }
          if(\Auth::check() && \Auth::user()->can('view-screens')) {
              $submenu->add(__('menus.sidebar_processes.screens'), [
                  'route' => 'screens.index',
                  'icon' => 'fa-file-alt',
                  'id' => 'process-screens'
              ]);
          }
          if(\Auth::check() && \Auth::user()->can('view-environment_variables')) {
              $submenu->add(__('menus.sidebar_processes.environment_variables'), [
                  'route' => 'environment-variables.index',
                  'icon' => 'fa-cogs',
                  'id' => 'process-environment'
              ]);
          }

    });

        Menu::make('sidebar_designer', function ($menu) {});

        Menu::make('sidebar_notifications', function ($menu) {
            $submenu = $menu->add(__('menus.sidebar_notifications.notifications'));
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
            'label' =>__('Profile'),
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
