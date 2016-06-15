<?php

$factory->define(Zenapply\Shared\Models\User::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->safeEmail,
        'first_name' => $faker->word,
        'last_name' => $faker->word,
    ];
});

$factory->define(Zenapply\Shared\Models\Company::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
        'domain' => $faker->words(3,true),
    ];
});

$factory->define(Zenapply\Shared\Models\File::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
    ];
});

$factory->define(Zenapply\Shared\Models\Image::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
    ];
});

$factory->define(Zenapply\Shared\Models\Module::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
    ];
});

$factory->define(Zenapply\Shared\Models\Permission::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
    ];
});


$factory->define(Zenapply\Shared\Models\Product::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
    ];
});

$factory->define(Zenapply\Shared\Models\Role::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->words(3,true),
    ];
});
