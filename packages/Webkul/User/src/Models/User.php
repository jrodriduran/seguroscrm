<?php

namespace Webkul\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Webkul\Lead\Models\UserAgentLicense;
use Webkul\User\Contracts\User as UserContract;

class User extends Authenticatable implements UserContract
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'api_token',
        'role_id',
        'status',
        'view_permission',
        'created_by',
        'spoken_languages',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'spoken_languages' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    /**
     * Get image url for the product image.
     */
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the product image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    /**
     * Get the role that owns the user.
     */
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    /**
     * The groups that belong to the user.
     */
    public function groups()
    {
        return $this->belongsToMany(GroupProxy::modelClass(), 'user_groups');
    }

    /**
     * Get the agent licensing, certification (AHIP), and E&O records.
     */
    public function agentLicenses()
    {
        return $this->hasMany(UserAgentLicense::class, 'user_id');
    }

    /**
     * Checks if user has permission to perform certain action.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->role->permission_type == 'custom' && ! $this->role->permissions) {
            return false;
        }

        return in_array($permission, $this->role->permissions);
    }

    /**
     * Check if user speaks the given language.
     */
    public function speaksLanguage(string $language): bool
    {
        $languages = $this->spoken_languages ?? ['es', 'en'];

        if (! is_array($languages)) {
            $languages = json_decode($languages, true) ?: ['es', 'en'];
        }

        return in_array(strtolower($language), array_map('strtolower', $languages));
    }

    /**
     * Check if agent holds an active, non-expired license in the specified state.
     */
    public function isLicensedInState(string $stateCode, string $line = 'health'): bool
    {
        return $this->agentLicenses()
            ->where('state_code', strtoupper($stateCode))
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', \Carbon\Carbon::today())
            ->get()
            ->contains(function ($lic) use ($line) {
                $lines = $lic->lines_of_authority ?? [];

                if (empty($lines)) {
                    return true;
                }

                $lines = array_map('strtolower', (array) $lines);

                return in_array(strtolower($line), $lines) || in_array('aca', $lines) || in_array('health', $lines);
            });
    }
}
