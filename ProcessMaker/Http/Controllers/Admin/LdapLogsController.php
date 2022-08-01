<?php

namespace ProcessMaker\Http\Controllers\Admin;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Setting;

class LdapLogsController extends Controller
{
    /**
     * Get ldap logs
     *
     * @return Factory|View
     */
    public function index()
    {
        if (Setting::notHidden()->count()) {
            return view('admin.settings.ldap-logs');
        } else {
            abort(404);
        }
    }
}
