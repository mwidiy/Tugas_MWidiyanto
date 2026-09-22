<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Transaksi</h4>
        <p class="text-muted mb-0 small">Catat dan kelola transaksi pembelian produk oleh pelanggan</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNewTransaction">
        <i class="bi bi-cart-plus-fill"></i>
        <span>Tambah Transaksi</span>
    </button>
</div>

<div class="card card-custom">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-receipt-cutoff text-primary fs-5"></i>
            <h6 class="mb-0 fw-bold">Daftar Transaksi (Transaction Table)</h6>
        </div>
        <span class="badge bg-light text-secondary border px-3 py-2">Total: <?= count($transactions) ?> Transaksi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Waktu Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Produk Dibeli</th>
                        <th>Harga Satuan</th>
                        <th>Qty</th>
                        <th>Total Bayar</th>
                        <th>Metode Pembayaran</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-cart-x fs-2 d-block mb-1 text-secondary"></i>
                                Belum ada riwayat transaksi. Klik "Tambah Transaksi" untuk memulai!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tr): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-secondary border">#<?= $tr['transaction_id'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= date('d M Y', strtotime($tr['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('H:i:s', strtotime($tr['created_at'])) ?> WIB</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= esc($tr['user_name'] ?? 'User #' . $tr['user_id']) ?></div>
                                    <small class="text-muted">ID User: <?= $tr['user_id'] ?></small>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= esc($tr['product_name'] ?? 'Produk #' . $tr['product_id']) ?></div>
                                    <small class="text-muted">ID Produk: <?= $tr['product_id'] ?></small>
                                </td>
                                <td>
                                    <span class="text-muted">Rp <?= number_format((float)($tr['product_price'] ?? 0), 0, ',', '.') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                        <?= $tr['qty'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success fs-6">Rp <?= number_format((float)$tr['total_price'], 0, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <?php 
                                        $method = $tr['payment_method'];
                                        $icon = 'bi-wallet2';
                                        if (stripos($method, 'QRIS') !== false) $icon = 'bi-qr-code';
                                        elseif (stripos($method, 'Bank') !== false) $icon = 'bi-bank';
                                        elseif (stripos($method, 'GoPay') !== false || stripos($method, 'OVO') !== false) $icon = 'bi-phone';
                                        elseif (stripos($method, 'Cash') !== false || stripos($method, 'Tunai') !== false) $icon = 'bi-cash';
                                    ?>
                                    <span class="badge bg-light text-dark border badge-pill d-inline-flex align-items-center gap-1">
                                        <i class="bi <?= $icon ?> text-primary"></i>
                                        <?= esc($method) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Edit Metode Pembayaran -->
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditTransaction"
                                                data-id="<?= $tr['transaction_id'] ?>"
                                                data-method="<?= esc($tr['payment_method']) ?>"
                                                title="Ubah Metode Pembayaran">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Hapus / Batalkan Transaksi -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDeleteTransaction"
                                                data-id="<?= $tr['transaction_id'] ?>"
                                                data-product="<?= esc($tr['product_name'] ?? 'Produk') ?>"
                                                data-qty="<?= $tr['qty'] ?>"
                                                title="Batalkan & Kembalikan Stok">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Tambah Transaksi -->
<div class="modal fade" id="modalNewTransaction" tabindex="-1" aria-labelledby="modalNewTransactionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= base_url('/transactions/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                        <h6 class="modal-title fw-bold" id="modalNewTransactionLabel">Tambah Transaksi Pembelian</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Pilih Pelanggan (User Table) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pelanggan (User) <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Pengguna / Pelanggan --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= esc($u['name']) ?> (ID: #<?= $u['user_id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Menghubungkan <code>user_id</code> dari tabel <code>users</code>.</small>
                    </div>

                    <!-- Pilih Produk (Product Table) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="product_id" id="trx_product_select" class="form-select" required>
                            <option value="" disabled selected data-stock="0" data-price="0">-- Pilih Produk yang Ingin Dibeli --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>" 
                                        data-stock="<?= $p['qty_in_stock'] ?>" 
                                        data-price="<?= $p['price'] ?>"
                                        <?= $p['qty_in_stock'] <= 0 ? 'disabled' : '' ?>>
                                    <?= esc($p['product_name']) ?> 
                                    (Stok: <?= $p['qty_in_stock'] ?> | Rp <?= number_format((float)$p['price'], 0, ',', '.') ?>)
                                    <?= $p['qty_in_stock'] <= 0 ? ' - [STOK HABIS]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Menghubungkan <code>product_id</code> dari tabel <code>products</code>.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <!-- Qty -->
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" name="qty" id="trx_qty_input" class="form-control" min="1" value="1" required>
                            <small class="text-muted d-block mt-1" id="trx_stock_info">Pilih produk dahulu</small>
                        </div>
                        <!-- Metode Pembayaran -->
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Metode Bayar <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="QRIS" selected>QRIS (Instant)</option>
                                <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                                <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                                <option value="E-Wallet GoPay">E-Wallet GoPay</option>
                                <option value="E-Wallet OVO">E-Wallet OVO</option>
                                <option value="Cash / Tunai">Cash / Tunai</option>
                            </select>
                        </div>
                    </div>

                    <!-- Subtotal Preview -->
                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between mt-3">
                        <div>
                            <span class="text-muted fw-medium d-block" style="font-size: 0.85rem;">Total Tagihan Pembelian:</span>
                            <small class="text-secondary" id="trx_calc_detail">0 item x Rp 0</small>
                        </div>
                        <h4 class="mb-0 fw-bold text-primary" id="trx_total_preview">Rp 0</h4>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 mt-3 mb-0 py-2 px-3 small">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Saat transaksi disimpan, <strong>stok produk akan otomatis berkurang</strong> di database.
                    </div>
                </div>

                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="trx_btn_submit">
                        <i class="bi bi-bag-check me-1"></i> Proses Pembelian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Metode Pembayaran -->
<div class="modal fade" id="modalEditTransaction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formEditTransaction" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Ubah Metode Pembayaran
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Pilih Metode Pembayaran Baru <span class="text-danger">*</span></label>
                        <select name="payment_method" id="edit_payment_method" class="form-select" required>
                            <option value="QRIS">QRIS</option>
                            <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                            <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                            <option value="E-Wallet GoPay">E-Wallet GoPay</option>
                            <option value="E-Wallet OVO">E-Wallet OVO</option>
                            <option value="Cash / Tunai">Cash / Tunai</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Transaksi -->
<div class="modal fade" id="modalDeleteTransaction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formDeleteTransaction" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4 text-center">
                    <div class="bg-danger-subtle text-danger rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-trash3-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Batalkan Transaksi?</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda ingin membatalkan transaksi ini?
                    </p>
                    <div class="alert alert-success border-0 small mt-3 text-start">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> 
                        Stok produk <strong id="delete_trx_product"></strong> sebanyak <strong id="delete_trx_qty"></strong> unit akan <strong>otomatis dikembalikan</strong> ke stok inventaris.
                    </div>
                </div>
                <div class="modal-footer bg-light border-top justify-content-center px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Ya, Batalkan & Kembalikan Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const prodSelect = document.getElementById('trx_product_select');
        const qtyInput = document.getElementById('trx_qty_input');
        const stockInfo = document.getElementById('trx_stock_info');
        const totalPreview = document.getElementById('trx_total_preview');
        const calcDetail = document.getElementById('trx_calc_detail');
        const submitBtn = document.getElementById('trx_btn_submit');

        function updateTrxCalculation() {
            const selectedOption = prodSelect.options[prodSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                totalPreview.textContent = 'Rp 0';
                calcDetail.textContent = '0 item x Rp 0';
                stockInfo.textContent = 'Pilih produk dahulu';
                stockInfo.className = 'text-muted d-block mt-1';
                return;
            }

            const stock = parseInt(selectedOption.getAttribute('data-stock') || '0');
            const price = parseFloat(selectedOption.getAttribute('data-price') || '0');
            const qty = parseInt(qtyInput.value || '1');

            qtyInput.max = stock;

            if (qty > stock) {
                stockInfo.textContent = `Peringatan: Jumlah (${qty}) melebihi stok yang tersedia (${stock})!`;
                stockInfo.className = 'text-danger fw-bold d-block mt-1';
                submitBtn.disabled = true;
            } else if (stock <= 0) {
                stockInfo.textContent = 'Stok produk ini habis!';
                stockInfo.className = 'text-danger fw-bold d-block mt-1';
                submitBtn.disabled = true;
            } else {
                stockInfo.textContent = `Stok tersedia: ${stock} unit`;
                stockInfo.className = 'text-success fw-semibold d-block mt-1';
                submitBtn.disabled = false;
            }

            const total = qty * price;
            totalPreview.textContent = 'Rp ' + total.toLocaleString('id-ID');
            calcDetail.textContent = `${qty} item x Rp ${price.toLocaleString('id-ID')}`;
        }

        prodSelect.addEventListener('change', updateTrxCalculation);
        qtyInput.addEventListener('input', updateTrxCalculation);

        // Edit Modal Event
        const modalEdit = document.getElementById('modalEditTransaction');
        modalEdit.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const method = button.getAttribute('data-method');

            document.getElementById('edit_payment_method').value = method;
            document.getElementById('formEditTransaction').action = `<?= base_url('/transactions/update') ?>/${id}`;
        });

        // Delete Modal Event
        const modalDelete = document.getElementById('modalDeleteTransaction');
        modalDelete.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const prod = button.getAttribute('data-product');
            const qty = button.getAttribute('data-qty');

            document.getElementById('delete_trx_product').textContent = prod;
            document.getElementById('delete_trx_qty').textContent = qty;
            document.getElementById('formDeleteTransaction').action = `<?= base_url('/transactions/delete') ?>/${id}`;
        });
    });
</script>
<?= $this->endSection() ?>
