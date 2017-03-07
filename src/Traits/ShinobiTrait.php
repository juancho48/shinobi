<?php

namespace Caffeinated\Shinobi\Traits;

trait ShinobiTrait
{
    /*
    |----------------------------------------------------------------------
    | Role Trait Methods
    |----------------------------------------------------------------------
    |
    */

    /**
     * Users can have many roles.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function roles()
    {
        return $this->belongsToMany('\Caffeinated\Shinobi\Models\Role')->withPivot('role_on');
    }

    /**
     * Users can have many permissions
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function permissions()
    {
        return $this->belongsToMany('\Caffeinated\Shinobi\Models\Permission')->withPivot('permission_on');
    }

    /**
     * Get all user roles.
     *
     * @return array|null
     */
    public function getRoles()
    {
        if (!is_null($this->roles)) {
            return $this->roles->pluck('slug')->all();
        }
    }

    /**
     * Checks if the user has the given role.
     *
     * @param string $slug
     *
     * @return bool
     */
    public function isRole($slug, $on = null)
    {
        $slug = strtolower($slug);

        foreach ($this->roles as $role) {
            if ($role->slug == $slug) {
                if ($on === null) {
                    return true;
                } elseif ($role->pivot->role_on == $on) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Assigns the given role to the user.
     *
     * @param int         $roleId
     * @param string|null $on
     *
     * @return bool
     */
    public function assignRole($roleId = null, $on = null)
    {
        $roles = $this->roles;

        if ($on === null) {
            if (!$roles->contains($roleId)) {
                return $this->roles()->attach($roleId);
            }
        } else {
            if (!$roles->where('role_id', $roleId)->where('role_on', $on)->first()) {
                return $this->roles()->attach($roleId, ['role_on' => $on]);
            }
        }

        return false;
    }

    /**
     * Revokes the given role from the user.
     *
     * @param int         $roleId
     * @param string|null $on
     *
     * @return bool
     */
    public function revokeRole($roleId = '', $on = null)
    {
        if ($on === null) {
            return $this->roles()->detach($roleId);
        } else {
            return $this->roles()->newPivotStatementForId($roleId)->where('role_on', $on)->delete();
        }
    }

    /**
     * Give a user a permission
     *
     * @param int         $permissionId
     * @param string|null $on
     *
     * @return bool
     */
    public function assignPermission($permissionId, $on = null)
    {
        $permissions = $this->permissions;

        if ($on === null) {
            if (!$permissions->contains($permissionId)) {
                return $this->permissions()->attach($permissionId);
            }
        } else {
            if (!$permissions->where('permission_id', $permissionId)->where('permission_on', $on)->first()) {
                return $this->permissions()->attach($permissionId, ['permission_on' => $on]);
            }
        }

        return false;
    }

    /**
     * Revokes the given permission from the user.
     *
     * @param int $permissionId
     *
     * @return bool
     */
    public function revokePermission($permissionId = '', $on = null)
    {
        if ($on === null) {
            return $this->permissions()->detach($roleId);
        } else {
            return $this->permissions()->newPivotStatementForId($permissionId)->where('permission_on', $on)->delete();
        }
    }

    /**
     * Syncs the given role(s) with the user.
     *
     * @param array $roleIds
     *
     * @return bool
     */
    public function syncRoles(array $roleIds)
    {
        return $this->roles()->sync($roleIds);
    }

    /**
     * Revokes all roles from the user.
     *
     * @return bool
     */
    public function revokeAllRoles()
    {
        return $this->roles()->detach();
    }

    /*
    |----------------------------------------------------------------------
    | Permission Trait Methods
    |----------------------------------------------------------------------
    |
    */

    /**
     * Get all user role permissions.
     *
     * @return array|null
     */
    public function getPermissions()
    {
        $permissions = [[], []];

        foreach ($this->roles as $role) {
            $permissions[] = $role->getPermissions();
        }

        $permissions[] = $this->permissions->pluck('slug')->toArray();

        return call_user_func_array('array_merge', $permissions);
    }

    /**
     * Check if user has the given permission.
     *
     * @param string $permission
     * @param array  $arguments
     *
     * @return bool
     */
    public function can($permission, $arguments = [])
    {
        $can = false;

        foreach ($this->roles as $role) {
            if ($role->special === 'no-access') {
                return false;
            }

            if ($role->special === 'all-access') {
                return true;
            }

            if ($role->can($permission)) {
                $can = true;
            }
        }

        return $can;
    }

    /**
     * Check if user has the given permission.
     *
     * @param string      $permission
     * @param string|null $on
     *
     * @return bool
     */
    public function canOn($permission, $on = null)
    {
        $permission = strtolower($permission);

        foreach ($this->roles as $role) {
            if ($role->special === 'no-access') {
                return false;
            }

            if ($role->special === 'all-access') {
                return true;
            }

            if ($role->special === 'level-access' && $on !== null && $role->pivot->role_on == $on) {
                return true;
            }

            if ($role->can($permission) &&
                (
                    ($on !== null && $role->pivot->role_on == $on) ||
                    ($on === null && $role->pivot->role_on === null)
                )
            ) {
                return true;
            }
        }

        foreach ($this->permissions as $userPermission) {
            if ($permission == $userPermission->slug) {
                if ($on === null) {
                    return true;
                } elseif ($on == $userPermission->pivot->permission_on) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has at least one of the given permissions.
     *
     * @param array $permissions
     *
     * @return bool
     */
    public function canAtLeast(array $permissions)
    {
        $can = false;

        foreach ($this->roles as $role) {
            if ($role->special === 'no-access') {
                return false;
            }

            if ($role->special === 'all-access') {
                return true;
            }

            if ($role->canAtLeast($permissions)) {
                $can = true;
            }
        }

        return $can;
    }

    /**
     * Check if user has at least one of the given permissions.
     *
     * @param array       $permissions
     * @param string|null $on
     *
     * @return bool
     */
    public function canAtLeastOn(array $permissions, $on = null)
    {
        $can = false;

        foreach ($permissions as $permission) {
            if ($this->canOn($permission, $on)) {
                return true;
            }
        }

        return $can;
    }

    /*
    |----------------------------------------------------------------------
    | Magic Methods
    |----------------------------------------------------------------------
    |
    */

    /**
     * Magic __call method to handle dynamic methods.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments = [])
    {
        // Handle isRoleslug() methods
        if (starts_with($method, 'is') and $method !== 'is') {
            $role = substr($method, 2);
            $role = str_replace('_', '.', $role);
            if (substr($role, -2) == 'On') {
                if (!array_key_exists(0, $arguments)) {
                    return false;
                }

                $role = substr($role, 0, -2);

                return $this->isRole($role, $arguments[0]);
            }

            return $this->isRole($role);
        }

        // Handle canDoSomething() methods
        if (starts_with($method, 'can') and $method !== 'can' and $method !== 'canOn' ) {
            $permission = substr($method, 3);
            $permission = str_replace('_', '.', $permission);

            if (substr($permission, -2) == 'On') {
                if (!array_key_exists(0, $arguments)) {
                    return false;
                }

                $permission = substr($permission, 0, -2);

                return $this->canOn($permission, $arguments[0]);
            }

            return $this->canOn($permission);
        }

        return parent::__call($method, $arguments);
    }
}
