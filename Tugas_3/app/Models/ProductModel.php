<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'product_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['product_name', 'qty_in_stock', 'price'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'product_name' => 'required|min_length[2]|max_length[150]',
        'qty_in_stock' => 'required|is_natural',
        'price'        => 'required|numeric|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'product_name' => [
            'required'   => 'Nama produk wajib diisi.',
            'min_length' => 'Nama produk minimal 2 karakter.',
            'max_length' => 'Nama produk maksimal 150 karakter.',
        ],
        'qty_in_stock' => [
            'required'   => 'Jumlah stok barang wajib diisi.',
            'is_natural' => 'Jumlah stok harus berupa bilangan bulat positif atau 0.',
        ],
        'price' => [
            'required'               => 'Harga produk wajib diisi.',
            'numeric'                => 'Harga harus berupa angka.',
            'greater_than_equal_to' => 'Harga tidak boleh negatif.',
        ],
    ];

    /**
     * Mengambil produk yang stoknya menipis (<= 5)
     */
    public function getLowStockProducts($threshold = 5)
    {
        return $this->where('qty_in_stock <=', $threshold)
                    ->orderBy('qty_in_stock', 'ASC')
                    ->findAll();
    }
}
