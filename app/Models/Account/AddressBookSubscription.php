<?php

namespace App\Models\Account;

use App\Models\User\User;
use function safe\json_decode;
use function safe\json_encode;
use Illuminate\Support\Facades\Http;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AddressBookSubscription extends Model
{
    use HasFactory;

    protected $table = 'addressbook_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'address_book_id',
        'name',
        'uri',
        'capabilities',
        'username',
        'password',
        'readonly',
        'syncToken',
        'localSyncToken',
        'frequency',
        'last_synchronized_at',
        'active',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'last_synchronized_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'readonly' => 'boolean',
        'active' => 'boolean',
        'localSyncToken' => 'integer',
    ];

    /**
     * Get the account record associated with the subscription.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the subscription.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the addressbook record associated with the subscription.
     *
     * @return BelongsTo
     */
    public function addressBook()
    {
        return $this->belongsTo(AddressBook::class);
    }

    /**
     * Get capabilities.
     *
     * @param  string  $value
     * @return array
     */
    public function getCapabilitiesAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Set capabilities.
     *
     * @param  string  $value
     * @return void
     */
    public function setCapabilitiesAttribute($value)
    {
        $this->attributes['capabilities'] = json_encode($value);
    }

    /**
     * Get password.
     *
     * @param  string  $value
     * @return string
     */
    public function getPasswordAttribute($value)
    {
        return decrypt($value);
    }

    /**
     * Set password.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = encrypt($value);
    }

    /**
     * Scope a query to only include active subscriptions.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Get a pending request.
     *
     * @return PendingRequest
     */
    public function getRequest(): PendingRequest
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->baseUrl($this->uri)
            ->withUserAgent('Monica DavClient '.config('monica.app_version'));
    }
}
