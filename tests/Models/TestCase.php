<?php

namespace Zenapply\Shared\Tests\Models;

use Zenapply\Shared\Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase {
    protected $model = 'Zenapply\Shared\Models\Base';

    public function testCreatesAnInstance(){
        $model = $this->model;
        $i = new $model();
        $this->assertInstanceOf($this->model,$i);
    }

    public function testRandomMethod(){
        $model = $this->model;
        $random = $model::random();
        $this->assertNotEmpty($random);
        $this->assertInstanceOf($this->model,$random);
    }
}