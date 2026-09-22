<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[150]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Nama pengguna wajib diisi.',
            'min_length' => 'Nama pengguna minimal 2 karakter.',
            'max_length' => 'Nama pengguna maksimal 150 karakter.',
        ],
    ];
}
