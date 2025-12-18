<?php
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
use KNCMS\KNCMS;

if (!KNCMS::checkLogin()) { header('Location: '.KNCMS::baseUrl().'/auth'); exit; }
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Cards</title></head>
<body>
  <div id="list"></div>
  <script>
    fetch("/api/card/list").then(r=>r.json()).then(j=>{
      document.getElementById("list").innerHTML =
        (j.cards||[]).map(c=>`<div>#${c.id} - ${c.title}</div>`).join("");
    });
  </script>
</body>
</html>
