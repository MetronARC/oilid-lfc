<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Search & Inspector Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <!-- Registration Card -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Inspector Registration</h4>
                    </div>
                    <div class="card-body">
                        <form id="registrationForm" class="mb-4">
                            <div class="mb-3">
                                <label for="reg_uid" class="form-label">Inspector UID</label>
                                <input type="text" class="form-control" id="reg_uid" name="reg_uid" required>
                            </div>
                            <input type="hidden" id="reg_password" name="reg_password">
                            <button type="button" class="btn btn-secondary mb-3" id="generatePasswordBtn">Generate Password</button>
                            <button type="submit" class="btn btn-success d-block w-100" id="registerBtn" disabled>Register</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Login Card -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Inspector Login</h4>
                    </div>
                    <div class="card-body">
                        <form id="loginForm" class="mb-4">
                            <div class="mb-3">
                                <label for="login_uid" class="form-label">Inspector UID</label>
                                <input type="text" class="form-control" id="login_uid" name="login_uid" required>
                            </div>
                            <div class="mb-3">
                                <label for="login_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="login_password" name="login_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Item Search Card -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Item Search</h4>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" class="mb-4">
                            <div class="mb-3">
                                <label for="uid" class="form-label">Enter UID</label>
                                <input type="text" class="form-control" id="uid" name="uid" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>

                        <div id="resultArea" class="d-none">
                            <h5 class="mb-3">Item Details</h5>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th>Item Name</th>
                                        <td id="item_name"></td>
                                    </tr>
                                    <tr>
                                        <th>Item Type</th>
                                        <td id="item_type"></td>
                                    </tr>
                                    <tr>
                                        <th>Certificate Number</th>
                                        <td id="item_certNo"></td>
                                    </tr>
                                    <tr>
                                        <th>Manufacturer</th>
                                        <td id="item_manufacturer"></td>
                                    </tr>
                                    <tr>
                                        <th>Part Number</th>
                                        <td id="item_partNo"></td>
                                    </tr>
                                    <tr>
                                        <th>Supplier</th>
                                        <td id="item_supplier"></td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td id="item_description"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function generateRandomPassword(length = 12) {
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < length; i++) {
                const randomIndex = Math.floor(Math.random() * charset.length);
                password += charset[randomIndex];
            }
            return password;
        }

        $(document).ready(function() {
            // Generate Password Button Handler
            $('#generatePasswordBtn').on('click', function() {
                const uid = $('#reg_uid').val().trim();
                
                if (!uid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please enter Inspector UID first'
                    });
                    return;
                }

                // Generate random password
                const password = generateRandomPassword();
                $('#reg_password').val(password);
                
                // Enable register button
                $('#registerBtn').prop('disabled', false);
                
                // Show the generated password to the user
                Swal.fire({
                    icon: 'info',
                    title: 'Generated Password',
                    text: `Your password is: ${password}`,
                    confirmButtonText: 'Copy & Close',
                    showCancelButton: true,
                    cancelButtonText: 'Just Close',
                }).then((result) => {
                    if (result.isConfirmed) {
                        navigator.clipboard.writeText(password);
                        Swal.fire({
                            icon: 'success',
                            title: 'Password Copied!',
                            text: 'The password has been copied to your clipboard.',
                            timer: 1500
                        });
                    }
                });
            });

            // Registration Form Handler
            $('#registrationForm').on('submit', function(e) {
                e.preventDefault();
                
                const uid = $('#reg_uid').val().trim();
                const password = $('#reg_password').val();
                
                if (!uid || !password) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please enter UID and generate password first'
                    });
                    return;
                }

                $.ajax({
                    url: '<?= base_url('inspector/register') ?>',
                    type: 'POST',
                    data: { 
                        uid: uid,
                        password: password
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Registration successful!'
                            });
                            $('#registrationForm')[0].reset();
                            $('#registerBtn').prop('disabled', true);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Registration failed'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred during registration'
                        });
                    }
                });
            });

            // Login Form Handler
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const uid = $('#login_uid').val().trim();
                const password = $('#login_password').val();
                
                if (!uid || !password) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please fill in all fields'
                    });
                    return;
                }

                $.ajax({
                    url: '<?= base_url('inspector/login') ?>',
                    type: 'POST',
                    data: { 
                        uid: uid,
                        password: password
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Login successful!'
                            });
                            $('#loginForm')[0].reset();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Invalid credentials'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred during login'
                        });
                    }
                });
            });

            // Existing Item Search Form Handler
            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                
                const uid = $('#uid').val().trim();
                if (!uid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please enter a UID'
                    });
                    return;
                }

                $.ajax({
                    url: '<?= base_url('itemsearch/search') ?>',
                    type: 'POST',
                    data: { uid: uid },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#item_name').text(response.data.item_name || 'N/A');
                            $('#item_type').text(response.data.item_type || 'N/A');
                            $('#item_certNo').text(response.data.item_certNo || 'N/A');
                            $('#item_manufacturer').text(response.data.item_manufacturer || 'N/A');
                            $('#item_partNo').text(response.data.item_partNo || 'N/A');
                            $('#item_supplier').text(response.data.item_supplier || 'N/A');
                            $('#item_description').text(response.data.item_description || 'N/A');
                            $('#resultArea').removeClass('d-none');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Not Found',
                                text: response.message || 'No data found for this UID'
                            });
                            $('#resultArea').addClass('d-none');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while searching for the item'
                        });
                        $('#resultArea').addClass('d-none');
                    }
                });
            });
        });
    </script>
</body>
</html>