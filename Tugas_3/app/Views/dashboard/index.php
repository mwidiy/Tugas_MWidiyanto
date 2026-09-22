<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Stat Cards Row -->
<div class="row g-3 mb-4">
    <!-- Total Pelanggan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-medium" style="font-size: 0.85rem;">Total Pelanggan</span>
                    <h3 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($totalUsers) ?></h3>
                    <small class="text-primary mt-1 d-inline-block">
                        <i class="bi bi-people-fill me-1"></i> Terdaftar di sistem
                    </small>
                </div>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Produk & Stok -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-medium" style="font-size: 0.85rem;">Total Produk & Stok</span>
                    <h3 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($totalProducts) ?> <span class="fs-6 fw-normal text-muted">item</span></h3>
                    <small class="text-info mt-1 d-inline-block">
                        <i class="bi bi-boxes me-1"></i> <?= number_format($totalStock) ?> unit stok gudang
                    </small>
                </div>
                <div class="stat-icon-wrapper bg-info-subtle text-info">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Transaksi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-medium" style="font-size: 0.85rem;">Total Transaksi</span>
                    <h3 class="fw-bold mb-0 mt-1 text-dark"><?= number_format($totalTransactions) ?></h3>
                    <small class="text-success mt-1 d-inline-block">
                        <i class="bi bi-cart-check me-1"></i> Pembelian berhasil
                    </small>
                </div>
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Pendapatan / Revenue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-medium" style="font-size: 0.85rem;">Total Pendapatan</span>
                    <h4 class="fw-bold mb-0 mt-1 text-dark" style="font-size: 1.35rem;">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></h4>
                    <small class="text-warning mt-1 d-inline-block">
                        <i class="bi bi-cash-coin me-1"></i> Akumulasi omset
                    </small>
                </div>
                <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row g-4">
    <!-- Kolom Kiri: Riwayat Transaksi Terbaru -->
    <div class="col-12 col-lg-8">
        <div class="card card-custom h-100">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary fs-5"></i>
                    <h6 class="mb-0 fw-bold">Transaksi Pembelian Terbaru</h6>
                </div>
                <a href="<?= base_url('/transactions') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Total Bayar</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTransactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                        Belum ada data transaksi. Lakukan simulasi pembelian pertama!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-secondary border">#<?= $item['transaction_id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= esc($item['user_name'] ?? 'User Dihapus') ?></div>
                                            <small class="text-muted"><?= date('d M Y H:i', strtotime($item['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= esc($item['product_name'] ?? 'Produk Dihapus') ?></div>
                                            <small class="text-muted">@ Rp <?= number_format((float)($item['product_price'] ?? 0), 0, ',', '.') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark"><?= $item['qty'] ?> item</span>
                                        </td>
                                        <td>
                                            <strong class="text-success">Rp <?= number_format((float)$item['total_price'], 0, ',', '.') ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle badge-pill">
                                                <?= esc($item['payment_method']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Status Stok & Shortcut Simulasi -->
    <div class="col-12 col-lg-4">
        <div class="d-flex flex-column gap-4">
            <!-- Shortcut Simulasi Beli -->
            <div class="card card-custom p-4 bg-primary text-white" style="background: linear-gradient(135deg, #4338ca 0%, #3b82f6 100%);">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-white text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-cart-plus-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-white">Simulasi Pembelian</h6>
                        <small class="text-white-50">Coba beli produk & kurangi stok</small>
                    </div>
                </div>
                <p class="small text-white-50 mb-3">
                    Fitur ini mensimulasikan pembelian produk oleh pengguna, memvalidasi stok otomatis dan mencatat pembayaran.
                </p>
                <button type="button" class="btn btn-light text-primary fw-bold w-100 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalQuickPurchase">
                    <i class="bi bi-play-circle-fill me-1"></i> Mulai Simulasi Beli
                </button>
            </div>

            <!-- Card Alert Stok Rendah -->
            <div class="card card-custom">
                <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                        <h6 class="mb-0 fw-bold">Peringatan Stok Menipis</h6>
                    </div>
                    <span class="badge bg-warning text-dark"><?= count($lowStockProducts) ?> Produk</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($lowStockProducts)): ?>
                        <div class="p-3 text-center text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                            Semua stok produk dalam kondisi aman (&gt; 5 item).
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($lowStockProducts as $p): ?>
                                <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                    <div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;"><?= esc($p['product_name']) ?></div>
                                        <small class="text-muted">Rp <?= number_format((float)$p['price'], 0, ',', '.') ?></small>
                                    </div>
                                    <div>
                                        <?php if ($p['qty_in_stock'] == 0): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Sisa <?= $p['qty_in_stock'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Quick Purchase di Dashboard -->
<div class="modal fade" id="modalQuickPurchase" tabindex="-1" aria-labelledby="modalQuickPurchaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= base_url('/transactions/store') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="from_dashboard" value="1">
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                        <h6 class="modal-title fw-bold" id="modalQuickPurchaseLabel">Form Simulasi Pembelian Produk</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Pilih Pengguna / Pelanggan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pilih Pelanggan <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Pengguna --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= esc($u['name']) ?> (ID: #<?= $u['user_id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pilih Produk -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="product_id" id="dash_product_select" class="form-select" required>
                            <option value="" disabled selected data-stock="0" data-price="0">-- Pilih Produk --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>" data-stock="<?= $p['qty_in_stock'] ?>" data-price="<?= $p['price'] ?>">
                                    <?= esc($p['product_name']) ?> (Stok: <?= $p['qty_in_stock'] ?> | Rp <?= number_format((float)$p['price'], 0, ',', '.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <!-- Qty -->
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" name="qty" id="dash_qty_input" class="form-control" min="1" value="1" required>
                            <small class="text-muted" id="dash_stock_info">Pilih produk dahulu</small>
                        </div>
                        <!-- Metode Pembayaran -->
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="QRIS" selected>QRIS</option>
                                <option value="Transfer Bank BCA">Transfer BCA</option>
                                <option value="Transfer Bank Mandiri">Transfer Mandiri</option>
                                <option value="E-Wallet GoPay">GoPay</option>
                                <option value="E-Wallet OVO">OVO</option>
                                <option value="Cash / Tunai">Cash / Tunai</option>
                            </select>
                        </div>
                    </div>

                    <!-- Subtotal Preview -->
                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between mt-3">
                        <span class="text-muted fw-medium">Perkiraan Total Bayar:</span>
                        <h5 class="mb-0 fw-bold text-primary" id="dash_total_preview">Rp 0</h5>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="dash_btn_submit">
                        <i class="bi bi-bag-check me-1"></i> Konfirmasi Pembelian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const prodSelect = document.getElementById('dash_product_select');
        const qtyInput = document.getElementById('dash_qty_input');
        const stockInfo = document.getElementById('dash_stock_info');
        const totalPreview = document.getElementById('dash_total_preview');
        const submitBtn = document.getElementById('dash_btn_submit');

        function updateCalculation() {
            const selectedOption = prodSelect.options[prodSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                totalPreview.textContent = 'Rp 0';
                stockInfo.textContent = 'Pilih produk dahulu';
                stockInfo.className = 'text-muted';
                return;
            }

            const stock = parseInt(selectedOption.getAttribute('data-stock') || '0');
            const price = parseFloat(selectedOption.getAttribute('data-price') || '0');
            const qty = parseInt(qtyInput.value || '1');

            qtyInput.max = stock;

            if (qty > stock) {
                stockInfo.textContent = `Peringatan: Stok hanya tersedia ${stock}!`;
                stockInfo.className = 'text-danger fw-bold';
                submitBtn.disabled = true;
            } else if (stock <= 0) {
                stockInfo.textContent = 'Stok barang habis!';
                stockInfo.className = 'text-danger fw-bold';
                submitBtn.disabled = true;
            } else {
                stockInfo.textContent = `Stok tersedia: ${stock} unit`;
                stockInfo.className = 'text-success fw-semibold';
                submitBtn.disabled = false;
            }

            const total = qty * price;
            totalPreview.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        prodSelect.addEventListener('change', updateCalculation);
        qtyInput.addEventListener('input', updateCalculation);
    });
</script>
<?= $this->endSection() ?>
