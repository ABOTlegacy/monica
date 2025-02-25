<?php

namespace App\Services\DavClient;

use Illuminate\Support\Arr;
use App\Services\BaseService;
use function Safe\preg_replace;
use App\Models\Account\AddressBook;
use GuzzleHttp\Client as GuzzleClient;
use App\Models\Account\AddressBookSubscription;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Services\DavClient\Utils\AddressBookGetter;
use App\Services\DavClient\Utils\Dav\DavClientException;

class CreateAddressBookSubscription extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'user_id' => 'required|integer|exists:users,id',
            'base_uri' => 'required|string|url',
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Add a new Adress Book.
     *
     * @param  array  $data
     * @param  GuzzleClient|null  $httpClient
     * @return AddressBookSubscription|null
     */
    public function execute(array $data, GuzzleClient $httpClient = null): ?AddressBookSubscription
    {
        $this->validate($data);

        $addressBookData = $this->getAddressBookData($data, $httpClient);
        if (! $addressBookData) {
            throw new DavClientException(__('Could not get address book data.'));
        }

        $lastAddressBook = AddressBook::where('account_id', $data['account_id'])
            ->orderBy('id', 'desc')
            ->first();

        $lastId = 0;
        if ($lastAddressBook) {
            $lastId = intval(preg_replace('/\w+(\d+)/i', '$1', $lastAddressBook->name));
        }
        $nextAddressBookName = 'contacts'.($lastId + 1);

        $addressBook = AddressBook::create([
            'account_id' => $data['account_id'],
            'user_id' => $data['user_id'],
            'name' => $nextAddressBookName,
            'description' => $addressBookData['name'],
        ]);
        $subscription = AddressBookSubscription::create([
            'account_id' => $data['account_id'],
            'user_id' => $data['user_id'],
            'username' => $data['username'],
            'address_book_id' => $addressBook->id,
            'uri' => $addressBookData['uri'],
            'capabilities' => $addressBookData['capabilities'],
        ]);
        $subscription->password = $data['password'];
        $subscription->save();

        return $subscription;
    }

    private function getAddressBookData(array $data, ?GuzzleClient $httpClient): ?array
    {
        $client = $this->getClient($data, $httpClient);

        return app(AddressBookGetter::class)
            ->execute($client);
    }

    private function getClient(array $data, ?GuzzleClient $client): DavClient
    {
        $settings = Arr::only($data, [
            'base_uri',
            'username',
            'password',
        ]);

        return app(DavClient::class)->init($settings, $client);
    }
}
