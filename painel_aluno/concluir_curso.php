<?php
session_start();
require_once("../config.php");

$aluno_id = $_SESSION['usuario_id'];
$curso_id = intval($_GET['curso_id'] ?? 0);

// Verifica se todas as aulas foram concluídas
$sql = "SELECT COUNT(*) AS total_aulas FROM aulas WHERE curso_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();
$total_aulas = $result->fetch_assoc()['total_aulas'] ?? 0;

$sql = "SELECT COUNT(*) AS aulas_concluidas FROM progresso WHERE aluno_id=? AND curso_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $aluno_id, $curso_id);
$stmt->execute();
$result = $stmt->get_result();
$aulas_concluidas = $result->fetch_assoc()['aulas_concluidas'] ?? 0;

if($total_aulas > 0 && $aulas_concluidas == $total_aulas){
    // Gerar certificado (PDF)
    require_once("../vendor/autoload.php"); // DomPDF

    $sql = "SELECT u.nome, c.titulo FROM usuarios u INNER JOIN cursos c ON c.id=? WHERE u.id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $curso_id, $aluno_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $dados = $res->fetch_assoc();

    $html = "
        <h1 style='text-align:center;'>Certificado de Conclusão</h1>
        <p style='text-align:center;'>Certificamos que <strong>{$dados['nome']}</strong></p>
        <p style='text-align:center;'>concluiu o curso <strong>{$dados['titulo']}</strong></p>
        <p style='text-align:center;'>Emitido em ".date('d/m/Y')."</p>
    ";

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $certificado_dir = "../uploads/certificados/";
    if(!is_dir($certificado_dir)) mkdir($certificado_dir,0777,true);

    $certificado_file = $certificado_dir."certificado_{$aluno_id}_{$curso_id}.pdf";
    file_put_contents($certificado_file, $dompdf->output());

    // Salvar no banco
    $sql = "INSERT INTO certificados (aluno_id, curso_id, certificado_arquivo) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $aluno_id, $curso_id, $certificado_file);
    $stmt->execute();
}
?>
