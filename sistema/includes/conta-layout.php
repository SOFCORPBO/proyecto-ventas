<?php
// Layout helper para páginas de Contabilidad (Bootstrap 3)
// Evita que se vea "sin estilos" si falta head.php/scripts.php.

if (!function_exists('conta_include_head')) {
    function conta_include_head() {
        $head = __DIR__ . '/head.php';
        if (file_exists($head)) {
            include($head);
            return;
        }

        // Fallback mínimo (si tu proyecto no tiene head.php)
        // Ajusta rutas si tu carpeta de estáticos difiere.
        ?>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo defined('TITULO') ? TITULO : 'Sistema'; ?></title>

        <?php if (defined('ESTATICO')): ?>
            <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/bootstrap.min.css">
            <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/font-awesome.min.css">
            <?php if (file_exists(__DIR__ . '/../../estatico/css/estilos.css')): ?>
                <link rel="stylesheet" href="<?php echo ESTATICO; ?>css/estilos.css">
            <?php endif; ?>
        <?php endif; ?>
        <style>
          body{ background:#f5f6fa; }
        </style>
        <?php
    }
}

if (!function_exists('conta_include_scripts')) {
    function conta_include_scripts() {
        $scripts = __DIR__ . '/scripts.php';
        if (file_exists($scripts)) {
            include($scripts);
            return;
        }

        // Fallback mínimo JS
        if (defined('ESTATICO')) {
            ?>
            <script src="<?php echo ESTATICO; ?>js/jquery.min.js"></script>
            <script src="<?php echo ESTATICO; ?>js/bootstrap.min.js"></script>
            <?php
        }
    }
}
