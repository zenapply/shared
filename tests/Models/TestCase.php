<?php

namespace Zenapply\Shared\Tests\Models;

use Zenapply\Shared\Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TestCase extends BaseTestCase {

    // use DatabaseTransactions;

    protected $model = 'Zenapply\Shared\Models\Base';

    public function testCreatesAnInstance(){
        $model = $this->model;
        $i = new $model();
        $this->assertInstanceOf($this->model,$i);
    }

    public function testRandomMethod(){

        $this->populate(10);

        $model = $this->model;
        $random = $model::random();
        $this->assertNotEmpty($random);
        $this->assertInstanceOf($this->model,$random);
    }

    protected function populate($count = 1){
        return factory($this->model, $count)->create();
    }
}