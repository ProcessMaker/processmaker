<?php

namespace ProcessMaker\Managers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\User;

class UserManager
{


    /**
     * Get a list of All Users.
     *
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(array $options): LengthAwarePaginator
    {
        // Grab pagination data
        $perPage = $options['per_page'];
        // Filter
        $filter = $options['filter'];
        // Default order by
        $orderBy = $options['order_by'];
        $orderDirection = $options['order_direction'];

        // Note, the current page is automatically handled by Laravel's pagination feature
        if ($filter) {
            $filter = '%' . $filter . '%';
            $query = User::where('firstname', 'like', $filter)
                ->orWhere('lastname', 'like', $filter)
                ->orWhere('username', 'like', $filter)
                ->orderBy($orderBy, $orderDirection);
            if ($orderBy === 'full_name') {
                // Add a calculated row of their first name
                $query = $query->select(DB::raw('users.*, CONCAT_WS(" ", firstname, lastname) as full_name'));
            } else if ($orderBy === 'role') {
                // Need to join the role table and grab the code in role and order by it
                $query = $query->leftJoin('roles', 'users.role_id', 'roles.id')
                    ->select(DB::raw('users.*, roles.code as role'));
            }
        } else {
            //default
            $query = User::orderBy($orderBy, $orderDirection);
            if ($orderBy === 'full_name') {
                // Add calculated full_name column
                $query = User::select(DB::raw('users.*, CONCAT_WS(" ", firstname, lastname) as full_name'))
                    ->orderBy($orderBy, $orderDirection);
            } else if ($orderBy === 'role') {
                // Join our role table if there is a role_id defined, and order by the role
                $query = User::leftJoin('roles', 'users.role_id', 'roles.id')
                    ->select(DB::raw('users.*, roles.code as role'))
                    ->orderBy($orderBy, $orderDirection);
            }
        }

        return $query->paginate($perPage)
            ->appends($options);
    }

    /**
     * Get url user avatar
     *
     * @param User $user
     *
     * @return string url
     */
    public function getUrlAvatar(User $user)
    {
        return $user->getAvatar();
    }

    /**
     * Update information User
     *
     * @param User $user
     * @param Request $request
     *
     * @return User
     * @throws \Throwable
     */
    public function update(User $user, Request $request): User
    {
        $data = $request->all();
        if (isset($data['avatar']) && !empty($data['avatar'])) {
            $this->uploadAvatar($user, $request);
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->fill($data);
        $user->saveOrFail();

        return $user->refresh();
    }

    /**
     * Upload file avatar
     *
     * @param User $user
     * @param Request $request
     *
     * @throws \Exception
     */
    public function uploadAvatar(User $user, Request $request)
    {
        //verify data
        $data = $request->all();
        if (preg_match('/^data:image\/(\w+);base64,/', $data['avatar'] , $type)) {
            $data = substr($data['avatar'], strpos($data['avatar'], ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' , 'svg'])) {
                throw new \Exception('invalid image type');
            }

            $data = base64_decode($data);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }

            file_put_contents("/tmp/img.{$type}", $data);

            $user->addMedia("/tmp/img.{$type}")
                ->toMediaCollection(User::COLLECTION_PROFILE, User::DISK_PROFILE);
        } else if (isset($data['avatar']) && !empty($data['avatar'])) {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $user->addMedia($request->avatar)
                ->toMediaCollection(User::COLLECTION_PROFILE, User::DISK_PROFILE);
        }
    }

}
