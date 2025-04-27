<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Information</title>
    <style>
        body {
            display: flex;
            margin: 0;
            background-color: #FFFF;
        }
        .sideBar {
            width: 450px;
            height: 100%;
            background-color: #01214B;
        }
        .systemTitle {
            text-align: center;
            height: 10vh;
            padding-bottom: 5.3px;
        }
        .systemTitle h1 {
            font-size: 25px;
            font-family: Inria Serif;
            align-items: center;
            position: relative;
            top: 5px;
            color: #FFFF;
        }
        .systemTitle p {
            font-size: 14px;
            font-family: Inria Serif;
            position: relative;
            bottom: 10px;
            color: #FFFF;
        }
        .sidebarContent {
            padding-top: 20px;
            height: 84vh;
            background-color: #004AAD;
            display: block;
        }
        .card {
            width: 100%;
            height: 50px;
            display: flex;
            margin: 10px 0px 10px;
            align-items: center;
            justify-content: center;
        }
        .card a {
            margin: auto 0px auto 0px;
            font-size: 24px;
            padding-left: 20px;
            font-weight: 500;
            display: flex;
            text-decoration: none;
            align-items: center;
            color: #01214B;
            height: 100%;
            width: 100%;
            background-color: #004AAD;
            color: white;
        }
        .card a:hover {
            background-color: 004AAD;
            color: #FFFF;
            background-color: #FFFF;
            color: #004AAD;
        }
        .card a:hover .DsidebarIcon {
            content: url('sidebarIcons/DashboardIcon.png');
        }
        .card a:hover .UIsidebarIcon {
            content: url('sidebarIcons/UnitsInfoIcon.png');
        }
        .card a:hover .THsidebarIcon {
            content: url('sidebarIcons/TenantsInfoIcon.png');
        }
        .card a:hover .PMsidebarIcon {
            content: url('sidebarIcons/PaymentManagementIcon.png');
        }
        .card a:hover .APLsidebarIcon {
            content: url('sidebarIcons/AccesspointIcon.png');
        }
        .card a:hover .CGsidebarIcon {
            content: url('sidebarIcons/CardregisterIcon.png');
        }
        .mainBody {
            width: 100vw;
            height: 100%;
            background-color: white;
        }
        .header {
            height: 13vh;
            width: 100%;
            background-color: #79B1FC;
            display: flex;
            justify-content: end;
            align-items: center;
        }
        .headerContent {
            margin-right: 40px;
            display: flex;
            justify-content: center;
            align-items: center;

        }
        .adminTitle {
            font-size: 16px;
            color: #01214B;
            position: relative;
        }
        .headerContent a {
            font-size: 16px;
            color: #FFFF;
            position: relative;
            margin-left: 2px;
            text-decoration: none;
        }
        .headerContent a:hover {
            color: #004AAD;
        }
        .mainContent {
            height: 100%;
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
        }
        .cardreg {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        .cardreg h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            height: 20px;
            align-items: center;
        }
        .rfidContainer {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .rfidImageContainer {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 203px;
            height: 170px;
            margin-top: 15px;
            margin-bottom: 30px;
            border: 2px solid #000000;
            border-radius: 0.5rem;
            object-fit: cover; /* Keeps aspect ratio and fills container */
        }
        .uploadBtnContainer {
            text-align: center;
            margin-top: 5px;
            width: 100%;

        }
        .customUploadBtn {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0px auto;
            height: 25px;
            width: 130px;
            background-color: #79B1FC;
        }
        .inputImage {
            visibility: hidden;
        }
        .cardregistration {
            display: flex;
            justify-content: space-between;
            height: 350px;
            width: 95%;
            margin-left: 10px;
            position: relative;
            bottom: 20px;
        }
        .rfidInput1 {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            position: relative;
            bottom: 25px;
        }
        .rfidInput2 {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            margin-left: 10px;
            position: relative;
            bottom: 25px;
        }
        .formContainer {
            width: 100%;
            height: 470px;
            margin-top: 52px;
            margin-left: 15px;
        }
        label {
            display: inline-block;
            width: 180px;
            margin-bottom: 5px;
            padding: 2px;
            vertical-align: top;
            color: #004AAD;
        }
        input[type="text"],
        input[type="date"]
        {
            width: 300px;
            padding: 2px;
            margin-bottom:5px;
        }
        select {
            width: 308px;
            padding: 2px;
            margin-bottom: 15px;
        }
        .buttonContainer {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: right;
            margin-top: 15px;
        }
        button {
            background-color: #004AAD;
            border: none;
            width: 130px;
            height: 30px;
            justify-content: center;
            align-items: center;
            display: flex;
            color: #FFFFFF;
            font-weight: 10000;
            margin: auto 10px;
        }
        button img {
            width: 15px;
            height: 15px;
            margin-right: 5px;
        }
        button :hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px #004AAD solid;
        }
        .footbtnContainer {
            width: 90%;
            height: 20px;
            margin-left: 60px;
            display: flex;
            position: relative;
            top: 38px;
            justify-content: space-between;
            align-items: center;
        }
        .backbtn {
            height: 36px;
            width: 110px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 22px;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
        }
        button {
            background-color: #004AAD;
            color: white;
            border: none;
            border-radius: 5px;
        }
        button:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .footbtnContainer a:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        
        .footbtnContainer a:hover .printTenantInfo {
            content: url('otherIcons/printIconblue.png');
        }
    </style>
</head>
<body>
    <div class="sideBar">
        <div class="systemTitle">
            <h1>RYC Dormitelle</h1>
            <p>APARTMENT MANAGEMENT SYSTEM</p>
        </div>
        <div class="sidebarContent">
            <div class="card">
                <a href="DASHBOARD.php" class="changeicon">
                    <img src="sidebarIcons/DashboardIconWht.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 10px;">
                    Dashboard
                </a>
            </div>
            <div class="card">
                <a href="UNITSINFORMATION.php">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
                    Tenants Lists</a>
            </div>
            <div class="card">
                <a href="PAYMENTMANAGEMENT.php">
                    <img src="sidebarIcons/PaymentManagementIconWht.png" alt="Payment Management Icon" class="PMsidebarIcon" style="margin-right: 10px;">
                    Payment Management</a>
            </div>
            <div class="card">
                <a href="ACCESSPOINTLOGS.php">
                    <img src="sidebarIcons/AccesspointIconWht.png" alt="Access Point Logs Icon" class="APLsidebarIcon" style="margin-right: 10px;">
                    Access Point Logs</a>
            </div>
            <div class="card">
                <a href="CARDREGISTRATION.php"  style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/CardregisterIcon.png" alt="Card Registration Icon" class="CGsidebarIcon" style="margin-right: 10px;">
                    Card Registration</a>
            </div>
            
        </div>
    </div>
        <div class="mainBody">
            <div class="header">
                <div class="headerContent">
                    <p class="adminTitle">ADMIN | </p>
                    <a href="#" class="logOutbtn">Log Out</a>
                </div>
            </div>
            <div class="mainContent">
                <div class="cardreg">
                    <h4>Renew or Delete Card</h4>
                </div>
                <div class="rfidContainer">
                    <img src="rfid000.jpg" alt="rfid" id="rfidImage" class="rfidImageContainer">
                                
                    <div class="cardregistration">
                        <div class="rfidInput1">
                            <div class="formContainer">
                                <div>
                                    <label for="tenantName">Tenant Name </label>
                                    <input type="text" name="tenantName">
                                </div>
                                <div>
                                    <label for="contactNum">Contact no. </label>
                                    <input type="text" name="contactNum">
                                </div>
                                <div>
                                    <label for="unitNum">Unit No. </label>
                                    <input type="text" name="unitNum">
                                </div>
                                <div>
                                    <label for="tenantID">Tenant ID </label>
                                    <input type="text" name="tenantID">
                                </div>
                            </div>
                        </div>
                        <div class="rfidInput2">
                            <div class="formContainer">
                                <div>
                                    <label for="rfidNum">RFID Card No. </label>
                                    <input type="text" name="rfidNum">
                                </div>
                                <div>
                                    <label for="dateCardReg">Date Card Registration </label>
                                    <input type="date" name="dateCardReg">
                                </div>
                                <div>
                                    <label for="daterenewal">Date Renewal </label>
                                    <input type="date" name="daterenewal">
                                </div>
                                <div>
                                    <label for="cardExp">Card Expiration </label>
                                    <input type="date" name="cardExp">
                                </div>
                                <div>
                                    <label for="Lease Status">Lease Status</label>
                                    <select id="Lease Status">
                                    <option value="">-- Lease Status --</option>
                                    <option>Active</option>
                                    <option>Expired</option>
                                    <option>Pending</option>
                                    </select>
                                    <div class="buttonContainer">
                                        <button id="renewBtn">Renew</button>
                                        <button id="deletebtn">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
            </div>
            <div class="footbtnContainer">
                <a href="CARDREGISTRATION.php" class="backbtn">&#10558; Back</a>
                
            </div>
        </div>
    </div>
    <script>
        const imageInput = document.getElementById('imageInput');
        const tenantImage = document.getElementById('tenantImage');
    
        imageInput.addEventListener('change', function(event) {
          const file = event.target.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
              tenantImage.src = e.target.result;
            }
            reader.readAsDataURL(file);
          }
        });
      </script>
</body>
</html>
