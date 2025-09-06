<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dropdown Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: #f8f9fa;
        }

        .dropdown {
            position: relative !important;
            display: inline-block !important;
        }

        .dropdown-menu {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: all 0.3s ease !important;
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            z-index: 999999 !important;
            min-width: 200px !important;
            background: white !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown.show .dropdown-menu {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .dropdown-toggle {
            cursor: pointer !important;
            user-select: none !important;
            padding: 8px 16px !important;
            background: #007bff !important;
            color: white !important;
            border: none !important;
            border-radius: 5px !important;
        }

        .dropdown-item {
            display: block !important;
            width: 100% !important;
            padding: 12px 16px !important;
            clear: both !important;
            font-weight: 400 !important;
            color: #212529 !important;
            text-align: inherit !important;
            white-space: nowrap !important;
            background-color: transparent !important;
            border: 0 !important;
            transition: all 0.2s ease !important;
        }

        .dropdown-item:hover {
            color: #fff !important;
            text-decoration: none !important;
            background-color: #007bff !important;
        }

        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }

        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
        }

        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="mb-4">🔍 Dropdown Test Page</h1>

        <div class="debug-info">
            <h5>Debug Information</h5>
            <p>This page tests the dropdown functionality. Check the dropdowns below and the browser console for debug messages.</p>
            <div id="debug-status">
                <span class="status warning">Checking dropdown functionality...</span>
            </div>
        </div>

        <h3 class="mb-3">Test Dropdowns</h3>

        <!-- Test 1: Basic Dropdown -->
        <div class="mb-4">
            <h4>1. Basic Dropdown</h4>
            <div class="dropdown" id="basic-dropdown">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-2"></i>Basic User Menu
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Test 2: Notifications Dropdown -->
        <div class="mb-4">
            <h4>2. Notifications Dropdown</h4>
            <div class="dropdown notifications-dropdown" id="notifications-dropdown">
                <button class="dropdown-toggle notification-bell" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell me-2"></i>Notifications
                    <span class="badge bg-danger">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow notifications-dropdown-menu" style="width: 350px; max-height: 400px; overflow-y: auto;">
                    <div class="dropdown-header">
                        <h6 class="m-0">Notifications</h6>
                        <small class="text-muted">3 new notifications</small>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-user-md text-primary"></i>
                        </div>
                        <div class="notification-details">
                            <div class="notification-title">New Diagnosis Submitted</div>
                            <div class="notification-message">Dr. Smith submitted a new diagnosis for patient John Doe</div>
                            <div class="notification-time">2 minutes ago</div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-clipboard-check text-success"></i>
                        </div>
                        <div class="notification-details">
                            <div class="notification-title">Review Completed</div>
                            <div class="notification-message">Your review has been completed and approved</div>
                            <div class="notification-time">15 minutes ago</div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-microphone text-info"></i>
                        </div>
                        <div class="notification-details">
                            <div class="notification-title">Voice Transcription Ready</div>
                            <div class="notification-message">Voice transcription is ready for review</div>
                            <div class="notification-time">1 hour ago</div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-footer">
                        <a href="#" class="text-decoration-none">View all notifications</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test 3: User Dropdown (from master layout) -->
        <div class="mb-4">
            <h4>3. User Dropdown (Master Layout Style)</h4>
            <div class="dropdown user-dropdown" id="user-dropdown">
                <a class="btn btn-sm d-flex align-items-center gap-2 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 500; border-radius: 25px; backdrop-filter: blur(10px);">
                    <img src="https://ui-avatars.com/api/?name=John+Doe&background=007bff&color=fff" class="rounded-circle" width="24" height="24" alt="User Avatar">
                    <span>John Doe</span>
                    <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="#">
                            <i class="fas fa-user-edit"></i>Edit Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="#">
                            <i class="fas fa-cog"></i>Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="#">
                            <i class="fas fa-bell"></i>Notifications
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 w-100 text-start">
                                <i class="fas fa-sign-out-alt"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <div class="debug-info">
            <h5>Instructions</h5>
            <ol>
                <li>Click on each dropdown to test if it opens</li>
                <li>Check the browser console for debug messages</li>
                <li>If dropdowns don't work, check for JavaScript errors</li>
                <li>Verify that Bootstrap is loaded properly</li>
            </ol>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Dropdown Test Initialized ===');

            // Check if Bootstrap is loaded
            if (typeof bootstrap === 'undefined') {
                console.error('❌ Bootstrap is not loaded!');
                updateStatus('Bootstrap Status', 'Not Loaded', false);
            } else {
                console.log('✅ Bootstrap is loaded');
                updateStatus('Bootstrap Status', 'Loaded', true);
            }

            // Check dropdown elements
            const dropdowns = document.querySelectorAll('.dropdown');
            console.log('Found dropdowns:', dropdowns.length);

            dropdowns.forEach((dropdown, index) => {
                console.log(`Dropdown ${index}:`, dropdown);

                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');

                console.log(`Dropdown ${index} - Toggle:`, !!toggle);
                console.log(`Dropdown ${index} - Menu:`, !!menu);

                if (toggle) {
                    // Add click event listener
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const dropdownInstance = bootstrap.Dropdown.getInstance(toggle);
                        if (dropdownInstance) {
                            dropdownInstance.toggle();
                            console.log(`Dropdown ${index} toggled`);
                        } else {
                            console.error(`Dropdown ${index} instance not found`);
                        }
                    });
                }
            });

            // Test manual dropdown show/hide
            setTimeout(() => {
                console.log('=== Testing Manual Dropdown Control ===');

                const basicDropdown = document.getElementById('basic-dropdown');
                if (basicDropdown) {
                    const toggle = basicDropdown.querySelector('.dropdown-toggle');
                    if (toggle) {
                        try {
                            const instance = new bootstrap.Dropdown(toggle);
                            console.log('✅ Basic dropdown initialized');

                            // Test showing
                            setTimeout(() => {
                                instance.show();
                                console.log('✅ Basic dropdown shown');
                            }, 1000);

                            // Test hiding
                            setTimeout(() => {
                                instance.hide();
                                console.log('✅ Basic dropdown hidden');
                            }, 2000);
                        } catch (e) {
                            console.error('❌ Error initializing basic dropdown:', e);
                        }
                    }
                }
            }, 1000);

            // Update status
            function updateStatus(label, status, isSuccess) {
                const statusDiv = document.getElementById('debug-status');
                const statusClass = isSuccess ? 'success' : 'error';
                statusDiv.innerHTML = `
                    <div class="status ${statusClass}">${label}: ${status}</div>
                `;
            }

            // Check for any CSS conflicts
            setTimeout(() => {
                console.log('=== Checking CSS Conflicts ===');

                const dropdownMenu = document.querySelector('.dropdown-menu');
                if (dropdownMenu) {
                    const computedStyle = window.getComputedStyle(dropdownMenu);
                    console.log('Dropdown menu computed style:');
                    console.log('- display:', computedStyle.display);
                    console.log('- visibility:', computedStyle.visibility);
                    console.log('- opacity:', computedStyle.opacity);
                    console.log('- position:', computedStyle.position);
                    console.log('- z-index:', computedStyle.zIndex);
                }
            }, 1500);
        });
    </script>
</body>
</html>
