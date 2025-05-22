<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Modal Preview</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .preview-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3174D6;
            margin-bottom: 30px;
        }
        .sample-data {
            background-color: #f8f9fa;
            border-left: 4px solid #3174D6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        code {
            color: #666;
        }
        .btn-preview {
            background-color: #3174D6;
            color: white;
            border: none;
            margin: 10px 5px;
        }
        .btn-preview:hover {
            background-color: #245cb3;
            color: white;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <h1 class="text-center">Notification Modal Preview</h1>
        
        <div class="sample-data">
            <h5>Sample PHP Data:</h5>
            <code>
                $_SESSION['current_notification'] = [<br>
                &nbsp;&nbsp;'title' => 'Payment Due Tomorrow',<br>
                &nbsp;&nbsp;'message' => 'This is a friendly reminder that your rent and utilities for UNIT-123 at RYC Dormitelle are due tomorrow, May 15, 2025.<br>Amount Due: ₱5,000<br><br>You may settle your payment through the following methods:<br>• GCash Transfer:<br>&nbsp;&nbsp;&nbsp;- GCash Number: 0917-123-4567<br>&nbsp;&nbsp;&nbsp;- Account Name: Kyle Catiis<br>• In-person payment at the leasing office (9 AM – 5 PM, Mon–Fri)<br>• Settle with Deposit: Let us know if you'd like to use your deposit for this payment.<br><br>Thank you.'<br>
                ];
            </code>
        </div>
        
        <div class="text-center mb-4">
            <button class="btn btn-preview" onclick="showModal('payment')">Preview Payment Notification</button>
            <button class="btn btn-preview" onclick="showModal('endstay')">Preview End of Stay Notification</button>
            <button class="btn btn-preview" onclick="showModal('billing')">Preview Billing Period Notification</button>
        </div>

        <!-- Modal Container -->
        <div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 shadow border-0">
                    <!-- Body with all content -->
                    <div class="modal-body p-4 text-center">
                        <!-- Email icon -->
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="#3174D6" class="bi bi-envelope" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                        </div>
                        
                        <!-- Date -->
                        <div class="text-muted mb-2" id="modal-date">
                            May 21, 2025
                        </div>
                        
                        <!-- Title -->
                        <h4 class="text-primary fw-bold mb-4" id="modal-title">
                            Rent Payment Reminder
                        </h4>
                        
                        <!-- Divider line -->
                        <hr class="my-4">
                        
                        <!-- Message content -->
                        <div class="text-start mb-4" id="modal-message">
                            This is a friendly reminder that your rent for May 2025 is now due.
                            <br><br>
                            Please settle the amount of ₱5,000 on or before May 30, 2025, to avoid any late fees. Thank you for your prompt attention!
                        </div>
                    </div>
                    
                    <!-- Footer with OK button -->
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="button" class="btn btn-primary px-5 py-2" data-bs-dismiss="modal" style="background-color: #3174D6; border: none;">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sample notification data
        const notifications = {
            payment: {
                title: "Rent Payment Reminder",
                date: "May 21, 2025",
                message: "This is a friendly reminder that your rent for May 2025 is now due.<br><br>Please settle the amount of ₱5,000 on or before May 30, 2025, to avoid any late fees. Thank you for your prompt attention!"
            },
            endstay: {
                title: "Stay Ends Tomorrow – Card Access Will Expire",
                date: "May 21, 2025",
                message: "We hope you've enjoyed your stay at RYC Dormitelle. This is a reminder that your apartment stay is set to end tomorrow, May 22, 2025.<br><br>Your access card will automatically expire on the end date. Contact us if you need to renew or move out.<br><br>Thank you for being part of our community."
            },
            billing: {
                title: "Billing Period Begins Today",
                date: "May 21, 2025",
                message: "Your billing period for UNIT-123 starts today, May 21, 2025, and ends May 26, 2025.<br><br>You can view your rent amount anytime in the resident portal.<br><br>Thanks for staying on top of it!"
            }
        };

        // Function to show modal with selected notification
        function showModal(type) {
            const notification = notifications[type];
            document.getElementById('modal-title').textContent = notification.title;
            document.getElementById('modal-date').textContent = notification.date;
            document.getElementById('modal-message').innerHTML = notification.message;
            
            const modalElement = document.getElementById('notifModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    </script>
</body>
</html>