<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\TransactionModel;

class Dashboard extends BaseController
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
        // Statistik
        $totalUsers        = $this->userModel->countAllResults();
        $totalProducts     = $this->productModel->countAllResults();
        $totalTransactions = $this->transactionModel->countAllResults();
        $totalRevenue      = $this->transactionModel->getTotalRevenue();

        // Total seluruh stok barang di gudang
        $stockRow = $this->productModel->selectSum('qty_in_stock', 'total_stock')->first();
        $totalStock = $stockRow ? (int)$stockRow['total_stock'] : 0;

        // Produk dengan stok rendah (<= 5)
        $lowStockProducts = $this->productModel->getLowStockProducts(5);

        // Transaksi terbaru (5 item)
        $recentTransactions = $this->transactionModel->getTransactionsWithDetails(5);

        // Data pendukung untuk Quick Purchase Modal di Dashboard
        $allUsers    = $this->userModel->orderBy('name', 'ASC')->findAll();
        $allProducts = $this->productModel->where('qty_in_stock >', 0)->orderBy('product_name', 'ASC')->findAll();

        $data = [
            'title'              => 'Dashboard Overview',
            'activeMenu'         => 'dashboard',
            'totalUsers'         => $totalUsers,
            'totalProducts'      => $totalProducts,
            'totalStock'         => $totalStock,
            'totalTransactions'  => $totalTransactions,
            'totalRevenue'       => $totalRevenue,
            'lowStockProducts'   => $lowStockProducts,
            'recentTransactions' => $recentTransactions,
            'users'              => $allUsers,
            'products'           => $allProducts,
        ];

        return view('dashboard/index', $data);
    }
}
