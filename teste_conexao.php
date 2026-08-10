<?php

include "includes/conexao.php";

if ($conexao) {

    echo "<h2 style='color:green;'>✅ Conexão realizada com sucesso!</h2>";

} else {

    echo "<h2 style='color:red;'>❌ Erro na conexão.</h2>";

}

?>