<?php

namespace Quantum\Tests\_root\shared\Models;

use Quantum\Model\QtModel;

class TestProfileModel extends QtModel
{

    public $idColumn = 'id';

    public $table = 'profiles';

    protected $fillable = [
        'password',
        'firstname',
        'lastname',
        'age'
    ];

    public $hidden = [
        'password'
    ];
}
