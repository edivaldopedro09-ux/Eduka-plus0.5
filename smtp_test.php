<?php
$hosts = [
  ['smtp.gmail.com', 587],
  ['smtp.gmail.com', 465]
];

foreach ($hosts as $h) {
  $fp = @fsockopen($h[0], $h[1], $errno, $errstr, 10);
  if (!$fp) {
    echo "Falha ao conectar {$h[0]}:{$h[1]} — $errstr ($errno)<br>";
  } else {
    echo "Conectado com sucesso a {$h[0]}:{$h[1]}<br>";
    fclose($fp);
  }
}
?>
