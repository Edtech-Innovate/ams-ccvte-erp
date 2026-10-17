<?php
require '../../includes/db-config.php';
require '../../includes/helpers.php';

$registrationNo = $_GET['registrationNo'] ?? '';
$year           = $_GET['year'] ?? '';

if (empty($registrationNo) || empty($year)) {
    exit('Invalid data!');
}

// ── Main student data (exact same query as original) ──
$data = $conn->query("
    SELECT Registration_No, Examination_Date, Exam_Month, Exam_Year,
           Candidate_Name, Course, Year, Center_Name, Certification_Date
    FROM tbl_results
    WHERE Registration_No = '$registrationNo'
      AND CAST(Year as UNSIGNED) = $year
    GROUP BY Registration_No
");

if ($data->num_rows == 0) { exit('No records found.'); }
$data = $data->fetch_assoc();

// ── Subjects (exact same query as original) ──
$subjectsQ = $conn->query("
    SELECT * FROM tbl_results
    WHERE Registration_No = '$registrationNo'
      AND CAST(Year as UNSIGNED) = $year
");

$subjects       = [];
$total_obtained = [];
$total_max      = [];

while ($subject = $subjectsQ->fetch_assoc()) {
    // exact same logic as original
    $obt_marks        = $subject['Obtained_Marks_Theory'];
    $total_obtained[] = $obt_marks;
    $total_max[]      = isset($subject['Max_Mark']) ? $subject['Max_Mark'] : 0;
    $subject['_obt']  = $obt_marks;
    $subjects[]       = $subject;
}

// ── Division (exact same as original) ──
$percentage = 0;
$division   = '';
if (array_sum($total_max) != 0) {
    $percentage = (array_sum($total_obtained) / array_sum($total_max)) * 100;
}
if      ($percentage < 40)                      $division = 'Fail';
elseif  ($percentage >= 40 && $percentage < 50) $division = 'IIIrd';
elseif  ($percentage >= 50 && $percentage < 60) $division = 'IInd';
elseif  ($percentage >= 60)                     $division = 'Ist';
else                                            $division = 'Distinction';

// ── Cert date (exact same as original) ──
$dateObj     = new DateTime($data['Certification_Date']);
$day         = $dateObj->format('d');
$month       = $dateObj->format('M');
$certYear    = $dateObj->format('Y');
$examSession = substr($data['Exam_Month'], 0, 3) . '-' . $data['Exam_Year'];

// ── Disclaimer (exact same text as original) ──
$disclaimer = 'Disclaimer: The published result is provisional only. Institution is not responsible for any inadvertent error that may have crept in the data / results being published online. This is being published just for the immediate information to the examinees. The final mark sheet(s) issued by institute will only be treated authentic & final in this regard.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mark Sheet — <?= htmlspecialchars($data['Registration_No']) ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

body{
    font-family:'Times New Roman',Times,serif;
    background:#d4d4d4;
    padding:20px 10px;
    display:flex;
    flex-direction:column;
    align-items:center;
}

/* Print button */
.print-bar{
    width:100%;
    max-width:210mm;
    text-align:right;
    margin-bottom:10px;
}
.print-btn{
    background:#222;color:#fff;border:none;
    padding:8px 24px;font-size:13px;
    font-family:inherit;cursor:pointer;border-radius:3px;
}
.print-btn:hover{background:#444}

/* A4 Sheet */
.sheet{
    background:#fff;
    width:100%;
    max-width:210mm;
    /*min-height:297mm;*/
    min-height:auto;
    padding:10mm 12mm;
    box-shadow:0 2px 20px rgba(0,0,0,.2);
}

/* Header */
.hdr{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:14px;
    border-bottom:2px solid #000;
    padding-bottom:8px;
    margin-bottom:8px;
    text-align:center;
}
.hdr img{height:60px;width:auto}
.hdr-txt .h1{font-size:17px;font-weight:900;text-transform:uppercase;letter-spacing:.5px}
.hdr-txt .h2{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-top:3px}

/* Info rows — exactly same fields as original PDF */
.info{margin:8px 0;font-size:11.5px}
.ir{display:flex;align-items:baseline;margin-bottom:4px;gap:4px}
.il{font-weight:700;white-space:nowrap;min-width:130px}
.id{margin:0 4px}
.iv{border-bottom:1px dotted #888;flex:1;padding-bottom:1px;text-transform:uppercase}

/* Marks table */
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;margin-top:10px}
table{width:100%;border-collapse:collapse;font-size:11px}
thead tr{background:#222;color:#fff}
th{padding:6px 7px;border:1px solid #000;text-align:center;font-weight:700;font-size:10.5px;text-transform:uppercase}
td{padding:5px 7px;border:1px solid #555;vertical-align:middle}
tr:nth-child(even) td{background:#f5f5f5}
td.tc{text-align:center}
td.tl{text-align:left}
tfoot td{background:#222!important;color:#fff;font-weight:700;text-align:center;border:1px solid #000;padding:5px 7px}
tfoot td.tl{text-align:right}

/* Grand total + division — same positions as original */
.bottom-row{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-top:10px;
    font-size:11.5px;
    gap:10px;
    flex-wrap:wrap;
}
.div-box{
    border:1.5px solid #000;
    padding:5px 18px;
    text-align:center;
    min-width:100px;
}
.div-box .dl{font-size:9.5px;text-transform:uppercase;letter-spacing:.5px;color:#555}
.div-box .dv{font-size:16px;font-weight:900;margin-top:2px}

/* Disclaimer */
.disc{
    margin-top:10px;
    /*font-size:9px;*/
     font-size:14px;
    color:#333;
    line-height:1.5;
    border-top:1px dashed #aaa;
    padding-top:6px;
    /*font-style:italic;*/
}

/* Signatures */
.sigs{
    display:flex;
    justify-content:space-between;
    margin-top:28px;
    font-size:10.5px;
    font-weight:700;
    flex-wrap:wrap;
    gap:10px;
}
.sb{text-align:center}
.sb .sl{border-top:1.5px solid #000;width:130px;margin:0 auto 4px}

/* Date — same as original $pdf->SetXY(9, 200.9) position */
.cert-date{font-size:10.5px;margin-top:8px}

/* PRINT */
@media print{
    body{background:none;padding:0}
    .sheet{box-shadow:none;padding:8mm 10mm;max-width:none;width:100%}
    .print-bar{display:none!important}
    @page{size:A4;margin:0}
    tr:nth-child(even) td{background:#f5f5f5!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    thead tr{background:#222!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    tfoot td{background:#222!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
}

/* Responsive small screens */
@media(max-width:600px){
    .hdr{flex-direction:column;gap:8px}
    .hdr-txt .h1{font-size:13px}
    .il{min-width:100px;font-size:10.5px}
    .iv{font-size:10.5px}
    .bottom-row{flex-direction:column}
    .sigs{justify-content:space-around}
    .sb .sl{width:90px}
}
</style>
</head>
<body>

<!--<div class="print-bar">-->
<!--    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>-->
<!--</div>-->

<div class="sheet">

    <!-- HEADER — same as original -->
    <div class="hdr">
        <img src="../../assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
        <div class="hdr-txt">
            <!--<div class="h1">Council for Certificate in Vocational &amp; Technical Education</div>-->
            <div class="h2">Statement of Marks</div>
        </div>
    </div>

    <!-- INFO — exact same fields original PDF mein the -->
    <div class="info">
        <div class="ir">
            <span class="il">Examination</span>
            <span class="id">:</span>
            <span class="iv"><?= htmlspecialchars($examSession) ?></span>
        </div>
        <div class="ir">
            <span class="il">Registration No</span>
            <span class="id">:</span>
            <span class="iv"><?= htmlspecialchars($data['Registration_No']) ?></span>
        </div>
        <div class="ir">
            <span class="il">Candidate Name</span>
            <span class="id">:</span>
            <span class="iv"><?= htmlspecialchars(strtoupper($data['Candidate_Name'])) ?></span>
        </div>
        <div class="ir">
            <span class="il">Course</span>
            <span class="id">:</span>
            <span class="iv"><?= htmlspecialchars(strtoupper($data['Course'])) ?></span>
        </div>
        <div class="ir">
            <span class="il">Year</span>
            <span class="id">:</span>
            <span class="iv"><?= htmlspecialchars(strtoupper($data['Year'])) ?></span>
        </div>
        <div class="ir">
            <span class="il">Center Name</span>
            <span class="id">:</span>
            <span class="iv"><?= htmlspecialchars(strtoupper($data['Center_Name'])) ?></span>
        </div>
    </div>

    <!-- MARKS TABLE — same 4 columns: Sub Code, Subject Name, Max Marks, Marks Obt -->
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:13%">Sub. Code</th>
                    <th style="width:52%">Subject Name</th>
                    <th style="width:17.5%">Max. Marks</th>
                    <th style="width:17.5%">Marks Obt.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $s): ?>
                <tr>
                    <td class="tc"><?= htmlspecialchars($s['Subject_Code'] ?? '') ?></td>
                    <td class="tl"><?= htmlspecialchars(strtoupper($s['Subject_Name'] ?? '')) ?></td>
                    <td class="tc"><?= htmlspecialchars($s['Max_Mark'] ?? '0') ?></td>
                    <td class="tc"><?= htmlspecialchars($s['_obt']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tl" colspan="2">Grand Total</td>
                    <td><?= array_sum($total_max) ?></td>
                    <td><?= array_sum($total_obtained) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- DIVISION — same as original -->
    <div class="bottom-row">
        <!--<div style="font-size:11px;line-height:2">-->
        <!--    <div><strong>Total Max Marks :</strong> <?= array_sum($total_max) ?></div>-->
        <!--    <div><strong>Marks Obtained &nbsp;:</strong> <?= array_sum($total_obtained) ?></div>-->
        <!--    <div><strong>Percentage &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong> <?= number_format($percentage, 2) ?>%</div>-->
        <!--</div>-->
        <div></div>
        <div class="div-box">
            <div class="dl">Division</div>
            <div class="dv"><?= $division ?></div>
        </div>
    </div>

    <!-- DISCLAIMER — exact same text as original -->
    <div class="disc">
        <?= htmlspecialchars($disclaimer) ?>
    </div>

    <!-- DATE — same as original $pdf->SetXY(9, 200.9) -->
    <!--<div class="cert-date">-->
    <!--    <?= $day . '&nbsp;&nbsp;&nbsp;' . $month . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $certYear ?>-->
    <!--</div>-->

    <!-- SIGNATURES -->
    <!--<div class="sigs">-->
    <!--    <div class="sb"><div class="sl"></div>Controller of Examination</div>-->
    <!--    <div class="sb"><div class="sl"></div>Chairman / Director</div>-->
    <!--    <div class="sb"><div class="sl"></div>Registrar</div>-->
    <!--</div>-->

</div><!-- sheet -->
</body>
</html>