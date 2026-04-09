<?php
require_once("../config.php");

// Buscar dados
$sql = "SELECT c.id, c.titulo, COUNT(i.aluno_id) as inscritos
        FROM cursos c
        LEFT JOIN inscricoes i ON c.id = i.curso_id
        GROUP BY c.id, c.titulo
        ORDER BY inscritos DESC";
$cursos = $conn->query($sql);

// Cabeçalhos para Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=relatorio_cursos.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Conteúdo
echo "ID\tCurso\tInscritos\n";
while($c = $cursos->fetch_assoc()){
    echo $c['id']."\t".$c['titulo']."\t".$c['inscritos']."\n";
}
