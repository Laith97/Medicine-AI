document.addEventListener('DOMContentLoaded', function () {
    // Initialize all charts
    initializeCharts();

    // Set up event listeners for filters
    setupFilters();

    // Set up table sorting and pagination
    setupTableFunctionality();

    // Set up export functionality
    setupExportFunctionality();

    // Set up patient modal functionality (using event delegation)
    setupPatientModal();
});

// Main chart initialization function
function initializeCharts() {
    // Add debug logs for variable checking

    try {
        // Check if global variables are properly set
        if (typeof chartLabels === 'undefined' || !Array.isArray(chartLabels)) {
            showChartFallback();
            return;
        }
        if (typeof chartData === 'undefined' || !Array.isArray(chartData)) {
            showChartFallback();
            return;
        }
        if (typeof records === 'undefined' || !Array.isArray(records)) {
            showChartFallback();
            return;
        }

        // Check if ApexCharts is loaded
        if (typeof ApexCharts === 'undefined') {
            showChartFallback();
            return;
        }

        // Initialize the main charts with error handling - each chart fails independently
        const chartPromises = [
            initializePatientManagementChart().catch(() => showChartFallbackFor('patientManagementChart')),
            initializeVisitsTimelineChart().catch(() => showChartFallbackFor('visitsTimelineChart')),
            initializeDemographicsChart().catch(() => showChartFallbackFor('demographicsChart')),
            initializeAgeDistributionChart().catch(() => showChartFallbackFor('ageDistributionChart'))
        ];

        Promise.all(chartPromises).then(() => {
        }).catch(error => {
            // Only show general fallback if all charts fail
            console.error('Chart initialization error:', error);
        });

    } catch (error) {
        showChartFallback();
    }
}

// Fallback function for when charts fail to load
function showChartFallback() {
    const chartContainers = document.querySelectorAll('[id$="Chart"]');
    chartContainers.forEach(container => {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <p>Chart temporarily unavailable</p>
                <small>Please refresh the page</small>
            </div>
        `;
    });
}

// Individual chart fallback function
function showChartFallbackFor(chartId) {
    const container = document.getElementById(chartId);
    if (container) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <p>Chart temporarily unavailable</p>
                <small>Please refresh the page</small>
            </div>
        `;
    }
}

// Initialize the patient management over time chart
function initializePatientManagementChart() {
    const labels = chartLabels;
    const data = chartData;
    const chartElement = document.getElementById('patientManagementChart');
    if (!chartElement) return Promise.reject('Patient Management chart element not found');

    // Validate data
    if (!Array.isArray(labels) || !Array.isArray(data)) {
        throw new Error('Invalid chart data format');
    }
    if (labels.length === 0 || data.length === 0) {
        // Handle empty data gracefully - show a message instead of throwing an error
        chartElement.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <p>No appointment data available for the selected period</p>
                <small>Charts will appear once you have appointments</small>
            </div>
        `;
        return Promise.resolve();
    }
    if (labels.length !== data.length) {
        throw new Error('Chart labels and data arrays have different lengths');
    }
    // Validate that data contains numbers
    if (!data.every(item => typeof item === 'number' && !isNaN(item))) {
        throw new Error('Chart data contains non-numeric values');
    }

    const options = {
        series: [{
            name: 'Patient Management',
            data: data
        }],
        chart: {
            type: 'area',
            height: 300,
            toolbar: {
                show: false
            },
            animations: {
                enabled: true
            }
        },
        colors: ['#DE6262'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                opacityFrom: 0.1,
                opacityTo: 0.3,
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: labels,
            labels: {
                style: {
                    colors: '#6c757d'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6c757d'
                }
            },
            min: 0,
            tickAmount: 5
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.1)',
            xaxis: {
                lines: {
                    show: false
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            }
        },
        tooltip: {
            theme: 'dark',
            style: {
                background: 'rgba(44, 62, 80, 0.9)',
                color: '#fff'
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 250
                },
                xaxis: {
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '10px'
                        }
                    }
                }
            }
        }]
    };

    const chart = new ApexCharts(chartElement, options);
    return chart.render();
}

// Initialize the visits timeline chart
function initializeVisitsTimelineChart() {
    const labels = chartLabels;
    const data = chartData;
    const chartElement = document.getElementById('visitsTimelineChart');
    if (!chartElement) return Promise.reject('Visits timeline chart element not found');

    // Handle empty data gracefully
    if (labels.length === 0 || data.length === 0) {
        chartElement.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                <p>No visit data available for the selected period</p>
                <small>Timeline will appear once you have patient visits</small>
            </div>
        `;
        return Promise.resolve();
    }

    const options = {
        series: [{
            name: 'New Patients',
            data: data.map((val, i) => Math.round(val * 0.7)) // Simulate new vs. returning
        }, {
            name: 'Return Visits',
            data: data.map((val, i) => Math.round(val * 0.3)) // Simulate new vs. returning
        }],
        chart: {
            type: 'line',
            height: 250,
            toolbar: {
                show: false
            }
        },
        colors: ['#3498db', '#e74c3c'],
        stroke: {
            curve: 'smooth',
            width: 2
        },
        markers: {
            size: 4,
            colors: ['#3498db', '#e74c3c'],
            strokeColors: '#fff',
            strokeWidth: 2
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: labels,
            labels: {
                style: {
                    colors: '#6c757d'
                }
            },
            title: {
                text: 'Date',
                style: {
                    color: '#6c757d'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6c757d'
                }
            },
            title: {
                text: 'Number of Patients',
                style: {
                    color: '#6c757d'
                }
            },
            min: 0
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            markers: {
                width: 12,
                height: 12,
                radius: 2
            },
            itemMargin: {
                horizontal: 10,
                vertical: 5
            }
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.1)'
        },
        tooltip: {
            theme: 'dark',
            style: {
                background: 'rgba(44, 62, 80, 0.9)',
                color: '#fff'
            },
            y: {
                formatter: function(value) {
                    return value + ' patients';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 200
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                }
            }
        }]
    };

    const chart = new ApexCharts(chartElement, options);
    return chart.render();
}

// Initialize the demographics pie chart
function initializeDemographicsChart() {
    const records = window.records || [];
    const chartElement = document.getElementById('demographicsChart');
    if (!chartElement) return Promise.reject('Demographics chart element not found');

    // Count male and female patients
    const maleCount = records.filter(r => r.gender === 'male').length;
    const femaleCount = records.filter(r => r.gender === 'female').length;
    const total = maleCount + femaleCount;

    if (total === 0) {
        chartElement.innerHTML = '<div class="text-center text-muted py-3">No data available</div>';
        return Promise.resolve();
    }

    const options = {
        series: [maleCount, femaleCount],
        chart: {
            type: 'pie',
            height: 250,
            toolbar: {
                show: false
            }
        },
        labels: ['Male', 'Female'],
        colors: ['#3498db', '#e74c3c'],
        legend: {
            position: 'bottom',
            markers: {
                width: 12,
                height: 12,
                radius: 2
            },
            itemMargin: {
                horizontal: 10,
                vertical: 5
            }
        },
        tooltip: {
            theme: 'dark',
            style: {
                background: 'rgba(44, 62, 80, 0.9)',
                color: '#fff'
            },
            y: {
                formatter: function(value) {
                    const percentage = Math.round((value / total) * 100);
                    return value + ' patients (' + percentage + '%)';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 200
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                }
            }
        }]
    };

    const chart = new ApexCharts(chartElement, options);
    return chart.render();
}

// Initialize the age distribution chart
function initializeAgeDistributionChart() {
    const records = window.records || [];
    const chartElement = document.getElementById('ageDistributionChart');
    if (!chartElement) return Promise.reject('Age distribution chart element not found');

    // Group patients by age ranges
    const ageGroups = {
        '0-18': 0,
        '19-35': 0,
        '36-50': 0,
        '51-65': 0,
        '66+': 0
    };

    records.forEach(record => {
        const age = parseInt(record.age);
        if (isNaN(age)) return;

        if (age <= 18) ageGroups['0-18']++;
        else if (age <= 35) ageGroups['19-35']++;
        else if (age <= 50) ageGroups['36-50']++;
        else if (age <= 65) ageGroups['51-65']++;
        else ageGroups['66+']++;
    });

    const categories = Object.keys(ageGroups);
    const data = Object.values(ageGroups);

    const options = {
        series: [{
            name: 'Patients',
            data: data
        }],
        chart: {
            type: 'bar',
            height: 250,
            toolbar: {
                show: false
            }
        },
        colors: [
            'rgba(46, 204, 113, 0.7)',
            'rgba(52, 152, 219, 0.7)',
            'rgba(155, 89, 182, 0.7)',
            'rgba(241, 196, 15, 0.7)',
            'rgba(231, 76, 60, 0.7)'
        ],
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '60%'
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    colors: '#6c757d'
                }
            },
            title: {
                text: 'Age Groups',
                style: {
                    color: '#6c757d'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6c757d'
                }
            },
            title: {
                text: 'Number of Patients',
                style: {
                    color: '#6c757d'
                }
            },
            min: 0,
            tickAmount: 5
        },
        grid: {
            borderColor: 'rgba(0, 0, 0, 0.1)'
        },
        tooltip: {
            theme: 'dark',
            style: {
                background: 'rgba(44, 62, 80, 0.9)',
                color: '#fff'
            },
            y: {
                formatter: function(value) {
                    return value + ' patients';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 200
                },
                plotOptions: {
                    bar: {
                        columnWidth: '80%'
                    }
                },
                xaxis: {
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '10px'
                        }
                    }
                }
            }
        }]
    };

    const chart = new ApexCharts(chartElement, options);
    return chart.render();
}

// Set up filter functionality
function setupFilters() {
    const dateRangeSelect = document.getElementById('date-range-select');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const customDateRangeFields = document.querySelectorAll('.custom-date-range');
    const filterForm = document.getElementById('stats-filter-form');

    // Show/hide custom date range fields based on selection
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRangeFields.forEach(field => field.style.display = 'block');
            } else {
                customDateRangeFields.forEach(field => field.style.display = 'none');
            }
        });
    }

    // Set default dates for custom range
    if (dateFrom && dateTo) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        dateTo.valueAsDate = today;
        dateFrom.valueAsDate = thirtyDaysAgo;
    }

    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Here you would typically make an AJAX request to get filtered data
            // For this demo, we'll just show a success message
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Applied!</strong> Data has been updated.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });

        // Handle filter form reset
        filterForm.addEventListener('reset', function() {
            // Reset custom date range visibility
            customDateRangeFields.forEach(field => field.style.display = 'none');
            dateRangeSelect.value = '30';

            // Show reset message
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Reset!</strong> Showing default data.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
    }

    // Set up refresh stats button
    const refreshStatsBtn = document.getElementById('refresh-stats');
    if (refreshStatsBtn) {
        refreshStatsBtn.addEventListener('click', function() {
            // Show loading spinner
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...';
            this.disabled = true;

            // Simulate refresh delay
            setTimeout(() => {
                // Reset button
                this.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Refresh';
                this.disabled = false;

                // Show success message
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <strong>Data Refreshed!</strong> Statistics are now up-to-date.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(toast);

                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(toast);
                    bsAlert.close();
                }, 3000);
            }, 1500);
        });
    }
}

// Set up table sorting and pagination
function setupTableFunctionality() {
    const table = document.getElementById('patients-table');
    const searchInput = document.getElementById('patient-search');
    const searchBtn = document.getElementById('search-btn');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const currentPageSpan = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const showingCountSpan = document.getElementById('showing-count');

    if (!table) return;

    // Variables for pagination
    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredRows = Array.from(table.querySelectorAll('tbody tr'));
    let totalPages = Math.ceil(filteredRows.length / rowsPerPage);

    // Update pagination display
    function updatePagination() {
        if (!currentPageSpan || !totalPagesSpan || !showingCountSpan) return;

        currentPageSpan.textContent = currentPage;
        totalPagesSpan.textContent = totalPages;

        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, filteredRows.length);
        showingCountSpan.textContent = filteredRows.length > 0 ? `${startIdx + 1}-${endIdx}` : '0';

        // Enable/disable pagination buttons
        if (prevPageBtn) prevPageBtn.disabled = currentPage === 1;
        if (nextPageBtn) nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
    }

    // Display rows for current page
    function displayRows() {
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));

        // Hide all rows
        allRows.forEach(row => row.style.display = 'none');

        // Show only rows for current page
        const startIdx = (currentPage - 1) * rowsPerPage;
        const endIdx = Math.min(startIdx + rowsPerPage, filteredRows.length);

        for (let i = startIdx; i < endIdx; i++) {
            filteredRows[i].style.display = '';
        }

        updatePagination();
    }

    // Filter rows based on search input
    function filterRows(searchTerm) {
        const tbody = table.querySelector('tbody');
        const allRows = Array.from(tbody.querySelectorAll('tr'));

        if (!searchTerm) {
            filteredRows = allRows;
        } else {
            searchTerm = searchTerm.toLowerCase();
            filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
        }

        totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        currentPage = 1; // Reset to first page after filtering

        displayRows();
    }

    // Sort table by column
    function sortTable(column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // Sort rows
        rows.sort((a, b) => {
            let aValue, bValue;

            switch (column) {
                case 'name':
                    aValue = a.cells[0].textContent;
                    bValue = b.cells[0].textContent;
                    break;
                case 'age':
                    aValue = parseInt(a.cells[1].textContent) || 0;
                    bValue = parseInt(b.cells[1].textContent) || 0;
                    break;
                case 'gender':
                    aValue = a.cells[2].textContent.trim();
                    bValue = b.cells[2].textContent.trim();
                    break;
                case 'visits':
                    aValue = parseInt(a.getAttribute('data-visits')) || 0;
                    bValue = parseInt(b.getAttribute('data-visits')) || 0;
                    break;
                case 'last-visit':
                    aValue = parseInt(a.getAttribute('data-last-visit')) || 0;
                    bValue = parseInt(b.getAttribute('data-last-visit')) || 0;
                    break;
                default:
                    return 0;
            }

            if (direction === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        // Reappend rows in sorted order
        rows.forEach(row => tbody.appendChild(row));

        // Update filtered rows and display
        filteredRows = Array.from(tbody.querySelectorAll('tr'));
        displayRows();
    }

    // Set up event listeners

    // Search functionality
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            filterRows(searchInput.value);
        });

        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                filterRows(this.value);
            }
        });
    }

    // Pagination
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayRows();
            }
        });
    }

    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                displayRows();
            }
        });
    }

    // Sorting
    const sortLinks = table.querySelectorAll('.sort-link');
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const column = this.getAttribute('data-sort');
            const currentDirection = this.getAttribute('data-direction') || 'desc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

            // Update direction attribute
            sortLinks.forEach(l => l.setAttribute('data-direction', ''));
            this.setAttribute('data-direction', newDirection);

            // Update sort icons
            sortLinks.forEach(l => {
                l.querySelector('i').className = 'fas fa-sort';
            });
            this.querySelector('i').className = newDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';

            // Sort the table
            sortTable(column, newDirection);
        });
    });

    // Initialize display
    updatePagination();
    displayRows();

    // Set up patient filter functionality
    setupPatientFilters();
}

// Set up patient filters
function setupPatientFilters() {
    const dateRangeSelect = document.getElementById('patient-date-range');
    const dateFrom = document.getElementById('patient-date-from');
    const dateTo = document.getElementById('patient-date-to');
    const customDateFields = document.querySelectorAll('.patient-custom-date');
    const filterForm = document.getElementById('patient-filter-form');
    const genderFilter = document.getElementById('patient-gender-filter');
    const ageFilter = document.getElementById('patient-age-filter');
    const visitFilter = document.getElementById('patient-visit-filter');

    // Show/hide custom date range fields based on selection
    if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateFields.forEach(field => field.style.display = 'block');
            } else {
                customDateFields.forEach(field => field.style.display = 'none');
            }
        });
    }

    // Set default dates for custom range
    if (dateFrom && dateTo) {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        dateTo.valueAsDate = today;
        dateFrom.valueAsDate = thirtyDaysAgo;
    }

    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const table = document.getElementById('patients-table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Apply filters
            rows.forEach(row => {
                let showRow = true;

                // Gender filter
                if (genderFilter && genderFilter.value !== 'all') {
                    const gender = row.cells[2].textContent.trim().toLowerCase();
                    if (!gender.includes(genderFilter.value.toLowerCase())) {
                        showRow = false;
                    }
                }

                // Age filter
                if (showRow && ageFilter && ageFilter.value !== 'all') {
                    const age = parseInt(row.cells[1].textContent) || 0;
                    const [minAge, maxAge] = ageFilter.value.split('-');

                    if (minAge && maxAge) {
                        if (age < parseInt(minAge) || age > parseInt(maxAge)) {
                            showRow = false;
                        }
                    } else if (minAge && minAge.includes('+')) {
                        const min = parseInt(minAge);
                        if (age < min) {
                            showRow = false;
                        }
                    }
                }

                // Visit count filter
                if (showRow && visitFilter && visitFilter.value !== 'all') {
                    const visitCount = parseInt(row.getAttribute('data-visits')) || 0;

                    if (visitFilter.value === '1' && visitCount !== 1) {
                        showRow = false;
                    } else if (visitFilter.value === 'multiple' && visitCount <= 1) {
                        showRow = false;
                    }
                }

                // Date range filter
                if (showRow && dateRangeSelect) {
                    const lastVisitTimestamp = parseInt(row.getAttribute('data-last-visit')) || 0;
                    const lastVisitDate = new Date(lastVisitTimestamp * 1000);
                    const today = new Date();

                    if (dateRangeSelect.value === 'custom') {
                        // Custom date range
                        if (dateFrom && dateTo) {
                            const fromDate = new Date(dateFrom.value);
                            const toDate = new Date(dateTo.value);
                            toDate.setHours(23, 59, 59, 999); // End of day

                            if (lastVisitDate < fromDate || lastVisitDate > toDate) {
                                showRow = false;
                            }
                        }
                    } else if (dateRangeSelect.value !== 'all') {
                        // Predefined date range
                        const days = parseInt(dateRangeSelect.value);
                        const cutoffDate = new Date();
                        cutoffDate.setDate(today.getDate() - days);

                        if (lastVisitDate < cutoffDate) {
                            showRow = false;
                        }
                    }
                }

                // Show or hide row
                row.style.display = showRow ? '' : 'none';
            });

            // Show success message
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Applied!</strong> Patient list has been filtered.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });

        // Handle filter form reset
        filterForm.addEventListener('reset', function() {
            // Reset custom date range visibility
            customDateFields.forEach(field => field.style.display = 'none');
            dateRangeSelect.value = 'all';

            // Reset table to show all rows
            const table = document.getElementById('patients-table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => row.style.display = '');
            }

            // Show reset message
            const toast = document.createElement('div');
            toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <strong>Filters Reset!</strong> Showing all patients.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }, 3000);
        });
    }
}

// Set up patient modal functionality
function setupPatientModal() {
    // Get all patient records from PHP
    const allRecords = [];

    // Patient modal elements
    const patientModal = document.getElementById('patientModal');
    const patientNameEl = document.querySelector('.patient-name');
    const patientAgeEl = document.querySelector('.patient-age');
    const patientGenderEl = document.querySelector('.patient-gender');
    const visitHistoryBody = document.getElementById('visit-history-body');
    const visitDetailsSection = document.getElementById('visit-details-section');
    const visitDetailsContent = document.getElementById('visit-details-content');
    const newVisitBtn = document.getElementById('new-visit-btn');

    if (!patientModal) return;

    // Use event delegation for patient view buttons - this ensures it works after filtering/pagination
    document.addEventListener('click', function(e) {
        // Handle patient modal opening
        if (e.target.closest('.btn-view-patient')) {
            e.preventDefault(); // Prevent any default behavior

            const btn = e.target.closest('.btn-view-patient');
            const patientKey = btn.getAttribute('data-patient-key');
            const patientName = btn.getAttribute('data-patient-name');
            const patientAge = btn.getAttribute('data-patient-age');
            const patientGender = btn.getAttribute('data-patient-gender');

            // Set patient info in modal
            if (patientNameEl) patientNameEl.textContent = patientName;
            if (patientAgeEl) patientAgeEl.textContent = patientAge;
            if (patientGenderEl) patientGenderEl.textContent = patientGender.charAt(0).toUpperCase() + patientGender.slice(1);

            // Set new visit button link with the correct patient key
            if (newVisitBtn) {
                newVisitBtn.href = `{{ route('ai.ask-ai') }}?patient_key=${encodeURIComponent(patientKey)}`;
            }

            // Clear previous visit history
            if (visitHistoryBody) visitHistoryBody.innerHTML = '';

            // Hide visit details section
            if (visitDetailsSection) visitDetailsSection.style.display = 'none';

            // Find all visits for this patient
            const patientVisits = allRecords.filter(record => {
                const recordKey = record.patient_key || (record.name + '-' + record.age + '-' + record.gender);
                return recordKey === patientKey;
            });

            // First, sort chronologically to assign correct visit numbers
            const sortedForNumbering = [...patientVisits].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            // Create a mapping of visit ID to visit number
            const visitNumberMap = {};
            sortedForNumbering.forEach((visit, index) => {
                visitNumberMap[visit.id] = index + 1;
            });

            // Now sort for display (newest first)
            patientVisits.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            // Populate visit history table
            patientVisits.forEach((visit) => {
                const visitNumber = visitNumberMap[visit.id]; // Correct chronological visit number
                const visitDate = new Date(visit.created_at);

                // Check if there are multiple visits on the same day
                const sameDay = patientVisits.filter(v => {
                    const vDate = new Date(v.created_at);
                    return vDate.toDateString() === visitDate.toDateString();
                }).length > 1;

                // Include time if there are multiple visits on the same day
                const formattedDate = visitDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    ...(sameDay && {
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                });

                // Get symptoms (if available)
                let symptomsText = 'N/A';
                if (visit.symptoms) {
                    try {
                        const symptoms = JSON.parse(visit.symptoms);
                        if (Array.isArray(symptoms) && symptoms.length > 0) {
                            symptomsText = symptoms.join(', ');
                        } else if (typeof symptoms === 'string') {
                            symptomsText = symptoms;
                        }
                    } catch (e) {
                        symptomsText = visit.symptoms;
                    }
                }

                // Truncate symptoms if too long
                if (symptomsText.length > 50) {
                    symptomsText = symptomsText.substring(0, 50) + '...';
                }

                // Create row
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${visitNumber}</td>
                    <td>${formattedDate}</td>
                    <td>${symptomsText}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary-custom view-visit-details" data-visit-id="${visit.id}">
                                            <i class="fas fa-file-medical me-1"></i> Details
                                        </button>
                                    </td>
                `;

                visitHistoryBody.appendChild(row);
            });

            // Show the modal using Bootstrap's JavaScript API
            try {
                const modal = new bootstrap.Modal(patientModal, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                modal.show();

                // Force modal to appear above everything with extreme z-index
                setTimeout(() => {
                    // Set modal z-index
                    patientModal.style.zIndex = '999999';
                    patientModal.style.position = 'fixed';

                    // Set backdrop z-index
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.style.zIndex = '999998';
                    }

                    // Move modal to end of body to escape any stacking contexts
                    document.body.appendChild(patientModal);
                }, 50);

            } catch (error) {
                // Fallback: try to show modal using jQuery if available
                if (typeof $ !== 'undefined') {
                    $(patientModal).modal('show');
                }
            }
        }

        // Handle visit details buttons (also using event delegation)
        if (e.target.closest('.view-visit-details')) {
            const btn = e.target.closest('.view-visit-details');
            const visitId = btn.getAttribute('data-visit-id');
            const visit = allRecords.find(record => record.id == visitId);

            if (!visit) return;

            // Show visit details section
            if (visitDetailsSection) visitDetailsSection.style.display = 'block';

            // Format visit details
            let detailsHTML = `
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Visit #${visit.visit_number || '1'} - ${new Date(visit.created_at).toLocaleDateString()}</strong>
                    </div>
                    <div class="card-body">
            `;

            // Add symptoms with better formatting
            detailsHTML += '<h6 class="mb-2">Symptoms:</h6>';
            if (visit.symptoms) {
                try {
                    let symptoms = JSON.parse(visit.symptoms);

                            // Handle different formats of symptoms data
                            if (Array.isArray(symptoms) && symptoms.length > 0) {
                                // Check if symptoms are IDs or actual symptom names
                                const areNumeric = symptoms.every(s => !isNaN(parseInt(s)));

                                if (areNumeric) {
                                    // These are likely symptom IDs - we need to convert them to names
                                    // Since we don't have direct access to the symptom names here,
                                    // we'll display a more user-friendly message
                                    detailsHTML += `
                                        <div class="alert alert-info mb-3">
                                            <i class="fas fa-info-circle me-2"></i>
                                            ${symptoms.length} symptom(s) recorded. View full details in patient record.
                                        </div>
                                    `;
                                } else {
                                    // These are actual symptom names
                                    detailsHTML += '<div class="symptom-tags mb-3">';
                                    symptoms.forEach(symptom => {
                                        detailsHTML += `<span class="symptom-tag">${symptom}</span>`;
                                    });
                                    detailsHTML += '</div>';
                                }
                            } else if (typeof symptoms === 'string') {
                                detailsHTML += `<p class="mb-3">${symptoms}</p>`;
                            } else {
                                detailsHTML += '<p class="mb-3">No symptoms recorded</p>';
                            }
                        } catch (e) {
                            // If we can't parse the JSON, display as plain text
                            // But first check if it looks like a list of numbers
                            if (/^\d+(\s*,\s*\d+)*$/.test(visit.symptoms)) {
                                detailsHTML += `
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Multiple symptoms recorded. View full details in patient record.
                                    </div>
                                `;
                            } else {
                                detailsHTML += `<p class="mb-3">${visit.symptoms}</p>`;
                            }
                        }
                    } else {
                        detailsHTML += '<p class="mb-3">No symptoms recorded</p>';
                    }

                    // Add test results if available
                    if (visit.test_results) {
                        detailsHTML += '<h6 class="mb-2">Test Results:</h6>';
                        detailsHTML += `<p class="mb-3">${visit.test_results}</p>`;
                    }

                    // Add AI analysis if available with professional formatting
                    if (visit.ai_response) {
                        detailsHTML += '<h6 class="mb-2">AI Analysis:</h6>';
                        detailsHTML += '<div class="ai-response mb-4">' + formatAIResponse(visit.ai_response) + '</div>';
                    }

                    // Add notes if available
                    if (visit.notes) {
                        detailsHTML += '<h6 class="mb-2">Notes:</h6>';
                        detailsHTML += `<p class="mb-3">${visit.notes}</p>`;
                    }

                    // Close card
                    detailsHTML += `
                            </div>
                        </div>
                    `;

            // Set visit details content
            if (visitDetailsContent) visitDetailsContent.innerHTML = detailsHTML;

            // Scroll to visit details
            visitDetailsSection.scrollIntoView({ behavior: 'smooth' });
        }
    });
}

// Set up export functionality
function setupExportFunctionality() {
    const exportCsvBtn = document.getElementById('export-csv');
    const exportPdfBtn = document.getElementById('export-pdf');

    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportTableToCSV('patient_data.csv');
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportToPDF();
        });
    }

    // Export table to CSV
    function exportTableToCSV(filename) {
        const table = document.getElementById('patient-management-table');
        if (!table) return;

        const rows = table.querySelectorAll('tr');
        const csvContent = [];

        // Get headers
        const headers = [];
        const headerCells = rows[0].querySelectorAll('th');
        headerCells.forEach(cell => {
            // Get text without the sort icon
            let headerText = cell.textContent.replace(/[▲▼]/g, '').trim();
            if (headerText.includes('ID')) headerText = 'ID';
            if (headerText.includes('Patient Name')) headerText = 'Patient Name';
            if (headerText.includes('Age')) headerText = 'Age';
            if (headerText.includes('Gender')) headerText = 'Gender';
            if (headerText.includes('Visit')) headerText = 'Visit Number';
            if (headerText.includes('Date')) headerText = 'Date';

            headers.push(headerText);
        });

        // Remove the Actions column
        headers.pop();
        csvContent.push(headers.join(','));

        // Get data rows
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.querySelectorAll('td');
            const rowData = [];

            // Skip the Actions column
            for (let j = 0; j < cells.length - 1; j++) {
                let cellText = cells[j].textContent.trim();

                // Clean up ID column
                if (j === 0) cellText = cellText.replace('#', '');

                // Clean up Gender column
                if (j === 3) cellText = cellText.replace(/\s+/g, '');

                // Add quotes if the cell contains commas
                if (cellText.includes(',')) {
                    cellText = `"${cellText}"`;
                }

                rowData.push(cellText);
            }

            csvContent.push(rowData.join(','));
        }

        // Create and download CSV file
        const csvData = csvContent.join('\n');
        const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Export dashboard to PDF
    function exportToPDF() {
        // Show loading message
        const loadingToast = document.createElement('div');
        loadingToast.className = 'alert alert-info position-fixed';
        loadingToast.style.top = '20px';
        loadingToast.style.right = '20px';
        loadingToast.style.zIndex = '9999';
        loadingToast.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generating PDF...';
        document.body.appendChild(loadingToast);

        setTimeout(() => {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');

                // Add title
                doc.setFontSize(18);
                doc.text('Medical Dashboard Report', 105, 15, { align: 'center' });

                // Add date
                doc.setFontSize(12);
                doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 105, 25, { align: 'center' });

                // Add content
                doc.setFontSize(10);
                doc.text('Dashboard Statistics', 20, 40);

                // Add some basic stats
                const stats = [
                    'Total Patients: ' + ([]),
                    'Weekly Cases: ' + (0),
                    'New Patients (30 days): ' + (0),
                ];

                stats.forEach((stat, index) => {
                    doc.text(stat, 20, 50 + (index * 10));
                });

                // Save the PDF
                doc.save('dashboard_report.pdf');

                // Remove loading message
                document.body.removeChild(loadingToast);

                // Show success message
                const successToast = document.createElement('div');
                successToast.className = 'alert alert-success position-fixed';
                successToast.style.top = '20px';
                successToast.style.right = '20px';
                successToast.style.zIndex = '9999';
                successToast.innerHTML = '<i class="fas fa-check-circle me-2"></i> PDF exported successfully!';
                document.body.appendChild(successToast);

                setTimeout(() => {
                    document.body.removeChild(successToast);
                }, 3000);

            } catch (error) {

                // Remove loading message
                if (document.body.contains(loadingToast)) {
                    document.body.removeChild(loadingToast);
                }

                // Show error message
                const errorToast = document.createElement('div');
                errorToast.className = 'alert alert-danger position-fixed';
                errorToast.style.top = '20px';
                errorToast.style.right = '20px';
                errorToast.style.zIndex = '9999';
                errorToast.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> PDF export failed. Please try again.';
                document.body.appendChild(errorToast);

                setTimeout(() => {
                    document.body.removeChild(errorToast);
                }, 3000);
            }
        }, 1000);
    }
}

// Format AI response for display
function formatAIResponse(response) {
    if (!response) return 'No analysis available';

    // Basic formatting - you can enhance this based on your AI response structure
    return response.replace(/\n/g, '<br>');
}
