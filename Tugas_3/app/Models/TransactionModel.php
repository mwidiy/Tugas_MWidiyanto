<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'transaction_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'product_id',
        'payment_method',
        'qty',
        'total_price',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id'        => 'required|is_natural_no_zero',
        'product_id'     => 'required|is_natural_no_zero',
        'payment_method' => 'required|min_length[2]|max_length[50]',
        'qty'            => 'required|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required'             => 'Pelanggan / Pengguna wajib dipilih.',
            'is_natural_no_zero'   => 'Pilihan pengguna tidak valid.',
        ],
        'product_id' => [
            'required'             => 'Produk wajib dipilih.',
            'is_natural_no_zero'   => 'Pilihan produk tidak valid.',
        ],
        'payment_method' => [
            'required'             => 'Metode pembayaran wajib dipilih.',
        ],
        'qty' => [
            'required'             => 'Jumlah (qty) pembelian wajib diisi.',
            'is_natural_no_zero'   => 'Jumlah pembelian minimal 1 item.',
        ],
    ];

    /**
     * Ambil data transaksi lengkap dengan nama pengguna dan detail produk
     */
    public function getTransactionsWithDetails($limit = null)
    {
        $builder = $this->select('
            transactions.*,
            users.name as user_name,
            products.product_name,
            products.price as product_price,
            products.qty_in_stock as current_stock
        ')
        ->join('users', 'users.user_id = transactions.user_id', 'left')
        ->join('products', 'products.product_id = transactions.product_id', 'left')
        ->orderBy('transactions.created_at', 'DESC')
        ->orderBy('transactions.transaction_id', 'DESC');

        if ($limit !== null) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Ambil total pendapatan (total revenue)
     */
    public function getTotalRevenue()
    {
        $row = $this->selectSum('total_price', 'total')->first();
        return $row ? (float)$row['total'] : 0.00;
    }
}
