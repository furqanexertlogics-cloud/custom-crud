<?php
/*
 * Plugin Name: Custom CRUD
 * Version: 1.3

 */

if (!defined('ABSPATH')) exit;

register_activation_hook(__FILE__, function () {
    global $wpdb;

    $table = $wpdb->prefix . 'custom_crud';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100),
        department VARCHAR(100),
        position VARCHAR(100),
        joining_date DATE,
        status VARCHAR(20),
        picture VARCHAR(255)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

function crud_upload_image($file)
{
    if (empty($file['name'])) return '';

    $dir = plugin_dir_path(__FILE__) . 'uploads/';
    $url = plugin_dir_url(__FILE__) . 'uploads/';

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $name = time() . '_' . sanitize_file_name($file['name']);
    $path = $dir . $name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $url . $name;
    }
    return '';
}

add_action('admin_menu', function () {
    add_menu_page('CRUD', 'CRUD', 'manage_options', 'emp-list', 'crud_list');
    add_submenu_page('emp-list', 'Add Employee', 'Add Employee', 'manage_options', 'add-emp', 'crud_add');
    add_submenu_page('emp-list', 'Shortcode', 'Shortcode', 'manage_options', 'emp-shortcode', 'crud_shortcode_page');
    add_submenu_page('emp-list', 'Edit Employee', 'Edit Employee', 'manage_options', 'edit-emp', 'crud_edit');
    add_submenu_page('emp-list', 'Delete Employee', 'Delete Employee', 'manage_options', 'delete-emp', 'crud_delete');
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
});

function crud_add()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_crud';
    $msg = '';

    if (isset($_POST['submit'])) {

        $image = crud_upload_image($_FILES['picture']);

        $wpdb->insert($table, [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'department' => sanitize_text_field($_POST['department']),
            'position' => sanitize_text_field($_POST['position']),
            'joining_date' => sanitize_text_field($_POST['joining_date']),
            'status' => sanitize_text_field($_POST['status']),
            'picture' => esc_url_raw($image)
        ]);

        $msg = "Data Saved Successfully";
    }
?>

<div class="container mt-4">
<div class="card shadow"><div class="card-body">

<h3>Add Employee</h3>
<?php if ($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>

<form method="post" enctype="multipart/form-data">
<input class="form-control mb-2" name="name" placeholder="Name" required>
<input class="form-control mb-2" name="email" placeholder="Email" required>
<input class="form-control mb-2" name="department" placeholder="Department" required>
<input class="form-control mb-2" name="position" placeholder="Position" required>
<input class="form-control mb-2" name="joining_date" type="date" required>

<select name="status" class="form-control mb-2" required>
  <option value="Active">Active</option>
  <option value="Inactive">Inactive</option>
</select>

<input type="file" name="picture" class="form-control mb-3">
<button class="btn btn-primary" name="submit">Save Employee</button>
</form>

</div></div></div>

<?php }

function crud_list()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_crud';
    $data = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
?>

<div class="container mt-4">
<h3>Employee List</h3>

<div class="table-responsive">
<table class="table table-bordered table-hover shadow">

<tr class="table-dark">
<th>ID</th><th>Name</th><th>Email</th>
<th>Department</th>
<?php if(is_admin()): ?>
<th>Position</th><th>Joining Date</th><th>Status</th>
<?php endif; ?>
<th>Picture</th>
<?php if(is_admin()): ?><th>Action</th><?php endif; ?>
</tr>

<?php foreach ($data as $row): ?>
<tr>
<td><?= $row->id ?></td>
<td><?= esc_html($row->name) ?></td>
<td><?= esc_html($row->email) ?></td>
<td><?= esc_html($row->department) ?></td>

<?php if(is_admin()): ?>
<td><?= esc_html($row->position) ?></td>
<td><?= !empty($row->joining_date) ? date('d M Y', strtotime($row->joining_date)) : '-' ?></td>
<td><?= esc_html($row->status) ?></td>
<?php endif; ?>

<td>
<?php if (!empty($row->picture)): ?>
<img src="<?= esc_url($row->picture) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">
<?php endif; ?>
</td>

<?php if(is_admin()): ?>
<td>
<a href="admin.php?page=edit-emp&id=<?= $row->id ?>" class="btn btn-warning btn-sm">Edit</a>
<a href="admin.php?page=delete-emp&id=<?= $row->id ?>" class="btn btn-danger btn-sm">Delete</a>
</td>
<?php endif; ?>

</tr>
<?php endforeach; ?>

</table>
</div>
</div>

<?php }

add_shortcode('employee_list', function () {
    ob_start();
    crud_list();
    return ob_get_clean();
});

function crud_shortcode_page()
{
    echo "<div class='container mt-4'>
    <h3>Use this shortcode:</h3>
    <input type='text' value='[employee_list]' class='form-control' readonly>
    </div>";
}

function crud_edit()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_crud';

    // ✅ If no ID → redirect to first record
    if (!isset($_GET['id'])) {

        $first = $wpdb->get_row("SELECT id FROM $table ORDER BY id ASC LIMIT 1");

        if ($first) {
            echo "<script>location.href='admin.php?page=edit-emp&id={$first->id}'</script>";
        } else {
            echo "<div class='alert alert-danger'>No records found</div>";
        }
        return;
    }

    $id = intval($_GET['id']);

    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));

    
    if (!$row) {
        echo "<div class='alert alert-danger'>Record not found</div>";
        return;
    }

    
    if (isset($_POST['update'])) {

        $image = crud_upload_image($_FILES['picture']);

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'department' => sanitize_text_field($_POST['department']),
            'position' => sanitize_text_field($_POST['position']),
            'joining_date' => sanitize_text_field($_POST['joining_date']),
            'status' => sanitize_text_field($_POST['status']),
        ];

        if ($image) {
            $data['picture'] = esc_url_raw($image);
        }

        $wpdb->update($table, $data, ['id' => $id]);

        echo "<div class='alert alert-success'>Updated successfully</div>";
    }
?>

<div class="container mt-4"><div class="card shadow"><div class="card-body">

<h3>Edit Employee</h3>

<form method="post" enctype="multipart/form-data">
<input class="form-control mb-2" name="name" value="<?= esc_attr($row->name) ?>">
<input class="form-control mb-2" name="email" value="<?= esc_attr($row->email) ?>">
<input class="form-control mb-2" name="department" value="<?= esc_attr($row->department) ?>">
<input class="form-control mb-2" name="position" value="<?= esc_attr($row->position) ?>">
<input class="form-control mb-2" name="joining_date" type="date" value="<?= esc_attr($row->joining_date) ?>">

<select name="status" class="form-control mb-2">
  <option value="Active" <?= $row->status == 'Active' ? 'selected' : '' ?>>Active</option>
  <option value="Inactive" <?= $row->status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
</select>

<input type="file" name="picture" class="form-control mb-2">
<button class="btn btn-primary" name="update">Update Employee</button>
</form>

</div></div></div>

<?php
}

function crud_delete()
{
    global $wpdb;
    $table = $wpdb->prefix . 'custom_crud';

    if (!isset($_GET['id'])) {
        echo "<div class='alert alert-danger'>Invalid request</div>";
        return;
    }

    $id = intval($_GET['id']);

    if (isset($_POST['delete'])) {
        if ($_POST['conf'] == 'yes') {
            $wpdb->delete($table, ['id' => $id]);
        }
        echo "<script>location.href='admin.php?page=emp-list'</script>";
    }
?>

<div class="container mt-4">
<div class="card"><div class="card-body">

<form method="post">
<p>Are you sure you want to delete?</p>

<label><input type="radio" name="conf" value="yes"> Yes</label>
<label><input type="radio" name="conf" value="no"> No</label>

<br><br>
<button class="btn btn-danger" name="delete">Delete</button>
</form>

</div></div></div>

<?php }



if (file_exists(plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php')) {

    require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

    if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {

        $updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/furqanexertlogics-cloud/custom-crud',
            __FILE__,
            'custom-crud'
        );

        $updateChecker->setBranch('main');
    }
}
