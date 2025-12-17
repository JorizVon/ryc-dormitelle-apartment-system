<!DOCTYPE html>
<html>
<head>
    <title>Security Deposit Deduction Summary</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; margin: 20px; color: #333; }
        .container { border: 2px solid #004AAD; padding: 25px; border-radius: 10px; }
        h1 { text-align: center; color: #01214B; margin-bottom: 30px; text-decoration: underline; }
        .info { margin-bottom: 25px; line-height: 1.6; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #e3f2fd; color: #01214B;}
        .summary { margin-top: 30px; text-align: right; font-size: 14px; }
        .summary p { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Damage/Deduction Summary</h1>
        <div class="info">
            <strong>Name:</strong> <?php echo htmlspecialchars($tenant_name); ?><br>
            <strong>Unit No.:</strong> <?php echo htmlspecialchars($unit_no); ?><br>
            <strong>Security Deposit:</strong> Php <?php echo number_format($security_deposit, 2); ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Object/Furniture</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $deductions_html; // This variable is passed from the main script ?>
            </tbody>
        </table>
        <div class="summary">
            <p><strong>Total Deductions:</strong> Php <?php echo number_format($total_deductions, 2); ?></p>
            <p><strong>Remaining Deposit:</strong> Php <?php echo number_format($remaining_deposit, 2); ?></p>
        </div>
    </div>
</body>
</html>