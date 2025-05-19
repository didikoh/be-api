<?php
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php'); // 路径根据你的实际位置
require_once "../connect.php"; // 包含 PDO 和 CORS 设置

// 自定义 Header
class MYPDF extends TCPDF
{
    public function Header()
    {
        // 蓝色横线
        $this->SetDrawColor(44, 62, 153);
        $this->SetLineWidth(2);
        $this->Line(10, 15, 200, 15);
    }
    public function Footer()
    {
        // 默认不加页码
    }
}

// 获取参数
$transaction_id = $_GET['transaction_id'] ?? null;

if (!$transaction_id) {
    die("无效的交易ID");
}

// 查询交易和会员信息
$stmt = $pdo->prepare("
  SELECT t.*, s.name AS member_name
  FROM transaction_list t
  LEFT JOIN student_list s ON t.member_id = s.id
  WHERE t.id = :id
");
$stmt->execute([':id' => $transaction_id]);
$row = $stmt->fetch();

if (!$row) die("未找到交易信息");

// 填充 PDF 字段
$member_name = $row['member_name'];
$invoice_no = $row['id'];
$date = date('Y-m-d', strtotime($row['time']));
$description = $row['package'] ?? 'Top Up';
$amount = $row['amount'] ?? 0;

// 创建PDF对象
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// 页边距
$pdf->SetMargins(10, 22, 10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// 字体（Arial 用 Helvetica, TCPDF 默认支持，中文可用 DejaVu 或 simsun.ttf）
$pdf->SetFont('helvetica', '', 11);

// ===== 头部公司资料 =====
$pdf->SetTextColor(70, 76, 199);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 7, 'KOA SIU HANN', 0, 1, 'L', 0, '', 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(44, 62, 153);
$pdf->Cell(0, 5, '(202403325983)', 0, 1, 'L', 0, '', 0);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, '77,JALAN INANG,TAMAN KEMAJUAN,83000 BATU PAHAT,JOHOR', 0, 1, 'L', 0, '', 0);
$pdf->Cell(0, 5, '017 761 5676', 0, 1, 'L', 0, '', 0);

$pdf->Ln(5);

// ===== 发票标题 =====
$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetTextColor(44, 62, 153);
$pdf->Cell(0, 12, 'Invoice', 0, 1, 'L', 0, '', 0);

$pdf->Ln(2);

// ===== 客户与发票号/日期 =====
// 左侧：Invoice for & 客户名
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(0, 0, 0);
$startY = $pdf->GetY(); // 当前Y值，备用

$pdf->Cell(60, 7, 'Invoice for', 0, 1, 'L');
$pdf->Cell(60, 7,  $member_name, 0, 1, 'L');

// 右侧：INVOICE NO. 和 DATE
$rightX = $pdf->GetPageWidth() - $pdf->getMargins()['right'] - 60; // 60为右侧块宽，可按需调整
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY($rightX, $startY); // 回到第一行的右侧
$pdf->Cell(60, 7, 'INVOICE NO.: ' . $invoice_no, 0, 2, 'R'); // 右对齐

$pdf->SetFont('helvetica', '', 10);
$pdf->SetX($rightX); // 第二行同X，向下
$pdf->Cell(60, 7, 'DATE: ' . $date, 0, 1, 'R');

// 恢复光标到两行后的最大Y
$pdf->SetY($startY + 14); // 两行高
$pdf->Ln(3); // 适当间距



// ===== 表格头和内容 =====
$html = <<<EOD
<table border="0" cellpadding="6" cellspacing="0" width="100%">
<tr style="background-color:#f0f0f0;font-weight:bold;">
    <td width="50%">Description</td>
    <td width="10%" align="center">Qty</td>
    <td width="20%" align="center">Unit price</td>
    <td width="20%" align="center">Total price</td>
</tr>
<tr>
    <td>$description</td>
    <td align="center">1</td>
    <td align="center">RM $amount</td>
    <td align="center">RM $amount</td>
</tr>
<tr><td colspan="4" height="20"></td></tr>
</table>
EOD;
$pdf->SetFont('helvetica', '', 12);
$pdf->writeHTML($html, true, false, false, false, '');

// ===== 小计与总计 =====
// 小计和折扣区域靠右且不重叠
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(44, 62, 153);
// 靠右对齐（比如在页宽减去边距后-60的地方）
$pdf->SetXY(130, $pdf->GetY() + 8);
$pdf->Cell(30, 8, 'Subtotal', 0, 0, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(30, 8, $amount, 0, 1, 'R');
$pdf->SetTextColor(44, 62, 153);
$pdf->SetX(130);
$pdf->Cell(30, 10, 'Discount', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(223, 36, 125);
$pdf->Cell(30, 12, '-', 0, 1, 'R');


// ===== 签名 =====
// 签名靠左
$signY = $pdf->GetY() + 15;
$pdf->SetXY(20, $signY); // 20是离左边的距离
$pdf->SetDrawColor(44, 62, 153);
$pdf->SetLineWidth(0.3);
// 横线
$pdf->Line(20, $signY + 5, 80, $signY + 5); // 从20到80mm横线

$pdf->SetXY(20, $signY + 8);
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(70, 76, 199);
$pdf->Cell(60, 7, 'XIAO HAN', 0, 2, 'L');
$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(60, 5, 'Authorised Signature', 0, 1, 'L');



// ===== 输出PDF =====
$pdf->Output('invoice.pdf', 'I');
