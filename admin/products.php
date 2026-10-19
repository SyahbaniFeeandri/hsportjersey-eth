<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if(!isAdmin()) {
    redirect('../login.php');
}

// Handle delete
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    redirect('products.php');
}

// Handle add/edit
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $material = $_POST['material'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $sizes = $_POST['sizes'];
    $colors = $_POST['colors'];
    
    $image = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
    }
    
    if(isset($_POST['id']) && $_POST['id']) {
        // Update
        if($image) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, material=?, price=?, description=?, sizes=?, colors=?, image=? WHERE id=?");
            $stmt->execute([$name, $category, $material, $price, $description, $sizes, $colors, $image, $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, material=?, price=?, description=?, sizes=?, colors=? WHERE id=?");
            $stmt->execute([$name, $category, $material, $price, $description, $sizes, $colors, $_POST['id']]);
        }
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO products (name, category, material, price, description, sizes, colors, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $material, $price, $description, $sizes, $colors, $image]);
    }
    redirect('products.php');
}

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin H-Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #34495e;
        }
        .sidebar a.active {
            background: #007bff;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py-4">
                    <i class="fas fa-tshirt fa-3x text-white"></i>
                    <h5 class="text-white mt-2">Admin Panel</h5>
                </div>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="products.php" class="active"><i class="fas fa-box me-2"></i> Kelola Produk</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</a>
                <a href="customers.php"><i class="fas fa-users me-2"></i> Pelanggan</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Produk</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="clearForm()">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Gambar</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Bahan</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if($product['image'] && file_exists('../uploads/' . $product['image'])): ?>
                                            <img src="../uploads/<?php echo $product['image']; ?>" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <i class="fas fa-tshirt fa-2x text-muted"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $product['category']; ?></span></td>
                                    <td><?php echo $product['material']; ?></td>
                                    <td class="text-success fw-bold">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Form Product -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah/Edit Produk</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="product_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk *</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori *</label>
                                    <select name="category" id="category" class="form-control" required>
                                        <option value="Futsal">Futsal</option>
                                        <option value="Sepak Bola">Sepak Bola</option>
                                        <option value="Basket">Basket</option>
                                        <option value="Sepeda">Sepeda</option>
                                        <option value="Lari">Lari</option>
                                        <option value="Padel">Padel</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Bahan *</label>
                                    <input type="text" name="material" id="material" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga *</label>
                                    <input type="number" name="price" id="price" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ukuran (pisahkan dengan koma) *</label>
                                    <input type="text" name="sizes" id="sizes" class="form-control" value="S,M,L,XL,XXL" required>
                                    <small class="text-muted">Contoh: S,M,L,XL,XXL</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Warna (pisahkan dengan koma)</label>
                                    <input type="text" name="colors" id="colors" class="form-control" value="Merah,Biru,Hijau,Kuning">
                                    <small class="text-muted">Contoh: Merah,Biru,Hijau,Kuning</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('product_id').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('category').value = product.category;
            document.getElementById('material').value = product.material;
            document.getElementById('price').value = product.price;
            document.getElementById('description').value = product.description;
            document.getElementById('sizes').value = product.sizes;
            document.getElementById('colors').value = product.colors;
            
            new bootstrap.Modal(document.getElementById('productModal')).show();
        }
        
        function clearForm() {
            document.getElementById('product_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('material').value = '';
            document.getElementById('price').value = '';
            document.getElementById('description').value = '';
            document.getElementById('sizes').value = 'S,M,L,XL,XXL';
            document.getElementById('colors').value = 'Merah,Biru,Hijau,Kuning';
        }
    </script>
</body>
</html>