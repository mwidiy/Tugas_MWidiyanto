<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Pengguna & Pelanggan</h4>
        <p class="text-muted mb-0 small">Kelola data pelanggan yang berbelanja pada sistem simulasi</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAddUser">
        <i class="bi bi-person-plus-fill"></i>
        <span>Tambah Pelanggan</span>
    </button>
</div>

<div class="card card-custom">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-people text-primary fs-5"></i>
            <h6 class="mb-0 fw-bold">Daftar Pengguna (User Table)</h6>
        </div>
        <span class="badge bg-light text-secondary border px-3 py-2">Total: <?= count($users) ?> Orang</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;">User ID</th>
                        <th>Nama Pengguna</th>
                        <th>Riwayat Belanja</th>
                        <th>Waktu Terdaftar</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-person-x fs-2 d-block mb-1 text-secondary"></i>
                                Belum ada data pengguna yang tersimpan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">#<?= $user['user_id'] ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                            <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark"><?= esc($user['name']) ?></div>
                                            <small class="text-muted">Pelanggan Aktif</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['total_orders'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle badge-pill">
                                            <i class="bi bi-bag-check me-1"></i> <?= $user['total_orders'] ?> Transaksi
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border badge-pill">
                                            Belum ada transaksi
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= !empty($user['created_at']) ? date('d M Y, H:i', strtotime($user['created_at'])) : '-' ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Tombol Edit -->
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditUser"
                                                data-id="<?= $user['user_id'] ?>"
                                                data-name="<?= esc($user['name']) ?>"
                                                title="Edit Pengguna">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Tombol Hapus -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDeleteUser"
                                                data-id="<?= $user['user_id'] ?>"
                                                data-name="<?= esc($user['name']) ?>"
                                                data-orders="<?= $user['total_orders'] ?>"
                                                title="Hapus Pengguna">
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

<!-- Modal Tambah User -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-labelledby="modalAddUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= base_url('/users/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h6 class="modal-title fw-bold" id="modalAddUserLabel">
                        <i class="bi bi-person-plus text-primary me-2"></i>Tambah Pelanggan Baru
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nama Lengkap Pengguna <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Muhammad Budi" required minlength="2" maxlength="150">
                        <small class="text-muted">Nama ini akan disimpan pada kolom <code>name</code> di tabel <code>users</code>.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formEditUser" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h6 class="modal-title fw-bold" id="modalEditUserLabel">
                        <i class="bi bi-pencil text-primary me-2"></i>Edit Data Pelanggan
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nama Pengguna <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required minlength="2" maxlength="150">
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

<!-- Modal Konfirmasi Hapus User -->
<div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="formDeleteUser" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4 text-center">
                    <div class="bg-danger-subtle text-danger rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-trash3-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Konfirmasi Hapus Pengguna</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menghapus pengguna <strong id="delete_user_name" class="text-dark"></strong>?
                    </p>
                    <div id="delete_warning_orders" class="alert alert-warning border-0 small mt-3 text-start d-none">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Pengguna ini memiliki transaksi aktif. Sistem akan mencegah penghapusan untuk menjaga integritas data.
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
    // Edit User Modal Event
    const modalEditUser = document.getElementById('modalEditUser');
    modalEditUser.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        
        document.getElementById('edit_user_name').value = name;
        document.getElementById('formEditUser').action = `<?= base_url('/users/update') ?>/${id}`;
    });

    // Delete User Modal Event
    const modalDeleteUser = document.getElementById('modalDeleteUser');
    modalDeleteUser.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const orders = parseInt(button.getAttribute('data-orders') || '0');
        
        document.getElementById('delete_user_name').textContent = name;
        document.getElementById('formDeleteUser').action = `<?= base_url('/users/delete') ?>/${id}`;

        const warnDiv = document.getElementById('delete_warning_orders');
        if (orders > 0) {
            warnDiv.classList.remove('d-none');
        } else {
            warnDiv.classList.add('d-none');
        }
    });
</script>
<?= $this->endSection() ?>
