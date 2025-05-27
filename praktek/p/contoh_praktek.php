<?php
// Pengaturan Koneksi Database
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "contoh buku";

// Buat koneksi
$koneksi = new mysqli($servername, $username_db, $password_db, $dbname);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

$pesan = ''; // Variabel untuk menampilkan pesan

// =========================
// CRUD BUKU
// =========================

// CREATE
if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah_buku') {
    $judul = $_POST['judul'];
    $penulis = $_POST['penulis'];
    $tahun_terbit = $_POST['tahun_terbit'];
    $stok = $_POST['stok'];

    $stmt = $koneksi->prepare("INSERT INTO buku (judul, penulis, tahun_terbit, stok) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        $pesan = '<div class="alert alert-danger">Error prepare tambah: ' . $koneksi->error . '</div>';
    } else {
        $stmt->bind_param("ssii", $judul, $penulis, $tahun_terbit, $stok);
        $pesan = $stmt->execute()
            ? '<div class="alert alert-success">Buku berhasil ditambahkan!</div>'
            : '<div class="alert alert-danger">Gagal tambah buku: ' . $stmt->error . '</div>';
        $stmt->close();
    }
}

// UPDATE
if (isset($_POST['aksi']) && $_POST['aksi'] == 'update_buku') {
    $id = $_POST['id_buku']; // Pastikan ID diambil dari form
    $judul = $_POST['judul'];
    $penulis = $_POST['penulis'];
    $tahun_terbit = $_POST['tahun_terbit'];
    $stok = $_POST['stok'];
    $stmt = $koneksi->prepare("UPDATE buku SET judul=?, penulis=?, tahun_terbit=?, stok=? WHERE id=?");
    if ($stmt === false) {
        $pesan = '<div class="alert alert-danger">Error prepare update: ' . $koneksi->error . '</div>';
    } else {
        $stmt->bind_param("ssiii", $judul, $penulis, $tahun_terbit, $stok, $id); // Pastikan ID terikat dengan benar
        $pesan = $stmt->execute()
            ? '<div class="alert alert-success">Buku berhasil diupdate!</div>'
            : '<div class="alert alert-danger">Gagal update buku: ' . $stmt->error . '</div>';
        $stmt->close();
    }
}

// DELETE
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus') {
    $id = $_GET['id']; // Pastikan ID diambil dari URL
    $stmt = $koneksi->prepare("DELETE FROM buku WHERE id = ?");
    if ($stmt === false) {
        $pesan = '<div class="alert alert-danger">Error prepare hapus: ' . $koneksi->error . '</div>';
    } else {
        $stmt->bind_param("i", $id); // Pastikan ID terikat dengan benar
        $pesan = $stmt->execute()
            ? '<div class="alert alert-success">Buku berhasil dihapus!</div>'
            : '<div class="alert alert-danger">Gagal hapus buku: ' . $stmt->error . '</div>';
        $stmt->close();
    }
}

// =========================
// PINJAM & KEMBALIKAN
// =========================

// PINJAM
if (isset($_GET['aksi']) && $_GET['aksi'] == 'pinjam') {
    $id_buku = $_GET['id'];
    $koneksi->begin_transaction();

    try {
        $stmt = $koneksi->prepare("SELECT stok FROM buku WHERE id = ? FOR UPDATE");
        if (!$stmt) throw new Exception('Prepare error: ' . $koneksi->error);
        $stmt->bind_param("i", $id_buku);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if ($data && $data['stok'] > 0) {
            $stmt = $koneksi->prepare("UPDATE buku SET stok = stok - 1 WHERE id = ?");
            if (!$stmt) throw new Exception('Prepare update error: ' . $koneksi->error);
            $stmt->bind_param("i", $id_buku);
            if ($stmt->execute()) {
                $koneksi->commit();
                $pesan = '<div class="alert alert-success">Buku berhasil dipinjam!</div>';
            } else {
                throw new Exception('Execute error: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            $pesan = '<div class="alert alert-warning">Stok habis atau buku tidak ditemukan.</div>';
        }
    } catch (Exception $e) {
        $koneksi->rollback();
        $pesan = '<div class="alert alert-danger">Gagal pinjam: ' . $e->getMessage() . '</div>';
    }
}

// KEMBALIKAN
if (isset($_GET['aksi']) && $_GET['aksi'] == 'kembali') {
    $id_buku = $_GET['id'];
    $koneksi->begin_transaction();

    try {
        $stmt = $koneksi->prepare("SELECT id FROM buku WHERE id = ? FOR UPDATE");
        if (!$stmt) throw new Exception('Prepare error: ' . $koneksi->error);
        $stmt->bind_param("i", $id_buku);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $stmt = $koneksi->prepare("UPDATE buku SET stok = stok + 1 WHERE id = ?");
            if (!$stmt) throw new Exception('Prepare update error: ' . $koneksi->error);
            $stmt->bind_param("i", $id_buku);
            if ($stmt->execute()) {
                $koneksi->commit();
                $pesan = '<div class="alert alert-success">Buku berhasil dikembalikan!</div>';
            } else {
                throw new Exception('Execute error: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            $pesan = '<div class="alert alert-warning">Buku tidak ditemukan.</div>';
        }
    } catch (Exception $e) {
        $koneksi->rollback();
        $pesan = '<div class="alert alert-danger">Gagal kembalikan: ' . $e->getMessage() . '</div>';
    }
}

// =========================
// FORM EDIT
// =========================
$data_edit = null;
if (isset($_GET['aksi']) && $_GET['aksi'] == 'edit') {
    $id = $_GET['id'];
    $stmt = $koneksi->prepare("SELECT * FROM buku WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data_edit = $result->fetch_assoc();
        } else {
            $pesan = '<div class="alert alert-warning">Data buku tidak ditemukan.</div>';
        }
        $stmt->close();
    } else {
        $pesan = '<div class="alert alert-danger">Error prepare edit: ' . $koneksi->error . '</div>';
    }
}
?>

<!-- ========================= -->
<!-- HTML -->
<!-- ========================= -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Aplikasi Perpustakaan UKK - XI RPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h2 class="text-center mb-4">Aplikasi Perpustakaan UKK - XI RPL</h2>

        <?= $pesan ?>

        <!-- Form Tambah Buku -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Tambah Buku Baru</div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="aksi" value="tambah_buku">
                    <div class="mb-3"><label class="form-label">Judul</label><input type="text" name="judul" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Penulis</label><input type="text" name="penulis" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Tahun Terbit</label><input type="number" name="tahun_terbit" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Stok</label><input type="number" name="stok" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary">Simpan Buku</button>
                </form>
            </div>
        </div>

        <!-- Form Edit Buku -->
        <?php if ($data_edit): ?>
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-white">Edit Buku: <?= htmlspecialchars($data_edit['judul']) ?></div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="aksi" value="update_buku">
                        <input type="hidden" name="id_buku" value="<?= $data_edit['id'] ?>">
                        <div class="mb-3"><label class="form-label">Judul</label><input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($data_edit['judul']) ?>" required></div>
                        <div class="mb-3"><label class="form-label">Penulis</label><input type="text" name="penulis" class="form-control" value="<?= htmlspecialchars($data_edit['penulis']) ?>" required></div>
                        <div class="mb-3"><label class="form-label">Tahun Terbit</label><input type="number" name="tahun_terbit" class="form-control" value="<?= htmlspecialchars($data_edit['tahun_terbit']) ?>" required></div>
                        <div class="mb-3"><label class="form-label">Stok</label><input type="number" name="stok" class="form-control" value="<?= htmlspecialchars($data_edit['stok']) ?>" required></div>
                        <button type="submit" class="btn btn-warning">Update</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabel Daftar Buku -->
        <h3 class="mb-3">Daftar Buku</h3>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Judul</th>
                    <th>Penulis</th>
                    <th>Tahun</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $koneksi->query("SELECT * FROM buku ORDER BY judul ASC");
                if ($result->num_rows > 0):
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['penulis']) ?></td>
                            <td><?= htmlspecialchars($row['tahun_terbit']) ?></td>
                            <td><?= htmlspecialchars($row['stok']) ?></td>
                            <td>
                                <a href="?aksi=edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?aksi=hapus&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus buku ini?')">Hapus</a>
                                <hr class="my-1">
                                <a href="?aksi=pinjam&id=<?= $row['id'] ?>" class="btn btn-success btn-sm <?= $row['stok'] == 0 ? 'disabled' : '' ?>" onclick="return <?= $row['stok'] == 0 ? 'false' : 'confirm(\'Yakin ingin meminjam?\')' ?>">Pinjam</a>
                                <a href="?aksi=kembali&id=<?= $row['id'] ?>" class="btn btn-info btn-sm">Kembalikan</a>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="6">Tidak ada data buku.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php $koneksi->close(); ?>