<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contract of Lease</title>
    <style>
        body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.6; text-align: justify; margin: 40px; }
        h2 { text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
        .subtitle { text-align: center; margin-top: 0; margin-bottom: 30px; }
        .section { margin-top: 20px; }
        .centered { text-align: center; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>CONTRACT OF LEASE</h2>
    <p class="subtitle">(Residential)</p>

    <p><strong>KNOW ALL MEN BY THESE PRESENTS:</strong></p>

    <p>
        This <strong>CONTRACT OF LEASE</strong> is made and executed in Daet, Camarines Norte,
        this <u><?php echo htmlspecialchars($tpl['day']); ?></u> day of
        <u><?php echo htmlspecialchars($tpl['month']); ?></u>,
        <u><?php echo htmlspecialchars($tpl['year']); ?></u>, by and between:
    </p>

    <p>
        <strong><?php echo htmlspecialchars($tpl['admin_name']); ?></strong>, of legal age, 
        <?php echo htmlspecialchars($tpl['admin_nationality']); ?> Citizen, 
        <?php echo htmlspecialchars($tpl['admin_civil_status']); ?>, and with residential and postal address at 
        <strong>Purok-2, Brgy. II, Pasig, Daet, Camarines Norte</strong>, hereinafter referred to as the <strong>LESSOR</strong>.
    </p>

    <p class="centered">
        - AND -
    </p>

    <p>
        <strong><u><?php echo htmlspecialchars($tpl['tenant_name']); ?></u></strong>, of legal age,
        <?php echo htmlspecialchars($tpl['citizenship']); ?> Citizen, with residence and postal address at
        <strong><u><?php echo htmlspecialchars($tpl['tenant_address']); ?></u></strong>, hereinafter referred to as the <strong>LESSEE</strong>.
    </p>

    <p class="centered">
        <strong>WITNESSETH:</strong>
    </p>

    <p class="section">
        <strong>WHEREAS</strong>, the <strong>LESSOR</strong> is the owner of THE LEASED PREMISES, a residential building/property situated at Purok-2, Brgy. II, Pasig, Daet, Camarines Norte;<br>
        <strong>WHEREAS</strong>, the <strong>LESSOR</strong> agrees to lease-out the property to the LESSEE and the LESSEE is willing to lease the same;<br>
        <strong>NOW THEREFORE</strong>, for and in consideration of the foregoing premises, the LESSOR leases unto the LESSEE and the LESSEE hereby accepts from the LESSOR the LEASED premises, subject to the following:
    </p>

    <p class="centered">
        <strong>TERMS AND CONDITIONS</strong>
    </p>

    <p class="section">
        1. <strong>PURPOSES:</strong> That premises hereby leased shall be used exclusively by the LESSEE for residential purposes only and shall not be diverted to other uses. It is hereby expressly agreed that if at any time the premises are used for other purposes, the LESSOR shall have the right to rescind the contract without prejudice to its other rights under the law;
    </p>

    <p class="section">
        2. <strong>TERM:</strong> This term of lease is for 
        <u><?php echo htmlspecialchars($tpl['lease_duration']); ?></u> from 
        <u><?php echo htmlspecialchars($tpl['start_date_formatted']); ?></u> to 
        <u><?php echo htmlspecialchars($tpl['end_date_formatted']); ?></u>;
    </p>


    <p class="section">
        3. <strong>RENTAL RATE & DEPOSIT:</strong> The monthly rental rate for the leased premises shall be in PESOS: 
        <u><?php echo htmlspecialchars($tpl['rent_words']); ?></u> 
        (Php <u><?php echo number_format($tpl['rent_amount'], 2); ?></u>), Philippine Currency. All rental payments shall be payable to the LESSOR. That the LESSEE shall deposit to the LESSOR upon signing of this contract and prior to move-in 1 month advance and 2 months security deposit which shall not be used as rent but will be applied. The minimum term is (6) months, once the LESSEE decide to depart, the terms and conditions number 2 must be applied and the remaining unused monthly rental should be paid by LESSEE.

    </p>

    <p class="section">
        4. <strong>DEFAULT PAYMENT:</strong> In case of default by the LESSEE, the LESSOR has the right to recover possession of the premises when the LESSEE is in default of payment for one (1) Month and may forfeit whatever rental deposit or advances have been given by the LESSEE;

    </p>

    <p class="section">
        5. <strong>SUB-LEASE:</strong> The LESSEE shall not directly or indirectly sublet, allow or permit the leased premises to be occupied in whole or in part by any person without the approval by the LESSOR;
    </p>

    <p class="section">
        6. <strong>PUBLIC UTILITIES & MAINTENANCE:</strong> The LESSEE shall pay for its telephone, electric, cable TV, water, Internet, association dues and other public services and utilities during the duration of the lease;
    </p>

    <p class="section">
        7. <strong>REMINDERS & PROHIBITIONS:</strong> 
        <ol type="a">
  <li>The Lessee should and always secure their personal properties and/or belongings. The Lessor shall not be liable in case of loss or stolen items;</li>
  <li>The Lessee should and always will maintain the cleanliness of the leased premises. Garbage must be put in a proper garbage bags or containers;</li>
  <li>The Lessee shall remove all food residue, hair shawl, shampoo sachet and others that would cause drain blockage;</li>
  <li>The Lessee should observe due care and diligence in using the pre-installed faucet, cabinets, doors and windows. Damages sustained by the said fixtures at the fault of the lessee shall be their liability;</li>
  <li>Major repair and changes of the unit shall not be allowed without the permission of the Lessor;</li>
  <li>Curfew hours starts at <strong>9:00PM</strong> (Applicable only to Students);</li>
  <li>Classroom trips or charter friends are prohibited. Unnecessary noise should be keep at minimum so as to avoid and cause disturbance to other Lesses/neighbors;</li>
  <li>Alcoholic beverages, wines and prohibited drugs are strongly forbidden;</li>
  <li>Vandalism is prohibited in all areas of the leased premises;</li>
  </ol> 
    </p>

    <p class="section">
        8. <strong>PRETERMINATION:</strong> Pre-termination cost of contract without fault of the LESSOR shall be shouldered by the LESSEE (notarization of Cancellation of Lease);
    </p>

        <p class="section">
        9. <strong>EXPIRATION OF LEASE:</strong> At the expiration of the term of this lease or cancellation thereof, as herein provided, the LESSEE will promptly deliver to the LESSOR the leased premises with all corresponding keys and in as good and tenable condition as the same is now, ordinary wear and tear expected devoid of all occupants, movable furniture, articles and effects of any kind;
    </p>

            <p class="section">
        10. This <strong>CONTRACT OF LEASE</strong> shall be valid and binding between the parties, their successors-in-interest and assigns.
    </p>

                <p class="section">
        <strong>IN WITNESS WHEREOF,</strong> parties herein affixed their sigantures on the date and place above written.
    </p>

<br><br>
<div style="text-align:center;">
    <table style="margin:0 auto;">
        <tr>
            <td style="padding: 0 50px; text-align:center;">
                <strong><?php echo htmlspecialchars($tpl['admin_name']); ?></strong><br>
                LESSOR<br>
                VIN: 1606-0104A-C1690RID10000
            </td>
            <td style="padding: 0 50px; text-align:center;">
                <strong><u><?php echo htmlspecialchars($tpl['tenant_name']); ?></u></strong><br>
                LESSEE<br>
                Valid ID: _____________
            </td>
        </tr>
    </table>
</div>

    <p class="section" style="text-align:center;">
        Signed in the presence of:<br><br><br>
        ________________________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;________________________    
    </p><br>

    <p class="centered">
        <strong>ACKNOWLEDGEMENT</strong>
    </p>

    <p class="section">
        <strong>Republic of the Philippines&nbsp;&nbsp;&nbsp;&nbsp;)</strong><br>
        <strong>Province of Camarines Norte</strong><br>
        <strong>Municipality of Daet&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;) S.S</strong><br>
    </p>

    <p class="section">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>BEFORE ME</strong>,  a Notary Public for and in the Province of Camarines Norte, this _____ Day of _________________ personally came and appeared the above-named contracting parties and showed valid proof of identifications shown below their names and known to me to be the same persons who executed the foregoing instrument and acknowledged to me that the same is their free and voluntary act and deed.
    </p>

    <p class="section">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;This instrument consisting of two (2) pages, including the page on which this acknowledgement is written, has been signed on each and every page thereof by the concerned parties and their witnesses, and sealed with my notarial seal.
    </p>

    <p class="section">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>WITNESS MY HAND AND SEAL</strong>,on the date and place first above written.
    </p>

    <p class="section">
        Doc. No. _____;<br>
        Page No. _____;<br>
        Book No. _____;<br>
        Series of 2024
    </p> 


</body>
</html>