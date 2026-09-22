<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\TransactionModel;

class Transactions extends BaseController
{
    protected $userModel;
    protected $productModel;
    protected $transactionModel;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->productModel     = new ProductModel();
        $this->transactionModel = new TransactionModel();
    }

    public function index()
    {
        $transactions = $this->transactionModel->getTransactionsWithDetails();
        $users        = $this->userModel->orderBy('name', 'ASC')->findAll();
        // Hanya ambil produk yang memiliki stok > 0 untuk form pembelian
        $products     = $this->productModel->orderBy('product_name', 'ASC')->findAll();

        $data = [
            'title'        => 'Data Transaksi',
            'activeMenu'   => 'transactions',
            'transactions' => $transactions,
            'users'        => $users,
            'products'     => $products,
        ];

        return view('transactions/index', $data);
    }

    /**
     * Proses Simulasi Pembelian Produk (Create Transaction)
     */
    public function store()
    {
        $rules = [
            'user_id'        => 'required|is_natural_no_zero',
            'product_id'     => 'required|is_natural_no_zero',
            'payment_method' => 'required|min_length[2]|max_length[50]',
            'qty'            => 'required|is_natural_no_zero',
        ];

        $messages = [
            'user_id' => [
                'required'           => 'Pelanggan wajib dipilih.',
                'is_natural_no_zero' => 'Pilihan pelanggan tidak valid.',
            ],
            'product_id' => [
                'required'           => 'Produk wajib dipilih.',
                'is_natural_no_zero' => 'Pilihan produk tidak valid.',
            ],
            'payment_method' => [
                'required' => 'Metode pembayaran wajib dipilih.',
            ],
            'qty' => [
                'required'           => 'Jumlah pembelian wajib diisi.',
                'is_natural_no_zero' => 'Jumlah pembelian minimal 1 item.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            $errors = implode('<br>', $this->validator->getErrors());
            return redirect()->back()->withInput()->with('error', $errors);
        }

        $userId        = (int)$this->request->getPost('user_id');
        $productId     = (int)$this->request->getPost('product_id');
        $paymentMethod = trim($this->request->getPost('payment_method'));
        $qty           = (int)$this->request->getPost('qty');

        // 1. Verifikasi User
        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Data pengguna tidak ditemukan.');
        }

        // 2. Verifikasi Produk & Ketersediaan Stok
        $product = $this->productModel->find($productId);
        if (!$product) {
            return redirect()->back()->withInput()->with('error', 'Produk tidak ditemukan.');
        }

        if ($qty > (int)$product['qty_in_stock']) {
            return redirect()->back()->withInput()->with(
                'error',
                'Stok tidak mencukupi! Pembelian: <strong>' . $qty . ' item</strong>, sedangkan stok <strong>' . esc($product['product_name']) . '</strong> yang tersedia hanya <strong>' . $product['qty_in_stock'] . ' item</strong>.'
            );
        }

        // 3. Hitung Total Harga
        $price      = (float)$product['price'];
        $totalPrice = $qty * $price;

        // 4. DB Transaction untuk konsistensi data dan pengurangan stok
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // A. Insert transaksi
            $this->transactionModel->insert([
                'user_id'        => $userId,
                'product_id'     => $productId,
                'payment_method' => $paymentMethod,
                'qty'            => $qty,
                'total_price'    => $totalPrice,
            ]);

            // B. Kurangi stok produk
            $newStock = (int)$product['qty_in_stock'] - $qty;
            $this->productModel->update($productId, [
                'qty_in_stock' => $newStock,
            ]);

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }

        $formattedTotal = 'Rp ' . number_format($totalPrice, 0, ',', '.');
        $redirectRoute = $this->request->getPost('from_dashboard') ? '/' : '/transactions';

        return redirect()->to($redirectRoute)->with(
            'success',
            "Simulasi Pembelian Berhasil! {$user['name']} membeli {$qty}x {$product['product_name']} ({$formattedTotal}) via {$paymentMethod}. Stok tersisa: {$newStock} item."
        );
    }

    /**
     * Update metode pembayaran transaksi
     */
    public function update($id)
    {
        $transaction = $this->transactionModel->find($id);
        if (!$transaction) {
            return redirect()->to('/transactions')->with('error', 'Transaksi tidak ditemukan.');
        }

        $paymentMethod = trim($this->request->getPost('payment_method') ?? '');
        if (empty($paymentMethod)) {
            return redirect()->to('/transactions')->with('error', 'Metode pembayaran tidak boleh kosong.');
        }

        $this->transactionModel->update($id, [
            'payment_method' => $paymentMethod,
        ]);

        return redirect()->to('/transactions')->with('success', 'Metode pembayaran transaksi berhasil diperbarui!');
    }

    /**
     * Hapus / Batalkan Transaksi dan kembalikan stok barang
     */
    public function delete($id)
    {
        $transaction = $this->transactionModel->find($id);
        if (!$transaction) {
            return redirect()->to('/transactions')->with('error', 'Transaksi tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // A. Kembalikan stok ke produk
            $product = $this->productModel->find($transaction['product_id']);
            if ($product) {
                $restoredStock = (int)$product['qty_in_stock'] + (int)$transaction['qty'];
                $this->productModel->update($transaction['product_id'], [
                    'qty_in_stock' => $restoredStock,
                ]);
            }

            // B. Hapus transaksi
            $this->transactionModel->delete($id);

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to('/transactions')->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }

        return redirect()->to('/transactions')->with(
            'success',
            'Transaksi #' . $id . ' berhasil dibatalkan dan stok produk (' . $transaction['qty'] . ' item) telah dikembalikan ke gudang!'
        );
    }
}
