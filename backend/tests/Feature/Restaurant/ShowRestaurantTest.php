<?php

namespace Tests\Feature\Restaurant;

use App\Models\Restaurant;
use Tests\ApiTestCase;
use Tests\UriWithId;

class ShowRestaurantTest extends ApiTestCase
{
    use UriWithId;

    /**
     * Test showing restaurant
     *
     * @return void
     */
    public function testShowRestaurant()
    {
        $this->assertFailed(null, 401);
        $this->login();
        $this->assertFailed(null, 404);
        $restaurant = factory(Restaurant::class)->create();
        $this->id = $restaurant->id;
        $this->assertSucceed(null);
    }

    public function method()
    {
        return 'GET';
    }

    protected function uri()
    {
        return '/restaurants/{id}';
    }

    protected function summary()
    {
        return 'Get the detail of a specific restaurant';
    }

    protected function tag()
    {
        return 'restaurant';
    }

    protected function rules()
    {
        return [];
    }
}
