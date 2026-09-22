<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\TransactionModel;

class Users extends BaseController
{
    protected $userModel;
    protected $transactionModel;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->transactionModel = new TransactionModel();
    }

    public function index()
    {
        // Ambil data users beserta total transaksi yang dilakukan
        $db = \Config\Database::connect();
        $users = $db->table('users')
            ->select('users.*, COUNT(transactions.transaction_id) as total_orders')
            ->join('transactions', 'transactions.user_id = users.user_id', 'left')
            ->groupBy('users.user_id')
            ->orderBy('users.user_id', 'DESC')
            ->get()
            ->getResultArray();

        $data = [
            'title'      => 'Manajemen Pengguna / Pelanggan',
            'activeMenu' => 'users',
            'users'      => $users,
        ];

        return view('users/index', $data);
    }

    public function store()
    {
        $name = trim($this->request->getPost('name') ?? '');

        if (!$this->validate([
            'name' => 'required|min_length[2]|max_length[150]',
        ], [
            'name' => [
                'required'   => 'Nama pengguna wajib diisi.',
                'min_length' => 'Nama pengguna minimal 2 karakter.',
                'max_length' => 'Nama pengguna maksimal 150 karakter.',
            ]
        ])) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors()['name'] ?? 'Data tidak valid.');
        }

        $this->userModel->insert([
            'name' => $name,
        ]);

        return redirect()->to('/users')->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    public function update($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan.');
        }

        $name = trim($this->request->getPost('name') ?? '');

        if (!$this->validate([
            'name' => 'required|min_length[2]|max_length[150]',
        ], [
            'name' => [
                'required'   => 'Nama pengguna wajib diisi.',
                'min_length' => 'Nama pengguna minimal 2 karakter.',
                'max_length' => 'Nama pengguna maksimal 150 karakter.',
            ]
        ])) {
            return redirect()->back()->withInput()->with('error', $this->validator->getErrors()['name'] ?? 'Data tidak valid.');
        }

        $this->userModel->update($id, [
            'name' => $name,
        ]);

        return redirect()->to('/users')->with('success', 'Data pengguna berhasil diperbarui!');
    }

    public function delete($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'Pengguna tidak ditemukan.');
        }

        // Cek apakah pengguna memiliki riwayat transaksi
        $hasTransactions = $this->transactionModel->where('user_id', $id)->countAllResults();
        if ($hasTransactions > 0) {
            return redirect()->to('/users')->with('error', 'Tidak dapat menghapus pengguna "' . esc($user['name']) . '" karena memiliki ' . $hasTransactions . ' riwayat transaksi aktif. Hapus transaksi terkait terlebih dahulu.');
        }

        $this->userModel->delete($id);

        return redirect()->to('/users')->with('success', 'Pengguna berhasil dihapus!');
    }
}
