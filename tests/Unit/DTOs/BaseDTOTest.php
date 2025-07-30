<?php

use MrNewport\LaravelPlaid\DTOs\BaseDTO;

class TestDTO extends BaseDTO
{
    public string $name;
    public ?int $age;
    public ?array $tags;
    public ?TestDTO $nested;
}

it('initializes properties from constructor array', function () {
    $data = [
        'name' => 'John Doe',
        'age' => 30,
        'tags' => ['developer', 'tester'],
        'ignored_field' => 'should not be set',
    ];
    
    $dto = new TestDTO($data);
    
    expect($dto->name)->toBe('John Doe');
    expect($dto->age)->toBe(30);
    expect($dto->tags)->toBe(['developer', 'tester']);
    expect(property_exists($dto, 'ignored_field'))->toBeFalse();
});

it('converts to array correctly', function () {
    $dto = new TestDTO([
        'name' => 'John Doe',
        'age' => 30,
        'tags' => ['developer', 'tester'],
    ]);
    
    $array = $dto->toArray();
    
    expect($array)->toBe([
        'name' => 'John Doe',
        'age' => 30,
        'tags' => ['developer', 'tester'],
    ]);
});

it('handles nested DTOs in toArray', function () {
    $dto = new TestDTO([
        'name' => 'Parent',
        'nested' => new TestDTO([
            'name' => 'Child',
            'age' => 10,
        ]),
    ]);
    
    $array = $dto->toArray();
    
    expect($array)->toBe([
        'name' => 'Parent',
        'nested' => [
            'name' => 'Child',
            'age' => 10,
        ],
    ]);
});

it('handles arrays of DTOs in toArray', function () {
    $dto = new TestDTO([
        'name' => 'Parent',
        'tags' => [
            new TestDTO(['name' => 'Tag1']),
            new TestDTO(['name' => 'Tag2']),
            'regular_string',
        ],
    ]);
    
    $array = $dto->toArray();
    
    expect($array['tags'][0])->toBe(['name' => 'Tag1']);
    expect($array['tags'][1])->toBe(['name' => 'Tag2']);
    expect($array['tags'][2])->toBe('regular_string');
});

it('excludes null values from toArray', function () {
    $dto = new TestDTO([
        'name' => 'John Doe',
        'age' => null,
        'tags' => null,
    ]);
    
    $array = $dto->toArray();
    
    expect($array)->toBe(['name' => 'John Doe']);
    expect(array_key_exists('age', $array))->toBeFalse();
    expect(array_key_exists('tags', $array))->toBeFalse();
});

it('converts to JSON correctly', function () {
    $dto = new TestDTO([
        'name' => 'John Doe',
        'age' => 30,
        'tags' => ['developer', 'tester'],
    ]);
    
    $json = $dto->toJson();
    
    expect($json)->toBeJson();
    expect(json_decode($json, true))->toBe([
        'name' => 'John Doe',
        'age' => 30,
        'tags' => ['developer', 'tester'],
    ]);
});