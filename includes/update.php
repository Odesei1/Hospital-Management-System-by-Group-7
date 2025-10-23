<?php
require_once "../class/customer.php";
require_once "../class/invoice.php";
require_once "../class/vendor.php";
require_once "../class/product.php";
require_once "../config/db.php";
require_once "../class/line.php";


$database = new Database();
$db = $database->connect();

$customer = new customer($db);
$invoice = new invoice($db);
$vendor = new vendor($db);
$product = new product($db);
$line = new line($db);


// CUSTOMER UPDATE
$updateCustomer = null;
if(isset($_POST["update"]) && isset($_POST["cus_code"])) {
  $cus_code = trim($_POST["cus_code"]);
  $updateCustomer=$customer->findID($cus_code);
}
if (isset($_POST['submit_update'])) {
    $cus_code = $_POST['cus_code'];
    $cus_lname = $_POST['cus_lname'];
    $cus_fname = $_POST['cus_fname'];
    $cus_initial = $_POST['cus_initial'];
    $cus_phone = $_POST['cus_phone'];
    $cus_balance = $_POST['cus_balance'];

    $customer->update($cus_code, $cus_lname, $cus_fname, $cus_initial, $cus_phone, $cus_balance);
    $updateCustomer = null; // hide the pop up modal
}
// INVOICE UPDATE
$updateInvoice = null;
if(isset($_POST["update"])  && isset($_POST["inv_number"])){
  $inv_number = trim($_POST["inv_number"]);
  $updateInvoice=$invoice->findID($inv_number);
}
if (isset($_POST["submit_update_invoice"])) {
    $inv_number =  $_POST['inv_number'];
    $inv_total = $_POST['inv_total'];
    $inv_status = $_POST['inv_status'];
  
    $invoice->update($inv_number, $inv_total, $inv_status);
    $updateInvoice = null;
}
// Vendor Update
$updateVendor = null;
if (isset($_POST["update"]) && isset($_POST["v_code"])) {
    $V_CODE = trim($_POST["v_code"]);
    $updateVendor = $vendor->findID($V_CODE);
}

if (isset($_POST["submit_update_vendor"])) {
    $V_CODE = $_POST['v_code'];
    $V_NAME = $_POST['v_name'];
    $V_CONTACT = $_POST['v_contact'];
    $V_PHONE = $_POST['v_phone'];
    $V_PROVINCE = $_POST['v_province'];
    $V_ORDER_FLAG = $_POST['v_order_flag'];

    $vendor->update($V_CODE, $V_NAME, $V_CONTACT, $V_PHONE, $V_PROVINCE, $V_ORDER_FLAG);
    $updateVendor = null; 
}

//Product Update
$updateProduct = null;
if (isset($_POST["update"]) && isset($_POST["p_code"])){
  $P_CODE = trim($_POST["p_code"]);
  $updateProduct = $product->findID($P_CODE);
}
if(isset($_POST["submit_update_product"])){
    $P_CODE = $_POST["p_code"];
    $P_DESCRIPT = $_POST["p_descript"];
    $P_QOH = $_POST["p_qoh"];
    $P_MIN = $_POST["p_min"];
    $P_PRICE = $_POST["p_price"];
    $P_DISCOUNT = $_POST["p_discount"];
    $V_CODE = $_POST["v_code"];

  $product->update($P_CODE, $P_DESCRIPT, $P_QOH, $P_MIN, $P_PRICE, $P_DISCOUNT, $V_CODE);
  $updateProduct =null;
}
//Line update
$updateLine = null;
if (isset($_POST["update"]) && isset($_POST["inv_number"]) && isset($_POST["line_number"])) {
    $LINE_NUMBER = trim($_POST["line_number"]);
    // get the line row; you might also want to fetch joined data
    $updateLine = $line->findID($LINE_NUMBER);
}

// if user submitted the update form
if (isset($_POST["submit_update_line"])) {
    $INV_NUMBER  = $_POST['inv_number'];
    $LINE_NUMBER = $_POST['line_number'];
    $P_CODE      = $_POST['p_code'];
    $LINE_UNITS  = $_POST['line_units'];
    $LINE_PRICE  = $_POST['line_price'];

    $line->update($INV_NUMBER, $LINE_NUMBER, $P_CODE, $LINE_UNITS, $LINE_PRICE);
    $updateLine = null; // hide modal after saving
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
<?php if ($updateCustomer): ?>
  <div class="card mt-4 border-primary">
    <div class="card shadow-sm">
      <h5 class="card-header bg-primary text-white">Update Customer: <?= htmlspecialchars($updateCustomer['CUS_CODE']) ?></h5>
    </div>
    <div class="card-body">
      <form method="POST">
          <input type="hidden" name="cus_code" value="<?= htmlspecialchars($updateCustomer["CUS_CODE"]) ?>">
          
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Last Name</label>
            <input type="text" name="cus_lname" class="form-control" value="<?= htmlspecialchars($updateCustomer['CUS_LNAME']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">First Name</label>
            <input type="text" name="cus_fname" class="form-control" value="<?= htmlspecialchars($updateCustomer['CUS_FNAME']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Middle Initial</label>
            <input type="text" name="cus_initial" class="form-control" value="<?= htmlspecialchars($updateCustomer['CUS_INITIAL']) ?>">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="cus_phone" class="form-control" value="<?= htmlspecialchars($updateCustomer['CUS_PHONE']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Balance</label>
            <input type="number" step="0.01" name="cus_balance" class="form-control" value="<?= htmlspecialchars($updateCustomer['CUS_BALANCE']) ?>" required>
          </div>
        </div>
        <button type="submit" name="submit_update" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
<?php endif; ?>


<?php if ($updateInvoice): ?>
  <!-- Invoice Update Form -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white"><h5>Update Invoice: <?= $updateInvoice["INV_NUMBER"] ?></h5></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="inv_number" value="<?= $updateInvoice["INV_NUMBER"] ?>">
        <input type="text" name="inv_total" class="form-control mb-2" value="<?= $updateInvoice["INV_TOTAL"] ?>" required>
        <input type="text" name="inv_status" class="form-control mb-3" value="<?= $updateInvoice["INV_STATUS"] ?>" required>
        <button type="submit" name="submit_update_invoice" class="btn btn-primary">Update Invoice</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($updateVendor): ?>
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5>Update Vendor: <?= htmlspecialchars($updateVendor["V_CODE"]) ?></h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="v_code" value="<?= htmlspecialchars($updateVendor["V_CODE"]) ?>">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Vendor Name</label>
            <input type="text" name="v_name" class="form-control" value="<?= htmlspecialchars($updateVendor["V_NAME"]) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact Person</label>
            <input type="text" name="v_contact" class="form-control" value="<?= htmlspecialchars($updateVendor["V_CONTACT"]) ?>" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="v_phone" class="form-control" value="<?= htmlspecialchars($updateVendor["V_PHONE"]) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Province</label>
            <input type="text" name="v_province" class="form-control" value="<?= htmlspecialchars($updateVendor["V_PROVINCE"]) ?>" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Order Flag</label>
            <input type="text" name="v_order_flag" class="form-control" value="<?= htmlspecialchars($updateVendor["V_ORDER_FLAG"]) ?>" required>
          </div>
        </div>

        <button type="submit" name="submit_update_vendor" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($updateProduct): ?>
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5>Update Product: <?= htmlspecialchars($updateProduct["P_CODE"]) ?></h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="p_code" value="<?= htmlspecialchars($updateProduct["P_CODE"]) ?>">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Product Descript</label>
            <input type="text" name="p_descript" class="form-control" value="<?= htmlspecialchars($updateProduct["P_DESCRIPT"]) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Product QOH</label>
            <input type="text" name="p_qoh" class="form-control" value="<?= htmlspecialchars($updateProduct["P_QOH"]) ?>" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Product MIN</label>
            <input type="text" name="p_min" class="form-control" value="<?= htmlspecialchars($updateProduct["P_MIN"]) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Product Price</label>
            <input type="text" name="p_price" class="form-control" value="<?= htmlspecialchars($updateProduct["P_PRICE"]) ?>" required>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Product Discount</label>
            <input type="text" name="p_discount" class="form-control" value="<?= htmlspecialchars($updateProduct["P_DISCOUNT"]) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Vendor Code</label>
            <input type="text" name="v_code" class="form-control" value="<?= htmlspecialchars($updateProduct["V_CODE"]) ?>" required>
          </div>
        </div>

        <button type="submit" name="submit_update_product" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($updateLine): ?>
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5>Update Line Item: <?= htmlspecialchars($updateLine["LINE_NUMBER"]) ?></h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="inv_number" value="<?= htmlspecialchars($updateLine["INV_NUMBER"]) ?>">
        <input type="hidden" name="line_number" value="<?= htmlspecialchars($updateLine["LINE_NUMBER"]) ?>">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Product Code</label>
            <input type="text" name="p_code" class="form-control" 
                   value="<?= htmlspecialchars($updateLine["P_CODE"]) ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Units</label>
            <input type="number" name="line_units" class="form-control" 
                   value="<?= htmlspecialchars($updateLine["LINE_UNITS"]) ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Line Price</label>
            <input type="number" step="0.01" name="line_price" class="form-control" 
                   value="<?= htmlspecialchars($updateLine["LINE_PRICE"]) ?>" required>
          </div>
        </div>

        <button type="submit" name="submit_update_line" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
<?php endif; ?>

</body>
