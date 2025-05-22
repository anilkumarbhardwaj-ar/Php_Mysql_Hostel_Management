// Report generation and display functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range picker
    const dateRangePicker = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    // Show/hide custom date range inputs
    dateRangePicker.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
        }
    });

    // Initialize report type selector
    const reportTypeSelect = document.getElementById('reportType');
    const generateBtn = document.getElementById('generateBtn');
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const reportContainer = document.getElementById('reportContainer');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Generate report
    generateBtn.addEventListener('click', function() {
        generateReport();
    });

    // Export PDF
    exportPdfBtn.addEventListener('click', function() {
        exportReport('pdf');
    });

    // Export CSV
    exportCsvBtn.addEventListener('click', function() {
        exportReport('csv');
    });

    // Function to generate report
    function generateReport() {
        const reportType = reportTypeSelect.value;
        const dateRange = dateRangePicker.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Validate custom date range
        if (dateRange === 'custom' && (!startDate || !endDate)) {
            showError('Please select both start and end dates');
            return;
        }

        // Show loading spinner
        loadingSpinner.style.display = 'flex';
        reportContainer.style.display = 'none';

        // Prepare form data
        const formData = new FormData();
        formData.append('reportType', reportType);
        formData.append('dateRange', dateRange);
        if (dateRange === 'custom') {
            formData.append('startDate', startDate);
            formData.append('endDate', endDate);
        }

        // Send AJAX request
        fetch('ajax/reports/generate_report.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                showError(data.error);
            } else {
                displayReport(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while generating the report. Please try again.');
        })
        .finally(() => {
            loadingSpinner.style.display = 'none';
            reportContainer.style.display = 'block';
        });
    }

    // Function to export report
    function exportReport(format) {
        const reportType = reportTypeSelect.value;
        const dateRange = dateRangePicker.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Validate custom date range
        if (dateRange === 'custom' && (!startDate || !endDate)) {
            showError('Please select both start and end dates');
            return;
        }

        // Build URL with parameters
        let url = `ajax/reports/export_${format}.php?reportType=${reportType}&dateRange=${dateRange}`;
        if (dateRange === 'custom') {
            url += `&startDate=${startDate}&endDate=${endDate}`;
        }

        // Open in new window/tab
        window.open(url, '_blank');
    }

    // Function to display report
    function displayReport(data) {
        const { report, dateRange } = data;
        const { summary, ...details } = report;

        // Create report HTML
        let html = `
            <div class="report-header">
                <h2>${reportTypeSelect.options[reportTypeSelect.selectedIndex].text} Report</h2>
                <p>Period: ${formatDate(dateRange.start)} to ${formatDate(dateRange.end)}</p>
            </div>
            <div class="report-summary">
                <h3>Summary</h3>
                <div class="summary-grid">
        `;

        // Add summary items
        for (const [key, value] of Object.entries(summary)) {
            html += `
                <div class="summary-item">
                    <span class="label">${formatLabel(key)}</span>
                    <span class="value">${formatValue(value)}</span>
                </div>
            `;
        }

        html += `
                </div>
            </div>
            <div class="report-details">
                <h3>Details</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
        `;

        // Add table headers
        const firstDetail = Object.values(details)[0][0];
        for (const key in firstDetail) {
            html += `<th>${formatLabel(key)}</th>`;
        }

        html += `
                            </tr>
                        </thead>
                        <tbody>
        `;

        // Add table rows
        const detailData = Object.values(details)[0];
        detailData.forEach(row => {
            html += '<tr>';
            for (const value of Object.values(row)) {
                html += `<td>${formatValue(value)}</td>`;
            }
            html += '</tr>';
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        // Update report container
        reportContainer.innerHTML = html;
    }

    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Helper function to format label
    function formatLabel(label) {
        return label
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    // Helper function to format value
    function formatValue(value) {
        if (typeof value === 'number') {
            if (Number.isInteger(value)) {
                return value.toLocaleString();
            }
            return value.toFixed(2);
        }
        return value;
    }

    // Function to show error message
    function showError(message) {
        reportContainer.innerHTML = `
            <div class="alert alert-danger" role="alert">
                ${message}
            </div>
        `;
    }

    // Generate initial report
    generateReport();
}); 