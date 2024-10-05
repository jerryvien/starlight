<!-- sidebar.php -->
<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #2F2F2F;">


    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-chart-line"></i> <!-- Corporate styled icon -->
        </div>
        <div class="sidebar-brand-text mx-3">KEN Group</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
       Members Interface
    </div>

    <!-- Nav Item - Customer Management Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
            aria-expanded="true" aria-controls="collapseTwo">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Customer Operations:</h6>
                <a class="collapse-item" href="customer_register.php">Create Customer</a>
                <a class="collapse-item" href="customer_listing.php">Customer Listing</a>
                <a class="collapse-item" href="customer_analysis.php">Customer Analysis</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Purchase Management Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities"
            aria-expanded="true" aria-controls="collapseUtilities">
            <i class="fas fa-shopping-cart"></i>
            <span>Purchases</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Purchase Operations:</h6>
                <a class="collapse-item" href="purchase_entry.php">New Purchase</a>
                <a class="collapse-item" href="purchase_listing.php">Purchase Listing</a>
            </div>
        </div>
    </li>
    <!-- Nav Item - Purchase Management Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSample"
            aria-expanded="true" aria-controls="collapseSample">
            <i class="fas fa-trophy"></i> 
            <span>Prize Report</span>
        </a>
        <div id="collapseSample" class="collapse" aria-labelledby="headingSample"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Prize Operations:</h6>
                <a class="collapse-item" href="winning_table.php">Create Prize</a>
                <a class="collapse-item" href="winning_report.php">Prize Listing</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">
       Admin Interface
    </div>

    <!-- Nav Item - Customer Management Collapse Menu -->
        <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAgent"
            aria-expanded="true" aria-controls="collapseTwo">
            <i class="fas fa-id-card"></i>
            <span>Agents</span>
        </a>
        <div id="collapseAgent" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Agent Operations:</h6>
                <a class="collapse-item" href="customer_register.php">Create Agent</a>
                <a class="collapse-item" href="customer_listing.php">Agent Listing</a>
                <a class="collapse-item" href="agent_analysis.php">Agent Analysis</a>
            </div>
        </div>
    </li>

      <!-- Nav Item - Report Management Collapse Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReport"
            aria-expanded="true" aria-controls="collapseTwo">
            <i class="fas fa-id-card"></i>
            <span>Reports</span>
        </a>
        <div id="collapseReport" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Analaysis Report:</h6>
                <a class="collapse-item" href="win_rate_table.php">Win/Loss Ratio Report</a>
                <a class="collapse-item" href="sales_dashboard.php">Sales Avg Report</a>
                <a class="collapse-item" href="avg_dashboard.php">Agent Avg Report</a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">
        <!-- Sidebar Toggler (Sidebar) -->
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>

</ul>
<!-- End of Sidebar -->
