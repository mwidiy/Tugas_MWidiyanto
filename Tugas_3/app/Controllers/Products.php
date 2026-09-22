<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\TransactionModel;

class Products extends BaseController
{
    protected $productModel;
    protected $transactionModel;

    public function __construct()
    {
        $this->productModel     = new ProductModel();
        $this->transactionModel = new TransactionModel();
    }

    public function index()
    {
        // Ambil data produk beserta jumlah terjual
        $db = \Config\Database::connect();
        $products = $db->table('products')
            ->select('products.*, COALESCE(SUM(transactions.qty), 0) as total_sold')
            ->join('transactions', 'transactions.product_id = products.product_id', 'left')
            ->groupBy('products.product_id')
            ->orderBy('products.product_id', 'DESC')
            ->get()
            ->getResultArray();

        $data = [
            'title'      => 'Manajemen Produk & Stok',
            'activeMenu' => 'products',
            'products'   => $products,
        ];

        return view('products/index', $data);
    }

    public function store()
    {
        $rules = [
            'product_name' => 'required|min_length[2]|max_length[150]',
            'qty_in_stock' => 'required|is_natural',
            'price'        => 'required|numeric|greater_than_equal_to[0]',
        ];

        $messages = [
            'product_name' => [
                'required'   => 'Nama produk wajib diisi.',
                'min_length' => 'Nama produk minimal 2 karakter.',
            ],
            'qty_in_stock' => [
                'required'   => 'Stok produk wajib diisi.',
                'is_natural' => 'Stok harus berupa angka bulat >= 0.',
            ],
            'price' => [
                'required'               => 'Harga produk wajib diisi.',
                'numeric'                => 'Harga harus berupa angka.',
                'greater_than_equal_to' => 'Harga tidak boleh minus.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            $errors = implode('<br>', $this->validator->getErrors());
            return redirect()->back()->withInput()->with('error', $errors);
        }

        $this->productModel->insert([
            'product_name' => trim($this->request->getPost('product_name')),
            'qty_in_stock' => (int)$this->request->getPost('qty_in_stock'),
            'price'        => (float)$this->request->getPost('price'),
        ]);

        return redirect()->to('/products')->with('success', 'Produk baru berhasil ditambahkan!');
    }

    public function update($id)
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            return redirect()->to('/products')->with('error', 'Produk tidak ditemukan.');
        }

        $rules = [
            'product_name' => 'required|min_length[2]|max_length[150]',
            'qty_in_stock' => 'required|is_natural',
            'price'        => 'required|numeric|greater_than_equal_to[0]',
        ];

        $messages = [
            'product_name' => [
                'required'   => 'Nama produk wajib diisi.',
                'min_length' => 'Nama produk minimal 2 karakter.',
            ],
            'qty_in_stock' => [
                'required'   => 'Stok produk wajib diisi.',
                'is_natural' => 'Stok harus berupa angka bulat >= 0.',
            ],
            'price' => [
                'required'               => 'Harga produk wajib diisi.',
                'numeric'                => 'Harga harus berupa angka.',
                'greater_than_equal_to' => 'Harga tidak boleh minus.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            $errors = implode('<br>', $this->validator->getErrors());
            return redirect()->back()->withInput()->with('error', $errors);
        }

        $this->productModel->update($id, [
            'product_name' => trim($this->request->getPost('product_name')),
            'qty_in_stock' => (int)$this->request->getPost('qty_in_stock'),
            'price'        => (float)$this->request->getPost('price'),
        ]);

        return redirect()->to('/products')->with('success', 'Data produk berhasil diperbarui!');
    }

    public function delete($id)
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            return redirect()->to('/products')->with('error', 'Produk tidak ditemukan.');
        }

        // Cek apakah produk memiliki riwayat transaksi
        $hasTransactions = $this->transactionModel->where('product_id', $id)->countAllResults();
        if ($hasTransactions > 0) {
            return redirect()->to('/products')->with('error', 'Tidak dapat menghapus produk "' . esc($product['product_name']) . '" karena tercatat di ' . $hasTransactions . ' transaksi penjualan. Hapus transaksi terkait terlebih dahulu.');
        }

        $this->productModel->delete($id);

        return redirect()->to('/products')->with('success', 'Produk berhasil dihapus!');
    }
}
