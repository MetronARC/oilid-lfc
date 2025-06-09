<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>


<a href="/" class="logo" target="_blank">
    <img src="img/oilid.png" alt="" style="width: 75px; height: 50px;" />
</a>

<div class="section">
    <div class="container">
        <div class="row full-height justify-content-center">
            <div class="col-12 text-center align-self-center py-5">
                <div class="section pb-5 pt-5 pt-sm-2 text-center">
                    <h6 class="mb-0 pb-3">
                        <span>Inspector </span><span>Guest</span>
                    </h6>
                    <input
                        class="checkbox"
                        type="checkbox"
                        id="reg-log"
                        name="reg-log" />
                    <label for="reg-log"></label>
                    <div class="card-3d-wrap mx-auto">
                        <div class="card-3d-wrapper">
                            <div class="card-front">
                                <div class="center-wrap">
                                    <div class="section text-center">
                                        <h4 class="mb-4 pb-3">Inspector Login</h4>
                                        <div id="rfid-container" class="form-group mb-4">
                                            <div class="rfid-message">
                                                <div class="loading-spinner">
                                                    <div class="spinner"></div>
                                                    <p>Waiting for RFID card...</p>
                                                </div>
                                                <div id="rfid-status" class="mt-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-back">
                                <div class="center-wrap">
                                    <div class="section text-center">
                                        <h4 class="mb-4 pb-3">Guest Login</h4>
                                        <div class="form-group">
                                            <input
                                                type="text"
                                                name="logname"
                                                class="form-style"
                                                placeholder="Your User Name"
                                                id="logname"
                                                autocomplete="off" />
                                            <i class="input-icon uil uil-user"></i>
                                        </div>
                                        <div class="form-group mt-2">
                                            <input
                                                type="password"
                                                name="logpass"
                                                class="form-style"
                                                placeholder="Your Password"
                                                id="logpass"
                                                autocomplete="off" />
                                            <i class="input-icon uil uil-lock-alt"></i>
                                        </div>
                                        <a href="#" class="btn mt-4">Submit</a>
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

<style>
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

    /* Add styles for test mode section */
    .test-mode-section {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 20px;
    }

    .test-mode-section h5 {
        color: #ffeba7;
        font-weight: 500;
    }
</style>
<script>
    // Check if we were redirected due to session expiry
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('session') === 'expired') {
        showErrorAlert('Your session has expired. Please log in again.');
    }

    let isPolling = false;
    let pollingInterval;

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

    // Function to show success message
    function showSuccessAlert(message) {
        hideLoading();
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message
        });
    }

    // Function to show error message
    function showErrorAlert(message) {
        hideLoading();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    }

    // Function to log messages on the page
    function log(message) {
        console.log(message);
        const statusElement = document.getElementById('rfid-status');
        statusElement.textContent = message;
    }

    // Function to validate RFID with the server
    async function processLogin(uid) {
        try {
            showLoading();
            log(`Initiating login process for UID: ${uid}`);

            const response = await fetch('inspector/nfc_login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `uid=${encodeURIComponent(uid)}`
            });

            const data = await response.json();
            hideLoading();

            if (data.success) {
                showSuccessAlert(data.message);
                // Redirect after showing the success message
                setTimeout(() => {
                    window.location.href = 'dashboard';
                }, 1500);
            } else {
                if (data.redirect) {
                    // Session expired or unauthorized access
                    window.location.href = data.redirect;
                } else {
                    showErrorAlert(data.message);
                    // Restart polling after showing error message
                    setTimeout(() => {
                        startPolling();
                    }, 1500);
                }
            }
        } catch (error) {
            hideLoading();
            showErrorAlert('An error occurred during login. Please try again.');
            console.error('Error:', error);
        }
    }

    // Function to check for RFID value with improved error handling
    async function checkRFIDValue() {
        try {
            const response = await fetch('inspector/check_rfid', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && data.rfid) {
                // Stop polling when we get a valid RFID
                stopPolling();
                // Process the login with the RFID value
                processLogin(data.rfid);
            }
        } catch (error) {
            console.error('Error checking RFID:', error.message);
            // Don't stop polling on error, just log it
            // The next poll might succeed
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

    // Start sync when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        startPolling();
        syncPendingData(); // Attempt to sync pending data
    });

    async function logout() {
        try {
            // For a regular HTTP request, use a form submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'logout';
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error('Error during logout:', error);
            showErrorAlert('An error occurred during logout');
        }
    }
</script>

<?= $this->endSection() ?>