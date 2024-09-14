<!-- sidebar.php -->

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">KEN Group Admin <sup>2</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Customer Management System</div>

    <!-- Nav Item - Customer Management Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCustomer"
            aria-expanded="true" aria-controls="collapseCustomer">
            <i class="fas fa-users"></i>
            <span>Customer</span>
        </a>
        <div id="collapseCustomer" class="collapse" aria-labelledby="headingCustomer" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Customer:</h6>
                <a class="collapse-item" href="customer_register.php">
                    <i class="fas fa-user-plus"></i> Create Customer
                </a>
                <a class="collapse-item" href="customer_listing.php">
                    <i class="fas fa-address-book"></i> Customer Listing
                </a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Purchase Management</div>

    <!-- Nav Item - Purchase Management Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePurchase"
            aria-expanded="true" aria-controls="collapsePurchase">
            <i class="fas fa-shopping-cart"></i>
            <span>Purchase</span>
        </a>
        <div id="collapsePurchase" class="collapse" aria-labelledby="headingPurchase" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Purchase Utilities:</h6>
                <a class="collapse-item" href="new_purchase.php">
                    <i class="fas fa-shopping-cart"></i> New Purchase
                </a>
                <a class="collapse-item" href="purchase_listing.php">
                    <i class="fas fa-list-alt"></i> Purchase Listing
                </a>
                <a class="collapse-item" href="purchase_report.php">
                    <i class="fas fa-chart-line"></i> Report
                </a>
                <a class="collapse-item" href="utilities-other.html">
                    <i class="fas fa-cogs"></i> Other
                </a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Nav Item - Logout -->
    <li class="nav-item">
        <a class="nav-link" href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span></a>
    </li>

</ul>
