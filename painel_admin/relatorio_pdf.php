<?php
require_once("../config.php");

// Buscar dados
$sql = "SELECT c.id, c.titulo, COUNT(i.aluno_id) as inscritos
        FROM cursos c
        LEFT JOIN inscricoes i ON c.id = i.curso_id
        GROUP BY c.id, c.titulo
        ORDER BY inscritos DESC";
$cursos = $conn->query($sql);

// Gerar PDF com FPDF
require("../libs/fpdf/fpdf.php"); // você deve baixar a lib FPDF em: http://www.fpdf.org/

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial","B",16);
$pdf->Cell(0,10,"Relatorio de Cursos",0,1,"C");

$pdf->SetFont("Arial","B",12);
$pdf->Cell(20,10,"ID",1);
$pdf->Cell(100,10,"Curso",1);
$pdf->Cell(40,10,"Inscritos",1);
$pdf->Ln();

$pdf->SetFont("Arial","",12);
while($c = $cursos->fetch_assoc()){
    $pdf->Cell(20,10,$c['id'],1);
    $pdf->Cell(100,10,utf8_decode($c['titulo']),1);
    $pdf->Cell(40,10,$c['inscritos'],1);
    $pdf->Ln();
}

$pdf->Output();
