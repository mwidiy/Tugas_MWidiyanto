<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Hapus semua data lama sebelum insert fresh seed
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->table('transactions')->truncate();
        $this->db->table('products')->truncate();
        $this->db->table('users')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        // 1. Data Dummy Users
        $users = [
            ['name' => 'Budi Pratama',     'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Siti Nurhaliza',   'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ahmad Rizky',      'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dewi Anggraini',   'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fajar Hidayat',    'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('users')->insertBatch($users);

        // 2. Data Dummy Products
        $products = [
            [
                'product_name' => 'Laptop ASUS VivoBook 14',
                'qty_in_stock' => 8,
                'price'        => 8500000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_name' => 'Logitech G102 Gaming Mouse',
                'qty_in_stock' => 25,
                'price'        => 260000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_name' => 'Keychron K2 Mechanical Keyboard',
                'qty_in_stock' => 12,
                'price'        => 1150000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_name' => 'Monitor LG 24 Inch IPS 100Hz',
                'qty_in_stock' => 7,
                'price'        => 1650000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_name' => 'Headset HyperX Cloud II',
                'qty_in_stock' => 15,
                'price'        => 980000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_name' => 'Flashdisk SanDisk 64GB USB 3.0',
                'qty_in_stock' => 50,
                'price'        => 85000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'product_name' => 'Webcam Logitech C920 Pro HD',
                'qty_in_stock' => 10,
                'price'        => 1100000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];
        $this->db->table('products')->insertBatch($products);

        // 3. Data Dummy Transactions
        $transactions = [
            [
                'user_id'        => 1,
                'product_id'     => 2,
                'payment_method' => 'QRIS',
                'qty'            => 2,
                'total_price'    => 520000.00,
                'created_at'     => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at'     => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'user_id'        => 2,
                'product_id'     => 1,
                'payment_method' => 'Transfer Bank BCA',
                'qty'            => 1,
                'total_price'    => 8500000.00,
                'created_at'     => date('Y-m-d H:i:s', strtotime('-1 days')),
                'updated_at'     => date('Y-m-d H:i:s', strtotime('-1 days')),
            ],
            [
                'user_id'        => 3,
                'product_id'     => 3,
                'payment_method' => 'E-Wallet GoPay',
                'qty'            => 1,
                'total_price'    => 1150000.00,
                'created_at'     => date('Y-m-d H:i:s', strtotime('-12 hours')),
                'updated_at'     => date('Y-m-d H:i:s', strtotime('-12 hours')),
            ],
            [
                'user_id'        => 4,
                'product_id'     => 6,
                'payment_method' => 'Cash / Tunai',
                'qty'            => 3,
                'total_price'    => 255000.00,
                'created_at'     => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'updated_at'     => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
        ];
        $this->db->table('transactions')->insertBatch($transactions);
    }
}
