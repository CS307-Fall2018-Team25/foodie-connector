<?php

namespace Tests\Feature\Address;

use App\Http\Controllers\AddressController;
use App\Models\Address;
use App\Models\ApiUser;
use Tests\ApiTestCase;

class UpdateAddressTest extends ApiTestCase
{
    /**
     * Current address id
     *
     * @param int
     */
    protected $id = 0;

    /**
     * Test updating address
     *
     * @return void
     */
    public function testUpdateAddress()
    {
        $this->assertFailed(null, 401);
        $this->login(factory(ApiUser::class)->create());
        $address = factory(Address::class)->create();
        $this->id = $address->id;
        $addressArray = $address->toArray();
        $addressArray['name'] = 'Changed Name';
        unset($addressArray['api_user_id']);
        $response = $this->assertSucceed([
            'name' => $addressArray['name'],
            'is_default' => true,
        ]);
        $addressArray['is_default'] = true;
        $response->assertJson($addressArray);
        $newAddress = factory(Address::class)->create();
        $this->id = $newAddress->id;
        $newAddressArray = $newAddress->toArray();
        $newAddressArray['is_default'] = true;
        $this->assertSucceed([
            'is_default' => true,
        ], false)
            ->assertJson($newAddressArray);
        $this->assertFailed([
            'phone' => 'invalid_phone',
        ], 422);
        $this->id = 0;
        $this->assertFailed([
            'name' => $addressArray['name'],
        ], 404);
    }

    protected function method()
    {
        return 'PUT';
    }

    protected function uri()
    {
        return '/addresses/{id}';
    }

    protected function uriParams()
    {
        return [
            'id' => $this->id,
        ];
    }

    protected function summary()
    {
        return 'Updating an existed address';
    }

    protected function tag()
    {
        return 'address';
    }

    protected function rules()
    {
        return AddressController::rules();
    }
}
