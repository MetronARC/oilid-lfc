<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>

<!-- Add Bootstrap JS and Popper.js CDN -->
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<div class="section">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-12 text-center">
                <a href="/" class="logo mb-4 d-inline-block">
                    <img src="img/oilid.png" alt="" style="width: 75px; height: 50px;" />
                </a>
            </div>
        </div>
        <div class="row full-height justify-content-center">
            <div class="col-12 text-center align-self-center py-5">
                <div class="section pb-5 pt-5 pt-sm-2 text-center">
                    <div class="card-3d-wrap mx-auto">
                        <div class="card-3d-wrapper">
                            <div class="card-front">
                                <div class="center-wrap">
                                    <div class="section text-center">
                                        <div class="nav-links mb-4">
                                            <a href="inspect" class="nav-link">Inspect</a>
                                            <a href="query" class="nav-link active">Query</a>
                                            <a href="inspector/logout" class="nav-link">Logout</a>
                                        </div>
                                        <h4 class="mb-4 pb-3">Query Item</h4>
                                        <div id="nfc-container" class="form-group">
                                            <div class="nfc-message">
                                                <div class="loading-spinner">
                                                    <div class="spinner"></div>
                                                    <p>Waiting for RFID card...</p>
                                                </div>
                                                <div id="nfc-status" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #2a2b38; color: #c4c3ca;">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="itemModalLabel" style="color: #ffeba7;">Item Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="itemImage" src="" alt="Item Image" class="img-fluid mb-4" style="max-height: 200px; width: auto;">
                <div class="item-details">
                    <div class="mb-3">
                        <span class="detail-label" style="color: #ffeba7;">Item Name:</span>
                        <h5 id="itemName" class="d-inline ms-2" style="color: #c4c3ca;"></h5>
                    </div>
                    <div>
                        <span class="detail-label" style="color: #ffeba7;">Item Type:</span>
                        <p id="itemType" class="d-inline ms-2 mb-0"></p>
                    </div>
                    <div class="text-center mt-4">
                        <button id="viewHistory" class="btn" style="background: linear-gradient(to right, #ffeba7 0%, #f5ce62 100%); color: #102770;">
                            View Inspection History
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inspection History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background-color: #2a2b38; color: #c4c3ca;">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="historyModalLabel" style="color: #ffeba7;">Inspection History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent" class="history-list"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .nfc-scan-button {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(to right, #ffeba7 0%, #f5ce62 100%);
        border: none;
        cursor: pointer;
        transition: all 0.4s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    .nfc-scan-button:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 24px 0 rgba(16, 39, 112, .2);
    }

    .nfc-scan-button i {
        font-size: 48px;
        color: #102770;
    }

    .scanning .nfc-scan-button {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(245, 206, 98, 0.7);
        }

        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 20px rgba(245, 206, 98, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(245, 206, 98, 0);
        }
    }

    /* Modal Styles */
    .modal-content {
        border: none;
        box-shadow: 0 8px 24px 0 rgba(0,0,0,.2);
    }
    .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    /* Add these new styles */
    .item-details {
        text-align: left;
        padding: 0 20px;
    }
    .detail-label {
        font-weight: 600;
        font-size: 1.1rem;
    }
    #itemName, #itemType {
        font-size: 1.1rem;
    }

    .loading-spinner {
        margin-top: 20px;
        text-align: center;
    }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #ffeba7;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loading-spinner p {
        margin-top: 10px;
        color: #ffeba7;
    }

    /* Add these new styles for history */
    .history-list {
        max-height: 60vh;
        overflow-y: auto;
    }

    .history-item {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        width: 100%;
    }

    .history-item:last-child {
        margin-bottom: 0;
    }

    .history-meta {
        font-size: 0.9rem;
        color: #8e8e96;
        margin-top: 5px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .history-notes {
        font-style: italic;
        color: #a9a9b1;
        margin-top: 8px;
        text-align: left;
        word-wrap: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
        padding: 0;
    }

    .history-notes strong {
        display: block;
        text-align: left;
        margin-bottom: 4px;
    }

    .notes-content {
        text-align: left;
        display: block;
        width: 100%;
        white-space: normal;
    }

    /* Add status badge styles */
    .status-badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 100px;
    }

    .status-Accept {
        background-color: #28a745;
        color: white;
    }

    .status-Repair {
        background-color: #ffc107;
        color: #000;
    }

    .status-Failed {
        background-color: #dc3545;
        color: white;
    }

    /* Add styles for test mode section */
    .test-mode-section {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 20px;
        margin-top: 20px;
    }

    .test-mode-section h5 {
        color: #ffeba7;
        font-weight: 500;
    }

    #test-query-btn {
        background: linear-gradient(to right, #ffeba7 0%, #f5ce62 100%);
        color: #102770;
    }

    #test-query-btn:hover {
        background: linear-gradient(to right, #f5ce62 0%, #ffeba7 100%);
    }

    /* Add these new styles for navigation */
    .nav-links {
        margin: 20px 0;
    }

    .nav-links .nav-link {
        display: inline-block;
        padding: 8px 20px;
        margin: 0 10px;
        color: #c4c3ca;
        text-decoration: none;
        transition: all 0.3s ease;
        border-radius: 4px;
    }

    .nav-links .nav-link:hover {
        color: #ffeba7;
        transform: translateY(-2px);
    }

    .nav-links .nav-link.active {
        color: #ffeba7;
        background: rgba(255, 235, 167, 0.1);
    }

    .logo {
        display: block;
        margin: 0 auto;
        transition: transform 0.3s ease;
    }

    .logo:hover {
        transform: scale(1.05);
    }
</style>

<script>
    let isPolling = false;
    let pollingInterval;
    let itemModal = null;
    let historyModal = null;
    let currentItemUid = null;

    // Function to show loading state
    function showLoading() {
        const loadingSpinner = document.querySelector('.loading-spinner');
        loadingSpinner.style.display = 'block';
    }

    // Function to hide loading state
    function hideLoading() {
        const loadingSpinner = document.querySelector('.loading-spinner');
        loadingSpinner.style.display = 'none';
    }

    function showSuccessModal(data) {
        hideLoading();
        currentItemUid = data.item_uid;
        document.getElementById('itemImage').src = `itemImage/${data.item_image}`;
        document.getElementById('itemName').textContent = data.item_name;
        document.getElementById('itemType').textContent = data.item_type;
        
        console.log('Current Item UID:', currentItemUid);
        
        if (itemModal) {
            itemModal.show();
        } else {
            console.error('Modal not initialized');
            itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
            itemModal.show();
        }
    }

    function checkCurrentItemUid() {
        console.log('Checking currentItemUid:', currentItemUid);
        return currentItemUid !== null && currentItemUid !== undefined;
    }

    // Function to check internet connectivity
    async function checkConnectivity() {
        try {
            const response = await fetch('ping', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                return false;
            }

            const data = await response.json();
            return data.status === 'ok';
        } catch (error) {
            console.error('Connectivity check error:', error);
            return false;
        }
    }

    // Function to sync pending inspection data
    async function syncPendingData() {
        try {
            const isOnline = await checkConnectivity();
            
            if (!isOnline) {
                console.log('No internet connection, skipping sync');
                return;
            }

            const response = await fetch('inspector/sync_pending_data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.success) {
                console.log('Sync completed successfully:', result.message);
                if (result.failed_items && result.failed_items.length > 0) {
                    console.warn('Some items failed to sync:', result.failed_items);
                }
            } else {
                console.error('Sync failed:', result.message);
            }
        } catch (error) {
            console.error('Error during sync:', error.message);
        }
    }

    // Initialize the modals when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
        historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
        
        // Add event listeners for modal close
        document.getElementById('itemModal').addEventListener('hidden.bs.modal', function () {
            // Restart polling when modal is closed
            startPolling();
        });

        document.getElementById('historyModal').addEventListener('hidden.bs.modal', function () {
            // Show the item modal again when history modal is closed
            itemModal.show();
        });
        
        // Start polling when the page loads
        startPolling();

        // Attempt to sync pending data
        syncPendingData();
        
        // Add click handler for history button
        document.getElementById('viewHistory').addEventListener('click', async function() {
            console.log('View History clicked. Current Item UID:', currentItemUid);
            
            if (!checkCurrentItemUid()) {
                showErrorAlert('Please scan an item first', 'validation_error');
                return;
            }

            try {
                showLoading();
                const response = await fetch('read/get_inspection_history', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `uid=${encodeURIComponent(currentItemUid)}`
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    const historyContent = document.getElementById('historyContent');
                    if (data.history.length > 0) {
                        const historyHtml = data.history.map(record => {
                            const date = new Date(record.inspection_timestamp);
                            const formattedDate = date.toLocaleString('en-US', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: true,
                                timeZone: 'Asia/Jakarta'
                            });
                            
                            return `
                                <div class="history-item">
                                    <div class="mb-2">
                                        <span class="status-badge status-${record.inspection_status}">${record.inspection_status}</span>
                                    </div>
                                    <div class="history-meta">
                                        <div>Date: ${formattedDate}</div>
                                        <div>Inspector: ${record.inspection_user}</div>
                                    </div>
                                    ${record.inspection_note ? `
                                        <div class="history-notes">
                                            <strong>Notes:</strong>
                                            <span class="notes-content">${record.inspection_note}</span>
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        }).join('');
                        historyContent.innerHTML = historyHtml;
                    } else {
                        historyContent.innerHTML = '<p class="text-center">No inspection history available.</p>';
                    }
                    itemModal.hide();
                    historyModal.show();
                } else {
                    showErrorAlert(
                        data.message || 'Failed to fetch inspection history',
                        data.error_type || 'error'
                    );
                }
            } catch (error) {
                hideLoading();
                showErrorAlert(
                    'Failed to fetch inspection history. Please try again.',
                    'network_error',
                    {
                        error: error.message,
                        stack: error.stack
                    }
                );
                console.error('Error:', error);
            }
        });
    });

    function showErrorAlert(message, errorType = null, details = null) {
        hideLoading();
        let errorMessage = message;
        
        // Add additional context based on error type
        if (errorType) {
            switch (errorType) {
                case 'database_error':
                    errorMessage = 'Database Error: ' + message;
                    break;
                case 'not_found':
                    errorMessage = `Item was not found in the database`;
                    break;
                case 'validation_error':
                    errorMessage = 'Validation Error: ' + message;
                    break;
                case 'request_error':
                    errorMessage = 'Request Error: ' + message;
                    break;
                case 'server_error':
                    errorMessage = 'Server Error: ' + message;
                    break;
            }
        }

        Swal.fire({
            icon: 'error',
            title: errorType ? errorType.replace('_', ' ').toUpperCase() : 'Error',
            text: errorMessage,
            background: '#2a2b38',
            color: '#c4c3ca',
            confirmButtonColor: '#ffeba7',
        });

        // Log the error for debugging
        console.error('Error Details:', {
            type: errorType,
            message: message,
            details: details
        });
    }

    function log(message) {
        console.log(message);
        const statusElement = document.getElementById('nfc-status');
        statusElement.textContent = message;
    }

    async function processRFID(serialNumber) {
        try {
            showLoading();
            const response = await fetch('read/item_query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `uid=${encodeURIComponent(serialNumber)}`
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                showSuccessModal(data.item);
            } else {
                showErrorAlert(
                    data.message,
                    data.error_type,
                    {
                        uid: data.uid,
                        status: response.status,
                        statusText: response.statusText
                    }
                );
            }
        } catch (error) {
            showErrorAlert(
                'An error occurred while processing your request. Please try again.',
                'network_error',
                {
                    error: error.message,
                    stack: error.stack
                }
            );
            console.error('Error:', error);
        }
    }

    // Function to check for RFID value
    async function checkRFIDValue() {
        try {
            const response = await fetch('inspector/check_rfid', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            
            if (data.success && data.rfid) {
                // Stop polling when we get a valid RFID
                stopPolling();
                // Process the RFID value
                processRFID(data.rfid);
            }
        } catch (error) {
            console.error('Error checking RFID:', error);
        }
    }

    // Function to start polling
    function startPolling() {
        if (isPolling) return;
        
        isPolling = true;
        showLoading();
        
        // Check every 1 second
        pollingInterval = setInterval(checkRFIDValue, 1000);
    }

    // Function to stop polling
    function stopPolling() {
        if (!isPolling) return;
        
        clearInterval(pollingInterval);
        isPolling = false;
    }
</script>

<?= $this->endSection() ?>
