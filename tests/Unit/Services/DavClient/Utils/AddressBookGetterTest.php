<?php

namespace Tests\Unit\Services\DavClient\Utils;

use Tests\TestCase;
use Tests\Helpers\DavTester;
use App\Services\DavClient\Utils\Dav\DavClient;
use App\Services\DavClient\Utils\AddressBookGetter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\DavClient\Utils\Dav\DavClientException;
use App\Services\DavClient\Utils\Dav\DavServerNotCompliantException;

class AddressBookGetterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_get_address_book_data()
    {
        $tester = (new DavTester())
            ->addressBookBaseUri()
            ->capabilities()
            ->displayName();
        $client = app(DavClient::class)->init([], $tester->getClient());
        $result = (new AddressBookGetter())
            ->execute($client);

        $tester->assert();
        $this->assertEquals([
            'uri' => 'https://test/dav/addressbooks/user@test.com/contacts/',
            'capabilities' => [
                'addressbookMultiget' => true,
                'addressbookQuery' => true,
                'syncCollection' => true,
                'addressData' => [
                    'content-type' => 'text/vcard',
                    'version' => '4.0',
                ],
            ],
            'name' => 'Test',
        ], $result);
    }

    /** @test */
    public function it_fails_on_server_not_compliant()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsFail();
        $client = app(DavClient::class)->init([], $tester->getClient());

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter())
            ->execute($client);
    }

    /** @test */
    public function it_fails_if_no_userprincipal()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipalEmpty();

        $client = app(DavClient::class)->init([], $tester->getClient());

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter())
            ->execute($client);
    }

    /** @test */
    public function it_fails_if_no_addressbook()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookEmpty();

        $client = app(DavClient::class)->init([], $tester->getClient());

        $this->expectException(DavServerNotCompliantException::class);
        (new AddressBookGetter())
            ->execute($client);
    }

    /** @test */
    public function it_fails_if_no_addressbook_url()
    {
        $tester = (new DavTester())
            ->serviceUrl()
            ->optionsOk()
            ->userPrincipal()
            ->addressbookHome()
            ->resourceTypeHomeOnly();
        $client = app(DavClient::class)->init([], $tester->getClient());

        $this->expectException(DavClientException::class);
        (new AddressBookGetter())
            ->execute($client);
    }
}
