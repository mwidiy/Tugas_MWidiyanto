<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Produk & Inventaris</h4>
        <p class="text-muted mb-0 small">Kelola informasi produk, stok barang, dan penetapan harga</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAddProduct">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Tambah Produk</span>
    </button>
</div>

<div class="card card-custom">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-box-seam text-primary fs-5"></i>
            <h6 class="mb-0 fw-bold">Daftar Produk (Product Table)</h6>
        </div>
        <span class="badge bg-light text-secondary border px-3 py-2">Total: <?= count($products) ?> Produk</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Nama Produk</th>
                        <th>Harga Satuan</th>
                        <th>Stok Tersedia</th>
                        <th>Status Stok</th>
                        <th>Total Terjual</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-box-arrow-in-down fs-2 d-block mb-1 text-secondary"></i>
                                Belum ada produk yang tersedia. Tambahkan produk pertama!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">#<?= $prod['product_id'] ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= esc($prod['product_name']) ?></div>
                                    <small class="text-muted">ID Produk: <?= $prod['product_id'] ?></small>
                                </td>
                                <td>
                                    <strong class="text-dark">Rp <?= number_format((float)$prod['price'], 0, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <span class="fw-bold fs-6"><?= number_format($prod['qty_in_stock']) ?></span> <small class="text-muted">unit</small>
                                </td>
                                <td>
                                    <?php if ($prod['qty_in_stock'] == 0): ?>
                                        <span class="badge bg-danger badge-pill">
                                            <i class="bi bi-x-circle me-1"></i> Habis
                                        </span>
                                    <?php elseif ($prod['qty_in_stock'] <= 5): ?>
                                        <span class="badge bg-warning text-dark badge-pill">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Menipis
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle badge-pill">
                                            <i class="bi bi-check2-circle me-1"></i> Tersedia
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border badge-pill">
                                        <?= (int)$prod['total_sold'] ?> unit terjual
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Tombol Edit -->
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditProduct"
                                                data-id="<?= $prod['product_id'] ?>"
                                                data-name="<?= esc($prod['product_name']) ?>"
                                                data-stock="<?= $prod['qty_in_stock'] ?>"
                                                data-price="<?= $prod['price'] ?>"
                                                title="Edit Produk">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Tombol Hapus -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDeleteProduct"
                                                data-id="<?= $prod['product_id'] ?>"
                                                data-name="<?= esc($prod['product_name']) ?>"
                                                data-sold="<?= (int)$prod['total_sold'] ?>"
                                                title="Hapus Produk">
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

<!-- Modal Tambah Produk -->
<div class="modal fade" id="modalAddProduct" tabindex="-1" aria-labelledby="modalAddProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= base_url('/products/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h6 class="modal-title fw-bold" id="modalAddProductLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Tambah Produk Baru
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" placeholder="Contoh: Monitor Samsung 27 Inch" required minlength="2" maxlength="150">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Stok Tersedia <span class="text-danger">*</span></label>
                            <input type="number" name="qty_in_stock" class="form-control" placeholder="0" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" placeholder="100000" min="0" step="1000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal fade" id="modalEditProduct" tabindex="-1" aria-labelledby="modalEditProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formEditProduct" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h6 class="modal-title fw-bold" id="modalEditProductLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Produk
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" id="edit_product_name" class="form-control" required minlength="2" maxlength="150">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Stok Tersedia <span class="text-danger">*</span></label>
                            <input type="number" name="qty_in_stock" id="edit_qty_in_stock" class="form-control" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="edit_price" class="form-control" min="0" step="1000" required>
                        </div>
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

<!-- Modal Konfirmasi Hapus Produk -->
<div class="modal fade" id="modalDeleteProduct" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formDeleteProduct" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4 text-center">
                    <div class="bg-danger-subtle text-danger rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-trash3-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Konfirmasi Hapus Produk</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menghapus produk <strong id="delete_product_name" class="text-dark"></strong>?
                    </p>
                    <div id="delete_warning_sold" class="alert alert-warning border-0 small mt-3 text-start d-none">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Produk ini tercatat dalam riwayat transaksi pembelian. Sistem akan mencegah penghapusan untuk menjaga konsistensi laporan.
                    </div>
                </div>
                <div class="modal-footer bg-light border-top justify-content-center px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Edit Product Modal Event
    const modalEditProduct = document.getElementById('modalEditProduct');
    modalEditProduct.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const stock = button.getAttribute('data-stock');
        const price = button.getAttribute('data-price');
        
        document.getElementById('edit_product_name').value = name;
        document.getElementById('edit_qty_in_stock').value = stock;
        document.getElementById('edit_price').value = Math.round(price);
        document.getElementById('formEditProduct').action = `<?= base_url('/products/update') ?>/${id}`;
    });

    // Delete Product Modal Event
    const modalDeleteProduct = document.getElementById('modalDeleteProduct');
    modalDeleteProduct.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const sold = parseInt(button.getAttribute('data-sold') || '0');
        
        document.getElementById('delete_product_name').textContent = name;
        document.getElementById('formDeleteProduct').action = `<?= base_url('/products/delete') ?>/${id}`;

        const warnDiv = document.getElementById('delete_warning_sold');
        if (sold > 0) {
            warnDiv.classList.remove('d-none');
        } else {
            warnDiv.classList.add('d-none');
        }
    });
</script>
<?= $this->endSection() ?>
