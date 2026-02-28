<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dropdown Fix Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CRITICAL Dropdown Visibility Styles - Highest Priority */
        .dropdown-toggle,
        .notification-bell,
        .btn[data-bs-toggle="dropdown"],
        a[data-bs-toggle="dropdown"],
        button[data-bs-toggle="dropdown"] {
            display: inline-flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
            z-index: 9999999 !important;
            transform: none !important;
            filter: none !important;
            pointer-events: auto !important;
            cursor: pointer !important;
            user-select: none !important;
        }

        /* FORCE dropdown containers to be visible */
        .dropdown,
        .notifications-dropdown,
        .user-dropdown {
            display: inline-block !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
            z-index: 9999998 !important;
            transform: none !important;
            filter: none !important;
        }

        /* FORCE dropdown menus to be hidden by default */
        .dropdown-menu {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            position: absolute !important;
            z-index: 9999997 !important;
            transform: none !important;
            filter: none !important;
            transition: all 0.3s ease !important;
        }

        /* FORCE dropdown menus to show when Bootstrap adds .show class */
        .dropdown.show .dropdown-menu {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* FORCE dropdown items to be visible */
        .dropdown-item {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
            z-index: 9999996 !important;
            transform: none !important;
            filter: none !important;
            pointer-events: auto !important;
        }

        /* FORCE specific dropdown elements to be visible */
        .notification-bell i,
        .dropdown-toggle i,
        .dropdown-toggle span {
            display: inline-block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            filter: none !important;
        }

        /* Override any conflicting styles */
        .dropdown-toggle[style],
        .notification-bell[style] {
            display: inline-flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            filter: none !important;
        }

        /* Test styling */
        .test-container {
            padding: 50px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .test-item {
            margin: 20px 0;
            padding: 20px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: white;
        }

        .test-item h4 {
            color: #495057;
            margin-bottom: 15px;
        }

        .dropdown-menu {
            min-width: 200px !important;
            background: white !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown-item {
            padding: 12px 16px !important;
            color: #212529 !important;
            text-decoration: none !important;
            border: none !important;
            transition: all 0.2s ease !important;
        }

        .dropdown-item:hover {
            background: #f8f9fa !important;
            color: #007bff !important;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="container">
            <h1 class="mb-4">Dropdown Fix Test</h1>
            <p class="lead">Test if dropdowns are now visible and working properly.</p>

            <div class="row">
                <div class="col-md-6">
                    <div class="test-item">
                        <h4><i class="fas fa-bell text-primary me-2"></i>Notifications Dropdown</h4>
                        <div class="dropdown notifications-dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell me-2"></i>Notifications
                                <span class="badge bg-danger">3</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle me-2"></i>New message</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-check-circle me-2"></i>Task completed</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-circle me-2"></i>Alert</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="test-item">
                        <h4><i class="fas fa-user text-success me-2"></i>User Dropdown</h4>
                        <div class="dropdown user-dropdown">
                            <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i>John Doe
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-bell me-2"></i>Notifications</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="test-item">
                        <h4><i class="fas fa-info-circle text-info me-2"></i>Basic Dropdown</h4>
                        <div class="dropdown">
                            <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-list me-2"></i>Options
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-plus me-2"></i>Add New</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-copy me-2"></i>Duplicate</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="test-item">
                        <h4><i class="fas fa-check-circle text-success me-2"></i>Test Results</h4>
                        <div id="test-results">
                            <p class="mb-2"><strong>Instructions:</strong></p>
                            <ol>
                                <li>Click on each dropdown button above</li>
                                <li>Verify that the dropdown menu appears</li>
                                <li>Check that all items are visible and clickable</li>
                                <li>Click outside to close the dropdown</li>
                            </ol>
                            <hr>
                            <p class="mb-2"><strong>Expected Results:</strong></p>
                            <ul>
                                <li>✅ Dropdown buttons should be visible</li>
                                <li>✅ Dropdown menus should appear on click</li>
                                <li>✅ All dropdown items should be functional</li>
                                <li>✅ No CSS conflicts should prevent visibility</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Dropdown Fix Test Initialized ===');

            // Test dropdown functionality
            const dropdowns = document.querySelectorAll('.dropdown-toggle');
            console.log('Found dropdowns:', dropdowns.length);

            dropdowns.forEach((dropdown, index) => {
                console.log(`Dropdown ${index}:`, dropdown);

                // Check if dropdown has proper attributes
                if (dropdown.hasAttribute('data-bs-toggle')) {
                    console.log(`✅ Dropdown ${index} has data-bs-toggle attribute`);
                } else {
                    console.log(`❌ Dropdown ${index} missing data-bs-toggle attribute`);
                }

                // Check if dropdown is visible
                const computedStyle = window.getComputedStyle(dropdown);
                if (computedStyle.display !== 'none' && computedStyle.opacity !== '0') {
                    console.log(`✅ Dropdown ${index} is visible`);
                } else {
                    console.log(`❌ Dropdown ${index} is hidden`);
                }
            });

            // Test dropdown menu visibility
            const dropdownMenus = document.querySelectorAll('.dropdown-menu');
            console.log('Found dropdown menus:', dropdownMenus.length);

            dropdownMenus.forEach((menu, index) => {
                console.log(`Dropdown menu ${index}:`, menu);
                const computedStyle = window.getComputedStyle(menu);
                console.log(`Dropdown menu ${index} computed style:`, {
                    display: computedStyle.display,
                    visibility: computedStyle.visibility,
                    opacity: computedStyle.opacity
                });
            });

            // Add click handlers for testing
            dropdowns.forEach((dropdown, index) => {
                dropdown.addEventListener('click', function() {
                    console.log(`Dropdown ${index} clicked`);

                    // Check if dropdown is showing
                    const dropdownElement = dropdown.closest('.dropdown');
                    if (dropdownElement.classList.contains('show')) {
                        console.log(`✅ Dropdown ${index} is showing`);
                    } else {
                        console.log(`❌ Dropdown ${index} is not showing`);
                    }
                });
            });

            console.log('=== Dropdown Fix Test Complete ===');
        });
    </script>
</body>
</html>
