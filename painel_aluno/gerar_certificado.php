<?php
require("../fpdf/fpdf.php");
require_once("../libs/phpqrcode/qrlib.php");
session_start();
require_once("../config.php");

// Verifica login
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$curso_id = intval($_GET['curso_id'] ?? 0);

// Buscar certificado válido
$sql = "SELECT u.nome AS aluno, c.titulo AS curso, cr.data_emissao, cr.codigo_autenticacao
        FROM certificados cr
        JOIN usuarios u ON u.id = cr.aluno_id
        JOIN cursos c ON c.id = cr.curso_id
        WHERE cr.aluno_id=? AND cr.curso_id=? AND cr.status='pago'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $aluno_id, $curso_id);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();

if (!$cert) {
    die("❌ Certificado não disponível. Verifique se foi pago.");
}

$aluno_nome   = $cert['aluno'];
$curso_nome   = $cert['curso'];
$data_emissao = date("d/m/Y", strtotime($cert['data_emissao']));

// Se já existe código, usar o mesmo. Senão, gerar e salvar no banco
if (!empty($cert['codigo_autenticacao'])) {
    $codigo_autenticacao = $cert['codigo_autenticacao'];
} else {
    $codigo_autenticacao = strtoupper(substr(md5($aluno_id.$curso_id.$data_emissao), 0, 12));

    $sql_upd = "UPDATE certificados 
                SET codigo_autenticacao=? 
                WHERE aluno_id=? AND curso_id=? AND status='pago'";
    $stmt_upd = $conn->prepare($sql_upd);
    $stmt_upd->bind_param("sii", $codigo_autenticacao, $aluno_id, $curso_id);
    $stmt_upd->execute();
}

// QR Code temporário
$qr_temp = "../temp/qr_$codigo_autenticacao.png";
if (!file_exists("../temp")) {
    mkdir("../temp", 0777, true);
}
$url_validacao = "https://edukaplus.free.nf/validar_certificado.php?codigo=$codigo_autenticacao";
QRcode::png($url_validacao, $qr_temp, QR_ECLEVEL_H, 4);

// Classe PDF
class PDF extends FPDF {
    function Header() {
        // Fundo suave
        $this->SetFillColor(255, 255, 245);
        $this->Rect(0, 0, 297, 210, 'F');

        // Moldura
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(2);
        $this->Rect(8, 8, 281, 194, 'D');

        // Logo no canto superior esquerdo
        if (file_exists("../logo.jpg")) {
            $this->Image("../logo.jpg", 15, 12, 30); // menor, no canto
        }

        // Título
        $this->SetFont("Times", "B", 26);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 40, utf8_decode("CERTIFICADO DE CONCLUSÃO"), 0, 1, "C");
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont("Arial", "I", 10);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, "Eduka Plus Angola - Certificado Oficial", 0, 0, "C");
    }
}

// Criar PDF
$pdf = new PDF("L", "mm", "A4");
$pdf->AddPage();

// Texto introdutório
$pdf->Ln(5);
$pdf->SetFont("Times", "I", 18);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 12, utf8_decode("Certificamos que"), 0, 1, "C");

// Nome do aluno
$pdf->SetFont("Arial", "B", 32);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 18, utf8_decode($aluno_nome), 0, 1, "C");

// Texto do curso
$pdf->SetFont("Times", "", 18);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 12, utf8_decode("concluiu com êxito o curso:"), 0, 1, "C");

// Nome do curso
$pdf->SetFont("Arial", "B", 24);
$pdf->SetTextColor(200, 50, 50);
$pdf->MultiCell(0, 12, utf8_decode($curso_nome), 0, "C");

// Data
$pdf->Ln(5);
$pdf->SetFont("Times", "", 16);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 12, utf8_decode("Emitido em: $data_emissao"), 0, 1, "C");

// Assinaturas
$pdf->Ln(15);
$pdf->SetFont("Arial", "I", 14);
$pdf->Cell(130, 10, "__________________________", 0, 0, "C");
$pdf->Cell(40, 10, "", 0, 0);
$pdf->Cell(130, 10, "__________________________", 0, 1, "C");

$pdf->Cell(130, 10, utf8_decode("Direção - Eduka Plus Angola"), 0, 0, "C");
$pdf->Cell(40, 10, "", 0, 0);


// QR Code menor no canto inferior direito
if (file_exists($qr_temp)) {
    $pdf->Image($qr_temp, 250, 145, 30, 30);
}
$pdf->SetFont("Courier", "B", 12);
$pdf->SetTextColor(90, 90, 90);
$pdf->SetXY(30, 160);
$pdf->MultiCell(180, 10, utf8_decode("Código de Autenticação: $codigo_autenticacao"), 0, "L");

// Saída
$pdf->Output("I", "certificado.pdf");

// unlink($qr_temp); // opcional
?>
